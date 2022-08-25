<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use \Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Request;
use Illuminate\Http\Exceptions\HttpResponseException;

class SupportFormRequest extends FormRequest
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
            'email' => 'required|email:rfc,dns',
            'subject' => 'required|string',
            'transaction_id' => 'sometimes',
            'issue_details' => 'required|min:20|max:1000',
            'attachment' => 'sometimes|string'
        ];
    }

    protected function failedValidation(Validator $validator) {
        $res = [
            'success' => false,
            'message' => __($validator->messages()->first())
        ];
        throw new HttpResponseException(response()->json($res, 422));
    }
}
