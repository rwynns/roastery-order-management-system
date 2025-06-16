<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Product;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;

class ProductController extends Controller
{
    /**
     * Display a listing of the products.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Product::with(['category', 'inventory']);

        // Search by name or SKU
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                ->orWhere('sku', 'like', "${$search}%");
            });
        }

        // Filter by category
        if ($request->has('category_id')) {
            $request->where('category_id', $request->category_id);
        }

        // Filter by status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Filter by stock status
        if ($request->has('low_stock') && $request->boolean('low_stock')) {
            $query->lowstock();
        }

        // Sort
        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->get('per_page', 15);
        $products = $query->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $products
        ]);

    }

    /**
     * Store a newly created product.
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = Product::create($request->validated());

        // Create inventory record
        $product->inventory()->create([
            'current_stock' => $request->get('initial_stock', 0),
            'reserved_stock' => 0,
            'last_updated_at' => now()
        ]);

        $product->load(['category', 'inventory']);

        return response()->json([
            'status' => 'success',
            'message' => 'Product created successfully',
            'date' => $product
        ], 201);
    }

    /**
     * Display the specified product.
     */
    public function show(Product $product): JsonResponse
    {
        $product->load(['category', 'inventory', 'orderItems.order']);

        return response()->json([
            'status' => 'success',
            'data' => $product
        ]);
    }

    /**
     * Update the specified product.
     */
    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $product->update($request->validate());
        $product->load(['category', 'inventory']);

        return response()->json([
            'status' => 'success',
            'message' => 'Product updated successfully',
            'data' => $product
        ]);
    }

    /**
     * Remove the specified product.
     */
    public function destroy(Product $product): JsonResponse
    {
        // Check if product has any orders
        if ($product->orderItems()->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot delete product with existing orders'
            ], 422);
        }

        $product->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Product deleted successfully'
        ]);
    }

    /**
     * Get product with low stock
     */
    public function lowStock(Request $request): JsonResponse
    {
        $products = Product::with(['category', 'inventory'])
        ->lowStock()
        ->paginate($request->get('per_page', 15));

        return response()->json([
            'status' => 'success',
            'data' => $products
        ]);
    }

    /**
     * Buld update product status
     */
    public function bulkUpdateStatus(Request $request): JsonResponse
    {
        $request->validate([
            'product_ids' => 'required|array',
            'product_ids' => 'exists:product,id',
            'is_active' => 'required|boolean'
        ]);

        Product::whereIn('id', $request->product_ids)
        ->update(['is_active' => $request->is_active]);

        return response()->json([
            'status' => 'success',
            'message' => 'Product update successfully'
        ]);
    }
}
