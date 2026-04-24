<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Favorite;
use App\Models\Property;
use Illuminate\Support\Facades\Auth;

class FavoriteController extends Controller
{
    /**
     * Get all favorites for authenticated user
     * GET /api/favorites
     */
    public function index()
    {
        $userId = auth()->id();
        
        $favorites = Favorite::where('userId', $userId)
            ->with(['property.images', 'property.currency', 'property.property_type', 'property.owner'])
            ->orderBy('created_at', 'desc')
            ->get();
        
        // Extract properties from favorites
        $properties = $favorites->map(function($favorite) {
            return $favorite->property;
        });
        
        return response()->json([
            'status' => 'success',
            'data' => $properties,
        ]);
    }

    /**
     * Add property to favorites
     * POST /api/favorites
     */
    public function store(Request $request)
    {
        $request->validate([
            'propertyId' => 'required|exists:properties,propertyId',
        ]);

        $userId = auth()->id();
        $propertyId = $request->propertyId;

        // Check if already favorited
        $exists = Favorite::where('userId', $userId)
            ->where('propertyId', $propertyId)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Property already in favorites',
            ], 409);
        }

        $favorite = Favorite::create([
            'userId' => $userId,
            'propertyId' => $propertyId,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Property added to favorites',
            'data' => $favorite,
        ], 201);
    }

    /**
     * Check if property is favorited
     * GET /api/favorites/check/{propertyId}
     */
    public function check($propertyId)
    {
        $userId = auth()->id();

        $isFavorited = Favorite::where('userId', $userId)
            ->where('propertyId', $propertyId)
            ->exists();

        return response()->json([
            'isFavorited' => $isFavorited,
        ]);
    }

    /**
     * Remove property from favorites
     * DELETE /api/favorites/{propertyId}
     */
    public function destroy($propertyId)
    {
        $userId = auth()->id();

        $favorite = Favorite::where('userId', $userId)
            ->where('propertyId', $propertyId)
            ->first();

        if (!$favorite) {
            return response()->json([
                'message' => 'Favorite not found',
            ], 404);
        }

        $favorite->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Property removed from favorites',
        ]);
    }
}