<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Barter extends Model
{
    use HasFactory;

    protected $fillable = [
        'requester_id',
        'receiver_id',
        'requester_product_id',
        'receiver_product_id',
        'status',
        'notes',
        'message', // ✅ TAMBAH INI jika ada di controller
        'requester_confirmed',
        'receiver_confirmed',
    ];

    protected $casts = [
        'requester_confirmed' => 'boolean',
        'receiver_confirmed' => 'boolean',
    ];

    // ========================================
    // 🔥 SCOPES BARU UNTUK FILTER STATUS
    // ========================================
    
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['pending', 'accepted']);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeCancelled($query)
    {
        return $query->whereIn('status', ['rejected', 'cancelled']);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    // ========================================
    // RELATIONS (TETAP SAMA)
    // ========================================
    
    public function requester()
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    public function requesterProduct()
    {
        return $this->belongsTo(Product::class, 'requester_product_id');
    }

    public function receiverProduct()
    {
        return $this->belongsTo(Product::class, 'receiver_product_id');
    }

    // ========================================
    // 🔥 METHOD STATUS CHECKER YANG DIPERBAIKI
    // ========================================
    
    public function isActive()
    {
        return in_array($this->status, ['pending', 'accepted']);
    }

    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isAccepted()
    {
        return $this->status === 'accepted';
    }

    public function isRejected()
    {
        return $this->status === 'rejected';
    }

    public function isCancelled()
    {
        return $this->status === 'cancelled';
    }

    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    // ========================================
    // 🔥 METHOD KONFIRMASI & VALIDASI
    // ========================================
    
    public function isConfirmedByBoth()
    {
        return $this->requester_confirmed && $this->receiver_confirmed;
    }

    public function isConfirmedByUser($userId)
    {
        if ($this->requester_id === $userId) {
            return $this->requester_confirmed;
        }
        if ($this->receiver_id === $userId) {
            return $this->receiver_confirmed;
        }
        return false;
    }

    public function confirmByUser($userId)
    {
        if ($this->requester_id === $userId) {
            $this->update(['requester_confirmed' => true]);
        } elseif ($this->receiver_id === $userId) {
            $this->update(['receiver_confirmed' => true]);
        }
        
        // 🔥 AUTO COMPLETE JIKA KEDUA PIHAK SUDAH KONFIRMASI
        if ($this->isConfirmedByBoth()) {
            $this->update(['status' => 'completed']);
            
            // Update status produk
            if ($this->requesterProduct) {
                $this->requesterProduct->update(['status' => 'sold']);
            }
            if ($this->receiverProduct) {
                $this->receiverProduct->update(['status' => 'sold']);
            }
        }
        
        return $this->refresh();
    }

    // ========================================
    // 🔥 METHOD UNTUK BUSINESS LOGIC
    // ========================================
    
    public function canBeAccepted()
    {
        return $this->isPending() && 
               $this->requesterProduct->isAvailable() && 
               $this->receiverProduct->isAvailable();
    }

    public function canBeCompleted()
    {
        return $this->isAccepted() && 
            $this->requesterProduct && 
            $this->receiverProduct &&
            !$this->isCompleted(); // 🔥 TAMBAH INI
    }

    public function canBeCancelled()
    {
        return $this->isActive(); // Bisa cancel selama masih aktif
    }

    // ========================================
    // 🔥 METHOD UNTUK UPDATE STATUS
    // ========================================
    public function markAsAccepted()
    {
        $this->update([
            'status' => 'accepted',
            'requester_confirmed' => false,
            'receiver_confirmed' => false
        ]);
        
        // 🔥 UPDATE: Gunakan status yang valid untuk products
        $this->requesterProduct->update(['status' => 'bartered']); // atau 'sold'
        $this->receiverProduct->update(['status' => 'bartered']); // atau 'sold'
        
        return $this;
    }

    public function markAsRejected()
    {
        $this->update(['status' => 'rejected']);
        return $this;
    }

    public function markAsCancelled()
    {
        $this->update(['status' => 'cancelled']);
        
        // Kembalikan status produk ke available
        if ($this->requesterProduct) {
            $this->requesterProduct->update(['status' => 'available']);
        }
        if ($this->receiverProduct) {
            $this->receiverProduct->update(['status' => 'available']);
        }
        
        return $this;
    }

    public function markAsCompleted()
    {
        $this->update(['status' => 'completed']);
        
        // Update status produk menjadi sold
        if ($this->requesterProduct) {
            $this->requesterProduct->update(['status' => 'sold']);
        }
        if ($this->receiverProduct) {
            $this->receiverProduct->update(['status' => 'sold']);
        }
        
        return $this;
    }
}