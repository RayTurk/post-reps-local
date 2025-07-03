<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Models\{User, AuthorizenetPaymentProfile};
use Exception;

class AuthorizeNetService
{
    private $production = false;

    private $url;

    private $validationMode;

    public function __construct()
    {
        $this->production = config('authorizenet.production');
        if ($this->production) {
            $this->url = "https://api.authorize.net/xml/v1/request.api";
            $this->validationMode = 'liveMode';
        } else {
            $this->url = "https://apitest.authorize.net/xml/v1/request.api";
            $this->validationMode = 'liveMode';
        }
    }

    public function template()
    {
        return json_decode('{
            "createTransactionRequest": {
                "merchantAuthentication": {
                    "name": "5KP3u95bQpv",
                    "transactionKey": "346HZ32z3fP4hTG2"
                },
                 "transactionRequest": {
                    "transactionType": "authOnlyTransaction",
                    "amount": "5",
                    "payment": {
                        "creditCard": {
                            "cardNumber": "5424000000000015",
                            "expirationDate": "2025-12",
                            "cardCode": "999"
                        }
                    },
                    "order": {
                        "invoiceNumber": "656565"
                    },
                    "tax": {
                        "amount": "0",
                        "name": "No tax",
                        "description": "No Tax"
                    },
                    "billTo": {
                        "firstName": "Ellen",
                        "lastName": "Johnson",
                        "company": "Souveniropolis",
                        "address": "14 Main Street",
                        "city": "Pecan Springs",
                        "state": "TX",
                        "zip": "44628",
                        "country": "US"
                    },
                    "authorizationIndicatorType": {
                        "authorizationIndicator": "final"
                    }
                }
            }
        }');
    }

    public function createProfileTemplate()
    {
        return json_decode('{
            "createCustomerProfileRequest": {
                "merchantAuthentication": {
                    "name": "4U6Br3P7kUef",
                    "transactionKey": "2vYc9v3t93qA3MkX"
                },
                "profile": {
                    "merchantCustomerId": "Merchant_Customer_ID",
                    "description": "Profile description here",
                    "email": "customer-profile-email@here.com",
                    "paymentProfiles": {
                        "customerType": "individual",
                        "billTo": {
                            "firstName": "John",
                            "lastName": "Doe",
                            "address": "25 weldon st",
                            "city": "Boise",
                            "state": "Idaho",
                            "zip": "99999"
                        },
                        "payment": {
                            "creditCard": {
                                "cardNumber": "4111111111111111",
                                "expirationDate": "2025-12"
                            }
                        }
                    }
                },
                "validationMode": "liveMode"
            }
        }');
    }

    public function createPaymentProfileTemplate()
    {
        return json_decode('{
            "createCustomerPaymentProfileRequest": {
                "merchantAuthentication": {
                    "name": "5KP3u95bQpv",
                    "transactionKey": "346HZ32z3fP4hTG2"
                },
                "customerProfileId": "10000",
                "paymentProfile": {
                    "billTo": {
                        "firstName": "John",
                        "lastName": "Doe",
                        "address": "123 Main St.",
                        "city": "Bellevue",
                        "state": "WA",
                        "zip": "98004",
                        "country": "US",
                        "phoneNumber": "000-000-0000"
                    },
                    "payment": {
                        "creditCard": {
                            "cardNumber": "4111111111111111",
                            "expirationDate": "2023-12"
                        }
                    },
                    "defaultPaymentProfile": false
                },
                "validationMode": "liveMode"
            }
        }');
    }
    public function createPaymentProfile($cardInfo, $billTo, $authorizeNetCustomerId)
    {
        $data = $this->createPaymentProfileTemplate();

        //auth
        $data->createCustomerPaymentProfileRequest->merchantAuthentication->name = config('authorizenet.login_id');
        $data->createCustomerPaymentProfileRequest->merchantAuthentication->transactionKey = config('authorizenet.transaction_key');

        $data->createCustomerPaymentProfileRequest->customerProfileId = $authorizeNetCustomerId;
        $data->createCustomerPaymentProfileRequest->validationMode = $this->validationMode;

        //card number
        $data->createCustomerPaymentProfileRequest->paymentProfile->payment->creditCard->cardNumber        = $cardInfo['cardNumber'];
        $data->createCustomerPaymentProfileRequest->paymentProfile->payment->creditCard->expirationDate    = $cardInfo['expirationDate'];
        $data->createCustomerPaymentProfileRequest->paymentProfile->payment->creditCard->cardCode          = $cardInfo['cardCode'];

        //bill to
        $data->createCustomerPaymentProfileRequest->paymentProfile->billTo->firstName  = $billTo['first_name'];
        $data->createCustomerPaymentProfileRequest->paymentProfile->billTo->lastName   = $billTo['last_name'];
        $data->createCustomerPaymentProfileRequest->paymentProfile->billTo->address    = $billTo['address'];
        $data->createCustomerPaymentProfileRequest->paymentProfile->billTo->city       = $billTo['city'];
        $data->createCustomerPaymentProfileRequest->paymentProfile->billTo->state      = $billTo['state'];
        $data->createCustomerPaymentProfileRequest->paymentProfile->billTo->zip        = $billTo['zipcode'];

        //send request
        $response = Http::post($this->url, (array)$data);

        //get response
        $createPaymentProfile = $this->jsonDecode(trim($response->body()));

        return $createPaymentProfile;
    }

