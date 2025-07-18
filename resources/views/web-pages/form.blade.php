<div class="card">
    <div class="card-body">
        <h4 class="card-title">Web Page</h4>
        <div class="row">
            <div class="col-md-4 mb-2">
                <x-form-input name="title" label="Page Title" value="{{ old('title', $webPage->title ?? '') }}"
                    placeholder="Enter Page Title" required="true" />
            </div>
            <div class="col-md-4">
                <x-form-input name="slug" label="Slug" value="{{ old('slug', $webPage->slug ?? '') }}"
                    placeholder="Enter Slug" />
            </div>

            <div class="col-md-4">
                <x-form-input type="datetime-local" class="date-picker-publish" name="published_at"
                    label="Published Timestamp" :value="old(
                        'published_at',
                        isset($webPage->published_at)
                            ? \Carbon\Carbon::parse($webPage->published_at)->format('Y-m-d\TH:i')
                            : \Carbon\Carbon::now()->format('Y-m-d\TH:i'),
                    )" />
            </div>
        </div>

        <h4 class="card-title mt-2">Page Content</h4>
        <textarea name="page_content" class="form-control editor" rows="5" placeholder="Enter Page Content">{!! old('page_content', $webPage->page_content ?? '') !!}</textarea>
    </div>
</div>

