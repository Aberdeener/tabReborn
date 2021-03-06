<?php

namespace App;

use App\Http\Controllers\TransactionController;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Collection;
use Rennokki\QueryCache\Traits\QueryCacheable;
use Illuminate\Support\Facades\DB;

class User extends Authenticatable
{
    
    use QueryCacheable;

    protected $cacheFor = 180;

    protected $fillable = [
        'balance',
        'deleted'
    ];

    protected $casts = [
        'name' => 'string',
        'username' => 'string',
        'balance' => 'float',
        'deleted' => 'boolean'
    ];
    
    public function role() 
    {
        return $this->hasOne(Role::class, 'id', 'role_id');
    }

    private ?Collection $_activity_transactions = null;
    private ?Collection $_transactions = null;
    private ?array $_activities = null;

    // TODO: add a "root" user? only they can edit superadmin roles

    // TODO: finish
    // public function limits(): HasMany
    // {
    //     return $this->hasMany(UserLimits::class);
    // }

    // public function getCategoryLimit(string $category): float
    // {
    //     foreach ($this->limits as $limit) {
    //         if ($limit->category == $category) {
    //             return $limit->duration;
    //         }
    //     }
    //     return -1;
    // }

    public function hasPermission($permission): bool
    {
        return $this->role->hasPermission($permission);
    }

    private function getActivityTransactions(): Collection
    {
        if ($this->_activity_transactions == null) {
            $this->_activity_transactions = DB::table('activity_transactions')->where('user_id', $this->id)->orderBy('created_at', 'DESC')->get();
        }

        return $this->_activity_transactions;
    }

    public function getTransactions(): Collection
    {
        if ($this->_transactions == null) {
            $this->_transactions = Transaction::where('purchaser_id', $this->id)->orderBy('created_at', 'DESC')->get();
        }

        return $this->_transactions;
    }

    public function getActivities(): array
    {
        if ($this->_activities == null) {
            $return = array();

            $activity_transactions = $this->getActivityTransactions();
            foreach ($activity_transactions as $activity) {
                $return[] = [
                    'created_at' => Carbon::parse($activity->created_at),
                    'cashier' => User::find($activity->cashier_id),
                    'activity' => Activity::find($activity->activity_id),
                    'price' => $activity->activity_price,
                    'status' => $activity->status
                ];
            }

            $this->_activities = $return;
        }

        return $this->_activities;
    }

    // Find how much a user has spent in total. 
    // Does not factor in returned items/orders.
    public function findSpent(): float
    {
        $spent = $this->getTransactions()->sum('total_price');

        $activity_transactions = $this->getActivityTransactions();
        foreach ($activity_transactions as $activity_transaction) {
            $spent += ($activity_transaction->activity_price * $activity_transaction->activity_gst);
        }

        return floatval($spent);
    }

    // Find how much a user has returned in total.
    // This will see if a whole order has been returned, or if not, check all items in an unreturned order.
    public function findReturned(): float
    {
        $returned = 0.00;

        $transactions = $this->getTransactions();
        foreach ($transactions as $transaction) {
            if ($transaction->status) {
                $returned += $transaction->total_price;
                continue;
            }

            $transaction_products = explode(", ", $transaction->products);
            foreach ($transaction_products as $transaction_product) {
                $product = TransactionController::deserializeProduct($transaction_product, false);
                if ($product['returned'] < 1) {
                    continue;
                }

                $tax = $product['gst'];
                if ($product['pst'] != "null") {
                    $tax += ($product['pst'] - 1);
                }
                $returned += ($product['returned'] * $product['price'] * $tax);
            }
        }

        $activity_transactions = $this->getActivityTransactions();
        foreach ($activity_transactions as $transaction) {
            if ($transaction->status) {
                $returned += ($transaction->activity_price * $transaction->activity_gst);
            }
        }

        return floatval($returned);
    }

    // Find how much money a user owes. 
    // Taking their amount spent and subtracting the amount they have returned.
    public function findOwing(): float
    {
        return floatval($this->findSpent() - $this->findReturned());
    }
}
