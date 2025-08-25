<div class="card">
    <div class="card-body">
        <h4 class="card-title">Basic Details</h4>

        <div class="row mb-2">

            {{-- Parent Category --}}
            @php
                $selectedCategories = old('category_parent_id', $productCategories ?? [$category->parent_id ?? '']);
            @endphp
            <div class="col-sm-6 col-md-4">
                <div class="mb-3">
                    <label for="category">Category</label>
                    <select class="form-control" id="category" name="parent_id">
                        <option value="" >Select Category</option>
                        @include('products.partials.category-dropdown', [
                            'categories' => $categories,
                            'prefix' => '',
                            'selectedCategories' => $selectedCategories
                        ])
                    </select>
                </div>
            </div>

            <div class="col-sm-6 col-md-4">
                <div class="mb-3">
                    <x-form-input name="name" value="{{ $category->name ?? '' }}" label="Category Name"
                        placeholder="Enter Category Name" required="true" />
                </div>
            </div>
            <div class="col-sm-6 col-md-4">
                <div class="mb-3">
                    <x-form-input name="slug" value="{{ $category->slug ?? '' }}" label="Category Slug"
                        placeholder="Enter Category Slug" required="true" />
                </div>
            </div>

            <div class="col-sm-6 col-md-4">
                <div class="mb-3">
                    <label class="form-label">Image</label>
                    <input type="file" name="category_image" id="add-category-image" class="form-control" accept="image/*">
                    <div class="row d-block d-md-none">
                        <div class="col-md-3 mt-2">
                            @if (isset($category) && $category->image)
                                <img src="{{ asset($category->image) }}" id="preview-category" class="img-preview img-fluid w-50">
                            @else
                                <img src="" id="preview-category" class="img-fluid w-50;" name="image" hidden>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-md-4">
                <div class="mb-3">
                    <label for="status" class="form-label">Status</label>
                    <select name="status" id="category-status" class="form-control">
                        <option value="Active" {{ isset($category) && $category->status === 'Active' ? 'selected' : '' }}>
                            Active
                        </option>
                        <option value="Inactive" {{ isset($category) && $category->status === 'Inactive' ? 'selected' : '' }}>
                            Inactive
                        </option>
                    </select>
                </div>
            </div>

            <div class="col-md-4 d-none d-md-block">
                @if (isset($category) && $category->image)
                    <img src="{{ asset($category->image) }}" id="previewCategory" class="img-preview img-fluid w-50">
                @else
                    <img src="" id="previewCategory" class="img-fluid w-50" name="image" hidden>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Summary & Description --}}
<div class="card">
    <div class="card-body">
        <div>
            <h4 class="card-title">Summary</h4>
            <textarea name="summary" id="summary" class="editor" rows="2">{{ $category->summary ?? '' }}</textarea>
        </div>
        <div class="mt-3">
            <h4 class="card-title">Description</h4>
            <textarea name="description" id="description" class="editor" rows="2">{{ $category->description ?? '' }}</textarea>
        </div>
    </div>
</div>

{{-- SEO --}}
<div class="card">
    <div class="card-body">
        <h4 class="card-title">SEO</h4>
        <div class="row">
            <div class="col-sm-10 mb-2">
                <x-form-input name="heading" label="Heading" value="{{ $category->heading ?? '' }}" placeholder="Heading" />
            </div>
        </div>
        <div class="row">
            <div class="col-sm-4">
                <div class="mb-2">
                    <x-form-input name="meta_title" label="Meta title" value="{{ $category->meta_title ?? '' }}" placeholder="Meta title" />
                </div>
                <div class="mb-2">
                    <x-form-input name="meta_keywords" label="Meta Keywords" value="{{ $category->meta_keywords ?? '' }}" placeholder="Meta Keywords" />
                </div>
            </div>
            <div class="col-sm-6">
                <div class="mb-3">
                    <label for="meta_description">Meta Description</label>
                    <textarea name="meta_description" class="form-control" id="meta_description" rows="5"
                        placeholder="Meta Description">{{ $category->meta_description ?? '' }}</textarea>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="sticky-submit">
<button type="submit" class="btn btn-primary w-md">Submit</button>
</div>

<x-include-plugins :plugins="['image', 'contentEditor']"></x-include-plugins>
<script>
    $(function() {
        $('#add-category-image').change(function() {
            Image(this, '#preview-category');
            Image(this, '#previewCategory');
        });
    });
</script>
