@extends('layouts.app')

@section('content')
    <div class="page-content">
        <div class="container-fluid">

            <!-- start page title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0 font-size-18">Step 2</h4>

                        <div class="page-title-right">
                            <a href="{{ route('products.create.step-1') }}" class="btn btn-primary"><i class="bx bx-arrow-back"></i> Back to Step 1</a>
                        </div>

                    </div>
                </div>
            </div>
            <!-- end page title -->

            <div class="row">
                <div class="col-12">
                    <x-error-message :message="$errors->first('message')" />
                    <x-success-message :message="session('success')" />

                    <div class="row">
                        <div class="col-sm-6 col-md-6">
                            <div class="mb-2 d-flex gap-1">
                                <h5 class="card-title">Article Code: </h5>
                                <p class="card-text"> {{ $savingProduct->article_code }}</p>
                            </div>    
                        </div>
                        @if($savingProduct->short_description)
                        <div class="col-sm-6 col-md-6">
                            <div class="mb-2 d-flex gap-1">
                                <h5 class="card-title">Short Description: </h5>
                                    <p class="card-text"> {{ $savingProduct->short_description }}</p>
                            </div>    
                        </div>
                        @endif
                        <div class="col-sm-6 col-md-6">
                            <div class="mb-2 d-flex gap-1">
                                <h5 class="card-title">Manufacture Code: </h5>
                                <p class="card-text"> {{ $savingProduct->manufacture_code }}</p>
                            </div>    
                        </div>
                        <div class="col-sm-6 col-md-6">
                            <div class="mb-2 d-flex gap-1">
                                <h5 class="card-title">MRP: </h5>
                                <p class="card-text"> {{ $savingProduct->mrp }}</p>
                            </div>    
                        </div>
                        <div class="col-sm-6 col-md-6">
                            <div class="mb-2 d-flex gap-1">
                                <h5 class="card-title">Brand: </h5>
                                <p class="card-text"> {{ $brand->brand_name }}</p>
                            </div>    
                        </div>
                        <div class="col-sm-6 col-md-6">
                            <div class="mb-2 d-flex gap-1">
                                <h5 class="card-title">Size Scale: </h5>
                                <p class="card-text">{{ $sizeScale->size_scale }}</p>
                            </div>    
                        </div>
                    </div>   

                    <div class="card">
                        <div class="card-body">  
                            <form action="{{ route('products.create.step-2') }}" method="post" enctype="multipart/form-data">
                                @csrf
                                <div class="row">
                                    <div class="col-sm-6 col-md-3">
                                        <div class="mb-3">
                                            <x-form-input name="supplier_color_code[0]" value="{{ old('supplier_color_code[0]', $savingProduct->variants[0] ?? '') }}" label="Supplier Color Code" placeholder="Enter Supplier code" required/>
                                        </div>
                                    </div>
                                    <div class="col-sm-6 col-md-3">
                                        <div class="mb-3">
                                            <label for="color">Select Color <span class="text-danger">*</span></label>
                                            <select name="colors[0]" id="color" class="form-control{{ $errors->has('colors.0') ? ' is-invalid' : '' }}">
                                                <option value="" selected>Select Color</option>                                                
                                                @foreach($colors as $color)
                                                    <option value="{{ $color->id }}" {{ old('colors[0]',$color->id) == $color->id ? 'selected' : '' }}>
                                                        {{ $color->color_name }} ({{ $color->color_code }})
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('colors.0')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-sm-6 col-md-3">
                                        <div class="mb-3">
                                            <label for="color">Select Size Range(Min) <span class="text-danger">*</span></label>
                                            <select name="size_range_min" id="size_range_min" class="form-control{{ $errors->has('color') ? ' is-invalid' : '' }}">
                                                @foreach($sizes as $size)
                                                    <option value="{{ $size->id }}">
                                                        {{ $size->size }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('size_range_min')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-sm-6 col-md-3">
                                        <div class="mb-3">
                                            <label for="size_range_max">Select Size Range(Max) <span class="text-danger">*</span></label>
                                            <select name="size_range_max" id="size_range_max" class="form-control{{ $errors->has('size_range_max') ? ' is-invalid' : '' }}">
                                                @foreach($sizes as $index => $size)
                                                    <option value="{{ $size->id }}" {{ $loop->last ? 'selected' : '' }}>
                                                        {{ $size->size }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('size_range_max')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    
                                    <div class="col-12" id="color-fields">
                                        @if(isset($savingProduct->variants))
                                            @foreach ($savingProduct->variants as $index => $variant)
                                                
                                                @if($index !== 0)
                                                <div class="color-field" id="color-field-{{ $index }}">
                                                    <div class="row">
                                                        <div class="col-sm-6 col-md-3">
                                                            <div class="mb-3">
                                                                <x-form-input 
                                                                    name="supplier_color_code[{{ $index }}]" 
                                                                    value="{{ old('supplier_color_code['.$index.']', $variant ?? '') }}" 
                                                                    label="Supplier Color Code" 
                                                                    placeholder="Enter Supplier code" 
                                                                    required
                                                                />
                                                            </div>
                                                        </div>
                                                        <div class="col-sm-6 col-md-3">
                                                            <div class="mb-3">
                                                                <label for="color-{{ $index }}">Select Color <span class="text-danger">*</span></label>
                                                                <select name="colors[{{ $index }}]" id="color-{{ $index }}" class="form-control{{ $errors->has("colors.$index") ? ' is-invalid' : '' }}">
                                                                    <option value=""  {{ old("colors.$index") === null ? 'selected' : '' }}>Select Color</option>
                                                                    @foreach($colors as $color)
                                                                        <option value="{{ $color->id }}" @selected($savingProduct->colors[$index] == $color->id)
                                                                            {{ old("colors[$index]", $savingProduct->colors[$index] ?? '') }}>
                                                                            {{ $color->color_name }} ({{ $color->color_code }})
                                                                        </option>
                                                                    @endforeach
                                                                </select>
                                                                @error("colors.$index")
                                                                    <span class="invalid-feedback">{{ $message }}</span>
                                                                @enderror
                                                            </div>
                                                        </div>
                                                        <div class="col-sm-6 col-md-3">
                                                            <button type="button" class="btn btn-danger remove-color-btn mt-4" data-id="{{ $index }}">Remove</button>
                                                        </div>
                                                    </div>
                                                </div>
                                                @endif
                                            @endforeach
                                        @endif
                                    </div>

                                    
                                    <div class="row mb-2">
                                        <div class="col-lg-6 mb-2">
                                            <button type="button" id="add-color-btn" class="btn btn-secondary">Add New Color</button>
                                            
                                            <button type="submit" class="btn btn-primary w-md">Continue</button>
                                        </div>
                                    </div>
                                </div>    
                            </form>      
                        </div>    
                    </div>
                </div> <!-- end col -->
            </div> <!-- end row -->
        </div> <!-- container-fluid -->
    </div>
    <x-include-plugins :plugins="['chosen']"></x-include-plugins>
    @push('scripts')
    <script>
        $(function(){
            var colorIndex = {{ isset($savingProduct->variants) ? count($savingProduct->variants)-1 : 1 }};
            $('#add-color-btn').click(function() {
                colorIndex++;

                var newColorField = `
                    <div class="color-field" id="color-field-${colorIndex}">
                        <div class="row">
                            <div class="col-sm-6 col-md-3">
                                <div class="mb-3">
                                    <x-form-input name="supplier_color_code[${colorIndex}]" value="" label="Supplier Color Code" placeholder="Enter Supplier code" required/>
                                </div>
                            </div>
                            <div class="col-sm-6 col-md-3">
                                <div class="mb-3">
                                    <label for="colors-${colorIndex}">Select Color <span class="text-danger">*</span></label>
                                    <select name="colors[${colorIndex}]" id="colors-${colorIndex}" class="form-control">
                                        <option value="" disabled selected>Select Color</option>
                                        @foreach($colors as $color)
                                            <option value="{{ $color->id }}">
                                                {{ $color->color_name }} ({{ $color->color_code }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-sm-6 col-md-3">
                                <button type="button" class="btn btn-danger remove-color-btn mt-4" data-id="${colorIndex}">Remove</button>
                            </div>
                        </div>
                    </div>`;

                // Append new color field to the container
                $('#color-fields').append(newColorField);
                $('#color-' + colorIndex).chosen({ width: '100%' });
            });

            
            // Remove color field when the remove button is clicked
            $(document).on('click', '.remove-color-btn', function() {
                var id = $(this).data('id');
                $('#color-field-' + id).remove();
            });

            $('#color').chosen({
                width: '100%',
                placeholder_text_multiple: 'Select Color'
            });
            
            // Select the first size by default for size_range_min
            $('#size_range_min').val($('#size_range_min option:first').val()).trigger('chosen:updated');

            // Event listener for changes in the 'size_range_min' dropdown
            $('#size_range_min').on('change', function() {
                var minSizeId = parseInt($(this).val()); // Convert min size ID to an integer

                // Enable the size_range_max dropdown and reset to default
                $('#size_range_max').prop('disabled', false).trigger('chosen:updated');

                // Show all the size options first
                $('#size_range_max option').show();

                // Hide options that are smaller than the selected min size
                $('#size_range_max option').each(function() {
                    var maxSizeId = parseInt($(this).val()); // Convert max size ID to an integer

                    // Only hide the options that are less than the min size
                    if (maxSizeId < minSizeId) {
                        $(this).hide();
                    }
                });

                // Refresh the 'chosen' dropdown after the changes
                $('#size_range_max').trigger('chosen:updated');

                // Find the last visible option in the size_range_max dropdown
                var visibleOptions = $('#size_range_max option:visible');
                
                // If there are visible options, select the last one
                if (visibleOptions.length > 0) {
                    // Select the last visible option
                    var lastVisibleOptionValue = visibleOptions.last().val();
                    $('#size_range_max').val(lastVisibleOptionValue).trigger('chosen:updated');
                } else {
                    // If no visible options are left, select the last option in the original list
                    var lastOptionValue = $('#size_range_max option:last').val();
                    $('#size_range_max').val(lastOptionValue).trigger('chosen:updated');
                }
            });
        });
        </script>
    @endpush
@endsection