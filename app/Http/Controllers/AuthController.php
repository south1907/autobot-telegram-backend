<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{

    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('jwt-auth', ['except' => ['login']]);
        //$this->userService = $userService;
    }

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login()
    {
        $secret = env('BOT_TOKEN', 'default_value');
        $auth_data = request()->all();

        $check_hash = $auth_data['hash'];
        unset($auth_data['hash']);
        $data_check_arr = [];
        foreach ($auth_data as $key => $value) {
            $data_check_arr[] = $key . '=' . $value;
        }

        sort($data_check_arr);
        $data_check_string = implode("\n", $data_check_arr);

        $secret_key = hash('sha256', $secret, true);
        $hash = hash_hmac('sha256', $data_check_string, $secret_key);
        if (strcmp($hash, $check_hash) !== 0) {
            return $this->responseForbidden();
        }
        if ((time() - $auth_data['auth_date']) > 86400) {
            return $this->responseForbidden();
        }

        $user = User::where('id_telegram', $auth_data['id'])->first();
        if (!$user) {
            $fullname = '';
            if (array_key_exists('first_name', $auth_data)) {
                $fullname .= $auth_data['first_name'];
            }
            if (array_key_exists('last_name', $auth_data)) {
                $fullname .= ' ' . $auth_data['last_name'];
            }
            $fullname = trim($fullname);
            $user = new User([
                'name'  =>  $fullname,
                'email' =>  $auth_data['id'] . '@telegram.org',
                'password' =>  Str::random(10),
                'id_telegram' =>  $auth_data['id']
            ]);
            $user->save();
        }
        $token = auth()->login($user);
        return $this->respondWithToken($token);
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        return response()->json(auth()->user());
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth()->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function verify()
    {
        if(Auth::user()) {
            return $this->responseSuccess();
        } else {
            return $this->responseForbidden();
        }
    }

    /**
     * Get the token array structure.
     *
     * @param string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        return response()->json([
            'name' => auth()->user()->name,
            'id' => auth()->user()->id,
            'access_token' => $token,
            'is_admin' => auth()->user()->is_admin,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60 * 6
        ]);
    }
}
