<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\PaymentService;
use App\Services\AuthorizeNetService;
use App\Models\User;
use App\Models\Agent;
use App\Models\Office;
use App\Models\AuthorizenetPaymentProfile;
use Illuminate\Support\Facades\Auth;

class OrderChargeController extends Controller
{
  protected $paymentService;
  protected $authorizeNetService;

  public function __construct(
    PaymentService $paymentService,
    AuthorizeNetService $authorizeNetService
  ) {
    $this->paymentService = $paymentService;
    $this->authorizeNetService = $authorizeNetService;
  }

  /**
   * Get all offices (admin only)
   */
  public function getOffices()
  {
    $user = auth()->user();

    if ($user->role !== User::ROLE_SUPER_ADMIN) {
      return response()->json(['error' => 'Unauthorized'], 403);
    }

    $offices = Office::with('user:id,name')
      ->whereHas('user', function ($query) {
        $query->where('inactive', 0);
      })
      ->get()
      ->map(function ($office) {
        return [
          'id' => $office->id,
          'user_id' => $office->user->id,
          'name' => $office->user->name,
        ];
      });

    return response()->json($offices);
  }

  /**
   * Get agents for an office
   */
  public function getAgentsForOffice($officeId)
  {
    $user = auth()->user();

    // Office can only get their own agents
    if ($user->role === User::ROLE_OFFICE) {
      $office = $user->office;
      if (!$office || $office->id != $officeId) {
        return response()->json(['error' => 'Unauthorized'], 403);
      }
    } elseif ($user->role !== User::ROLE_SUPER_ADMIN) {
      return response()->json(['error' => 'Unauthorized'], 403);
    }

    $agents = Agent::with('user:id,name')
      ->where('agent_office', $officeId)
      ->whereHas('user', function ($query) {
        $query->where('inactive', 0);
      })
      ->get()
      ->map(function ($agent) {
        return [
          'id' => $agent->id,
          'user_id' => $agent->user->id,
          'name' => $agent->user->name,
        ];
      });

    return response()->json($agents);
  }

  /**
   * Get cards based on role and query params
   *
   * Query params:
   *   - office_user_id: office user ID (for admin selecting an office)
   *   - agent_user_id: agent user ID (for loading agent's cards)
   *   - source: 'office' or 'agent' (whose cards to load)
   */
  public function getCards(Request $request)
  {
    $user = auth()->user();
    $officeUserId = $request->query('office_user_id');
    $agentUserId = $request->query('agent_user_id');
    $source = $request->query('source', 'auto');

    // Determine which cards to fetch based on role and params
    $cards = collect();

    if ($user->role === User::ROLE_AGENT) {
      // Agent: show own cards only
      $cards = $this->paymentService->getUniquePaymentProfiles($user->id);
    } elseif ($user->role === User::ROLE_OFFICE) {
      if ($agentUserId && $source === 'agent') {
        // Office selected an agent - show agent's cards visible to office
        $cards = $this->paymentService->getPaymentProfilesForOfficeToCharge(
          (int) $agentUserId,
          $user->id
        );
      } else {
        // Office: show own cards
        $cards = $this->paymentService->getUniquePaymentProfiles($user->id);
      }
    } elseif ($user->role === User::ROLE_SUPER_ADMIN) {
      if ($agentUserId && $source === 'agent') {
        // Admin selected an agent - show all agent cards
        $cards = AuthorizenetPaymentProfile::where('user_id', (int) $agentUserId)
          ->distinct()
          ->select('payment_profile_id', 'authorizenet_profile_id', 'user_id', 'office_card_visible_agents')
          ->get();
      } elseif ($officeUserId) {
        // Admin selected an office - show office's own cards
        $cards = $this->paymentService->getUniquePaymentProfiles((int) $officeUserId);
      }
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
          'authorizenet_profile_id' => $card->authorizenet_profile_id,
          'cardNumber' => $cardInfo['cardNumber'],
          'cardType' => $cardInfo['cardType'],
          'expDate' => $cardInfo['expirationDate'],
          'value' => $card->authorizenet_profile_id . '::' . $card->payment_profile_id,
        ];
      }
    }

    return response()->json($returnData);
  }
}
