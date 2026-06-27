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



use Illuminate\Support\Facades\DB;


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
    'images.*' => 'file|mimes:jpeg,png,jpg,gif,svg,avif|max:1024',
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
            'owner',
            'inquiries'
        ])
        ->first();

    if (!$property) {
        return response()->json(['message' => 'Property not found'], 404);
    }

    $viewsCount = (int) $property->views ?? 0;
    $isFeatured = (int) $property->isFeatured ?? 0;
    $favoritesCount = $property->favoritedBy()->count();

    // ✅ Fix: Check if user is authenticated before accessing user data
    $loggedInUser = auth()->check() ? auth()->user()->id : null;
    $isOwner = $loggedInUser !== null && $loggedInUser === $property->addedBy;

    return response()->json([
        'property' => $property,
        'viewsCount' => $viewsCount,
        'favoritesCount' => $favoritesCount,
        'isFeatured' => $isFeatured,
        'isOwner' => $isOwner,
    ]);
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


    public function adminIndex(Request $request)
    {
        // Check if user is admin (role = 3)
        $user = auth()->user();
        if (!$user || $user->role !== 3) {
            return response()->json([
                'message' => 'Unauthorized. Admin access required.'
            ], 403);
        }
 
        $query = Property::query()
            ->with(['images', 'owner', 'currency', 'property_type'])
            ->orderBy('created_at', 'desc');
 
        // Filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
 
        if ($request->filled('isAvailable')) {
            $query->where('isAvailable', $request->boolean('isAvailable'));
        }
 
        if ($request->filled('isFeatured')) {
            $query->where('isFeatured', $request->boolean('isFeatured'));
        }
 
        if ($request->filled('listingType')) {
            $query->where('listingType', $request->listingType);
        }
 
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('propertyTitle', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%")
                  ->orWhere('state', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%");
            });
        }
 
        if ($request->filled('userId')) {
            $query->where('addedBy', $request->userId);
        }
 
        $properties = $query->paginate($request->get('per_page', 20));
 
        return response()->json([
            'status' => 'success',
            'data' => $properties,
        ]);
    }
 
    /**
     * Get revenue analytics for admin
     * GET /api/admin/revenue
     */
    public function adminRevenue(Request $request)
    {
        // Check if user is admin (role = 3)
        $user = auth()->user();
        if (!$user || $user->role !== 3) {
            return response()->json([
                'message' => 'Unauthorized. Admin access required.'
            ], 403);
        }
 
        // Date range filter (default: last 30 days)
        $startDate = $request->filled('start_date') 
            ? Carbon::parse($request->start_date) 
            : Carbon::now()->subDays(30);
        
        $endDate = $request->filled('end_date') 
            ? Carbon::parse($request->end_date) 
            : Carbon::now();
 
        // Total properties
        $totalProperties = Property::count();
        $activeListings = Property::where('isAvailable', true)->count();
        $featuredProperties = Property::where('isFeatured', true)
            ->whereNotNull('featuredUntil')
            ->where('featuredUntil', '>=', now())
            ->count();
 
        // Revenue from promotion packages
        $promotionRevenue = Property::whereNotNull('promotionPackageId')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with('promotionPackage')
            ->get()
            ->sum(function($property) {
                return $property->promotionPackage ? $property->promotionPackage->price : 0;
            });
 
        // Total property value (sum of all property prices)
        $totalPropertyValue = Property::where('isAvailable', true)->sum('price');
 
        // Revenue by listing type
        $revenueByType = Property::select('listingType', DB::raw('COUNT(*) as count'), DB::raw('SUM(price) as total_value'))
            ->where('isAvailable', true)
            ->groupBy('listingType')
            ->get();
 
        // Revenue by property type
        $revenueByPropertyType = Property::select('propertyTypeId', DB::raw('COUNT(*) as count'), DB::raw('SUM(price) as total_value'))
            ->with('property_type')
            ->where('isAvailable', true)
            ->groupBy('propertyTypeId')
            ->get();
 
        // Revenue by location (top cities)
        $revenueByCity = Property::select('city', DB::raw('COUNT(*) as count'), DB::raw('SUM(price) as total_value'))
            ->where('isAvailable', true)
            ->whereNotNull('city')
            ->groupBy('city')
            ->orderByDesc('count')
            ->limit(10)
            ->get();
 
        // Monthly revenue trend (properties listed per month)
        $monthlyTrend = Property::select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(price) as total_value')
            )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('month')
            ->orderBy('month')
            ->get();
 
        // Top earning agents/owners
        $topAgents = Property::select('addedBy', DB::raw('COUNT(*) as properties_count'), DB::raw('SUM(price) as total_value'))
            ->with(['owner' => function($query) {
                $query->select('id', 'firstName', 'lastName', 'email', 'role');
            }])
            ->where('isAvailable', true)
            ->groupBy('addedBy')
            ->orderByDesc('total_value')
            ->limit(10)
            ->get();
 
        // Average property price
        $averagePrice = Property::where('isAvailable', true)->avg('price');
 
        // Properties added in date range
        $newProperties = Property::whereBetween('created_at', [$startDate, $endDate])->count();
 
        // Promotion packages usage
        $promotionPackages = DB::table('properties_promotion_packages as pp')
            ->leftJoin('properties as p', 'p.promotionPackageId', '=', 'pp.packageId')
            ->select('pp.packageId', 'pp.packageName', 'pp.price', DB::raw('COUNT(p.propertyId) as usage_count'), DB::raw('SUM(pp.price) as total_revenue'))
            ->groupBy('pp.packageId', 'pp.packageName', 'pp.price')
            ->get();
 
        return response()->json([
            'status' => 'success',
            'data' => [
                'summary' => [
                    'total_properties' => $totalProperties,
                    'active_listings' => $activeListings,
                    'featured_properties' => $featuredProperties,
                    'promotion_revenue' => round($promotionRevenue, 2),
                    'total_property_value' => round($totalPropertyValue, 2),
                    'average_price' => round($averagePrice, 2),
                    'new_properties' => $newProperties,
                ],
                'revenue_by_type' => $revenueByType,
                'revenue_by_property_type' => $revenueByPropertyType,
                'revenue_by_city' => $revenueByCity,
                'monthly_trend' => $monthlyTrend,
                'top_agents' => $topAgents,
                'promotion_packages' => $promotionPackages,
                'date_range' => [
                    'start' => $startDate->format('Y-m-d'),
                    'end' => $endDate->format('Y-m-d'),
                ],
            ],
        ]);
    }
 
    /**
     * Get analytics dashboard stats for admin
     * GET /api/admin/analytics/stats
     */
    public function adminStats(Request $request)
    {
        // Check if user is admin (role = 3)
        $user = auth()->user();
        if (!$user || $user->role !== 3) {
            return response()->json([
                'message' => 'Unauthorized. Admin access required.'
            ], 403);
        }
 
        // Calculate promotion revenue
        $promotionRevenue = Property::whereNotNull('promotionPackageId')
            ->with('promotionPackage')
            ->get()
            ->sum(function($property) {
                return $property->promotionPackage ? $property->promotionPackage->price : 0;
            });
 
        $stats = [
            'total_properties' => Property::count(),
            'active_listings' => Property::where('isAvailable', true)->count(),
            'pending_approvals' => Property::where('status', 'pending')->count(),
            'featured_properties' => Property::where('isFeatured', true)
                ->whereNotNull('featuredUntil')
                ->where('featuredUntil', '>=', now())
                ->count(),
            'total_views' => Property::sum('views'),
            'properties_for_sale' => Property::where('listingType', 'sale')->where('isAvailable', true)->count(),
            'properties_for_rent' => Property::where('listingType', 'rent')->where('isAvailable', true)->count(),
            'total_revenue' => round($promotionRevenue, 2),
        ];
 
        return response()->json([
            'status' => 'success',
            'data' => $stats,
        ]);
    }
 
    /**
     * Admin: Approve/Reject property
     * PATCH /api/admin/properties/{slug}/approve
     */
    public function approveProperty(Request $request, $slug)
    {
        $user = auth()->user();
        if (!$user || $user->role !== 3) {
            return response()->json([
                'message' => 'Unauthorized. Admin access required.'
            ], 403);
        }
 
        $property = Property::where('slug', $slug)->first();
        if (!$property) {
            return response()->json(['message' => 'Property not found'], 404);
        }
 
        $validated = $request->validate([
            'status' => 'required|in:active,pending,sold,rented',
            'reason' => 'nullable|string',
        ]);
 
        $property->update([
            'status' => $validated['status'],
            'isAvailable' => $validated['status'] === 'active',
        ]);
 
        // TODO: Send notification to property owner about approval/rejection
 
        return response()->json([
            'message' => 'Property status updated successfully',
            'property' => $property,
        ]);
    }
 
    /**
     * Admin: Feature/Unfeature property with promotion package
     * PATCH /api/admin/properties/{slug}/feature
     */
    public function featureProperty(Request $request, $slug)
    {
        $user = auth()->user();
        if (!$user || $user->role !== 3) {
            return response()->json([
                'message' => 'Unauthorized. Admin access required.'
            ], 403);
        }
 
        $property = Property::where('slug', $slug)->first();
        if (!$property) {
            return response()->json(['message' => 'Property not found'], 404);
        }
 
        $validated = $request->validate([
            'isFeatured' => 'required|boolean',
            'featuredUntil' => 'nullable|date',
            'promotionPackageId' => 'nullable|exists:properties_promotion_packages,packageId',
        ]);
 
        // Get duration from promotion package if provided
        $featuredUntil = null;
        if ($validated['isFeatured']) {
            if ($validated['promotionPackageId']) {
                $package = DB::table('properties_promotion_packages')
                    ->where('packageId', $validated['promotionPackageId'])
                    ->first();
                
                if ($package && $package->durationDays) {
                    $featuredUntil = Carbon::now()->addDays($package->durationDays);
                }
            } elseif (isset($validated['featuredUntil'])) {
                $featuredUntil = $validated['featuredUntil'];
            } else {
                $featuredUntil = Carbon::now()->addDays(30); // Default 30 days
            }
        }
 
        $property->update([
            'isFeatured' => $validated['isFeatured'],
            'featuredUntil' => $featuredUntil,
            'promotionPackageId' => $validated['promotionPackageId'] ?? null,
        ]);
 
        return response()->json([
            'message' => $validated['isFeatured'] ? 'Property featured successfully' : 'Property unfeatured successfully',
            'property' => $property->load('promotionPackage'),
        ]);
    }
 
    /**
     * Admin: Delete any property
     * DELETE /api/admin/properties/{slug}
     */
    public function adminDestroy($slug)
    {
        $user = auth()->user();
        if (!$user || $user->role !== 3) {
            return response()->json([
                'message' => 'Unauthorized. Admin access required.'
            ], 403);
        }
 
        $property = Property::where('slug', $slug)->first();
        if (!$property) {
            return response()->json(['message' => 'Property not found'], 404);
        }
 
        // Delete associated images from storage
        foreach ($property->images as $image) {
            Storage::disk('public')->delete($image->imageUrl);
        }
 
        // Delete images from database
        $property->images()->delete();
 
        // Delete property
        $property->delete();
 
        return response()->json(['message' => 'Property deleted successfully']);
    }
}