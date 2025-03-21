<script>
    $(function() {
        $('.search-image-modal').click(function() {
            $('#googleImagesModal').modal('show');
            initialize();
        });

        $('.color-swatch-container').click(function() {
            $(this).parent().find('.color_image_picker').click();
        });

        $('.color_image_picker').change(function(event) {
            let file = event.target.files[0];
            if (file) {
                var reader = new FileReader();
                var $parentRow = $(this).closest('td');

                reader.onload = function(e) {
                    $parentRow.find('.avatar-sm').css('background-image', 'url(' + e.target.result +
                        ')');
                    $parentRow.find('.remove-image').removeClass('d-none');
                };

                reader.readAsDataURL(file);
            }
        });

        function removeTempImage(input, id) {
            $targetedElement = $(`[data-input="${input}"][data-id="${id}"]`);
            if (input === 'removed_color_images') {
                $parentRow = $targetedElement.closest('td');
                $parentRow.find('.avatar-sm').css('background-image', '');
                $parentRow.find('.remove-image').addClass('d-none');
            } else if (input === 'removed_product_images') {
                $parentContainer = $targetedElement.closest('.preview-image-container');
                $parentContainer.parent().remove();
            } else {
                console.warn('Add condition to remove temp image')
            }
        }

        $(document).on('click', '.remove-image', function() {
            $targetElement = $(this);

            swal({
                title: "Are you sure?",
                text: `You really want to remove this image?`,
                type: "warning",
                showCancelButton: true,
                closeOnConfirm: false,
            }, function(isConfirm) {
                if (isConfirm) {
                    let inputName = $targetElement.attr('data-input');

                    $respectiveInput = $(`[name="${inputName}"]`);
                    $targetedId = $targetElement.attr('data-id');;

                    let currentValue = $respectiveInput.val();
                    if (currentValue) {
                        $respectiveInput.val(currentValue + ',' + $targetedId);
                    } else {
                        $respectiveInput.val($targetedId);
                    }

                    removeTempImage(inputName, $targetedId);

                    swal.close();
                }
            });
        });

        $('#colorForImages').change(function(){
            let selected_color_id = $(this).val();
            $(`.image-preview [data-color-id]`).addClass('d-none');
            $(`.image-preview [data-color-id="${selected_color_id}"]`).removeClass('d-none');
        })

        let filesByColor = {}; // Object to map colorId to an array of files

        $('#productImages').on('change', function(event) {
            let files = event.target.files;
            let colorId = $('#colorForImages').val();

            // Initialize the colorId array if it doesn't exist yet
            if (!filesByColor[colorId]) {
                filesByColor[colorId] = [];
            }

            // Add the new files to the corresponding colorId
            filesByColor[colorId] = filesByColor[colorId].concat(Array.from(files));

            var dataTransfer = new DataTransfer();

            // Add the previously selected files for the current colorId
            if (filesByColor[colorId]) {
                filesByColor[colorId].forEach(file => {
                    dataTransfer.items.add(file);
                });
            }

            // Update the file input field with the new files
            $(`#productImages${colorId}`)[0].files = dataTransfer.files;

            // Image preview logic
            $.each(files, function(index, file) {
                let reader = new FileReader();

                reader.onload = function(e) {
                    let imageBox = $(`<div class="col-6 col-sm-2 mb-2" data-color-id="${colorId}"></div>`);
                    let imageContainer = $('<div class="preview-image-container"></div>');
                    let img = $('<img src="' + e.target.result + '" class="img-fluid" />');
                    let removeBtn = $(
                        '<button type="button" class="btn btn-danger btn-sm remove-image-btn"><i class="fa fa-trash"></i></button>'
                    );
                    let altDiv = $(
                        `<div class="alt-container"><input type="text" name="image_alt[${colorId}][${index}]" class="form-control" placeholder="Alt text"></div>`
                    );

                    // Remove image when remove button is clicked
                    removeBtn.on('click', function() {
                        filesByColor[colorId] = filesByColor[colorId].filter(f =>
                            f !== file);
                        updateImagesInput(colorId);
                        imageBox.remove();
                    });

                    imageContainer.append(img).append(removeBtn).append(altDiv);
                    imageBox.append(imageContainer);

                    $('#imagePreview').append(imageBox);
                };

                reader.readAsDataURL(file);
            });

            $(this).empty();
            $(this).val('');
        });

        function updateImagesInput(colorId) {
            var dataTransfer = new DataTransfer();

            // Add the files for the specific colorId
            if (filesByColor[colorId]) {
                filesByColor[colorId].forEach(file => dataTransfer.items.add(file));
            }

            $(`#productImages${colorId}`)[0].files = dataTransfer.files;
        }


        // Add Specification
        let specCount = $('.specification-item').length;

        $('#add-specification').click(function(event) {
            event.preventDefault();
            specCount++;
            const newDiv = `
                <div class="col-md-6 specification-item mb-3" id="spec-${specCount}">
                    <div class="row">
                        <div class="col-md-5">
                            <x-form-input name="specifications[${specCount}][key]" type="text" label="Key" placeholder="Key" class="form-control" required="true" />
                        </div>
                        <div class="col-md-5">
                            <x-form-input name="specifications[${specCount}][value]" label="Value" placeholder="Value" class="form-control" required="true" />
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-danger delete-specification mt-4" data-spec-id="spec-${specCount}"><i class="fas fa-trash-alt"></i> </button>
                        </div>
                    </div>
                </div>
            `;
            $('#specification-container').append(newDiv);
        });

        // Delete Specification
        $(document).on('click', '.delete-specification', function() {
            const specId = $(this).data('spec-id');
            $(`#${specId}`).remove();
        });

        flatpickr('.sale-date-picker', {
            dateFormat: "d-m-Y",
            allowInput: true,
            minDate: "today"
        });

        $('#tags').select2({
            width: '100%'
        });

        function initialize() {
            const searchImageContent = `
                <div class="row">
                    <div class="col-md-2"></div>
                    <div class="col-md-8 d-flex gap-2">
                        <input type="text" id="google_search_keyword" class="form-control" placeholder="Enter search keyword">
                        <button type="button" class="btn btn-primary search-by-keyword">Search</button>
                    </div>
                </div>
                <div class="row image-container my-2"></div>
                <div class="text-center my-1">
                    <button id="loadMoreButton" type="button" class="btn btn-primary">Load More</button>
                </div>
            `;
            $('#googleImagesModal .modal-body').html(searchImageContent);

            // Set default search query
            const searchQuery = `Blue Shirt`;
            $('#google_search_keyword').val(searchQuery);

            // Search button click event
            $('.search-by-keyword').click(function() {
                const searchKeyword = $('#google_search_keyword').val();
                startIndex = 1;
                allItems = [];
                $('#googleImagesModal .image-container').html('');
                loadMoreImages(searchKeyword);
            });

            // Load more button click event
            $('#loadMoreButton').click(function() {
                const searchKeyword = $('#google_search_keyword').val();
                loadMoreImages(searchKeyword);
            });

            // Initial load with default search query
            loadMoreImages(searchQuery);
        }
        function loadMoreImages(searchKeyword) {
            fetchImages(startIndex, searchKeyword).done(function(response) {
                if (response.items && response.items.length > 0) {
                    $('#googleImagesModal .modal-body .alert').remove();

                    allItems = allItems.concat(response.items);
                    renderImages(response.items);
                    startIndex += resultsPerPage;

                    if (response.queries.nextPage) {
                        $('#loadMoreButton').show();
                    } else {
                        $('#loadMoreButton').hide();
                    }
                } else {
                    const alertHTML = `
                        <div class="alert alert-danger fade show mt-2" role="alert">
                            <strong>Oops!</strong> Search results not found.
                        </div>
                    `;
                    $('#googleImagesModal .modal-body').append(alertHTML);

                    $('#loadMoreButton').hide();
                }
            }).fail(function(apiErr) {
                let {
                    errors
                } = apiErr.responseJSON.error;

                errors.forEach(err => {
                    const alertHTML = `
                        <div class="alert alert-danger fade show mt-2" role="alert">
                            <strong>Error!</strong> ${err.message}
                        </div>
                    `;
                    $('#googleImagesModal .modal-body').append(alertHTML);
                });


                $('#loadMoreButton').hide();
            });
        }
                    // Google Custom Search API variables
        const searchEngineId = '{{ setting('google_search_engine_id') }}';
        const googleSearchApiKey = '{{ setting('google_search_api_key') }}';
        const resultsPerPage = 10;
        let startIndex = 1;
        let allItems = [];

        function fetchImages(startIndex, searchKeyword) {
            return $.ajax({
                url: 'https://www.googleapis.com/customsearch/v1',
                method: 'GET',
                data: {
                    q: searchKeyword,
                    cx: searchEngineId,
                    searchType: 'image',
                    key: googleSearchApiKey,
                    start: startIndex
                }
            });
        }
        function renderImages(items) {
            let newContent = '';
            $(items).each(function() {
                newContent += `
                    <div class="col-md-3">
                        <img src="${this.link}" alt="${this.title}" data-image-url="${this.link}" class="img-fluid" />
                    </div>
                `;
            });
            $('#googleImagesModal .image-container').append(newContent);
        }
        $('#googleImagesModal').on('click', 'img', function() {
            const imageUrl = $(this).data('image-url');

            fetch(imageUrl)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! Status: ${response.status}`);
                    }
                    return response.blob();
                })
                .then(blob => {
                    const mimeType = blob.type;

                    if (mimeType === "text/html") {
                        alert("This image type is not supported. Please choose another.");
                        return; 
                    }

                    fetchBinaryImage(imageUrl);

                    $('#googleImagesModal img').removeClass('selected-image');
                    $(this).addClass('selected-image');

                    $('#googleImagesModal').modal('hide');
                    $('#chooseColorImageModal').modal('hide');
                })
                .catch(error => {
                    console.error("Error fetching image:", error);
                });
        });
        function fetchBinaryImage(imageUrl) {
            fetch(imageUrl)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! Status: ${response.status}`);
                    }
                    return response.blob();
                })
                .then(blob => {
                    const mimeType = blob.type;

                    // Check if the response is an HTML error page
                    if (mimeType === "text/html") {
                        alert("This image type is not supported. Please choose another.");
                        throw new Error("Received an HTML response instead of an image.");
                    }

                    const fileExtension = mimeType.split('/')[1]; 
                    const fileName = `image.${fileExtension}`; 

                    const file = new File([blob], fileName, { type: mimeType });

                    const dataTransfer = new DataTransfer();
                    dataTransfer.items.add(file);

                    const fileInput = document.getElementById("productImages");

                    fileInput.files = dataTransfer.files;

                    fileInput.dispatchEvent(new Event("change", { bubbles: true }));
                    console.log("Binary image added to input and change event triggered.");
                })
                .catch(error => {
                    console.error("Error fetching image:", error);
                });
        }


    });
</script>
