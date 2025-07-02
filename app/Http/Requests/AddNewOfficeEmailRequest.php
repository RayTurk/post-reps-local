<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddNewOfficeEmailRequest extends FormRequest
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
            'office_id' => ['required', 'numeric'],
            'email' => ['required', 'email'],
            'user_email' => ['email'],
            'order' => ['numeric'],
            'accounting' => ['numeric']
        ];
    }

    public function messages()
    {
        return [
            'email.unique' => 'Email already in use. Please enter a different email.',
        ];
    }
}
