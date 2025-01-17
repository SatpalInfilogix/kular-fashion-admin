<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Branch;
use Illuminate\Support\Facades\Gate;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if (!Gate::allows('view users')) {
            abort(403);
        }
        $users = User::with('branch')->get();
        return view('users.index', compact('users'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        if (!Gate::allows('create users')) {
            abort(403);
        }
        $roles = Role::where('name', '!=', 'Super Admin')->get();
        $branches = Branch::where('status', 'Active')->get();
        return view('users.create', compact('roles', 'branches'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if (!Gate::allows('create users')) {
            abort(403);
        }
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:users,email',
            'name' => 'required',
            'password' => 'required',
            'role' => 'required|exists:roles,name',
        ]);
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $user = User::create([
            "name" => $request->name,
            "email" => $request->email,
            "password" => Hash::make($request->password),
            "branch_id" => $request->branch_id,
        ]);
        $user->assignRole($request->role);

        return redirect()->route('users.index')->with("success", "User created successfully");
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    

    public function editProfile()
    {
        $user = auth()->user(); // Get the currently authenticated user
        $branches = Branch::all(); // Include branches if needed
        return view('profile.edit', compact('user', 'branches'));
    }


    public function updateProfile(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|min:8|confirmed',
        ]);

        $user->name = $request->name;
        $user->email = $request->email;

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return redirect()->route('profile.edit')->with('success', 'Profile updated successfully.');
    }

    public function edit(string $id)
    {
        if (!Gate::allows('edit users')) {
            abort(403);
        }

        $user = User::find($id);

        // Check if the user has the Super Admin role
        $isSuperAdmin = $user->getRoleNames()->contains('Super Admin');

        // Fetch roles excluding Super Admin for non-super-admin users
        $roles = $isSuperAdmin ? Role::where('name', 'Super Admin')->get() : Role::where('name', '!=', 'Super Admin')->get();
        $branches = Branch::where('status', 'Active')->get();

        return view('users.edit', compact('roles', 'user', 'branches', 'isSuperAdmin'));
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        if (!Gate::allows('edit users')) {
            abort(403);
        }
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'name' => 'required',
            'role' => 'nullable|exists:roles,name'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $user = User::find($id);
        $user->name = $request->name;
        $user->email = $request->email;
        $user->branch_id = $request->branch_id;
        if ($request->password) {
            $user->password = Hash::make($request->password);
        }

        // Ensure the logged-in user is authenticated and their ID is valid
        if (auth()->check() && auth()->id() != $user->id && $request->filled('role')) {
            $user->syncRoles([$request->role]);
        }

        $user->save();

        return redirect()->back()->with('success', 'User updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        if (!Gate::allows('delete users')) {
            abort(403);
        }
        $delete = User::where('id', $id)->delete();
        return response()->json([
            'success' => true,
            'message' => 'Role deleted successfully.'
        ]);
    }
}
