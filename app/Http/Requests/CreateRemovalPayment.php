<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateRemovalPayment extends FormRequest
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
        if($_POST['removal_payment_type'] == 'use_card'){
            return [
                'removal_card_profile' => "required|string",
                'removal_order_id' => 'integer|required',
                'removal_payment_type' => 'string|required',
            ];
        }
        return [
            'removal_card_number' => 'string|required',
            'removal_card_code' => 'string|required',
            'removal_expire_date_month' => 'required',
            'removal_expire_date_year' => 'required',
            'removal_order_id' => 'integer|required',
        ];
    }


}
