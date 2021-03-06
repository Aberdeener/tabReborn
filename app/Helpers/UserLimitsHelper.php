<?php

namespace App\Helpers;

use App\Transaction;
use App\Product;
use App\UserLimits;
use Illuminate\Support\Carbon;
use stdClass;
use App\Http\Controllers\TransactionController;
use App\User;

class UserLimitsHelper
{

    // TODO clean
    public static function getInfo(?int $user_id, string $category): stdClass
    {
        $info = $user_id == null ? array() : UserLimits::where([['user_id', $user_id], ['category', $category]])->select('duration', 'limit_per')->get();
        if (count($info)) {
            $info = $info[0];
        }
        
        $return = new stdClass;
        if (isset($info->duration)) {
            $return->duration = $info->duration == 0 ? 'day' : 'week';
        } else {
            $return->duration = 'week';
        }
        if (isset($info->limit_per)) {
            $return->limit_per = $info->limit_per;
        } else {
            // Usually this will happen when we make a new category after a user was made
            $return->limit_per = -1;
        }

        return $return;
    }

    public static function findSpent(User $user, string $category, object $info): float
    {
        // First, if they have unlimited money for this category, let's grab all their transactions
        if ($info->limit_per == -1) {
            $transactions = $user->getTransactions();
        } else {
            // Determine how far back to grab transactions from
            // TODO: dont waste a query here
            $transactions = Transaction::where([['created_at', '>=', Carbon::now()->subDays($info->duration == 'day' ? 1 : 7)->toDateTimeString()], ['purchaser_id', $user->id]])->get();
        }

        $category_spent = 0.00;

        // Loop applicable transactions, then do a bunch of wacky shit
        foreach ($transactions as $transaction) {
            if ($transaction->status) {
                continue;
            }
            // Loop transaction products. Determine if the product's category is the one we are looking at,
            // if so, add its ((value * (quantity - returned)) * tax) to the end result
            $transaction_products = explode(", ", $transaction['products']);
            foreach ($transaction_products as $transaction_product) {
                if (strtolower($category) == Product::find(strtok($transaction_product, "*"))->category) {
                    $item_info = TransactionController::deserializeProduct($transaction_product, false);
                    $tax_percent = $item_info['gst'];
                    if ($item_info['pst'] != "null") {
                        $tax_percent += $item_info['pst'] - 1;
                    }
                    $quantity_available = $item_info['quantity'] - $item_info['returned'];
                    $category_spent += ($item_info['price'] * $quantity_available) * $tax_percent;
                }
            }
        }

        return $category_spent;
    }
}
