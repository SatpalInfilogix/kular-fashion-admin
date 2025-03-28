<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserAddressController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' =>'required|max:25',
            'last_name' =>'nullable|max:25',
            'address_line1' =>'required|max:100',
            'address_line2' =>'nullable|max:100',
            'city' => 'required|string|max:30',
            'state' => 'required|string|max:30',
            'zip_code' => 'required|string|max:20',
            'country' => 'required|string|max:100',
            'phone_number' => 'required|string|max:15',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $address = UserAddress::create($request->all());

        return response()->json([
            'message' => 'Address saved successfully',
            'address' => $address
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(UserAddress $userAddress)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, UserAddress $userAddress)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(UserAddress $userAddress)
    {
        //
    }
}
