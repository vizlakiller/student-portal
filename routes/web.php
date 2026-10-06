<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\BrandingController;
use App\Http\Controllers\ChargeController;
use App\Http\Controllers\ClassroomController;
use App\Http\Controllers\ClassSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\LecturerController;
use App\Http\Controllers\MarkChangeController;
use App\Http\Controllers\MarksController;
use App\Http\Controllers\MyResultsController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProgrammeController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\ResultController;
use App\Http\Controllers\StatisticsController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\TermController;
use App\Http\Controllers\TimetableController;
use App\Http\Controllers\TimetableSlotController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
| Who can open each page is decided by the "can:..." middleware below,
| using the permissions in config/roles.php. Department limits (a head of
| department managing only their own departments) are checked in the controllers.
*/

// Visitors are sent to the dashboard; the "auth" middleware redirects
// anyone who is not logged in to the login page.
Route::redirect('/', '/dashboard');

// Only for visitors who are NOT logged in
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');   // see AppServiceProvider
});

// Only for logged-in users. "password.changed" makes new students set their own password first.
Route::middleware(['auth', 'password.changed'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Everyone lands here; it shows the right page for each role.
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/statistics', StatisticsController::class)->name('statistics')->middleware('can:view-statistics');

    // My profile and password (everyone)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    // ---------- Students ----------
    Route::get('/students/export', [StudentController::class, 'export'])->name('students.export')->middleware('can:view-students');
    Route::get('/students/create', [StudentController::class, 'create'])->name('students.create')->middleware('can:create-students');
    Route::post('/students', [StudentController::class, 'store'])->name('students.store')->middleware('can:create-students');
    Route::get('/students', [StudentController::class, 'index'])->name('students.index')->middleware('can:view-students');
    Route::get('/students/{student}', [StudentController::class, 'show'])->name('students.show')->middleware('can:view-students');
    Route::get('/students/{student}/edit', [StudentController::class, 'edit'])->name('students.edit')->middleware('can:edit-student-contact');
    Route::put('/students/{student}', [StudentController::class, 'update'])->name('students.update')->middleware('can:edit-student-contact');
    Route::delete('/students/{student}', [StudentController::class, 'destroy'])->name('students.destroy')->middleware('can:delete-students');
    Route::post('/students/{student}/reset-password', [StudentController::class, 'resetPassword'])->name('students.reset-password')->middleware('can:reset-student-password');
    Route::get('/students/{student}/transcript', [StudentController::class, 'transcript'])->name('students.transcript');   // checked in the controller

    // ---------- Subject registration and results ----------
    Route::get('/students/{student}/registrations/create', [RegistrationController::class, 'create'])->name('registrations.create')->middleware('can:register-subjects');
    Route::post('/students/{student}/registrations', [RegistrationController::class, 'store'])->name('registrations.store')->middleware('can:register-subjects');

    Route::scopeBindings()->group(function () {
        Route::get('/students/{student}/results/{result}/edit', [ResultController::class, 'edit'])->name('results.edit')->middleware('can:edit-marks-directly');
        Route::put('/students/{student}/results/{result}', [ResultController::class, 'update'])->name('results.update')->middleware('can:edit-marks-directly');
        Route::delete('/students/{student}/results/{result}', [ResultController::class, 'destroy'])->name('results.destroy')->middleware('can:register-subjects');
    });

    // Lecturers enter marks for their own sessions
    Route::get('/my-sessions', [MarksController::class, 'index'])->name('marks.index')->middleware('can:teach');
    Route::get('/sessions/{classSession}/marks', [MarksController::class, 'edit'])->name('marks.edit');      // checked in the controller
    Route::put('/sessions/{classSession}/marks', [MarksController::class, 'update'])->name('marks.update');  // checked in the controller

    // Head of department asks for a mark change; the session lecturer approves it
    Route::get('/mark-changes', [MarkChangeController::class, 'index'])->name('mark-changes.index')->middleware('can:view-mark-changes');
    Route::get('/results/{result}/mark-change', [MarkChangeController::class, 'create'])->name('mark-changes.create');
    Route::post('/results/{result}/mark-change', [MarkChangeController::class, 'store'])->name('mark-changes.store');
    Route::post('/mark-changes/{changeRequest}/approve', [MarkChangeController::class, 'approve'])->name('mark-changes.approve');
    Route::post('/mark-changes/{changeRequest}/reject', [MarkChangeController::class, 'reject'])->name('mark-changes.reject');

    // ---------- Lecturers, subjects, programmes and departments ----------
    Route::get('/lecturers', [LecturerController::class, 'index'])->name('lecturers.index')->middleware('can:view-lecturers');
    Route::get('/lecturers/{lecturer}', [LecturerController::class, 'show'])->name('lecturers.show')->middleware('can:view-lecturers');

    Route::get('/subjects', [SubjectController::class, 'index'])->name('subjects.index')->middleware('can:view-subjects');
    Route::resource('subjects', SubjectController::class)->except(['index', 'show'])->middleware('can:manage-subjects');

    Route::get('/programmes', [ProgrammeController::class, 'index'])->name('programmes.index')->middleware('can:view-programmes');
    Route::resource('programmes', ProgrammeController::class)->except(['index', 'show'])->middleware('can:manage-programmes');

    Route::get('/departments', [DepartmentController::class, 'index'])->name('departments.index')->middleware('can:view-departments');
    Route::resource('departments', DepartmentController::class)->except(['index', 'show'])->middleware('can:manage-departments');

    // ---------- Timetable ----------
    Route::get('/timetable', [TimetableController::class, 'index'])->name('timetable.index')->middleware('can:view-timetable');
    Route::get('/my-timetable', [TimetableController::class, 'mine'])->name('timetable.mine');   // lecturers and students; checked in the controller

    Route::get('/sessions', [ClassSessionController::class, 'index'])->name('sessions.index')->middleware('can:view-timetable');
    Route::get('/sessions/create', [ClassSessionController::class, 'create'])->name('sessions.create')->middleware('can:manage-timetable');
    Route::post('/sessions', [ClassSessionController::class, 'store'])->name('sessions.store')->middleware('can:manage-timetable');
    Route::get('/sessions/{classSession}', [ClassSessionController::class, 'show'])->name('sessions.show')->middleware('can:view-timetable');
    Route::get('/sessions/{classSession}/edit', [ClassSessionController::class, 'edit'])->name('sessions.edit')->middleware('can:manage-timetable');
    Route::put('/sessions/{classSession}', [ClassSessionController::class, 'update'])->name('sessions.update')->middleware('can:manage-timetable');
    Route::delete('/sessions/{classSession}', [ClassSessionController::class, 'destroy'])->name('sessions.destroy')->middleware('can:manage-timetable');
    Route::post('/sessions/{classSession}/slots', [TimetableSlotController::class, 'store'])->name('slots.store')->middleware('can:manage-timetable');
    Route::delete('/slots/{slot}', [TimetableSlotController::class, 'destroy'])->name('slots.destroy')->middleware('can:manage-timetable');

    Route::get('/classrooms', [ClassroomController::class, 'index'])->name('classrooms.index')->middleware('can:view-timetable');
    Route::resource('classrooms', ClassroomController::class)->except(['index', 'show'])->middleware('can:manage-classrooms');
    Route::get('/classrooms/{classroom}', [ClassroomController::class, 'show'])->name('classrooms.show')->middleware('can:view-timetable');

    Route::resource('terms', TermController::class)->except('show')->middleware('can:manage-terms');
    Route::post('/terms/{term}/current', [TermController::class, 'makeCurrent'])->name('terms.current')->middleware('can:manage-terms');

    // ---------- Fees and payments ----------
    Route::prefix('finance')->name('finance.')->group(function () {
        // Viewing: accountant and management
        Route::middleware('can:view-finance')->group(function () {
            Route::get('/', [FinanceController::class, 'index'])->name('index');
            Route::get('/students/{student}', [FinanceController::class, 'show'])->name('students.show');
            Route::get('/payments/{payment}/receipt', [PaymentController::class, 'receipt'])->name('payments.receipt');
        });

        // Changing: accountant only
        Route::middleware('can:manage-finance')->group(function () {
            Route::get('/students/{student}/payments/create', [PaymentController::class, 'create'])->name('payments.create');
            Route::post('/students/{student}/payments', [PaymentController::class, 'store'])->name('payments.store');
            Route::delete('/payments/{payment}', [PaymentController::class, 'destroy'])->name('payments.destroy')->middleware('can:delete-payments');
            Route::get('/students/{student}/charges/create', [ChargeController::class, 'create'])->name('charges.create');
            Route::post('/students/{student}/charges', [ChargeController::class, 'store'])->name('charges.store');
            Route::delete('/charges/{charge}', [ChargeController::class, 'destroy'])->name('charges.destroy');
            Route::get('/billing', [BillingController::class, 'create'])->name('billing.create');
            Route::post('/billing', [BillingController::class, 'store'])->name('billing.store');
        });
    });

    // ---------- Students' own pages ----------
    Route::middleware('can:view-own-results')->group(function () {
        Route::get('/my-results', [MyResultsController::class, 'index'])->name('my.results');
        Route::get('/my-results/transcript', [MyResultsController::class, 'transcript'])->name('my.transcript');
    });

    // ---------- System (super admin) ----------
    Route::resource('users', UserController::class)->except('show')->middleware('can:manage-users');

    Route::middleware('can:manage-branding')->group(function () {
        Route::get('/branding', [BrandingController::class, 'edit'])->name('branding.edit');
        Route::put('/branding', [BrandingController::class, 'update'])->name('branding.update');
        Route::delete('/branding/logo', [BrandingController::class, 'removeLogo'])->name('branding.logo.destroy');
        Route::delete('/branding', [BrandingController::class, 'reset'])->name('branding.reset');
    });
});
