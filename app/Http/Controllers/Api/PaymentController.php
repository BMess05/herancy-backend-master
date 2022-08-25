<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Exception;
use Tymon\JWTAuth\JWTAuth;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\User;
use App\Models\Card;
use App\Models\Payment;
use App\Models\DeviceToken;
use URL;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    protected $jwtAuth;
    public function __construct( JWTAuth $jwtAuth )
    {
        $this->jwtAuth = $jwtAuth;

    }


    /**
     * @OA\Post(
     * path="/api/payment",
     * summary="Send and request payment",
     * description="Send and request payment",
     * operationId="authsendrequest",
     * tags={"Payment"},
     * security={ {"bearer_token": {} }},

     * @OA\Response(
     *    response=422,
     *    description="Wrong credentials response",
     *    @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Sorry, wrong invalid credentials. Please try again")
     *        )
     *     )
     * )
     */
    public function sendRequestPayment() {

        $card = User::with('card')->where('id', auth()->user()->id)->first();
        if($card) {
            return response()->json(['success' => true, 'message' => 'User card details found', 'user' => $card]);
        }   else {
            return response()->json(['success' => false, 'message' => 'No card details found']);
        }
    }


    public function deleteCard(Request $request)
    {
        $data=$request->all();
        $validator = Validator::make($data, [
            'card_id' => 'required|numeric'
        ]);
        if ($validator->fails()) {
            $res = [
                'success' => false,
                'message' => __($validator->messages()->first())
            ];
            return response()->json($res);
        }
        $card =  Card::where(['user_id'=>\Auth::id(),'id'=>$data['card_id']])->first();
        if($card)
        {
            if($card->delete()) {
                $res = ['success' => true, 'message' => 'Card deleted successfully'];
            }else {
                $res = ['success' => false, 'message' =>'Something went wrong'];
            }
        }else{
            $res = ['success' => false, 'message' =>'No card found'];
        }

        return response()->json($res);

    }

    public function addCard(Request $request) {
        $validator = Validator::make($request->all(), [
            'card_holder_name' => 'required|string|between:2,100',
            'card_number' => 'required',
            'expiry_date' => 'required',
            'cvv' => 'required',
            'card_type' => 'required',
            'default_card' => 'required'
        ]);
        if ($validator->fails()) {
            $res = [
                'success' => false,
                'message' => $validator->messages()->first()
            ];
            return response()->json($res);
        }
        $data = $request->all();
        if(empty($data)) {
            return response()->json(['success' => false, 'message' => 'Please add some values to create profile']);
        }
        $card_exists = Card::withTrashed()->where('card_number', $data['card_number'])->first();
        if($card_exists) {
            return response()->json(['success' => false, 'message' => 'Card Number already exists']);
        }
        $card = new Card();
        $card->user_id = auth()->user()->id;
        $card->card_number = $data['card_number'];
        $card->expiry_date = $data['expiry_date'];
        $card->cvv = $data['cvv'];
        $card->card_holder_name = $data['card_holder_name'];
        $card->card_type = $data['card_type'];
        $card->default_card = $data['default_card'];
        $result = $card->save();
        if($result) {
            $card_info =  User::with('card')->where('id', auth()->user()->id)->first();
            $res = ['success' => true, 'message' => 'Card created successfully','card' => $card_info];
        }   else {
            $res =['success' => true, 'message' => 'Something went wrong, Please try again.'];
        }
        return response()->json($res);

    }


    function sendPushNotification($fcm_token, $title, $message, $id) {
        $your_project_id_as_key= env('FCM_TOKEN');
        //dd($your_project_id_as_key);
        Log::info($fcm_token);
        $url = "https://fcm.googleapis.com/fcm/send";
        $header = [
        'authorization: key=' . $your_project_id_as_key,
            'content-type: application/json'
        ];
        $finalPostArray = array('registration_ids' => $fcm_token,
                            'notification' => array('body' => $message,
                                                    'title' => $title,
                                                    'sound' => "default",
                                                    'badge' => 1,
                                                   ),
                            "data"=> array('id' => $id,
                                            'title' => $title,
                                            'body' => $message));
        $postdata = json_encode($finalPostArray);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }



}