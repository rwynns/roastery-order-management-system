<?php


namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;

class OrderController extends Controller
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
        if ($request->has('status')) $params['status'] = $request->status;
        if ($request->has('payment_status')) $params['payment_status'] = $request->payment_status;
        if ($request->has('customer_id')) $params['customer_id'] = $request->customer_id;
        if ($request->has('date_from')) $params['date_from'] = $request->date_from;
        if ($request->has('date_to')) $params['date_to'] = $request->date_to;
        if ($request->has('per_page')) $params['per_page'] = $request->per_page;

        $queryString = http_build_query($params);
        $endpoint = '/orders' . ($queryString ? '?' . $queryString : '');

        // Get orders from API
        $orders = $this->apiCall($endpoint);
        
        // Get customers for filter dropdown
        $customers = $this->apiCall('/customers?per_page=100');

        return view('orders.index', [
            'orders' => $orders['data'] ?? null,
            'customers' => $customers['data']['data'] ?? [],
        ]);
    }

    public function create()
    {
        // Get customers and products for order creation
        $customers = $this->apiCall('/customers?per_page=100');
        $products = $this->apiCall('/products?is_active=1&per_page=100');
        
        return view('orders.create', [
            'customers' => $customers['data']['data'] ?? [],
            'products' => $products['data']['data'] ?? [],
        ]);
    }

    public function store(Request $request)
    {
        $response = $this->apiCall('/orders', 'POST', $request->all());
        
        if ($response['status'] === 'success') {
            return redirect()->route('orders.show', $response['data']['id'])->with('success', 'Order created successfully');
        } else {
            return back()->withErrors($response['errors'] ?? ['error' => $response['message']])->withInput();
        }
    }

    public function show($id)
    {
        $order = $this->apiCall("/orders/{$id}");
        
        if ($order['status'] !== 'success') {
            return redirect()->route('orders.index')->with('error', 'Order not found');
        }
        
        return view('orders.show', [
            'order' => $order['data']
        ]);
    }

    public function edit($id)
    {
        $order = $this->apiCall("/orders/{$id}");
        $customers = $this->apiCall('/customers?per_page=100');
        $products = $this->apiCall('/products?is_active=1&per_page=100');
        
        if ($order['status'] !== 'success') {
            return redirect()->route('orders.index')->with('error', 'Order not found');
        }
        
        return view('orders.edit', [
            'order' => $order['data'],
            'customers' => $customers['data']['data'] ?? [],
            'products' => $products['data']['data'] ?? [],
        ]);
    }

    public function update(Request $request, $id)
    {
        $response = $this->apiCall("/orders/{$id}", 'PUT', $request->all());
        
        if ($response['status'] === 'success') {
            return redirect()->route('orders.show', $id)->with('success', 'Order updated successfully');
        } else {
            return back()->withErrors($response['errors'] ?? ['error' => $response['message']])->withInput();
        }
    }

    public function destroy($id)
    {
        $response = $this->apiCall("/orders/{$id}", 'DELETE');
        
        if ($response['status'] === 'success') {
            return redirect()->route('orders.index')->with('success', 'Order deleted successfully');
        } else {
            return back()->with('error', $response['message'] ?? 'Failed to delete order');
        }
    }

    public function pos()
    {
        // Get active products and customers for POS interface
        $products = $this->apiCall('/products?is_active=1&per_page=100');
        $customers = $this->apiCall('/customers?is_active=1&per_page=50');
        
        return view('pos.index', [
            'products' => $products['data']['data'] ?? [],
            'customers' => $customers['data']['data'] ?? [],
        ]);
    }

    public function addPayment(Request $request, $id)
    {
        $response = $this->apiCall("/orders/{$id}/payments", 'POST', $request->all());
        
        if ($response['status'] === 'success') {
            return redirect()->route('orders.show', $id)->with('success', 'Payment added successfully');
        } else {
            return back()->withErrors($response['errors'] ?? ['error' => $response['message']])->withInput();
        }
    }
}