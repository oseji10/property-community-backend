<?php
// app/Http/Controllers/Api/FavoriteController.php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Favorite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FavoriteController extends Controller
{
   
    /**
     * Get all favorites of authenticated user
     */
    public function index()
    {
        $favorites = Favorite::where('userId', Auth::id())
            ->with('property')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $favorites
        ]);
    }

    /**
     * Add property to favorites
     */
    public function store(Request $request)
    {
        $request->validate([
            'propertyId' => 'required|exists:properties,propertyId'
        ]);

        $exists = Favorite::where('userId', Auth::id())
            ->where('propertyId', $request->propertyId)
            ->exists();

        if ($exists) {
            return response()->json([
                'status' => 'error',
                'message' => 'Property already favorited'
            ], 409);
        }

        $favorite = Favorite::create([
            'userId' => Auth::id(),
            'propertyId' => $request->propertyId
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Added to favorites',
            'data' => $favorite
        ], 201);
    }

    /**
     * Remove property from favorites
     */
    public function destroy($propertyId)
    {
        $favorite = Favorite::where('user_id', Auth::id())
            ->where('property_id', $propertyId)
            ->first();

        if (!$favorite) {
            return response()->json([
                'status' => 'error',
                'message' => 'Favorite not found'
            ], 404);
        }

        $favorite->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Removed from favorites'
        ]);
    }

    /**
     * Check if property is favorited by user
     */
    public function check($propertyId)
    {
        $exists = Favorite::where('user_id', Auth::id())
            ->where('property_id', $propertyId)
            ->exists();

        return response()->json([
            'status' => 'success',
            'isFavorite' => $exists
        ]);
    }
}