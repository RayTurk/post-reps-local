<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAgent extends FormRequest
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
            'first_name' => 'string|required',
            'last_name' => 'string|required',
            'email' => 'required|email|unique:users,email,' . $this->user_id,
            'phone' => 'string|required',
            'city' => 'string|required',
            'state' => 'string|required',
            'zipcode' => 'string|required',
            're_license' => 'nullable|string',
            'address' => 'required|string',
            'agent_office' => 'required|numeric',
            'inactive' => 'required|numeric',
        ];
    }
}
