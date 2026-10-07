<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'reviewer_id',
        'transaction_id',
        'barter_id',
        'trade_in_id',
        'rating',
        'comment',
    ];

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function barter()
    {
        return $this->belongsTo(Barter::class);
    }

    public function tradeIn()
    {
        return $this->belongsTo(TradeIn::class);
    }
}