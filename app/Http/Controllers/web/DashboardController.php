<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;

class DashboardController extends Controller
{
    private function getToken()
    {
        return Session::get('auth_token');
    }

    private function apiCall($endpoint, $method = 'GET', $data = null)
    {
        try {
            $token = $this->getToken();
            $request = Http::withToken($token)->acceptJson();

            switch (strtoupper($method)) {
                case 'POST':
                    $response = $request->post(url("/api{$endpoint}"), $data);
                    break;
                case 'PUT':
                    $response = $request->put(url("/api{$endpoint}"), $data);
                    break;
                case 'DELETE':
                    $response = $request->delete(url("/api{$endpoint}"));
                    break;
                default:
                    $response = $request->get(url("/api{$endpoint}"));
            }

            return $response->json();
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => 'API connection failed'];
        }
    }

    public function index()
    {
        $user = Session::get('user');
        
        // Get dashboard statistics
        $stats = $this->getDashboardStats();
        
        return view('dashboard.index', compact('user', 'stats'));
    }

    private function getDashboardStats()
    {
        // Get counts from each endpoint
        $productsResponse = $this->apiCall('/products?per_page=1');
        $customersResponse = $this->apiCall('/customers?per_page=1');
        $ordersResponse = $this->apiCall('/orders?per_page=1');
        $lowStockResponse = $this->apiCall('/inventories-low-stock?per_page=1');

        // Get today's orders for sales calculation
        $todayOrders = $this->apiCall('/orders?today=true&status=completed');
        $todaySales = 0;
        if ($todayOrders['status'] === 'success' && isset($todayOrders['data']['data'])) {
            $todaySales = collect($todayOrders['data']['data'])->sum('total_amount');
        }

        return [
            'total_products' => $productsResponse['data']['total'] ?? 0,
            'total_customers' => $customersResponse['data']['total'] ?? 0,
            'total_orders' => $ordersResponse['data']['total'] ?? 0,
            'low_stock_items' => $lowStockResponse['data']['total'] ?? 0,
            'today_sales' => $todaySales,
        ];
    }
}