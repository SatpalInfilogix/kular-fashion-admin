<template>
    <table id="product-table" data-selected-articles=""
        class="table table-striped table-bordered dt-responsive nowrap w-100">
        <thead>
            <tr>
                <th class="p-1">
                    <div class="form-check form-check-primary mb-3">
                        <input class="form-check-input" type="checkbox" id="select-all">
                    </div>
                </th>
                <th class="p-1">Article Code</th>
                <th class="p-1">Description</th>
                <th class="p-1">Product Type</th>
                <th class="p-1">Brand</th>
                <th class="p-1">Quantity</th>
                <th class="p-1">Last In</th>
                <th class="p-1">Price</th>
            </tr>
        </thead>
    </table>
</template>


<script>
export default {
    mounted() {
        const table = $('#product-table').DataTable({
            processing: true,
            lengthMenu: [ 
                [10, 25, 50, 100, 250, 350, 500, -1], 
                [10, 25, 50, 100, 250, 350, 500] 
            ],
            serverSide: true,
            ajax: {
                url: '/get-products',
                data: function (d) {
                    d.page = Math.floor(d.start / d.length) + 1;
                    d.new_products_only = true
                }
            },
            columns: [
                {
                    title: `<div class="form-check form-check-primary">
                            <input class="form-check-input" type="checkbox" id="select-all" checked>
                        </div>`,
                    orderable: false,
                    render: function (data, type, row) {
                        let selectedArticles = $('#product-table').attr('data-selected-articles').split(',');
                        let checked = selectedArticles.includes(String(row.id)) ? 'checked' : '';

                        if (!checked && !row.barcodes_printed_for_all) {
                            checked = 'checked';
                        }

                        return `<div class="form-check form-check-primary">
                            <input class="form-check-input select-row" type="checkbox" value="${row.id}" ${checked}>
                        </div>`
                    }
                },
                { title: "Article Code", data: 'article_code' },
                { title: "Description", data: 'short_description' },
                { title: "Brand", data: 'brand.name' },
                { title: "Product Type", data: 'product_type.name' },
                { title: "Price", data: 'mrp' },
                {
                    title: "Quantity", render: function (data, type, row) {
                        return row.quantities.reduce((acc, item) => acc + (item.total_quantity - item.original_printed_barcodes), 0);
                    }
                },
                {
                    title: "Last In", render: function (data, type, row) {
                        // Format date using moment.js
                        if (row.updated_at) {
                            return moment(row.updated_at).format('DD-MM-YYYY');
                        }
                        return '-';
                    }
                },
            ],
            order: [[7, 'desc']],
            drawCallback: function (settings) {
                $('#product-table tbody tr').each(function () {
                    $(this).children('td').addClass('p-1');
                });

                // Call expandRow for each row after table is drawn
                /* table.rows().every(function () {
                    const rowData = this.data();
                    const row = this.node();
                    expandRow($(row), rowData, false);
                }); */
            }
        });

        // Handle row expansion on clicking any td (except the checkbox column)
        $('#product-table').on('change', '.select-row', (e) => {
            const checkbox = $(e.target);
            const row = checkbox.closest('tr');
            const rowData = table.row(row).data();
            const expandedRow = row.next('.expanded-row');

            if (checkbox.prop('checked')) {
                expandRow(row, rowData);
            } else {
                expandedRow.remove();
            }
        });

        $('table').on('change', '#select-all', (event) => {
            var checkboxes = $('#product-table .select-row');

            if (event.target.checked) {
                checkboxes.each((_, checkbox) => {
                    const row = $(checkbox).closest('tr');
                    const rowData = $(checkbox).closest('table').DataTable().row(row).data();
                    expandRow(row, rowData);
                });
            } else {
                $('#product-table .expanded-row').remove();
            }
        });

        $('#product-table').on('click', 'tbody tr', (e) => {
            if (e.target.tagName !== 'TD') return;
            const row = $(e.currentTarget);
            const rowData = table.row(row).data();
            const nextRow = row.next('.expanded-row');

            const checkbox = row.find('.select-row');
            checkbox.prop('checked', 'checked');

            if (nextRow.length) {
                nextRow.remove();
            } else {
                expandRow(row, rowData);
            }
        });
    },
};
</script>