<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateDeliveryPayment extends FormRequest
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
        if($_POST['delivery_payment_type'] == 'use_card'){
            return [
                'delivery_card_profile' => "required|string",
                'delivery_order_id' => 'integer|required',
                'delivery_payment_type' => 'string|required',
            ];
        }
        return [
            'delivery_card_number' => 'string|required',
            'delivery_card_code' => 'string|required',
            'delivery_expire_date_month' => 'required',
            'delivery_expire_date_year' => 'required',
            'delivery_order_id' => 'integer|required',
        ];
    }


}
