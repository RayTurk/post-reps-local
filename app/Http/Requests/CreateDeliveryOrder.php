<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateDeliveryOrder extends FormRequest
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
            "delivery_order_comment" => "required|min:2",
            "office_id" => "required | numeric",
            "agent_id" => "nullable",
            "delivery_order_desired_date" => "nullable | string",
            "delivery_order_custom_desired_date" => "nullable | date ",
        ];
    }

    public function messages()
    {
        return [
            'delivery_order_comment.required' => 'Please enter comments.',
            'delivery_order_comment.min' => 'Comments must have at least 2 characters.',
            'delivery_office.required' => 'Please select office.',
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
            if ($request['delivery_order_desired_date'] == "custom_date") {
                if (empty(trim($request['delivery_order_custom_desired_date']))) {
                    $validator->errors()->add('delivery_order_desired_date', "Please select a date.");
                }
            }

            /*$signPanels = json_decode($request['signPanels']);
            if (
                ! count($signPanels->pickup->panel)
                && ! count($signPanels->dropoff->panel)
            ) {
                $validator->errors()->add('signPanels', "Please select sign panels.");
            }*/
        });
    }
}
