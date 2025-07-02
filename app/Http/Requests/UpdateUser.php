<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUser extends FormRequest
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
            'first_name' => 'sometimes|required',
            'last_name' => 'sometimes|required',
            'email' => 'sometimes|email|unique:users,email,' . $this->id,
            'phone' => 'sometimes|required',
            'city' => 'sometimes|required',
            'state' => 'sometimes|required',
            'zipcode' => 'sometimes|required',
            'address' => 'sometimes|string',
        ];
    }
}
