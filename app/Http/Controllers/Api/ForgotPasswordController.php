<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Tymon\JWTAuth\JWTAuth;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Mail;
use Twilio\Rest\Client;
use Exception;

class ForgotPasswordController extends Controller
{
    protected $jwtAuth;
    function __construct( JWTAuth $jwtAuth ) {
        $this->jwtAuth = $jwtAuth;
        $this->middleware('auth:api', ['except' => ['forgot_password', 'reset_password','verify_otp','send_otp']]);
        //
    }

    function forgot_password(Request $request) {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email'
        ]);

        if ($validator->fails()) {
            $response = [
                'success' => false,
                'message' => $validator->messages()->first()
            ];
            return response()->json($response);
        }

        $data = $request->all();
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return response()->json(['success' => false, 'message' => 'Email Invalid.']);
        }

        $user = User::where('email', $data['email'])->first();
        if(!$user) {
            return response()->json(['success' => false, 'message' => 'Email Not Registered.']);
        }

        $otp = $this->generateOtp();
        $details = [
            'title' => 'OTP for password reset',
            'otp' => $otp,
            'email' => $data['email'],
            'name' => $user->name,
        ];

        $res = User::where('email', $data['email'])->update(['otp' => $otp]);
        \Mail::to($data['email'])->send(new \App\Mail\ForgotPasswordEmail($details));

        return response()->json(['success' => true,'message' => 'Email Sent For Otp.', 'otp' => $otp]);

    }
    /**
     * @OA\Post(
     * path="/api/auth/send_otp",
     * summary="Send Otp",
     * description="Send otp to verify user",
     * operationId="authSendOtp",
     * tags={"Auth"},
     * @OA\RequestBody(
     *    required=true,
     *    description="Pass user phone number and type if its 0 or 1,0 for verify user and 1 for forgot password",
     *    @OA\JsonContent(
     *       required={"phone_number","type"},
     *       @OA\Property(property="phone_number", type="string", format="phone_number", example="9786543210"),
     *       @OA\Property(property="type", type="string", format="type", example="0"),
     *    ),
     * ),
     * @OA\Response(
     *    response=422,
     *    description="Wrong Phone number response",
     *    @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Sorry, wrong phone number. Please try again")
     *        )
     *     )
     * )
     */
    function send_otp(Request $request) {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|numeric',
            'country_code' =>  'required|numeric',
            'type' => 'required|numeric'
        ]);

        if ($validator->fails()) {
            $response = [
                'success' => false,
                'message' => $validator->messages()->first()
            ];
            return response()->json($response);
        }
        $data = $request->all();

        if($data['type'] == '1'){
            $user = User::where('phone_number', $data['phone_number'])->where('country_code', intval($data['country_code']))->first();
            if(!$user) {
                return response()->json(['success' => false, 'message' => 'Phone number not registered.']);
            }
        }

        if($data['type'] == '0'){
            $user = User::where('phone_number', $data['phone_number'])->where('country_code', intval($data['country_code']))->first();

            if($user) {
                return response()->json(['success' => false, 'message' => 'Phone number already registered.']);
            }
        }
        $otp = $this->generateOtp();
        $res = User::where('phone_number', $data['phone_number'])->where('country_code', intval($data['country_code']))->update(['otp' => $otp]);
        // if($data['type'] == '0'){
        //     $message = "OTP for your Herancy Account verification is " . $otp;
        // } else {
        //     $message = "OTP for your Herancy Account Password reset is " . $otp;
        // }
        // $recipients = $data['country_code'] . $data['phone_number'];

        // $res = $this->sendMessage($message, $recipients);
        if($data['type'] == '0'){
            return response()->json(['success' => true,'message' => 'OTP is sent to the registered number.', 'otp' => $otp]);
        }   else {
            if($res) {
                return response()->json(['success' => true,'message' => 'OTP is sent to the registered number.', 'otp' => $otp]);
            }   else {
                return response()->json(['success' => false,'message' => 'Something went wrong, please try again.']);
            }
        }

    }
    function generateOtp() {
        $otp = rand ( 100000 , 999999 );
        $res = $this->checkOTPUnique($otp);
        if($res) {
            return $otp;
        }   else {
            $this->generateOtp();
        }
    }

    function checkOTPUnique($otp) {
        $res = User::where('otp', $otp)->first();
        if($res) {
            return false;
        }   else {
            return true;
        }
    }


    /**
     * Sends sms to user using Twilio's programmable sms client
     * @param String $message Body of sms
     * @param Number $recipients string or array of phone number of recepient
     */
    private function sendMessage($message, $recipients) {
        $account_sid = config("constants.TWILIO_SID");
        $auth_token = config("constants.TWILIO_AUTH_TOKEN");
        $twilio_number = config("constants.TWILIO_NUMBER");

        $client = new Client($account_sid, $auth_token);
        try {
            $client->messages->create($recipients,
                ['from' => $twilio_number, 'body' => $message] );
            $data = [
                'status' => true,
                'message' => 'sent successfully'
            ];
        }   catch(Exception $e) {
            $message = $e->getMessage();
            $data = [
                'status' => false,
                'message' => $message
            ];
        }
        return $data;
    }

    function verify_otp(Request $request){
        $validator = Validator::make($request->all(), [
            'otp' => 'required'
        ]);

        if ($validator->fails()) {
            $response = [
                'success' => false,
                'message' => $validator->messages()->first()
            ];
            return response()->json($response);
        }

        $data = $request->all();
        $response = User::where('otp', $data['otp'])->first();

        if($response) {
            return response()->json(['success' => true, 'message' => 'valid_OTP']);

        }else {
            return response()->json(['success' => false, 'message' => 'invalid_OTP']);
        }
    }
    /**
     * @OA\Post(
     * path="/api/auth/reset_password",
     * summary="Reset Password",
     * description="Reset user password",
     * operationId="authResetpassword",
     * tags={"Auth"},
     * @OA\RequestBody(
     *    required=true,
     *    description="Pass user password and otp",
     *    @OA\JsonContent(
     *       required={"password","otp"},
     *       @OA\Property(property="password", type="string", format="password", example="password"),
     *       @OA\Property(property="otp", type="string", format="otp", example="046677"),
     *    ),
     * ),
     * @OA\Response(
     *    response=422,
     *    description="Wrong response",
     *    @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Sorry, wrong otp number. Please try again")
     *        )
     *     )
     * )
     */
    function reset_password(Request $request) {
        $validator = Validator::make($request->all(), [
            'password' => 'required',
            // 'password' => 'required|string|confirmed|min:6',
            'otp' => 'required'
        ]);

        if ($validator->fails()) {
            $response = [
                'success' => false,
                'message' => $validator->messages()->first()
            ];
            return response()->json($response);
        }

        $data = $request->all();
        $response = User::where('otp', $data['otp'])->first();

        if($response) {
            $password = bcrypt($data['password']);
            $response2 = User::where('otp', $data['otp'])->update(['password' => $password, 'otp' => null]);
            if($response2) {
                return response()->json(['success' => true,
                'message' =>'Password Updated Successfully.']);
            }else {
                return response()->json(['success' => false, 'message' =>'Something Went Wrong.']);
            }
        }else {
            return response()->json(['success' => false, 'message' => 'Invalid OTP.']);
        }
    }
}
