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

  /**
   * Convert stdClass object to array recursively
   * This fixes the Http::post() TypeError issue
   */
  private function objectToArray($object)
  {
    return json_decode(json_encode($object), true);
  }

  /**
   * Convert HTTP response to expected object format
   */
  private function processResponse($response)
  {
    if ($response->successful()) {
      // Use our custom jsonDecode that handles encoding issues
      $responseData = $this->jsonDecode(trim($response->body()));

      // Convert array to object for consistency with existing code
      return json_decode(json_encode($responseData));
    } else {
      return (object)[
        'messages' => (object)[
          'resultCode' => 'Error',
          'message' => [(object)['text' => 'HTTP Error: ' . $response->status()]]
        ]
      ];
    }
  }

  public function jsonDecode($jsonString)
  {
    return json_decode(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $jsonString), true);
  }

  public function genId($prefix)
  {
    return str_replace('.', '_', uniqid($prefix . microtime(true)));
  }

  public function testCredentials()
  {
    $data = [
      "authenticateTestRequest" => [
        "merchantAuthentication" => [
          "name" => config('authorizenet.login_id'),
          "transactionKey" => config('authorizenet.transaction_key')
        ]
      ]
    ];

    $response = Http::post($this->url, $data);
    $result = json_decode($response->body(), true);

    return $result;
  }

  public function template()
  {
    $template = [
      "createTransactionRequest" => [
        "merchantAuthentication" => [
          "name" => config('authorizenet.login_id'),
          "transactionKey" => config('authorizenet.transaction_key')
        ],
        "transactionRequest" => [
          "transactionType" => "authOnlyTransaction",
          "amount" => "5",
          "payment" => [
            "creditCard" => [
              "cardNumber" => "5424000000000015",
              "expirationDate" => "2025-12",
              "cardCode" => "999"
            ]
          ],
          "order" => [
            "invoiceNumber" => "656565"
          ],
          "tax" => [
            "amount" => "0",
            "name" => "No tax",
            "description" => "No Tax"
          ],
          "billTo" => [
            "firstName" => "Ellen",
            "lastName" => "Johnson",
            "company" => "Souveniropolis",
            "address" => "14 Main Street",
            "city" => "Pecan Springs",
            "state" => "TX",
            "zip" => "44628",
            "country" => "US"
          ],
          "authorizationIndicatorType" => [
            "authorizationIndicator" => "final"
          ]
        ]
      ]
    ];

    return json_decode(json_encode($template));
  }

  public function createProfileTemplate()
  {
    return json_decode('{
      "createCustomerProfileRequest": {
        "merchantAuthentication": {
          "name": "",
          "transactionKey": ""
        },
        "profile": {
          "merchantCustomerId": "",
          "description": "Profile description here",
          "email": "",
          "paymentProfiles": {
            "customerType": "individual",
            "billTo": {
              "firstName": "",
              "lastName": "",
              "address": "",
              "city": "",
              "state": "",
              "zip": ""
            },
            "payment": {
              "creditCard": {
                "cardNumber": "",
                "expirationDate": "",
                "cardCode": ""
              }
            }
          }
        },
        "validationMode": "liveMode"
      }
    }');
  }

  public function createProfile($params)
  {
    $data = $this->createProfileTemplate();

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

    $response = Http::post($this->url, $this->objectToArray($data));
    $transaction = $this->jsonDecode(trim($response->body()));
    logger($transaction);

    if (isset($transaction['customerProfileId'])) {
      $cardOwner = $params['cardOwner'];
      $cardOwner->authorizenet_profile_id = $transaction['customerProfileId'];
      $cardOwner->save();

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

  public function createCustomerProfile($params)
  {
    $data = json_decode('{
      "createCustomerProfileRequest": {
        "merchantAuthentication": {
          "name": "",
          "transactionKey": ""
        },
        "profile": {
          "merchantCustomerId": "",
          "description": "",
          "email": ""
        }
      }
    }');

    $data->createCustomerProfileRequest->merchantAuthentication->name = config('authorizenet.login_id');
    $data->createCustomerProfileRequest->merchantAuthentication->transactionKey = config('authorizenet.transaction_key');
    $data->createCustomerProfileRequest->profile->merchantCustomerId = $params['merchantCustomerId'];
    $data->createCustomerProfileRequest->profile->description = $params['description'];
    $data->createCustomerProfileRequest->profile->email = $params['email'];

    $response = \Illuminate\Support\Facades\Http::post($this->url, $this->objectToArray($data));

    if ($response->successful()) {
      $responseBody = trim($response->body());
      $transaction = $this->jsonDecode($responseBody);
      return $transaction;
    } else {
      return [
        'messages' => [
          'resultCode' => 'Error',
          'message' => [['text' => 'HTTP Error: ' . $response->status()]]
        ]
      ];
    }
  }

  public function getCustomerProfileTemplate()
  {
    return json_decode('{
      "getCustomerProfileRequest": {
        "merchantAuthentication": {
          "name": "",
          "transactionKey": ""
        },
        "customerProfileId": ""
      }
    }');
  }

  public function getCustomerProfile($customerProfileId)
  {
    $data = $this->getCustomerProfileTemplate();

    $data->getCustomerProfileRequest->merchantAuthentication->name = config('authorizenet.login_id');
    $data->getCustomerProfileRequest->merchantAuthentication->transactionKey = config('authorizenet.transaction_key');
    $data->getCustomerProfileRequest->customerProfileId = "$customerProfileId";

    $response = Http::post($this->url, $this->objectToArray($data));
    $profile = $this->jsonDecode(trim($response->body()));

    return $profile;
  }

  public function createPaymentProfileTemplate()
  {
    return json_decode('{
      "createCustomerPaymentProfileRequest": {
        "merchantAuthentication": {
          "name": "",
          "transactionKey": ""
        },
        "customerProfileId": "",
        "paymentProfile": {
          "billTo": {
            "firstName": "",
            "lastName": "",
            "address": "",
            "city": "",
            "state": "",
            "zip": ""
          },
          "payment": {
            "creditCard": {
              "cardNumber": "",
              "expirationDate": "",
              "cardCode": ""
            }
          }
        },
        "validationMode": "liveMode"
      }
    }');
  }

  /**
   * Create a payment profile for an existing customer
   *
   * @param array $cardInfo Card information
   * @param array $billTo Billing information
   * @param string $authorizeNetCustomerId Customer profile ID
   * @return array Response from Authorize.Net
   */
  public function createPaymentProfile($cardInfo, $billTo, $authorizeNetCustomerId)
  {
    $data = $this->createPaymentProfileTemplate();

    $data->createCustomerPaymentProfileRequest->merchantAuthentication->name = config('authorizenet.login_id');
    $data->createCustomerPaymentProfileRequest->merchantAuthentication->transactionKey = config('authorizenet.transaction_key');
    $data->createCustomerPaymentProfileRequest->customerProfileId = "$authorizeNetCustomerId";

    // Set card info
    $data->createCustomerPaymentProfileRequest->paymentProfile->payment->creditCard->cardNumber = $cardInfo['cardNumber'];
    $data->createCustomerPaymentProfileRequest->paymentProfile->payment->creditCard->expirationDate = $cardInfo['expirationDate'];
    $data->createCustomerPaymentProfileRequest->paymentProfile->payment->creditCard->cardCode = $cardInfo['cardCode'];

    // Set billing info
    $data->createCustomerPaymentProfileRequest->paymentProfile->billTo->firstName = $billTo['first_name'];
    $data->createCustomerPaymentProfileRequest->paymentProfile->billTo->lastName = $billTo['last_name'];
    $data->createCustomerPaymentProfileRequest->paymentProfile->billTo->address = $billTo['address'];
    $data->createCustomerPaymentProfileRequest->paymentProfile->billTo->city = $billTo['city'];
    $data->createCustomerPaymentProfileRequest->paymentProfile->billTo->state = $billTo['state'];
    $data->createCustomerPaymentProfileRequest->paymentProfile->billTo->zip = $billTo['zipcode'];

    $data->createCustomerPaymentProfileRequest->validationMode = $this->validationMode;

    $response = Http::post($this->url, $this->objectToArray($data));
    $transaction = $this->jsonDecode(trim($response->body()));

    logger('createPaymentProfile response:', $transaction);

    return $transaction;
  }

  public function getPaymentProfile($authorizeNetCustomerId, $customerPaymentProfileId)
  {
    $data = $this->getPaymentProfileTemplate();

    $data->getCustomerPaymentProfileRequest->merchantAuthentication->name = config('authorizenet.login_id');
    $data->getCustomerPaymentProfileRequest->merchantAuthentication->transactionKey = config('authorizenet.transaction_key');
    $data->getCustomerPaymentProfileRequest->customerProfileId = "$authorizeNetCustomerId";
    $data->getCustomerPaymentProfileRequest->customerPaymentProfileId = "$customerPaymentProfileId";

    $response = \Illuminate\Support\Facades\Http::post($this->url, $this->objectToArray($data));

    if ($response->successful()) {
      $responseBody = trim($response->body());
      $paymentProfile = $this->jsonDecode($responseBody);
      return $paymentProfile;
    } else {
      return [
        'messages' => [
          'resultCode' => 'Error',
          'message' => [['text' => 'HTTP Error: ' . $response->status()]]
        ]
      ];
    }
  }

  public function chargeCardTemplate()
  {
    return json_decode('{
      "createTransactionRequest": {
        "merchantAuthentication": {
          "name": "",
          "transactionKey": ""
        },
        "transactionRequest": {
          "transactionType": "authCaptureTransaction",
          "amount": "0.00",
          "payment": {
            "creditCard": {
              "cardNumber": "",
              "expirationDate": "",
              "cardCode": ""
            }
          },
          "order": {
            "invoiceNumber": "",
            "description": ""
          },
          "billTo": {
            "firstName": "",
            "lastName": "",
            "address": "",
            "city": "",
            "state": "",
            "zip": ""
          }
        }
      }
    }');
  }

  public function chargeCard($cardInfo, $billTo, $amount, $description = '')
  {
    $data = $this->chargeCardTemplate();

    $data->createTransactionRequest->merchantAuthentication->name = config('authorizenet.login_id');
    $data->createTransactionRequest->merchantAuthentication->transactionKey = config('authorizenet.transaction_key');

    $data->createTransactionRequest->transactionRequest->amount = $amount;
    $data->createTransactionRequest->transactionRequest->payment->creditCard->cardNumber = $cardInfo['cardNumber'];
    $data->createTransactionRequest->transactionRequest->payment->creditCard->expirationDate = $cardInfo['expirationDate'];
    $data->createTransactionRequest->transactionRequest->payment->creditCard->cardCode = $cardInfo['cardCode'];

    $data->createTransactionRequest->transactionRequest->billTo->firstName = $billTo['first_name'] ?? '';
    $data->createTransactionRequest->transactionRequest->billTo->lastName = $billTo['last_name'] ?? '';
    $data->createTransactionRequest->transactionRequest->billTo->address = $billTo['address'] ?? '';
    $data->createTransactionRequest->transactionRequest->billTo->city = $billTo['city'] ?? '';
    $data->createTransactionRequest->transactionRequest->billTo->state = $billTo['state'] ?? '';
    $data->createTransactionRequest->transactionRequest->billTo->zip = $billTo['zipcode'] ?? '';

    $data->createTransactionRequest->transactionRequest->order->invoiceNumber = 'CHG-' . time();
    if ($description) {
      $data->createTransactionRequest->transactionRequest->order->description = substr($description, 0, 255);
    }

    $response = \Illuminate\Support\Facades\Http::post($this->url, $this->objectToArray($data));

    if ($response->successful()) {
      $responseBody = trim($response->body());

      // Use existing jsonDecode method that handles encoding issues
      $responseData = $this->jsonDecode($responseBody);
      $transaction = json_decode(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $responseBody));

      logger()->info('Direct card charge', ['amount' => $amount, 'response' => $responseData]);
      return $transaction;
    } else {
      logger()->error('HTTP Error from Authorize.Net', [
        'status' => $response->status(),
        'body' => $response->body()
      ]);

      return (object)[
        'messages' => (object)[
          'resultCode' => 'Error',
          'message' => [(object)['text' => 'HTTP Error: ' . $response->status()]]
        ]
      ];
    }
  }

  public function chargeCustomerProfile($customerProfileId, $paymentProfileId, $amount, $description = '')
  {
    logger()->info('chargeCustomerProfile called', [
      'customer_profile_id' => $customerProfileId,
      'payment_profile_id' => $paymentProfileId,
      'amount' => $amount,
      'description' => $description
    ]);

    $data = json_decode('{
        "createTransactionRequest": {
            "merchantAuthentication": {
                "name": "",
                "transactionKey": ""
            },
            "transactionRequest": {
                "transactionType": "authCaptureTransaction",
                "amount": "0.00",
                "profile": {
                    "customerProfileId": "",
                    "paymentProfile": {
                        "paymentProfileId": ""
                    }
                },
                "order": {
                    "description": ""
                }
            }
        }
    }');

    $data->createTransactionRequest->merchantAuthentication->name = config('authorizenet.login_id');
    $data->createTransactionRequest->merchantAuthentication->transactionKey = config('authorizenet.transaction_key');

    // Cast to float before formatting
    $data->createTransactionRequest->transactionRequest->amount = number_format((float)$amount, 2, '.', '');

    // Cast IDs to string
    $data->createTransactionRequest->transactionRequest->profile->customerProfileId = (string)$customerProfileId;
    $data->createTransactionRequest->transactionRequest->profile->paymentProfile->paymentProfileId = (string)$paymentProfileId;
    $data->createTransactionRequest->transactionRequest->order->description = substr($description, 0, 255);

    logger()->info('Sending request to Authorize.Net', [
      'url' => $this->url,
      'amount' => $data->createTransactionRequest->transactionRequest->amount,
      'customer_profile_id' => $data->createTransactionRequest->transactionRequest->profile->customerProfileId,
      'payment_profile_id' => $data->createTransactionRequest->transactionRequest->profile->paymentProfile->paymentProfileId
    ]);

    try {
      $response = Http::post($this->url, $this->objectToArray($data));

      // Log the raw response for debugging
      logger()->info('Raw HTTP response from Authorize.Net', [
        'status' => $response->status(),
        'successful' => $response->successful(),
        'body' => $response->body(),
        'json' => $response->json()
      ]);

      $transaction = $this->processResponse($response);

      logger()->info('Processed transaction object', [
        'transaction' => $transaction
      ]);

      logger()->info('chargeCustomerProfile response', [
        'result_code' => $transaction->messages->resultCode ?? 'Unknown',
        'messages' => $transaction->messages ?? null,
        'transaction_response' => $transaction->transactionResponse ?? null
      ]);

      // Log error details if transaction failed
      if (isset($transaction->messages->resultCode) && $transaction->messages->resultCode === 'Error') {
        logger()->error('Authorize.Net charge failed', [
          'messages' => $transaction->messages,
          'transaction_response' => $transaction->transactionResponse ?? null
        ]);
      }

      return $transaction;
    } catch (\Exception $e) {
      logger()->error('Exception in chargeCustomerProfile', [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
      ]);
      throw $e;
    }
  }
  public function chargeCustomerProfileTemplate()
  {
    return json_decode('{
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
    }');
  }

  public function chargeCustomerProfileAuthOnlyTemplate()
  {
    return json_decode('{
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
    }');
  }

  public function chargeCustomerProfileAuthOnly($authorizeNetCustomerId, $customerPaymentProfileId, $order, $orderType, $cardVisibility, $cardOwner)
  {
    $data = $this->chargeCustomerProfileAuthOnlyTemplate();

    $data->createTransactionRequest->merchantAuthentication->name = config('authorizenet.login_id');
    $data->createTransactionRequest->merchantAuthentication->transactionKey = config('authorizenet.transaction_key');

    $data->createTransactionRequest->transactionRequest->profile->customerProfileId = "$authorizeNetCustomerId";
    $data->createTransactionRequest->transactionRequest->profile->paymentProfile->paymentProfileId = "$customerPaymentProfileId";
    $data->createTransactionRequest->transactionRequest->order->invoiceNumber = $order->order_number;
    $data->createTransactionRequest->transactionRequest->amount = $order->total;

    $response = Http::post($this->url, $this->objectToArray($data));
    info("chargeCustomerProfileAuthOnly for order $order->order_number");
    info($response);

    $transaction = $this->jsonDecode(trim($response->body()));

    if ($transaction['messages']['resultCode'] == "Ok") {
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
    }

    return $transaction;
  }

  public function chargeInvoiceCustomerProfileCaptureTemplate()
  {
    return json_decode('{
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
    }');
  }

  public function chargeInvoiceCustomerProfileCapture($authorizeNetCustomerId, $customerPaymentProfileId, $invoice, $fees = 0)
  {
    $data = $this->chargeInvoiceCustomerProfileCaptureTemplate();

    $data->createTransactionRequest->merchantAuthentication->name = config('authorizenet.login_id');
    $data->createTransactionRequest->merchantAuthentication->transactionKey = config('authorizenet.transaction_key');

    $data->createTransactionRequest->transactionRequest->profile->customerProfileId = "$authorizeNetCustomerId";
    $data->createTransactionRequest->transactionRequest->profile->paymentProfile->paymentProfileId = "$customerPaymentProfileId";
    $data->createTransactionRequest->transactionRequest->order->invoiceNumber = $invoice->invoice_number;
    $data->createTransactionRequest->transactionRequest->amount = $invoice->payment_amount + $fees;

    $response = Http::post($this->url, $this->objectToArray($data));
    $transaction = $this->processResponse($response);

    return $transaction;
  }

  public function createInvoicePayment($cardInfo, $billTo, $invoice, $fees = 0)
  {
    $data = $this->chargeCardTemplate();

    $data->createTransactionRequest->merchantAuthentication->name = config('authorizenet.login_id');
    $data->createTransactionRequest->merchantAuthentication->transactionKey = config('authorizenet.transaction_key');

    $data->createTransactionRequest->transactionRequest->payment->creditCard->cardNumber = $cardInfo['cardNumber'];
    $data->createTransactionRequest->transactionRequest->payment->creditCard->expirationDate = $cardInfo['expirationDate'];
    $data->createTransactionRequest->transactionRequest->payment->creditCard->cardCode = $cardInfo['cardCode'];

    $data->createTransactionRequest->transactionRequest->billTo->firstName = $billTo['first_name'];
    $data->createTransactionRequest->transactionRequest->billTo->lastName = $billTo['last_name'];
    $data->createTransactionRequest->transactionRequest->billTo->address = $billTo['address'];
    $data->createTransactionRequest->transactionRequest->billTo->city = $billTo['city'];
    $data->createTransactionRequest->transactionRequest->billTo->state = $billTo['state'];
    $data->createTransactionRequest->transactionRequest->billTo->zip = $billTo['zipcode'];

    $data->createTransactionRequest->transactionRequest->amount = $invoice->payment_amount + $fees;
    $data->createTransactionRequest->transactionRequest->order->invoiceNumber = $invoice->invoice_number;

    $response = Http::post($this->url, $this->objectToArray($data));
    $transaction = $this->processResponse($response);

    $cardOwner = User::find($billTo['userId']);

    $getProfile = $this->getCustomerProfile($cardOwner->authorizenet_profile_id);
    if (isset($getProfile['profile'])) {
      $createPaymentProfile = $this->createPaymentProfile($cardInfo, $billTo, $cardOwner->authorizenet_profile_id);

      if (isset($createPaymentProfile['customerPaymentProfileId'])) {
        AuthorizenetPaymentProfile::updateOrCreate(
          ['payment_profile_id' => $createPaymentProfile['customerPaymentProfileId']],
          ['user_id' => $cardOwner->id]
        );
      }
    } else {
      $this->createProfile([
        "email" => $billTo['email'],
        "cardNumber" => $cardInfo['cardNumber'],
        "expirationDate" => $cardInfo['expirationDate'],
        "cardCode" => $cardInfo['cardCode'],
        "cardOwner" => $cardOwner,
        "billTo" => $billTo
      ]);
    }

    return $transaction;
  }

  public function getPaymentProfileTemplate()
  {
    return json_decode('{
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
    }');
  }

  public function voidTransactionTemplate()
  {
    return json_decode(json_encode([
      "createTransactionRequest" => [
        "merchantAuthentication" => [
          "name" => config('authorizenet.login_id'),
          "transactionKey" => config('authorizenet.transaction_key')
        ],
        "transactionRequest" => [
          "transactionType" => "voidTransaction",
          "refTransId" => "1234567890"
        ]
      ]
    ]));
  }

  public function voidTransaction($refTransId)
  {
    $data = $this->voidTransactionTemplate();

    $data->createTransactionRequest->merchantAuthentication->name = config('authorizenet.login_id');
    $data->createTransactionRequest->merchantAuthentication->transactionKey = config('authorizenet.transaction_key');
    $data->createTransactionRequest->transactionRequest->refTransId = $refTransId;

    $response = Http::post($this->url, $this->objectToArray($data));
    $transaction = $this->processResponse($response);

    return $transaction;
  }

  public function refundTemplate()
  {
    return json_decode('{
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
    }');
  }

  public function refundCardPayment($authorizeNetCustomerId, $customerPaymentProfileId, $transId, $amount)
  {
    $data = $this->refundTemplate();

    $data->createTransactionRequest->merchantAuthentication->name = config('authorizenet.login_id');
    $data->createTransactionRequest->merchantAuthentication->transactionKey = config('authorizenet.transaction_key');

    $data->createTransactionRequest->transactionRequest->profile->customerProfileId = "$authorizeNetCustomerId";
    $data->createTransactionRequest->transactionRequest->profile->paymentProfile->paymentProfileId = "$customerPaymentProfileId";
    $data->createTransactionRequest->transactionRequest->refTransId = "$transId";
    $data->createTransactionRequest->transactionRequest->order->invoiceNumber = "REF-{$transId}";
    $data->createTransactionRequest->transactionRequest->amount = $amount;

    $response = Http::post($this->url, $this->objectToArray($data));
    $transaction = $this->processResponse($response);

    return $transaction;
  }

  public function captureTransaction($transId, $amount)
  {
    $data = json_decode('{
      "createTransactionRequest": {
        "merchantAuthentication": {
          "name": "",
          "transactionKey": ""
        },
        "transactionRequest": {
          "transactionType": "priorAuthCaptureTransaction",
          "amount": "0.00",
          "refTransId": ""
        }
      }
    }');

    $data->createTransactionRequest->merchantAuthentication->name = config('authorizenet.login_id');
    $data->createTransactionRequest->merchantAuthentication->transactionKey = config('authorizenet.transaction_key');
    $data->createTransactionRequest->transactionRequest->amount = $amount;
    $data->createTransactionRequest->transactionRequest->refTransId = $transId;

    $response = Http::post($this->url, $this->objectToArray($data));
    $transaction = $this->processResponse($response);

    info("Capturing payment of $$amount for transId $transId");
    info($transaction);

    return $transaction;
  }

  public function deleteCustomerProfileTemplate()
  {
    return json_decode(json_encode([
      "deleteCustomerProfileRequest" => [
        "merchantAuthentication" => [
          "name" => config('authorizenet.login_id'),
          "transactionKey" => config('authorizenet.transaction_key')
        ],
        "customerProfileId" => "10000"
      ]
    ]));
  }

  public function deleteCustomerProfile($customerProfileId)
  {
    $data = $this->deleteCustomerProfileTemplate();

    $data->deleteCustomerProfileRequest->merchantAuthentication->name = config('authorizenet.login_id');
    $data->deleteCustomerProfileRequest->merchantAuthentication->transactionKey = config('authorizenet.transaction_key');
    $data->deleteCustomerProfileRequest->customerProfileId = $customerProfileId;

    $response = Http::post($this->url, $this->objectToArray($data));
    info($this->jsonDecode(trim($response->body())));
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

    $data->deleteCustomerPaymentProfileRequest->merchantAuthentication->name = config('authorizenet.login_id');
    $data->deleteCustomerPaymentProfileRequest->merchantAuthentication->transactionKey = config('authorizenet.transaction_key');
    $data->deleteCustomerPaymentProfileRequest->customerProfileId = "$authorizeNetCustomerId";
    $data->deleteCustomerPaymentProfileRequest->customerPaymentProfileId = "$customerPaymentProfileId";

    $response = Http::post($this->url, $this->objectToArray($data));
    $responseData = $this->jsonDecode(trim($response->body()));

    if ($responseData['messages']['resultCode'] == "Error") {
      throw new Exception($responseData['messages']['message'][0]['text']);
    }

    AuthorizenetPaymentProfile::where("payment_profile_id", $customerPaymentProfileId)->delete();

    return $responseData;
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

    $response = Http::post($this->url, $this->objectToArray($data));
    $transaction = $this->processResponse($response);

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

    $response = Http::post($this->url, $this->objectToArray($data));
    $transaction = $this->processResponse($response);

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

    $response = Http::post($this->url, $this->objectToArray($data));
    $transaction = $this->processResponse($response);

    return $transaction;
  }

  public function createPayment($cardInfo, $billTo, $order, $orderType)
  {
    $data = $this->template();

    $data->createTransactionRequest->merchantAuthentication->name = config('authorizenet.login_id');
    $data->createTransactionRequest->merchantAuthentication->transactionKey = config('authorizenet.transaction_key');

    $data->createTransactionRequest->transactionRequest->payment->creditCard->cardNumber = $cardInfo['cardNumber'];
    $data->createTransactionRequest->transactionRequest->payment->creditCard->expirationDate = $cardInfo['expirationDate'];
    $data->createTransactionRequest->transactionRequest->payment->creditCard->cardCode = $cardInfo['cardCode'];

    $data->createTransactionRequest->transactionRequest->billTo->firstName = $billTo['first_name'];
    $data->createTransactionRequest->transactionRequest->billTo->lastName = $billTo['last_name'];
    $data->createTransactionRequest->transactionRequest->billTo->address = $billTo['address'];
    $data->createTransactionRequest->transactionRequest->billTo->city = $billTo['city'];
    $data->createTransactionRequest->transactionRequest->billTo->state = $billTo['state'];
    $data->createTransactionRequest->transactionRequest->billTo->zip = $billTo['zipcode'];

    $data->createTransactionRequest->transactionRequest->amount = $order->total;
    $data->createTransactionRequest->transactionRequest->order->invoiceNumber = $order->order_number;

    $response = Http::post($this->url, $this->objectToArray($data));
    $transaction = $this->processResponse($response);

    $cardOwner = User::find($billTo['userId']);

    $getProfile = $this->getCustomerProfile($cardOwner->authorizenet_profile_id);
    if (isset($getProfile['profile'])) {
      $createPaymentProfile = $this->createPaymentProfile($cardInfo, $billTo, $cardOwner->authorizenet_profile_id);

      if (isset($createPaymentProfile['customerPaymentProfileId'])) {
        AuthorizenetPaymentProfile::updateOrCreate(
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
      $this->createProfile([
        "email" => $billTo['email'],
        "cardNumber" => $cardInfo['cardNumber'],
        "expirationDate" => $cardInfo['expirationDate'],
        "cardCode" => $cardInfo['cardCode'],
        "cardOwner" => $cardOwner,
        'order_id' => $order->id,
        'order_type' => $orderType,
        "billTo" => $billTo
      ]);
    }

    return $transaction;
  }

  public function authrorizeCardFromProfile($cardInfo, $billTo, $order, $orderType, $cardVisibility, $cardOwner)
  {
    $authorizeNetCustomerId = $cardOwner->authorizenet_profile_id;

    $getProfile = $this->getCustomerProfile($authorizeNetCustomerId);
    if (isset($getProfile['profile'])) {
      $createPaymentProfile = $this->createPaymentProfile($cardInfo, $billTo, $authorizeNetCustomerId);
      info("authrorizeCardFromProfile createPaymentProfile for new card: order $order->order_number");
      info($createPaymentProfile);

      if ($createPaymentProfile['messages']['resultCode'] == "Error") {
        return $createPaymentProfile;
      }

      if (isset($createPaymentProfile['customerPaymentProfileId'])) {
        $paymentProfile = $createPaymentProfile['customerPaymentProfileId'];

        AuthorizenetPaymentProfile::updateOrCreate(
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
      $profile = $this->createProfile([
        "email" => $billTo['email'],
        "cardNumber" => $cardInfo['cardNumber'],
        "expirationDate" => $cardInfo['expirationDate'],
        "cardCode" => $cardInfo['cardCode'],
        "cardOwner" => $cardOwner,
        'order_id' => $order->id,
        'order_type' => $orderType,
        "billTo" => $billTo,
        "card_shared_with" => $cardVisibility
      ]);

      if ($profile['messages']['resultCode'] == "Error") {
        return $profile;
      }

      $paymentProfile = $profile['customerPaymentProfileIdList'][0];
      $authorizeNetCustomerId = $profile['customerProfileId'];
    }

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

  public function addCard($cardInfo, $billTo, $cardVisibility, $cardOwner, $officeCardShared)
  {
    $authorizeNetCustomerId = $cardOwner->authorizenet_profile_id;

    $getProfile = $this->getCustomerProfile($authorizeNetCustomerId);
    if (isset($getProfile['profile'])) {
      $createPaymentProfile = $this->createPaymentProfile($cardInfo, $billTo, $authorizeNetCustomerId);

      if ($createPaymentProfile['messages']['resultCode'] == "Error") {
        return $createPaymentProfile;
      }

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
      $profile = $this->createProfile([
        "email" => $billTo['email'],
        "cardNumber" => $cardInfo['cardNumber'],
        "expirationDate" => $cardInfo['expirationDate'],
        "cardCode" => $cardInfo['cardCode'],
        "cardOwner" => $cardOwner,
        "billTo" => $billTo,
        "card_shared_with" => $cardVisibility,
        "office_card_visible_agents" => $officeCardShared
      ]);

      if ($profile['messages']['resultCode'] == "Error") {
        return $profile;
      }

      return $profile;
    }
  }
}