    public function createPayment($cardInfo, $billTo, $order, $orderType)
    {
        //transaction request template
        $data = $this->template();

        //auth
        $data->createTransactionRequest->merchantAuthentication->name = config('authorizenet.login_id');
        $data->createTransactionRequest->merchantAuthentication->transactionKey = config('authorizenet.transaction_key');

        //card number
        $data->createTransactionRequest->transactionRequest->payment->creditCard->cardNumber        = $cardInfo['cardNumber'];
        $data->createTransactionRequest->transactionRequest->payment->creditCard->expirationDate    = $cardInfo['expirationDate'];
        $data->createTransactionRequest->transactionRequest->payment->creditCard->cardCode          = $cardInfo['cardCode'];

        //bill to
        $data->createTransactionRequest->transactionRequest->billTo->firstName  = $billTo['first_name'];
        $data->createTransactionRequest->transactionRequest->billTo->lastName   = $billTo['last_name'];
        $data->createTransactionRequest->transactionRequest->billTo->address    = $billTo['address'];
        $data->createTransactionRequest->transactionRequest->billTo->city       = $billTo['city'];
        $data->createTransactionRequest->transactionRequest->billTo->state      = $billTo['state'];
        $data->createTransactionRequest->transactionRequest->billTo->zip        = $billTo['zipcode'];

        //amount
        $data->createTransactionRequest->transactionRequest->amount = $order->total;

        $data->createTransactionRequest->transactionRequest->order->invoiceNumber =  $order->order_number;

        //send request
        $response = Http::post($this->url, (array)$data);

        //get response
        $transaction = $this->jsonDecode(trim($response->body()));

        //Get user of the card owner
        $cardOwner = User::find($billTo['userId']);

        //check if profile exists
        $getProfile = $this->getCustomerProfile($cardOwner->authorizenet_profile_id);
        if (isset($getProfile['profile'])) {
            //Add card to existing customer profile by creating payment profile
            $createPaymentProfile = $this->createPaymentProfile($cardInfo, $billTo, $cardOwner->authorizenet_profile_id);

            //Store the payment profile in db
            if (isset($createPaymentProfile['customerPaymentProfileId'])) {
                $storePaymentProfile = AuthorizenetPaymentProfile::updateOrCreate(
                    [
                        'payment_profile_id' => $createPaymentProfile['customerPaymentProfileId'],
                        'order_id' => $order->id,
                        'order_type' => $orderType
                    ],
                    [
                        'user_id' => $cardOwner->id,
                        'order_id' => $order->id,
                        'order_type' => $orderType
                    ]
                );
            }
        } else {
            //create profile
            //if customer profile doesn't exist then create one
            $profile = $this->createProfile(
                [
                    "email" => $billTo['email'],
                    "cardNumber" => $cardInfo['cardNumber'],
                    "expirationDate" => $cardInfo['expirationDate'],
                    "cardCode" => $cardInfo['cardCode'],
                    "cardOwner" => $cardOwner,
                    'order_id' => $order->id,
                    'order_type' => $orderType,
                    "billTo" => $billTo
                ]
            );
        }

        return $transaction;
    }

