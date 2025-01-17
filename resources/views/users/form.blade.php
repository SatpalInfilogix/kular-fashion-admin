<div class="row mb-2">
    <div class="col-md-4">
        <div class="mb-3">
            <x-form-input name="name" value="{{ old('name', $user->name ?? '') }}" label="Username"
                placeholder="Enter Username" required="true" />
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            <x-form-input name="email" value="{{ old('email', $user->email ?? '') }}" label="Email"
                placeholder="Enter Email" required="true" />
        </div>
    </div>
    <div class="col-md-4">
        <label>Role <span style="color:red;">*</span></label>
        <select name="role" class="form-control"
            {{ isset($user) && ($user->getRoleNames()->first() == 'Super Admin' || auth()->id() == $user->id) ? 'readonly disabled' : '' }}>
            <option value="">Select Role</option>
            @foreach ($roles as $role)
                <option value="{{ $role->name }}"
                    @isset($user) @selected($user->hasRole($role->name)) @endisset>
                    {{ $role->name }}
                </option>
            @endforeach
        </select>
        @if (isset($user) && ($user->getRoleNames()->first() == 'Super Admin' || auth()->id() == $user->id))
            <small class="text-muted">
                {{ auth()->id() == $user->id ? 'You cannot edit your own role.' : 'Role cannot be changed for Super Admin.' }}
            </small>
        @endif
        @if ($errors->has('role'))
            <div class="text-danger">
                {{ $errors->first('role') }}
            </div>
        @endif
    </div>
    <div class="col-md-4">
        <label>Branch</label>
        <select name="branch_id" class="form-control">
            <option value="">Select Branch</option>
            @foreach ($branches as $branch)
                <option value="{{ $branch->id }}"
                    @isset($user) @selected($branch->id == $user->branch_id) @endisset>
                    {{ $branch->name }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            <x-form-input type="password" name="password" value="{{ old('password') }}" label="Password"
                placeholder="Enter Password" required="true" />
        </div>
    </div>


</div>
<div class="row mb-2">
    <div class="col-lg-6 mb-2">
        <button type="submit" class="btn btn-primary w-md">Submit</button>
    </div>
</div>
