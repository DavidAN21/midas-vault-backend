<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Barter;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BarterController extends Controller
{
    // ===============================
    // STORE (DIPERBAIKI)
    // ===============================

    public function store(Request $request)
    {
        $request->validate([
            'receiver_product_id' => 'required|exists:products,id',
            'requester_product_id' => 'required|exists:products,id',
            'notes' => 'nullable|string|max:500',
        ]);

        $receiverProduct = Product::with('user')->findOrFail($request->receiver_product_id);
        $requesterProduct = Product::with('user')->findOrFail($request->requester_product_id);

        // Debug info
        Log::info('Barter Attempt:', [
            'requester_user_id' => $request->user()->id,
            'requester_product_user_id' => $requesterProduct->user_id,
            'receiver_product_user_id' => $receiverProduct->user_id,
            'receiver_allows_barter' => $receiverProduct->allow_barter,
            'requester_product_status' => $requesterProduct->status,
            'receiver_product_status' => $receiverProduct->status,
            'requester_verified' => $requesterProduct->isVerified(),
            'receiver_verified' => $receiverProduct->isVerified(),
        ]);

        // Validasi 1: Cek apakah produk requester milik user
        if ($requesterProduct->user_id !== $request->user()->id) {
            Log::warning('Barter Failed: Requester product not owned by user');
            return response()->json([
                'success' => false,
                'message' => 'Anda hanya bisa menawarkan produk sendiri untuk barter'
            ], 400);
        }

        // Validasi 2: Cek tidak barter dengan diri sendiri
        if ($receiverProduct->user_id === $request->user()->id) {
            Log::warning('Barter Failed: Cannot barter with own product');
            return response()->json([
                'success' => false,
                'message' => 'Tidak bisa barter dengan produk sendiri'
            ], 400);
        }

        // Validasi 3: Cek apakah receiver product menerima barter
        if (!$receiverProduct->allowsBarter()) {
            Log::warning('Barter Failed: Receiver product does not allow barter');
            return response()->json([
                'success' => false,
                'message' => 'Produk ini tidak menerima penawaran barter'
            ], 400);
        }

        // Validasi 4: Cek status verification kedua produk
        if (!$requesterProduct->isVerified()) {
            Log::warning('Barter Failed: Requester product not verified');
            return response()->json([
                'success' => false,
                'message' => 'Produk Anda belum terverifikasi'
            ], 400);
        }

        if (!$receiverProduct->isVerified()) {
            Log::warning('Barter Failed: Receiver product not verified');
            return response()->json([
                'success' => false,
                'message' => 'Produk target belum terverifikasi'
            ], 400);
        }

        // Validasi 5: Cek ketersediaan produk
        if (!$requesterProduct->isAvailable()) {
            Log::warning('Barter Failed: Requester product not available');
            return response()->json([
                'success' => false,
                'message' => 'Produk Anda sudah tidak tersedia'
            ], 400);
        }

        if (!$receiverProduct->isAvailable()) {
            Log::warning('Barter Failed: Receiver product not available');
            return response()->json([
                'success' => false,
                'message' => 'Produk target sudah tidak tersedia'
            ], 400);
        }

        // ========================================
        // 🔥 VALIDASI 6: CEK EXISTING BARTER AKTIF (DIPERBAIKI)
        // ========================================
        $existingActiveBarter = Barter::active()
            ->where(function($query) use ($requesterProduct, $receiverProduct) {
                $query->where('requester_product_id', $requesterProduct->id)
                    ->where('receiver_product_id', $receiverProduct->id);
            })->orWhere(function($query) use ($requesterProduct, $receiverProduct) {
                $query->where('requester_product_id', $receiverProduct->id)
                    ->where('receiver_product_id', $requesterProduct->id);
            })
            ->first();

        if ($existingActiveBarter) {
            Log::warning('Barter Failed: Active barter exists', [
                'barter_id' => $existingActiveBarter->id,
                'status' => $existingActiveBarter->status
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Sudah ada penawaran barter aktif antara produk ini'
            ], 400);
        }

        // SEMUA VALIDASI LULUS - BUAT BARTER
        $barter = Barter::create([
            'requester_id' => $request->user()->id,
            'receiver_id' => $receiverProduct->user_id,
            'requester_product_id' => $requesterProduct->id,
            'receiver_product_id' => $receiverProduct->id,
            'notes' => $request->notes, // 🔥 HANYA PAKAI NOTES, TIDAK ADA MESSAGE
            'status' => 'pending',
            'requester_confirmed' => false,
            'receiver_confirmed' => false,
        ]);

        Log::info('Barter Created Successfully:', ['barter_id' => $barter->id]);

        return response()->json([
            'success' => true,
            'data' => $barter->load(['requester', 'receiver', 'requesterProduct', 'receiverProduct']),
            'message' => 'Penawaran barter berhasil dikirim!'
        ], 201);
    }

    // ===============================
    // ACCEPT (DIPERBAIKI)
    // ===============================

// ===============================
// ACCEPT (FIXED - Use valid status)
// ===============================

    public function accept(Request $request, Barter $barter)
    {
        if ($barter->receiver_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        if (!$barter->canBeAccepted()) {
            return response()->json([
                'success' => false, 
                'message' => 'Penawaran barter tidak dapat diterima'
            ], 400);
        }

        try {
            $barter->markAsAccepted();
            
            Log::info('Barter Accepted:', ['barter_id' => $barter->id]);

            return response()->json([
                'success' => true,
                'data' => $barter->load(['requester', 'receiver', 'requesterProduct', 'receiverProduct']),
                'message' => 'Penawaran barter diterima! Silahkan konfirmasi setelah pertukaran.'
            ]);
        } catch (\Exception $e) {
            Log::error('Barter Accept Error:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    // ===============================
    // REJECT (DIPERBAIKI)
    // ===============================

    public function reject(Request $request, Barter $barter)
    {
        if ($barter->receiver_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        if (!$barter->isPending()) {
            return response()->json([
                'success' => false,
                'message' => 'Penawaran barter sudah diproses'
            ], 400);
        }

        try {
            $barter->markAsRejected();
            
            Log::info('Barter Rejected:', ['barter_id' => $barter->id]);

            return response()->json([
                'success' => true,
                'data' => $barter->load(['requester', 'receiver', 'requesterProduct', 'receiverProduct']),
                'message' => 'Penawaran barter ditolak!'
            ]);
        } catch (\Exception $e) {
            Log::error('Barter Reject Error:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    // ===============================
    // COMPLETE (DIPERBAIKI DENGAN DEBUG)
    // ===============================

    public function complete(Request $request, Barter $barter)
    {
        if (!in_array($request->user()->id, [$barter->requester_id, $barter->receiver_id])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        // 🔥 DEBUG: Log status barter sebelum validasi
        Log::info('Barter Complete Attempt:', [
            'barter_id' => $barter->id,
            'user_id' => $request->user()->id,
            'barter_status' => $barter->status,
            'requester_confirmed' => $barter->requester_confirmed,
            'receiver_confirmed' => $barter->receiver_confirmed,
            'isAccepted' => $barter->isAccepted(),
            'canBeCompleted' => $barter->canBeCompleted(),
            'isConfirmedByUser' => $barter->isConfirmedByUser($request->user()->id)
        ]);

        // Cek apakah barter bisa dikonfirmasi
        if (!$barter->canBeCompleted()) {
            Log::warning('Barter cannot be completed:', [
                'barter_id' => $barter->id,
                'status' => $barter->status,
                'requester_confirmed' => $barter->requester_confirmed,
                'receiver_confirmed' => $barter->receiver_confirmed
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Barter belum bisa dikonfirmasi'
            ], 400);
        }

        // Cek apakah user sudah konfirmasi sebelumnya
        if ($barter->isConfirmedByUser($request->user()->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Anda sudah mengkonfirmasi barter ini'
            ], 400);
        }

        try {
            // Konfirmasi oleh user
            $barter->confirmByUser($request->user()->id);
            
            $message = 'Konfirmasi berhasil! ';

            // 🔥 DEBUG: Log setelah konfirmasi
            Log::info('After confirmByUser:', [
                'barter_id' => $barter->id,
                'requester_confirmed' => $barter->requester_confirmed,
                'receiver_confirmed' => $barter->receiver_confirmed,
                'isConfirmedByBoth' => $barter->isConfirmedByBoth(),
                'status' => $barter->status
            ]);

            // Cek apakah kedua belah pihak sudah konfirmasi
            if ($barter->isCompleted()) {
                $barter->markAsCompleted();
                $message .= 'Barter selesai! Kedua pihak telah mengkonfirmasi.';
            } else {
                $message .= 'Menunggu konfirmasi dari pihak lain.';
            }

            Log::info('Barter Completion Updated:', [
                'barter_id' => $barter->id,
                'status' => $barter->status,
                'requester_confirmed' => $barter->requester_confirmed,
                'receiver_confirmed' => $barter->receiver_confirmed
            ]);

            return response()->json([
                'success' => true,
                'data' => $barter->load(['requester', 'receiver', 'requesterProduct', 'receiverProduct']),
                'message' => $message
            ]);

        } catch (\Exception $e) {
            Log::error('Barter Complete Error:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    // ===============================
    // CANCEL (DIPERBAIKI)
    // ===============================

    public function cancel(Request $request, Barter $barter)
    {
        if (!in_array($request->user()->id, [$barter->requester_id, $barter->receiver_id])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        if (!$barter->canBeCancelled()) {
            return response()->json([
                'success' => false,
                'message' => 'Barter tidak dapat dibatalkan'
            ], 400);
        }

        try {
            $barter->markAsCancelled();
            
            Log::info('Barter Cancelled:', ['barter_id' => $barter->id]);

            return response()->json([
                'success' => true,
                'data' => $barter->load(['requester', 'receiver', 'requesterProduct', 'receiverProduct']),
                'message' => 'Barter dibatalkan! Produk kembali tersedia di marketplace.'
            ]);
        } catch (\Exception $e) {
            Log::error('Barter Cancel Error:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    // ===============================
    // MY BARTERS (TIDAK DIUBAH)
    // ===============================

    public function myBarters(Request $request)
    {
        $barters = Barter::where('requester_id', $request->user()->id)
            ->orWhere('receiver_id', $request->user()->id)
            ->with(['requester', 'receiver', 'requesterProduct', 'receiverProduct'])
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $barters
        ]);
    }

    // ===============================
    // SHOW (TAMBAHAN - JIKA DIPERLUKAN)
    // ===============================

    public function show(Request $request, Barter $barter)
    {
        if (!in_array($request->user()->id, [$barter->requester_id, $barter->receiver_id])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $barter->load(['requester', 'receiver', 'requesterProduct', 'receiverProduct'])
        ]);
    }
}