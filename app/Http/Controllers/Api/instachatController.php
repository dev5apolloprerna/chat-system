<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Message;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\WhatsAppAccount;

class instachatController extends Controller
{
    public function instachat(Request $request)
    {
        // ===============================
        // STEP 1 — META WEBHOOK VERIFY
        // ===============================
        if ($request->isMethod('get')) {
    
            $verify_token = "instachat"; // same as Meta dashboard
    
            if ($request->get('hub_verify_token') === $verify_token) {
                return response($request->get('hub_challenge'), 200)
                    ->header('Content-Type', 'text/plain');
            }
    
            return response('Invalid Verify Token', 403);
        }
    
        // ===============================
        // STEP 2 — RECEIVE EVENTS
        // ===============================
        if ($request->isMethod('post')) {
    
            DB::table('instagramchat')->insert([
                'request' => $request->getContent(),
            ]);
    
            return response('EVENT_RECEIVED', 200);
        }
    }

}