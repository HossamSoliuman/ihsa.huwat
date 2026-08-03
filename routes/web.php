<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Dashboard\AdminDashboardController;
use App\Http\Controllers\Dashboard\AlertController;
use App\Http\Controllers\Dashboard\AttendanceController;
use App\Http\Controllers\Dashboard\BoatController;
use App\Http\Controllers\Dashboard\CaptainController;
use App\Http\Controllers\Dashboard\CoverageController;
use App\Http\Controllers\Dashboard\DiscrepancyController;
use App\Http\Controllers\Dashboard\EmployeeOperationsController;
use App\Http\Controllers\Dashboard\EmployeePerformanceController;
use App\Http\Controllers\Dashboard\EmploymentApplicationController as DashboardEmploymentApplicationController;
use App\Http\Controllers\Dashboard\EmploymentAttachmentController;
use App\Http\Controllers\Dashboard\EmploymentJobController;
use App\Http\Controllers\Dashboard\EmploymentProfileController;
use App\Http\Controllers\Dashboard\FishSpeciesController;
use App\Http\Controllers\Dashboard\GovernorateController;
use App\Http\Controllers\Dashboard\GovernorateOverviewController;
use App\Http\Controllers\Dashboard\HarborAttachmentController;
use App\Http\Controllers\Dashboard\HarborBoatController;
use App\Http\Controllers\Dashboard\HarborCapacityController;
use App\Http\Controllers\Dashboard\HarborController;
use App\Http\Controllers\Dashboard\HarborExportController;
use App\Http\Controllers\Dashboard\HarborLicenseController;
use App\Http\Controllers\Dashboard\HarborViolationController;
use App\Http\Controllers\Dashboard\HarborWorkerController;
use App\Http\Controllers\Dashboard\HumanResourcesController;
use App\Http\Controllers\Dashboard\MasterDataController;
use App\Http\Controllers\Dashboard\PayrollController;
use App\Http\Controllers\Dashboard\PortController;
use App\Http\Controllers\Dashboard\PortOperationsController;
use App\Http\Controllers\Dashboard\RegionController;
use App\Http\Controllers\Dashboard\RegionOverviewController;
use App\Http\Controllers\Dashboard\ReportController;
use App\Http\Controllers\Dashboard\ReportExportController;
use App\Http\Controllers\Dashboard\TripController;
use App\Http\Controllers\EmploymentApplicationController;
use App\Http\Controllers\InformationPortalController;
use App\Http\Controllers\PublicJobController;
use App\Http\Controllers\SetupController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicJobController::class, 'index'])->name('home');
Route::get('/jobs', [PublicJobController::class, 'index'])->name('jobs.index');
Route::get('/jobs/{job}', [PublicJobController::class, 'show'])->name('jobs.show');
Route::get('/jobs/{job}/apply', [EmploymentApplicationController::class, 'create'])->name('applications.create');
Route::post('/jobs/{job}/apply', [EmploymentApplicationController::class, 'store'])->middleware('throttle:applications')->name('applications.store');
Route::get('/applications/{reference}/submitted', [EmploymentApplicationController::class, 'submitted'])->name('applications.submitted');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->middleware('throttle:login')->name('login.store');
    Route::get('/setup', [SetupController::class, 'create'])->name('setup');
    Route::post('/setup', [SetupController::class, 'store'])->name('setup.store');
});

