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
    $query = Property::query()->where('isAvailable', true);

    // Eager load relations
    $query->with('images', 'owner', 'currency', 'property_type');

    // Filters
   if ($request->filled('type')) {
    $type = strtolower($request->type);

    // Map frontend values to DB values if needed
    if ($type === 'buy' || $type === 'sale') {
        $type = 'sale';
    } elseif ($type === 'rent') {
        $type = 'rent';
    } else {
        $type = null; // ignore invalid types
    }

    if ($type) {
        $query->where('listingType', $type); // column in DB
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

    // Latest properties first, paginate 10 per page
    $properties = $query->latest()->paginate(10);

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
        $properties = Property::with('images', 'currency')->get();
        // $properties = Property::where('addedBy', $user->id)->with('images','currency')->latest()->paginate(10);
        return response()->json($properties);
    }


    // public function show(Request $request, $slug){
    //     $property = Property::where('slug', $slug)->with('images','currency','property_type')->first();
    //     if (!$property) {
    //         return response()->json(['message' => 'Property not found'], 404);
    //     }
        
    //     return response()->json($property);
    // }

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
