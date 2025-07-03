<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Traits\HelperTrait;
use App\Models\{Invoice, InvoiceAdjustments, User};
use App\Services\InvoiceService;
use App\Services\OfficeService;
use App\Services\PanelService;
use App\Services\PostService;
use App\Services\AccessoryService;
use App\Models\{ServiceSetting, InvoicePayments};
use App\Services\AuthorizeNetService;
use App\Services\UserService;
use App\Services\NotificationService;

use DB;

class AccountingUnpaidInvoicesController extends Controller
{

    use HelperTrait;

    protected $invoiceService;
    protected $officeService;
    protected $postService;
    protected $panelService;
    protected $accessoryService;
    protected $authorizeNetService;
    protected $userService;
    protected $notificationService;

    public function __construct(
        InvoiceService $invoiceService,
        OfficeService $officeService,
        PostService $postService,
        PanelService $panelService,
        AuthorizeNetService $authorizeNetService,
        AccessoryService $accessoryService,
        UserService $userService,
        NotificationService $notificationService
    ) {
        $this->invoiceService = $invoiceService;
        $this->officeService = $officeService;
        $this->postService = $postService;
        $this->panelService = $panelService;
        $this->accessoryService = $accessoryService;
        $this->authorizeNetService = $authorizeNetService;
        $this->userService = $userService;
        $this->notificationService = $notificationService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $authUser = auth()->user();

        if ($authUser->role == User::ROLE_SUPER_ADMIN) {
            $offices = $this->officeService->getAll();
            $posts = $this->postService->getOrderByListingOrderAndName();
            $panels = $this->panelService->getOrderByListingOrderAndName();
            $accessories = $this->accessoryService->getOrderByListingOrderAndName();

            $serviceSettings = $service_settings = ServiceSetting::first();

            $data = compact('offices', 'accessories', 'panels', 'posts', 'service_settings', 'serviceSettings');

            return view('accounting.unpaid_invoices.index', $data);
        }

        if ($authUser->role == User::ROLE_OFFICE) {
            $agents = $this->officeService->getAgents($authUser->office->id);
            $posts = $this->postService->getOrderByListingOrderAndName();
            $panels = $this->panelService->getOrderByListingOrderAndName();
            $accessories = $this->accessoryService->getOrderByListingOrderAndName();

            $serviceSettings = $service_settings = ServiceSetting::first();

            $data = compact('agents', 'accessories', 'panels', 'posts', 'service_settings', 'serviceSettings');

            return view('accounting.unpaid_invoices.office.index', $data);
        }

        if ($authUser->role == User::ROLE_AGENT) {
            $agents = $this->officeService->getAgents($authUser->agent->office->id);
            $posts = $this->postService->getOrderByListingOrderAndName();
            $panels = $this->panelService->getOrderByListingOrderAndName();
            $accessories = $this->accessoryService->getOrderByListingOrderAndName();

            $serviceSettings = $service_settings = ServiceSetting::first();

            $data = compact('agents', 'accessories', 'panels', 'posts', 'service_settings', 'serviceSettings');

            return view('accounting.unpaid_invoices.agent.index', $data);
        }

    }

    public function datatable()
    {
        return $this->invoiceService->datatableUnpaidInvoices();
    }

    public function getInvoicePayer($invoiceId)
    {
        return $this->invoiceService->getInvoicePayer($invoiceId);
    }

