<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'address',
        'avatar',
        'reputation_score',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'reputation_score' => 'decimal:2',
        ];
    }

    // Relationships tetap sama
    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function transactionsAsBuyer()
    {
        return $this->hasMany(Transaction::class, 'buyer_id');
    }

    public function transactionsAsSeller()
    {
        return $this->hasMany(Transaction::class, 'seller_id');
    }

    public function bartersAsRequester()
    {
        return $this->hasMany(Barter::class, 'requester_id');
    }

    public function bartersAsReceiver()
    {
        return $this->hasMany(Barter::class, 'receiver_id');
    }

    public function tradeInsAsBuyer()
    {
        return $this->hasMany(TradeIn::class, 'buyer_id');
    }

    public function tradeInsAsSeller()
    {
        return $this->hasMany(TradeIn::class, 'seller_id');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class, 'reviewer_id');
    }

    public function verifications()
    {
        return $this->hasMany(Verification::class, 'verifier_id');
    }

    // Simplify role checks
    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function isUser()
    {
        return $this->role === 'user';
    }

    // User bisa menjadi buyer dan seller
    public function canSell()
    {
        return $this->isUser(); // Semua user bisa jual
    }

    public function canBuy()
    {
        return $this->isUser(); // Semua user bisa beli
    }
}