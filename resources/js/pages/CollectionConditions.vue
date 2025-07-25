<template>
    <div class="card">
        <div class="card-body">
            <h4 class="card-title">Basic Information</h4>
            <div class="row">
                <div class="col-sm-6 col-md-4">
                    <div class="form-group">
                        <label for="collection_name">Collection Name<span class="text-danger">*</span></label>
                        <input class="form-control" v-model="collection.name" name="collection_name"
                            placeholder="Enter Collection Name" v-bind:class="{ 'is-invalid': errors.name }" />

                        <span v-if="errors.name" class="invalid-feedback">{{ errors.name }}</span>
                    </div>
                </div>
                <div class="col-sm-6 col-md-4">
                    <label for="collection-status" class="form-label">Status</label>
                    <select name="status" id="collection-status" class="form-control" v-model="savedCollection.status">
                        <option value="1" v-bind:value="1">Active</option>
                        <option value="0" v-bind:value="0">Inactive</option>
                    </select>
                </div>
                <div class="col-sm-6 col-md-4">
                    <div class="form-group">
                        <label for="collection-status" class="form-label">Published Timestamp</label>
                        <input type="text" class="form-control date-picker-publish">
                    </div>
                </div>
                <div class="col-sm-6 col-md-6">
                    <label for="status">Image</label>
                    <input type="file" name="image" class="form-control" accept="image/*" @change="previewImage">

                    <div class="row d-block" v-if="imagePreviewUrl">
                        <div class="col-md-8 mt-2">
                            <img :src="imagePreviewUrl" id="preview-collection" class="img-fluid w-50" alt="Preview">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h4 class="card-title">Conditions</h4>
            <div class="col-md-12 mb-3">
                <div class="row">
                    <div class="col-md-6 d-flex justify-content-between">
                        <h6>Include Conditions</h6>
                        <button type="button" class="btn btn-sm btn-secondary" @click="addNewCondition('include')">Add
                            new
                            condition</button>
                    </div>
                </div>

                <AddedConditions :conditionType="'include'" :conditions="conditions.include"
                    @removeCondition="removeCondition"></AddedConditions>
            </div>

            <div class="col-md-12">
                <div class="row">
                    <div class="col-md-6 d-flex justify-content-between">
                        <h6>Exclude Conditions</h6>
                        <button type="button" class="btn btn-sm btn-secondary" @click="addNewCondition('exclude')">Add
                            new
                            condition</button>
                    </div>
                </div>
                <AddedConditions :conditionType="'exclude'" :conditions="conditions.exclude"
                    @removeCondition="removeCondition"></AddedConditions>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h4 class="card-title">Listing Page Content</h4>
            <div>
                <h4 class="card-title">Summary</h4>
                <textarea name="summary" id="summary" class="editor" rows="2">{{ savedCollection.summary }}</textarea>
            </div>
            <div class="mt-3">
                <h4 class="card-title">Description</h4>
                <textarea name="description" id="description" class="editor"
                    rows="2">{{ savedCollection.description }}</textarea>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h4 class="card-title">Listing Page Options</h4>

            <div class="d-flex mt-3" style="gap:5px">
                <input type="checkbox" name="hide_category" v-model="listingOptions.hide_categories" :true-value="1" :false-value="0"> 
                <label>Hide Categories</label>
            </div>

            <!-- All radio buttons use the same name -->
            <div class="d-flex mt-2" style="gap:5px">
                <input type="radio" name="filter_option" value="show" id="show_all" v-model="filterOption">
                Show all filters
            </div>
            <div class="d-flex mt-2" style="gap:5px">
                <input type="radio" name="filter_option" value="hide" id="hide_all" v-model="filterOption">
                Hide all filters
            </div>
            <div class="d-flex mt-2" style="gap:5px">
                <input type="radio" name="filter_option" value="particular_filter" id="show_filters" v-model="filterOption">
                Only show some filters
            </div>

            <!-- Hidden by default -->
            <div class="filter-section w-50" id="filter-section" v-show="filterOption === 'particular_filter'">
                <select class="form-control w-50" name="filters[]" id="filters" multiple>
                    <option value="departments">Departments</option>
                    <option value="product_types">Product Types</option>
                    <option value="size">Size</option>
                    <option value="brand">Brand</option>
                    <option value="color">Color</option>
                    <option value="tag">Tag</option>
                </select>

