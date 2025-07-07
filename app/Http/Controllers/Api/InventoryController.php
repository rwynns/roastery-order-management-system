<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use App\Models\Product;
use App\Http\Requests\StoreInventoryRequest;
use App\Http\Requests\UpdateInventoryRequest;
use App\Http\Requests\StockAdjustmentRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Inventory::with(['product.category']);

        // Search by product name or SKU
        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('product', function($q) use ($search){
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        // Filter by category
        if ($request->has('category_id')) {
            $query->whereHas('product', function($q) use ($request) {
                $q->where('category_id', $request->category_id);
            });
        }

        // Filter by stock level
        if ($request->has('stock_level')) {
            switch ($request->stock_level) {
                case 'low': 
                    $query->lowStock();
                    break;
                case 'out':
                    $query->where('current_stock', '<=', 0);
                    break;
                case 'available':
                    $query->where('current_stock', '>', 0);
                    break;
            }
        }

        // Filter by stock range
        if ($request->has('min_stock')) {
            $query->where('current_stock', '>=', $request->min_stock);
        }
        if ($request->has('max_stock')) {
            $query->where('current_stock', '<=', $request->max_stock);
        }

        // Filter by last updated date
        if ($request->has('updated_form')){
            $query->whereData('last_updated', '>=', $request->update_from);
        }
        if ($request->has('updated_to')) {
            $query->whereDate('last_updated', '<=', $request->update_to);
        }

        // Sort
        $sortBy = $request->get('sort_by', 'product.name');
        $sortOrder = $request->get('sort_order', 'asc');

        if ($sortBy === 'product.name'){
            $query->join('products', 'inventories.product_id', '=', 'products.id')
                    ->orderBy('products.name', $sortOrder)
                    ->select('inventories.*');
        } else {
            $allowedSorts = ['current_stock', 'reserved_stock', 'available_stock', 'last_updated_at'];
            if (in_array($sortBy, $allowedSorts)){
                $query->orderBy($sortBy, $sortOrder);
            }
        }

        // Pagination
        $perPage = $request->get('per_page', 15);
        $inventories = $query->paginate($perPage);

        // Add computed attributes
        $inventories->getCollection()->transform(function ($inventory) {
            $inventory->available_stock = $inventory->available_stock;
            $inventory->stock_status = $this->getStockStatus($inventory);
            return $inventory;
        });

        return response()->json([
            'status' => 'success',
            'data'=> $inventories
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreInventoryRequest $request): JsonResponse
    {
        try {
            $inventory = Inventory::create([
                'product_id' => $request->product_id,
                'current_stock' => $request->current_stock,
                'reserved_stock' => $request->get('reserved_stock', 0),
                'last_cost' => $request->last_cost,
                'last_updated_at' => now()
            ]);

            $inventory->load('product');

            return response()->json([
                'status' => 'success',
                'message' => 'Inventory created successfully.',
                'data' => $inventory
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create inventory',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Inventory $inventory): JsonResponse
    {
        $inventory->load('product.category');

        // Add computed attributes
        $inventory->available_stock = $inventory->available_stock;
        $inventory->stock_status = $this->getStockStatus($inventory);

        // Get recent stock movements
        $stockHistory = $this->getStockHistory($inventory->id);

        return response()->json([
            'status' => 'success',
            'data' => [
                'inventory' => $inventory,
                'stock_history' => $stockHistory,
                'statistic' => [
                    'day_of_stock' => $this->calculateDaysOfStock($inventory),
                    'turnover_rate' => $this->calculateTurnoverRate($inventory),
                    'reorder_point' => $this->calculateReorderPoint($inventory)
                ]
            ]
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateInventoryRequest $request, Inventory $inventory): JsonResponse
    {
        try {
            $inventory->update([
                'current_stock' => $request->current_stock,
                'reserved_stock' => $request->get('reserved_stock', $inventory->reserved_stock),
                'last_cost' => $request->get('last_cost', $inventory->last_cost),
                'last_updated_at' => now()
            ]);

            $inventory->load('product');

            return response()->json([
                'status' => 'success',
                'message' => 'Inventory updated successfully.',
                'data' => $inventory
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update inventory',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Inventory $inventory): JsonResponse
    {
        try {
            // check if inventory has reserved stock
            if( $inventory->reserved_stock > 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot delete inventory with reserved stock.'
                ], 422);
            }

            $inventory->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Inventory deleted successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete inventory',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get low stock items
     */
    public function lowStock(Request $request): JsonResponse
    {
        $query = Inventory::with(['product.category'])
            ->lowStock();

        // Pagination
        $perPage = $request->get('per_page', 15);
        $lowStockItems = $query->paginate($perPage);

        // Add computed attributes
        $lowStockItems->getCollection()->transform(function ($inventory) {
            $inventory->available_stock = $inventory->available_stock;
            $inventory->stock_status = $this->getStockStatus($inventory);
            $inventory->recommended_order_quantity = $this->calculateRecommendedOrderQuantity($inventory);
            return $inventory;
        });

        return response()->json([
            'status' => 'success',
            'data' => $lowStockItems,
            'meta' => [
                'total_low_stock_items' => $lowStockItems->total(),
                'alert_message' => $lowStockItems->total() > 0 ?
                "You have {$lowStockItems->total()} Items with low stock!" :
                "All items are well stocked!"
            ]
        ]);
    }

    /**
     * Get out of stock items
     */
    public function outOfStock(Request $request): JsonResponse
    {
        $query = Inventory::with(['product.category'])
            ->where('current_stock', '<=', 0);

        $outOfStockItems = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'status' => 'success',
            'data' => $outOfStockItems,
            'meta' => [
                'total_out_of_stock_items' => $outOfStockItems->total()
            ]
        ]);
    }

    /**
     * Perform stock adjustment
     */
    public function stockAdjustment(StockAdjustmentRequest $request): JsonResponse
    {
        try{
            DB::beginTransaction();

            $inventory = Inventory::findOrFail($request->inventory_id);
            $oldStock = $inventory->current_stock;
            $adjustment = $request->adjustment;
            $newStock = $oldStock + $adjustment;

            // validate adjustment
            if ($newStock < 0) {
                throw new \Exception('Stock adjustment cannot result in negative stock.');
            }

            // update inventory
            $inventory->update([
                'current_stock' => $newStock,
                'last_cost' => $request->get('last_cost', $inventory->last_cost),
                'last_updated_at' => now()
            ]);

            // Log the adjustment (you can create a stock_movements table for this)
            $this->logStockMovement($inventory->id, [
                'type' => $adjustment > 0 ? 'adjustment_in' : 'adjustment_out',
                'quantity' => abs($adjustment),
                'old_stock' => $oldStock,
                'new_stock' => $newStock,
                'reason' => $request->reason,
                'reference' => $request->get('reference'),
                'user_id' => $request->user()->id
            ]);

            DB::commit();

            $inventory->load('product');

            return response()->json([
                'status' => 'success',
                'message' => 'Stock adjustment successful.',
                'data' => [
                    'inventory' => $inventory,
                    'adjustment_details' => [
                        'old_stock' => $oldStock,
                        'new_stock' => $newStock,
                        'adjustment' => $adjustment,
                        'reason' => $request->reason,
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to perform stock adjustment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk stock adjustment
     */
    public function bulkStockAdjustment(Request $request): JsonResponse
    {
        $request->validate([
            'adjustments' => 'required|array',
            'adjustments.*.inventory_id' => 'required|exists:inventories,id',
            'adjustments.*.adjustment' => 'required|integer',
            'adjustments.*.reason' => 'required|string',
            'adjustments.*.last_cost' => 'nullable|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();
            
            $results = [];
            $errors = [];

            foreach ($request->adjustments as $adjustmentData) {
                try {
                    $inventory = Inventory::findOrFail($adjustmentData['inventory_id']);
                    $oldStock = $inventory->current_stock;
                    $adjustment = $adjustmentData['adjustment'];
                    $newStock = $oldStock + $adjustment;

                    // validate adjustment
                    if ($newStock < 0) {
                        $errors[] = "Product {$inventory->product->name}: Stock cannot be negative";
                        continue;
                    }

                    // update inventory
                    $inventory->update([
                        'current_stock' => $newStock,
                        'last_cost' => $adjustmentData['last_cost'] ?? $inventory->last_cost,
                        'last_updated_at' => now()
                    ]);

                    // Log the adjustment
                    $this->logStockMovement($inventory->id, [
                        'type' => $adjustment > 0 ? 'bulk_adjustment_in' : 'bulk_adjustment_out',
                        'quantity' => abs($adjustment),
                        'old_stock' => $oldStock,
                        'new_stock' => $newStock,
                        'reason' => $adjustmentData['reason'],
                        'user_id' => $request->user()->id
                    ]);

                    // Prepare success response
                    $results[] = [
                        'product_name' => $inventory->product->name,
                        'old_stock' => $oldStock,
                        'adjustment' => $adjustment,
                        'new_stock' => $newStock,
                    ];
                } catch (\Exception $e) {
                    // Collect errors for each adjustment
                    $errors[] = "Product ID {$adjustmentData['inventory_id']}: {$e->getMessage()}";
                }
            }

            if (!empty($errors)) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Some adjustments failed',
                    'errors' => $errors
                ], 422);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Bulk stock adjustment successful.',
                'data' => [
                    'total_adjusted' => count($results),
                    'adjustments' => $results
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to perform bulk stock adjustment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * get inventory analytics
     */
    public function analytics(Request $request): JsonResponse
    {
        $analytics = [
            // basic counts
            'total_products' => Product::count(),
            'low_stock_count' => Inventory::lowStock()->count(),
            'out_of_stock_count' => Inventory::where('current_stock', '<=', 0)->count(),
            'well_stocked_count' => Inventory::whereRaw('current_stock > reserved_stock')->count(),

            // stock values
            'total_stock_value' => Inventory::selectRaw('SUM(current_stock * COALESCE(last_cost,0))')
                ->value('SUM(current_stock * COALESCE(last_cost, 0))') ?? 0,
            'low_stock_value' => $this->getLowStockValue(),
            'average_stock_per_product' => Inventory::avg('current_stock') ?? 0,

            // Recent activity
            'recent_updates' => Inventory::with('product')
                ->orderBy('last_updated_at', 'desc')
                ->take(10)
                ->get(['id', 'product_id', 'current_stock', 'last_updated_at']),

            // Category breakdown
            'stock_by_category' => $this->getStockByCategory(),

            // Stock status distribution
            'stock_status_distribution' => [
                'well_stocked' => Inventory::whereRaw('current_stock > (SELECT min_stock_level FROM products WHERE products.id = inventories.product_id)')->count(),
                'low_stock' => Inventory::lowStock()->count(),
                'out_of_stock' => Inventory::where('current_stock', '<=', 0)->count(),
                'reserved_stock' => Inventory::where('reserved_stock', '>', 0)->count(),
            ],

            // Top products by stock value
            'top_value_products' => Inventory::with('product')
                                           ->selectRaw('inventories.*, (current_stock * COALESCE(last_cost, 0)) as stock_value')
                                           ->orderBy('stock_value', 'desc')
                                           ->take(10)
                                           ->get()
        ];

        return response()->json([
            'status' => 'success',
            'data' => $analytics
        ]);
    }

    /**
     * Export inventory data
     */
    public function export(Request $request): JsonResponse
    {
        $query = Inventory::with(['product.category']);

        // Apply filters (same as index method)
        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('product', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if ($request->has('category_id')) {
            $query->whereHas('product', function($q) use ($request) {
                $q->where('category_id', $request->category_id);
            });
        }

        if ($request->has('stock_level')) {
            switch ($request->stock_level) {
                case 'low':
                    $query->lowStock();
                    break;
                case 'out':
                    $query->where('current_stock', '<=', 0);
                    break;
                case 'available':
                    $query->where('current_stock', '>', 0);
                    break;
            }
        }

        $inventories = $query->get();

        // Format data for export
        $exportData = $inventories->map(function ($inventory) {
            return [
                'product_id' => $inventory->product->id,
                'product_name' => $inventory->product->name,
                'sku' => $inventory->product->sku,
                'category' => $inventory->product->category->name ?? 'N/A',
                'current_stock' => $inventory->current_stock,
                'reserved_stock' => $inventory->reserved_stock,
                'available_stock' => $inventory->available_stock,
                'min_stock_level' => $inventory->product->min_stock_level,
                'last_cost' => $inventory->last_cost,
                'stock_value' => $inventory->current_stock * ($inventory->last_cost ?? 0),
                'stock_status' => $this->getStockStatus($inventory),
                'last_updated' => $inventory->last_updated_at->format('Y-m-d H:i:s')
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $exportData,
            'meta' => [
                'total_records' => $exportData->count(),
                'exported_at' => now()->format('Y-m-d H:i:s'),
                'total_stock_value' => $exportData->sum('stock_value')
            ]
        ]);
    }

    /**
     * Get stock status for an inventory item
     */
    private function getStockStatus(Inventory $inventory): string
    {
        if ($inventory->current_stock <= 0) {
            return 'out_of_stock';
        }

        if ($inventory->current_stock <= $inventory->product->min_stock_level) {
            return 'low_stock';
        }

        return 'well_stocked';
    }

    /**
     * Calculate days of stock remaining
     */
    private function calculateDaysOfStock(Inventory $inventory): int
    {
        // This is a simplified calculation
        // In real implementation, you'd use historical sales data
        $avgDailySales = 1; // Placeholder - calculate from order history
        
        if ($avgDailySales <= 0) {
            return 999; // Infinite days if no sales
        }

        return (int) ($inventory->available_stock / $avgDailySales);
    }

    /**
     * Calculate inventory turnover rate
     */
    private function calculateTurnoverRate(Inventory $inventory): float
    {
        // Simplified calculation
        // In real implementation, use: COGS / Average Inventory Value
        return 0.0; // Placeholder
    }

    /**
     * Calculate reorder point
     */
    private function calculateReorderPoint(Inventory $inventory): int
    {
        // Simplified: min_stock_level * safety factor
        return (int) ($inventory->product->min_stock_level * 1.5);
    }

    /**
     * Calculate recommended order quantity
     */
    private function calculateRecommendedOrderQuantity(Inventory $inventory): int
    {
        $reorderPoint = $this->calculateReorderPoint($inventory);
        $currentStock = $inventory->current_stock;
        
        return max(0, $reorderPoint - $currentStock);
    }

    /**
     * Get stock value for low stock items
     */
    private function getLowStockValue(): float
    {
        return Inventory::lowStock()
                       ->selectRaw('SUM(current_stock * COALESCE(last_cost, 0))')
                       ->value('SUM(current_stock * COALESCE(last_cost, 0))') ?? 0;
    }

    /**
     * Get stock breakdown by category
     */
    private function getStockByCategory(): array
    {
        return DB::table('inventories')
                ->join('products', 'inventories.product_id', '=', 'products.id')
                ->join('categories', 'products.category_id', '=', 'categories.id')
                ->select(
                    'categories.name as category_name',
                    DB::raw('COUNT(*) as product_count'),
                    DB::raw('SUM(inventories.current_stock) as total_stock'),
                    DB::raw('SUM(inventories.current_stock * COALESCE(inventories.last_cost, 0)) as total_value')
                )
                ->groupBy('categories.id', 'categories.name')
                ->get()
                ->toArray();
    }

    /**
     * Get stock history (placeholder - implement when you add stock_movements table)
     */
    private function getStockHistory(int $inventoryId): array
    {
        // Placeholder - implement when you create stock_movements table
        return [];
    }

    /**
     * Log stock movement (placeholder - implement when you add stock_movements table)
     */
    private function logStockMovement(int $inventoryId, array $data): void
    {
        // Placeholder - implement when you create stock_movements table
        // This would insert into stock_movements table
    }
}
