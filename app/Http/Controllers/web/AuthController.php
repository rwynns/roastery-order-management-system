<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        // If already logged in, redirect to dashboard
        if (Session::has('auth_token')) {
            return redirect()->route('dashboard');
        }
        
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string'
        ]);

        try {
            // Call API login endpoint
            $response = Http::post(url('/api/login'), [
                'email' => $request->email,
                'password' => $request->password
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                // Store auth data in session
                Session::put('auth_token', $data['data']['token']);
                Session::put('user', $data['data']['user']);
                
                return redirect()->route('dashboard')->with('success', 'Login successful!');
            } else {
                $error = $response->json('message') ?? 'Login failed';
                return back()->withErrors(['email' => $error])->withInput();
            }

        } catch (\Exception $e) {
            return back()->withErrors(['email' => 'Connection error. Please try again.'])->withInput();
        }
    }

    public function logout(Request $request)
    {
        $token = Session::get('auth_token');
        
        if ($token) {
            try {
                // Call API logout endpoint
                Http::withToken($token)->post(url('/api/logout'));
            } catch (\Exception $e) {
                // Continue with logout even if API call fails
            }
        }

        // Clear session
        Session::forget(['auth_token', 'user']);
        Session::flush();

        return redirect()->route('login')->with('success', 'Logged out successfully');
    }
}