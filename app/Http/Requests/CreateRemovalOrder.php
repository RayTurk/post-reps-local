<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateRemovalOrder extends FormRequest
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
            "removal_order_comment" => "required|min:2",
        ];
    }

    public function messages()
    {
        return [
            'removal_order_comment.required' => 'Please enter comments.',
            'removal_order_comment.min' => 'Comments must have at least 2 characters.',
        ];
    }
    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $request = $_POST;
            if ($request['removal_order_desired_date'] == "custom_date") {
                if (empty(trim($request['removal_order_custom_desired_date']))) {
                    $validator->errors()->add('removal_order_desired_date', "Please select a date.");
                }
            }
        });
    }
}
