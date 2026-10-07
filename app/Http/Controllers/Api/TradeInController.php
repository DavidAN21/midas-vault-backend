<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\TradeIn;
use Illuminate\Http\Request;

class TradeInController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'new_product_id' => 'required|exists:products,id',
            'old_product_id' => 'required|exists:products,id',
        ]);

        // DEBUG
        \Log::info('TradeIn Store Request:', $request->all());

        $newProduct = Product::with('user')->findOrFail($request->new_product_id);
        $oldProduct = Product::with('user')->findOrFail($request->old_product_id);

        // Validasi
        if ($oldProduct->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda hanya bisa menukar produk sendiri'
            ], 400);
        }

        if ($newProduct->user_id === $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak bisa tukar tambah dengan produk sendiri'
            ], 400);
        }

        if (!$newProduct->allowsTradeIn()) {
            return response()->json([
                'success' => false,
                'message' => 'Produk ini tidak menerima tukar tambah'
            ], 400);
        }

        if (!$newProduct->isAvailable() || !$oldProduct->isAvailable()) {
            return response()->json([
                'success' => false,
                'message' => 'Salah satu produk sudah tidak tersedia'
            ], 400);
        }

        // Hitung selisih harga berdasarkan harga asli produk
        $newProductValue = $newProduct->price;
        $oldProductValue = $oldProduct->price;
        
        $priceDifference = max(0, $newProductValue - $oldProductValue);

        $tradeIn = TradeIn::create([
            'buyer_id' => $request->user()->id,
            'seller_id' => $newProduct->user_id,
            'old_product_id' => $oldProduct->id,
            'new_product_id' => $newProduct->id,
            'price_difference' => $priceDifference,
            'status' => 'pending',
        ]);

        // DEBUG
        \Log::info('TradeIn Created:', $tradeIn->toArray());

        return response()->json([
            'success' => true,
            'data' => $tradeIn->load(['buyer', 'seller', 'oldProduct', 'newProduct']),
            'message' => 'Penawaran tukar tambah berhasil dikirim!'
        ], 201);
    }

    public function accept(Request $request, TradeIn $tradeIn)
    {
        if ($tradeIn->seller_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $tradeIn->update(['status' => 'accepted']);

        return response()->json([
            'success' => true,
            'data' => $tradeIn,
            'message' => 'Penawaran tukar tambah diterima! Silakan lanjutkan pembayaran.'
        ]);
    }

    public function reject(Request $request, TradeIn $tradeIn)
    {
        if ($tradeIn->seller_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $tradeIn->update(['status' => 'rejected']);

        return response()->json([
            'success' => true,
            'data' => $tradeIn,
            'message' => 'Penawaran tukar tambah ditolak!'
        ]);
    }

    public function cancel(Request $request, TradeIn $tradeIn)
    {
        if (!in_array($request->user()->id, [$tradeIn->buyer_id, $tradeIn->seller_id])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        // Kembalikan status produk ke available
        if ($tradeIn->status === 'accepted') {
            $tradeIn->oldProduct->makeAvailable();
            $tradeIn->newProduct->makeAvailable();
        }

        $tradeIn->update(['status' => 'cancelled']);

        return response()->json([
            'success' => true,
            'data' => $tradeIn,
            'message' => 'Tukar tambah dibatalkan! Produk kembali tersedia di marketplace.'
        ]);
    }

    public function pay(Request $request, TradeIn $tradeIn)
    {
        if ($tradeIn->buyer_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        if ($tradeIn->status !== 'accepted') {
            return response()->json([
                'success' => false,
                'message' => 'Penawaran belum diterima'
            ], 400);
        }

        $tradeIn->update([
            'payment_status' => 'paid',
            'status' => 'completed',
        ]);

        // Update status produk
        $tradeIn->oldProduct->update(['status' => 'traded']);
        $tradeIn->newProduct->update(['status' => 'traded']);

        return response()->json([
            'success' => true,
            'data' => $tradeIn,
            'message' => 'Pembayaran berhasil! Tukar tambah selesai.'
        ]);
    }

    public function myTradeIns(Request $request)
    {
        $tradeIns = TradeIn::where('buyer_id', $request->user()->id)
            ->orWhere('seller_id', $request->user()->id)
            ->with(['buyer', 'seller', 'oldProduct', 'newProduct'])
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $tradeIns
        ]);
    }
}