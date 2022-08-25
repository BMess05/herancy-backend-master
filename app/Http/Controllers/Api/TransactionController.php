<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Services\TransactionService;
use App\Http\Requests\CreateTransactionRequest;
use App\Services\NotificationService;
use App\Services\EmailService;
use App\Mail\TransactionMail;
use App\Models\DeviceToken;
use DB;
use App\Http\Requests\ListNotificationRequest;
use Illuminate\Support\Facades\Mail;

class TransactionController extends Controller
{
    /**
     * @OA\Post(
     * path="/api/transaction/create",
     * summary="Create transaction",
     * description="Create transaction for send money or request money and create notification for receiver_id",
     * operationId="authCreateTransaction",
     * tags={"Transaction"},
     * security={ {"bearer_token": {} }},
     * @OA\RequestBody(
     *    required=true,
     *    description="create payment transaction",
     *    @OA\JsonContent(
     *       @OA\Property(property="sender_id", type="string", format="sender_id", example="2"),
     *       @OA\Property(property="sender_phone", type="string", format="phone_number", example="9898989898"),
     *       @OA\Property(property="receiver_phone", type="string", format="phone_number", example="1234567890"),
     *       @OA\Property(property="amount", type="numeric", format="amount", example="1000"),
     *       @OA\Property(property="notes", type="string", format="notes", example="dispatch payment"),
     *       @OA\Property(property="receiver_name", type="string", format="receiver_name", example="John"),
     *       @OA\Property(property="payment_type", type="integer", format="payment_type", example="1 => payment request 0 => send payment"),
     *       @OA\Property(property="receiver_id", type="integer", format="receiver_id", example="3")

     *    ),
     * ),
     * @OA\Response(
     *    response=422,
     *    description="Wrong credentials response",
     *    @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Sorry, wrong invalid email or name. Please try again")
     *        )
     *     )
     * )
     */

    public function make_transaction(CreateTransactionRequest $request, TransactionService $service, NotificationService $notification, EmailService $email) {
        DB::beginTransaction();
        try {
            $result = $service->createTransaction($request);
            if($result) {
                $data = $request->all();
                $sender_user = $service->getUserById($request->input('sender_id'));
                $data['sender_user'] = $sender_user->toArray();

                if($request->input('receiver_id') && $request->input('receiver_id') != null) {
                    $receiver_user = $service->getUserById($request->input('receiver_id'));
                    $result = $notification->createNotification($request, 1);
                    if($receiver_user) {
                        $data['receiver_user'] = $receiver_user->toArray();
                        $device_token_obj = DeviceToken::where('user_id', $request->input('receiver_id'))->orderBy('id', 'DESC')->get();

                        // dd($device_token_obj);

                        if(($receiver_user->email != null) && ($receiver_user->email_notification == 1)) {
                            if($data['payment_type'] == 1) {
                                $data['mail_type'] = "request_toreceiver";
                                // $result = $email->setTo($receiver_user->email)
                                //                 ->setHtml(new TransactionMail($data))
                                //                 ->send();
                                // Mail::to($this->sendTo)->send($this->mailClass);
                            }   else {
                                $data['mail_type'] = "payment_toreceiver";
                                $result = $email->setTo($receiver_user->email)
                                                ->setHtml(new TransactionMail($data))
                                                ->send();
                            }
                        }
                        if($device_token_obj->count() > 0 && ($receiver_user->push_notification == 1)) {
                            if($data['payment_type'] == 1) {
                                $msg = $sender_user->name . " sent you payment request of amount " . $request->input('amount') . ".";
                                foreach($device_token_obj as $device_token) {
                                        $this->sendNotification_new($device_token->device_token, [
                                            "title" => "Payment request received.",
                                            "body" => $msg
                                        ]);
                                }


                            }  else {
                                $msg = $sender_user->name . " sent you payment of amount " . $request->input('amount') . ".";
                                foreach($device_token_obj as $device_token) {
                                    $this->sendNotification_new($device_token->device_token, [
                                        "title" => "Payment received.",
                                        "body" => $msg
                                    ]);
                                }

                            }
                        }
                    }
                }
                if($sender_user && $data['payment_type'] == 1 && ($sender_user->email != null)) {
                    $data['mail_type'] = "request_tosender";
                    $result = $email->setTo($sender_user->email)
                                    ->setHtml(new TransactionMail($data))
                                    ->send();

                }

                if($data['payment_type'] == 1) {
                    $message = "Request sent successfully.";
                }   else {
                    $message = "Payment sent successfully.";
                }
                if($result) {
                    DB::commit();
                    $res = [
                        'success' => true,
                        'message' => __($message)
                    ];
                    return response()->json($res);
                }
            }
        } catch(Exception $e) {
            // $message = $e->getMessage();
        }
        DB::rollback();
        $res = [
            'success' => false,
            'message' => __("Transaction failed.")
        ];
        return response()->json($res);
    }


