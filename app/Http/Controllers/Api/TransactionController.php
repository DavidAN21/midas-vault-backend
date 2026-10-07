<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TransactionController extends Controller
{
    public function store(Request $request)
    {
        Log::info('🛒 Transaction attempt', [
            'user_id' => $request->user()->id,
            'product_id' => $request->product_id
        ]);

        try {
            $request->validate([
                'product_id' => 'required|exists:products,id',
            ]);

            $product = Product::with('user')->findOrFail($request->product_id);

            Log::info('📦 Product found', [
                'product_id' => $product->id,
                'seller_id' => $product->user_id,
                'status' => $product->status,
                'verification_status' => $product->verification_status,
                'price' => $product->price
            ]);

            // Validasi 1: Produk sendiri
            if ($product->user_id === $request->user()->id) {
                Log::warning('❌ Cannot buy own product', [
                    'user_id' => $request->user()->id,
                    'product_owner' => $product->user_id
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak bisa membeli produk sendiri'
                ], 400);
            }

            // Validasi 2: Produk available
            if ($product->status !== 'available') {
                Log::warning('❌ Product not available', [
                    'product_id' => $product->id,
                    'status' => $product->status
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Produk sudah tidak tersedia'
                ], 400);
            }

            // Validasi 3: Produk sudah terverifikasi
            if ($product->verification_status !== 'approved') {
                Log::warning('❌ Product not verified', [
                    'product_id' => $product->id,
                    'verification_status' => $product->verification_status
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Produk belum terverifikasi oleh admin'
                ], 400);
            }

            // Buat transaksi
            $transaction = Transaction::create([
                'buyer_id' => $request->user()->id,
                'seller_id' => $product->user_id,
                'product_id' => $product->id,
                'amount' => $product->price,
                'payment_method' => 'transfer',
                'status' => 'escrow',
                'payment_reference' => 'TRX-' . time() . '-' . $request->user()->id,
            ]);

            Log::info('✅ Transaction created', [
                'transaction_id' => $transaction->id,
                'buyer_id' => $transaction->buyer_id,
                'seller_id' => $transaction->seller_id,
                'amount' => $transaction->amount
            ]);

            // Update status produk menjadi sold
            $product->update(['status' => 'sold']);

            Log::info('📦 Product status updated to sold', [
                'product_id' => $product->id,
                'new_status' => $product->status
            ]);

            return response()->json([
                'success' => true,
                'data' => $transaction->load(['buyer', 'seller', 'product']),
                'message' => 'Transaksi berhasil! Produk masuk sistem escrow.'
            ], 201);

        } catch (\Exception $e) {
            Log::error('❌ Transaction error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()
            ], 500);
        }
    }

    public function myTransactions(Request $request)
    {
        $transactions = Transaction::where('buyer_id', $request->user()->id)
            ->orWhere('seller_id', $request->user()->id)
            ->with(['buyer', 'seller', 'product'])
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $transactions
        ]);
    }

    public function confirm(Request $request, Transaction $transaction)
    {
        // Hanya seller yang bisa konfirmasi
        if ($transaction->seller_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $transaction->update(['status' => 'completed']);

        return response()->json([
            'success' => true,
            'data' => $transaction,
            'message' => 'Transaksi dikonfirmasi selesai! Dana akan ditransfer ke seller.'
        ]);
    }

    public function cancel(Request $request, Transaction $transaction)
    {
        // Buyer atau seller bisa cancel
        if (!in_array($request->user()->id, [$transaction->buyer_id, $transaction->seller_id])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        // Kembalikan status produk ke available
        $transaction->product->update(['status' => 'available']);

        $transaction->update(['status' => 'cancelled']); // PASTIKAN PAKAI QUOTES 'cancelled'

        return response()->json([
            'success' => true,
            'data' => $transaction,
            'message' => 'Transaksi dibatalkan! Produk kembali tersedia di marketplace.'
        ]);
    }

    // Method untuk get transaction by ID
    public function show(Transaction $transaction)
    {
        if (!in_array(request()->user()->id, [$transaction->buyer_id, $transaction->seller_id])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $transaction->load(['buyer', 'seller', 'product'])
        ]);
    }
}