<div id="filter-container" class="mt-1">
    <ul class="list-reset flex flex-col leading-normal mt-2" style="list-style: none;">
        <li v-for="(isCollapsed, filterKey) in collapsedFilters" :key="filterKey" class="collaps-list mt-2">
            <div class="flex flex-row items-center justify-between">
                {{ formatFilterName(filterKey) }}
            </div>
            <div class="checkbox d-flex" style="gap: 5px;">
                <input 
                    type="checkbox" 
                    :name="'collaps[' + filterKey + ']'"
                    v-model="collapsedFilters[filterKey]"
                >
                Collapse
            </div>
        </li>
    </ul>
</div>

            </div>

            <div class="mt-3">
                <div class="form-group">
                    <h6>Per Page (Optional)</h6>
                    <input type="number" class="form-control w-50" name="per_page" v-model="savedCollection.listing_option.show_per_page">
                </div>
            </div>

            <div class="mt-3">
                <div class="form-group">
                    <h6>Sort By</h6>
                    <select class="form-control w-50" name="sort_by[]" id="sort_by" multiple>
                        <option value="name_asc">Name A-Z</option>
                        <option value="name_desc">Name Z-A</option>
                        <option value="model_asc">Model A-Z</option>
                        <option value="model_desc">Model Z-A</option>
                        <option value="manufacturer_asc">Manufacturer A-Z</option>
                        <option value="manufacturer_desc">Manufacturer Z-A</option>
                        <option value="price_asc">Price Low</option>
                        <option value="price_desc">Price High</option>
                        <option value="quantity_asc">Stock Low</option>
                        <option value="quantity_desc">Stock High</option>
                        <option value="has_stock">Has Stock</option>
                        <option value="date_added_desc">Newest</option>
                        <option value="date_added_asc">Oldest</option>
                        <option value="reduction_desc">Reduced First</option>
                        <option value="reduction_asc">Reduced Last</option>
                        <option value="saving_percent_asc">Saving Percentage Low</option>
                        <option value="saving_percent_desc">Saving Percentage High</option>
                        <option value="saving_price_asc">Saving Price Low</option>
                        <option value="saving_price_desc">Saving Price High</option>
                        <option value="random">Random</option>
                        <option value="merchandised">Merchandised</option>
                        <option value="id_asc">Id Asc</option>
                        <option value="id_desc">Id Desc</option>
                        <option value="best_selling_asc">Best Selling All Asc</option>
                        <option value="best_selling_desc">Best Selling All Desc</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h4 class="card-title">SEO</h4>
            <div class="row">
                <div class="col-12 mb-3">
                    <div class="row">
                        <div class="col-sm-10">
                            <label for="heading">Heading</label>
                            <input name="heading" id="heading" class="form-control" :value="savedCollection.heading"
                                placeholder="Meta title" />
                        </div>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="mb-3">
                        <label for="meta_title">Meta title</label>
                        <input name="meta_title" id="meta_title" class="form-control"
                            :value="savedCollection.meta_title" placeholder="Meta title" />
                    </div>
                    <div class="mb-3">
                        <label for="meta_keywords">Meta Keywords</label>
                        <input name="meta_keywords" id="meta_keywords" class="form-control"
                            :value="savedCollection.meta_keywords" placeholder="Meta Keywords" />
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="mb-3">
                        <label for="meta_description">Meta Description</label>
                        <textarea name="meta_description" class="form-control" id="meta_description" rows="5"
                            placeholder="Meta Description">{{ savedCollection.meta_description }}</textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-2">
        <div class="col-lg-6 mb-2">
            <button type="submit" class="btn btn-primary w-md" :disabled="!collection.name">Submit</button>
        </div>
    </div>

    <AddConditionModal :conditionType="conditionType" :addedConditions="conditions[conditionType]"
        @addCondition="addCondition" :conditionMap="conditionMap"></AddConditionModal>
</template>

<script>
import axios from 'axios';
import AddConditionModal from '../components/collections/AddConditionModal.vue';
import AddedConditions from '../components/collections/AddedConditions.vue';

let cancelTokenSource = null;

