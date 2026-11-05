<?php

use App\Http\Controllers\AdminLoginController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\client\ChatController;
use App\Http\Controllers\client\ClientAuthController;
use App\Http\Controllers\client\WhatsAppWebhookController;
use App\Http\Controllers\ClientController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Route::fallback(function () {
//     return view('errors.404'); // Make sure the view path matches your custom 404 page
// });

Route::get('login', fn() => redirect()->route('admin.login'))->name('login');

Route::middleware('guest:admin')->group(function () {
    Route::get('/admin/login', [AdminLoginController::class, 'showAdminLoginForm'])->name('admin.login');
    Route::post('/admin-login', [AdminLoginController::class, 'adminLogin'])->name('admin.login.post');
    Route::get('/admin-logout', [AdminLoginController::class, 'logout'])->name('admin.logout');
});

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

Route::get('/clear-cache', function () {
    Artisan::call('cache:clear');
    Artisan::call('view:clear');
    Artisan::call('route:clear');
    Artisan::call('config:clear');
    return 'Cache is cleared';
});

// Profile Routes
Route::prefix('profile')->name('profile.')->middleware('auth')->group(function () {
    Route::get('/', [HomeController::class, 'getProfile'])->name('detail');
    Route::get('/edit', [HomeController::class, 'EditProfile'])->name('EditProfile');
    Route::post('/update', [HomeController::class, 'updateProfile'])->name('update');
    Route::post('/change-password', [HomeController::class, 'changePassword'])->name('change-password');
});

Route::get('logout', [LoginController::class, 'logout'])->name('logout');

// Roles
Route::resource('roles', App\Http\Controllers\RolesController::class);

// Permissions
Route::resource('permissions', App\Http\Controllers\PermissionsController::class);

// Users
Route::middleware('auth')->prefix('users')->name('users.')->group(function () {
    Route::get('/', [UserController::class, 'index'])->name('index');
    Route::get('/create', [UserController::class, 'create'])->name('create');
    Route::post('/store', [UserController::class, 'store'])->name('store');
    Route::get('/edit/{id?}', [UserController::class, 'edit'])->name('edit');
    Route::post('/update/{user}', [UserController::class, 'update'])->name('update');
    Route::delete('/delete/{user}', [UserController::class, 'delete'])->name('destroy');
    Route::get('/update/status/{user_id}/{status}', [UserController::class, 'updateStatus'])->name('status');
    Route::post('/password-update/{Id?}', [UserController::class, 'passwordupdate'])->name('passwordupdate');
});

//Offer Master
Route::prefix('admin/clients')->name('admin.clients.')->middleware('auth')->group(function () {
    Route::get('/index', [ClientController::class, 'index'])->name('index');
    Route::get('/create', [ClientController::class, 'create'])->name('create');
    Route::post('/store', [ClientController::class, 'store'])->name('store');
    Route::get('/edit/{id?}', [ClientController::class, 'editview'])->name('edit');
    Route::post('/update', [ClientController::class, 'update'])->name('update');
    Route::delete('/delete', [ClientController::class, 'delete'])->name('delete');
    Route::post('/change-password', [ClientController::class, 'change_password'])->name('change_password');
});

Route::prefix('client')->name('client.')->group(function () {
    Route::get('login', [ClientAuthController::class, 'showLoginForm'])->name('login');
    Route::post('login', [ClientAuthController::class, 'login'])->name('login.submit');
    Route::get('logout', [ClientAuthController::class, 'logout'])->name('logout');

    // Protected client routes
    Route::middleware(['auth:client'])->group(function () {
        Route::get('dashboard', [ClientAuthController::class, 'dashboard'])->name('dashboard');

        // Chat routes
        Route::prefix('chat')->name('chat.')->group(function () {
            Route::get('/', [ChatController::class, 'index'])->name('index');
            Route::get('conversation/{conversation}/messages', [ChatController::class, 'messages'])->name('messages');
            Route::post('conversation/{conversation}/send', [ChatController::class, 'send'])->name('send');
        });
    });
});

// WhatsApp Webhook
Route::get('/webhook/whatsapp', [WhatsAppWebhookController::class, 'verify']);  // Verification (GET)
Route::post('/webhook/whatsapp', [WhatsAppWebhookController::class, 'handle']); // Messages (POST)
