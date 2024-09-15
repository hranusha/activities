<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
     public function register(Request $request): JsonResponse
     {
         $request->validate([
             'name' => 'required|string|max:255',
             'email' => 'required|string|email|max:255|unique:users',
             'password' => 'required|string|min:8',
         ]);
 
         $user = User::create([
             'name' => $request->name,
             'email' => $request->email,
             'password' => Hash::make($request->password),
         ]);
 
         $token = $user->createToken('auth_token')->plainTextToken;

         return response()->json([
             'message' => 'User registered successfully!'
         ]);
     }
 
     public function login(Request $request)
     {
         $request->validate([
             'email' => 'required|string|email',
             'password' => 'required|string',
         ]);
 
         $credentials = $request->only('email', 'password');
 
         if (!Auth::attempt($credentials)) {
             return response()->json(['message' => 'Invalid login details'], 401);
         }
 
         $user = Auth::user();
         $token = $user->createToken('auth_token')->plainTextToken;
 
         return response()->json([
             'access_token' => $token,
             'token_type' => 'Bearer',
         ]);
     }

     public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json(['message' => 'Successfully logged out']);
    }
}
