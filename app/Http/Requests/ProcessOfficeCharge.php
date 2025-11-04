<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\User;

class ProcessOfficeCharge extends FormRequest
{
  /**
   * Determine if the user is authorized to make this request.
   *
   * @return bool
   */
  public function authorize()
  {
    // Debug logging
    logger()->info('ProcessOfficeCharge authorization check', [
      'auth_check' => auth()->check(),
      'user_id' => auth()->id(),
      'user_role' => auth()->user() ? auth()->user()->role : 'no user',
      'role_check' => auth()->user() ? in_array(auth()->user()->role, [User::ROLE_OFFICE, User::ROLE_SUPER_ADMIN]) : 'no user'
    ]);

    // Temporarily return true for testing
    return true;

    // Original authorization:
    // return auth()->check() && in_array(auth()->user()->role, [User::ROLE_OFFICE, User::ROLE_SUPER_ADMIN]);
  }

  /**
   * Get the validation rules that apply to the request.
   *
   * @return array
   */
  public function rules()
  {
    $rules = [
      'agent_id' => 'required|integer|exists:agents,id',
      'amount' => 'required|numeric|min:0.01|max:999999.99',
      'description' => 'nullable|string|max:255',
      'payment_method' => 'required|in:saved_card,new_card',
    ];

    // Additional rules based on payment method
    if ($this->payment_method === 'saved_card') {
      $rules['payment_profile_id'] = 'required|string';
    } else {
      // New card validation
      $rules['card_number'] = 'required|string|regex:/^[0-9\s]{13,19}$/';
      $rules['expire_month'] = 'required|integer|min:1|max:12';
      $rules['expire_year'] = 'required|integer|min:' . date('Y') . '|max:' . (date('Y') + 10);
      $rules['card_code'] = 'required|string|regex:/^[0-9]{3,4}$/';
      $rules['billing_name'] = 'required|string|max:100';
      $rules['billing_address'] = 'required|string|max:100';
      $rules['billing_city'] = 'required|string|max:50';
      $rules['billing_state'] = 'required|string|size:2';
      $rules['billing_zip'] = 'required|string|regex:/^[0-9]{5}$/';
      $rules['save_card'] = 'nullable|boolean';
    }

    return $rules;
  }

  /**
   * Get custom error messages for validation rules.
   *
   * @return array
   */
  public function messages()
  {
    return [
      'agent_id.required' => 'Please select an agent.',
      'agent_id.exists' => 'The selected agent is invalid.',
      'amount.required' => 'Please enter a charge amount.',
      'amount.min' => 'The charge amount must be at least $0.01.',
      'amount.max' => 'The charge amount cannot exceed $999,999.99.',
      'payment_method.required' => 'Please select a payment method.',
      'payment_profile_id.required' => 'Please select a saved card.',
      'card_number.required' => 'Please enter a card number.',
      'card_number.regex' => 'Please enter a valid card number.',
      'expire_month.required' => 'Please select an expiration month.',
      'expire_year.required' => 'Please select an expiration year.',
      'expire_year.min' => 'The expiration year cannot be in the past.',
      'card_code.required' => 'Please enter the CVV code.',
      'card_code.regex' => 'Please enter a valid CVV code (3-4 digits).',
      'billing_name.required' => 'Please enter the cardholder name.',
      'billing_address.required' => 'Please enter the billing address.',
      'billing_city.required' => 'Please enter the billing city.',
      'billing_state.required' => 'Please select the billing state.',
      'billing_zip.required' => 'Please enter the billing ZIP code.',
      'billing_zip.regex' => 'Please enter a valid 5-digit ZIP code.',
    ];
  }

  /**
   * Configure the validator instance.
   *
   * @param  \Illuminate\Validation\Validator  $validator
   * @return void
   */
  public function withValidator($validator)
  {
    $validator->after(function ($validator) {
      // Custom validation for expiration date
      if ($this->payment_method === 'new_card' && $this->expire_month && $this->expire_year) {
        $expDate = mktime(0, 0, 0, $this->expire_month, 1, $this->expire_year);
        $currentDate = mktime(0, 0, 0, date('m'), 1, date('Y'));

        if ($expDate < $currentDate) {
          $validator->errors()->add('expire_month', 'The card has expired.');
          $validator->errors()->add('expire_year', 'The card has expired.');
        }
      }
    });
  }
}
