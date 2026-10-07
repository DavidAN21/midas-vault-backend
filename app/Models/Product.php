<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'category',
        'condition',
        'price',
        'image_url',

        'verified_by',
        'verified_at',
        'status',
        'verification_status',

        // New fields
        'allow_barter',
        'barter_preferences',
        'allow_trade_in',
        'trade_in_value',
        'trade_in_preferences',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'verified_at' => 'datetime',
        'allow_barter' => 'boolean',
        'allow_trade_in' => 'boolean',
    ];

    // ============================
    // RELATIONSHIPS
    // ============================

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function bartersAsRequester()
    {
        return $this->hasMany(Barter::class, 'requester_product_id');
    }

    public function bartersAsReceiver()
    {
        return $this->hasMany(Barter::class, 'receiver_product_id');
    }

    public function tradeInsAsOld()
    {
        return $this->hasMany(TradeIn::class, 'old_product_id');
    }

    public function tradeInsAsNew()
    {
        return $this->hasMany(TradeIn::class, 'new_product_id');
    }

    public function verifications()
    {
        return $this->hasMany(Verification::class);
    }

    // ============================
    // STATUS CHECKERS
    // ============================

    public function isVerified()
    {
        return $this->verification_status === self::STATUS_APPROVED;
    }

    public function isPending()
    {
        return $this->verification_status === self::STATUS_PENDING;
    }

    public function isRejected()
    {
        return $this->verification_status === self::STATUS_REJECTED;
    }

    // ============================
    // TRANSACTION CHECKERS
    // ============================

    public function hasActiveTransactions()
    {
        return $this->transactions()
            ->whereIn('status', ['pending', 'confirmed'])
            ->exists();
    }

    public function hasAnyTransactions()
    {
        return $this->transactions()->exists() ||
               $this->bartersAsRequester()->exists() ||
               $this->bartersAsReceiver()->exists() ||
               $this->tradeInsAsOld()->exists() ||
               $this->tradeInsAsNew()->exists();
    }

    // ============================
    // PRODUCT AVAILABILITY
    // ============================

    public function makeAvailable()
    {
        $this->update(['status' => 'available']);
    }

    public function makeSold()
    {
        $this->update(['status' => 'sold']);
    }

    public function isAvailable()
    {
        return $this->status === 'available'
            && $this->isVerified()
            && !$this->hasActiveTransactions(); // FINAL VERSION
    }

    // ============================
    // BARTER & TRADE-IN LOGIC
    // ============================

    public function allowsBarter()
    {
        return $this->allow_barter
            && $this->isVerified()
            && $this->isAvailable();
    }

    public function allowsTradeIn()
    {
        return $this->allow_trade_in
            && $this->isVerified()
            && $this->isAvailable();
    }

    public function getTradeInValue()
    {
        return $this->trade_in_value ?? ($this->price * 0.7);
    }
        public function getImageUrlAttribute($value)
    {
        if (!$value) {
            return asset('/images/default-product.jpg');
        }
        
        // Jika sudah full URL, return langsung
        if (filter_var($value, FILTER_VALIDATE_URL)) {
            return $value;
        }
        
        // Jika relative path, convert ke full URL
        if (str_starts_with($value, '/storage/')) {
            return asset($value);
        }
        
        if (str_starts_with($value, 'products/')) {
            return asset('storage/' . $value);
        }
        
        return asset($value);
    }
}
