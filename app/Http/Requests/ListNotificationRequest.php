<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use \Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Request;
use Illuminate\Http\Exceptions\HttpResponseException;

class ListNotificationRequest extends FormRequest
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
            'page' => 'sometimes|integer',
            'per_page' => 'required|integer'
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
