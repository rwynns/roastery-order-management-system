<?php


namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;

class CustomerController extends Controller
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
        if ($request->has('is_active')) $params['is_active'] = $request->is_active;
        if ($request->has('gender')) $params['gender'] = $request->gender;
        if ($request->has('tier')) $params['tier'] = $request->tier;
        if ($request->has('per_page')) $params['per_page'] = $request->per_page;

        $queryString = http_build_query($params);
        $endpoint = '/customers' . ($queryString ? '?' . $queryString : '');

        // Get customers from API
        $customers = $this->apiCall($endpoint);

        return view('customers.index', [
            'customers' => $customers['data'] ?? null,
        ]);
    }

    public function create()
    {
        return view('customers.create');
    }

    public function store(Request $request)
    {
        $response = $this->apiCall('/customers', 'POST', $request->all());
        
        if ($response['status'] === 'success') {
            return redirect()->route('customers.index')->with('success', 'Customer created successfully');
        } else {
            return back()->withErrors($response['errors'] ?? ['error' => $response['message']])->withInput();
        }
    }

    public function show($id)
    {
        $customer = $this->apiCall("/customers/{$id}");
        
        if ($customer['status'] !== 'success') {
            return redirect()->route('customers.index')->with('error', 'Customer not found');
        }
        
        return view('customers.show', [
            'customer' => $customer['data']
        ]);
    }

    public function edit($id)
    {
        $customer = $this->apiCall("/customers/{$id}");
        
        if ($customer['status'] !== 'success') {
            return redirect()->route('customers.index')->with('error', 'Customer not found');
        }
        
        return view('customers.edit', [
            'customer' => $customer['data']
        ]);
    }

    public function update(Request $request, $id)
    {
        $response = $this->apiCall("/customers/{$id}", 'PUT', $request->all());
        
        if ($response['status'] === 'success') {
            return redirect()->route('customers.show', $id)->with('success', 'Customer updated successfully');
        } else {
            return back()->withErrors($response['errors'] ?? ['error' => $response['message']])->withInput();
        }
    }

    public function destroy($id)
    {
        $response = $this->apiCall("/customers/{$id}", 'DELETE');
        
        if ($response['status'] === 'success') {
            return redirect()->route('customers.index')->with('success', 'Customer deleted successfully');
        } else {
            return back()->with('error', $response['message'] ?? 'Failed to delete customer');
        }
    }
}