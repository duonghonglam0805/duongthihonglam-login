<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\JWT;
use App\Http\ControllersException;
use App\Http\Controllers\JWTException;
use App\Models\User;

class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'refresh']]);
    }
    public function login()
    {
        $credentials = request(['email', 'password']);

        if (!$token = auth('api')->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    
        return $this->createRefreshToken();
    }
    public function profile()
    {
        try {
            return response()->json(auth('api')->user());
        } catch (JWTException $exception) {
            return response()->json(['error' => 'Unauthorized'], 401);
        
    }
}
    public function logout()
    {
        auth('api')->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }
    public function refresh()
    {
        // $refreshToken = request()->refresh_token;
        // return response()->json($refreshToken); 
        // hoặc 
        $refreshToken = request()->refresh_token;
        try {
            // $decoded= JWTAuth::getJWTProvider()->decode($refreshToken);
            // return response()->json($decoded); 
            $decoded = JWTAuth::getJWTProvider()->decode($refreshToken);
            //Xử lý cấp lại token mới
            //Lấy thông tin user
            $user = User::find($decoded['sub']);
            if(!$user){
                return response()->json(['error' => 'User not found'], 404);
            }
            $token = auth('api')->login($user);
            $refreshToken = $this->createRefreshToken();
            return $this->respondWithToken($token, $refreshToken);
            return response()->json($user);
        } catch (JWTException $exception) {
            return response()->json(['error' => 'Refresh token invalid'], 500);
        }


        // return $this->respondWithToken(auth('api')->refresh());
    }
    protected function respondWithToken($token, $refreshToken)
    {
        return response()->json([
            'access_token' => $token,
            'refresh_token' => $refreshToken,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60
        ]);
    }
    public function createRefreshToken(){
        $data = [
            'sub' => auth('api')->user()->id,
            'random' => rand() . time(),
            'exp' => time() + config('jwt.refresh_tt1'),
        ];
        $refreshToken = JWTAuth::getJWTProvider()->endode($data);
        return $refreshToken;
    }
}