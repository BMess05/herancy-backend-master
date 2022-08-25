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
use App\Models\DeviceToken;
use URL;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CardController extends Controller
{
    protected $jwtAuth;
    public function __construct( JWTAuth $jwtAuth )
    {
        $this->jwtAuth = $jwtAuth;

    }


    /**
     * @OA\Get(
     * path="/api/get_card",
     * summary="Get card details",
     * description="Get card details",
     * operationId="authGetCard",
     * tags={"Card"},
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
    public function getCard() {
        $card = User::with(['card' => function($query) {
            $query->orderBy('default_card', 'ASC');
        }])->where('id', auth()->user()->id)->first();
        if($card) {
            return response()->json(['success' => true, 'message' => 'User card details found', 'user' => $card]);
        }   else {
            return response()->json(['success' => false, 'message' => 'No card details found']);
        }
    }
   /**
     * @OA\Post(
     * path="/api/delete_card",
     * summary="Delete Card",
     * description="Delete user card",
     * operationId="authDeleteCard",
     * tags={"Card"},
     * security={ {"bearer_token": {} }},
     * @OA\RequestBody(
     *    required=true,
     *    description="Pass user card details Id",
     *    @OA\JsonContent(
     *       required={"card_id"},
     *       @OA\Property(property="card_id", type="string", format="card_id", example="1"),
     *    ),
     * ),
     * @OA\Response(
     *    response=422,
     *    description="Wrong credentials response",
     *    @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Sorry, wrong card details. Please try again")
     *        )
     *     )
     * )
     */

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
    /**
     * @OA\Post(
     * path="/api/add_card",
     * summary="Add Card",
     * description="Create user card",
     * operationId="authCreateCard",
     * tags={"Card"},
     * security={ {"bearer_token": {} }},
     * @OA\RequestBody(
     *    required=true,
     *    description="Pass user card details",
     *    @OA\JsonContent(
     *       required={"card_holder_name","card_number","expiry_date","cvv","card_type","default_card"},
     *       @OA\Property(property="card_holder_name", type="string", format="card_holder_name", example="john walker"),
     *       @OA\Property(property="card_number", type="string", format="card_number", example="4242424242424242"),
     *       @OA\Property(property="expiry_date", type="string", format="expiry_date", example="08/26"),
     *       @OA\Property(property="cvv", type="string", format="cvv", example="111"),
     *       @OA\Property(property="card_type", type="string", format="card_type", example="visa"),
     *       @OA\Property(property="default_card", type="string", format="default_card", example="primary"),
     *    ),
     * ),
     * @OA\Response(
     *    response=422,
     *    description="Wrong credentials response",
     *    @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Sorry, wrong card details. Please try again")
     *        )
     *     )
     * )
     */
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
            return response()->json(['success' => false, 'message' => 'Please add some values to add card.']);
        }
        $card_exists = Card::where(['card_number' => $data['card_number'], 'user_id' => auth()->user()->id])->first();
        if($card_exists) {
            return response()->json(['success' => false, 'message' => 'Card number already exists.']);
        }
        if($data['default_card'] == "primary"){
            Card::where('user_id', auth()->user()->id)->update(['default_card'=>'secondary']);
        }
        $card = Card::withTrashed()->where(['card_number' => $data['card_number'], 'user_id' => auth()->user()->id])->first();
        if($card) {
            $card->deleted_at = NULL;
            $card->created_at = date('Y-m-d H:i:s');
        }   else {
            $card = new Card();
            $card->user_id = auth()->user()->id;
            $card->card_number = $data['card_number'];
        }
        $card->expiry_date = $data['expiry_date'];
        $card->cvv = $data['cvv'];
        $card->card_holder_name = $data['card_holder_name'];
        $card->card_type = $data['card_type'];
        $card->default_card = $data['default_card'];
        $result = $card->save();
        if($result) {
            $card_info =  User::with('card')->where('id', auth()->user()->id)->first();
            $res = ['success' => true, 'message' => 'Card added successfully.','card' => $card_info];
        }   else {
            $res =['success' => true, 'message' => 'Something went wrong, Please try again.'];
        }
        return response()->json($res);

    }


    public function makeCardPrimary1(Request $request) {
        $validator = Validator::make($request->all(), [
            'card_id' => 'required',
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
        $card_exists = Card::find($data['card_id']);
        // echo '<pre>';
        // print_r($card_exists);die;
        if($card_exists) {
            if($data['default_card'] == "primary"){
                Card::where('user_id', auth()->user()->id)->update(['default_card'=>'secondary']);
            }
            $card_exists->default_card = $data['default_card'];
            $result = 1;
            $card_exists->save();
            if($result){

                $res = ['success' => true, 'message' => 'Maked primary successfully.'];
            }else{
                $res = ['success' => false, 'message' => 'Something went wrong, Please try again.'];
            }
        }else{
            $res = ['success' => false, 'message' => 'Card not exit.'];
        }

        return response()->json($res);

    }

    public function makeCardPrimary(Request $request) {
        $validator = Validator::make($request->all(), [
            'card_id' => 'required'
        ]);
        if ($validator->fails()) {
            $res = [
                'success' => false,
                'message' => $validator->messages()->first()
            ];
            return response()->json($res);
        }
        $data = $request->all();
        // if(empty($data)) {
        //     return response()->json(['success' => false, 'message' => 'Please add some values to create profile']);
        // }

        $card_exists = Card::find($data['card_id']);
        if($card_exists) {
            if($card_exists['default_card'] == "primary"){
                return response()->json(['success' => true, 'message' => 'Card is already Primary']);
            }else{
                Card::where('user_id', auth()->user()->id)->update(['default_card'=>'secondary']);
                $result = Card::where('id', $data['card_id'])->update(['default_card'=>'primary']);
                if($result){
                    $res = ['success' => true, 'message' => 'Maked primary successfully.'];
                }else{
                    $res = ['success' => false, 'message' => 'Something went wrong, Please try again.'];
                }
            }
        }else{
            $res = ['success' => false, 'message' => 'Card not exit.'];
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
        $finalPostArray = [
            'registration_ids' => $fcm_token,
            'notification' => [
                'body' => $message,
                'title' => $title,
                'sound' => "default",
                'badge' => 1,
            ],
            "data"=> [
                'id' => $id,
                'title' => $title,
                'body' => $message
            ]
        ];
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