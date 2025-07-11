<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Maatwebsite\Excel\Events\AfterSheet;

class CreateNotice extends FormRequest
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
                'required',
                'date',
                //'date_format:Y-m-d',
            ],
            'end_date' => [
                'required',
                'date',
                //'date_format:Y-m-d',
                'after:start_date',
            ],
            'subject' => 'required|string|min:2',
            'details' => 'required|string|min:2',
        ];
    }
}
