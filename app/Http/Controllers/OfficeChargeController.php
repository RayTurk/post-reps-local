<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Traits\HelperTrait;
use App\Services\PaymentService;
use App\Services\AuthorizeNetService;
use App\Services\AgentService;
use App\Http\Requests\ProcessOfficeCharge;
use App\Models\User;
use App\Models\Agent;
use App\Models\Office;
use App\Models\AuthorizenetPaymentProfile;
use App\Models\PaymentHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Exception;

class OfficeChargeController extends Controller
{
  use HelperTrait;

  protected $paymentService;
  protected $authorizeNetService;
  protected $agentService;
  protected $paymentModel;

  public function __construct(
    PaymentService $paymentService,
    AuthorizeNetService $authorizeNetService,
    AgentService $agentService,
    AuthorizenetPaymentProfile $paymentModel
  ) {
    $this->paymentService = $paymentService;
    $this->authorizeNetService = $authorizeNetService;
    $this->agentService = $agentService;
    $this->paymentModel = $paymentModel;
  }

  /**
   * Display the charge agent form
   */
  public function index()
  {
    $user = auth()->user();

    // Allow Office and Super Admin access
    if (!in_array($user->role, [User::ROLE_OFFICE, User::ROLE_SUPER_ADMIN])) {
      abort(403, 'Unauthorized action.');
    }

    if ($user->role === User::ROLE_SUPER_ADMIN) {
      // Admin can charge any agent from any office
      $agents = Agent::with(['user', 'office.user'])
        ->whereHas('user', function ($query) {
          $query->where('inactive', 0);
        })
        ->get();

      return view('accounting.office.charge-agent', compact('agents'));
    }

    // Office can only charge their own agents
    $office = $user->office;
    $agents = $this->agentService->getOfficeAgents($office->id);

    return view('accounting.office.charge-agent', compact('agents'));
  }

  /**
   * Get agent's saved cards
   */
  public function getAgentCards($agentId)
  {
    $user = auth()->user();

    // Allow Office and Super Admin access
    if (!in_array($user->role, [User::ROLE_OFFICE, User::ROLE_SUPER_ADMIN])) {
      return response()->json(['error' => 'Unauthorized'], 403);
    }

    $agent = Agent::with('user')->find($agentId);

    if (!$agent) {
      return response()->json(['error' => 'Agent not found'], 404);
    }

    // Verify permissions
    if ($user->role === User::ROLE_OFFICE && $agent->agent_office !== $user->office->id) {
      return response()->json(['error' => 'Unauthorized - Agent does not belong to your office'], 403);
    }

    // Get cards - for Super Admin, show all cards; for Office, show shared cards
    if ($user->role === User::ROLE_SUPER_ADMIN) {
      // Super Admin can see all agent cards
      $cards = AuthorizenetPaymentProfile::where('user_id', $agent->user->id)
        ->distinct()
        ->select('payment_profile_id', 'authorizenet_profile_id', 'user_id')
        ->get();
    } else {
      // Office can see cards shared with them or owned by agent
      $cards = $this->paymentService->getPaymentProfilesForOfficeToCharge(
        $agent->user->id,
        $user->id
      );
    }

    $returnData = [];

    foreach ($cards as $card) {
      $paymentProfile = $this->authorizeNetService->getPaymentProfile(
        $card->authorizenet_profile_id,
        $card->payment_profile_id
      );

      if (isset($paymentProfile['paymentProfile'])) {
        $cardInfo = $paymentProfile['paymentProfile']['payment']['creditCard'];
        $returnData[] = [
          'payment_profile_id' => $card->payment_profile_id,
          'cardNumber' => str_replace('XXXX', 'XXXX-', $cardInfo['cardNumber']),
          'cardType' => $cardInfo['cardType'],
          'expDate' => $this->formatExpirationDate($cardInfo['expirationDate']),
          'authorizenet_profile_id' => $card->authorizenet_profile_id,
          'user_id' => $card->user_id
        ];
      }
    }

    return response()->json($returnData);
  }

