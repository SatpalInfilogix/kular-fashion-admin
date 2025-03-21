<!-- Product Basic Information -->
<div class="card">
    <div class="card-body">
        <h4 class="card-title">Basic Information</h4>

        <div class="row">
            <div class="col-md-4 mb-2">
                <x-form-input name="name" value='{{ $product->name ?? "" }}' label="Product Name"
                    placeholder="Enter Product Name" required="true" />
            </div>
            <div class="col-md-2 mb-2">
                <x-form-input name="price" type="number" step="0.01" value="{{ $product->price ?? '' }}" label="Price"
                    placeholder="Enter Price" required="true" />
            </div>
            <div class="col-md-2 mb-2">
                <x-form-input name="sale_price" type="number" step="0.01" value="{{ $product->sale_price ?? '' }}"
                    label="Sale Price" placeholder="Enter Sale Price" />
            </div>
            <div class="col-md-2 mb-2">
                <x-form-input name="sale_start" class="sale-date-picker"
                    value="{{ isset($product->sale_start) && $product->sale_start ? \Carbon\Carbon::parse($product->sale_start)->format('d-m-Y') : '' }}"
                    label="Sale Start at" placeholder="Sale Start at" />
            </div>
            <div class="col-md-2 mb-2">
                <x-form-input name="sale_end" class="sale-date-picker"
                    value="{{ isset($product->sale_end) && $product->sale_end ? \Carbon\Carbon::parse($product->sale_end)->format('d-m-Y') : '' }}"
                    label="Sale End at" placeholder="Sale End at" />
            </div>

            <div class="col-md-4 mb-2">
                <label for="tags">Tags</label>
                <select name="tags[]" id="tags" @class(['form-control', 'is-invalid'=> $errors->has('tags')]) multiple>
                    <option value="" disabled>Select tag</option>
                    @foreach ($tags as $tag)
                    <option value="{{ $tag->id }}" @selected(in_array($tag->id, old('tags',
                        $product->tags->pluck('tag_id')->toArray())))>
                        {{ $tag->name }}
                    </option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
</div>

<!-- Product Specification -->
<div class="card">
    <div class="card-body" id="product-specification">
        <h4 class="card-title">Specifications</h4>
        <div class="row" id="specification-container">
            @foreach (old('specifications', $product->webSpecification) as $specificationIndex => $specification)
            <div class="col-md-6 specification-item mb-3" id="spec-0">
                <div class="row">
                    <div class="col-md-5">
                        <x-form-input name="specifications[{{ $specificationIndex }}][key]"
                            value="{{ $specification->key ?? $specification['key'] }}" label="Key" placeholder="Key"
                            class="form-control" required="true" />
                    </div>

                    <div class="col-md-5">
                        <x-form-input name="specifications[{{ $specificationIndex }}][value]"
                            value="{{ $specification->value ?? $specification['value'] }}" label="Value"
                            placeholder="Value" class="form-control" required="true" />
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-danger delete-specification mt-4" data-spec-id="spec-0"><i
                                class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        <button id="add-specification" class="btn btn-secondary mt-3">Add Specification</button>
    </div>
</div>

<!-- Product Description & Summary -->
<div class="card">
    <div class="card-body">
        <div>
            <h4 class="card-title">Summary</h4>
            <textarea name="summary" id="summary" class="editor"
                rows="2">{{ $product->webInfo->summary ?? '' }}</textarea>
        </div>
        <div class="mt-3">
            <h4 class="card-title">Description</h4>
            <textarea name="description" id="description" class="editor"
                rows="2">{{ $product->webInfo->description ?? '' }}</textarea>
        </div>
    </div>
</div>

