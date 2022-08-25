<?php

namespace App\Http\Controllers\Api;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\JWTAuth;
use App\Models\DeviceToken;
class AuthController extends Controller
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
        $this->middleware('auth:api', ['except' => ['login','register', 'logout', 'refresh']]);
    }
    public function guard() {
        return Auth::guard();
    }

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    /**

     * @OA\Post(
     * path="/api/auth/login",
     * summary="Sign In",
     * description="Login by phone number, password",
     * operationId="authLogin",
     * tags={"Auth"},
     * @OA\RequestBody(
     *    required=true,
     *    description="Pass user credentials",
     *    @OA\JsonContent(
     *       required={"phone_number","password"},
     *       @OA\Property(property="phone_number", type="string", format="phone_number", example="9786543210"),
     *   @OA\Property(property="country_code", type="string",format="country_code",        example="91"),
     *       @OA\Property(property="password", type="string", format="password", example="PassWord12345"),
     *       @OA\Property(property="device_token", type="string", format="device_token", example="1232443bcvhsdgdfs"),
     *   @OA\Property(property="device_type", type="string",format="device_type",        example="1"),
     *    ),
     * ),
     * @OA\Response(
     *    response=422,
     *    description="Wrong credentials response",
     *    @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Sorry, wrong phone number or password. Please try again")
     *        )
     *     )
     * )
     */
    public function login(Request $request){
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|numeric',
            'country_code' =>  'required|numeric',
            'password' => 'required',
            'device_token' => 'required',
            'device_type' => 'required|in:1,2'
        ]);

        if ($validator->fails()) {
            $response = [
                'success' => false,
                'message' => $validator->messages()->first()
            ];
            return response()->json($response);
        }

        $data = $request->all();

        $user = User::where('phone_number', $data['phone_number'])->where('country_code', $data['country_code'])->first();
        if(!$user) {
            return json_encode(['success' => false,'message' => 'Phone number not registered.']);
        }
        // print_r($user->toArray()); die;
        $credentials = $request->only('phone_number', 'password', 'country_code');

        if ($token = $this->guard()->attempt($credentials)) {
            $token = $this->jwtAuth->fromUser($user);
            $token_exists = DeviceToken::where(['device_token' => $data['device_token'], 'device_type' => $data['device_type']])->first();

            $token_save = DeviceToken::updateOrCreate(['device_token' => $data['device_token'], 'device_type' => $data['device_type']], ['user_id' => $user->id]);

            if(!$token_save) {
                $response = ['success' =>false,'message' =>'Something went wrong, please try again.'];
                return response()->json($response);
            }

            $user = auth()->user();
            $response = ['success' => true, 'message' => 'Login successfully.', 'token' => $token, 'user' => $user->toArray()];
            return response()->json($response);
        }
        $response = ['success' => false, 'message' => 'Please enter correct password.'];
        return response()->json($response);

        // return $this->createNewToken($token);
    }
    /**
     * Register a User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    /**
     * @OA\Post(
     * path="/api/auth/register",
     * summary="Sign Up",
     * description="Sign up by phone number, password",
     * operationId="authSignUp",
     * tags={"Auth"},
     * @OA\RequestBody(
     *    required=true,
     *    description="Pass user details",
     *    @OA\JsonContent(
     *       required={"phone_number","password","country_code"},
     *       @OA\Property(property="phone_number", type="string", format="phone_number", example="9786543210"),
     *       @OA\Property(property="password", type="string", format="password", example="PassWord12345"),
     *       @OA\Property(property="device_token", type="string", format="device_token", example="1232443bcvhsdgdfs"),
     *       @OA\Property(property="country_code", type="string", format="country_code", example="91"),
     *       @OA\Property(property="device_type", type="string",format="device_type",        example="1"),
     *    ),
     * ),
     * @OA\Response(
     *    response=422,
     *    description="Wrong credentials response",
     *    @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Sorry, wrong phone number or password. Please try again")
     *        )
     *     )
     * )
     */
    public function register(Request $request) {

        $data = $request->all();

        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|numeric',
            'country_code' => 'required|numeric',
            // 'password' => 'required|string|confirmed|min:6',
            'password' => 'required|string|min:6',
            // 'otp'      => 'required',
            'device_token' => 'required',
            'device_type' => 'required|in:1,2'
        ]);

        if($validator->fails()){
            $response = [
                'success' => false,
                'message' => $validator->messages()->first()
            ];
            return response()->json($response);
        }


        $user_exists = User::withTrashed()->where('phone_number', $data['phone_number'])->where('country_code', $data['country_code'])->first();

        if($user_exists) {
            if($user_exists->deleted_at != NULL) {
                $user = User::withTrashed()->find($user_exists->id);
                $user->deleted_at = NULL;
                $result = $user->save();
                if($result) {
                    $token_save = DeviceToken::updateOrCreate(['device_token' => $data['device_token'], 'device_type' => $data['device_type']], ['user_id' => $user->id]);
                    if(!$token_save) {
                        $response = ['success' => false, 'message' => 'Something went wrong, please try again.'];
                        return response()->json($response);
                    }
                    $token = $this->jwtAuth->fromUser($user);
                    $user_info = $user->toArray();

                    $response = ['success' => true,'message' => 'Registered successfully.', 'token' => $token, 'user' => $user_info];
                }else {
                    $response = ['success' => false, 'message' => 'Something went wrong, please try again.'];
                }
                return response()->json($response);
            }else{
                $response = ['success' => false, 'message' => 'Phone number already registered.'];
                return response()->json($response);
            }
        }

        $user = new User();
        $user->phone_number = $data['phone_number'];
        $user->country_code = $data['country_code'];
        $user->password = bcrypt($data['password']);
        $result = $user->save();
        if($result) {
            $token = $this->jwtAuth->fromUser($user);
            $user = User::where('id', $user->id)->first();
            $user_info = $user->toArray();
            $response = ['success' => true, 'message' => 'Registerd successfully.', 'token' => $token, 'user' => $user_info];
            $token_save = DeviceToken::updateOrCreate(['device_token' => $data['device_token'], 'device_type' => $data['device_type']], ['user_id' => $user->id]);
            if(!$token_save) {
                $response=['success' =>false, 'message' => 'Something went wrong, please try again.'];
                return response()->json($response);
            }

        }else {
            $response =['success' => false, 'message' => 'Something went wrong, please try again.'];
        }
        return response()->json($response);

    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */

     /**
     * @OA\Post(
     * path="/api/logout",
     * summary="Logout",
     * description="Logout user and invalidate token",
     * operationId="authLogout",
     * tags={"Auth"},
     * security={ {"bearer_token": {} }},
     * @OA\RequestBody(
     *    required=true,
     *    description="Pass user details",
     *    @OA\JsonContent(
     *       required={"device_token","device_type"},
     *       @OA\Property(property="device_token", type="string", format="device_token", example="1232443bcvhsdgdfs"),
     *       @OA\Property(property="device_type", type="string",format="device_type",example="1"),
     *    ),
     * ),
     * @OA\Response(
     *    response=200,
     *    description="Success"
     *     ),
     * @OA\Response(
     *    response=401,
     *    description="Returns when user is not authenticated",
     *    @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Not authorized"),
     *    )
     * )
     * )
     */
    public function logout(Request $request) {

        $validator = Validator::make($request->all(), [
            'device_token' => 'required',
            'device_type' => 'required|in:1,2'
        ]);

        if ($validator->fails()) {
            $res = [
                'success' => false,
                'message' => $validator->messages()->first()
            ];
            return response()->json($res);
        }
        $user_id = auth()->user()->id;
        $data = $request->all();
        // auth()->logout();

        $token = $this->jwtAuth->parseToken();
        $this->jwtAuth->invalidate($token);
        // $this->guard()->logout();
        $data = $request->all();

        $resp = DeviceToken::where('device_token', $data['device_token'])->orWhere('user_id', $user_id)->delete();

        return response()->json(['success'=>true,'message' => 'Successfully logged out']);
    }
    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh() {

        return $this->respondWithToken(auth()->refresh());
        // $token =  $this->jwtAuth->refresh();
        // $response = [
        //     'success' => true,
        //     'message' => 'Token Refreshed',
        //     'access_token' => $token,
        //     'token_type' => 'bearer',
        //     'expires_in' => auth('api')->factory()->getTTL() * 60
        // ];
        // return response()->json($response);
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function userProfile() {
        return response()->json(auth()->user());
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function createNewToken($token){
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60,
            'user' => auth()->user()
        ]);
    }

}