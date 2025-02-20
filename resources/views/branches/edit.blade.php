@extends('layouts.app')

@section('title', 'Edit Branch')
@section('header-button')
    <a href="{{ route('branches.index') }}" class="btn btn-primary">Go Back</a>
@endsection

@section('content')
    <div class="page-content">
        <div class="container-fluid">

            <!-- start page title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0 font-size-18">Update Branch</h4>
                        <div class="page-title-right">
                            <a href="{{ route('branches.index') }}" class="btn btn-primary">Back</a>
                        </div>
                    </div>
                </div>
            </div>
            <!-- end page title -->

            <div class="row">
                <div class="col-12">
                    <x-error-message :message="session('message')" />
                    <x-success-message :message="session('success')" />

                    <div class="card">
                        <div class="card-body">
                            <form action="{{ route('branches.update', $branch->id) }}" method="POST">
                                @csrf
                                @method('PUT')
                                @include('branches.form')
                            </form>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection
