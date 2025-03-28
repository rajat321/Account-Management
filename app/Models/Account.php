<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Helpers\Helper;

class Account extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $fillable = ['user_id', 'account_name', 'account_number', 'account_type', 'currency', 'balance'];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($account) {
            do {
                $account_number = Helper::generateAccountNumber();
            } while (self::where('account_number', $account_number)->exists());
            
            $account->account_number = $account_number;
        });
    }
}