    public function authrorizeCardFromProfile($cardInfo, $billTo, $order, $orderType, $cardVisibility, $cardOwner)
    {
        //Get customer profile ID from users table
        $authorizeNetCustomerId = $cardOwner->authorizenet_profile_id;

        //check if profile exists in AuthNet
        $getProfile = $this->getCustomerProfile($authorizeNetCustomerId);
        if (isset($getProfile['profile'])) {
            //Add card to existing customer profile by creating payment profile
            $createPaymentProfile = $this->createPaymentProfile($cardInfo, $billTo, $authorizeNetCustomerId);
            info("authrorizeCardFromProfile createPaymentProfile for new card: order $order->order_number");
            info($createPaymentProfile);

            if ($createPaymentProfile['messages']['resultCode'] == "Error") {
                return $createPaymentProfile;
            }

            //Store the payment profile in db
            if (isset($createPaymentProfile['customerPaymentProfileId'])) {
                $paymentProfile = $createPaymentProfile['customerPaymentProfileId'];

                $storePaymentProfile = AuthorizenetPaymentProfile::updateOrCreate(
                    [
                        'payment_profile_id' => $paymentProfile,
                        'order_id' => $order->id,
                        'order_type' => $orderType
                    ],
                    [
                        'user_id' => $cardOwner->id,
                        'order_id' => $order->id,
                        'order_type' => $orderType,
                        "card_shared_with" => $cardVisibility
                    ]
                );
            }
        } else {
            //create profile
            //if customer profile doesn't exist then create one
            $profile = $this->createProfile(
                [
                    "email" => $billTo['email'],
                    "cardNumber" => $cardInfo['cardNumber'],
                    "expirationDate" => $cardInfo['expirationDate'],
                    "cardCode" => $cardInfo['cardCode'],
                    "cardOwner" => $cardOwner,
                    'order_id' => $order->id,
                    'order_type' => $orderType,
                    "billTo" => $billTo,
                    "card_shared_with" => $cardVisibility
                ]
            );

            if ($profile['messages']['resultCode'] == "Error") {
                return $profile;
            }

            $paymentProfile = $profile['customerPaymentProfileIdList'][0];
            $authorizeNetCustomerId = $profile['customerProfileId'];
        }
        //dd($paymentProfile);

        $charge = $this->chargeCustomerProfileAuthOnly(
            $authorizeNetCustomerId,
            $paymentProfile,
            $order,
            $orderType,
            $cardVisibility,
            $cardOwner
        );

        return $charge;
    }

