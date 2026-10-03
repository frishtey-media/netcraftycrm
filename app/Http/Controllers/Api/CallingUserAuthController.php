<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CallingUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use App\Models\callingorder as CallingOrder;

class CallingUserAuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = CallingUser::where(
            'email',
            $request->email
        )->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid email or password.'
            ], 401);
        }

        if (!Hash::check(
            $request->password,
            $user->password
        )) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid email or password.'
            ], 401);
        }

        /*
        |--------------------------------------------------------------------------
        | Check Staff Status
        |--------------------------------------------------------------------------
        */

        if (
            isset($user->status) &&
            !in_array(
                strtolower($user->status),
                ['active', '1', 'enabled']
            )
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Your account is inactive.'
            ], 403);
        }

        /*
        |--------------------------------------------------------------------------
        | Remove Previous Mobile Tokens
        |--------------------------------------------------------------------------
        */

        $user->tokens()->delete();

        /*
        |--------------------------------------------------------------------------
        | Create New Token
        |--------------------------------------------------------------------------
        */

        $token = $user->createToken(
            'calling-panel-mobile'
        )->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful',

            'data' => [
                'staff' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'status' => $user->status,
                ],

                'token' => $token,
                'token_type' => 'Bearer',
            ],
        ]);
    }


    public function me(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'success' => true,

            'data' => [
                'staff' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'status' => $user->status,
                ]
            ]
        ]);
    }


    public function logout(Request $request)
    {
        $user = $request->user();

        if ($user && $user->currentAccessToken()) {
            $user->currentAccessToken()->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Logout successful'
        ]);
    }
}
