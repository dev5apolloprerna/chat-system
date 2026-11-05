<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UsersApiController;
use App\Http\Controllers\Api\AuthController;

use App\Http\Controllers\Api\ChatSystemController;
use App\Http\Controllers\Api\WhatsAppWebhookController;
use App\Http\Controllers\Api\instachatController;
use Illuminate\Support\Facades\Artisan;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::get('/clear-cache', function () {
    Artisan::call('cache:clear');
    Artisan::call('view:clear');
    Artisan::call('route:clear');
    Artisan::call('config:clear');
    return 'Cache is cleared';
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('login', [AuthController::class, 'login']);
Route::post('admin_login', [AuthController::class, 'admin_login']);
Route::get('logout', [AuthController::class, 'logout']);
Route::post('refresh', [AuthController::class, 'refresh']);
Route::post('change_password', [AuthController::class, 'change_password']);
Route::get('me', [AuthController::class, 'me']);

Route::post('/wa/send', [ChatSystemController::class, 'sendAny']); // {to, text}


Route::get('/wa/webhook',  [WhatsAppWebhookController::class, 'verify']);   // Meta verification
Route::post('/wa/webhook', [WhatsAppWebhookController::class, 'receive']);  // Events


// Route::get('/wa/webhook',  [ChatSystemController::class, 'verify']);
// Route::post('/wa/webhook', [ChatSystemController::class, 'receive']);

Route::post('/send-text', [ChatSystemController::class, 'sendText']); // no auth
Route::post('/wa/send/template', [ChatSystemController::class, 'sendTemplate']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/wa/send/text',     [ChatSystemController::class, 'sendText']);
    // Route::post('/wa/send/template', [ChatSystemController::class, 'sendTemplate']);

    // NEW: read endpoints for your UI
    Route::get('/chats',                    [ChatSystemController::class, 'listConversations']);
    Route::get('/chats/{conversationId}',   [ChatSystemController::class, 'showConversation']);
    Route::get('/chats/{conversationId}/messages', [ChatSystemController::class, 'listMessages']);
});


// whatsapp status webhook
Route::post('/webhook/whatsapp-status', [WhatsAppWebhookController::class, 'handleStatus']);

// Route::any('/instachat', [instachatController::class, 'instachat']);
Route::match(['get','post'], '/instachat', [instachatController::class, 'instachat']);

