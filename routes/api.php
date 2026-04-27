<?php

use App\Http\Controllers\Backend\AreaController;
use App\Http\Controllers\Backend\BusinessTypeController;
use App\Http\Controllers\Backend\ClientController;
use App\Http\Controllers\Backend\ClientNoteController;
use App\Http\Controllers\Backend\CountryController;
use App\Http\Controllers\Backend\CustomerController;
use App\Http\Controllers\Backend\CustomerDemoNoteController;
use App\Http\Controllers\Backend\CustomerHistoryController;
use App\Http\Controllers\Backend\DesignationController;
use App\Http\Controllers\Backend\DemoPresenterController;
use App\Http\Controllers\Backend\EmployeeController;
use App\Http\Controllers\Backend\InterestLevelController;
use App\Http\Controllers\Backend\LeadSourceController;
use App\Http\Controllers\Backend\LogoController;
use App\Http\Controllers\Backend\OfferConnectController;
use App\Http\Controllers\Backend\ServiceTypeController;
use App\Http\Controllers\Backend\ShopTypeController;
use App\Http\Controllers\Backend\TaskAssignmentController;
use App\Http\Controllers\Backend\TaskController;
use App\Http\Controllers\Backend\TaskNoteController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::prefix('/api')->group(function () {

    Route::middleware('auth:sanctum')->get('/current-user', function (Request $request) {
        return $request->user();
    });

    // Employee Create API Routes
    Route::prefix('/employees')->group(function () {
        Route::get('/', [EmployeeController::class, 'index']);
        Route::post('/', [EmployeeController::class, 'store']);
        Route::get('/{employee}', [EmployeeController::class, 'show']);
        Route::put('/{employee}', [EmployeeController::class, 'update']);
        Route::delete('/{employee}', [EmployeeController::class, 'destroy']);
    });

    // Demo Presenter Create API Routes
    Route::prefix('/demo-presenters')->group(function () {
        Route::get('/', [DemoPresenterController::class, 'index']);
        Route::post('/', [DemoPresenterController::class, 'store']);
        Route::get('/{presenter}', [DemoPresenterController::class, 'show']);
        Route::put('/{presenter}', [DemoPresenterController::class, 'update']);
        Route::delete('/{presenter}', [DemoPresenterController::class, 'destroy']);
    });

    Route::get('/task-assignments', [TaskAssignmentController::class, 'index']);
    // Area Name Routes
    Route::get('/areas', [AreaController::class, 'index']);
    Route::post('/areas', [AreaController::class, 'store']);
    Route::put('/areas/{id}', [AreaController::class, 'update']);
    Route::delete('/areas/{id}', [AreaController::class, 'destroy']);

    // Lead Source Routes
    Route::prefix('lead-sources')->group(function () {
        Route::get('/', [LeadSourceController::class, 'index']);
        Route::post('/', [LeadSourceController::class, 'store']);
        Route::put('/{id}', [LeadSourceController::class, 'update']);
        Route::delete('/{id}', [LeadSourceController::class, 'destroy']);
    });

    // Shop Type Routes
    Route::get('/shop-types', [ShopTypeController::class, 'index']);
    Route::post('/shop-types', [ShopTypeController::class, 'store']);
    Route::put('/shop-types/{shopType}', [ShopTypeController::class, 'update']);
    Route::delete('/shop-types/{shopType}', [ShopTypeController::class, 'destroy']);

    // Service Type Routes
    Route::get('/service-types', [ServiceTypeController::class, 'index']);
    Route::post('/service-types', [ServiceTypeController::class, 'store']);
    Route::put('/service-types/{serviceType}', [ServiceTypeController::class, 'update']);
    Route::delete('/service-types/{serviceType}', [ServiceTypeController::class, 'destroy']);

    // Designations Routes
    Route::get('/designations', [DesignationController::class, 'index']);
    Route::post('/designations', [DesignationController::class, 'store']);
    Route::put('/designations/{id}', [DesignationController::class, 'update']);
    Route::delete('/designations/{id}', [DesignationController::class, 'destroy']);

    // InterestLevel Routes
    Route::get('/interest-levels', [InterestLevelController::class, 'index']);
    Route::post('/interest-levels', [InterestLevelController::class, 'store']);
    Route::get('/interest-levels/{id}', [InterestLevelController::class, 'show']);
    Route::put('/interest-levels/{id}', [InterestLevelController::class, 'update']);
    Route::delete('/interest-levels/{id}', [InterestLevelController::class, 'destroy']);

    // Offer Connects Me Route
    Route::get('/offer-connects', [OfferConnectController::class, 'index']);
    Route::post('/offer-connects', [OfferConnectController::class, 'store']);
    Route::put('/offer-connects/{offerConnect}', [OfferConnectController::class, 'update']);
    Route::delete('/offer-connects/{offerConnect}', [OfferConnectController::class, 'destroy']);

    Route::get('/service-types/names', function () {
        return \App\Models\ServiceType::where('status', '!=', 'Disabled')
            ->pluck('service_type_name');
    });

    // Country Create , Edit , Delete Routes
    Route::get('/countries', [CountryController::class, 'index']);
    Route::post('/countries', [CountryController::class, 'store']);
    Route::put('/countries/{id}', [CountryController::class, 'update']);
    Route::delete('/countries/{id}', [CountryController::class, 'destroy']);

    Route::get('/clients/search', [ClientController::class, 'search']);
    Route::patch('/clients/{id}/status', [ClientController::class, 'updateStatus']);
    Route::apiResource('clients', ClientController::class);
    Route::get('clients/{client}/timeline', [ClientController::class, 'timeline']);
    Route::get('/clients/{client}/operator-history', [ClientController::class, 'operatorHistory']);

    Route::get('/notes', [ClientNoteController::class, 'index']); // fetch all notes
    Route::get('/clients/{clientId}/notes', [ClientNoteController::class, 'clientNotes']); // fetch client-specific
    Route::post('/notes', [ClientNoteController::class, 'store']); // add note
    Route::put('/notes/{id}', [ClientNoteController::class, 'update']);
    Route::delete('/notes/{id}', [ClientNoteController::class, 'destroy']); // delete note

    Route::prefix('business-types')->group(function () {
        Route::get('/', [BusinessTypeController::class, 'index']);       // fetch all
        Route::post('/', [BusinessTypeController::class, 'store']);      // create
        Route::put('/{id}', [BusinessTypeController::class, 'update']);  // update
        Route::delete('/{id}', [BusinessTypeController::class, 'destroy']); // delete
    });

    // Add Customer Related Routes
    Route::prefix('customers')->group(function () {

        // ✅ MUST be first
        Route::get('/search', [CustomerController::class, 'search']);

        Route::get('/', [CustomerController::class, 'index']);
        Route::post('/', [CustomerController::class, 'store']);
        Route::get('/{id}', [CustomerController::class, 'show']);
        Route::put('/{id}', [CustomerController::class, 'update']);
        Route::delete('/{id}', [CustomerController::class, 'destroy']);
    });

    Route::post('/customers/assign', [CustomerController::class, 'assignToStaff']);
    Route::put('/customers/{id}/staff-status', [CustomerController::class, 'updateStaffStatus']);
    Route::put('/customers/{id}/option/service-type', [CustomerController::class, 'updateServiceType']);
    Route::put('/customers/{id}/service-type', [CustomerHistoryController::class, 'updateServiceType']);
    Route::get('/customers/all/history', [CustomerHistoryController::class, 'getAllHistory']);

    Route::middleware('auth:sanctum')->group(function() {
        Route::get('/customers/{customer}/history', [CustomerHistoryController::class, 'getHistory']);
        Route::get('/customers/{customer}/notes', [CustomerHistoryController::class, 'getNotes']);
        Route::put('/customers/{customer}/notes/mark-read', [CustomerHistoryController::class, 'markRead']);
        Route::put('/customer-history/{id}/update-note', [CustomerHistoryController::class, 'updateNote']);
        Route::post('/customers/{customer}/add-note', [CustomerHistoryController::class, 'addNote']);

        // Demo presenter notes (chat)
        Route::get('/customers/{customer}/demo-notes', [CustomerDemoNoteController::class, 'index']);
        Route::post('/customers/{customer}/demo-notes', [CustomerDemoNoteController::class, 'store']);
        Route::put('/customers/{customer}/demo-notes/mark-read', [CustomerDemoNoteController::class, 'markRead']);

        // Demo status update (for demo presenter + staff/admin)
        Route::put('/customers/{id}/demo-status', [CustomerController::class, 'updateDemoStatus']);


        Route::get('/tasks/{task}/notes', [TaskNoteController::class, 'index']);
        Route::post('/tasks/{task}/notes', [TaskNoteController::class, 'store']);
        Route::post('/tasks/{task}/notes/mark-read', [TaskNoteController::class, 'markAsRead']);

        Route::post('/tasks/{id}/staff-decision', [TaskController::class, 'staffTaskDecision']);
    });

    // Website Configuration Routes
    Route::apiResource('logos', LogoController::class);
});