    public function processPayment(Request $request)
    {
        //dd($request->all());

        $invoice = $this->invoiceService->findById($request->invoice_id);

        if ($invoice->amount == 0) {
            $this->invoiceService->makeInvoiceFullyPaid($invoice);
            return $this->backWithSuccess('Payment processed successfully.');
        }

        if ($invoice->agent_id) {
            $payer = $invoice->agent->user;
        } else {
            $payer = $invoice->office->user;
        }

        //Paying with balance
        if ($request->payment_method == 'balance') {
            $deduct = $payer->balance > $invoice->amount ? $invoice->amount : $payer->balance;

            $paymentData = [
                'invoice_id' => $request->invoice_id,
                'total' =>  $deduct,
                'payment_method' => InvoicePayments::BALANCE
            ];

            $this->invoiceService->createInvoicePayment($paymentData);

            $this->userService->updateBalance($payer, $deduct * (-1));
        }

        $credit = 0;

        if ($request->payment_method == 'check') {
            $amount = $request->amount;

            //If overpaid then change payment amount
            if ($amount > $invoice->amount) {
                $credit = $amount - $invoice->amount;
                $amount = $invoice->amount;
            }

            //Store in invoice_payments and recalculate invoice amount
            $paymentData = [
                'invoice_id' => $request->invoice_id,
                'total' =>  $amount,
                'payment_method' => InvoicePayments::CHECK,
                'check_number' =>  $request->check_number,
                'comments' =>  $request->comments
            ];

            $this->invoiceService->createInvoicePayment($paymentData);

            //If overpaid then credit user balance
            if ($credit > 0) {
                $this->userService->updateBalance($payer, $credit);
            }
        }

        if ($request->payment_method == 'card') {
            $amount = $request->card_payment_amount;

            //If overpaid then change payment amount
            if ($amount > $invoice->amount) {
                $credit = $amount - $invoice->amount;
                $amount = $invoice->amount;
            }

            $invoice->payment_amount = $amount;

            $profile = explode('::', $request->card_profile);

            $fees = 0;
            if ( $request->has('convenience_fee_amount') ) {
                $convenienceFeeAmount = (float) $request->convenience_fee_amount;
                if ($convenienceFeeAmount > 0) {
                    $fees = $convenienceFeeAmount;
                }
            }

            if ($request->payment_type == 'use_card') { //Existing card
                $payment = $this->authorizeNetService->chargeInvoiceCustomerProfileCapture(
                    $profile[0],
                    $profile[1],
                    $invoice,
                    $fees
                );
                //info($payment);
                if ($payment['messages']['resultCode'] == "Error") {
                    //dd($payment['messages']);
                    //info($payment['messages']);
                    if (isset($payment['transactionResponse']['errors'][0]['errorText'])) {
                        return $this->backWithError($payment['transactionResponse']['errors'][0]['errorText']);
                    }
                    if (isset($payment['messages']['message'][0]['text'])) {
                        return $this->backWithError($payment['messages']['message'][0]['text']);
                    }
                    if (isset($payment['messages']['message'][0]['description'])) {
                        return $this->backWithError($payment['messages']['message'][0]['description']);
                    }
                }
                //dd($payment['transactionResponse']);
                $cardType = explode(':', $request->card_info)[0];
                $cardLastFour = substr(explode('-', $request->card_info)[3], 0, 4);
                //$cardExp = str_replace('/', '', substr($request->card_info, -7));
                $paymentProfile = $payment['transactionResponse']['profile']['customerPaymentProfileId'];
            } else {
                /*$billTo['first_name'] = $payer->first_name;
                $billTo['last_name'] = $payer->last_name;
                $billTo['address'] = $payer->address;
                $billTo['city'] = $payer->city;
                $billTo['state'] = $payer->state;
                $billTo['zipcode'] = $payer->zipcode;*/

                //Don't proceed if name, address and zipcode not provided
                if (! $request->billing_name || ! $request->billing_address || ! $request->billing_zip ) {
                    return $this->backWithError('Please fill out all billing fields');
                }

                $getFirstLast = $this->getFirstLastFromName($request->billing_name);
                $billTo['first_name'] = $getFirstLast['firstName'];
                $billTo['last_name'] = $getFirstLast['lastName'];
                $billTo['address'] = $request->billing_address;
                $billTo['city'] = $request->billing_city;
                $billTo['state'] = $request->billing_state;
                $billTo['zipcode'] = substr($request->billing_zip, 0, 5);
                $billTo['email'] = $payer->email;
                $billTo['userId'] = $payer->id;

                //$card_name = trim($request->card_name);
                $card_number = trim($request->card_number);

                $exyear = trim($request->expire_date_year);
                $exmonth = trim($request->expire_date_month);
                $expireDate = "$exyear-$exmonth";
                $card_code = $request->card_code;

                // card info
                $cardInfo = [
                   // "cardName" => $card_name,
                    "cardNumber" => str_replace(' ', '', $card_number),
                    "expirationDate" => $expireDate,
                    "cardCode" => $card_code,
                ];

                //capture payment
                $payment = $this->authorizeNetService->createInvoicePayment($cardInfo, $billTo, $invoice, $fees);
                //info($payment);
                if ($payment['messages']['resultCode'] == "Error") {
                    //dd($payment['messages']);
                    //dd($payment['transactionResponse']['errors'][0]['errorText']);
                    if (isset($payment['transactionResponse']['errors'][0]['errorText'])) {
                        return $this->backWithError($payment['transactionResponse']['errors'][0]['errorText']);
                    }
                    if (isset($payment['messages']['message'][0]['text'])) {
                        return $this->backWithError($payment['messages']['message'][0]['text']);
                    }
                    if (isset($payment['messages']['message'][0]['description'])) {
                        return $this->backWithError($payment['messages']['message'][0]['description']);
                    }
                }

                //Get card info after authorizeNet profile is saved/updated
                $cardLastFour = substr(str_replace(' ', '', $card_number), -4);
                $cardType = $this->getCardTypeFromAuthorizeNet($payer, $cardLastFour);
                //$cardExp = $exmonth.$exyear;
                //Payment profile is already created so just need to get it
                $paymentProfile = $payer->latestPaymentProfile->payment_profile_id;
            }

            $transId = $payment['transactionResponse']['transId'];

            $paymentData = [
                'invoice_id' => $request->invoice_id,
                'total' =>  $amount,
                'payment_method' => InvoicePayments::CREDIT_CARD,
                'card_type' =>  $cardType,
                'card_last_four' => $cardLastFour,
                'transaction_id' => $transId,
                'payment_profile' => $paymentProfile
            ];

            $this->invoiceService->createInvoicePayment($paymentData);

            //If overpaid then credit user balance
            if ($credit > 0) {
                $this->userService->updateBalance($payer, $credit);
            }
        }

        return $this->backWithSuccess('Payment processed successfully.');
    }

