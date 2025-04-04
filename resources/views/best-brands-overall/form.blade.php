<div class="row mb-3">
    <div class="col-md-4">
        <label for="brands" class="form-label">Brands</label>
        <select class="form-select" id="brands">
            <option selected>Select Brand</option>
            <option>Brand 1</option>
            <option>Brand 2</option>
        </select>
    </div>
    <div class="col-md-4">
        <label for="branches" class="form-label">Branches</label>
        <select class="form-select" id="branches">
            <option selected>Select Branch</option>
            <option>Branch 1</option>
            <option>Branch 2</option>
        </select>
    </div>
    <div class="col-md-4">
        <label for="departments" class="form-label">Departments</label>
        <select class="form-select" id="departments">
            <option selected>Select Department</option>
            <option>Department 1</option>
            <option>Department 2</option>
        </select>
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-4">
        <label for="productTypes" class="form-label">Product Types</label>
        <select class="form-select" id="productTypes">
            <option selected>Select Product Type</option>
            <option>Type 1</option>
            <option>Type 2</option>
        </select>
    </div>
    <div class="col-md-4">
        <label for="seasons" class="form-label">Seasons</label>
        <select class="form-select" id="seasons">
            <option selected>Select Season</option>
            <option>Summer</option>
            <option>Winter</option>
        </select>
    </div>
    <div class="col-md-4">
        <label for="category" class="form-label">Category</label>
        <select class="form-select" id="category">
            <option selected>Select Category</option>
            <option>Category 1</option>
            <option>Category 2</option>
        </select>
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-6">
        <label for="considerBy" class="form-label">Consider By</label>
        <select class="form-select" id="considerBy">
            <option selected>Select Consider By</option>
            <option>Option 1</option>
            <option>Option 2</option>
        </select>
    </div>
    <div class="col-md-6">
        <label for="thenBy" class="form-label">Then By</label>
        <select class="form-select" id="thenBy">
            <option selected>Select Then By</option>
            <option>Option 1</option>
            <option>Option 2</option>
        </select>
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-6">
        <label for="fromDate" class="form-label">From Date</label>
        <input type="date" class="form-control" id="fromDate">
    </div>
    <div class="col-md-6">
        <label for="toDate" class="form-label">To Date</label>
        <input type="date" class="form-control" id="toDate">
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-6">
        <label class="form-label">Sorting Sequence</label>
        <div>
            <input type="radio" class="btn-check" name="sequence" id="ascending" autocomplete="off">
            <label class="btn btn-outline-primary" for="ascending">Ascending</label>

            <input type="radio" class="btn-check" name="sequence" id="descending" autocomplete="off">
            <label class="btn btn-outline-primary" for="descending">Descending</label>
        </div>
    </div>

    <div class="col-md-6">
        <label class="form-label">Show Options</label>
        <div>
            <input type="radio" class="btn-check" name="showOptions" id="allLines" autocomplete="off">
            <label class="btn btn-outline-secondary" for="allLines">All Lines</label>

            <input type="radio" class="btn-check" name="showOptions" id="firstLines" autocomplete="off">
            <label class="btn btn-outline-secondary" for="firstLines">Only Show First</label>
            <input type="number" class="form-control d-inline w-auto ms-2" placeholder="Enter number">
        </div>
    </div>
</div>

<div class="text-end">
    <button type="submit" class="btn btn-success">Proceed</button>
    <button type="reset" class="btn btn-danger">Cancel</button>
</div>

<x-include-plugins :plugins="['select2']"></x-include-plugins>
<script>
    $(function(){
        $('.form-select').select2();
    })
</script>