@extends('layouts.app')

@section('content')
    <div class="page-content">
        <div class="container-fluid">
            <!-- start page title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0 font-size-18">Colors</h4>

                        <div class="page-title-right">
                            @if(Auth::user()->can('create colors'))
                            <a href="{{ route('colors.create') }}" class="btn btn-primary">Add New Color</a>
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
                            <table id="datatable" class="table table-bordered dt-responsive nowrap w-100">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Color Name</th>
                                        <th>Color Short Code</th>
                                        <th>Status</th>
                                        @canany(['edit colors', 'delete colors'])
                                        <th>Action</th>
                                        @endcanany
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($colors as $key => $color)
                                        <tr>
                                            <td>{{ ++$key }}</td>
                                            <td>{{ ucwords($color->color_name) }}</td>
                                            <td>{{ $color->color_code }}</td>
                                            <td>
                                                <input type="checkbox" id="{{ $color->id }}"  class="update-status" data-id="{{ $color->id }}" switch="success"  data-on="Active" data-off="Inactive" {{ $color->status === 'Active' ? 'checked' : '' }} data-endpoint="{{ route('color-status')}}"/>
                                                <label for="{{ $color->id }}" data-on-label="Active" data-off-label="Inactive"></label>
                                            </td>
                                            @canany(['edit colors', 'delete colors'])
                                            <td class="action-buttons">
                                                @if(Auth::user()->can('edit colors'))
                                                <a href="{{ route('colors.edit', $color->id)}}" class="btn btn-primary btn-sm edit"><i class="fas fa-pencil-alt"></i></a>
                                                @endif
                                                @if(Auth::user()->can('delete colors'))
                                                <button data-source="Color" data-endpoint="{{ route('colors.destroy', $color->id)}}"
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
                </div>
            </div>
        </div>
    </div>
    <x-include-plugins :plugins="['dataTable', 'update-status' ]"></x-include-plugins>

    <script>
        $(document).ready(function() {
            $('#datatable').DataTable({
                columnDefs: [
                    { type: 'string', targets: 1 } 
                ],
                order: [[1, 'asc']]
            });
        });
    </script>
@endsection