    public function getCardTypeFromAuthorizeNet($user, $cardLastFour)
    {
        $storedPaymentProfiles = $user->authorizenet_payment_profiles;
        $authorizeNetCustomerId = $user->authorizenet_profile_id;

        $cardType = 'Visa';
        foreach ($storedPaymentProfiles as $storedPaymentProfile) {
            $paymentProfile = $this->authorizeNetService->getPaymentProfile(
                $authorizeNetCustomerId,
                $storedPaymentProfile->payment_profile_id
            );
            //info($paymentProfile);
            if (isset($paymentProfile['paymentProfile'])) {
                $cardInfo = $paymentProfile['paymentProfile']['payment']['creditCard'];
                $lastFour = str_replace('XXXX', '', $cardInfo['cardNumber']);
                if ($cardLastFour == $lastFour) {
                    $cardType = $cardInfo['cardType'];
                }
            }
        }

        return $cardType;
    }

    public function sendEmail($id)
    {
        $this->notificationService->sendUnpaidInvoiceReminder((int) $id);

        //return email history
        return $this->emailHistory($id);
    }

    public function emailHistory($id)
    {
        return $this->invoiceService->findEmailSentHistoryByInvoiceId($id);
    }

    public function invoiceAdjustments(Request $request)
    {
        //dd($request->all());

        $descriptionArray = $request->invoice_adjustment_description;
        $chargeArray = $request->invoice_adjustment_charge;
        $discountArray = $request->invoice_adjustment_discount;

        foreach ($descriptionArray as $key => $description) {
            if ($chargeArray[$key]) {
                $amount = $chargeArray[$key];

                $adjustment = [
                    'invoice_id' => $request->invoice_id,
                    'description' => $description,
                    'amount' => $amount,
                    'type' => InvoiceAdjustments::DEFAULT_TYPE
                ];

                $this->invoiceService->createInvoiceAdjustment($adjustment);
            }

            if ($discountArray[$key]) {
                $amount = -1 * abs($discountArray[$key]);

                $adjustment = [
                    'invoice_id' => $request->invoice_id,
                    'description' => $description,
                    'amount' => $amount,
                    'type' => InvoiceAdjustments::DEFAULT_TYPE
                ];

                $this->invoiceService->createInvoiceAdjustment($adjustment);
            }

            return $this->backWithSuccess('Adjustment processed successfully.');
        }
    }

    public function removeAgentFromInvoice($agentId, $invoiceId)
    {
        if ( ! $agentId || ! $invoiceId) {
            abort(404);
        }

        $this->invoiceService->removeAgentFromInvoice($agentId, $invoiceId);

        return $this->backWithSuccess('Agent removed successfully.');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $invoice = $this->invoiceService->invoiceView($id);

        return $invoice;
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
