<?php

namespace App\Http\Controllers\web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;

class ReportController extends Controller
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

            switch (strtoupper($method)){
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
        } catch (\Exception $e){
            return ['status' => 'error', 'message' => 'API connection failed'];
        }
    }

    public function index()
    {
        return view('reports.index');
    }

    public function sales(Request $request)
    {
        // Build query parameters for sales report
        $params = [];
        if ($request->has('period')) $params['period'] = $request->period;
        if ($request->has('start_date')) $params['start_date'] = $request->start_date;
        if ($request->has('end_date')) $params['end_date'] = $request->end_date;
        if ($request->has('group_by')) $params['group_by'] = $request->group_by;
        if ($request->has('status')) $params['status'] = $request->status;

        $queryString = http_build_query($params);
        $endpoint = '/reports/sales' . ($queryString ? '?' . $queryString : '');

        $salesReport = $this->apiCall($endpoint);

        return view('reports.sales', [
            'report' => $salesReport['data'] ?? null,
        ]);
    }

    public function inventory(Request $request)
    {
        // Build query parameters for inventory report
        $params = [];
        if ($request->has('catery_id')) $params['category_id'] = $request->category_id;
        if ($request->has('stock_status')) $params['stock_status'] = $request->stock_status;
        if ($request->has('sort_by')) $params['sort_by'] = $request->sort_by;
        if ($request->has('sort_order')) $params['sort_order'] = $request->sort_order;

        $queryString = http_build_query($params);
        $endpoint = '/reports/inventory' . ($queryString ? '?' . $queryString : '');

        $inventoryReport = $this->apiCall($endpoint);

        // Get categories for filter
        $categories = $this->apiCall('/categories');

        return view('reports.inventory', [
            'report' => $inventoryReport['data'] ?? null,
            'categories' => $categories['data'] ?? [],
        ]);
    }

    public function customers(Request $request)
    {
        // Build query parameters for customer report
        $params = [];
        if ($request->has('period')) $params['period'] = $request->period;
        if ($request->has('start_date')) $params['start_date'] = $request->start_date;
        if ($request->has('end_date')) $params['end_date'] = $request->end_date;
        if ($request->has('tier')) $params['tier'] = $request->tier;
        if ($request->has('sort_by')) $params['sort_by'] = $request->sort_by;

        $queryString = http_build_query($params);
        $endpoint = '/reports/customers' . ($queryString ? '?' . $queryString : '');

        $customerReport = $this->apiCall($endpoint);

        return view('reports.customers', [
            'report' => $customerReport['data'] ?? null,
        ]);
    }

    public function export(Request $request)
    {
        $response = $this->apiCall('/reports/export', 'POST',$request->all());

        if($response['status'] === 'success'){
            // Handle file download or return success message
            return back()->with('success', 'Report exported successfully');
        } else {
            return back()->with('error', $response['message'] ?? 'Export failed');
        }
    }
}