    public function list_transactions(TransactionService $service) {
        $user_id = auth()->user()->id;
        $transactions = $service->getUserTransactions($user_id);
        $msg = "No transactions found";
        if(count($transactions) > 0) {
            $msg = "List of transactions found";
        }
        $res = [
            'success' => true,
            'message' => __($msg),
            'data' => $transactions
        ];
        return response()->json($res);
    }

    public function list_notifications(ListNotificationRequest $request, NotificationService $service) {
        $data = $request->validated();
        $data['user_id'] = auth()->user()->id;
        $result = $service->getUserNotifications($data);
        $res = [
            'success' => true,
            'message' => __('List found'),
            'data' => $result
        ];
        return response()->json($res);
    }

    public function read_notifications(NotificationService $service) {
        $user_id = auth()->user()->id;
        $result = $service->readNotifications($user_id);
        $res = [
            'success' => true,
            'message' => __('All notifications read')
        ];
        return response()->json($res);
    }

    public function sendNotification($device_token, $message)
    {
        $SERVER_API_KEY = env('FIREBASE_SERVER_KEY', 'AAAASydMWVg:APA91bHt9vY8pVsVY0MAH-9KEoIJX8zXQTSsBQ5pAKI_JAFJVZfokJbPH_UnHiXYjalbLmGzTKLeckb15-0HAcLhCe3J6OXz4NOgCcWo4L1lsfZQ-cm2j7Fri4rk1KwYIL5_ATTq0j7w');

        // payload data, it will vary according to requirement
        $data = [
            "to" =>  $device_token,
            "data" => $message
        ];
        $dataString = json_encode($data);

        $headers = [
            'Authorization: key='.$SERVER_API_KEY,
            'Content-Type: application/json',
        ];

        $fcm_url = "https://fcm.googleapis.com/fcm/send";
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $fcm_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);

        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }


    public function sendNotification_new($device_token, $message)
    {
        $serverKey = env('FIREBASE_SERVER_KEY', 'AAAASydMWVg:APA91bHWyQdoAZe-tK1LuuEn4MYBi8YgC9g-FXW0HC1lO5WPvi8rp6tWtUALnE0Ow6k0KCexKAPqpKRysDzk5bn4s2KSABYo_3I4PXmlpjcfc6cZ9y_5U5LJg9oeYJnhjgRHEaa8kdKI');

        // dd($serverKey);
        $url = "https://fcm.googleapis.com/fcm/send";
        $token = $device_token;
        $notification = array(
            'title' => "Harency",
            'text' => $message['body'],
            'body' => $message['body'],
            'sound' => 'default',
            'badge' => 1,
            'content-available' => 1
        );
        $arrayToSend = array(
            'to' => $token,
            'notification' => $notification,
            'priority' => 'high',
            'data' => [
                'message' => $message['body']
            ]
        );
        $json = json_encode($arrayToSend);
        $headers = array();
        $headers[] = 'Content-Type: application/json';
        $headers[] = 'Authorization: key=' . $serverKey;
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
        $response = curl_exec($ch);

        curl_close($ch);
        // dd($response);
        return $response;
    }
}
