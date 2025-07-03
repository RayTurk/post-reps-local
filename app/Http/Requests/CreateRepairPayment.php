<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateRepairPayment extends FormRequest
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
        if($_POST['repair_payment_type'] == 'use_card'){
            return [
                'repair_card_profile' => "required|string",
                'repair_order_id' => 'integer|required',
                'repair_payment_type' => 'string|required',
            ];
        }
        return [
            'repair_card_number' => 'string|required',
            'repair_card_code' => 'string|required',
            'repair_expire_date_month' => 'required',
            'repair_expire_date_year' => 'required',
            'repair_order_id' => 'integer|required',
        ];
    }


}
