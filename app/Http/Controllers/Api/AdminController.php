<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Barter;
use App\Models\Product;
use App\Models\TradeIn;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function overview(Request $request)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $overview = [
            // User & Product Counts
            'total_users' => User::count(),
            'total_products' => Product::count(),

            // Total Other Activities
            'total_transactions' => Transaction::count(),
            'total_barters' => Barter::count(),
            'total_trade_ins' => TradeIn::count(),

            // Verification Stats
            'pending_verifications' => Product::where('verification_status', 'pending')->count(),
            'approved_verifications' => Product::where('verification_status', 'approved')->count(),
            'rejected_verifications' => Product::where('verification_status', 'rejected')->count(),

            // Product Status Stats
            'available_products' => Product::where('status', 'available')->count(),
            'sold_products' => Product::where('status', 'sold')->count(),
            'bartered_products' => Product::where('status', 'bartered')->count(),
            'traded_products' => Product::where('status', 'traded')->count(),

            // Transaction Stats
            'pending_transactions' => Transaction::where('status', 'pending')
                ->orWhere('status', 'escrow')
                ->count(),

            'completed_transactions' => Transaction::where('status', 'completed')->count(),
            'refunded_transactions' => Transaction::where('status', 'refunded')->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $overview
        ]);
    }
}
