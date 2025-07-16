<div class="card">
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="icon" class="form-label">Icon (Image) @if (!isset($option))
                            <span class="text-danger">*</span>
                        @endif
                    </label>
                    <input type="file" name="icon" class="form-control @error('icon') is-invalid @enderror"
                        @if (!isset($option))  @endif accept=".jpeg,.png,.jpg,.gif,.svg,.webp">

                    @error('icon')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror

                    @if (isset($option) && $option->icon)
                        <div class="mt-2">
                            <img src="{{ asset('storage/' . $option->icon) }}" width="60" height="60"
                                alt="Icon" class="img-thumbnail">
                            <p class="text-muted small mt-1">Current icon</p>
                        </div>
                    @endif

                    <div class="form-text">
                        Allowed formats: JPEG, PNG, JPG, GIF, SVG, WEBP (Max 2MB)
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="text" class="form-label">Text <span class="text-danger">*</span></label>
                    <input type="text" name="text" class="form-control @error('text') is-invalid @enderror"
                        value="{{ old('text', $option->text ?? '') }}" placeholder="Enter text">

                    @error('text')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </div>
</div>
<div class="mt-3 d-flex justify-content-between">
    <button type="submit" class="btn btn-primary w-md">
        {{ isset($option) ? 'Update' : 'Save' }}
    </button>
</div>
<script>
    $(function () {
        $('input[name="text"]').on('input', function () {
            $(this).removeClass('is-invalid').next('.invalid-feedback').hide();
        });

        $('input[name="icon"]').on('change', function () {
            $(this).removeClass('is-invalid').next('.invalid-feedback').hide();
        });
    });
</script>

