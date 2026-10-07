<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    // ========================================
    // UPDATE (Full Modified Version)
    // ========================================
    public function update(ProductRequest $request, Product $product)
    {
        if ($product->user_id !== $request->user()->id && !$request->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        // DEBUG: Log request data
        \Log::info('Product Update Request:', $request->all());
        \Log::info('Product ID:', ['id' => $product->id]);

        // Validated data
        $updateData = $request->validated();

        // Handle image upload jika ada file baru
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '_' . $image->getClientOriginalName();
            $path = $image->storeAs('products', $imageName, 'public');

            // simpan path
            $updateData['image_url'] = Storage::url($path);
        }

        // Field `image` harus dihapus karena tidak ada di database
        unset($updateData['image']);

        // DEBUG: Log data yang akan diupdate
        \Log::info('Update Data:', $updateData);

        // Update product
        $product->update($updateData);

        // DEBUG: Log setelah update
        \Log::info('Product After Update:', $product->toArray());

        return response()->json([
            'success' => true,
            'data' => $product->fresh(),
            'message' => 'Produk berhasil diupdate!'
        ]);
    }


    // ========================================
    // INDEX
    // ========================================
    public function index(Request $request)
    {
        $query = Product::with(['user', 'verifier'])
            ->where('verification_status', '!=', 'rejected') // Tampilkan semua kecuali rejected
            ->where('status', 'available');

        // Filter by verification status
        if ($request->has('verification_status') && $request->verification_status !== 'all') {
            $query->where('verification_status', $request->verification_status);
        }

        if ($request->has('category') && $request->category !== 'all') {
            $query->where('category', $request->category);
        }

        if ($request->has('condition') && $request->condition !== 'all') {
            $query->where('condition', $request->condition);
        }

        return response()->json([
            'success' => true,
            'data' => $query->latest()->get()
        ]);
    }


    // ========================================
    // STORE (Sudah termasuk barter & trade-in)
    // Di ProductController.php - method store()
    public function store(ProductRequest $request)
    {
        // Upload image
        $imageUrl = '/images/default-product.jpg';
        
        if ($request->hasFile('image')) {
            try {
                $file = $request->file('image');
                $fileName = time() . '_' . uniqid() . '_' . $file->getClientOriginalName();
                
                // Simpan file
                $path = $file->storeAs('products', $fileName, 'public');
                
                // 🔥 PASTIKAN URL BENAR
                $imageUrl = asset('storage/' . $path); // atau Storage::url($path)
                
                \Log::info('🖼️ Image upload details:', [
                    'original_name' => $file->getClientOriginalName(),
                    'stored_name' => $fileName,
                    'storage_path' => $path,
                    'image_url' => $imageUrl,
                    'asset_url' => asset('storage/' . $path),
                    'storage_url' => Storage::url($path)
                ]);
                
            } catch (\Exception $e) {
                \Log::error('❌ Image upload failed:', ['error' => $e->getMessage()]);
            }
        }

        $product = Product::create([
            'user_id'               => $request->user()->id,
            'name'                  => $request->name,
            'description'           => $request->description,
            'category'              => $request->category,
            'condition'             => $request->condition,
            'price'                 => $request->price,
            'image_url'             => $imageUrl,
            'verification_status'   => 'pending',
            'allow_barter'          => (bool) $request->allow_barter,
            'barter_preferences'    => $request->barter_preferences,
            'allow_trade_in'        => (bool) $request->allow_trade_in,
            'trade_in_value'        => $request->trade_in_value ?: null,
            'trade_in_preferences'  => $request->trade_in_preferences,
        ]);

        \Log::info('✅ Product created with image:', [
            'product_id' => $product->id,
            'image_url' => $product->image_url
        ]);

        return response()->json([
            'success' => true,
            'data'    => $product->load('user'),
            'message' => 'Produk berhasil diupload!'
        ], 201);
    }


    // ========================================
    // SHOW
    // ========================================
    public function show(Product $product)
    {
        return response()->json([
            'success' => true,
            'data'    => $product->load(['user', 'verifier'])
        ]);
    }


// ========================================
// DELETE - Dengan validasi transaksi
// ========================================
    public function destroy(Request $request, Product $product)
    {
        // Cek authorization
        if ($product->user_id !== $request->user()->id && !$request->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        // 🔥 CEK APAKAH PRODUK SUDAH PERNAH ADA TRANSAKSI
        $hasTransactions = $product->transactions()->exists();
        $hasBarters = $product->bartersAsRequester()->exists() || $product->bartersAsReceiver()->exists();
        $hasTradeIns = $product->tradeInsAsOld()->exists() || $product->tradeInsAsNew()->exists();

        if ($hasTransactions || $hasBarters || $hasTradeIns) {
            return response()->json([
                'success' => false,
                'message' => 'Produk tidak dapat dihapus karena sudah memiliki riwayat transaksi, barter, atau tukar tambah'
            ], 422);
        }

        // Jika aman, hapus produk
        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully'
        ]);
    }

    // Di ProductController - Method untuk admin
    public function adminDelete(Request $request, Product $product)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        // Admin bisa archive produk meski ada transaksi
        $product->update([
            'status' => 'archived',
            'verification_status' => 'rejected'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Produk berhasil diarchive'
        ]);
    }

    // GET ALL PRODUCTS (Including pending)
    // ========================================
    public function getAllProducts(Request $request)
    {
        $query = Product::with(['user', 'verifier'])
            ->where('verification_status', '!=', 'rejected') // Tampilkan approved dan pending
            ->where('status', 'available');

        if ($request->has('category') && $request->category !== 'all') {
            $query->where('category', $request->category);
        }

        if ($request->has('condition') && $request->condition !== 'all') {
            $query->where('condition', $request->condition);
        }

        if ($request->has('verification_status') && $request->verification_status !== 'all') {
            $query->where('verification_status', $request->verification_status);
        }

        return response()->json([
            'success' => true,
            'data' => $query->latest()->get()
        ]);
    }


    // ========================================
    // GET USER PRODUCTS
    // ========================================
    public function myProducts(Request $request)
    {
        return response()->json([
            'success' => true,
            'data'    => Product::where('user_id', $request->user()->id)
                                ->with(['user', 'verifier'])
                                ->latest()
                                ->get()
        ]);
    }
}
