<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\SupportFormRequest;
use App\Services\EmailService;
use App\Mail\SupportMail;

class SupportController extends Controller
{
    public function support_form_submit(SupportFormRequest $request, EmailService $email)
    {
        $support_email = env('SUPPORT_EMAIL', 'testingsoftradix@gmail.com');
        // echo $support_email; die;
        $data = $request->validated();
        $email->setTo($support_email)
            ->setHtml(new SupportMail($data))
            ->send();
        $res = [
            'success' => true,
            'message' => __('Email send to support.')
        ];
        return response()->json($res);
    }
}
