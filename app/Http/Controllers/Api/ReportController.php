<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Inventory;
use App\Models\OrderItem;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportController extends Controller
{
    /**
     * Get sales report
     */
    public function salesReport(Request $request): JsonResponse
    {
        $request->validate([
            'period' => 'nullable|in:today,yesterday,this_week,last_week,this_month,last_month,this_year,custom',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'group_by' => 'nullable|in:day,week,month,year',
            'status' => 'nullable|in:pending,processing,completed,cancelled'
        ]);

        $period = $request->get('period', 'today');
        $groupBy = $request->get('group_by', 'day');
        $status = $request->get('status', 'completed');

        // Get date range
        $dateRange = $this->getDateRange($period, $request->start_date, $request->end_date);

        // Build base query
        $query = Order::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
                     ->where('status', $status);

        // Get summary data
        $summary = [
            'total_orders' => (clone $query)->count(),
            'total_sales' => (clone $query)->sum('total_amount'),
            'total_tax' => (clone $query)->sum('tax_amount'),
            'total_discount' => (clone $query)->sum('discount_amount'),
            'average_order_value' => (clone $query)->avg('total_amount') ?? 0,
            'total_items_sold' => $this->getTotalItemsSold($dateRange, $status),
        ];

        // Get sales trend data
        $salesTrend = $this->getSalesTrend($dateRange, $groupBy, $status);

        // Get top products
        $topProducts = $this->getTopSellingProducts($dateRange, $status, 10);

        // Get payment method breakdown
        $paymentMethods = $this->getPaymentMethodBreakdown($dateRange, $status);

        // Get hourly sales (for today/yesterday)
        $hourlySales = null;
        if (in_array($period, ['today', 'yesterday'])) {
            $hourlySales = $this->getHourlySales($dateRange, $status);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'period' => $period,
                'date_range' => [
                    'start' => $dateRange['start']->format('Y-m-d'),
                    'end' => $dateRange['end']->format('Y-m-d')
                ],
                'summary' => $summary,
                'sales_trend' => $salesTrend,
                'top_products' => $topProducts,
                'payment_methods' => $paymentMethods,
                'hourly_sales' => $hourlySales
            ]
        ]);
    }

    /**
     * Get inventory report
     */
    public function inventoryReport(Request $request): JsonResponse
    {
        $request->validate([
            'category_id' => 'nullable|exists:categories,id',
            'stock_status' => 'nullable|in:all,low_stock,out_of_stock,well_stocked',
            'sort_by' => 'nullable|in:name,current_stock,stock_value,turnover',
            'sort_order' => 'nullable|in:asc,desc'
        ]);

        $query = Inventory::with(['product.category']);

        // Filter by category
        if ($request->has('category_id')) {
            $query->whereHas('product', function($q) use ($request) {
                $q->where('category_id', $request->category_id);
            });
        }

        // Filter by stock status
        switch ($request->get('stock_status', 'all')) {
            case 'low_stock':
                $query->lowStock();
                break;
            case 'out_of_stock':
                $query->where('current_stock', '<=', 0);
                break;
            case 'well_stocked':
                $query->whereRaw('current_stock > (SELECT min_stock_level FROM products WHERE products.id = inventories.product_id)');
                break;
        }

        // Get inventory items
        $inventories = $query->get();

        // Calculate inventory metrics
        $inventoryData = $inventories->map(function ($inventory) {
            $stockValue = $inventory->current_stock * ($inventory->last_cost ?? 0);
            $turnoverRate = $this->calculateProductTurnover($inventory->product_id);
            
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
                'stock_value' => $stockValue,
                'turnover_rate' => $turnoverRate,
                'stock_status' => $this->getStockStatus($inventory),
                'days_of_stock' => $this->calculateDaysOfStock($inventory),
                'reorder_needed' => $inventory->current_stock <= $inventory->product->min_stock_level
            ];
        });

        // Sort data
        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');
        
        $inventoryData = $inventoryData->sortBy($sortBy, SORT_REGULAR, $sortOrder === 'desc');

        // Calculate summary
        $summary = [
            'total_products' => $inventoryData->count(),
            'total_stock_value' => $inventoryData->sum('stock_value'),
            'low_stock_items' => $inventoryData->where('stock_status', 'low_stock')->count(),
            'out_of_stock_items' => $inventoryData->where('stock_status', 'out_of_stock')->count(),
            'reorder_needed' => $inventoryData->where('reorder_needed', true)->count(),
            'average_turnover_rate' => $inventoryData->avg('turnover_rate')
        ];

        // Category breakdown
        $categoryBreakdown = $inventoryData->groupBy('category')->map(function ($items, $category) {
            return [
                'category' => $category,
                'product_count' => $items->count(),
                'total_stock' => $items->sum('current_stock'),
                'total_value' => $items->sum('stock_value'),
                'low_stock_count' => $items->where('stock_status', 'low_stock')->count()
            ];
        })->values();

        return response()->json([
            'status' => 'success',
            'data' => [
                'summary' => $summary,
                'category_breakdown' => $categoryBreakdown,
                'inventory_details' => $inventoryData->values(),
                'generated_at' => now()->format('Y-m-d H:i:s')
            ]
        ]);
    }

    /**
     * Get customer report
     */
    public function customerReport(Request $request): JsonResponse
    {
        $request->validate([
            'period' => 'nullable|in:this_month,last_month,this_year,last_year,all_time',
            'tier' => 'nullable|in:vip,gold,silver,regular',
            'sort_by' => 'nullable|in:name,total_spent,total_orders,last_order',
            'sort_order' => 'nullable|in:asc,desc'
        ]);

        $period = $request->get('period', 'all_time');
        $query = Customer::query();

        // Filter by tier
        if ($request->has('tier')) {
            $tier = $request->tier;
            switch (strtolower($tier)) {
                case 'vip':
                    $query->where('total_spent', '>=', 1000000);
                    break;
                case 'gold':
                    $query->whereBetween('total_spent', [500000, 999999]);
                    break;
                case 'silver':
                    $query->whereBetween('total_spent', [100000, 499999]);
                    break;
                case 'regular':
                    $query->where('total_spent', '<', 100000);
                    break;
            }
        }

        // Get customers with computed attributes
        $customers = $query->get()->map(function ($customer) {
            return [
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'total_spent' => $customer->total_spent,
                'total_orders' => $customer->total_orders,
                'average_order_value' => $customer->average_order_value,
                'tier' => $customer->tier,
                'last_order_at' => $customer->last_order_at?->format('Y-m-d H:i:s'),
                'purchase_frequency' => $customer->purchase_frequency,
                'is_active' => $customer->is_active,
                'days_since_last_order' => $customer->last_order_at ? 
                    now()->diffInDays($customer->last_order_at) : null
            ];
        });

        // Sort customers
        $sortBy = $request->get('sort_by', 'total_spent');
        $sortOrder = $request->get('sort_order', 'desc');
        $customers = $customers->sortBy($sortBy, SORT_REGULAR, $sortOrder === 'desc');

        // Calculate summary
        $summary = [
            'total_customers' => $customers->count(),
            'active_customers' => $customers->where('is_active', true)->count(),
            'total_customer_value' => $customers->sum('total_spent'),
            'average_customer_value' => $customers->avg('total_spent'),
            'average_orders_per_customer' => $customers->avg('total_orders'),
            'tier_distribution' => [
                'vip' => $customers->where('tier', 'VIP')->count(),
                'gold' => $customers->where('tier', 'Gold')->count(),
                'silver' => $customers->where('tier', 'Silver')->count(),
                'regular' => $customers->where('tier', 'Regular')->count()
            ]
        ];

        // Top customers
        $topCustomers = $customers->take(10)->values();

        // Customer acquisition (new customers by month)
        $customerAcquisition = $this->getCustomerAcquisition();

        // Customer retention analysis
        $retentionAnalysis = $this->getCustomerRetention();

        return response()->json([
            'status' => 'success',
            'data' => [
                'summary' => $summary,
                'top_customers' => $topCustomers,
                'customer_acquisition' => $customerAcquisition,
                'retention_analysis' => $retentionAnalysis,
                'all_customers' => $customers->values()
            ]
        ]);
    }

    /**
     * Get financial report
     */
    public function financialReport(Request $request): JsonResponse
    {
        $request->validate([
            'period' => 'nullable|in:today,this_week,this_month,this_year,custom',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date'
        ]);

        $period = $request->get('period', 'this_month');
        $dateRange = $this->getDateRange($period, $request->start_date, $request->end_date);

        // Revenue calculations
        $revenue = [
            'gross_sales' => Order::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
                                 ->where('status', 'completed')
                                 ->sum('subtotal'),
            'tax_collected' => Order::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
                                   ->where('status', 'completed')
                                   ->sum('tax_amount'),
            'discounts_given' => Order::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
                                     ->where('status', 'completed')
                                     ->sum('discount_amount'),
            'net_sales' => Order::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
                               ->where('status', 'completed')
                               ->sum('total_amount')
        ];

        // Cost of goods sold (simplified)
        $cogs = $this->calculateCOGS($dateRange);

        // Profit calculations
        $grossProfit = $revenue['net_sales'] - $cogs;
        $grossMargin = $revenue['net_sales'] > 0 ? ($grossProfit / $revenue['net_sales']) * 100 : 0;

        // Payment method breakdown
        $paymentBreakdown = Payment::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
                                  ->where('status', 'completed')
                                  ->groupBy('payment_method')
                                  ->selectRaw('payment_method, SUM(amount) as total')
                                  ->pluck('total', 'payment_method')
                                  ->toArray();

        // Monthly comparison (if applicable)
        $monthlyComparison = null;
        if ($period === 'this_month') {
            $lastMonth = $this->getDateRange('last_month');
            $lastMonthSales = Order::whereBetween('created_at', [$lastMonth['start'], $lastMonth['end']])
                                  ->where('status', 'completed')
                                  ->sum('total_amount');
            
            $growthRate = $lastMonthSales > 0 ? 
                (($revenue['net_sales'] - $lastMonthSales) / $lastMonthSales) * 100 : 0;

            $monthlyComparison = [
                'current_month' => $revenue['net_sales'],
                'last_month' => $lastMonthSales,
                'growth_rate' => round($growthRate, 2)
            ];
        }

        // Daily sales trend
        $dailySalesTrend = $this->getDailySalesTrend($dateRange);

        return response()->json([
            'status' => 'success',
            'data' => [
                'period' => $period,
                'date_range' => [
                    'start' => $dateRange['start']->format('Y-m-d'),
                    'end' => $dateRange['end']->format('Y-m-d')
                ],
                'revenue' => $revenue,
                'costs' => [
                    'cost_of_goods_sold' => $cogs
                ],
                'profit' => [
                    'gross_profit' => $grossProfit,
                    'gross_margin_percentage' => round($grossMargin, 2)
                ],
                'payment_breakdown' => $paymentBreakdown,
                'monthly_comparison' => $monthlyComparison,
                'daily_sales_trend' => $dailySalesTrend
            ]
        ]);
    }

    /**
     * Get product performance report
     */
    public function productPerformanceReport(Request $request): JsonResponse
    {
        $request->validate([
            'period' => 'nullable|in:this_week,this_month,this_year,custom',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'category_id' => 'nullable|exists:categories,id',
            'sort_by' => 'nullable|in:quantity_sold,revenue,profit_margin',
            'limit' => 'nullable|integer|min:1|max:100'
        ]);

        $period = $request->get('period', 'this_month');
        $dateRange = $this->getDateRange($period, $request->start_date, $request->end_date);
        $sortBy = $request->get('sort_by', 'revenue');
        $limit = $request->get('limit', 20);

        // Build query for order items within date range
        $query = OrderItem::whereHas('order', function($q) use ($dateRange) {
            $q->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
              ->where('status', 'completed');
        })->with(['product.category']);

        // Filter by category
        if ($request->has('category_id')) {
            $query->whereHas('product', function($q) use ($request) {
                $q->where('category_id', $request->category_id);
            });
        }

        // Get product performance data
        $productPerformance = $query->get()
            ->groupBy('product_id')
            ->map(function ($items, $productId) {
                $product = $items->first()->product;
                $totalQuantity = $items->sum('quantity');
                $totalRevenue = $items->sum('total_price');
                $averagePrice = $items->avg('unit_price');
                $costOfGoods = $totalQuantity * ($product->cost ?? 0);
                $profit = $totalRevenue - $costOfGoods;
                $profitMargin = $totalRevenue > 0 ? ($profit / $totalRevenue) * 100 : 0;

                return [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'sku' => $product->sku,
                    'category' => $product->category->name ?? 'N/A',
                    'quantity_sold' => $totalQuantity,
                    'revenue' => $totalRevenue,
                    'average_selling_price' => $averagePrice,
                    'cost_of_goods' => $costOfGoods,
                    'profit' => $profit,
                    'profit_margin_percentage' => round($profitMargin, 2),
                    'current_stock' => $product->inventory->current_stock ?? 0
                ];
            })
            ->sortByDesc($sortBy)
            ->take($limit)
            ->values();

        // Calculate summary
        $summary = [
            'total_products_sold' => $productPerformance->count(),
            'total_quantity_sold' => $productPerformance->sum('quantity_sold'),
            'total_revenue' => $productPerformance->sum('revenue'),
            'total_profit' => $productPerformance->sum('profit'),
            'average_profit_margin' => $productPerformance->avg('profit_margin_percentage')
        ];

        // Category performance
        $categoryPerformance = $productPerformance->groupBy('category')->map(function ($products, $category) {
            return [
                'category' => $category,
                'product_count' => $products->count(),
                'total_quantity' => $products->sum('quantity_sold'),
                'total_revenue' => $products->sum('revenue'),
                'total_profit' => $products->sum('profit'),
                'average_margin' => $products->avg('profit_margin_percentage')
            ];
        })->values();

        return response()->json([
            'status' => 'success',
            'data' => [
                'period' => $period,
                'date_range' => [
                    'start' => $dateRange['start']->format('Y-m-d'),
                    'end' => $dateRange['end']->format('Y-m-d')
                ],
                'summary' => $summary,
                'top_products' => $productPerformance,
                'category_performance' => $categoryPerformance
            ]
        ]);
    }

    /**
     * Export report as JSON (placeholder for CSV/PDF export)
     */
    public function exportReport(Request $request): JsonResponse
    {
        $request->validate([
            'report_type' => 'required|in:sales,inventory,customer,financial,product_performance',
            'format' => 'nullable|in:json,csv,pdf',
            'period' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date'
        ]);

        $reportType = $request->report_type;
        $format = $request->get('format', 'json');

        // Get report data based on type
        switch ($reportType) {
            case 'sales':
                $reportData = $this->salesReport($request)->getData();
                break;
            case 'inventory':
                $reportData = $this->inventoryReport($request)->getData();
                break;
            case 'customer':
                $reportData = $this->customerReport($request)->getData();
                break;
            case 'financial':
                $reportData = $this->financialReport($request)->getData();
                break;
            case 'product_performance':
                $reportData = $this->productPerformanceReport($request)->getData();
                break;
            default:
                return response()->json(['status' => 'error', 'message' => 'Invalid report type'], 400);
        }

        // For now, return JSON. Later you can implement CSV/PDF export
        return response()->json([
            'status' => 'success',
            'data' => $reportData,
            'meta' => [
                'report_type' => $reportType,
                'format' => $format,
                'exported_at' => now()->format('Y-m-d H:i:s'),
                'generated_by' => $request->user()->name ?? 'System'
            ]
        ]);
    }

    // PRIVATE HELPER METHODS

    /**
     * Get date range based on period
     */
    private function getDateRange(string $period, ?string $startDate = null, ?string $endDate = null): array
    {
        $now = Carbon::now();

        switch ($period) {
            case 'today':
                return ['start' => $now->startOfDay(), 'end' => $now->endOfDay()];
            case 'yesterday':
                $yesterday = $now->subDay();
                return ['start' => $yesterday->startOfDay(), 'end' => $yesterday->endOfDay()];
            case 'this_week':
                return ['start' => $now->startOfWeek(), 'end' => $now->endOfWeek()];
            case 'last_week':
                $lastWeek = $now->subWeek();
                return ['start' => $lastWeek->startOfWeek(), 'end' => $lastWeek->endOfWeek()];
            case 'this_month':
                return ['start' => $now->startOfMonth(), 'end' => $now->endOfMonth()];
            case 'last_month':
                $lastMonth = $now->subMonth();
                return ['start' => $lastMonth->startOfMonth(), 'end' => $lastMonth->endOfMonth()];
            case 'this_year':
                return ['start' => $now->startOfYear(), 'end' => $now->endOfYear()];
            case 'custom':
                return [
                    'start' => $startDate ? Carbon::parse($startDate)->startOfDay() : $now->startOfMonth(),
                    'end' => $endDate ? Carbon::parse($endDate)->endOfDay() : $now->endOfDay()
                ];
            default:
                return ['start' => $now->startOfDay(), 'end' => $now->endOfDay()];
        }
    }

    /**
     * Get total items sold in period
     */
    private function getTotalItemsSold(array $dateRange, string $status): int
    {
        return OrderItem::whereHas('order', function($q) use ($dateRange, $status) {
            $q->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
              ->where('status', $status);
        })->sum('quantity');
    }

    /**
     * Get sales trend data
     */
    private function getSalesTrend(array $dateRange, string $groupBy, string $status): array
    {
        $format = match($groupBy) {
            'day' => '%Y-%m-%d',
            'week' => '%Y-%u',
            'month' => '%Y-%m',
            'year' => '%Y',
            default => '%Y-%m-%d'
        };

        return Order::selectRaw("DATE_FORMAT(created_at, '{$format}') as period, SUM(total_amount) as sales, COUNT(*) as orders")
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->where('status', $status)
            ->groupBy('period')
            ->orderBy('period')
            ->get()
            ->toArray();
    }

    /**
     * Get top selling products
     */
    private function getTopSellingProducts(array $dateRange, string $status, int $limit): array
    {
        return OrderItem::selectRaw('product_id, products.name, SUM(quantity) as total_sold, SUM(total_price) as revenue')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->whereHas('order', function($q) use ($dateRange, $status) {
                $q->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
                  ->where('status', $status);
            })
            ->groupBy('product_id', 'products.name')
            ->orderBy('total_sold', 'desc')
            ->take($limit)
            ->get()
            ->toArray();
    }

    /**
     * Get payment method breakdown
     */
    private function getPaymentMethodBreakdown(array $dateRange, string $status): array
    {
        return Payment::selectRaw('payment_method, SUM(amount) as total, COUNT(*) as count')
            ->whereHas('order', function($q) use ($dateRange, $status) {
                $q->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
                  ->where('status', $status);
            })
            ->where('status', 'completed')
            ->groupBy('payment_method')
            ->get()
            ->toArray();
    }

    /**
     * Get hourly sales
     */
    private function getHourlySales(array $dateRange, string $status): array
    {
        return Order::selectRaw('HOUR(created_at) as hour, SUM(total_amount) as sales, COUNT(*) as orders')
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->where('status', $status)
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->toArray();
    }

    /**
     * Calculate COGS (simplified)
     */
    private function calculateCOGS(array $dateRange): float
    {
        return OrderItem::whereHas('order', function($q) use ($dateRange) {
            $q->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
              ->where('status', 'completed');
        })
        ->join('products', 'order_items.product_id', '=', 'products.id')
        ->selectRaw('SUM(order_items.quantity * COALESCE(products.cost, 0)) as total_cost')
        ->value('total_cost') ?? 0;
    }

    /**
     * Get customer acquisition data
     */
    private function getCustomerAcquisition(): array
    {
        return Customer::selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as new_customers')
            ->where('created_at', '>=', now()->subMonths(12))
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->toArray();
    }

    /**
     * Get customer retention analysis
     */
    private function getCustomerRetention(): array
    {
        $totalCustomers = Customer::count();
        $activeCustomers = Customer::whereHas('orders', function($q) {
            $q->where('created_at', '>=', now()->subMonth());
        })->count();
        
        $retentionRate = $totalCustomers > 0 ? ($activeCustomers / $totalCustomers) * 100 : 0;

        return [
            'total_customers' => $totalCustomers,
            'active_customers_last_month' => $activeCustomers,
            'retention_rate_percentage' => round($retentionRate, 2)
        ];
    }

    /**
     * Get daily sales trend
     */
    private function getDailySalesTrend(array $dateRange): array
    {
        return Order::selectRaw('DATE(created_at) as date, SUM(total_amount) as sales')
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->where('status', 'completed')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();
    }

    /**
     * Calculate product turnover rate
     */
    private function calculateProductTurnover(int $productId): float
    {
        // Simplified calculation - you can make this more sophisticated
        $soldLast30Days = OrderItem::whereHas('order', function($q) {
            $q->where('created_at', '>=', now()->subDays(30))
              ->where('status', 'completed');
        })
        ->where('product_id', $productId)
        ->sum('quantity');

        $avgStock = Inventory::where('product_id', $productId)->value('current_stock') ?? 0;
        
        return $avgStock > 0 ? round($soldLast30Days / $avgStock, 2) : 0;
    }

    /**
     * Get stock status
     */
    private function getStockStatus($inventory): string
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
     * Calculate days of stock
     */
    private function calculateDaysOfStock($inventory): int
    {
        // Simplified - use historical sales data in real implementation
        $avgDailySales = 1;
        return $avgDailySales > 0 ? (int)($inventory->available_stock / $avgDailySales) : 999;
    }
}