    public function jsonDecode($jsonString)
    {
        return json_decode(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $jsonString), true);
    }

    public function genId($prefix)
    {
        return str_replace('.', '_', uniqid($prefix . microtime(true)));
    }

    public function createProfile($params)
    {
        $data = $this->createProfileTemplate();
        // auth
        $data->createCustomerProfileRequest->merchantAuthentication->name = config('authorizenet.login_id');
        $data->createCustomerProfileRequest->merchantAuthentication->transactionKey = config('authorizenet.transaction_key');
        $data->createCustomerProfileRequest->profile->merchantCustomerId = "";
        $data->createCustomerProfileRequest->profile->email = $params['email'];
        $data->createCustomerProfileRequest->profile->paymentProfiles->payment->creditCard->cardNumber = $params['cardNumber'];
        $data->createCustomerProfileRequest->profile->paymentProfiles->payment->creditCard->expirationDate = $params['expirationDate'];
        $data->createCustomerProfileRequest->profile->paymentProfiles->payment->creditCard->cardCode = $params['cardCode'];
        $data->createCustomerProfileRequest->profile->paymentProfiles->billTo->firstName = $params['billTo']['first_name'];
        $data->createCustomerProfileRequest->profile->paymentProfiles->billTo->lastName = $params['billTo']['last_name'];
        $data->createCustomerProfileRequest->profile->paymentProfiles->billTo->address = $params['billTo']['address'];
        $data->createCustomerProfileRequest->profile->paymentProfiles->billTo->city = $params['billTo']['city'];
        $data->createCustomerProfileRequest->profile->paymentProfiles->billTo->state = $params['billTo']['state'];
        $data->createCustomerProfileRequest->profile->paymentProfiles->billTo->zip = $params['billTo']['zipcode'];

        //send request
        $response = Http::post($this->url, (array)$data);
        //get response
        $transaction = $this->jsonDecode(trim($response->body()));
        logger($transaction);

        //Store authorize customer profile
        if (isset($transaction['customerProfileId'])) {
            $cardOwner = $params['cardOwner'];
            $cardOwner->authorizenet_profile_id = $transaction['customerProfileId'];
            $cardOwner->save();

            //Store payment profiles in DB for faster queries
            $paymentProfilesList = $transaction['customerPaymentProfileIdList'];
            foreach ($paymentProfilesList as $paymentProfileId) {
                $storePaymentProfile = AuthorizenetPaymentProfile::updateOrCreate(
                    [
                        'user_id' => $cardOwner->id,
                        'payment_profile_id' => $paymentProfileId,
                        'order_id' => $params['order_id'] ?? null,
                        'order_type' => $params['order_type'] ?? null
                    ],
                    [
                        'authorizenet_profile_id' => $transaction['customerProfileId'],
                        'card_shared_with' => $params['card_shared_with'] ?? null,
                        'office_card_visible_agents' => $params['office_card_visible_agents'] ?? null
                    ]
                );
            }
        }

        return $transaction;
    }

    public function getCustomerProfileTemplate()
    {
        return json_decode('
                    {
                        "getCustomerProfileRequest": {
                            "merchantAuthentication": {
                                "name": "4U6Br3P7kUef",
                                "transactionKey": "2vYc9v3t93qA3MkX"
                            },
                            "customerProfileId": "10000",
                            "includeIssuerInfo": "true"
                        }
                    }
        ');
    }

    public function getCustomerProfile($authorizeNetCustomerId)
    {
        $data = $this->getCustomerProfileTemplate();
        // auth
        $data->getCustomerProfileRequest->merchantAuthentication->name = config('authorizenet.login_id');
        $data->getCustomerProfileRequest->merchantAuthentication->transactionKey = config('authorizenet.transaction_key');
        $data->getCustomerProfileRequest->customerProfileId = $authorizeNetCustomerId;
        //send request
        $response = Http::post($this->url, (array)$data);
        //get response
        $transaction = $this->jsonDecode(trim($response->body()));
        return $transaction;
    }

    public function CaptureTemplate()
    {
        return json_decode('
        {
            "createTransactionRequest": {
                "merchantAuthentication": {
                    "name": "6S7euv4PZhK",
                    "transactionKey": "95qmN6usT48NK2fN"
                },
                "refId": "123456",
                "transactionRequest": {
                    "transactionType": "priorAuthCaptureTransaction",
                    "amount": "5",
                    "refTransId": "1234567890"
                }
            }
        }

          ');
    }

    public function capture($amount, $transId)
    {
        $data = $this->CaptureTemplate();
        // auth
        $data->createTransactionRequest->merchantAuthentication->name = config('authorizenet.login_id');
        $data->createTransactionRequest->merchantAuthentication->transactionKey = config('authorizenet.transaction_key');
        $data->createTransactionRequest->transactionRequest->amount = $amount;
        $data->createTransactionRequest->transactionRequest->refTransId = $transId;
        //send request
        $response = Http::post($this->url, (array)$data);
        //get response
        $transaction = $this->jsonDecode(trim($response->body()));

        info("Capturing payment of $$amount for transId $transId");
        info($transaction);

        return $transaction;
    }

    public function getPaymentProfileTemplate()
    {
        return json_decode(
            '{
                "getCustomerPaymentProfileRequest": {
                    "merchantAuthentication": {
                        "name": "6S7euv4PZhK",
                        "transactionKey": "95qmN6usT48NK2fN"
                    },
                    "customerProfileId": "903341534",
                    "customerPaymentProfileId": "20000",
                    "unmaskExpirationDate": "true",
                    "includeIssuerInfo": "false"
                }
            }'
        );
    }

    public function getPaymentProfile($authorizeNetCustomerId, $customerPaymentProfileId)
    {
        $data = $this->getPaymentProfileTemplate();
        // auth
        $data->getCustomerPaymentProfileRequest->merchantAuthentication->name = config('authorizenet.login_id');
        $data->getCustomerPaymentProfileRequest->merchantAuthentication->transactionKey = config('authorizenet.transaction_key');
        $data->getCustomerPaymentProfileRequest->customerProfileId = "$authorizeNetCustomerId";
        $data->getCustomerPaymentProfileRequest->customerPaymentProfileId = "$customerPaymentProfileId";

        //send request
        $response = Http::post($this->url, (array)$data);

        //get response
        $paymentProfile = $this->jsonDecode(trim($response->body()));

        return $paymentProfile;
    }

    public function chargeCustomerProfileAuthOnlyTemplate()
    {
        return json_decode(
            '{
                "createTransactionRequest": {
                    "merchantAuthentication": {
                        "name": "9bSaKC66uHg",
                        "transactionKey": "8xszx7B7674QxHqe"
                    },
                    "transactionRequest": {
                        "transactionType": "authOnlyTransaction",
                        "amount": "45",
                        "profile": {
                            "customerProfileId": "40338125",
                            "paymentProfile": { "paymentProfileId": "1000177237" }
                        },
                        "order": {
                            "invoiceNumber": "656565"
                        },
                        "tax": {
                            "amount": "0",
                            "name": "No tax",
                            "description": "No Tax"
                        },
                        "authorizationIndicatorType": {
                            "authorizationIndicator": "final"
                        }
                    }
                }
            }'
        );
    }

    public function chargeInvoiceCustomerProfileCaptureTemplate()
    {
        return json_decode(
            '{
                "createTransactionRequest": {
                    "merchantAuthentication": {
                        "name": "9bSaKC66uHg",
                        "transactionKey": "8xszx7B7674QxHqe"
                    },
                    "transactionRequest": {
                        "transactionType": "authCaptureTransaction",
                        "amount": "45",
                        "profile": {
                            "customerProfileId": "40338125",
                            "paymentProfile": { "paymentProfileId": "1000177237" }
                        },
                        "order": {
                            "invoiceNumber": "656565"
                        },
                        "tax": {
                            "amount": "0",
                            "name": "No tax",
                            "description": "No Tax"
                        },
                        "authorizationIndicatorType": {
                            "authorizationIndicator": "final"
                        }
                    }
                }
            }'
        );
    }

    public function chargeCustomerProfileAuthOnly(
        $authorizeNetCustomerId,
        $customerPaymentProfileId,
        $order,
        $orderType,
        $cardVisibility,
        $cardOwner
    ) {
        $data = $this->chargeCustomerProfileAuthOnlyTemplate();
        // auth
        $data->createTransactionRequest->merchantAuthentication->name = config('authorizenet.login_id');
        $data->createTransactionRequest->merchantAuthentication->transactionKey = config('authorizenet.transaction_key');

        $data->createTransactionRequest->transactionRequest->profile->customerProfileId = "$authorizeNetCustomerId";
        $data->createTransactionRequest->transactionRequest->profile->paymentProfile->paymentProfileId = "$customerPaymentProfileId";
        $data->createTransactionRequest->transactionRequest->order->invoiceNumber =  $order->order_number;

        $data->createTransactionRequest->transactionRequest->amount = $order->total;

        //send request
        $response = Http::post($this->url, (array)$data);
        info("chargeCustomerProfileAuthOnly for order $order->order_number");
        info($response);

        //get response
        $transaction = $this->jsonDecode(trim($response->body()));

        if ($transaction['messages']['resultCode'] == "Ok") {
            //Save payment profile for later charge
            $storePaymentProfile = AuthorizenetPaymentProfile::updateOrCreate(
                [
                    'user_id' => $cardOwner->id,
                    'payment_profile_id' => $customerPaymentProfileId,
                    'order_id' => $order->id,
                    'order_type' => $orderType
                ],
                [
                    'order_id' => $order->id,
                    'order_type' => $orderType,
                    'card_shared_with' => $cardVisibility,
                    'authorizenet_profile_id' => $authorizeNetCustomerId
                ]
            );
            //dd($storePaymentProfile);
        }

        return $transaction;
    }

    public function chargeInvoiceCustomerProfileCapture($authorizeNetCustomerId, $customerPaymentProfileId, $invoice, $fees = 0)
    {
        $data = $this->chargeInvoiceCustomerProfileCaptureTemplate();
        // auth
        $data->createTransactionRequest->merchantAuthentication->name = config('authorizenet.login_id');
        $data->createTransactionRequest->merchantAuthentication->transactionKey = config('authorizenet.transaction_key');

        $data->createTransactionRequest->transactionRequest->profile->customerProfileId = "$authorizeNetCustomerId";
        $data->createTransactionRequest->transactionRequest->profile->paymentProfile->paymentProfileId = "$customerPaymentProfileId";
        $data->createTransactionRequest->transactionRequest->order->invoiceNumber =  $invoice->invoice_number;

        $data->createTransactionRequest->transactionRequest->amount = $invoice->payment_amount + $fees;

        //send request
        $response = Http::post($this->url, (array)$data);

        //get response
        $transaction = $this->jsonDecode(trim($response->body()));

        return $transaction;
    }

    public function chargeCardTemplate()
    {
        return json_decode('{
            "createTransactionRequest": {
                "merchantAuthentication": {
                    "name": "5KP3u95bQpv",
                    "transactionKey": "346HZ32z3fP4hTG2"
                },
                 "transactionRequest": {
                    "transactionType": "authCaptureTransaction",
                    "amount": "5",
                    "payment": {
                        "creditCard": {
                            "cardNumber": "5424000000000015",
                            "expirationDate": "2025-12",
                            "cardCode": "999"
                        }
                    },
                    "order": {
                        "invoiceNumber": "656565"
                    },
                    "tax": {
                        "amount": "0",
                        "name": "No tax",
                        "description": "No Tax"
                    },
                    "billTo": {
                        "firstName": "Ellen",
                        "lastName": "Johnson",
                        "address": "14 Main Street",
                        "city": "Pecan Springs",
                        "state": "TX",
                        "zip": "44628",
                        "country": "US"
                    },
                    "authorizationIndicatorType": {
                        "authorizationIndicator": "final"
                    }
                }
            }
        }');
    }

    public function createInvoicePayment($cardInfo, $billTo, $invoice, $fees=0)
    {
        //transaction request template
        $data = $this->chargeCardTemplate();

        //auth
        $data->createTransactionRequest->merchantAuthentication->name = config('authorizenet.login_id');
        $data->createTransactionRequest->merchantAuthentication->transactionKey = config('authorizenet.transaction_key');

        //card number
        $data->createTransactionRequest->transactionRequest->payment->creditCard->cardNumber        = $cardInfo['cardNumber'];
        $data->createTransactionRequest->transactionRequest->payment->creditCard->expirationDate    = $cardInfo['expirationDate'];
        $data->createTransactionRequest->transactionRequest->payment->creditCard->cardCode          = $cardInfo['cardCode'];

        //bill to
        $data->createTransactionRequest->transactionRequest->billTo->firstName  = $billTo['first_name'];
        $data->createTransactionRequest->transactionRequest->billTo->lastName   = $billTo['last_name'];
        $data->createTransactionRequest->transactionRequest->billTo->address    = $billTo['address'];
        $data->createTransactionRequest->transactionRequest->billTo->city       = $billTo['city'];
        $data->createTransactionRequest->transactionRequest->billTo->state      = $billTo['state'];
        $data->createTransactionRequest->transactionRequest->billTo->zip        = $billTo['zipcode'];

        //amount
        $data->createTransactionRequest->transactionRequest->amount = $invoice->payment_amount + $fees;

        $data->createTransactionRequest->transactionRequest->order->invoiceNumber = $invoice->invoice_number;

        //send request
        $response = Http::post($this->url, (array)$data);

        //get response
        $transaction = $this->jsonDecode(trim($response->body()));

        //Get user of the card owner
        $cardOwner = User::find($billTo['userId']);

        //check if profile exists
        $getProfile = $this->getCustomerProfile($cardOwner->authorizenet_profile_id);
        if (isset($getProfile['profile'])) {
            //Add card to existing customer profile by creating payment profile
            $createPaymentProfile = $this->createPaymentProfile($cardInfo, $billTo, $cardOwner->authorizenet_profile_id);

            //Store the payment profile in db
            if (isset($createPaymentProfile['customerPaymentProfileId'])) {
                $storePaymentProfile = AuthorizenetPaymentProfile::updateOrCreate(
                    ['payment_profile_id' => $createPaymentProfile['customerPaymentProfileId']],
                    ['user_id' => $cardOwner->id]
                );
            }
        } else {
            //create profile
            //if customer profile doesn't exist then create one
            $profile = $this->createProfile(
                [
                    "email" => $billTo['email'],
                    "cardNumber" => $cardInfo['cardNumber'],
                    "expirationDate" => $cardInfo['expirationDate'],
                    "cardCode" => $cardInfo['cardCode'],
                    "cardOwner" => $cardOwner,
                    "billTo" => $billTo
                ]
            );
        }

        return $transaction;
    }

    public function refundTemplate()
    {
        return json_decode('
            {
                "createTransactionRequest": {
                    "merchantAuthentication": {
                        "name": "5KP3u95bQpv",
                        "transactionKey": "346HZ32z3fP4hTG2"
                    },
                    "transactionRequest": {
                        "transactionType": "refundTransaction",
                        "amount": "5.00",
                        "profile": {
                            "customerProfileId": "1234567",
                            "paymentProfile": {
                                "paymentProfileId": "3456789"
                            }
                        },
                        "refTransId": "60126862687",
                        "order": {
                            "invoiceNumber": "656565"
                        }
                    }
                }
            }

        ');
    }

    public function refundCardPayment($authorizeNetCustomerId, $customerPaymentProfileId, $transId, $amount)
    {
        $data = $this->refundTemplate();

        //auth
        $data->createTransactionRequest->merchantAuthentication->name = config('authorizenet.login_id');
        $data->createTransactionRequest->merchantAuthentication->transactionKey = config('authorizenet.transaction_key');

        $data->createTransactionRequest->transactionRequest->profile->customerProfileId = "$authorizeNetCustomerId";
        $data->createTransactionRequest->transactionRequest->profile->paymentProfile->paymentProfileId = "$customerPaymentProfileId";
        $data->createTransactionRequest->transactionRequest->refTransId = "$transId";
        $data->createTransactionRequest->transactionRequest->order->invoiceNumber = "REF-{$transId}";

        //amount
        $data->createTransactionRequest->transactionRequest->amount = $amount;

        //send request
        $response = Http::post($this->url, (array)$data);

        //get response
        $transaction = $this->jsonDecode(trim($response->body()));

        return $transaction;
    }

    public function getSettledBatchListTemplate()
    {
        return json_decode('{
            "getSettledBatchListRequest": {
                "merchantAuthentication": {
                    "name": "5KP3u95bQpv",
                    "transactionKey": "346HZ32z3fP4hTG2"
                }
            }
        }');
    }

    public function getSettledBatchList()
    {
        $data = $this->getSettledBatchListTemplate();

        $data->getSettledBatchListRequest->merchantAuthentication->name = config('authorizenet.login_id');
        $data->getSettledBatchListRequest->merchantAuthentication->transactionKey = config('authorizenet.transaction_key');

        $response = Http::post($this->url, (array)$data);

        $transaction = $this->jsonDecode(trim($response->body()));

        return $transaction;
    }

    public function getTransactionListTemplate()
    {
        return json_decode('{
            "getTransactionListRequest": {
              "merchantAuthentication": {
                "name": "5KP3u95bQpv",
                "transactionKey": "346HZ32z3fP4hTG2"
              },
              "batchId" : "6680535",
              "sorting": {
                "orderBy": "submitTimeUTC",
                "orderDescending": "true"
              },
              "paging": {
                "limit": "1000",
                "offset": "1"
              }
            }
        }');
    }

    public function getTransactionList($batchId)
    {
        $data = $this->getTransactionListTemplate();

        $data->getTransactionListRequest->merchantAuthentication->name = config('authorizenet.login_id');
        $data->getTransactionListRequest->merchantAuthentication->transactionKey = config('authorizenet.transaction_key');
        $data->getTransactionListRequest->batchId = $batchId;

        $response = Http::post($this->url, (array)$data);

        $transaction = $this->jsonDecode(trim($response->body()));

        return $transaction;
    }

    public function getUnsettledTransactionListTemplate()
    {
        return json_decode('{
            "getUnsettledTransactionListRequest": {
                "merchantAuthentication": {
                    "name": "5KP3u95bQpv",
                    "transactionKey": "346HZ32z3fP4hTG2"
                },
                "sorting": {
                    "orderBy": "submitTimeUTC",
                    "orderDescending": true
                },
                "paging": {
                    "limit": "500",
                    "offset": "1"
                }
            }
        }');
    }

    public function getUnsettledTransactionList()
    {
        $data = $this->getUnsettledTransactionListTemplate();

        $data->getUnsettledTransactionListRequest->merchantAuthentication->name = config('authorizenet.login_id');
        $data->getUnsettledTransactionListRequest->merchantAuthentication->transactionKey = config('authorizenet.transaction_key');

        $response = Http::post($this->url, (array)$data);

        $transaction = $this->jsonDecode(trim($response->body()));

        return $transaction;
    }

    public function deleteCustomerPaymentProfileTemplate()
    {
        return json_decode('{
            "deleteCustomerPaymentProfileRequest": {
                "merchantAuthentication": {
                    "name": "5KP3u95bQpv",
                    "transactionKey": "346HZ32z3fP4hTG2"
                },
                "customerProfileId": "10000",
                "customerPaymentProfileId": "20000"
            }
        }');
    }

    public function removeCard($authorizeNetCustomerId, $customerPaymentProfileId)
    {
        $data = $this->deleteCustomerPaymentProfileTemplate();

        $data->deleteCustomerPaymentProfileRequest->merchantAuthentication->name = config('authorizenet.login_id'); /*config('authorizenet.login_id');*/
        $data->deleteCustomerPaymentProfileRequest->merchantAuthentication->transactionKey = config('authorizenet.transaction_key'); /*config('authorizenet.transaction_key');*/
        $data->deleteCustomerPaymentProfileRequest->customerProfileId = "$authorizeNetCustomerId";
        $data->deleteCustomerPaymentProfileRequest->customerPaymentProfileId = "$customerPaymentProfileId";

        $response = Http::post($this->url, (array)$data);

        $responseData = $this->jsonDecode(trim($response->body()));

        if ($responseData['messages']['resultCode'] == "Error") {
            throw new Exception($responseData['messages']['message'][0]['text']);
        }

        AuthorizenetPaymentProfile::where("payment_profile_id", $customerPaymentProfileId)->delete();

        return $responseData;
    }

    public function chargeCustomerProfileTemplate()
    {
        return json_decode(
            '{
                "createTransactionRequest": {
                    "merchantAuthentication": {
                        "name": "9bSaKC66uHg",
                        "transactionKey": "8xszx7B7674QxHqe"
                    },
                    "transactionRequest": {
                        "transactionType": "authCaptureTransaction",
                        "amount": "45",
                        "profile": {
                            "customerProfileId": "40338125",
                            "paymentProfile": { "paymentProfileId": "1000177237" }
                        },
                        "order": {
                            "invoiceNumber": "656565"
                        },
                        "tax": {
                            "amount": "0",
                            "name": "No tax",
                            "description": "No Tax"
                        },
                        "authorizationIndicatorType": {
                            "authorizationIndicator": "final"
                        }
                    }
                }
            }'
        );
    }

    public function chargeCustomerProfile(
        $authorizeNetCustomerId,
        $customerPaymentProfileId,
        $order,
        $amount = 0
    ) {
        $data = $this->chargeCustomerProfileTemplate();
        // auth
        $data->createTransactionRequest->merchantAuthentication->name = config('authorizenet.login_id');
        $data->createTransactionRequest->merchantAuthentication->transactionKey = config('authorizenet.transaction_key');

        $data->createTransactionRequest->transactionRequest->profile->customerProfileId = "$authorizeNetCustomerId";
        $data->createTransactionRequest->transactionRequest->profile->paymentProfile->paymentProfileId = "$customerPaymentProfileId";
        $data->createTransactionRequest->transactionRequest->order->invoiceNumber =  $order->order_number;

        //If amount is specified use it, otherwise charge order total
        if ($amount === 0) {
            $amount = $order->total;
        }
        $data->createTransactionRequest->transactionRequest->amount = $amount;

        //send request
        $response = Http::post($this->url, (array)$data);

        //get response
        $transaction = $this->jsonDecode(trim($response->body()));

        info("Charging customer profile $authorizeNetCustomerId for order $order->order_number");
        info($transaction);

        return $transaction;
    }

    public function voidTransactionTemplate()
    {
        return json_decode('{
            "createTransactionRequest": {
                "merchantAuthentication": {
                    "name": "5KP3u95bQpv",
                    "transactionKey": "346HZ32z3fP4hTG2"
                },
                "transactionRequest": {
                    "transactionType": "voidTransaction",
                    "refTransId": "1234567890"
                }
            }
        }');
    }

    public function voidTransaction($refTransId)
    {
        $data = $this->voidTransactionTemplate();
        // auth
        $data->createTransactionRequest->merchantAuthentication->name = config('authorizenet.login_id');
        $data->createTransactionRequest->merchantAuthentication->transactionKey = config('authorizenet.transaction_key');

        $data->createTransactionRequest->transactionRequest->refTransId = $refTransId;

        //send request
        $response = Http::post($this->url, (array)$data);

        //get response
        $transaction = $this->jsonDecode(trim($response->body()));

        return $transaction;
    }

    public function deleteCustomerProfileTemplate()
    {
        return json_decode('{
            "deleteCustomerProfileRequest": {
                "merchantAuthentication": {
                    "name": "5KP3u95bQpv",
                    "transactionKey": "346HZ32z3fP4hTG2"
                },
                "customerProfileId": "10000"
            }
        }');
    }

    public function deleteCustomerProfile($customerProfileId)
    {
        $data = $this->deleteCustomerProfileTemplate();
        // auth
        $data->deleteCustomerProfileRequest->merchantAuthentication->name = config('authorizenet.login_id');
        $data->deleteCustomerProfileRequest->merchantAuthentication->transactionKey = config('authorizenet.transaction_key');

        $data->deleteCustomerProfileRequest->customerProfileId = $customerProfileId;

        //send request
        $response = Http::post($this->url, (array)$data);

        //get response
        info($this->jsonDecode(trim($response->body())));
    }

    public function addCard($cardInfo, $billTo, $cardVisibility, $cardOwner, $officeCardShared)
    {
        //Get customer profile ID from users table
        $authorizeNetCustomerId = $cardOwner->authorizenet_profile_id;

        //check if profile exists in AuthNet
        $getProfile = $this->getCustomerProfile($authorizeNetCustomerId);
        if (isset($getProfile['profile'])) {
            //Add card to existing customer profile by creating payment profile
            $createPaymentProfile = $this->createPaymentProfile($cardInfo, $billTo, $authorizeNetCustomerId);

            if ($createPaymentProfile['messages']['resultCode'] == "Error") {
                return $createPaymentProfile;
            }

            //Store the payment profile in db
            if (isset($createPaymentProfile['customerPaymentProfileId'])) {
                $paymentProfile = $createPaymentProfile['customerPaymentProfileId'];

                AuthorizenetPaymentProfile::create([
                        'user_id' => $cardOwner->id,
                        'payment_profile_id' => $paymentProfile,
                        'authorizenet_profile_id' => $authorizeNetCustomerId,
                        'order_id' => null,
                        'order_type' => null,
                        "card_shared_with" => $cardVisibility,
                        "office_card_visible_agents" => $officeCardShared
                ]);
            }

            return $createPaymentProfile;
        } else {
            //create profile
            //if customer profile doesn't exist then create one
            $profile = $this->createProfile(
                [
                    "email" => $billTo['email'],
                    "cardNumber" => $cardInfo['cardNumber'],
                    "expirationDate" => $cardInfo['expirationDate'],
                    "cardCode" => $cardInfo['cardCode'],
                    "cardOwner" => $cardOwner,
                    "billTo" => $billTo,
                    "card_shared_with" => $cardVisibility,
                    "office_card_visible_agents" => $officeCardShared
                ]
            );

            if ($profile['messages']['resultCode'] == "Error") {
                return $profile;
            }

            $paymentProfile = $profile['customerPaymentProfileIdList'][0];
            $authorizeNetCustomerId = $profile['customerProfileId'];

            return $profile;
        }
    }
}
