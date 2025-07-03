<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddCard extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'add_card_number' => 'required',
            'add_card_code' => 'required',
            'add_card_expire_date_month' => 'required',
            'add_card_expire_date_year' => 'required',
            'add_card_billing_name' => 'string|required',
            'add_card_billing_address' => 'string|required',
            'add_card_billing_city' => 'string|required',
            'add_card_billing_state' => 'string|required',
            'add_card_billing_zip' => 'string|required'
        ];
    }

    public function messages()
    {
        return [
            'add_card_number.required' => 'Card number is required.',
            'add_card_code.required' => 'Card code is required.',
            'add_card_expire_date_month.required' => 'Card expiration month is required.',
            'add_card_expire_date_year.required' => 'Card expiration year is required.',
            'add_card_billing_name.required' => 'Billing name is required.',
            'add_card_billing_address.required' => 'Billing address is required.',
            'add_card_billing_city.required' => 'Billing city is required.',
            'add_card_billing_state.required' => 'Billing state is required.',
            'add_card_billing_zip.required' => 'Billing zip is required.'
        ];
    }
}
