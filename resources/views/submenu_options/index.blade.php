@extends('layouts.app')

@section('title', 'Submenu Options')
@section('header-button')
    <a href="{{ route('submenu-options.create') }}" class="btn btn-primary">
        Add New Submenu Option
    </a>
@endsection
@section('content')
    <div class="page-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <x-error-message :message="$errors->first('message')" />
                    <x-success-message :message="session('success')" />

                    <div class="card">
                        <div class="card-body">
                            <table id="submenu-table" class="table table-sm table-striped dt-responsive nowrap w-100">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Icon</th>
                                        <th>Text</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($submenuOptions as $index => $option)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>
                                                @if ($option->icon)
                                                    <img src="{{ asset('storage/' . $option->icon) }}" alt="Icon"
                                                        style="width: 30px; height: 30px;">
                                                @else
                                                    N/A
                                                @endif
                                            </td>
                                            <td>{{ $option->text }}</td>
                                            <td>
                                                <a href="{{ route('submenu-options.edit', $option->id) }}"
                                                    class="btn btn-primary btn-sm py-0 px-1">
                                                    <i class="fas fa-pencil-alt"></i>
                                                </a>
                                                <button data-source="Submenu Option"
                                                    data-endpoint="{{ route('submenu-options.destroy', $option->id) }}"
                                                    class="delete-btn btn btn-danger btn-sm py-0 px-1" title="Delete">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                               
                                            </td>
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

    <x-include-plugins :plugins="['dataTable', 'delete', 'select2']" />

    <script>
        $(document).ready(function() {
            $('#submenu-table').DataTable({
                order: [
                    [0, 'asc']
                ],
                drawCallback: function() {
                    $('#submenu-table th, #submenu-table td').addClass('p-0');
                }
            });
        });
    </script>
@endsection