export default {
    components: {
        AddConditionModal,
        AddedConditions
    },
    props: {
        conditionDependencies: {
            type: Object,
            requied: true
        },
        savedCollection: {
            type: Object,
            default: () => ({
                listing_option: {  // Initialize nested object here
                    hide_categories: 0,
                    hide_filters: 0,
                    show_all_filters: 0,
                    visible_filters: "[]",
                    collapsed_filters: "{}",
                    show_per_page: 10,
                    sort_options: "[]",
                    show_all_products: 0
                }
            })
        }
    },
    data() {
        return {
            conditionType: 'include',
            conditions: {
                'include': [],
                'exclude': []
            },
            collection: {
                name: this.savedCollection.name || ''
            },
            errors: {
                name: ''
            },
            imagePreviewUrl: this.savedCollection.image
                ? `${window.location.origin}/${this.savedCollection.image}`
                : '',
            filterOption: 'show', // 'show', 'hide', or 'particular_filter'
            conditionMap: {
                tags: "Have one of these tags",
                product_types: "Any of these product types",
                price_list: "Be in the price list",
                price_range: "Be in the price range",
                price_status: "Have the price status",
                published_within: "Have been published within"
            },
            conditionMap: {
                tags: "Have one of these tags",
                product_types: "Any of these product types",
                price_list: "Be in the price list",
                price_range: "Be in the price range",
                price_status: "Have the price status",
                published_within: "Have been published within"
            },
            listingOptions: {
                hide_categories: 0,
                hide_filters: 0,
                show_all_filters: 0,
                visible_filters: "[]",
                collapsed_filters: "{}",
                show_per_page: 10,
                sort_options: "[]",
                show_all_products: 0
            }
        };
    },
    created() {
        this.initializeListingOptions();
        if (this.savedCollection.listing_option) {
            this.listingOptions = {
                ...this.listingOptions,
                ...this.savedCollection.listing_option
            };
        }
    },
    mounted() {
        if (this.savedCollection) {
            let includeConditions = this.savedCollection.include_conditions;
            if (includeConditions) {
                includeConditions = JSON.parse(includeConditions);

                for (let [key, value] of Object.entries(includeConditions || {})) {
                    const conditionLabel = this.conditionMap[key];

                    if (conditionLabel) {
                        const conditionObject = {
                            name: key,
                            label: conditionLabel,
                            defaulValue: value
                        };

                        this.addCondition(conditionObject);
                    }
                }
            }

            let excludeConditions = this.savedCollection.exclude_conditions;
            if (excludeConditions) {
                excludeConditions = JSON.parse(excludeConditions);

                for (let [key, value] of Object.entries(excludeConditions || {})) {
                    const conditionLabel = this.conditionMap[key];
                    const conditionObject = {
                        name: key,
                        label: conditionLabel,
                        defaulValue: value
                    };

                    this.addCondition(conditionObject, 'exclude');
                }
            }
        }
        if (this.savedCollection.listing_option) {
            if (this.savedCollection.listing_option.show_all_filters) {
                this.filterOption = 'show';
            } else if (this.savedCollection.listing_option.hide_filters) {
                this.filterOption = 'hide';
            } else {
                this.filterOption = 'particular_filter';
                this.$nextTick(() => {
                    $('#filter-section').show();
                });
            }
        }


        $('[name="image"]').change(function (event) {
            var reader = new FileReader();
            reader.onload = function (e) {
                $('#preview-collection').attr('src', e.target.result).removeAttr('hidden');
            }
            reader.readAsDataURL(this.files[0]);
        });
        $(document).ready(function() {
            $('#sort_by,#filters').select2();
            
            // Update Vue model when Select2 changes
            $('#filters').on('change', (e) => {
                const values = $(e.target).val() || [];
                this.savedCollection.listing_option.visible_filters = JSON.stringify(values);
            });
            
            $('#sort_by').on('change', (e) => {
                const values = $(e.target).val() || [];
                this.savedCollection.listing_option.sort_options = JSON.stringify(values);
            });
            
            // Set initial values if they exist
            try {
                const visibleFilters = JSON.parse(this.savedCollection.listing_option.visible_filters || "[]");
                if (visibleFilters.length) {
                    $('#filters').val(visibleFilters).trigger('change');
                }
                
                const sortOptions = JSON.parse(this.savedCollection.listing_option.sort_options || "[]");
                if (sortOptions.length) {
                    $('#sort_by').val(sortOptions).trigger('change');
                }
            } catch (e) {
                console.error("Error parsing saved values:", e);
            }
        }.bind(this));

        // Original filter container setup
        $('[name="filter_option"]').change(function (event){
            var option = $(this).val();
            if(option == 'particular_filter'){
                $('#filter-section').show();
            }else{
                $('#filter-section').hide();
            }
        });

        $('#filters').change(function (event) {
            var selectedOpts = $(this).val(); // value like departs, brand, size
            console.log(selectedOpts);
            var appendCollaps = `<ul class="list-reset flex flex-col leading-normal mt-2" style="list-style:none;">`;

            selectedOpts.forEach(function (value) {
                // Replace underscores with spaces and capitalize the first letter of each word
                let formattedValue = value.replace(/_/g, ' ').replace(/\b\w/g, function (char) {
                    return char.toUpperCase();
                });

                appendCollaps += `
                    <li class="collaps-list mt-2">
                        <div class="flex flex-row items-center justify-between">
                            ${formattedValue}
                        </div>
                        <div class="checkbox d-flex" style="gap:5px;">
                            <input type="checkbox" name="collaps[${value}]"> 
                            Collapse
                        </div> 
                    </li>
                `;
            });
            appendCollaps += `</ul>`;

            // Append the generated list to a DOM element (e.g., filter-container)
            $('#filter-container').html(appendCollaps); // Assuming there's a container with id 'filter-container'
        });
        console.log("Mounted Collapsed Filters:", this.collapsedFilters);
        this.collapsedFilters = { ...this.listingOptions.collapsed_filters };
    },
    methods: {
        initializeSelect2() {
            this.$nextTick(() => {
                $('#sort_by, #filters').select2({
                    width: '100%',
                    placeholder: 'Select options',
                    closeOnSelect: false
                }).on('change', (e) => {
                    // Update Vue model when Select2 selection changes
                    const target = e.target;
                    const values = $(target).val() || [];
                    if (target.id === 'filters') {
                        this.listingOptions.visible_filters = values;
                    } else if (target.id === 'sort_by') {
                        this.listingOptions.sort_options = values;
                    }
                });

                // Initialize with current values
                if (this.listingOptions.visible_filters.length) {
                    $('#filters').val(this.listingOptions.visible_filters).trigger('change');
                }
                if (this.listingOptions.sort_options.length) {
                    $('#sort_by').val(this.listingOptions.sort_options).trigger('change');
                }
            });
        },
        initializeListingOptions() {
            if (this.savedCollection.listing_option) {
                const options = this.savedCollection.listing_option;
                
                // Set basic options
                this.listingOptions.hide_categories = options.hide_categories || 0;
                this.listingOptions.show_per_page = options.show_per_page || 10;
                this.listingOptions.show_all_products = options.show_all_products || 0;
                
                // Determine filter option
                if (options.show_all_filters) {
                    this.listingOptions.filter_option = 'show_all';
                } else if (options.hide_filters) {
                    this.listingOptions.filter_option = 'hide_all';
                } else {
                    this.listingOptions.filter_option = 'show_some';
                }
                
                // Parse JSON fields
                try {
                    this.listingOptions.visible_filters = options.visible_filters 
                        ? JSON.parse(options.visible_filters) 
                        : [];
                } catch (e) {
                    this.listingOptions.visible_filters = [];
                }
                
                try {
                    this.listingOptions.sort_options = options.sort_options 
                        ? JSON.parse(options.sort_options) 
                        : [];
                } catch (e) {
                    this.listingOptions.sort_options = [];
                }
                
                try {
                    const collapsed = options.collapsed_filters ? JSON.parse(options.collapsed_filters) : {};
                    const converted = {};
                    for (const key in collapsed) {
                        converted[key] = collapsed[key] === 'on';  // Ensure values are true/false (not strings)
                    }

                    this.listingOptions.collapsed_filters = converted;
                    this.collapsedFilters = { ...converted };  // Make sure this is reactive
                } catch (e) {
                    this.listingOptions.collapsed_filters = {};
                    this.collapsedFilters = {};
                }
            }
        },
        formatFilterName(filterKey) {
            if (typeof filterKey !== 'string') return '';
            return filterKey.replace(/_/g, ' ').replace(/\b\w/g, char => char.toUpperCase());
        },
        addNewCondition(conditionType) {
            this.conditionType = conditionType;
            $('#addConditionModal').modal('show');
        },
        previewImage(event) {
            const file = event.target.files[0];
            if (file) {
                this.imagePreviewUrl = URL.createObjectURL(file);
            }
        },
        removeCondition(payload) {
            let selectedCondition = this.conditions[payload.conditionType][payload.conditionIndex];
            if (selectedCondition.type === 'select' && selectedCondition.multiple) {
                $(`#${payload.conditionType}_${selectedCondition.name}`).chosen('destroy');

                setTimeout(() => {
                    $('.multiSelect').each(function () {
                        let defaultPlaceholder = $(this).find('option').first().html();

                        $(this).chosen({
                            width: '100%',
                            placeholder_text_multiple: defaultPlaceholder,
                        });
                    });

                }, 100);
            }

            this.conditions[payload.conditionType].splice(payload.conditionIndex, 1);
        },
        addCondition(condition) {
            switch (condition.name) {
                case 'tags':
                    condition.type = 'select';
                    condition.multiple = true;
                    condition.values = this.conditionDependencies.tags;
                    break;
                case 'product_types':
                    condition.type = 'select';
                    condition.multiple = true;
                    condition.values = this.conditionDependencies.ProductTypes;
                    break;
                case 'price_list':
                    condition.type = 'number';
                    break;
                case 'price_range':
                    condition.type = 'range';
                    condition.values = { min: 0, max: this.conditionDependencies.maxProductPrice };
                    break;
                case 'published_within':
                    condition.type = 'select';

                    condition.values = [{
                        id: 'Days',
                        value: 'Days',
                    }, {
                        id: 'Range',
                        value: 'Range',
                    }];

                    condition.subFields = [{
                        type: 'number',
                        name: 'published_within_number_of_days',
                        label: 'Number Of Days',
                        value: 30,
                        basedOn: 'Days'
                    }, {
                        type: 'date',
                        name: 'published_between_dates',
                        label: 'Published Between',
                        multiple: true,
                        basedOn: 'Range'
                    }];

                    let savedData = this.savedCollection[`${this.conditionType}_conditions`];
                    if (savedData) {
                        savedData = JSON.parse(savedData);

                        let subfieldKey = 'published_between_dates';
                        if (savedData?.published_within_number_of_days) {
                            subfieldKey = 'published_within_number_of_days';
                        }

                        if (savedData) {
                            const specificSubField = condition.subFields.find(subField => subField.name === subfieldKey);
                            specificSubField.value = savedData[subfieldKey];
                        }
                    }

                    break;
                case 'price_status':
                    condition.type = 'select';
                    condition.values = [{
                        id: 'Reduce Item Only',
                        value: 'Reduce Item Only'
                    }, {
                        id: 'Full Price Item Only',
                        value: 'Full Price Item Only'
                    }];
                    break;
                default:
                    condition.type = 'text';
                    break;
            }

            this.conditions[this.conditionType].push(condition);

            setTimeout(function () {
                if (condition.type === 'select' && condition.multiple) {
                    $('.multiSelect').each(function () {
                        let defaultPlaceholder = $(this).find('option').first().html();

                        $(this).chosen({
                            width: '100%',
                            placeholder_text_multiple: defaultPlaceholder,
                        });
                    });
                }
            }, 10);
        }
    },
    computed: {
        collapsedFilters() {
            return this.listingOptions.collapsed_filters;
        }
    },
    watch: {
        'collection.name': function (name) {
            // If there's a pending request, cancel it
            if (cancelTokenSource) {
                cancelTokenSource.cancel('Request canceled due to changing the name');
            }

            // Create a new CancelToken for this request
            cancelTokenSource = axios.CancelToken.source();

            let paylad = {
                name,
                id: this.savedCollection.id || null
            }

            axios.post('/api/collections/check-name', paylad, {
                cancelToken: cancelTokenSource.token
            })
                .then((response) => {
                    this.errors.name = '';
                })
                .catch((error) => {
                    if (axios.isCancel(error)) {
                        console.log('Request canceled:', error.message);
                    } else if (error.response && error.response.status === 400) {
                        this.errors.name = error.response.data.message;
                    } else {
                        console.error('There was an error!', error);
                    }
                });
        },
        'listingOptions.filter_option': function(newVal) {
            if (newVal === 'show_all') {
                this.listingOptions.show_all_filters = 1;
                this.listingOptions.hide_filters = 0;
            } else if (newVal === 'hide_all') {
                this.listingOptions.show_all_filters = 0;
                this.listingOptions.hide_filters = 1;
            } else {
                this.listingOptions.show_all_filters = 0;
                this.listingOptions.hide_filters = 0;
            }
        },
          'listingOptions.collapsed_filters': function(newValue) {
            // Handle the updated collapsed_filters here (e.g., make an API call to save it)
            console.log('Updated collapsed filters:', newValue);
        }
    }
};
</script>