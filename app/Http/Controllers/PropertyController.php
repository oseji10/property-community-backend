<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Property;
use App\Models\AdmissionSettings;
use App\Models\Applications;
use App\Models\Programmes;
use App\Models\AcademicSession;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use App\Models\PropertyType;
use PDF;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;



class PropertyController extends Controller
{
public function index(Request $request)
{
    $query = Property::query()
        ->where('isAvailable', true)
        ->with('images', 'owner', 'currency', 'property_type')
        ->orderBy('created_at', 'desc'); // default order by latest

    // Filters
    if ($request->filled('type')) {
        $type = strtolower($request->type);

        if ($type === 'buy' || $type === 'sale') {
            $type = 'sale';
        } elseif ($type === 'rent') {
            $type = 'rent';
        } else {
            $type = null;
        }

        if ($type) {
            $query->where('listingType', $type);
        }
    }

    if ($request->filled('location')) {
        $location = $request->location;
        $query->where(function($q) use ($location) {
            $q->where('city', 'like', "%{$location}%")
              ->orWhere('state', 'like', "%{$location}%")
              ->orWhere('address', 'like', "%{$location}%");
        });
    }

    if ($request->filled('propertyType')) {
        $query->where('propertyTypeId', $request->propertyType);
    }

    if ($request->filled('min')) {
        $query->where('price', '>=', $request->min);
    }

    if ($request->filled('max')) {
        $query->where('price', '<=', $request->max);
    }

    if ($request->filled('beds')) {
        $query->where('bedrooms', $request->beds);
    }

    // 🔥 KEY PART: Featured first (not expired), then latest
    $query->orderByRaw("
        CASE 
            WHEN isFeatured = 1 
                 AND featuredUntil IS NOT NULL 
                 AND featuredUntil >= NOW()
            THEN 0
            ELSE 1
        END
    ")
    ->latest(); // created_at DESC inside each group

    $properties = $query->paginate(10);

    return response()->json([
        'status' => 'success',
        'data' => $properties,
    ]);
}


   public function featuredProperties(Request $request)
{
    
    $query = Property::query()
        ->where('isAvailable', true)
        ->where('isFeatured', true)
        ->whereNotNull('featuredUntil')
        ->where('featuredUntil', '>=', now()); // not expired


    // Eager load relations
    $query->with('images', 'owner', 'currency', 'property_type');

    $properties = $query->latest()->paginate(30);
    return response()->json([
        'status' => 'success',
        'data' => $properties,
    ]);
}


    public function propertyType()
    {
        $property_types = PropertyType::all();
        return response()->json($property_types);
    }

public function store(Request $request)
{
    $data = $request->validate([
        'propertyTitle'       => 'required|string|max:255',
        'propertyDescription' => 'required|string',
        'propertyTypeId'      => 'required|integer|exists:property_types,typeId',
        'address'             => 'required|string',
        'city'                => 'nullable|string',
        'state'               => 'nullable|string',
        'price'               => 'required|numeric|min:0',
        'listingType'         => 'required|in:rent,sale',
        'bedrooms'            => 'nullable|integer|min:0',
        'bathrooms'           => 'nullable|integer|min:0',
        'garage'              => 'nullable|string',
        'longitude'           => 'nullable|string',
        'latitude'            => 'nullable|string',
        'otherFeatures'       => 'nullable|string',
        'amenities'           => 'nullable|string',
        'size'                => 'nullable|string',
        'currency'            => 'nullable|integer|exists:currencies,currencyId',
        // 'images'              => 'nullable',
        // 'images.*'            => 'image|mimes:jpeg,png,jpg,gif,svg,avif|max:5120',
        'images'   => 'nullable|array',
    'images.*' => 'file|mimes:jpeg,png,jpg,gif,svg,avif|max:5120',
    ]);

    $data['addedBy'] = auth()->id();
    $data['slug']    = \Str::slug($data['propertyTitle']) . '-' . uniqid();

    $property = Property::create($data);

    // Handle images
    $files = $request->file('images');

    // Normalize to array (handles single file or multiple)
    $images = is_array($files) ? $files : ($files ? [$files] : []);

    $savedImages = []; // for debugging/response

    foreach ($images as $image) {
        if ($image && $image->isValid()) {
            $path = $image->store('property_images', 'public');

            // ← This is the important fix
            $fullUrl = Storage::url($path);           // returns /storage/...
            // or: '/storage/' . $path;               // same result in most cases

            $property->images()->create([
                'imageUrl' => $path,
            ]);

            $savedImages[] = $path; // collect for response/debug
        }
    }

    // Optional: reload with images for response
    $property->load('images');

    return response()->json([
        'message'  => 'Property created successfully',
        'property' => $property,
        'saved_image_urls' => $savedImages, // ← add this temporarily to verify
    ], 201);
}

    public function myProperties()
    {
        $user = auth()->user();
        // $properties = Property::with('images', 'currency')->get();
        $properties = Property::where('addedBy', $user->id)->with('images','currency')
        ->orderBy('created_at', 'desc')
        ->get();
        return response()->json($properties);
    }


    // public function show(Request $request, $slug){
    //     $property = Property::where('slug', $slug)->with('images','currency','property_type')->first();
    //     if (!$property) {
    //         return response()->json(['message' => 'Property not found'], 404);
    //     }
        
    //     return response()->json($property);
    // }


    public function rate(Request $request, string $slug)
{
    $user = auth()->user();
    $request->validate(['rating' => 'required|integer|min:1|max:5']);

    $property = Property::where('slug', $slug)->firstOrFail();

    // Upsert — update if already rated, insert if not
    $property->ratings()->updateOrCreate(
        ['userId' => $user->id],
        ['rating'  => $request->rating]
    );

    $averageRating = round($property->ratings()->avg('rating'), 1);
    $totalRatings  = $property->ratings()->count();

    // Optionally cache on the property row for fast reads
    $property->update([
        'average_rating' => $averageRating,
        'total_ratings'  => $totalRatings,
    ]);

    return response()->json([
        'data' => compact('averageRating', 'totalRatings'),
    ]);
}


    public function show(Request $request, $slug)
{
    $property = Property::where('slug', $slug)
        ->with([
            'images',
            'currency',
            'property_type',
            // If you have these relationships defined, include them too
            'owner',
            'inquiries'
        ])
        ->first();

    if (!$property) {
        return response()->json(['message' => 'Property not found'], 404);
    }

    // Option 1: If you have a view_count column on properties table (recommended)
    $viewsCount = (int) $property->views ?? 0;
    $isFeatured = (int) $property->isFeatured ?? 0;

  
    // Favorites count – assuming many-to-many relationship named 'favoritedBy' or 'favorites'
    $favoritesCount = $property->favoritedBy()->count();   // or ->favorites()->count();

    // Check if current user is the owner
    $loggedInUser = auth()->user()->id;
    // return $property->addedBy;
    $isOwner = $loggedInUser === $property->addedBy;

    // You can either:
    // A) Add attributes directly to the model instance (cleanest for frontend)
    // $property->viewsCount     = $viewsCount;
    // $property->favoritesCount = $favoritesCount;
    // $property->isOwner        = $isOwner;

    // B) Or return a custom array / resource (more control over what’s sent)
    return response()->json([
        'property' => $property,
        'viewsCount' => $viewsCount,
        'favoritesCount' => $favoritesCount,
        'isFeatured' => $isFeatured,
        'isOwner' => $isOwner,
    ]);

    // Most common & clean approach: just augment the model
    // return response()->json($property);
}


    public function update(Request $request, $slug)
    {
        $property = Property::where('slug', $slug)->first();
        if (!$property) {
            return response()->json(['message' => 'Property not found'], 404);
        }

        if ($property->addedBy !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $data = $request->validate([
            'propertyTitle'       => 'sometimes|required|string|max:255',
            'propertyDescription' => 'sometimes|required|string',
            'propertyTypeId'      => 'sometimes|required|integer|exists:property_types,typeId',
            'address'             => 'sometimes|required|string',
            'city'                => 'nullable|string',
            'state'               => 'nullable|string',
            'price'               => 'sometimes|required|numeric|min:0',
            'listingType'         => 'sometimes|required|in:rent,sale',
            'bedrooms'            => 'nullable|integer|min:0',
            'bathrooms'           => 'nullable|integer|min:0',
            'garage'              => 'nullable|string',
            'longitude'           => 'nullable|string',
            'latitude'            => 'nullable|string',
            'otherFeatures'       => 'nullable|string',
            'amenities'           => 'nullable|string',
            'size'                => 'nullable|string',
            'currency'            => 'nullable|integer|exists:currencies,currencyId',
        ]);

        $property->update($data);

        return response()->json([
            'message'  => 'Property updated successfully',
            'property' => $property,
        ]);
    }


     public function updateStatus(Request $request, $slug)
    {
        $property = Property::where('slug', $slug)->first();
        if (!$property) {
            return response()->json(['message' => 'Property not found'], 404);
        }

        if ($property->addedBy !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $data = $request->validate([
            'status'                => 'nullable|string',
            ]);

        $property->update($data);

        return response()->json([
            'message'  => 'Property status successfully',
            'property' => $property,
        ]);
    }


    public function destroy($slug)
    {
        $property = Property::where('slug', $slug)->first();
        if (!$property) {
            return response()->json(['message' => 'Property not found'], 404);
        }

        if ($property->addedBy !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $property->delete();
        // Delete associated images from storage
        foreach ($property->images as $image) {
            Storage::disk('public')->delete($image->imageUrl);
        }

        // Also delete images from database
        $property->images()->delete();

        return response()->json(['message' => 'Property deleted successfully']);
    }


    public function deleteImage(Request $request, $slug, $imageId)
    {
        $property = Property::where('slug', $slug)->first();
        if (!$property) {
            return response()->json(['message' => 'Property not found'], 404);
        }

        if ($property->addedBy !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $image = $property->images()->where('imageId', $imageId)->first();
        if (!$image) {
            return response()->json(['message' => 'Image not found'], 404);
        }

        // Delete image file from storage
        Storage::disk('public')->delete($image->imageUrl);

        // Delete image record from database
        $image->delete();

        return response()->json(['message' => 'Image deleted successfully']);
    }


    public function propertyDetail($slug)
    {
        $property = Property::where('slug', $slug)->with('images','currency','property_type', 'owner.user_role')->first();
        if (!$property) {
            return response()->json(['message' => 'Property not found'], 404);
        }
               // Optional: more accurate (per IP per day)
    $ip = request()->ip();
    $key = "property_view:{$property->propertyId}:{$ip}";
    if (!Cache::has($key)) {
        $property->increment('views');
        Cache::put($key, true, now()->addDay());
    }
        return response()->json($property);
    }

}
