<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;

class ProductController extends Controller
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

    public function index(Request $request)
    {
        // Build query parameters
        $params = [];
        if ($request->has('search')) $params['search'] = $request->search;
        if ($request->has('category_id')) $params['category_id'] = $request->category_id;
        if ($request->has('is_active')) $params['is_active'] = $request->is_active;
        if ($request->has('per_page')) $params['per_page'] = $request->per_page;

        $queryString = http_build_query($params);
        $endpoint = '/products' . ($queryString ? '?' . $queryString : '');

        // Get products from API
        $products = $this->apiCall($endpoint);
        
        // Get categories for filter dropdown
        $categories = $this->apiCall('/categories');

        return view('products.index', [
            'products' => $products['data'] ?? null,
            'categories' => $categories['data'] ?? [],
        ]);
    }

    public function create()
    {
        // Get categories for dropdown
        $categories = $this->apiCall('/categories');
        
        return view('products.create', [
            'categories' => $categories['data'] ?? [],
        ]);
    }

    public function store(Request $request)
    {
        $response = $this->apiCall('/products', 'POST', $request->all());
        
        if ($response['status'] === 'success') {
            return redirect()->route('products.index')->with('success', 'Product created successfully');
        } else {
            return back()->withErrors($response['errors'] ?? ['error' => $response['message']])->withInput();
        }
    }

    public function show($id)
    {
        $product = $this->apiCall("/products/{$id}");
        
        if ($product['status'] !== 'success') {
            return redirect()->route('products.index')->with('error', 'Product not found');
        }
        
        return view('products.show', [
            'product' => $product['data']
        ]);
    }

    public function edit($id)
    {
        $product = $this->apiCall("/products/{$id}");
        $categories = $this->apiCall('/categories');
        
        if ($product['status'] !== 'success') {
            return redirect()->route('products.index')->with('error', 'Product not found');
        }
        
        return view('products.edit', [
            'product' => $product['data'],
            'categories' => $categories['data'] ?? [],
        ]);
    }

    public function update(Request $request, $id)
    {
        $response = $this->apiCall("/products/{$id}", 'PUT', $request->all());
        
        if ($response['status'] === 'success') {
            return redirect()->route('products.show', $id)->with('success', 'Product updated successfully');
        } else {
            return back()->withErrors($response['errors'] ?? ['error' => $response['message']])->withInput();
        }
    }

    public function destroy($id)
    {
        $response = $this->apiCall("/products/{$id}", 'DELETE');
        
        if ($response['status'] === 'success') {
            return redirect()->route('products.index')->with('success', 'Product deleted successfully');
        } else {
            return back()->with('error', $response['message'] ?? 'Failed to delete product');
        }
    }
}