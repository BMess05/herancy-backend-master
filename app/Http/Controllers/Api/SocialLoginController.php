<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DeviceToken;
use Tymon\JWTAuth\JWTAuth;
use App\Models\User;

class SocialLoginController extends Controller
{
     /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    protected $jwtAuth;
    public function __construct( JWTAuth $jwtAuth )
    {
        $this->jwtAuth = $jwtAuth;
        $this->middleware('auth:api', ['except' => ['loginWithSocialMedia']]);
    }
    /**
     * @OA\Post(
     * path="/api/auth/social_media_login",
     * summary="Login with social media",
     * description="Login with social media :- Facebook, Instagram, Twitter, Google",
     * operationId="authSendOtp",
     * tags={"Auth"},
     * @OA\RequestBody(
     *    required=true,
     *    description="Pass user phone number, name, email, social media id, social media type",
     *    @OA\JsonContent(
     *       required={"name","email","social_id","social_media_type"},
     *       @OA\Property(property="name", type="string", format="name", example="Pradeep"),
     *       @OA\Property(property="email", type="string", format="email", example="pradeep@example.com"),
     *       @OA\Property(property="social_id", type="string", format="social_id", example="dfgd435345f35"),
     *       @OA\Property(property="social_media_type", type="string", format="social_media_type", example="1"),
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
    public function loginWithSocialMedia(Request $request)
    {
        $data = $request->all();

        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'social_id' => "required",
            'social_media_type' => "required|in:1,2,3,4,5", // 5 for apple
            'device_token' => 'required',
            'device_type' => 'required|in:1,2',
            'email' => 'sometimes'
        ]);

        if ($validator->fails()) {
            $response = [
                'success' => false,
                'message' => $validator->messages()->first(),
            ];
            return response()->json($response);
        }
        if(isset($data['email']) && ($data['email'] != "")){
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $response = [
                    'success' => false,
                    'message' => "Email address is invalid.",
                ];
                return response()->json($response);
            }
            $user_exists = User::withTrashed()
            ->where('email', $data['email'])
            ->first();
        }else{
            $user_exists = User::withTrashed()
            ->where('social_id', $data['social_id'])
            ->first();
        }

        if ($user_exists) {
            $user = User::withTrashed()->find($user_exists->id);
            if (($user_exists->deleted_at != null) || ($user_exists->social_type == null)) {
                $user->deleted_at = null;
                $user->name = $data['name'];
                $user->social_id = $data['social_id'];
                $user->social_type = $data['social_media_type'];
                $result = $user->save();
                if ($result) {
                    $token_save = DeviceToken::updateOrCreate(['device_token' => $data['device_token'], 'device_type' => $data['device_type']], ['user_id' => $user->id]);
                    if (!$token_save) {
                        $response = ['success' => false, 'message' => 'Something went wrong, please try again.'];
                        return response()->json($response);
                    }
                    $token = $this->jwtAuth->fromUser($user);
                    $user_info = $user->toArray();

                    $response = ['success' => true, 'message' => 'Registered successfully.', 'token' => $token, 'user' => $user_info];
                } else {
                    $response = ['success' => false, 'message' => 'Something went wrong, please try again.'];
                }
            }
            /* elseif($user_exists->social_type == null){
                $response = ['success' => false, 'message' => 'Email is already exist.'];
                return response()->json($response);
            } */
            else {
                $user->name = $data['name'];
                $user->social_id = $data['social_id'];
                $user->social_type = $data['social_media_type'];
                $user->email = $data['email'] ? $data['email'] : $user->email;
                $result = $user->save();
                if ($result) {
                    $token_save = DeviceToken::updateOrCreate(['device_token' => $data['device_token'], 'device_type' => $data['device_type']], ['user_id' => $user->id]);
                    if (!$token_save) {
                        $response = ['success' => false, 'message' => 'Something went wrong, please try again.'];
                        return response()->json($response);
                    }
                    $token = $this->jwtAuth->fromUser($user);
                    $user_info = $user->toArray();

                    $response = ['success' => true, 'message' => 'Registered successfully.', 'token' => $token, 'user' => $user_info];
                } else {
                    $response = ['success' => false, 'message' => 'Something went wrong, please try again.'];
                }
            }
            return response()->json($response);
        }   else {
            $user = new User();
            $user->name = $data['name'];
            $user->social_id = $data['social_id'];
            $user->social_type = $data['social_media_type'];
            $user->email = $data['email'] ? $data['email'] : null;
            $user->password = bcrypt($data['social_id']);
            $user->is_pasword_generated = 0;
            $result = $user->save();
            if ($result) {
                $token = $this->jwtAuth->fromUser($user);
                $user = User::where('id', $user->id)->first();
                $user_info = $user->toArray();
                $response = ['success' => true, 'message' => 'Registerd successfully', 'token' => $token, 'user' => $user_info];
                $token_save = DeviceToken::updateOrCreate(['device_token' => $data['device_token'], 'device_type' => $data['device_type']], ['user_id' => $user->id]);
                if (!$token_save) {
                    $response = ['success' => false, 'message' => 'Something went wrong, please try again.'];
                    return response()->json($response);
                }
            } else {
                $response = ['success' => false, 'message' => 'Something went wrong, please try again.'];
            }
        }

        return response()->json($response);
    }
}
