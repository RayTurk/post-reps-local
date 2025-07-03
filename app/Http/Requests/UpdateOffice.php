<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOffice extends FormRequest
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
            'name' => 'string|required',
            'email' => 'required|email|unique:users,email,' . $this->user_id,
            'phone' => 'string|required',
            'primary_contact' => 'string|nullable',
            'zipcode' => 'string|required',
            'website' => 'string|nullable',
            'inactive' => 'required|numeric',
            'private' => 'required|numeric',
            'region_id' => 'required|numeric',
            'edit_logo_image' => 'nullable|image',
            'address' => 'required|string',
            'city' => 'required|string',
            'state' => 'required|string',
        ];
    }
}
