<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class LoginController extends Controller
{
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'         => 'required|email',
            'password'      => 'required',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()], 400);
        }
    
        $user = $request->filled('email') 
            ? User::where('email', $request->email)->first() 
            : User::where('phone_number', $request->phone_number)->first();
    
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }
    
        if (Hash::check($request->password, $user->password)) {
            $token = $user->createToken('MyApp')->plainTextToken;
            $user->token = $token;
            return response()->json([
                'success' => true,
                'message' => 'User logged in successfully!',
                'data'    => $user
            ], 200);
        }
    
        return response()->json(['success' => false, 'message' => 'Invalid credentials.'], 401);
    }

    public function register(Request $request){
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:users,email',
            'name' => 'required',
            //'phone_number' => 'required|digits:10|unique:users,phone_number',
            'password' => 'required|min:6',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }
    
        $user = User::create([
            "name" => $request->name,
            "email" => $request->email,
            //"phone_number" => $request->phone_number,
            "password" => Hash::make($request->password),
        ]);
    
        $user->assignRole('Customer');
    
        return response()->json([
            'success' => true,
            'message' => 'User registered successfully',
            'data' => $user
        ], 201);
    }

}
