<?php

namespace App\Http\Controllers;

use App\User;
use App\Products;
use App\Transactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CashierController extends Controller
{
    public function submit(Request $request)
    {
        if (isset($request->product)) {
            $products = array();
            $total_price = 0;
            foreach ($request->product as $product) {
                array_push($products, $product);
                $product_price = Products::select('price')->where('id', '=', $product)->get();
                $total_price += $product_price['0']['price'];
            }
            $purchaser_info = User::select('full_name', 'balance')->where('id', '=', $request->purchaser_id)->get();
            $remaining_balance = $purchaser_info['0']['balance'] - $total_price;
            if ($remaining_balance < 0) {
                return redirect()->back()->withInput()->with('error', 'Not enough balance. ' . $purchaser_info['0']['full_name'] . " only has $" . $purchaser_info['0']['balance']);
            } else {
                DB::table('users')
                    ->where('id', $request->purchaser_id)
                    ->update(['balance' => $remaining_balance]);
            }
            $transaction = new Transactions();
            $transaction->purchaser_id = $request->purchaser_id;
            $transaction->cashier_id = $request->cashier_id;
            $transaction->products = implode(", ", $products);
            $transaction->total_price = $total_price;
            $transaction->save();

            return redirect('/')->with('success', 'Order #' . $transaction->id . '. ' . $purchaser_info['0']['full_name'] . " now has $" . $remaining_balance);
        } else {
            return redirect()->back()->withInput()->with('error', 'Please select at least one item.');
        }
    }
}
