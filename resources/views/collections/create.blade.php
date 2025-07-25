@extends('layouts.app', ['isVueComponent' => true])

@section('title', 'Create a new Collection')
@section('header-button')
    <a href="{{ route('collections.index') }}" class="btn btn-primary"><i class="bx bx-arrow-back"></i> Go Back</a>
@endsection

@section('content')
    <div class="page-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <x-error-message :message="$errors->first('message')" />
                    <x-success-message :message="session('success')" />

                    <form action="{{ route('collections.store') }}" method="post" enctype="multipart/form-data">
                        @csrf
                        <collection-conditions
                            :condition-dependencies='@json($conditionDependencies)'></collection-conditions>
                    </form>

                </div>
            </div>
        </div>
    </div>

    <x-include-plugins :plugins="['chosen', 'datePicker', 'contentEditor', 'select2']"></x-include-plugins>
    <script>
        $(document).ready(function() {
            $('#sort_by,#filters').select2();
        });
    </script>
@endsection

