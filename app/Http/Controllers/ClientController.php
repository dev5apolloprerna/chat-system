<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Client;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        try {
            $clients = Client::orderBy('id', 'desc')->paginate(config('app.per_page', 10));

            return view('admin.clients.index', compact('clients'));
        } catch (\Throwable $th) {
            Log::error('Client index error: ' . $th->getMessage());
            return back()->with('error', 'Failed to load clients.');
        }
    }

    public function create(Request $request)
    {
        try {
            return view('admin.clients.add');
        } catch (\Throwable $th) {
            Log::error('Client create error: ' . $th->getMessage());
            return back()->with('error', 'Failed to load client creation form.');
        }
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'name'             => 'required|string|max:255',
                'mobile'           => 'required|digits:10|unique:clients,mobile',
                'email'            => 'required|email|unique:clients,email',
                'whatsapp_meta_no' => 'required|digits:10|unique:clients,whatsapp_meta_no',
                'address'          => 'required|string',
                'state'            => 'required|string',
                'city'             => 'required|string',
                'pincode'          => 'required|digits:6',
                'password'         => 'required|min:6',
            ]);

            Client::create([
                'name'             => $request->name,
                'mobile'           => $request->mobile,
                'email'            => $request->email,
                'whatsapp_meta_no' => $request->whatsapp_meta_no,
                'address'          => $request->address,
                'state'            => $request->state,
                'city'             => $request->city,
                'pincode'          => $request->pincode,
                'password'         => Hash::make($request->password),
                'strIP'            => $request->ip(),
            ]);

            return redirect()->route('admin.clients.index')->with('success', 'Client Created Successfully.');
        } catch (\Throwable $th) {
            Log::error('Client store error: ' . $th->getMessage());
            return back()->withInput()->with('error', 'Failed to create client.');
        }
    }

    public function editview(Request $request, $id)
    {
        try {
            $client = Client::where(['iStatus' => 1, 'isDelete' => 0, 'id' => $id])->firstOrFail();

            return view('admin.clients.edit', compact('client'));
        } catch (\Throwable $th) {
            Log::error('Client editview error: ' . $th->getMessage());
            return response()->json(['error' => 'Failed to load client data.'], 500);
        }
    }

    public function update(Request $request)
    {
        try {
            $request->validate([
                'client_id'        => 'required|exists:clients,id',
                'name'             => 'required|string|max:255',
                'mobile'           => 'required|digits:10|unique:clients,mobile,' . $request->client_id,
                'email'            => 'required|email|unique:clients,email,' . $request->client_id,
                'whatsapp_meta_no' => 'required|digits:10|unique:clients,whatsapp_meta_no,' . $request->client_id,
                'address'          => 'required|string',
                'state'            => 'required|string',
                'city'             => 'required|string',
                'pincode'          => 'required|digits:6',
                'password'         => 'nullable|min:6',
            ]);

            $client = Client::findOrFail($request->client_id);

            $data = [
                'name'             => $request->name,
                'mobile'           => $request->mobile,
                'email'            => $request->email,
                'whatsapp_meta_no' => $request->whatsapp_meta_no,
                'address'          => $request->address,
                'state'            => $request->state,
                'city'             => $request->city,
                'pincode'          => $request->pincode,
                'strIP'            => $request->ip(),
            ];

            if ($request->filled('password')) {
                $data['password'] = Hash::make($request->password);
            }

            $client->update($data);

            return redirect()->route('admin.clients.index')->with('success', 'Client Updated Successfully.');
        } catch (\Throwable $th) {
            Log::error('Client update error: ' . $th->getMessage());
            return back()->withInput()->with('error', 'Failed to update client.');
        }
    }

    public function delete(Request $request)
    {
        try {
            $client = Client::findOrFail($request->id);
            $client->delete();

            return back()->with('success', 'Client Deleted Successfully!');
        } catch (\Throwable $th) {
            Log::error('Client delete error: ' . $th->getMessage());
            return back()->with('error', 'Failed to delete client.');
        }
    }

    public function change_password(Request $request)
    {
        try {
            $request->validate([
                'id'              => 'required|exists:clients,id',
                'newpassword'     => 'required|min:6',
                'confirmpassword' => 'required|same:newpassword',
            ]);

            $client = Client::findOrFail($request->id);
            $client->update([
                'password' => Hash::make($request->newpassword),
            ]);

            return redirect()->route('admin.clients.index')->with('success', 'Client password updated successfully.');
        } catch (\Throwable $th) {
            Log::error('Client change_password error: ' . $th->getMessage());
            return back()->with('error', 'Failed to update password.');
        }
    }
}