  /**
   * Process the charge
   */
  public function processCharge(ProcessOfficeCharge $request)
  {
    $user = auth()->user();

    // Allow Office and Super Admin access
    if (!in_array($user->role, [User::ROLE_OFFICE, User::ROLE_SUPER_ADMIN])) {
      return $this->backWithError('Unauthorized action.');
    }

    $data = $request->validated();

    DB::beginTransaction();

    try {
      $agent = Agent::with('user')->find($data['agent_id']);

      if (!$agent) {
        throw new Exception('Agent not found');
      }

      // Verify permissions
      if ($user->role === User::ROLE_OFFICE && $agent->agent_office !== $user->office->id) {
        throw new Exception('Unauthorized - Agent does not belong to your office');
      }

      // Get billing information
      $billTo = [
        'first_name' => $agent->user->first_name,
        'last_name' => $agent->user->last_name,
        'address' => $agent->user->address,
        'city' => $agent->user->city,
        'state' => $agent->user->state,
        'zipcode' => $agent->user->zipcode,
      ];

      $amount = $data['amount'];
      $description = $data['description'] ?? 'Office charge';

      // Process payment based on method
      if ($data['payment_method'] === 'saved_card') {
        // Verify the card access
        if ($user->role === User::ROLE_SUPER_ADMIN) {
          // Admin can use any card belonging to the agent
          $paymentProfile = AuthorizenetPaymentProfile::where('payment_profile_id', $data['payment_profile_id'])
            ->where('user_id', $agent->user->id)
            ->first();
        } else {
          // Office can only use cards shared with them or owned by agent
          $paymentProfile = AuthorizenetPaymentProfile::where('payment_profile_id', $data['payment_profile_id'])
            ->where(function ($query) use ($agent, $user) {
              $query->where('user_id', $agent->user->id)
                ->where(function ($q) use ($user) {
                  $q->whereNull('card_shared_with')
                    ->orWhere('card_shared_with', $user->id);
                });
            })
            ->first();
        }

        if (!$paymentProfile) {
          throw new Exception('Invalid payment profile');
        }

        // Charge using saved card
        $response = $this->authorizeNetService->chargeCustomerProfile(
          $paymentProfile->authorizenet_profile_id,
          $paymentProfile->payment_profile_id,
          $amount,
          $description
        );
      } else {
        // New card
        $cardInfo = [
          'cardNumber' => str_replace(' ', '', $data['card_number']),
          'expirationDate' => $data['expire_year'] . '-' . str_pad($data['expire_month'], 2, '0', STR_PAD_LEFT),
          'cardCode' => $data['card_code'],
        ];

        // Process charge
        $response = $this->authorizeNetService->chargeCard($cardInfo, $billTo, $amount, $description);

        // Save card if requested
        if (!empty($data['save_card'])) {
          $cardSharedWith = $user->role === User::ROLE_OFFICE ? $user->id : null;
          $this->saveNewCard($agent->user, $cardInfo, $billTo, $cardSharedWith);
        }
      }

      // Check response
      if (
        !isset($response->transactionResponse) ||
        !in_array($response->transactionResponse->responseCode, ['1', '4'])
      ) {

        $errorMessage = isset($response->transactionResponse->errors[0])
          ? $response->transactionResponse->errors[0]->errorText
          : 'Transaction failed';

        throw new Exception($errorMessage);
      }

      // Record payment history
      $this->recordPaymentHistory([
        'office_id' => $user->role === User::ROLE_OFFICE ? $user->office->id : $agent->agent_office,
        'agent_id' => $agent->id,
        'amount' => $amount,
        'description' => $description,
        'transaction_id' => $response->transactionResponse->transId ?? null,
        'auth_code' => $response->transactionResponse->authCode ?? null,
        'processed_by' => $user->id,
        'card_info' => $data['payment_method'] === 'saved_card'
          ? 'Saved card ending in ' . substr($data['payment_profile_id'], -4)
          : 'Card ending in ' . substr($data['card_number'], -4),
      ]);

      DB::commit();

      return $this->backWithSuccess("Successfully charged $" . number_format($amount, 2) . " to {$agent->user->name}'s card.");
    } catch (Exception $e) {
      DB::rollback();
      logger()->error('Office charge error: ' . $e->getMessage());
      return $this->backWithError($e->getMessage());
    }
  }

  /**
   * Save new card for agent
   */
  private function saveNewCard($agentUser, $cardInfo, $billTo, $officeUserId)
  {
    // Create or get customer profile
    if (!$agentUser->authorizenet_profile_id) {
      $customerProfile = $this->authorizeNetService->createCustomerProfile([
        'email' => $agentUser->email,
        'description' => $agentUser->name,
        'merchantCustomerId' => $agentUser->id,
      ]);

      // createCustomerProfile returns an array
      if (isset($customerProfile['customerProfileId'])) {
        $agentUser->authorizenet_profile_id = $customerProfile['customerProfileId'];
        $agentUser->save();
      }
    }

    // Add payment profile
    if ($agentUser->authorizenet_profile_id) {
      $paymentProfile = $this->authorizeNetService->createPaymentProfile(
        $cardInfo,
        $billTo,
        $agentUser->authorizenet_profile_id
      );

      // createPaymentProfile returns an array
      if (isset($paymentProfile['customerPaymentProfileId'])) {
        AuthorizenetPaymentProfile::create([
          'user_id' => $agentUser->id,
          'payment_profile_id' => $paymentProfile['customerPaymentProfileId'],
          'authorizenet_profile_id' => $agentUser->authorizenet_profile_id,
          'card_shared_with' => $officeUserId,
        ]);
      }
    }
  }

  /**
   * Record payment history
   */
  private function recordPaymentHistory($data)
  {
    // You can create a PaymentHistory model or use existing payment tracking
    // For now, logging the transaction
    logger()->info('Office charge processed', $data);

    // If you have a PaymentHistory model, uncomment:
    // PaymentHistory::create($data);
  }

  /**
   * Format expiration date
   */
  private function formatExpirationDate($date)
  {
    $parts = explode('-', $date);
    return isset($parts[1]) ? "{$parts[1]}/{$parts[0]}" : $date;
  }
}
