@extends('layouts.default')
@section('content')
@php
use App\Products;
use App\Http\Controllers\UserLimitsController;
use App\Http\Controllers\SettingsController;
@endphp

<h2><strong>Stock Adjustment</strong></h2>
<div id="loading" align="center">
    <img src="{{ url('loader.gif') }}" alt="Loading..." class="loading-spinner" />
</div>
<br>
<div class="row">
    <div class="col-md-1">
        <select id="category_select">
            <option value="">Choose a Category</option>
            @foreach(SettingsController::getCategories() as $category)
            <option value="{{ $category->value }}">{{ ucfirst($category->value) }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-7" id="product_container" style="visibility: hidden;">
        <table id="product_list">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Stock</th>
                    <th>Stock Override</th>
                    <th>Box Size</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach(Products::where('deleted', false)->get() as $product)
                <tr>
                    <td class="table-text">
                        <div>{{ $product->name }}</div>
                    </td>
                    <td class="table-text">
                        <div>{{ ucfirst($product->category) }}</div>
                    </td>
                    <td class="table-text">
                        <div>{!! Products::getStock($product->id) !!}</div>
                    </td>
                    <td class="table-text">
                        <div>{{ $product->stock_override ? 'True' : 'False' }}</div>
                    </td>
                    <td class="table-text">
                        <div>{!! $product->box_size == -1 ? '<i>N/A</i>' : $product->box_size !!}</div>
                    </td>
                    <td class="table-text">
                        <div><button class="btn btn-info" id="adjust_select" value="{{ $product->id }}">Adjust</button>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="col-md-4">
        @include('includes.messages')
        <div id="adjust_product">
            @if(session()->has('last_product'))
            @include('pages.products.adjust.form', ['product' => session('last_product')])
            @endif
        </div>
    </div>
</div>
<script type="text/javascript">
    let table = null;

    $(document).ready(function() {
        table = $('#product_list').DataTable({
            "paging": false,
            "scrollY": "24vw",
            "scrollCollapse": true,
            "columnDefs": [
                { 
                    "orderable": false, 
                    "targets": 5
                }
            ]
        });
        $('#loading').hide();
        $('#product_container').css('visibility', 'visible');
    });

    $('#category_select').on('change',function(){
        table.search($(this).val()).draw();
    });

   $(document).on("click", "#adjust_select", function() {
        $.ajax({
            type : "POST",
            url : "{{ route('adjust_ajax') }}",
            data: {
                "_token": "{{ csrf_token() }}",
                "id": $(this).attr("value")
            },
            beforeSend : function() {
                $('#adjust_product').show().html("<center><img src='{{ url('loader.gif') }}' class='loading-spinner'></img></center>");
            },
            success : function(response) {
                $('#adjust_product').html(response);
            },
            error: function(xhr, status, error) {
                $('#adjust_product').show().html("<p style='color: red;'><b>ERROR: </b><br>" + xhr.responseText + "</p>");
            }
        });
    });
</script>
@stop