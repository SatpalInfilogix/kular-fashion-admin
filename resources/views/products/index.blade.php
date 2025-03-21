@extends('layouts.app')

@section('title', 'Products')
@section('header-button')
    {{-- <form action="{{ route('products.import') }}" method="POST" enctype="multipart/form-data" id="importForm" class="d-inline">
        @csrf
        <input type="file" name="file" id="fileInput" accept=".csv" required style="display: none;" onchange="document.getElementById('importForm').submit();">
        <button type="button" class="btn btn-primary" onclick="document.getElementById('fileInput').click();">
            <i class="fas fa-file-import"></i> Import Products
        </button>
    </form>

    <a href="{{ route('products.export') }}" class="btn btn-primary">
        <i class="bx bx-download"></i> Download Product Configuration File
    </a> --}}


    <button id="bulk-edit-button" class="btn btn-warning d-none" data-bs-toggle="modal" data-bs-target="#bulkEditModal">
        <i class="fas fa-edit"></i> Bulk Edit
    </button>

    @if (Auth::user()->can('create products'))
        <a href="{{ route('products.create') }}" id="add-product-link" class="btn btn-primary">
            <i class="bx bx-plus fs-16"></i> Add New Product
        </a>
    @endif
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
                            <div class="row">
                                <div class="form-group col-3 mb-2">
                                    <label for="brandFilter" class="mb-0">Brand Name:</label>
                                    <select id="brandFilter" class="form-control select2">
                                        <option value="">All Brands</option>
                                        @foreach ($brands as $brand)
                                            <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="form-group col-3 mb-2">
                                    <label for="typeFilter" class="mb-0">Product Type:</label>
                                    <select id="typeFilter" class="form-control select2">
                                        <option value="">All Products Types</option>
                                        @foreach ($productTypes as $productType)
                                            <option value="{{ $productType->id }}">{{ $productType->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="form-group col-3 mb-2">
                                    <label for="departmentFilter" class="mb-0">Department:</label>
                                    <select id="departmentFilter" class="form-control select2">
                                        <option value="">All Department</option>
                                        @foreach ($departments as $department)
                                            <option value="{{ $department->id }}">{{ $department->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <table id="product-table" data-selected-products="" data-unselected-products=""
                                class="table table-bordered dt-responsive nowrap w-100 table-striped">
                                <thead>
                                    <tr>
                                        <th>
                                            <input type="checkbox" id="select-all-checkbox" class="form-check-input">
                                        </th>
                                        <th>#</th>
                                        <th>Article Code</th>
                                        <th>Manufacture Code</th>
                                        <th>Product Type</th>
                                        <th>Brand</th>
                                        <th>Department</th>
                                        <th>Description</th>
                                        <th>Price</th>
                                    </tr>
                                </thead>

                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <x-include-plugins :plugins="['dataTable', 'update-status', 'select2']"></x-include-plugins>
    <script type="text/javascript">
        $(document).ready(function() {
            $('#brandFilter, #typeFilter, #departmentFilter').select2({
                width: '100%',
            });

            var selectedProducts = [];
            var unselectedProducts = [];

            var table = $('#product-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('get.products') }}",
                    data: function(d) {
                        d.page = Math.floor(d.start / d.length) + 1;
                        d.brand_id = $('#brandFilter').val();
                        d.product_type_id = $('#typeFilter').val();
                        d.department_id = $('#departmentFilter').val();
                        d.order = d.order; // Pass sorting parameters
                    }
                },
                columns: [
                    {
                        title: '<input type="checkbox" class="form-check-input" id="select-all-checkbox">',
                        data: null,
                        render: function(data, type, row) {
                            let selectedProducts = $('[data-selected-products]').attr('data-selected-products');
                            selectedProducts = selectedProducts.split(',');

                            let checked = selectedProducts.includes(String(row.id)) ? 'checked' : '';

                            if (selectedProducts.includes('-1')) {
                                checked = 'checked';
                                selectedProducts.push(row.id);
                            }

                            if (unselectedProducts.includes(String(row.id))) {
                                checked = '';
                            }

                            return `<input type="checkbox" class="product-checkbox form-check-input" value="${row.id}" ${checked}>`;
                        },
                        orderable: false
                    },
                    { title: "Article Code", data: 'article_code' },
                    { title: "Manufacture Code", data: 'manufacture_code' },
                    { title: "Brand", data: 'brand.name' },
                    { title: "Product Type", data: 'product_type.name' },
                    { title: "Department", data: 'department.name' },
                    { title: "Short Description", data: 'short_description' },
                    { title: "Price", data: 'price' },
                    {
                        title: "Actions",
                        data: null,
                        render: function(data, type, row) {
                            var actions = '<div class="action-buttons">';
                            @can('view products')
                                actions += `<a href="{{ route('products.show', ':id') }}" class="btn btn-secondary btn-sm py-0 px-1">`
                                    .replace(/:id/g, row.id);
                                actions += `<i class="fas fa-eye"></i>`;
                                actions += `</a>`;
                            @endcan

                            @can('edit products')
                                actions += `<a href="{{ route('products.edit.web-configuration', ':id') }}" class="btn btn-success btn-sm edit py-0 px-1">`
                                    .replace(/:id/g, row.id);
                                actions += `<i class="fas fa-image"></i>`;
                                actions += `</a>`;

                                actions += `<a href="{{ route('products.edit', ':id') }}" class="btn btn-primary btn-sm edit py-0 px-1">`
                                    .replace(/:id/g, row.id);
                                actions += `<i class="fas fa-pencil-alt"></i>`;
                                actions += `</a>`;
                            @endcan

                            @can('delete products')
                                actions += `<button data-source="product" data-endpoint="{{ route('products.destroy', ':id') }}" class="delete-btn btn btn-danger btn-sm py-0 px-1">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>`
                                    .replace(/:id/g, row.id);
                            @endcan

                            return actions;
                        }
                    }
                ],
                order: [[2, 'asc']], // Default sorting on Manufacture Code (3rd column)
                drawCallback: function(settings) {
                    let api = this.api();
                    $('#product-table th, #product-table td').addClass('p-1');

                    let rows = api.rows({ page: 'current' }).data().length;

                    let searchValue = $('#custom-search-input').val();
                    if (rows === 0 && searchValue) {
                        $('#add-product-link').attr('href', `{{ route('products.create') }}?mfg_code=${searchValue}`)
                    } else {
                        $('#add-product-link').attr('href', `{{ route('products.create') }}`)
                    }
                }
            });

            $('#brandFilter, #typeFilter, #departmentFilter').on('change', function() {
                table.ajax.reload();
            });

            $('#product-table_filter').prepend(
                `<input type="text" id="custom-search-input" class="form-control" placeholder="Search Products">`
            );

            $('#custom-search-input').on('keyup', function() {
                table.search(this.value).draw();
            });

            function updateSelectedProducts() {
                $('#product-table').attr('data-selected-products', selectedProducts.join(','));
                $('#product-table').attr('data-unselected-products', unselectedProducts.join(','));

                if (!$('.product-checkbox:checked').length) {
                    $('#bulk-edit-button').addClass('d-none');
                } else {
                    $('#bulk-edit-button').removeClass('d-none');
                }
            }

            // Select all checkboxes
            $('#select-all-checkbox').on('change', function() {
                if ($(this).is(':checked')) {
                    selectedProducts = ['-1'];
                } else {
                    selectedProducts = [];
                }

                var checked = this.checked;
                $('.product-checkbox').each(function() {
                    if (!unselectedProducts.includes($(this).val())) {
                        this.checked = checked;
                    }
                });

                updateSelectedProducts();
            });

            // Individual checkbox selection
            $('#product-table').on('change', '.product-checkbox', function() {
                if ($(this).is(':checked')) {
                    selectedProducts.push($(this).val());
                } else {
                    let selectedProductIndex = selectedProducts.indexOf($(this).val());
                    if (selectedProductIndex !== -1) {
                        selectedProducts.splice(selectedProductIndex, 1);
                    }
                }

                if (!$(this).is(':checked') && $('#select-all-checkbox:checked').length) {
                    unselectedProducts.push($(this).val());
                } else {
                    let unselectedProductIndex = unselectedProducts.indexOf($(this).val());
                    if (unselectedProductIndex !== -1) {
                        unselectedProducts.splice(unselectedProductIndex, 1);
                    }
                }
                updateSelectedProducts();
            });

            $('.apply-bulk-edit-action').on('click', function() {
                $.ajax({
                    url: "{{ route('products.bulk-edit') }}",
                    method: 'POST',
                    data: {
                        selected_products: selectedProducts,
                        unselected_products: unselectedProducts,
                        '_token': '{{ csrf_token() }}',
                        action: $('#bulkEditAction').val(),
                        tags: $('#bulkEditTags').val()
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#bulkEditTags').val(null).trigger('change');
                            $('#bulkEditTagsModal').modal('hide');

                            swal({
                                title: "Success!",
                                text: response.message,
                                type: "success",
                                showConfirmButton: false,
                                timer: 2000
                            })
                        } else {
                            let message = response.message || `Something went wrong!`;

                            swal({
                                title: "Oops!",
                                text: message,
                                type: "error",
                                confirmButtonText: 'Okay'
                            })
                        }
                    }
                })
            });
        });
    </script>

    @include('products.partials.bulk-edit')

    @push('styles')
        <style>
            #product-table_filter label {
                display: none;
            }
        </style>
    @endpush
@endsection
