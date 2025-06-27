<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\CustomerAddresses;
use Illuminate\Support\Facades\Auth;

class CustomerAddressesController extends Controller
{
    public function customer_addresses(Request $request)
    {
        if ($request->has('phone_no')) {
            $customerAddresses = CustomerAddresses::where('phone_no', $request->phone_no)->get();
        }else{
            $customerAddresses = CustomerAddresses::where('user_id', $request->user_id)->get();
        }
        

        $formattedAddresses = $customerAddresses->map(function ($address) {
            $address->is_default = (bool) $address->is_default;
            return $address;
        });

        if ($formattedAddresses->isNotEmpty()) {
            return response()->json([
                'message' => 'Customer Addresses list fetched',
                'data' => $formattedAddresses
            ], 200);
        } else {
            return response()->json([
                'message' => 'Customer Addresses list is empty',
                'data' => []
            ], 200);
        }
    }

    public function add_address(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'country_code' => 'required|string|max:255',
            'phone_no' => 'required|string|max:255',
            'address_line_1' => 'required|string|max:255',
            'address_line_2' => 'required|string|max:255',
            'landmark' => 'required|string|max:255',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'zip_code' => 'required|string|max:20',
            'country' => 'required|string|max:100',
            'is_default' => 'required|boolean',
            'type' => 'required|string|max:20'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if ($request->is_default) {
            CustomerAddresses::where('user_id', $request->user_id)->update(['is_default' => false]);
        }

        $check_if_default_address_exists = CustomerAddresses::where('user_id', $request->user_id)->where('is_default', true)->first();

        $address = CustomerAddresses::create([
            'user_id' => $request->user_id,
            'name' => $request->name,
            'country_code' => $request->country_code,
            'phone_no' => $request->phone_no,
            'address_line_1' => $request->address_line_1,
            'address_line_2' => $request->address_line_2,
            'landmark' => $request->landmark,
            'city' => $request->city,
            'state' => $request->state,
            'zip_code' => $request->zip_code,
            'country' => $request->country,
            'is_default' => !empty($check_if_default_address_exists) ? ($request->is_default ? true : false) : true,
            'type' => $request->type,
        ]);

        if ($address) {
            return response()->json($address, 201);
        }

        return response()->json(['message' => 'Something went wrong. Please try again.'], 500);
    }

    public function delete_address($id)
    {
        $address = CustomerAddresses::find($id);

        if (!$address) {
            return response()->json(['message' => 'Address not found'], 404);
        }

        $address->delete();

        $firstAddress = CustomerAddresses::where('user_id', Auth::id())->first();
        $firstAddress->update(['is_default' => true]);

        return response()->json(['message' => 'Address removed successfully']);
    }

    public function updateAddress(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'country_code' => 'required|string|max:255',
            'phone_no' => 'required|string|max:255',
            'address_line_1' => 'required|string|max:255',
            'address_line_2' => 'required|string|max:255',
            'landmark' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'state' => 'required|string|max:255',
            'zip_code' => 'required|string|max:10',
            'country' => 'required|string|max:255',
            'is_default' => 'nullable|boolean',
            'type' => 'required|string|max:20'
        ]);

        $request->merge([
            'is_default' => $request->has('is_default') ? true : false
        ]);

        $userId = Auth::id();
        $address = CustomerAddresses::where('id', $id)
            ->where('user_id', $userId)
            ->first();

        if (!$address) {
            return response()->json([
                'message' => 'Address not found or unauthorized access.'
            ], 404);
        }

        if ($address->is_default == 1 && !$request->is_default) {
            $firstAddress = CustomerAddresses::where('user_id', $userId)
                ->where('id', '!=', $address->id)
                ->first();

            if ($firstAddress) {
                $firstAddress->update(['is_default' => true]);
            }
        }

        if ($request->is_default) {
            CustomerAddresses::where('user_id', $userId)->update(['is_default' => false]);
        }

        $address->update([
            'name' => $request->name,
            'country_code' => $request->country_code,
            'phone_no' => $request->phone_no,
            'address_line_1' => $request->address_line_1,
            'address_line_2' => $request->address_line_2,
            'landmark' => $request->landmark,
            'city' => $request->city,
            'state' => $request->state,
            'zip_code' => $request->zip_code,
            'country' => $request->country,
            'is_default' => $request->is_default,
            'type' => $request->type,
        ]);

        $address->is_default = (bool) $address->is_default;

        return response()->json([
            'message' => 'Address updated successfully',
            'data' => $address
        ], 200);
    }
}
