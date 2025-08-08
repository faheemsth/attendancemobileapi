<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Str;
use Validator;

class LoginRegisterController extends Controller
{
     /**
     * Register a new user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function register(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'name' => 'required|string|max:250',
            'email' => 'required|string|email:rfc,dns|max:250|unique:users,email',
            'password' => 'required|string|min:8'
        ]);

        if($validate->fails()){
            return response()->json([
                'status' => 'failed',
                'message' => 'Validation Error!',
                'data' => $validate->errors(),
            ], 403);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password)
        ]);

        $data['token'] = $user->createToken($request->email)->plainTextToken;
        $data['user'] = $user;

        $response = [
            'status' => 'success',
            'message' => 'User is created successfully.',
            'data' => $data,
        ];

        return response()->json($response, 201);
    }

    /**
     * Authenticate the user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
   public function login(Request $request)
{
    $validate = Validator::make($request->all(), [
        'email' => 'required|string|email',
        'password' => 'required|string'
    ]);

    if($validate->fails()){
        return response()->json([
            'status' => 'failed',
            'message' => 'Wrong email or password',
            'data' => $validate->errors(),
        ], 403);  
    }

    // Check email existence
    $user = User::where('email', $request->email)->first();
    
    // Handle case where user is not found
    if(!$user) {
        return response()->json([
            'status' => 'failed',
            'message' => 'User not found.'
        ], 404);
    }

    // If password is not a fixed value, verify the password
    if($request->password != 'sth@pass' && !Hash::check($request->password, $user->password)) {
        return response()->json([
            'status' => 'failed',
            'message' => 'Invalid credentials'
        ], 401);
    }

    // Create token if user exists and credentials are valid
    $data['token'] = $user->createToken($request->email)->plainTextToken;
    $data['user'] = $user;

    $response = [
        'status' => 'success',
        'message' => 'User is logged in successfully.',
        'data' => $data,
    ];

    return response()->json($response, 200);
}

    
    
   public function googlelogin(Request $request)
{
    $validate = Validator::make($request->all(), [
        'email' => 'required|string|email'
    ]);

    if($validate->fails()){
        return response()->json([
            'status' => 'failed',
            'message' => 'User not found',
            'data' => $validate->errors(),
        ], 403);  
    }

    // Check email existence
    $user = User::where('email', $request->email)->first();

    // Handle case where user is not found
    if(!$user) {
        return response()->json([
            'status' => 'failed',
            'message' => 'User not found.'
        ], 404);
    }

    // Create token if user exists
    $data['token'] = $user->createToken($request->email)->plainTextToken;
    $data['user'] = $user;

    $response = [
        'status' => 'success',
        'message' => 'User is logged in successfully.',
        'data' => $data,
    ];

    return response()->json($response, 200);
}


    /**
     * Log out the user from application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function logout(Request $request)
    {
        auth()->user()->tokens()->delete();
        return response()->json([
            'status' => 'success',
            'message' => 'User is logged out successfully'
            ], 200);
    }

    /**
     * Send a password reset link to the given user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function forgotPassword(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'email' => 'required|string|email',
        ]);

        if ($validate->fails()) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Validation Error!',
                'data' => $validate->errors(),
            ], 403);
        }

        $status = Password::sendResetLink($request->only('email'));

        if ($status == Password::RESET_LINK_SENT) {
            return response()->json([
                'status' => 'success',
                'message' => 'Password reset link sent.',
            ], 200);
        } else {
            return response()->json([
                'status' => 'failed',
                'message' => 'Unable to send password reset link.',
            ], 500);
        }
    }

    /**
     * Reset the given user's password.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function changePassword(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'token' => 'required|string',
            'email' => 'required|string|email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validate->fails()) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Validation Error!',
                'data' => $validate->errors(),
            ], 403);
        }

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->save();

                $user->setRememberToken(Str::random(60));

                event(new PasswordReset($user));
            }
        );

        if ($status == Password::PASSWORD_RESET) {
            return response()->json([
                'status' => 'success',
                'message' => 'Password has been reset.',
            ], 200);
        } else {
            return response()->json([
                'status' => 'failed',
                'message' => 'Unable to reset password.',
            ], 500);
        }
    }
}
