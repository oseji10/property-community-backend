<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\PromotionPackages;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PromotionPackageController extends Controller
{
    /**
     * Display a listing of the promotion packages.
     */
    public function index(Request $request): JsonResponse
    {
        $query = PromotionPackages::query();

        // Filter by promotion type
        if ($request->has('type') && $request->type !== 'all') {
            $query->byType($request->type);
        }

        // Filter by active status
        if ($request->has('status')) {
            if ($request->status === 'active') {
                $query->active();
            } elseif ($request->status === 'inactive') {
                $query->where('isActive', false);
            }
        }

        // Search functionality
        if ($request->has('search') && !empty($request->search)) {
            $query->search($request->search);
        }

        // Pagination
        $perPage = $request->get('per_page', 50);
        $packages = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $packages->items(),
            'pagination' => [
                'total' => $packages->total(),
                'per_page' => $packages->perPage(),
                'current_page' => $packages->currentPage(),
                'last_page' => $packages->lastPage(),
            ],
        ], 200);
    }

    /**
     * Store a newly created promotion package.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'packageName' => 'required|string|max:100|unique:properties_promotion_packages,packageName',
            'packageDescription' => 'nullable|string|max:500',
            'price' => 'required|numeric|min:0',
            'durationDays' => 'required|integer|min:1|max:365',
            'promotionType' => ['required', Rule::in(['featured', 'premium', 'urgent', 'standard'])],
            'isActive' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $package = PromotionPackages::create([
                'packageName' => $request->packageName,
                'packageDescription' => $request->packageDescription,
                'price' => $request->price,
                'durationDays' => $request->durationDays,
                'promotionType' => $request->promotionType,
                'isActive' => $request->isActive ?? true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Promotion package created successfully',
                'data' => $package,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create promotion package: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified promotion package.
     */
    public function show(int $id): JsonResponse
    {
        $package = PromotionPackages::find($id);

        if (!$package) {
            return response()->json([
                'success' => false,
                'message' => 'Promotion package not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $package,
        ], 200);
    }

    /**
     * Update the specified promotion package.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $package = PromotionPackages::find($id);

        if (!$package) {
            return response()->json([
                'success' => false,
                'message' => 'Promotion package not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'packageName' => [
                'required',
                'string',
                'max:100',
                Rule::unique('properties_promotion_packages', 'packageName')->ignore($id, 'packageId'),
            ],
            'packageDescription' => 'nullable|string|max:500',
            'price' => 'required|numeric|min:0',
            'durationDays' => 'required|integer|min:1|max:365',
            'promotionType' => ['required', Rule::in(['featured', 'premium', 'urgent', 'standard'])],
            'isActive' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $package->update([
                'packageName' => $request->packageName,
                'packageDescription' => $request->packageDescription,
                'price' => $request->price,
                'durationDays' => $request->durationDays,
                'promotionType' => $request->promotionType,
                'isActive' => $request->isActive ?? $package->isActive,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Promotion package updated successfully',
                'data' => $package,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update promotion package: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle the active status of the promotion package.
     */
    public function toggleActive(Request $request, int $id): JsonResponse
    {
        $package = PromotionPackages::find($id);

        if (!$package) {
            return response()->json([
                'success' => false,
                'message' => 'Promotion package not found',
            ], 404);
        }

        try {
            $package->isActive = !$package->isActive;
            $package->save();

            return response()->json([
                'success' => true,
                'message' => $package->isActive ? 'Package activated successfully' : 'Package deactivated successfully',
                'data' => [
                    'packageId' => $package->packageId,
                    'isActive' => $package->isActive,
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle package status: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified promotion package.
     */
    public function destroy(int $id): JsonResponse
    {
        $package = PromotionPackages::find($id);

        if (!$package) {
            return response()->json([
                'success' => false,
                'message' => 'Promotion package not found',
            ], 404);
        }

        // Check if package is being used by any property
        if ($package->properties()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete package because it is being used by ' . $package->properties()->count() . ' property(s)',
            ], 400);
        }

        try {
            $package->delete();

            return response()->json([
                'success' => true,
                'message' => 'Promotion package deleted successfully',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete promotion package: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get active packages for public display
     */
    public function getActivePackages(Request $request): JsonResponse
    {
        $query = PromotionPackages::active();

        // Filter by promotion type
        if ($request->has('type')) {
            $query->byType($request->type);
        }

        $packages = $query->orderBy('price', 'asc')->get();

        return response()->json([
            'success' => true,
            'data' => $packages,
        ], 200);
    }
}