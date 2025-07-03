<?php

namespace App\Http\Controllers;

use App\Http\Requests\{
    CreatePayment,
    CreateRepairPayment,
    CreateRemovalPayment,
    CreateDeliveryPayment,
    AddCard
};
use App\Http\Traits\HelperTrait;
use App\Jobs\RemoveCardJob;
use App\Models\Accessory;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Models\Agent;
use App\Models\Office;
use App\Services\AuthorizeNetService;
use App\Services\OrderService;
use App\Services\NotificationService;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Session;
use App\Models\{CardRejectionCounter, RepairOrder, RemovalOrder, DeliveryOrder};
use App\Services\AccessoryService;
use App\Services\OfficeService;
use App\Services\PanelService;
use App\Services\PostService;
use App\Models\ServiceSetting;

class PaymentController extends Controller
{
    use HelperTrait;

    protected $paymentService;

    protected $orderService;

    protected $officeService;
    protected $postService;
    protected $panelService;
    protected $accessoryService;

    protected $authorizeNetService;
    protected $notificationService;

    protected $rejectionCodes = [
        '2',
        '3',
        '4',
        '27',
        '44',
        '45',
        '65',
        '250',
        '251',
        '254',
    ];

    public function __construct(
        PaymentService $paymentService,
        OrderService $orderService,
        AuthorizeNetService $authorizeNetService,
        NotificationService $notificationService,
        OfficeService $officeService,
        PostService $postService,
        PanelService $panelService,
        AccessoryService $accessoryService
    ) {
        $this->paymentService = $paymentService;

        $this->orderService = $orderService;

        $this->authorizeNetService = $authorizeNetService;

        $this->notificationService = $notificationService;

        $this->officeService = $officeService;

        $this->postService = $postService;

        $this->panelService = $panelService;

        $this->accessoryService = $accessoryService;
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
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

    public function pay(CreatePayment $request)
    {
        $data = $request->all();

        //store payment
        $order = $this->orderService->findById($request->order_id);

        //Get card owner and billing details
        $billing = $this->paymentService->getBillingDetails($order);
        info("getBillingDetails for install order $order->order_number");
        info($billing);


        $cardVisibility = $billing['card_shared_with'];
        $cardOwner = $billing['cardOwner'];
        $billTo['email'] = $billing['email'];

        if ($data['payment_type'] == 'use_card') { //Existing card
            //Authorize payment through customer profile
            $profile = explode('::', $data['card_profile']);

            $payment = $this->authorizeNetService->chargeCustomerProfileAuthOnly(
                $profile[0] ?? $cardOwner->authorizenet_profile_id,
                $profile[1],
                $order,
                Order::INSTALL_ORDER,
                $cardVisibility,
                $cardOwner
            );
            //info($payment);
            if (
                $payment['messages']['resultCode'] == "Error"
                || empty($payment['transactionResponse']['authCode'])
            ) {

                //Transaction declined error
                foreach($this->rejectionCodes as $key => $code){
                    if($code == substr($payment['transactionResponse']['errors'][0]['errorCode'], 0, strlen($code))){
                        return $this->paymentService->handleCardRejected($order->office_id, $order->agent_id, $payment, $profile[1], $profile[0]);
                    }
                }

                if (empty($payment['transactionResponse']['authCode'])) {
                    $payment['messages']['message'][0]['text'] = 'The card selected has been entered incorrectly or has been declined. Please check the card numbers or enter a different card. If a card is declined three consecutive times, the card will be removed from our system and would need to be reentered.';
                }

                return $payment;
            } else {
                //dd($payment);
                $this->paymentService->resetCardRejectionCounter(substr($payment['transactionResponse']['accountNumber'], -4), $order->office_id, $order->agent_id);
                $order->auth_transaction_id = $payment['transactionResponse']['transId'];
                $order->card_last_four = substr($payment['transactionResponse']['accountNumber'], -4);
                $order->card_type = $payment['transactionResponse']['accountType'];
                $order->authorized_amount = $order->total;

                $this->savePaymentAndUpdateOrder($order);

                Session::flash("success", "Payment submitted successfully. Thank you!");
            }
        } else { //New card
            //card numbers
            //$card_name = trim($data['card_name']);
            $card_number = trim($data['card_number']);

            $exyear = trim($data['expire_date_year']);
            $exmonth = trim($data['expire_date_month']);
            $expireDate = "$exyear-$exmonth";
            $card_code = $data['card_code'];

            // card info
            $cardInfo = [
                //"cardName" => $card_name,
                "cardNumber" => str_replace(' ', '', $card_number),
                "expirationDate" => $expireDate,
                "cardCode" => $card_code,
            ];

            //Don't proceed if name, address and zipcode not provided
            if (! $request->billing_name || ! $request->billing_address || ! $request->billing_zip ) {
                $payment['messages']['resultCode'] = "Error";
                $payment['messages']['message'][0]['text'] = 'Please fill out all billing fields.';

                return $payment;
            }

            //Don't proceed if card expires before the service date
            if ((new Carbon($expireDate))->endOfMonth() < (new Carbon($order->desired_date))->endOfDay()) {
                $payment['messages']['resultCode'] = "Error";
                $payment['messages']['message'][0]['text'] = 'The entered card expires before the service date, please use a different card.';

                return $payment;
            }

            $getFirstLast = $this->getFirstLastFromName($request->billing_name);
            $billTo['first_name'] = $getFirstLast['firstName'];
            $billTo['last_name'] = $getFirstLast['lastName'];
            $billTo['address'] = $request->billing_address;
            $billTo['city'] = $request->billing_city;
            $billTo['state'] = $request->billing_state;
            $billTo['zipcode'] = substr($request->billing_zip, 0, 5);

            //authorize payment
            info(config('authorizenet.login_id'));
            info(config('authorizenet.transaction_key'));
            $payment = $this->authorizeNetService->authrorizeCardFromProfile(
                $cardInfo,
                $billTo,
                $order,
                Order::INSTALL_ORDER,
                $cardVisibility,
                $cardOwner
            );
            //dd($payment);
            if (
                $payment['messages']['resultCode'] == "Error"
                || empty($payment['transactionResponse']['authCode'] )
            ) {
                if (empty($payment['transactionResponse']['authCode'])) {
                    $payment['messages']['message'][0]['text'] = 'The card selected has been entered incorrectly or has been declined. Please check the card numbers or enter a different card. If a card is declined three consecutive times, the card will be removed from our system and would need to be reentered.';
                }

                return $payment;
            } else {
                $order->auth_transaction_id = $payment['transactionResponse']['transId'];
                $order->card_last_four = substr($payment['transactionResponse']['accountNumber'], -4);
                $order->card_type = $payment['transactionResponse']['accountType'];
                $order->authorized_amount = $order->total;

                $this->savePaymentAndUpdateOrder($order);

                Session::flash("success", "Payment submitted successfully. Thank you!");
            }
        }

        try {
            $this->notificationService->orderCreated($order);
        } catch (Throwable $e) {
            logger()->error($e->getMessage());
        }

        return $payment;
    }

    public function savePaymentAndUpdateOrder(Order $order)
    {
        /*$payment = $this->paymentService->create([
            "order_id" => $order->id,
            "paid_by" => auth()->id(),
            "office_id" => $order->office_id,
            "agent_id" => $order->agent_id,
            "amount" => $order->total
        ]);*/

        //
        $order->status = Order::STATUS_RECEIVED;
        $order->action_needed = false;

        // if property type is "New construction" or "vacant land" and no files set status imcomplete
        if ($order->property_type == Order::NEW_CONSTRUCTION || $order->property_type == Order::VACANT_LAND) {
            if ($order->platMapFiles->first() == null) {
                $order->status = Order::STATUS_INCOMPLETE;
                $order->action_needed = true;
                $order->save();
            }
        }
        //if accessories files no uploaded set status incomplete
        foreach ($order->accessories as $accessory) {
            $accessory = $accessory->accessory;
            if ($accessory->prompt) {
                $fileFound = $order->files->where('accessory_id', $accessory->id)->first();
                if ($fileFound == null) {
                    $order->status = Order::STATUS_INCOMPLETE;
                    $order->action_needed = true;
                    $order->save();
                }
            }
        }

        $order->save();
    }

    public function getSavedCards(User $user)
    {
        $storedPaymentProfiles = $this->paymentService->getUniquePaymentProfiles((int) $user->id);
        $authorizeNetCustomerId = $user->authorizenet_profile_id;
        info($storedPaymentProfiles);

        $returnData = [];
        if ($storedPaymentProfiles->isNotEmpty()) {
            foreach ($storedPaymentProfiles as $storedPaymentProfile) {
                $paymentProfile = $this->authorizeNetService->getPaymentProfile(
                    $authorizeNetCustomerId,
                    $storedPaymentProfile->payment_profile_id
                );
                info("Payment profile from getSavedCards for user $user->name");
                info($paymentProfile);
                if (isset($paymentProfile['paymentProfile'])) {
                    $cardinfo = $paymentProfile['paymentProfile']['payment']['creditCard'];

                    if ((new Carbon($cardinfo['expirationDate']))->endOfMonth() < (new Carbon(now()))->endOfDay()) {
                        RemoveCardJob::dispatch($paymentProfile['paymentProfile']['customerPaymentProfileId'], $authorizeNetCustomerId);
                        continue;
                    }

                    $returnData[$storedPaymentProfile->payment_profile_id]['cardNumber'] = str_replace('XXXX', 'XXXX-', $cardinfo['cardNumber']);
                    $returnData[$storedPaymentProfile->payment_profile_id]['cardType'] = $cardinfo['cardType'];
                    $expDateArray = explode('-', $cardinfo['expirationDate']);
                    $expDate = "{$expDateArray[1]}/{$expDateArray[0]}";
                    $returnData[$storedPaymentProfile->payment_profile_id]['expDate'] = $expDate;
                    $returnData[$storedPaymentProfile->payment_profile_id]['customerProfileId'] = $storedPaymentProfile->authorizenet_profile_id;
                }
            }
        }

        return response()->json($returnData);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Payment  $payment
     * @return \Illuminate\Http\Response
     */
    public function show(Payment $payment)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Payment  $payment
     * @return \Illuminate\Http\Response
     */
    public function edit(Payment $payment)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Payment  $payment
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Payment $payment)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Payment  $payment
     * @return \Illuminate\Http\Response
     */
    public function destroy(Payment $payment)
    {
        //
    }

    public function payRepairOrder(CreateRepairPayment $request)
    {
        $data = $request->all();

        //store payment
        $repairOrder = $this->orderService->findRepairOrderById($request->repair_order_id);

        //Get card owner and billing details
        $billing = $this->paymentService->getBillingDetails($repairOrder->order);

        $cardVisibility = $billing['card_shared_with'];
        $cardOwner = $billing['cardOwner'];
        $billTo['email'] = $billing['email'];

        if ($data['repair_payment_type'] == 'use_card') { //Existing card
            //Authorize payment through customer profile
            $profile = explode('::', $data['repair_card_profile']);

            $payment = $this->authorizeNetService->chargeCustomerProfileAuthOnly(
                $profile[0] ?? $cardOwner->authorizenet_profile_id,
                $profile[1],
                $repairOrder,
                Order::REPAIR_ORDER,
                $cardVisibility,
                $cardOwner
            );

            if (
                $payment['messages']['resultCode'] == "Error"
                || empty($payment['transactionResponse']['authCode'] )
            ) {

                //Transaction declined error
                foreach($this->rejectionCodes as $key => $code){
                    if($code == substr($payment['transactionResponse']['errors'][0]['errorCode'], 0, strlen($code))){
                        return $this->paymentService->handleCardRejected($repairOrder->order->office_id, $repairOrder->order->agent_id, $payment, $profile[1], $profile[0]);
                    }
                }

                if (empty($payment['transactionResponse']['authCode'])) {
                    $payment['messages']['message'][0]['text'] = 'The card selected has been entered incorrectly or has been declined. Please check the card numbers or enter a different card. If a card is declined three consecutive times, the card will be removed from our system and would need to be reentered.';
                }

                return $payment;
            } else {
                $this->paymentService->resetCardRejectionCounter(substr($payment['transactionResponse']['accountNumber'], -4), $repairOrder->order->office_id, $repairOrder->order->agent_id);
                $repairOrder->auth_transaction_id = $payment['transactionResponse']['transId'];
                $repairOrder->card_last_four = substr($payment['transactionResponse']['accountNumber'], -4);
                $repairOrder->card_type = $payment['transactionResponse']['accountType'];
                $repairOrder->authorized_amount = $repairOrder->total;

                $this->saveRepairPaymentAndUpdateOrder($repairOrder);

                Session::flash("success", "Payment submitted successfully. Thank you!");
            }
        } else { //New card
            //card numbers
            //$card_name = trim($data['repair_card_name']);
            $card_number = trim($data['repair_card_number']);
            $card_numbers = explode(' ', $card_number);
            //$last_number = encrypt(end($card_numbers));

            $exyear = trim($data['repair_expire_date_year']);
            $exmonth = trim($data['repair_expire_date_month']);
            $expireDate = "$exyear-$exmonth";
            $card_code = $data['repair_card_code'];

            // card info
            $cardInfo = [
                //"cardName" => $card_name,
                "cardNumber" => str_replace(' ', '', $card_number),
                "expirationDate" => $expireDate,
                "cardCode" => $card_code,
            ];

            //Don't proceed if name, address and zipcode not provided
            if (! $request->repair_billing_name || ! $request->repair_billing_address || ! $request->repair_billing_zip ) {
                $payment['messages']['resultCode'] = "Error";
                $payment['messages']['message'][0]['text'] = 'Please fill out all billing fields.';

                return $payment;
            }

            //Don't proceed if card expires before the service date
            if ((new Carbon($expireDate))->endOfMonth() < (new Carbon($repairOrder->service_date))->endOfDay()) {
                $payment['messages']['resultCode'] = "Error";
                $payment['messages']['message'][0]['text'] = 'The entered card expires before the service date, please use a different card.';

                return $payment;
            }

            $getFirstLast = $this->getFirstLastFromName($request->repair_billing_name);
            $billTo['first_name'] = $getFirstLast['firstName'];
            $billTo['last_name'] = $getFirstLast['lastName'];
            $billTo['address'] = $request->repair_billing_address;
            $billTo['city'] = $request->repair_billing_city;
            $billTo['state'] = $request->repair_billing_state;
            $billTo['zipcode'] = substr($request->repair_billing_zip, 0, 5);

            //authorize payment
            $payment = $this->authorizeNetService->authrorizeCardFromProfile(
                $cardInfo,
                $billTo,
                $repairOrder,
                Order::REPAIR_ORDER,
                $cardVisibility,
                $cardOwner
            );

            if (
                $payment['messages']['resultCode'] == "Error"
                || empty($payment['transactionResponse']['authCode'] )
            ) {
                if (empty($payment['transactionResponse']['authCode'])) {
                    $payment['messages']['message'][0]['text'] = 'The card selected has been entered incorrectly or has been declined. Please check the card numbers or enter a different card. If a card is declined three consecutive times, the card will be removed from our system and would need to be reentered.';
                }

                return $payment;
            } else {
                $repairOrder->auth_transaction_id = $payment['transactionResponse']['transId'];
                $repairOrder->card_last_four = substr($payment['transactionResponse']['accountNumber'], -4);
                $repairOrder->card_type = $payment['transactionResponse']['accountType'];
                $repairOrder->authorized_amount = $repairOrder->total;

                $this->saveRepairPaymentAndUpdateOrder($repairOrder);

                Session::flash("success", "Payment submitted successfully. Thank you!");
            }
        }

        try {
            $this->notificationService->repairOrderCreated($repairOrder);
        } catch (Throwable $e) {
            logger()->error($e->getMessage());
        }

        return $payment;
    }

    public function saveRepairPaymentAndUpdateOrder(RepairOrder $repairOrder)
    {
        /*$payment = $this->paymentService->createRepairPayment([
            "repair_order_id" => $repairOrder->id,
            "paid_by" => auth()->id(),
            "office_id" => $repairOrder->order->office_id,
            "agent_id" => $repairOrder->order->agent_id,
            "amount" => $repairOrder->total
        ]);*/

        //
        $repairOrder->status = RepairOrder::STATUS_RECEIVED;
        $repairOrder->action_needed = false;
        $repairOrder->save();
    }

    public function payRemovalOrder(CreateRemovalPayment $request)
    {
        $data = $request->all();

        //store payment
        $removalOrder = $this->orderService->findRemovalOrderById($request->removal_order_id);

        //Get card owner and billing details
        $billing = $this->paymentService->getBillingDetails($removalOrder->order);
        info("getBillingDetails for removal order $removalOrder->order_number");
        info($billing);

        $cardVisibility = $billing['card_shared_with'];
        $cardOwner = $billing['cardOwner'];
        $billTo['email'] = $billing['email'];

        if ($data['removal_payment_type'] == 'use_card') { //Existing card
            //Authorize payment through customer profile
            $profile = explode('::', $data['removal_card_profile']);

            $payment = $this->authorizeNetService->chargeCustomerProfileAuthOnly(
                $profile[0] ?? $cardOwner->authorizenet_profile_id,
                $profile[1],
                $removalOrder,
                Order::REMOVAL_ORDER,
                $cardVisibility,
                $cardOwner
            );

            if (
                $payment['messages']['resultCode'] == "Error"
                || empty($payment['transactionResponse']['authCode'] )
            ) {

                //Transaction declined error
                foreach($this->rejectionCodes as $key => $code){
                    if($code == substr($payment['transactionResponse']['errors'][0]['errorCode'], 0, strlen($code))){
                        return $this->paymentService->handleCardRejected($removalOrder->order->office_id, $removalOrder->order->agent_id, $payment, $profile[1], $profile[0]);
                    }
                }

                if (empty($payment['transactionResponse']['authCode'])) {
                    $payment['messages']['message'][0]['text'] = 'The card selected has been entered incorrectly or has been declined. Please check the card numbers or enter a different card. If a card is declined three consecutive times, the card will be removed from our system and would need to be reentered.';
                }

                return $payment;
            } else {
                $this->paymentService->resetCardRejectionCounter(substr($payment['transactionResponse']['accountNumber'], -4), $removalOrder->order->office_id, $removalOrder->order->agent_id);
                $removalOrder->auth_transaction_id = $payment['transactionResponse']['transId'];
                $removalOrder->card_last_four = substr($payment['transactionResponse']['accountNumber'], -4);
                $removalOrder->card_type = $payment['transactionResponse']['accountType'];
                $removalOrder->authorized_amount = $removalOrder->total;

                $data['authorizenet_profile_id'] = $payment['transactionResponse']['profile']['customerProfileId'];
                $data['payment_profile_id'] = $payment['transactionResponse']['profile']['customerPaymentProfileId'];

                $this->saveRemovalPaymentAndUpdateOrder($removalOrder, $data, $cardOwner);

                Session::flash("success", "Payment submitted successfully. Thank you!");
            }
        } else { //New card
            //card numbers
            //$card_name = trim($data['removal_card_name']);
            $card_number = trim($data['removal_card_number']);
            $card_numbers = explode(' ', $card_number);
            //$last_number = encrypt(end($card_numbers));

            $exyear = trim($data['removal_expire_date_year']);
            $exmonth = trim($data['removal_expire_date_month']);
            $expireDate = "$exyear-$exmonth";
            $card_code = $data['removal_card_code'];

            // card info
            $cardInfo = [
                //"cardName" => $card_name,
                "cardNumber" => str_replace(' ', '', $card_number),
                "expirationDate" => $expireDate,
                "cardCode" => $card_code,
            ];

            //Don't proceed if name, address and zipcode not provided
            if (! $request->removal_billing_name || ! $request->removal_billing_address || ! $request->removal_billing_zip ) {
                $payment['messages']['resultCode'] = "Error";
                $payment['messages']['message'][0]['text'] = 'Please fill out all billing fields.';

                return $payment;
            }

            //Don't proceed if card expires before the service date
            if ((new Carbon($expireDate))->endOfMonth() < (new Carbon($removalOrder->service_date))->endOfDay()) {
                $payment['messages']['resultCode'] = "Error";
                $payment['messages']['message'][0]['text'] = 'The entered card expires before the service date, please use a different card.';

                return $payment;
            }

            $getFirstLast = $this->getFirstLastFromName($request->removal_billing_name);
            $billTo['first_name'] = $getFirstLast['firstName'];
            $billTo['last_name'] = $getFirstLast['lastName'];
            $billTo['address'] = $request->removal_billing_address;
            $billTo['city'] = $request->removal_billing_city;
            $billTo['state'] = $request->removal_billing_state;
            $billTo['zipcode'] = substr($request->removal_billing_zip, 0, 5);

            //authorize payment
            $payment = $this->authorizeNetService->authrorizeCardFromProfile(
                $cardInfo,
                $billTo,
                $removalOrder,
                Order::REMOVAL_ORDER,
                $cardVisibility,
                $cardOwner
            );

            if (
                $payment['messages']['resultCode'] == "Error"
                || empty($payment['transactionResponse']['authCode'] )
            ) {
                if (empty($payment['transactionResponse']['authCode'])) {
                    $payment['messages']['message'][0]['text'] = 'The card selected has been entered incorrectly or has been declined. Please check the card numbers or enter a different card. If a card is declined three consecutive times, the card will be removed from our system and would need to be reentered.';
                }

                return $payment;
            } else {
                $removalOrder->auth_transaction_id = $payment['transactionResponse']['transId'];
                $removalOrder->card_last_four = substr($payment['transactionResponse']['accountNumber'], -4);
                $removalOrder->card_type = $payment['transactionResponse']['accountType'];
                $removalOrder->authorized_amount = $removalOrder->total;

                $data['authorizenet_profile_id'] = $payment['transactionResponse']['profile']['customerProfileId'];
                $data['payment_profile_id'] = $payment['transactionResponse']['profile']['customerPaymentProfileId'];

                $this->saveRemovalPaymentAndUpdateOrder($removalOrder, $data, $cardOwner);

                Session::flash("success", "Payment submitted successfully. Thank you!");
            }
        }

        try {
            $this->notificationService->removalOrderCreated($removalOrder);
        } catch (Throwable $e) {
            logger()->error($e->getMessage());
        }

        return $payment;
    }

    public function saveRemovalPaymentAndUpdateOrder(RemovalOrder $removalOrder, $data, $cardOwner)
    {
        $removalOrder->status = RemovalOrder::STATUS_RECEIVED;
        $removalOrder->action_needed = false;
        $removalOrder->save();

        if ($data['multiplePosts'] == 'true') {
            $othersOrders = $this->orderService->getOthersRemovalOrdersSameProperty($removalOrder);
            foreach ($othersOrders as $otherOrder) {
                $otherOrder->status = $removalOrder->status;
                $otherOrder->auth_transaction_id = $removalOrder->auth_transaction_id;
                $otherOrder->card_last_four = $removalOrder->card_last_four;
                $otherOrder->card_type = $removalOrder->card_type;
                $otherOrder->authorized_amount = $removalOrder->authorized_amount;
                $otherOrder->action_needed = false;
                $otherOrder->save();

                //LOOKS LIKE THIS IS NOT BEING USED
                /*$this->paymentService->createRemovalPayment([
                    "removal_order_id" => $otherOrder->id,
                    "paid_by" => auth()->id(),
                    "office_id" => $otherOrder->order->office_id,
                    "agent_id" => $otherOrder->order->agent_id,
                    "amount" => $otherOrder->total
                ]);*/

                //Create payment profile for the order
                $profileData['payment_profile_id'] = $data['payment_profile_id'];
                $profileData['authorizenet_profile_id'] = $data['authorizenet_profile_id'];
                $profileData['user_id'] = $cardOwner->id;
                $profileData['order_id'] = $otherOrder->id;
                $profileData['order_type'] = Order::REMOVAL_ORDER;

                $this->paymentService->createAuthorizenetPaymentProfile($profileData);
            }
        }
    }

    public function payDeliveryOrder(CreateDeliveryPayment $request)
    {
        $data = $request->all();

        //store payment
        $deliveryOrder = $this->orderService->findDeliveryOrderById($request->delivery_order_id);

        //Get card owner and billing details
        $billing = $this->paymentService->getBillingDetails($deliveryOrder);

        $cardVisibility = $billing['card_shared_with'];
        $cardOwner = $billing['cardOwner'];
        $billTo['email'] = $billing['email'];

        if ($data['delivery_payment_type'] == 'use_card') { //Existing card
            //Authorize payment through customer profile
            $profile = explode('::', $data['delivery_card_profile']);

            $payment = $this->authorizeNetService->chargeCustomerProfileAuthOnly(
                $profile[0] ?? $cardOwner->authorizenet_profile_id,
                $profile[1],
                $deliveryOrder,
                Order::DELIVERY_ORDER,
                $cardVisibility,
                $cardOwner
            );

            if (
                $payment['messages']['resultCode'] == "Error"
                || empty($payment['transactionResponse']['authCode'] )
            ) {

                //Transaction declined error
                foreach($this->rejectionCodes as $key => $code){
                    if($code == substr($payment['transactionResponse']['errors'][0]['errorCode'], 0, strlen($code))){
                        return $this->paymentService->handleCardRejected($deliveryOrder->office_id, $deliveryOrder->agent_id, $payment, $profile[1], $profile[0]);
                    }
                }

                if (empty($payment['transactionResponse']['authCode'])) {
                    $payment['messages']['message'][0]['text'] = 'The card selected has been entered incorrectly or has been declined. Please check the card numbers or enter a different card. If a card is declined three consecutive times, the card will be removed from our system and would need to be reentered.';
                }

                return $payment;
            } else {
                $this->paymentService->resetCardRejectionCounter(substr($payment['transactionResponse']['accountNumber'], -4), $deliveryOrder->office_id, $deliveryOrder->agent_id);
                $deliveryOrder->auth_transaction_id = $payment['transactionResponse']['transId'];
                $deliveryOrder->card_last_four = substr($payment['transactionResponse']['accountNumber'], -4);
                $deliveryOrder->card_type = $payment['transactionResponse']['accountType'];
                $deliveryOrder->authorized_amount = $deliveryOrder->total;

                $this->saveDeliveryPaymentAndUpdateOrder($deliveryOrder);

                Session::flash("success", "Payment submitted successfully. Thank you!");
            }
        } else { //New card
            //card numbers
            //$card_name = trim($data['delivery_card_name']);
            $card_number = trim($data['delivery_card_number']);
            $card_numbers = explode(' ', $card_number);
            //$last_number = encrypt(end($card_numbers));

            $exyear = trim($data['delivery_expire_date_year']);
            $exmonth = trim($data['delivery_expire_date_month']);
            $expireDate = "$exyear-$exmonth";
            $card_code = $data['delivery_card_code'];

            // card info
            $cardInfo = [
                //"cardName" => $card_name,
                "cardNumber" => str_replace(' ', '', $card_number),
                "expirationDate" => $expireDate,
                "cardCode" => $card_code,
            ];

            //Don't proceed if name, address and zipcode not provided
            if (! $request->delivery_billing_name|| ! $request->delivery_billing_address|| ! $request->delivery_billing_zip) {
                $payment['messages']['resultCode'] = "Error";
                $payment['messages']['message'][0]['text'] = 'Please fill out all billing fields.';

                return $payment;
            }

            //Don't proceed if card expires before the service date
            if ((new Carbon($expireDate))->endOfMonth() < (new Carbon($deliveryOrder->service_date))->endOfDay()) {
                $payment['messages']['resultCode'] = "Error";
                $payment['messages']['message'][0]['text'] = 'The entered card expires before the service date, please use a different card.';

                return $payment;
            }

            $getFirstLast = $this->getFirstLastFromName($request->delivery_billing_name);
            $billTo['first_name'] = $getFirstLast['firstName'];
            $billTo['last_name'] = $getFirstLast['lastName'];
            $billTo['address'] = $request->delivery_billing_address;
            $billTo['city'] = $request->delivery_billing_city;
            $billTo['state'] = $request->delivery_billing_state;
            $billTo['zipcode'] = substr($request->delivery_billing_zip, 0, 5);

            //authorize payment
            $payment = $this->authorizeNetService->authrorizeCardFromProfile(
                $cardInfo,
                $billTo,
                $deliveryOrder,
                Order::DELIVERY_ORDER,
                $cardVisibility,
                $cardOwner
            );
            //dd($payment);
            if (
                $payment['messages']['resultCode'] == "Error"
                || empty($payment['transactionResponse']['authCode'] )
            ) {
                if (empty($payment['transactionResponse']['authCode'])) {
                    $payment['messages']['message'][0]['text'] = 'The card selected has been entered incorrectly or has been declined. Please check the card numbers or enter a different card. If a card is declined three consecutive times, the card will be removed from our system and would need to be reentered.';
                }

                return $payment;
            } else {
                $deliveryOrder->auth_transaction_id = $payment['transactionResponse']['transId'];
                $deliveryOrder->card_last_four = substr($payment['transactionResponse']['accountNumber'], -4);
                $deliveryOrder->card_type = $payment['transactionResponse']['accountType'];
                $deliveryOrder->authorized_amount = $deliveryOrder->total;

                $this->saveDeliveryPaymentAndUpdateOrder($deliveryOrder);

                Session::flash("success", "Payment submitted successfully. Thank you!");
            }
        }

        try {
            $this->notificationService->deliveryOrderCreated($deliveryOrder);
        } catch (Throwable $e) {
            logger()->error($e->getMessage());
        }

        return $payment;
    }

    public function saveDeliveryPaymentAndUpdateOrder(DeliveryOrder $deliveryOrder)
    {
        /*$payment = $this->paymentService->createDeliveryPayment([
            "delivery_order_id" => $deliveryOrder->id,
            "paid_by" => auth()->id(),
            "office_id" => $deliveryOrder->office_id,
            "agent_id" => $deliveryOrder->agent_id,
            "amount" => $deliveryOrder->total
        ]);*/

        //
        $deliveryOrder->status = DeliveryOrder::STATUS_RECEIVED;
        $deliveryOrder->action_needed = false;
        $deliveryOrder->save();
    }

    public function manageCards()
    {
        $authUser = auth()->user();

        $posts = $this->postService->getOrderByListingOrderAndName();
        $panels = $this->panelService->getOrderByListingOrderAndName();
        $accessories = $this->accessoryService->getOrderByListingOrderAndName();
        $cards = $this->getCurrentUserSavedCards();
        $serviceSettings = $service_settings = ServiceSetting::first();

        if ($authUser->role == User::ROLE_OFFICE) {
            $agents = $this->officeService->getAgents($authUser->office->id);

            $data = compact('agents', 'accessories', 'panels', 'posts', 'service_settings', 'serviceSettings', 'cards');

            return view('accounting.manage_cards.office.index', $data);
        }

        if ($authUser->role == User::ROLE_AGENT) {
            $agents = [];
            $data = compact('agents', 'accessories', 'panels', 'posts', 'service_settings', 'serviceSettings', 'cards');

            return view('accounting.manage_cards.agent.index', $data);
        }
    }

    public function getCurrentUserSavedCards()
    {
        $user = auth()->user();

        //Get only unique rows because it's taking too long to load cards.
        $storedPaymentProfiles = $this->paymentService->getUniquePaymentProfiles((int) $user->id);
        $authorizeNetCustomerId = $user->authorizenet_profile_id;

        $returnData = [];
        if ($storedPaymentProfiles->isNotEmpty()) {
            foreach ($storedPaymentProfiles as $storedPaymentProfile) {
                $paymentProfile = $this->authorizeNetService->getPaymentProfile(
                    $authorizeNetCustomerId,
                    $storedPaymentProfile->payment_profile_id
                );
                //info($paymentProfile);
                if (isset($paymentProfile['paymentProfile'])) {
                    $cardinfo = $paymentProfile['paymentProfile']['payment']['creditCard'];

                    if ((new Carbon($cardinfo['expirationDate']))->endOfMonth() < (new Carbon(now()))->endOfDay()) {
                        RemoveCardJob::dispatch($paymentProfile['paymentProfile']['customerPaymentProfileId'], $authorizeNetCustomerId);
                        continue;
                    }

                    $returnData[$storedPaymentProfile->payment_profile_id]['cardNumber'] = str_replace('XXXX', 'XXXX-XXXXX-XXXX-', $cardinfo['cardNumber']);
                    $returnData[$storedPaymentProfile->payment_profile_id]['cardType'] = $cardinfo['cardType'];
                    $expDateArray = explode('-', $cardinfo['expirationDate']);
                    $expDate = "{$expDateArray[1]}/{$expDateArray[0]}";
                    $returnData[$storedPaymentProfile->payment_profile_id]['expDate'] = $expDate;

                    $authPaymentProfile = $this->paymentService->findByPaymentProfileIdAndUserId(
                        $storedPaymentProfile->payment_profile_id,
                        (int) $user->id
                    );

                    $returnData[$storedPaymentProfile->payment_profile_id]['visibleToAgents'] = false;
                    if ($authPaymentProfile->office_card_visible_agents) {
                        $returnData[$storedPaymentProfile->payment_profile_id]['visibleToAgents'] = true;
                    }
                }
            }
        }

        return $returnData;
    }

    public function removeCard(Request $request)
    {
        $user = auth()->user();

        $authorizeNetCustomerId = $user->authorizenet_profile_id;
        $paymentProfileId = $request->payment_profile_id;

        try {
            $this->authorizeNetService->removeCard($authorizeNetCustomerId, $paymentProfileId);
            return $this->backWithSuccess('Card removed successfully.');
        } catch (\Exception $ex) {
            logger()->error($ex->getMessage());
            return $this->backWithError($this->serverErrorMessage());
        }
    }

    public function getAgentCardsVisibleToOffice($agentUserId, $officeUserId)
    {
        $storedPaymentProfiles = $this->paymentService->getPaymentProfilesSharedByOfficeAndAgent(
            $agentUserId,
            $officeUserId
        );

        $returnData = [];
        if ($storedPaymentProfiles->isNotEmpty()) {
            foreach ($storedPaymentProfiles as $storedPaymentProfile) {
                $paymentProfile = $this->authorizeNetService->getPaymentProfile(
                    $storedPaymentProfile->authorizenet_profile_id,
                    $storedPaymentProfile->payment_profile_id
                );
                //return $paymentProfile;
                if (isset($paymentProfile['paymentProfile'])) {
                    $cardinfo = $paymentProfile['paymentProfile']['payment']['creditCard'];
                    $returnData[$storedPaymentProfile->payment_profile_id]['cardNumber'] = str_replace('XXXX', 'XXXX-', $cardinfo['cardNumber']);
                    $returnData[$storedPaymentProfile->payment_profile_id]['cardType'] = $cardinfo['cardType'];
                    $expDateArray = explode('-', $cardinfo['expirationDate']);
                    $expDate = "{$expDateArray[1]}/{$expDateArray[0]}";
                    $returnData[$storedPaymentProfile->payment_profile_id]['expDate'] = $expDate;
                    $returnData[$storedPaymentProfile->payment_profile_id]['customerProfileId'] = $storedPaymentProfile->authorizenet_profile_id;
                }
            }
        }

        return response()->json($returnData);
    }

    public function addCard(AddCard $request)
    {
        $data = $request->all();

        $card_number = trim($data['add_card_number']);

        $exyear = trim($data['add_card_expire_date_year']);
        $exmonth = trim($data['add_card_expire_date_month']);
        $expireDate = "$exyear-$exmonth";
        $card_code = $data['add_card_code'];

        // card info
        $cardInfo = [
            "cardNumber" => str_replace(' ', '', $card_number),
            "expirationDate" => $expireDate,
            "cardCode" => $card_code,
        ];

        $cardOwner = auth()->user();

        $getFirstLast = $this->getFirstLastFromName($data['add_card_billing_name']);
        $billTo['first_name'] = $getFirstLast['firstName'];
        $billTo['last_name'] = $getFirstLast['lastName'];
        $billTo['address'] = $data['add_card_billing_address'];
        $billTo['city'] = $data['add_card_billing_city'];
        $billTo['state'] = $data['add_card_billing_state'];
        $billTo['zipcode'] = substr($data['add_card_billing_zip'], 0, 5);
        $billTo['email'] = $cardOwner->email;

        $cardVisibility = null;
        $officeCardShared = null;

        //If Offices then card will be visible to them or to all agents
        //based on their selection
        if ($cardOwner->role == User::ROLE_OFFICE) {
            if (isset($data['add_card_visible_to_agents'])) {
                if ($data['add_card_visible_to_agents'] == 'on') {
                    $officeCardShared = true;
                }
            }
        }

        //Add card
        $addCard = $this->authorizeNetService->addCard(
            $cardInfo,
            $billTo,
            $cardVisibility,
            $cardOwner,
            $officeCardShared
        );

        if ($addCard['messages']['resultCode'] == "Error") {
            return $addCard;
        } else {
            Session::flash("success", "Card saved successfully.");
        }

        return $addCard;
    }

    public function getOfficeCardsVisibleToAgents($officeUserId, $officePayMethod, $agentPayMethod)
    {
        $storedPaymentProfiles = $this->paymentService->getOfficePaymentProfilesSharedWithAllAgents(
            (int) $officeUserId,
            (int) $officePayMethod,
            (int) $agentPayMethod
        );

        $returnData = [];
        if ($storedPaymentProfiles->isNotEmpty()) {
            foreach ($storedPaymentProfiles as $storedPaymentProfile) {
                $paymentProfile = $this->authorizeNetService->getPaymentProfile(
                    $storedPaymentProfile->authorizenet_profile_id,
                    $storedPaymentProfile->payment_profile_id
                );
                //return $paymentProfile;
                if (isset($paymentProfile['paymentProfile'])) {
                    $cardinfo = $paymentProfile['paymentProfile']['payment']['creditCard'];
                    $returnData[$storedPaymentProfile->payment_profile_id]['cardNumber'] = str_replace('XXXX', 'XXXX-', $cardinfo['cardNumber']);
                    $returnData[$storedPaymentProfile->payment_profile_id]['cardType'] = $cardinfo['cardType'];
                    $expDateArray = explode('-', $cardinfo['expirationDate']);
                    $expDate = "{$expDateArray[1]}/{$expDateArray[0]}";
                    $returnData[$storedPaymentProfile->payment_profile_id]['expDate'] = $expDate;
                    $returnData[$storedPaymentProfile->payment_profile_id]['customerProfileId'] = $storedPaymentProfile->authorizenet_profile_id;
                }
            }
        }

        return response()->json($returnData);
    }

    public function officeToggleCardVisibility(Request $request)
    {
        //make sure payment profile Exists
        $profile = $this->paymentService->findByPaymentProfileIdAndUserId(
            $request->payment_profile_id,
            (int) auth()->id()
        );
        if (! $profile) {
            return $this->responseJsonError('Invalid data.');
        }

        $this->paymentService->officeToggleCardVisibility(
            (int) $request->payment_profile_id,
            (int) $request->visibility,
            (int) auth()->id()
        );

        return $this->responseJsonSuccess((object)[]);
    }

}
