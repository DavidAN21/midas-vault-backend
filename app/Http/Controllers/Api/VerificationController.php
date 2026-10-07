<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Verification;
use Illuminate\Http\Request;

class VerificationController extends Controller
{
    public function pending(Request $request)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        // Produk menunggu verifikasi
        $products = Product::where('verification_status', 'pending')
            ->where('status', 'available')
            ->with(['user'])
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $products
        ]);
    }

    public function update(Request $request, Product $product)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $request->validate([
            'status' => 'required|in:approved,rejected',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            if ($request->status === 'approved') {
                $product->update([
                    'verified_by' => $request->user()->id,
                    'verified_at' => now(),
                    'verification_status' => 'approved',
                ]);

                $message = 'Produk berhasil diverifikasi! Sekarang muncul di marketplace.';
            } else {
                $product->update([
                    'verification_status' => 'rejected',
                ]);

                $message = 'Produk ditolak verifikasi. Tidak akan muncul di marketplace.';
            }

            // Simpan riwayat verifikasi
            Verification::create([
                'product_id' => $product->id,
                'verifier_id' => $request->user()->id,
                'status' => $request->status,
                'notes' => $request->notes,
            ]);

            return response()->json([
                'success' => true,
                'data' => $product->load(['user', 'verifier']),
                'message' => $message
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    public function verifiedProducts(Request $request)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $products = Product::where('verification_status', 'approved')
            ->with(['user', 'verifier'])
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $products
        ]);
    }

    public function rejectedProducts(Request $request)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $products = Product::where('verification_status', 'rejected')
            ->with(['user', 'verifications']) // semua histori verifikasi
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $products
        ]);
    }
}
