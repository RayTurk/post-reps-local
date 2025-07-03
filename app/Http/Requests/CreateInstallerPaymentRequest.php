<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateInstallerPaymentRequest extends FormRequest
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
            'user_id' => ['required', 'integer'],
            'payment_amount' => ['required', 'numeric'],
            'payment_check_number' => ['required', 'numeric'],
            'payment_comments' => ['required', 'string', 'min:3', 'max:255'],
        ];
    }
}
