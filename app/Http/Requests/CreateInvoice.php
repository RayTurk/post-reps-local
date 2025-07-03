<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateInvoice extends FormRequest
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
            'create_invoice_office' => ['sometimes', 'required', 'integer'],
            'create_invoice_agent' => [],
            'from_date' => ['required','date'],
            'to_date' => ['required', 'date', 'after:from_date'],
        ];
    }
}
