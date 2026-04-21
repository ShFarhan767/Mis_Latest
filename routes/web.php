<?php

use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Backend\TaskController;
use App\Http\Controllers\Backend\StaffController;
use App\Http\Controllers\Backend\ClientController;
use App\Http\Controllers\Backend\EmployeeController;
use App\Http\Controllers\Backend\DashboardController;
use App\Http\Controllers\Backend\TaskAssignmentController;

Route::get('/', function () {
    return redirect()->route('employee.login');
});

Route::get('/shopx', function () {
    return Inertia::render('ShopHome');
})->name('ShopHome');

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
});

Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin/dashboard', fn() => Inertia::render('Backend/AdminDashboard'))->name('admin.dashboard');

    // HomePage Data Entry Routes
    Route::get('/staffCreate', function () {
        $user = Auth::user(); // get logged-in user
        return Inertia::render('Backend/StaffCreate', [
            'userRole' => $user->role,  // e.g., 'admin' or 'employee'
            'userId'   => $user->id,    // logged-in user ID
        ]);
    })->name('staffCreate');
    Route::get('/areaCreate', fn() => Inertia::render('Backend/AreaNameEntry'))->name('areaCreate');
    Route::get('/leadSourceCreate', fn() => Inertia::render('Backend/LeadSourceEntry'))->name('leadSourceCreate');
    Route::get('/shopTypeCreate', fn() => Inertia::render('Backend/ShopTypeEntry'))->name('shopTypeCreate');
    Route::get('/serviceTypeCreate', fn() => Inertia::render('Backend/ServiceTypeEntry'))->name('serviceTypeCreate');
    Route::get('/employeeCreate', function () {
        $user = Auth::user(); // get logged-in user
        return Inertia::render('Backend/EmployeeCreate', [
            'userRole' => $user->role,  // 'admin' or 'employee'
            'userId'   => $user->id,    // logged-in user ID
        ]);
    })->name('employeeCreate');
    Route::get('/presenterCreate', function () {
        $user = Auth::user(); // get logged-in user
        return Inertia::render('Backend/DemoPresenterCreate', [
            'userRole' => $user->role,  // 'admin' or 'employee'
            'userId'   => $user->id,    // logged-in user ID
        ]);
    })->name('presenterCreate');
    Route::get('/clientManagement', fn() => Inertia::render('Backend/ClientManage'))->name('clientManagement');
    Route::get('/clientList', fn() => Inertia::render('Backend/ClientList'))->name('clientList');
    Route::get('/customer-assign', fn() => Inertia::render('Backend/CustomerAssign'))->name('customerAssign');

    // Website Configuration Routes
    Route::get('/logo-upload', fn() => Inertia::render('Backend/LogoUpload'))->name('logoUpload');

    // Lists (read-only)
    Route::get('/employeeList', fn() => Inertia::render('Backend/EmployeeList'))->name('employeeList');
    Route::get('/staffList', fn() => Inertia::render('Backend/StaffList'))->name('staffList');
    Route::get('/clientListSimple', fn() => Inertia::render('Backend/ClientListSimple'))->name('clientListSimple');
    Route::get('/areaList', fn() => Inertia::render('Backend/AreaList'))->name('areaList');
});

Route::get('/login', function () {
    return Inertia::render('auth/EmployeeLogin', [
        'canResetPassword' => Route::has('password.request'),
    ]);
})->name('employee.login');

Route::post('/employee/login', [EmployeeController::class, 'login'])->name('employee.login.store');

Route::middleware(['auth', 'admin_or_staff'])->group(function () {

    // Staff Management Related Routes
    Route::prefix('/api/staff')->group(function () {
        Route::get('/', [StaffController::class, 'index']);
        Route::post('/', [StaffController::class, 'store']);
        Route::get('/{staffs}', [StaffController::class, 'show']);
        Route::put('/{staffs}', [StaffController::class, 'update']);
        Route::delete('/{staffs}', [StaffController::class, 'destroy']);
    });

    // Customer Details Reports Lists
    Route::get('/new-contacts/list', fn() => Inertia::render('Backend/NewContactList'))->name('newContactLists');
    Route::get('/cancel-contacts/list', fn() => Inertia::render('Backend/CancelContactList'))->name('cancelContactLists');
    Route::get('/all-contacts/list', fn() => Inertia::render('Backend/AllContactList'))->name('allContactLists');
    Route::get('/numbers-histories/list', fn() => Inertia::render('Backend/NumbersHistory'))->name('numbersHistories');


    // Task Entry Page Related Routes

    Route::get('/task-entry', function () {
        return Inertia::render('Backend/TaskEntry');
    })->name('task.entry');

    Route::get('/contact-entry', function () {

        $user = Auth::user();

        return Inertia::render('Backend/ContactEntry', [
            'userRole' => $user->role,       // admin or employee
            'userId'   => $user->id,         // logged in user ID
        ]);
    })->name('contact.entry');

    Route::post('/api/tasks', [TaskController::class, 'store']);
    Route::put('/api/tasks/{id}', [TaskController::class, 'update']); // update task
    Route::delete('/api/tasks/{id}', [TaskController::class, 'destroy']); // delete task
    Route::get('/api/shops/search', [TaskController::class, 'searchShops']);
});

Route::get('/api/tasks', [TaskController::class, 'index']);
Route::get('/api/clients/search', [ClientController::class, 'search']);
Route::get('/api/users', [UserController::class, 'index']);


Route::middleware(['auth', 'admin'])->group(function () {


    Route::prefix('/api/clients')->group(function () {
        Route::get('/', [ClientController::class, 'index']);
        Route::post('/', [ClientController::class, 'store']);
        Route::get('/{id}', [ClientController::class, 'show']); // 👈 this must come last
        Route::put('/{id}', [ClientController::class, 'update']);
        Route::delete('/{id}', [ClientController::class, 'destroy']);
    });

    Route::get('/task-assignment', function () {
        return Inertia::render('Backend/TaskAssignment');
    })->name('task.assignment');

    Route::get('/task-list', function () {
        return Inertia::render('Backend/TaskAssignmentList');
    })->name('task.assignment');
});

Route::prefix('/api/task-assignments')->group(function () {
    // Route::get('/', [TaskAssignmentController::class, 'index']);
    Route::post('/', [TaskAssignmentController::class, 'store']);
    Route::put('/task/{task_id}', [TaskAssignmentController::class, 'updateByTaskId']);
    Route::put('/{assignment}', [TaskAssignmentController::class, 'update']);
    Route::delete('/{assignment}', [TaskAssignmentController::class, 'destroy']);

    // 🔹 Work session routes
    Route::post('/{task_id}/start-work', [TaskAssignmentController::class, 'startWork']);
    Route::post('/{task_id}/stop-work', [TaskAssignmentController::class, 'stopWork']);
    Route::get('/{task_id}/work-history', [TaskAssignmentController::class, 'workHistory']);
});

Route::post('/employee-login', [EmployeeController::class, 'login'])->name('employee.login');


require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
require __DIR__.'/api.php';
