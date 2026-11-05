<?php

namespace App\Http\Controllers\client;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClientAuthController extends Controller
{
    public function showLoginForm(Request $request)
    {
        return view('client.auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'mobile' => 'required|digits:10',
            'password' => 'required|string',
        ]);

        if (Auth::guard('client')->attempt($request->only('mobile', 'password'))) {
            return redirect()->route('client.dashboard')->with('success', 'Logged in successfully!');
        }

        return back()->with('error', 'Invalid login credentials.');
    }

    public function logout(Request $request)
    {
        Auth::guard('client')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('client.login')->with('success', 'Logged out successfully!');
    }

    public function dashboard()
    {
        $Client = Client::where(['iStatus' => 1, 'isDelete' => 0])->count();

        return view('client.home', compact('Client'));
    }
}
