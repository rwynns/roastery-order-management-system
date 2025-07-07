<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Customer::query();

        // Search by name, email, or phone
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Filter by gender
        if ($request->has('gender')){
            $query->where('gender', $request->gender);
        }

        // Filter by customer tier
        if ($request->has('tier')) {
            $tier = $request->tier;
            switch (strtolower($tier)) {
                case 'vip':
                    $query->where('total_spent', '>=', 1000000);
                    break;
                case 'gold':
                    $query->where('total_spent', [500000, 999999]);
                    break;
                case 'silver':
                    $query->where('total_spent', [100000, 499999]);
                    break;
                case 'regular':
                    $query->where('total_spent', '<', 100000);
                    break;
            }
        }

        // Filter by date range
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Sort
        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');

        // Validate sort fields
        $allowedSortFields = ['name', 'email', 'total_spent', 'total_orders', 'created_at', 'last_order_at'];
        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'name';
        }

        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->get('per_page', 15);
        $customers = $query->paginate($perPage);

        // Add computed attributes
        $customers->getCollection()->transform(function ($customer) {
            $customer->tier = $customer->tier;
            $customer->average_order_value = $customer->average_order_value;
            $customer->purchase_frequency = $customer->purchase_frequency;

            return $customer;
        });

        return response()->json([
            'status' => 'success',
            'data' => $customers
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCustomerRequest $request): JsonResponse
    {
        try {
            $customer = Customer::create($request->validated());

            return response()->json([
                'status' => 'success',
                'message' => 'Customer created successfully',
                'data' => $customer
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create customer ',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Customer $customer): JsonResponse
    {
        // Load relationships
        $customer->load([
            'orders' => function ($query) {
                $query->latest()->take(10); // last 10 orders
            },
            'orders.orderItems.product'
        ]);

        // Add computed attributes
        $customer->tier = $customer->tier;
        $customer->average_order_value = $customer->average_order_value;
        $customer->purchase_frequency = $customer->purchase_frequency;

        // Get order statistics
        $orderStats = [
            'total_orders' => $customer->orders->count(),
            'completed_orders' => $customer->orders->where('status', 'completed')->count(),
            'pending_orders' => $customer->orders->where('status', 'pending')->count(),
            'cancelled_orders' => $customer->orders->where('status', 'cancelled')->count(),
            'last_order_at' => $customer->orders->first()?->created_at,
            'favorite_products' => $this->getFavoriteProducts($customer->id)
        ];

        return response()->json([
            'status' => 'success',
            'data' => [
                'customer' => $customer,
                'order_statistics' => $orderStats
            ]
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCustomerRequest $request, Customer $customer)
    {
        try {
            $customer->update($request->validated());

            return response()->json([
                'status' => 'success',
                'message' => 'Customer updated successfully',
                'data' => $customer
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update customer',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Customer $customer): JsonResponse
    {
        try {
            // Check if customer has orders
            if ($customer->orders()->exists()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot delete customer with existing orders. Consider deactivating instead.'
                ], 422);
            }

            $customer->delete();
            return response()->json([
                'status' => 'success',
                'message' => 'Customer deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete customer',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get customer analytics/statistics
     */
    public function analytics(Request $request): JsonResponse
    {
        $analytics = [
            'total_customers' => Customer::count(),
            'active_customers' => Customer::where('is_active', true)->count(),
            'inactive_customers' => Customer::where('is_active', false)->count(),

            // Customer tiers
            'tier_distribution' => [
                'vip' => Customer::where('total_spent', '>=', 1000000)->count(),
                'gold' => Customer::whereBetween('total_spent', [500000, 999999])->count(),
                'silver' => Customer::whereBetween('total_spent', [100000, 499999])->count(),
                'regular' => Customer::where('total_spent', '<', 100000)->count(),
            ],

            // New customers this month
            'new_customers_this_month' => Customer::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),

            // Top spending customers
            'top_customers' => Customer::orderBy('total_spent', 'desc')
                ->take(5)
                ->get(['id', 'name', 'total_spent', 'total_orders']),

            // Average order value across all customers
            'average_customer_value' => Customer::avg('total_spent'),
            'average_orders_per_customer' => Customer::avg('total_orders'),

            // Gender distribution
            'gender_distribution' => Customer::select('gender', DB::raw('count(*) as count'))
            ->groupBy('gender')
            ->pluck('count','gender')
            ->toArray()
        ];

        return response()->json([
            'status' => 'success',
            'data' => $analytics
        ]);
    }

    /**
     * Get customer's order history
     */
    public function orderHistory(Customer $customer, Request $request): JsonResponse
    {
        $query = $customer->orders()->with(['orderItems.product', 'payment']);

        // Filter by status
        if ($request->has('status')){
            $query->where('status', $request->status);
        }

        //Filter by date range
        if ($request->has('date_from')){
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $orders = $query->latest()->paginate($request->get('per_page', 10));

        return response()->json([
            'status' => 'success',
            'data' => $orders
        ]);
    }

    /**
     * Bulk update customer status
     */
    public function bulkUpdateStatus(Request $request): JsonResponse
    {
        $request->validate([
            'customers_ids' => 'required|array',
            'customers_ids.*' => 'exists:customers,id',
            'is_active' => 'required|boolean'
        ]);

        try {
            Customer::whereIn('id', $request->customers_ids)
                ->update(['is_active' => $request->is_active]);

            $action = $request->is_active ? 'activated' : 'deactivated';

            return response()->json([
                'status' => 'success',
                'message' => "Customers {$action} successfully"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update customer status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export customers data
     */
    public function export(Request $request): JsonResponse
    {
        $query = Customer::query();

        // Apply same filters as index
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $customers = $query->get();

        // Add computed fields for export
        $exportData = $customers->map(function ($customer) {
            return [
                'ID' => $customer->id,
                'Name' => $customer->name,
                'Email' => $customer->email,
                'Phone' => $customer->phone,
                'address' => $customer->address,
                'date_of_birth' => $customer->date_of_birth?->format('Y-m-d'),
                'gender' => $customer->gender,
                'total_spent' => $customer->total_spent,
                'total_orders' => $customer->total_orders,
                'tier' => $customer->tier,
                'average_order_value' => $customer->average_order_value,
                'is_active' => $customer->is_active ? 'Yes' : 'No',
                'created_at' => $customer->created_at->format('Y-m-d H:i:s'),
                'last_order_at' => $customer->last_order_at?->format('Y-m-d H:i:s'),
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $exportData,
            'meta' => [
                'total_records' => $exportData->count(),
                'exported_at' => now()->format('Y-m-d H:i:s')
            ]
        ]);
    }

    /**
     * Get customer's favorite products
     */
    private function getFavoriteProducts($customerId, $limit = 5)
    {
        return DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('orders.customer_id', $customerId)
            ->where('orders.status', 'completed')
            ->select(
                'products.id',
                'products.name',
                DB::raw('SUM(order_items.quantity) as total_quantity'),
                DB::raw('COUNT(DISTINCT orders.id) as order_count')
            )
            ->groupBy('products.id', 'products.name')
            ->orderBy('total_quantity', 'desc')
            ->take($limit)
            ->get();
    }
}
