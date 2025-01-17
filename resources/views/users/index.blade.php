@extends('layouts.app')

@section('content')
    <div class="page-content">
        <div class="container-fluid">

            <!-- start page title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0 font-size-18">Users List</h4>
                        <div class="page-title-right">
                            @if (Auth::user()->can('create users'))
                                <a href="{{ route('users.create') }}" class="btn btn-primary">Add New User</a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <!-- end page title -->

            <div class="row">
                <div class="col-12">
                    <x-error-message :message="$errors->first('message')" />
                    <x-success-message :message="session('success')" />

                    <div class="card">
                        <div class="card-body">
                            <table id="datatable" class="table table-bordered dt-responsive  nowrap w-100">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Branch</th>
                                        @canany(['edit users', 'delete users'])
                                            <th>Action</th>
                                        @endcanany
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($users as $key => $user)
                                        <tr>
                                            <td>{{ ++$key }}</td>
                                            <td>{{ ucwords($user->name) }}</td>
                                            <td>{{ $user->email }}</td>
                                            <td>{{ $user->getRoleNames()->first() }}</td>
                                            <td>{{ $user->branch->name ?? 'N/A' }}</td>
                                            @canany(['edit users', 'delete users'])
                                                <td class="action-buttons">
                                                    @if (Auth::user()->can('edit users') &&
                                                            (Auth::user()->hasRole('Super Admin') || $user->getRoleNames()->first() !== 'Super Admin'))
                                                        <a href="{{ route('users.edit', $user->id) }}"
                                                            class="btn btn-primary btn-sm edit">
                                                            <i class="fas fa-pencil-alt"></i>
                                                        </a>
                                                    @endif

                                                    @if (Auth::user()->can('delete users') && $user->getRoleNames()->first() !== 'Super Admin')
                                                        <button data-source="user"
                                                            data-endpoint="{{ route('users.destroy', $user->id) }}"
                                                            class="delete-btn btn btn-danger btn-sm edit">
                                                            <i class="fas fa-trash-alt"></i>
                                                        </button>
                                                    @endif
                                                </td>
                                            @endcanany
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div> <!-- end col -->
            </div> <!-- end row -->

        </div> <!-- container-fluid -->
    </div>
    <x-include-plugins :plugins="['dataTable']"></x-include-plugins>
@endsection
