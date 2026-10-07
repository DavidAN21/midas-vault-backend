<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes; // 🔥 TAMBAH INI

class Transaction extends Model
{
    use HasFactory, SoftDeletes; // 🔥 TAMBAH SoftDeletes

        protected $fillable = [
        'buyer_id',
        'seller_id', 
        'product_id',
        'amount',
        'total_amount', // jika ada
        'payment_method',
        'payment_reference', 
        'status',
        'escrow_status'
    ];

    protected $attributes = [
        'amount' => 0, // 🔥 DEFAULT VALUE
        'status' => 'pending',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    // Relations
    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function review()
    {
        return $this->hasOne(Review::class, 'transaction_id');
    }

    // 🔥 TAMBAH METHOD UNTUK CEK STATUS
    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    public function isCancelled()
    {
        return $this->status === 'cancelled';
    }

    public function isActive()
    {
        return in_array($this->status, ['pending', 'confirmed']);
    }
}