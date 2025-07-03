<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendCommunicationsEmail extends FormRequest
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
            'office' => [
                'nullable',
                // 'integer',
            ],

            'agents' => [
                'sometimes',
                'required',
                'array',
            ],

            'agents.*' => [
                'sometimes',
                'required',
                // 'integer',
            ],

            'installers' => [
                'required_without_all:agents,office',
                'array',
            ],

            'installers.*' => [
                'sometimes',
                'required',
                'integer',
            ],

            'subject' => [
                'required',
                'string',
                'min:3',
                'max:255'
            ],

            'message' => [
                'required',
                'string',
                'min:3',
                'max:1000'
            ],
        ];
    }
}
