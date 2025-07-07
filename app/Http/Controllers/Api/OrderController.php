<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\Customer;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Order::with(['customer', 'user', 'orderItems.product', 'payments']);

        // Search by order number or customer name
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($customerQuery) use ($search) {
                      $customerQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by payment status
        if ($request->has('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        // Filter by customer
        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        // Filter by cashier/user
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by date range
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Filter by today
        if ($request->has('today') && $request->boolean('today')) {
            $query->whereDate('created_at', today());
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->get('per_page', 15);
        $orders = $query->paginate($perPage);

        // Add computed attributes
        $orders->getCollection()->transform(function ($order) {
            $order->total_paid = $order->total_paid;
            $order->remaining_amount = $order->remaining_amount;
            $order->is_fully_paid = $order->is_fully_paid;
            return $order;
        });

        return response()->json([
            'status' => 'success',
            'data' => $orders
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreOrderRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Generate order number
            $orderNumber = $this->generateOrderNumber();

            // Calculate totals
            $subtotal = 0;
            $items = $request->items;

            // Validate stock and calculate subtotal
            foreach ($items as $item) {
                $product = Product::find($item['product_id']);
                
                if (!$product) {
                    throw new \Exception("Product not found");
                }

                if (!$product->isInStock($item['quantity'])) {
                    throw new \Exception("Insufficient stock for product: {$product->name}");
                }

                $subtotal += $item['quantity'] * $item['unit_price'];
            }

            // Calculate tax and total
            $taxRate = $request->get('tax_rate', 10) / 100;
            $discountAmount = $request->get('discount_amount', 0);
            $taxAmount = ($subtotal - $discountAmount) * $taxRate;
            $totalAmount = $subtotal - $discountAmount + $taxAmount;

            // Create order
            $order = Order::create([
                'order_number' => $orderNumber,
                'customer_id' => $request->customer_id,
                'user_id' => $request->user()->id,
                'status' => 'pending',
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'discount_amount' => $discountAmount,
                'total_amount' => $totalAmount,
                'payment_status' => 'pending',
                'notes' => $request->notes
            ]);

            // Create order items and reduce inventory
            foreach ($items as $item) {
                $product = Product::find($item['product_id']);
                
                // Create order item
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $item['quantity'] * $item['unit_price']
                ]);

                // Reduce inventory if product tracks stock
                if ($product->track_stock && $product->inventory) {
                    $product->inventory->reduceStock($item['quantity']);
                }
            }

            // Create payment if amount_paid is provided
            if ($request->has('amount_paid') && $request->amount_paid > 0) {
                $this->processPayment($order, $request);
            }

            // Update customer statistics if customer exists
            if ($order->customer_id) {
                $customer = Customer::find($order->customer_id);
                if ($customer) {
                    $customer->updateStatistics();
                }
            }

            DB::commit();

            // Load relationships for response
            $order->load(['customer', 'user', 'orderItems.product', 'payments']);

            return response()->json([
                'status' => 'success',
                'message' => 'Order created successfully',
                'data' => $order
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Order $order): JsonResponse
    {
        $order->load([
            'customer',
            'user',
            'orderItems.product.category',
            'payments'
        ]);

        // Add computed attributes
        $order->total_paid = $order->total_paid;
        $order->remaining_amount = $order->remaining_amount;
        $order->is_fully_paid = $order->is_fully_paid;

        return response()->json([
            'status' => 'success',
            'data' => $order
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateOrderRequest $request, Order $order): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Check if order can be updated
            if ($order->status === 'completed') {
                throw new \Exception('Cannot update completed order');
            }

            // Update basic order info
            $updateData = $request->only(['status', 'customer_id', 'discount_amount', 'notes']);
            
            // Handle status change
            if ($request->has('status')) {
                if ($request->status === 'completed' && $order->status !== 'completed') {
                    $updateData['completed_at'] = now();
                }
                
                if ($request->status === 'cancelled' && $order->status !== 'cancelled') {
                    // Restore inventory when order is cancelled
                    $this->restoreInventory($order);
                }
            }

            // Update items if provided
            if ($request->has('items')) {
                $this->updateOrderItems($order, $request->items);
                
                // Recalculate totals
                $totals = $this->calculateOrderTotals($order, $request->get('discount_amount', $order->discount_amount));
                $updateData = array_merge($updateData, $totals);
            }

            $order->update($updateData);

            // Update customer statistics
            if ($order->customer_id) {
                $customer = Customer::find($order->customer_id);
                if ($customer) {
                    $customer->updateStatistics();
                }
            }

            DB::commit();

            $order->load(['customer', 'user', 'orderItems.product', 'payments']);

            return response()->json([
                'status' => 'success',
                'message' => 'Order updated successfully',
                'data' => $order
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Order $order): JsonResponse
    {
        try {
            // Only allow deletion of pending or cancelled orders
            if (!in_array($order->status, ['pending', 'cancelled'])) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot delete order with status: ' . $order->status
                ], 422);
            }

            DB::beginTransaction();

            // Restore inventory if needed
            if ($order->status !== 'cancelled') {
                $this->restoreInventory($order);
            }

            // Delete related records
            $order->payments()->delete();
            $order->orderItems()->delete();
            $order->delete();

            // Update customer statistics
            if ($order->customer_id) {
                $customer = Customer::find($order->customer_id);
                if ($customer) {
                    $customer->updateStatistics();
                }
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Order deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add payment to order
     */
    public function addPayment(Request $request, Order $order): JsonResponse
    {
        $request->validate([
            'payment_method' => 'required|string|in:cash,card,transfer,ewallet',
            'amount' => 'required|numeric|min:0.01',
            'reference_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:500'
        ]);

        try {
            DB::beginTransaction();

            $amount = $request->amount;
            $remainingAmount = $order->remaining_amount;

            if ($amount > $remainingAmount) {
                $changeAmount = $amount - $remainingAmount;
                $amount = $remainingAmount;
            } else {
                $changeAmount = 0;
            }

            // Create payment record
            $payment = Payment::create([
                'order_id' => $order->id,
                'payment_method' => $request->payment_method,
                'amount' => $amount,
                'change_amount' => $changeAmount,
                'reference_number' => $request->reference_number,
                'status' => 'completed',
                'notes' => $request->notes,
                'paid_at' => now()
            ]);

            // Update order payment status
            $totalPaid = $order->total_paid + $amount;
            if ($totalPaid >= $order->total_amount) {
                $order->update(['payment_status' => 'paid']);
                
                // Auto-complete order if fully paid
                if ($order->status === 'pending') {
                    $order->update([
                        'status' => 'completed',
                        'completed_at' => now()
                    ]);
                }
            } else {
                $order->update(['payment_status' => 'partial']);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Payment added successfully',
                'data' => [
                    'payment' => $payment,
                    'order' => $order->fresh(['payments']),
                    'change_amount' => $changeAmount
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to add payment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get order analytics
     */
    public function analytics(Request $request): JsonResponse
    {
        $today = now()->startOfDay();
        $startOfMonth = now()->startOfMonth();

        $analytics = [
            // Today's stats
            'today' => [
                'total_orders' => Order::whereDate('created_at', $today)->count(),
                'total_sales' => Order::whereDate('created_at', $today)
                                    ->where('status', 'completed')
                                    ->sum('total_amount'),
                'pending_orders' => Order::whereDate('created_at', $today)
                                        ->where('status', 'pending')
                                        ->count(),
                'completed_orders' => Order::whereDate('created_at', $today)
                                          ->where('status', 'completed')
                                          ->count(),
            ],

            // This month's stats
            'this_month' => [
                'total_orders' => Order::whereDate('created_at', '>=', $startOfMonth)->count(),
                'total_sales' => Order::whereDate('created_at', '>=', $startOfMonth)
                                    ->where('status', 'completed')
                                    ->sum('total_amount'),
                'average_order_value' => Order::whereDate('created_at', '>=', $startOfMonth)
                                             ->where('status', 'completed')
                                             ->avg('total_amount'),
            ],

            // Status distribution
            'status_distribution' => Order::select('status', DB::raw('count(*) as count'))
                                         ->groupBy('status')
                                         ->pluck('count', 'status')
                                         ->toArray(),

            // Payment method distribution
            'payment_methods' => Payment::select('payment_method', DB::raw('sum(amount) as total'))
                                      ->groupBy('payment_method')
                                      ->pluck('total', 'payment_method')
                                      ->toArray(),

            // Top selling products
            'top_products' => DB::table('order_items')
                               ->join('products', 'order_items.product_id', '=', 'products.id')
                               ->join('orders', 'order_items.order_id', '=', 'orders.id')
                               ->where('orders.status', 'completed')
                               ->select(
                                   'products.name',
                                   DB::raw('SUM(order_items.quantity) as total_sold'),
                                   DB::raw('SUM(order_items.total_price) as total_revenue')
                               )
                               ->groupBy('products.id', 'products.name')
                               ->orderBy('total_sold', 'desc')
                               ->take(5)
                               ->get(),

            // Recent orders
            'recent_orders' => Order::with(['customer', 'user'])
                                   ->latest()
                                   ->take(5)
                                   ->get(['id', 'order_number', 'customer_id', 'user_id', 'status', 'total_amount', 'created_at'])
        ];

        return response()->json([
            'status' => 'success',
            'data' => $analytics
        ]);
    }

    /**
     * Generate unique order number
     */
    private function generateOrderNumber(): string
    {
        $prefix = 'ORD';
        $date = now()->format('Ymd');
        $sequence = Order::whereDate('created_at', today())->count() + 1;
        
        return $prefix . $date . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Process payment for order
     */
    private function processPayment(Order $order, $request): void
    {
        $amountPaid = $request->amount_paid;
        $changeAmount = 0;

        if ($amountPaid >= $order->total_amount) {
            $changeAmount = $amountPaid - $order->total_amount;
            $amountPaid = $order->total_amount;
            $order->update(['payment_status' => 'paid']);
        } else {
            $order->update(['payment_status' => 'partial']);
        }

        Payment::create([
            'order_id' => $order->id,
            'payment_method' => $request->get('payment_method', 'cash'),
            'amount' => $amountPaid,
            'change_amount' => $changeAmount,
            'status' => 'completed',
            'paid_at' => now()
        ]);
    }

    /**
     * Restore inventory when order is cancelled
     */
    private function restoreInventory(Order $order): void
    {
        foreach ($order->orderItems as $item) {
            $product = $item->product;
            if ($product->track_stock && $product->inventory) {
                $product->inventory->increment('current_stock', $item->quantity);
                $product->inventory->update(['last_updated_at' => now()]);
            }
        }
    }

    /**
     * Update order items
     */
    private function updateOrderItems(Order $order, array $items): void
    {
        // Delete existing items
        $order->orderItems()->delete();

        // Create new items
        foreach ($items as $item) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'total_price' => $item['quantity'] * $item['unit_price']
            ]);
        }
    }

    /**
     * Calculate order totals
     */
    private function calculateOrderTotals(Order $order, float $discountAmount = 0): array
    {
        $subtotal = $order->orderItems()->sum('total_price');
        $taxRate = 0.10; // 10% tax
        $taxAmount = ($subtotal - $discountAmount) * $taxRate;
        $totalAmount = $subtotal - $discountAmount + $taxAmount;

        return [
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'discount_amount' => $discountAmount,
            'total_amount' => $totalAmount
        ];
    }
}
