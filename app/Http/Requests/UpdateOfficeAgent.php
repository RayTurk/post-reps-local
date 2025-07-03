<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOfficeAgent extends FormRequest
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
        $agent = $this->route('agent');
        return [
            'first_name' => 'string|required',
            'last_name' => 'string|required',
            'email' => ['required', 'email', \Illuminate\Validation\Rule::unique('users')->ignore($agent->user->id)],
            'phone' => 'string|required',
            'city' => 'string|required',
            'state' => 'string|required',
            'zipcode' => 'string|required',
            're_license' => 'nullable|string',
            'address' => 'required|string',
        ];
    }

    public function messages()
    {
        return [
            'email.unique' => "This agent already has an account with PostReps. In order for the agent to move to a new office, the agent will have to login and change their office under their account settings."
        ];
    }
}