<!-- Listing Page Rules -->
<div class="card">
    <div class="card-body p-3">
        <h4 class="card-title">Listing Page Rules</h4>

        <div id="rules-container">
            <div class="rule-group border p-3 mb-3">
                <div class="row">
                    <div class="col-md-3">
                        <label class="form-label">Rule</label>
                        <select name="rules[0][type]" class="form-select">
                            <option value="must">Must</option>
                            <option value="must_not">Must Not</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Condition</label>
                        <select name="rules[0][condition]" class="form-select condition-select">
                            <option value="">Select Condition</option>
                            <option value="has_tags">Have one of these tags</option>
                            <option value="has_all_tags">Have all of these tags</option>
                        </select>
                    </div>
                    <div class="col-md-6 tag-group" style="display: none;">
                        <label class="form-label">Tags</label>
                        <select name="rules[0][tags][]" class="form-select tags-select" multiple>
                            @foreach ($tags as $tag)
                                <option value="{{ $tag }}">{{ $tag }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <button type="button" class="btn btn-outline-primary mt-2" onclick="addRuleGroup()">+ Add New Rule
            Group</button>
    </div>
</div>

<!-- Listing Page Content -->
<div class="card">
    <div class="card-body p-3">
        <h4 class="card-title">Listing Page Content</h4>

        <h4 class="card-title mt-3">Description</h4>
        <textarea name="description" id="description" class="form-control editor" rows="5"
            placeholder="Enter Description">{{ old('description', $webPage->description ?? '') }}</textarea>
        <div class="text-muted mt-1">words: <span id="wordCount">0</span> | chars: <span id="charCount">0</span></div>

        <h4 class="card-title mt-2">Summary</h4>
        <textarea name="summary" id="summary" class="form-control editor" rows="5" placeholder="Enter Summary">{{ old('summary', $webPage->summary ?? '') }}</textarea>

        <div class="row mt-4">
            <div class="col-md-6 mb-3">
                <div class="upload-area">
                    <label class="form-label">Image</label>
                    <div class="dropzone">
                        <input type="file" name="image_large" id="image_large" class="file-input" accept="image/*"
                            onchange="previewImage(event, 'large')">

                        <div class="dropzone-content" id="preview_large">
                            @if (!empty($webPage) && !empty($webPage->image_large))
                                <img src="{{ asset('assets/images/' . $webPage->image_large) }}" alt="large image"
                                    class="img-thumbnail uploaded-image" width="15">
                            @else
                                <i class="fas fa-cloud-upload-alt"></i>
                                <p>Drop file here to upload</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Listing Page Options -->
<div class="card">
    <div class="card-body p-4">
        <h4 class="card-title">Listing Page Options</h4>
        @foreach ([
        'hide_categories' => 'Hide categories',
        'hide_all_filters' => 'Hide all filters',
        'show_all_filters' => 'Show all filters',
    ] as $key => $label)
            <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" name="{{ $key }}" id="{{ $key }}"
                    {{ old($key, $webPage->$key ?? false) ? 'checked' : '' }}>
                <label class="form-check-label" for="{{ $key }}">{{ $label }}</label>
            </div>
        @endforeach
        @php
            $filterMode = old('filter_mode') ?? ($webPage->filter_mode ?? '');
            $filtersInput = old('filters', $webPage->filters ?? []);
            $selectedFilters = is_array($filtersInput)
                ? $filtersInput
                : (is_string($filtersInput)
                    ? json_decode($filtersInput, true) ?? []
                    : []);
        @endphp

        <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" name="filter_mode" id="filter_mode_show_some"
                value="show_some" {{ $filterMode === 'show_some' || count($selectedFilters) > 0 ? 'checked' : '' }}>
            <label class="form-check-label" for="filter_mode_show_some">Only show some filters</label>
        </div>

        <div id="filter-list" class="mt-1" style="{{ $filterMode === 'show_some' ? '' : 'display: none;' }}">
            <div class="row">
                @foreach (['Manufacturer', 'Product Type', 'Gender', 'Colour', 'Shoe Style', 'Size'] as $filter)
                    @php
                        $key = strtolower(str_replace(' ', '_', $filter));
                    @endphp
                    <div class="col-md-4 mb-2">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="filters[]"
                                value="{{ $key }}" id="filters_{{ $key }}"
                                {{ in_array($key, $selectedFilters) ? 'checked' : '' }}>
                            <label class="form-check-label"
                                for="filters_{{ $key }}">{{ $filter }}</label>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<!-- SEO Section -->
<div class="card">
    <div class="card-body p-4">
        <h4 class="card-title">Search Engine Optimization (SEO)</h4>
        <div class="row">
            <div class="col-sm-10 mb-2">
                <x-form-input name="heading" label="Heading" value="{{ old('heading', $webPage->heading ?? '') }}"
                    placeholder="Heading" />
            </div>
        </div>
        <div class="row">
            <div class="col-sm-4">
                <div class="mb-2">
                    <x-form-input name="meta_title" label="Meta Title"
                        value="{{ old('meta_title', $webPage->meta_title ?? '') }}" placeholder="Meta Title" />
                </div>
                <div class="mb-2">
                    <x-form-input name="meta_keywords" label="Meta Keywords"
                        value="{{ old('meta_keywords', $webPage->meta_keywords ?? '') }}"
                        placeholder="Meta Keywords" />
                </div>
            </div>
            <div class="col-sm-6">
                <div class="mb-2">
                    <label for="meta_description">Meta Description</label>
                    <textarea name="meta_description" class="form-control" id="meta_description" rows="5"
                        placeholder="Meta Description">{{ old('meta_description', $webPage->meta_description ?? '') }}</textarea>
                </div>
            </div>
        </div>
    </div>
    <div class="sticky-submit">
        <button type="submit" class="btn btn-primary w-md">Submit</button>
    </div>
</div>

<!-- Include necessary plugins -->
<x-include-plugins :plugins="['chosen', 'datePicker', 'contentEditor', 'select2']" />

<style>
    .upload-area {
        text-align: center;
    }

    .dropzone {
        border: 2px dashed #ccc;
        border-radius: 5px;
        padding: 20px;
        cursor: pointer;
        transition: all 0.3s ease;
        position: relative;
        height: 150px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
    }

    .sticky-submit {
        position: fixed;
        bottom: 0;
        left: 18.5%;
        width: 80%;
        background: #fff;
        padding: 1rem 2rem;
        text-align: right;
        box-shadow: 0 -2px 8px rgba(0, 0, 0, 0.1);
        z-index: 1050;
    }

    @media (max-width: 768px) {
        .sticky-submit {
            background: #f8f9fa;
            left: 0;
            width: 100%;
        }
    }

    .dropzone-content {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .dropzone:hover {
        border-color: #999;
        background-color: #f8f9fa;
    }

    .dropzone .file-input {
        position: absolute;
        width: 100%;
        height: 100%;
        opacity: 0;
        cursor: pointer;
    }

    .uploaded-image {
        width: 200px !important;
        height: auto;
        max-height: 300px;
        object-fit: contain;
        display: block;
        margin: 0 auto 0px;
    }

    .form-check {
        padding-left: 1.5em;
    }

    /* Select2 styles */
    .select2-container--default .select2-selection--multiple {
        border: 1px solid #ced4da;
        min-height: 38px;
    }

    .select2-container--default.select2-container--focus .select2-selection--multiple {
        border-color: #86b7fe;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
    }

    .select2-container--default .select2-selection--multiple .select2-selection__choice {
        background-color: #e9ecef;
        border: 1px solid #ced4da;
        color: #495057;
        padding: 0 5px;
        margin-top: 4px;
    }

    .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
        color: #6c757d;
        margin-right: 5px;
    }

    .select2-container .select2-search--inline .select2-search__field {
        margin-top: 5px;
    }
</style>

<script>
    $(document).ready(function() {

        $('.tags-select').select2({
            tags: true,
            tokenSeparators: [','],
            width: '100%',
            placeholder: 'Select or type tags',
            allowClear: true
        });


        $('.dropzone').each(function() {
            const dropzone = $(this);
            const fileInput = dropzone.find('.file-input');
            const previewBox = dropzone.find('.dropzone-content');

            dropzone.on('click', function(e) {
                if (!$(e.target).is('input[type="file"]')) {
                    fileInput.trigger('click');
                }
            });

            fileInput.on('change', function() {
                if (this.files.length > 0) {
                    showPreview(this.files[0], previewBox);
                }
            });

            function showPreview(file, target) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    target.html(
                        `<img src="${e.target.result}" class="img-thumbnail uploaded-image" width="150">`
                    );
                };
                reader.readAsDataURL(file);
            }
        });

        function previewImage(event) {
            const file = event.target.files[0];
            const previewBox = document.getElementById('preview_large');

            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewBox.innerHTML =
                        `<img src="${e.target.result}" class="img-thumbnail uploaded-image" width="150">`;
                };
                reader.readAsDataURL(file);
            }
        }

        function toggleFilterList() {
            if ($('#filter_mode_show_some').is(':checked')) {
                $('#filter-list').show();
            } else {
                $('#filter-list').hide();
            }
        }

        toggleFilterList();
        $('#filter_mode_show_some').on('change', toggleFilterList);


        $('#description').on('input', function() {
            const text = $(this).val();
            $('#wordCount').text(text.trim() ? text.trim().split(/\s+/).length : 0);
            $('#charCount').text(text.length);
        }).trigger('input');
    });


    function addRuleGroup() {
        var index = $('.rule-group').length;

        var $ruleGroup = $('<div>', {
            class: 'rule-group border p-3 mb-3 position-relative'
        });


        var $deleteBtn = $('<button>', {
            type: 'button',
            class: 'btn btn-sm btn-outline-danger delete-rule-group',
            html: '<i class="fas fa-times"></i>',
            click: function() {
                deleteRuleGroup(this);
            }
        }).css({
            'position': 'absolute',
            'right': '10px',
            'top': '10px',
            'width': '24px',
            'height': '24px',
            'padding': '0',
            'display': 'flex',
            'align-items': 'center',
            'justify-content': 'center',
            'border-radius': '50%'
        });

        $ruleGroup.append($deleteBtn);

        var $row = $('<div>', {
            class: 'row'
        });

        var $ruleCol = $('<div>', {
            class: 'col-md-3'
        }).append(
            $('<label>', {
                class: 'form-label',
                text: 'Rule'
            }),
            $('<select>', {
                name: `rules[${index}][type]`,
                class: 'form-select'
            }).append(
                $('<option>', {
                    value: 'must',
                    text: 'Must'
                }),
                $('<option>', {
                    value: 'must_not',
                    text: 'Must Not'
                })
            )
        );

        var $conditionCol = $('<div>', {
            class: 'col-md-3'
        }).append(
            $('<label>', {
                class: 'form-label',
                text: 'Condition'
            }),
            $('<select>', {
                name: `rules[${index}][condition]`,
                class: 'form-select condition-select'
            }).append(
                $('<option>', {
                    value: '',
                    text: 'Select Condition'
                }),
                $('<option>', {
                    value: 'has_tags',
                    text: 'Have one of these tags'
                }),
                $('<option>', {
                    value: 'has_all_tags',
                    text: 'Have all of these tags'
                })
            )
        );

        var $tagCol = $('<div>', {
            class: 'col-md-6 tag-group',
            style: 'display: none;'
        }).append(
            $('<label>', {
                class: 'form-label',
                text: 'Tags'
            }),
            $('<select>', {
                name: `rules[${index}][tags][]`,
                class: 'form-select tags-select',
                multiple: 'multiple'
            })
        );


        @foreach ($tags as $tag)
            $tagCol.find('select').append(
                $('<option>', {
                    value: '{{ $tag }}',
                    text: '{{ $tag }}'
                })
            );
        @endforeach

        $row.append($ruleCol, $conditionCol, $tagCol);
        $ruleGroup.append($row);
        $('#rules-container').append($ruleGroup);

        $ruleGroup.find('.tags-select').select2({
            tags: true,
            tokenSeparators: [','],
            width: '100%',
            placeholder: 'Select or type tags',
            allowClear: true
        });
    }

    function deleteRuleGroup(button) {

        if ($('.rule-group').length > 1) {
            $(button).closest('.rule-group').fadeOut(200, function() {
                $(this).remove();

                $('.rule-group').each(function(index) {
                    $(this).find('select, input').each(function() {
                        var name = $(this).attr('name');
                        if (name) {
                            name = name.replace(/rules\[\d+\]/, `rules[${index}]`);
                            $(this).attr('name', name);
                        }
                    });
                });
            });
        } else {
            alert('You must have at least one rule group.');
        }
    }

    $(document).on('change', '.condition-select', function() {
        var selectedValue = $(this).val();
        var $row = $(this).closest('.row');
        var $tagGroup = $row.find('.tag-group');
        var $tagSelect = $row.find('.tags-select');

        if (selectedValue === 'has_tags' || selectedValue === 'has_all_tags') {
            $tagGroup.show();
            if (selectedValue === 'has_all_tags') {
                $tagSelect.find('option').prop('selected', true);
                $tagSelect.trigger('change');
            }
        } else {
            $tagGroup.hide();
        }
    });
    $(document).on('select2:open', () => {
        document.querySelector('.select2-container--open .select2-search__field').focus();
    });
</script>
