@extends('layouts.app')

@section('title', 'Edit Submenu Option')

@section('header-button')
    <a href="{{ route('submenu-options.index') }}" class="btn btn-primary">
        <i class="bx bx-arrow-back"></i> Go Back
    </a>
@endsection

@section('content')
    <div class="page-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <x-error-message :message="session('message')" />
                    <x-success-message :message="session('success')" />

                    <form action="{{ route('submenu-options.update', $option->id) }}" method="POST"
                        enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        @include('submenu_options.form')
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
