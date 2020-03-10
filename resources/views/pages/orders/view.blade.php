<?php

use App\Http\Controllers\OrderController;
use App\Products;
use App\Transactions;

$transaction = Transactions::where('id', '=', request()->route('id'))->get();
$transaction_items = explode(", ", $transaction['0']['products']);
?>
@extends('layouts.default')
@section('content')
<h2>View Order</h2>
<div class="row">
    <div class="col-md-7">
        <br>
        <h4>Order ID: {{request()->route('id') }}</h4>
        <h4>Time: {{ $transaction['0']['created_at']->format('M jS Y h:ia') }}</h4>
        <h4>Purchaser: <a
                href="/users/info/{{ $transaction['0']['purchaser_id'] }}">{{ DB::table('users')->where('id', $transaction['0']['purchaser_id'])->pluck('full_name')->first() }}</a>
        </h4>
        <h4>Cashier: <a
                href="/users/info/{{ $transaction['0']['cashier_id'] }}">{{ DB::table('users')->where('id', $transaction['0']['cashier_id'])->pluck('full_name')->first() }}</a>
        </h4>
        <h4>Total Price: ${{ number_format($transaction['0']['total_price'], 2) }}</h4>
        <h4>Status: {{ $transaction['0']['status'] == 0 ? "Normal" : "Returned" }}</h4>
        @if($transaction['0']['status'] == 0)
        <form>
            <input type="hidden" id="transaction_id" value="{{ $transaction['0']['id'] }}">
            <a href="javascript:;" data-toggle="modal" onclick="returnData()" data-target="#returnModal"
                class="btn btn-xs btn-danger">Return</a>
        </form>
        @endif
    </div>
    <div class="col-md-5">
        <h2 align="center">Items</h2>
        <table id="product_list">
            <thead>
                <th>Name</th>
                <th>Price</th>
                <th>Quantity</th>
                <th>Item Price</th>
                <th></th>
            </thead>
            <tbody>
                @foreach($transaction_items as $product)
                <?php
                $item_info = OrderController::deserializeProduct($product);
                ?>
                <tr>
                    <td class="table-text">
                        <div>{{ $item_info['name'] }}</div>
                    </td>
                    <td class="table-text">
                        <div>${{ number_format($item_info['price'], 2) }}</div>
                    </td>
                    <td class="table-text">
                        <div>{{ $item_info['quantity'] }}</div>
                    </td>
                    <td class="table-text">
                        <div>${{ number_format($item_info['price'] * $item_info['quantity'], 2) }}</div>
                    </td>
                    <td class="table-text">
                        <div><a href="">Return</a></div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
<div id="returnModal" class="modal fade" role="dialog">
    <div class="modal-dialog ">
        <form action="" id="returnForm" method="get">
            <div class="modal-content">
                <div class="modal-body">
                    {{ csrf_field() }}
                    <p class="text-center">Are you sure you want to return this transaction?</p>
                </div>
                <div class="modal-footer">
                    <center>
                        <button type="button" class="btn btn-success" data-dismiss="modal">Cancel</button>
                        <button type="submit" name="" class="btn btn-danger" data-dismiss="modal"
                            onclick="formSubmit()">Return</button>
                    </center>
                </div>
            </div>
        </form>
    </div>
</div>
<script type="text/javascript">
    $(document).ready(function() {
        var table = $('#product_list').DataTable({
            paging: false,
            // we want the scroll to be as big as possible without making the page scroll
            "scrollY": "300px",
            "scrollCollapse": true,
        });
    });

    function returnData() {
        var id = document.getElementById('transaction_id').value;
        console.log(id);
        var url = '{{ route("return_order", ":id") }}';
        url = url.replace(':id', id);
        $("#returnForm").attr('action', url);
    }

    function formSubmit() {
        $("#returnForm").submit();
    }
</script>
@endsection