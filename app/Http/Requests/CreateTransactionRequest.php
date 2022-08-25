<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use \Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Request;
use Illuminate\Http\Exceptions\HttpResponseException;

class CreateTransactionRequest extends FormRequest
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
            'sender_id' => 'required|numeric|exists:users,id',
            'sender_phone' => 'required',
            'receiver_phone' => 'required',
            'amount' => 'required|numeric',
            'notes' => 'sometimes',
            'receiver_name' => 'sometimes|string',
            'receiver_image' => 'sometimes|string',
            'payment_type' => 'required|in:0,1' // payment_send, payment_req resp.
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
