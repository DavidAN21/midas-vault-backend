<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'rating' => 'required|integer|between:1,5',
            'comment' => 'required|string',
            'transaction_id' => 'nullable|exists:transactions,id',
            'barter_id' => 'nullable|exists:barters,id',
            'trade_in_id' => 'nullable|exists:trade_ins,id',
        ]);

        $review = Review::create([
            'reviewer_id' => $request->user()->id,
            'transaction_id' => $request->transaction_id,
            'barter_id' => $request->barter_id,
            'trade_in_id' => $request->trade_in_id,
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);

        return response()->json([
            'success' => true,
            'data' => $review->load('reviewer')
        ], 201);
    }

    public function userReviews($userId)
    {
        $reviews = Review::whereHas('transaction', function($query) use ($userId) {
                $query->where('seller_id', $userId)
                      ->orWhere('buyer_id', $userId);
            })
            ->orWhereHas('barter', function($query) use ($userId) {
                $query->where('requester_id', $userId)
                      ->orWhere('receiver_id', $userId);
            })
            ->orWhereHas('tradeIn', function($query) use ($userId) {
                $query->where('buyer_id', $userId)
                      ->orWhere('seller_id', $userId);
            })
            ->with(['reviewer', 'transaction', 'barter', 'tradeIn'])
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $reviews
        ]);
    }
}