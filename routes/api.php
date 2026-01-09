<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Handle OPTIONS preflight requests BEFORE any middleware
Route::options('{any}', function () {
    $origin = request()->header('Origin');
    $allowedOrigins = ['https://core.kalaexcel.com', 'https://www.kalaexcel.com', 'https://kalaexcel.com'];
    
    if ($origin && in_array($origin, $allowedOrigins)) {
        return response('', 204)
            ->header('Access-Control-Allow-Origin', $origin)
            ->header('Access-Control-Allow-Credentials', 'true')
            ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, X-XSRF-TOKEN, Accept, Origin')
            ->header('Access-Control-Max-Age', '86400');
    }
    
    return response('', 204);
})->where('any', '.*');
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PropertyController;
use App\Http\Controllers\Api\TenantController;
use App\Http\Controllers\Api\ContractController;
use App\Http\Controllers\Api\RentPaymentController;
use App\Http\Controllers\Api\ConstructionProjectController;
use App\Http\Controllers\Api\ConstructionExpenseController;
use App\Http\Controllers\Api\BankAccountController;
use App\Http\Controllers\Api\BankTransactionController;
use App\Http\Controllers\Api\ToiletCollectionController;
use App\Http\Controllers\Api\WaterSupplyCustomerController;
use App\Http\Controllers\Api\WaterSupplyReadingController;
use App\Http\Controllers\Api\WaterSupplyPaymentController;
use App\Http\Controllers\Api\WaterWellCollectionController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\PropertyTypeController;
use App\Http\Controllers\Api\BusinessTypeController;
use App\Http\Controllers\Api\TransactionCategoryController;
use App\Http\Controllers\Api\ConstructionMaterialController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\VendorController;

// Public routes (authentication)
Route::get('/csrf-cookie', [AuthController::class, 'csrfCookie']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// Protected routes (require authentication)
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::put('/user/password', [AuthController::class, 'updatePassword']);
    
    // Real Estate Module
    Route::apiResource('properties', PropertyController::class);
    Route::get('properties/available/list', [PropertyController::class, 'available']);
    Route::apiResource('tenants', TenantController::class);
    Route::apiResource('contracts', ContractController::class);
    Route::apiResource('rent-payments', RentPaymentController::class);
    
    // Construction Module
    Route::apiResource('construction-projects', ConstructionProjectController::class);
    Route::apiResource('construction-expenses', ConstructionExpenseController::class);
    
    // Banking Module
    Route::apiResource('bank-accounts', BankAccountController::class);
    Route::apiResource('bank-transactions', BankTransactionController::class);
    
    // Public Services Module
    Route::apiResource('toilet-collections', ToiletCollectionController::class);
    Route::apiResource('water-supply-customers', WaterSupplyCustomerController::class);
    Route::apiResource('water-supply-readings', WaterSupplyReadingController::class);
    Route::apiResource('water-supply-payments', WaterSupplyPaymentController::class);
    Route::apiResource('water-well-collections', WaterWellCollectionController::class);
    
    // Users Module
    Route::post('users/{user}/reset-password', [UserController::class, 'resetPassword']);
    Route::post('users/{user}/toggle-status', [UserController::class, 'toggleStatus']);
    Route::apiResource('users', UserController::class);
    
    // Settings Module
    Route::apiResource('property-types', PropertyTypeController::class);
    Route::apiResource('business-types', BusinessTypeController::class);
    Route::apiResource('transaction-categories', TransactionCategoryController::class);
    Route::apiResource('construction-materials', ConstructionMaterialController::class);
    Route::apiResource('locations', LocationController::class);
    Route::apiResource('vendors', VendorController::class);
    Route::apiResource('profile', ProfileController::class)->except(['index', 'update']);
    Route::get('/profile', [ProfileController::class, 'index']);
    Route::put('/profile', [ProfileController::class, 'update']);
});
