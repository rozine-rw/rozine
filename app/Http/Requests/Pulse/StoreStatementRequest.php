<?php

namespace App\Http\Requests\Pulse;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreStatementRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'statement' => [
                'required',
                'file',
                'mimes:pdf,csv,txt,xls,xlsx,jpg,jpeg,png',
                'mimetypes:application/pdf,text/csv,text/plain,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,image/jpeg,image/png',
                'max:10240',
            ],
        ];
    }

    /**
     * Get custom messages for the validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'statement.mimes' => 'Upload a MoMo or bank statement as a PDF, spreadsheet or image.',
            'statement.max' => 'Statements must be smaller than 10MB.',
        ];
    }
}
