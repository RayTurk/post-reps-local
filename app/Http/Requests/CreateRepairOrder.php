<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateRepairOrder extends FormRequest
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
            "repair_order_comment" => "required|min:2",
        ];
    }

    public function messages()
    {
        return [
            'repair_order_comment.required' => 'Please enter comments.',
            'repair_order_comment.min' => 'Comments must have at least 2 characters.',
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
            if ($request['repair_order_desired_date'] == "custom_date") {
                if (empty(trim($request['repair_order_custom_desired_date']))) {
                    $validator->errors()->add('repair_order_desired_date', "Please select a date.");
                }
            }

            /*$accessories = json_decode($request['repair_order_select_accessories'], true);
            if (
                empty($request['relocate_post'])
                && empty($request['repair_replace_post'])
                && empty($accessories)
                && (empty($request['panel_id']) || $request['panel_id'] == 'undefined')
            ) {
                $validator->errors()->add('items_not_selected', "Please select at least one item.");
            }*/
        });
    }
}
