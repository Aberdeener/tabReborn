<?php

use App\Transactions;
?>
@extends('layouts.default')
@section('content')
<h2>Order List</h2>
<table id="order_list">
    <thead>
        <th>Time</th>
        <th>Purchaser</th>
        <th>Cashier</th>
        <th>Total Price</th>
        <th></th>
    </thead>
    <tbody>
        @foreach (Transactions::all() as $transaction)
        <tr>
            <td class="table-text">
                <div>{{ $transaction->created_at }}</div>
            </td>
            <td class="table-text">
                <div> {{ DB::table('users')->where('id', $transaction->purchaser_id)->pluck('full_name')->first() }}</div>
            </td>
            <td class="table-text">
                <div> {{ DB::table('users')->where('id', $transaction->cashier_id)->pluck('full_name')->first() }}</div>
            </td>
            <td class="table-text">
                <div>${{ $transaction->total_price }}</div>
            </td>
            <td>
                <div><a href="orders/view/{{ $transaction->id }}">View</a></div>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
<script>
    $(document).ready(function() {
        $('#order_list').DataTable();
    });
    $('#order_list').DataTable({
        "order": [
            [0, "desc"]
        ],
        paging: false,
        "scrollY": "250px",
        "scrollCollapse": true,
    });
</script>
@endsection