Route::post('/logout', [AuthController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::prefix('/info')->name('information.')->middleware('role:super_admin,stat_employee')->group(function () {
        Route::get('/', [InformationPortalController::class, 'create'])->name('create');
        Route::post('/', [InformationPortalController::class, 'store'])->middleware('throttle:30,1')->name('store');
        Route::post('/draft', [InformationPortalController::class, 'storeDraft'])->middleware('throttle:30,1')->name('draft.store');
        Route::delete('/draft', [InformationPortalController::class, 'discardDraft'])->middleware('throttle:30,1')->name('draft.discard');
        Route::get('/submitted/{reference}', [InformationPortalController::class, 'submitted'])->name('submitted');
    });

    Route::get('/dashboard/admin', AdminDashboardController::class)->middleware('role:super_admin')->name('dashboard.admin');
    Route::get('/dashboard/alerts', AlertController::class)->middleware('role:super_admin,gov_supervisor,port_supervisor')->name('dashboard.alerts.index');

    Route::get('/dashboard/regions', RegionOverviewController::class)
        ->middleware('role:super_admin,region_manager')->name('dashboard.region-overview.index');
    Route::get('/dashboard/governorates', GovernorateOverviewController::class)
        ->middleware('role:super_admin,region_manager,gov_supervisor')->name('dashboard.governorate-overview.index');
    Route::get('/dashboard/reports', [ReportController::class, 'index'])
        ->middleware('role:super_admin,region_manager,gov_supervisor')->name('dashboard.reports.index');
    Route::get('/dashboard/reports/export', ReportExportController::class)
        ->middleware(['role:super_admin,region_manager,gov_supervisor', 'throttle:20,1'])->name('dashboard.reports.export');
    Route::prefix('/dashboard/port-operations')->name('dashboard.port-operations.')->middleware('role:super_admin,gov_supervisor,port_supervisor')->group(function () {
        Route::get('/', [PortOperationsController::class, 'index'])->name('index');
        Route::post('/{port}/assignments', [PortOperationsController::class, 'storeAssignment'])->name('assignments.store');
        Route::patch('/{port}/trips/{trip}/assignment', [PortOperationsController::class, 'assignTrip'])->name('trips.assignment');
        Route::patch('/{port}/trips/{trip}/review', [PortOperationsController::class, 'transfer'])->name('trips.review');
        Route::patch('/{port}/discrepancies/{discrepancy}', [PortOperationsController::class, 'approve'])->name('discrepancies.approve');
    });

    Route::prefix('/dashboard/attendance')->name('dashboard.attendance.')->middleware('role:super_admin,hr_manager,port_supervisor')->group(function () {
        Route::get('/', [AttendanceController::class, 'index'])->name('index');
        Route::post('/substitutes', [AttendanceController::class, 'substitute'])->name('substitutes.store');
        Route::post('/{assignment}/check-in', [AttendanceController::class, 'checkIn'])->name('check-in');
        Route::post('/{assignment}/check-out', [AttendanceController::class, 'checkOut'])->name('check-out');
        Route::post('/{assignment}/absence', [AttendanceController::class, 'markAbsent'])->name('absence');
        Route::patch('/{assignment}/shift', [AttendanceController::class, 'swapShift'])->name('shift.update');
    });

    Route::prefix('/dashboard/coverage')->name('dashboard.coverage.')->middleware('role:super_admin,region_manager')->group(function () {
        Route::get('/', [CoverageController::class, 'index'])->name('index');
        Route::post('/assignments', [CoverageController::class, 'store'])->name('assignments.store');
    });

    Route::get('/dashboard/employee-performance', EmployeePerformanceController::class)
        ->middleware('role:super_admin,hr_manager,gov_supervisor')->name('dashboard.employee-performance.index');

    Route::prefix('/dashboard/hr')->name('dashboard.hr.')->middleware('role:super_admin,hr_manager')->group(function () {
        Route::get('/', [HumanResourcesController::class, 'index'])->name('index');
        Route::post('/employees', [HumanResourcesController::class, 'store'])->name('employees.store');
        Route::post('/assignments', [HumanResourcesController::class, 'assign'])->name('assignments.store');
        Route::patch('/leaves/{leave}', [HumanResourcesController::class, 'review'])->name('leaves.review');
    });

    Route::prefix('/dashboard/payroll')->name('dashboard.payroll.')->middleware('role:super_admin,finance_officer,hr_manager')->group(function () {
        Route::get('/', [PayrollController::class, 'index'])->name('index');
        Route::post('/runs', [PayrollController::class, 'generate'])->name('generate');
        Route::put('/{payroll}', [PayrollController::class, 'update'])->name('update');
        Route::patch('/{payroll}/payment', [PayrollController::class, 'pay'])->name('pay');
    });

    Route::prefix('/dashboard/employee-operations')->name('dashboard.employee-operations.')->middleware('role:stat_employee')->group(function () {
        Route::get('/', [EmployeeOperationsController::class, 'index'])->name('index');
        Route::post('/trips/{trip}/counting', [EmployeeOperationsController::class, 'start'])->name('trips.start');
        Route::put('/trips/{trip}/catch', [EmployeeOperationsController::class, 'storeCatch'])->name('trips.catch');
    });

    Route::prefix('/dashboard/profile')->name('dashboard.profile.')->middleware('role:employee_portal')->group(function () {
        Route::get('/', [EmploymentProfileController::class, 'show'])->name('show');
        Route::post('/leaves', [EmploymentProfileController::class, 'storeLeave'])->name('leaves.store');
    });

    Route::prefix('/dashboard/discrepancies')->name('dashboard.discrepancies.')->middleware('role:super_admin,port_supervisor,quality_supervisor')->group(function () {
        Route::get('/', [DiscrepancyController::class, 'index'])->name('index');
        Route::patch('/{discrepancy}/approval', [DiscrepancyController::class, 'approve'])->name('approve');
    });

    Route::prefix('/dashboard/harbors')->name('dashboard.harbors.')->middleware('role:super_admin,region_manager,gov_supervisor,port_supervisor')->group(function () {
        Route::get('/', [HarborController::class, 'index'])->name('index');
        Route::post('/', [HarborController::class, 'store'])->name('store');
        Route::get('/{port}', [HarborController::class, 'show'])->name('show');
        Route::put('/{port}', [HarborController::class, 'update'])->name('update');
        Route::put('/{port}/capacities', [HarborCapacityController::class, 'update'])->name('capacities.update');
        Route::post('/{port}/boats', [HarborBoatController::class, 'store'])->name('boats.store');
        Route::put('/{port}/boats/{boat}', [HarborBoatController::class, 'update'])->name('boats.update');
        Route::delete('/{port}/boats/{boat}', [HarborBoatController::class, 'destroy'])->name('boats.destroy');
        Route::post('/{port}/workers', [HarborWorkerController::class, 'store'])->name('workers.store');
        Route::put('/{port}/workers/{harborWorker}', [HarborWorkerController::class, 'update'])->name('workers.update');
        Route::delete('/{port}/workers/{harborWorker}', [HarborWorkerController::class, 'destroy'])->name('workers.destroy');
        Route::post('/{port}/licenses', [HarborLicenseController::class, 'store'])->name('licenses.store');
        Route::put('/{port}/licenses/{harborLicense}', [HarborLicenseController::class, 'update'])->name('licenses.update');
        Route::delete('/{port}/licenses/{harborLicense}', [HarborLicenseController::class, 'destroy'])->name('licenses.destroy');
        Route::get('/{port}/licenses/{harborLicense}/attachment', [HarborAttachmentController::class, 'license'])->name('licenses.attachment');
        Route::post('/{port}/violations', [HarborViolationController::class, 'store'])->name('violations.store');
        Route::put('/{port}/violations/{harborViolation}', [HarborViolationController::class, 'update'])->name('violations.update');
        Route::delete('/{port}/violations/{harborViolation}', [HarborViolationController::class, 'destroy'])->name('violations.destroy');
        Route::get('/{port}/violations/{harborViolation}/attachment', [HarborAttachmentController::class, 'violation'])->name('violations.attachment');
        Route::get('/{port}/export', HarborExportController::class)->name('export');
    });

    Route::prefix('/dashboard/trips')->name('dashboard.trips.')->middleware('role:super_admin,gov_supervisor,port_supervisor,stat_employee')->group(function () {
        Route::get('/', [TripController::class, 'index'])->name('index');
        Route::get('/create', [TripController::class, 'create'])->name('create');
        Route::post('/', [TripController::class, 'store'])->name('store');
        Route::patch('/{trip}/arrival', [TripController::class, 'arrive'])->name('arrive');
        Route::delete('/{trip}', [TripController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('/dashboard/master-data')
        ->name('dashboard.')
        ->middleware('role:super_admin')
        ->group(function () {
            Route::get('/', MasterDataController::class)->name('master-data.index');
            Route::post('/regions', [RegionController::class, 'store'])->name('regions.store');
            Route::delete('/regions/{region}', [RegionController::class, 'destroy'])->name('regions.destroy');
            Route::post('/governorates', [GovernorateController::class, 'store'])->name('governorates.store');
            Route::delete('/governorates/{governorate}', [GovernorateController::class, 'destroy'])->name('governorates.destroy');
            Route::post('/ports', [PortController::class, 'store'])->name('ports.store');
            Route::patch('/ports/{port}/status', [PortController::class, 'toggle'])->name('ports.toggle');
            Route::delete('/ports/{port}', [PortController::class, 'destroy'])->name('ports.destroy');
            Route::post('/boats', [BoatController::class, 'store'])->name('boats.store');
            Route::delete('/boats/{boat}', [BoatController::class, 'destroy'])->name('boats.destroy');
            Route::post('/captains', [CaptainController::class, 'store'])->name('captains.store');
            Route::delete('/captains/{captain}', [CaptainController::class, 'destroy'])->name('captains.destroy');
            Route::post('/species', [FishSpeciesController::class, 'store'])->name('species.store');
            Route::delete('/species/{species}', [FishSpeciesController::class, 'destroy'])->name('species.destroy');
        });

    Route::prefix('/dashboard/recruitment')
        ->name('dashboard.')
        ->middleware('role:super_admin,hr_manager')
        ->group(function () {
            Route::resource('jobs', EmploymentJobController::class)
                ->parameters(['jobs' => 'job'])
                ->except(['show', 'destroy']);
            Route::patch('/jobs/{job}/status', [EmploymentJobController::class, 'transition'])->name('jobs.transition');

            Route::get('/applications', [DashboardEmploymentApplicationController::class, 'index'])->name('applications.index');
            Route::get('/applications/{application}', [DashboardEmploymentApplicationController::class, 'show'])->name('applications.show');
            Route::patch('/applications/{application}/review', [DashboardEmploymentApplicationController::class, 'review'])->name('applications.review');
            Route::post('/applications/{application}/employee', [DashboardEmploymentApplicationController::class, 'provision'])->name('applications.provision');
            Route::get('/attachments/{attachment}', EmploymentAttachmentController::class)->name('attachments.download');
        });
});
