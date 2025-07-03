<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNotice extends FormRequest
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
            'start_date' => [
                'sometimes',
                'required',
                'date',
                //'date_format:Y-m-d',
            ],
            'end_date' => [
                'sometimes',
                'required',
                'date',
                //'date_format:Y-m-d',
                'after:start_date',
            ],
            'subject' => 'sometimes|required|string|min:2',
            'details' => 'sometimes|required|string|min:2',
        ];
    }
}