<!-- Sizes & prices -->
<div class="card">
    <div class="card-body">
        <div class="row">
            <div class="col-md-12">
                <h4 class="card-title">Sizes & Prices</h4>

                <table class="table table-striped table-sm">
                    <thead>
                        <tr>
                            <th>Size</th>
                            @foreach ($product->sizes as $index => $size)
                            <th>{{ $size->sizeDetail->size }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <th>Price</th>
                            @foreach ($product->sizes as $index => $size)
                            <th><input type="text" name="sizes[{{ $size->id }}][web_price]" class="form-control"
                                value="{{ $size->web_price }}"></th>
                            @endforeach
                        </tr>
                        <tr>
                            <th>Sale Price</th>
                            @foreach ($product->sizes as $index => $size)
                            <td><input type="text" name="sizes[{{ $size->id }}][web_sale_price]" class="form-control"
                                value="{{ $size->web_sale_price }}"></td>
                            @endforeach
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="col-md-6">
                <h4 class="card-title">Colors</h4>
                <table class="table table-striped table-sm">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Color Name</th>
                            <th>Swatch</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($product->colors as $index => $color)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $color->colorDetail->name }}</td>
                            <td class="d-flex align-items-center gap-2">
                                <div class="color-swatch-container">
                                    <div class="avatar-sm"
                                        @style(['background: ' . $color->colorDetail->ui_color_code, ' background-image:
                                        url(' . asset($color->swatch_image_path) . ')'])>
                                        <div class="overlay">
                                            <i class="mdi mdi-camera-outline"></i>
                                        </div>
                                    </div>
                                </div>

                                <input type="file" name="color_images[{{ $color->id }}]" accept="image/*"
                                    class="color_image_picker d-none">

                                <button type="button" data-input="removed_color_images" data-id="{{ $color->id }}"
                                    @class([ 'btn btn-text remove-image' , 'd-none'=> !$color->swatch_image_path,
                                    ])>Remove
                                    Image</button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Status & Visibility -->
<div class="card">
    <div class="card-body">
        <h4 class="card-title">Status & Visibility</h4>
        <div class="row">
            <div class="col-md-4">
                <select name="visibilty" id="visibilty" class="form-control">
                    <option value="0" @selected(($product->webInfo->status ?? '') === 0)>Inactive</option>
                    <option value="1" @selected(($product->webInfo->status ?? 1) === 1)>Active</option>
                    <option value="2" @selected(($product->webInfo->status ?? '') === 2)>Hide When Out Of Stock</option>
                </select>
            </div>
            <div class="col-md-4 d-flex gap-2 align-items-center">
                <div class="small-toggle-button">
                    <input type="checkbox" name="split_with_colors" id="splitWithColors" switch="success" data-on="On"
                        data-off="Off" @checked(($product->webInfo->is_splitted_with_colors ?? 0) === 1) />
                    <label class="mb-0" for="splitWithColors" data-on-label="On" data-off-label="Off"></label>
                </div>
                <label for="splitWithColors">Split With Colors</label>
            </div>
        </div>
    </div>
</div>

<!-- Product Images -->
<div class="card">
    <div class="card-body">
        <h4 class="card-title mb-3">Images</h4>

        <div class="row">
            <div class="col-md-3">
                <label for="colorForImages">Select Color</label>
                <select id="colorForImages" class="form-control">
                    <option value="0">Select Color</option>
                    @foreach ($product->colors as $color)
                    <option value="{{ $color->id }}" @selected(count($product->colors)===1)> {{ $color->colorDetail->name }} </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3">
                <label for="colorForImages">Choose Images</label>

                <input type="file" name="images[0][]" id="productImages0" class="d-none" multiple>
                @foreach ($product->colors as $color)
                <input type="file" name="images[{{ $color->id }}][]" class="d-none" id="productImages{{ $color->id }}"
                    multiple>
                @endforeach

                <input type="file" class="form-control" id="productImages" accept="image/*" multiple="multiple" />
            </div>
            <div class="col-md-3 mt-4">
                <button type="button" class="btn btn-google w-100 search-image-modal">
                    <img src="https://www.google.com/favicon.ico" alt="Google Logo" class="google-logo">
                    Choose from Google
                </button>
            </div>
        </div>
        <div id="imagePreview" class="row mt-2 image-preview"></div>

        <div class="row image-preview">
            @foreach ($product->webImage as $index => $image)
            <div data-color-id="{{ $image->product_color_id ?? 0 }}" @class(['col-6 col-sm-2 mb-2', 'd-none'=>
                $image->product_color_id ?? 0 !== 0])>
                <div class="preview-image-container">
                    <img src="{{ asset($image->path) }}" alt="{{ $image->alt }}" class="img-fluid">

                    <button type="button" class="btn btn-danger btn-sm remove-image" data-input="removed_product_images"
                        data-id="{{ $image->id }}">
                        <i class="fa fa-trash"></i>
                    </button>

                    <div class="alt-container">
                        <x-form-input value="{{ $image->alt }}" name="saved_image_alt[{{ $image->id }}]"
                            placeholder="Alt text" />
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>

<!-- SEO -->
<div class="card">
    <div class="card-body">
        <h4 class="card-title">SEO</h4>
        <div class="row">
            <div class="col-sm-10 mb-2">
                <x-form-input name="heading" label="Heading" value="{{ $product->webInfo->heading ?? '' }}"
                    placeholder="Heading" />
            </div>
        </div>
        <div class="row">
            <div class="col-sm-4">
                <div class="mb-2">
                    <x-form-input name="meta_title" label="Meta title" value="{{ $product->webInfo->meta_title ?? '' }}"
                        placeholder="Meta title" />
                </div>
                <div class="mb-2">
                    <x-form-input name="meta_keywords" label="Meta Keywords"
                        value="{{ $product->webInfo->meta_keywords ?? '' }}" placeholder="Meta Keywords"/>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="mb-3">
                    <label for="meta_description">Meta Description</label>
                    <textarea name="meta_description" class="form-control" id="meta_description" rows="5"
                        placeholder="Meta Description">{{ $product->webInfo->meta_description ?? '' }}</textarea>
                </div>
            </div>
        </div>
    </div>
</div>

<input type="hidden" name="removed_color_images">
<input type="hidden" name="removed_product_images">

<div class="card form-footer">
    <div class="card-body">
        <button type="submit" class="btn btn-primary waves-effect waves-light"><i class="fa fa-save me-2"></i>Save Changes</button>
        <a href="{{ setting('web_url') }}/product/{{$product->slug}}" class="btn btn-success waves-effect waves-light" target="_blank"><i class="fa fa-eye me-2"></i>View Product</a>
    </div>
</div>

<div class="modal fade" id="googleImagesModal" tabindex="-1" aria-labelledby="googleImagesModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="googleImagesModalLabel">Select Image For Color</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body"></div>
        </div>
    </div>
</div>