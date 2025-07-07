<?php

namespace App\Http\Controllers\web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;

class InventoryController extends Controller
{
    private function getToken()
    {
        return Session::get('auth_token');
    }

    private function apiCall($endpoint, $method = 'GET', $data = null)
    {
        try{
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

    public function index(Request $request)
    {
        // Build query parameters
        $params = [];
        if ($request->has('search')) $params['search'] = $request->search;
        if ($request->has('category_id')) $params['category_id'] = $request->category_id;
        if ($request->has('stock_level')) $params['stock_level'] = $request->stock_level;
        if ($request->has('per_page')) $params['per_page'] = $request->per_page;

        $queryString = http_build_query($params);
        $endpoint = '/inventories' . ($queryString ? '?' . $queryString : '');

        // Get inventory from API
        $inventories = $this->apiCall($endpoint);

        // Get categories for filter dropdown
        $categories = $this->apiCall('/categories');

        return view('inventory.index' , [
            'inventories' => $inventories['data'] ?? null,
            'categories' => $categories['data'] ?? [],
        ]);
    }

    public function lowStock(Request $request)
    {
        $params = [];
        if ($request->has('per_page')) $params['per_page'] = $request->per_page;

        $queryString = http_build_query($params);
        $endpoint = '/inventories-low-stock' . ($queryString ? '?' . $queryString : '');

        $lowStockItems = $this->apiCall($endpoint);

        return view('inventory.low_stock', [
            'inventories' => $lowStockItems['data'] ?? null,
            'meta' => $lowStockItems['meta'] ?? null,
        ]);
    }

    public function adjustments()
    {
        // Get all products for adjustment form
        $products = $this->apiCall('/products?per_page=100');

        return view('inventory.adjustments', [
            'products' => $products['data']['data'] ?? [],
        ]);
    }

    public function adjustment(Request $request)
    {
        $response = $this->apiCall('/inventories-stock-adjustment', 'POST', $request->all());

        if ($response['status'] === 'success'){
            return back() ->with('success', 'Stock adjustment completed successfully');
        } else {
            return back() ->withErrors($response['errors'] ?? ['error' => $response['message']])->withInput();
        }
    }

    public function bulkAdjustment(Request $request)
    {
        $response = $this->apiCall('/inventories-bulk-adjustment', 'POST', $request->all());

        if ($response['status'] === 'success') {
            return back()->with('success', 'Bulk stock adjustment completed successfully');
        } else {
            return back()->withErrors($response['errors'] ?? ['error' => $response['message']])->withInput();
        }
    }
}
