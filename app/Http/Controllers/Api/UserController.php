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
use App\Models\DeviceToken;
use URL;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Services\UserService;

class UserController extends Controller
{
    protected $jwtAuth;
    public function __construct( JWTAuth $jwtAuth )
    {
        $this->jwtAuth = $jwtAuth;

    }


    /**
     * @OA\Post(
     * path="/api/change_password",
     * summary="Change Password",
     * description="Change password for user",
     * operationId="authChangePassword",
     * tags={"User"},
     * security={ {"bearer_token": {} }},
     * @OA\RequestBody(
     *    required=true,
     *    description="Pass user current password and new password",
     *    @OA\JsonContent(
     *       required={"current_password","new_password"},
     *       @OA\Property(property="current_password", type="string", format="current_password", example="password"),
     *       @OA\Property(property="new_password", type="string", format="new_password", example="password"),
    *    ),
     * ),
     * @OA\Response(
     *    response=422,
     *    description="Wrong credentials response",
     *    @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Sorry, wrong credentials. Please try again")
     *        )
     *     )
     * )
     */


    public function change_password(Request $request) {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'new_password' => 'required|different:current_password',
        ]);
        if ($validator->fails()) {
            $res = [
                'success' => false,
                'message' => __($validator->messages()->first())
            ];
            return response()->json($res);
        }
        $data = $request->all();
        $user = User::find(auth()->user()->id);
        $result = \Hash::check($data['current_password'], $user->password);
        if(!$result) {
            return response()->json(['success' => false, 'message' => 'Enter the correct password for old password field.']);
        }
        $user->password = bcrypt($data['new_password']);
        if($user->save()) {
            return response()->json(['success' => true, 'message' => 'Password updated successfully.']);
        }   else {
            return response()->json(['success' => false, 'message' => 'Something went wrong, Please try again.']);
        }
    }
    /**
     * @OA\Get(
     * path="/api/get_profile",
     * summary="Get Profile",
     * description="Get user profile",
     * operationId="authCreateProfile",
     * tags={"User"},
     * security={ {"bearer_token": {} }},

     * @OA\Response(
     *    response=422,
     *    description="Wrong credentials response",
     *    @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Sorry, wrong invalid email or name. Please try again")
     *        )
     *     )
     * )
     */
    public function getProfile() {

        $user_id = auth()->user()->id;
        $user = User::find($user_id);
        if($user) {
            return response()->json(['success' => true, 'message' => 'User details found', 'user' => $user]);
        }   else {
            return response()->json(['success' => false, 'message' => 'No details found']);
        }
    }
  /**
     * @OA\Post(
     * path="/api/update_profile",
     * summary="Update Profile",
     * description="Update profile for user",
     * operationId="authUpdateProfile",
     * tags={"User"},
     * security={ {"bearer_token": {} }},
     * @OA\RequestBody(
     *    required=true,
     *    description="Pass user details",
     *    @OA\JsonContent(
     *       @OA\Property(property="name", type="string", format="name", example="john"),
     *       @OA\Property(property="email", type="string", format="email", example="abc@gmail.com"),
     *       @OA\Property(property="phone_number", type="string", format="phone_number", example="1234567890"),
     *       @OA\Property(property="country_code", type="string", format="country_code", example="91"),

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
    public function update_profile(Request $request) {
        $validator = Validator::make($request->all(), [
            'push_notification' => 'sometimes|in:0,1',
            'name' => 'required|string',
            "email" => "required|email:rfc,dns|unique:App\Models\User,email,".auth()->user()->id
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
            return response()->json(['success' => false, 'message' => 'Please add some values to update']);
        }
        $user = User::find(auth()->user()->id);
        $user->name = $data['name'];
        $user->email = $data['email'];
        // $user->country_code = $data['country_code'];
        // $user->phone_number = $data['phone_number'];
        if($user->save()) {
            $res = ['success' => true, 'message' => 'Profile updated successfully.','user' => $user];
        }   else {
            $res = ['success' => true, 'message' => 'Something went wrong, Please try again.'];
        }
        return response()->json($res);

    }

   /**
    * @OA\Post(
    * path="/api/upload_image",
    * summary="Upload Image",
    * description="Upload Image",
    * operationId="authUploadImage",
    * tags={"User"},
    * security={ {"bearer_token": {} }},
    * @OA\RequestBody(
    *         required=true,
    *         @OA\MediaType(
    *             mediaType="multipart/form-data",
    *             @OA\Schema(
    *                 @OA\Property(
    *                     description="Image to upload",
    *                     property="image",
    *                     type="file",
    *                     format="file",
    *                 ),
    *                 required={"image"}
    *             )
    *         )
    * ),

    * @OA\Response(
    *    response=422,
    *    description="Wrong format response",
    *    @OA\JsonContent(
    *       @OA\Property(property="message", type="string", example="Sorry, wrong Format. Please try again")
    *        )
    *     )
    * )
    */
    public function upload_image(Request $request) {

        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,png,jpg,svg|max:5120' // 3mb
        ]);
        if ($validator->fails()) {
            $response = [
                'success' => false,
                'message' => $validator->messages()->first()
            ];
            return response()->json($response);
        }

        $file = $request->file('image');
        $name = auth()->user()->id .'-'.time() .  '.' . $file->getClientOriginalExtension();
        $path = public_path('/uploads/users');
        if(!\File::exists($path)) {
            \File::makeDirectory($path, 0777, true, true);
        }
        $file_r = $file->move($path, $name);
        $path = url('/uploads/users/'.$name);


        $user = User::find(auth()->user()->id);
        $user->image = $name;
        if($user->save()) {
            $response = ['success' => true, 'message' => 'Image uploaded successfully', 'file_name' => $path];
        }   else {
            $response = ['success' => true, 'message' => 'Something went wrong, Please try again.'];
        }
        return response()->json($response);
    }
    /**
     * @OA\Post(
     * path="/api/create_profile",
     * summary="Create Profile",
     * description="Create profile for user",
     * operationId="authCreateProfile",
     * tags={"User"},
     * security={ {"bearer_token": {} }},
     * @OA\RequestBody(
     *    required=true,
     *    description="Pass user name and email",
     *    @OA\JsonContent(
     *       required={"name","email"},
     *       @OA\Property(property="name", type="string", format="name", example="john"),
     *       @OA\Property(property="email", type="string", format="email", example="abc@gmail.com"),
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
    public function create_profile(Request $request) {
        $data = $request->all();
        $validator = Validator::make($data, [
            'name' => 'required|string|between:2,100',
            'email' => 'required|unique:users,email,'.auth()->user()->id,
        ]);
        if ($validator->fails()) {
            $res = [
                'success' => false,
                'message' => $validator->messages()->first()
            ];
            return response()->json($res);
        }
        if(empty($data)) {
            return response()->json(['success' => false, 'message' => 'Please add some values to create profile.']);
        }
        $user = User::find(auth()->user()->id);
        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->profile_status = 1;
        if($user->save()) {
            $res = ['success' => true, 'message' => 'Profile created successfully.','user' => $user];
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

    public function add_phone_number(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'country_code' =>  'required|numeric',
            'phone_number' => 'required|numeric'
        ]);
        if ($validator->fails()) {
            $res = [
                'success' => false,
                'message' => $validator->messages()->first()
            ];
            return response()->json($res);
        }
        $data = $request->all();
        $user_exists = User::withTrashed()->where('phone_number', $data['phone_number'])->where('country_code', $data['country_code'])->first();
        if($user_exists) {
            $response = ['success' => false, 'message' => 'Phone number already registered.'];
            return response()->json($response);
        }
        $user = User::find(auth()->user()->id);
        $user->phone_number = $data['phone_number'];
        $user->country_code = $data['country_code'];
        if($user->save()) {
            $res = ['success' => true, 'message' => 'Phone number added successfully','user' => $user];
        }   else {
            $res =['success' => true, 'message' => 'Something went wrong, Please try again.'];
        }
        return response()->json($res);

    }
    public function create_password(Request $request) {
        $validator = Validator::make($request->all(), [
            'password' => 'required'
        ]);
        if ($validator->fails()) {
            $res = [
                'success' => false,
                'message' => __($validator->messages()->first())
            ];
            return response()->json($res);
        }
        $data = $request->all();
        $user = User::find(auth()->user()->id);
        $user->password = bcrypt($data['password']);
        $user->is_pasword_generated = 1;
        if($user->save()) {
            return response()->json(['success' => true, 'message' => 'Password created successfully.', 'user' =>  $user]);
        }   else {
            return response()->json(['success' => false, 'message' => 'Something went wrong, Please try again.']);
        }
    }

    public function search_users_by_phone(Request $request, UserService $service) {
        $validator = Validator::make($request->all(), [
            'phone' => 'required'
        ]);
        if ($validator->fails()) {
            $res = [
                'success' => false,
                'message' => __($validator->messages()->first())
            ];
            return response()->json($res);
        }
        $data = $request->all();
        $users = $service->getUsersByPhone($data['phone']);
        if($users) {
            return response()->json([
                'success' => true,
                'message' => 'Users found.',
                'data' => $users->toArray()
            ]);
        }   else {
            return response()->json(['success' => false, 'message' => 'User not found with given phone number']);
        }
    }

    public function multiSearchByPhone(Request $request, UserService $service) {
        $validator = Validator::make($request->all(), [
            'contact_list' => 'required|array|min:1',
            'contact_list.*.name' => 'required',
            'contact_list.*.phone' => 'required'
        ]);
        if ($validator->fails()) {
            $res = [
                'success' => false,
                'message' => __($validator->messages()->first())
            ];
            return response()->json($res);
        }
        $data = $request->all();
        $users = $service->getUsersByPhoneArray($data['contact_list']);
        return response()->json(['success' => true, 'message' => 'Users found.', 'data' => $users]);
    }

    public function get_registered_users(Request $request, UserService $service) {
        $recent_requests = $service->getUserRecentRequests();
        $users = $service->getAllRegisteredUsers();
        $data = [
            'recent_requests' => $recent_requests,
            'users' => $users
        ];
        return response()->json(['success' => true, 'message' => 'Users found.', 'data' => $data]);
    }

    public function notification_setting(Request $request, UserService $service) {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:0,1', // 0 for email_notification, 1 for push_notification
            'value' => 'required|in:0,1'
        ]);
        if ($validator->fails()) {
            $res = [
                'success' => false,
                'message' => __($validator->messages()->first())
            ];
            return response()->json($res);
        }
        $data = $request->all();
        $result = $service->updateNotificationSettings($data);
        if($result) {
            return response()->json(['success' => true, 'message' => 'Notification settings updated.']);
        }   else {
            return response()->json(['success' => false, 'message' => 'Something went wrong, please try again.']);
        }
    }

}