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
    return auth()->check() && in_array(auth()->user()->role, [User::ROLE_OFFICE, User::ROLE_SUPER_ADMIN]);
  }

  /**
   * Get the validation rules that apply to the request.
   *
   * @return array
   */
  public function rules()
  {
    $rules = [
      'agent_id' => 'required|exists:agents,id',
      'amount' => 'required|numeric|min:0.01|max:999999.99',
      'description' => 'nullable|string|max:255',
      'payment_method' => 'required|in:saved_card,new_card',
    ];

    if ($this->input('payment_method') === 'saved_card') {
      $rules['payment_profile_id'] = 'required|string';
    } else {
      $rules['card_number'] = 'required|string|min:13|max:19';
      $rules['expire_month'] = 'required|integer|min:1|max:12';
      $rules['expire_year'] = 'required|integer|min:' . date('Y');
      $rules['card_code'] = 'required|string|min:3|max:4';
      $rules['billing_name'] = 'required|string|max:255';
      $rules['billing_address'] = 'required|string|max:255';
      $rules['billing_city'] = 'required|string|max:255';
      $rules['billing_state'] = 'required|string|size:2';
      $rules['billing_zip'] = 'required|string|min:5|max:10';
      $rules['save_card'] = 'nullable|boolean';
    }

    return $rules;
  }

  /**
   * Get custom messages for validator errors.
   *
   * @return array
   */
  public function messages()
  {
    return [
      'agent_id.required' => 'Please select an agent.',
      'agent_id.exists' => 'Invalid agent selected.',
      'amount.required' => 'Please enter an amount to charge.',
      'amount.numeric' => 'Amount must be a valid number.',
      'amount.min' => 'Amount must be at least $0.01.',
      'amount.max' => 'Amount cannot exceed $999,999.99.',
      'payment_method.required' => 'Please select a payment method.',
      'payment_profile_id.required' => 'Please select a saved card.',
      'card_number.required' => 'Card number is required.',
      'expire_month.required' => 'Expiration month is required.',
      'expire_year.required' => 'Expiration year is required.',
      'card_code.required' => 'Security code is required.',
    ];
  }
}
