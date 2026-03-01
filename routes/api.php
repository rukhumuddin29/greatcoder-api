<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\LeadController;
use App\Http\Controllers\Api\V1\CourseController;
use App\Http\Controllers\Api\V1\EnrollmentController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\ExpenseController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\WorkdayController;
use App\Http\Controllers\Api\V1\CompanyController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\ForgotPasswordController;
use App\Http\Controllers\Api\V1\RoleController;
use App\Http\Controllers\Api\V1\PermissionController;
use App\Http\Controllers\Api\V1\BulkEmailController;
use App\Http\Controllers\Api\V1\SalaryStructureController;
use App\Http\Controllers\Api\V1\AttendanceSystemController;
use App\Http\Controllers\Api\V1\PayrollController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\ActivityLogController;
use App\Http\Controllers\Api\V1\LeaveController;
use App\Http\Controllers\Api\V1\BdeScorecardController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\LeadDocumentController;
use App\Http\Controllers\Api\V1\HiringCompanyController;
use App\Http\Controllers\Api\V1\PlacementController;

Route::group(['prefix' => 'v1'], function () {

    // Public Routes
    Route::post('login', [AuthController::class , 'login']);
    Route::post('password/email', [ForgotPasswordController::class , 'sendResetLinkEmail']);
    Route::post('password/reset', [ForgotPasswordController::class , 'reset']);
    Route::get('company/public', [CompanyController::class , 'publicInfo']);

    // Protected Routes
    Route::group(['middleware' => 'auth:sanctum'], function () {

            // Dashboard
            Route::get('dashboard', [DashboardController::class , 'index']);
            Route::get('dashboard/lead-stats', [DashboardController::class , 'getLeadStats']);
            Route::get('dashboard/follow-ups', [DashboardController::class , 'getFollowUps']);
            Route::get('dashboard/interested-leads', [DashboardController::class , 'getInterestedLeads']);

            // Auth & Profile
            Route::get('/me', [AuthController::class , 'me']);
            Route::put('/profile', [ProfileController::class , 'update']);
            Route::post('/profile/avatar', [ProfileController::class , 'updateAvatar']);
            Route::post('/logout', [AuthController::class , 'logout']);

            // Workday Setup
            Route::group(['prefix' => 'workday-setup'], function () {
                    Route::get('/', [WorkdayController::class , 'index']);
                    Route::post('settings', [WorkdayController::class , 'updateSettings']);
                    Route::post('holidays', [WorkdayController::class , 'storeHoliday']);
                    Route::delete('holidays/{holiday}', [WorkdayController::class , 'destroyHoliday']);
                    Route::post('leave-policy', [WorkdayController::class , 'updateLeavePolicy'])->middleware('role:super_admin');
                }
                );

                // Leads
                Route::group(['prefix' => 'leads'], function () {
                    Route::get('/', [LeadController::class , 'index'])->middleware('permission:leads.view,leads.view_assigned');
                    Route::post('/', [LeadController::class , 'store'])->middleware('permission:leads.create');
                    Route::get('unassigned-counts', [LeadController::class , 'unassignedCounts'])->middleware('permission:leads.assign');
                    Route::get('pipeline', [LeadController::class , 'pipeline'])->middleware('permission:leads.view,leads.view_assigned');
                    Route::post('bulk-assign', [LeadController::class , 'bulkAssign'])->middleware('permission:leads.assign');
                    Route::post('bulk-import', [LeadController::class , 'bulkImport'])->middleware('permission:leads.create');
                    Route::post('check-duplicates', [LeadController::class , 'checkDuplicates'])->middleware('permission:leads.create,leads.view');
                    Route::post('merge', [LeadController::class , 'mergeLeads'])->middleware('permission:leads.update_all');
                    Route::get('duplicates', [LeadController::class , 'duplicates'])->middleware('permission:leads.view');

                    Route::get('{lead}', [LeadController::class , 'show'])->middleware('permission:leads.view,leads.view_assigned');
                    Route::put('{lead}', [LeadController::class , 'update'])->middleware('permission:leads.update');
                    Route::post('{lead}/call-logs', [LeadController::class , 'addCallLog'])->middleware('permission:leads.update');
                    Route::post('{lead}/assign', [LeadController::class , 'assign'])->middleware('permission:leads.assign');
                    Route::patch('{lead}/snooze', [LeadController::class , 'snoozeFollowUp'])->middleware('permission:leads.update');
                    Route::patch('{lead}/complete', [LeadController::class , 'completeFollowUp'])->middleware('permission:leads.update');
                    Route::patch('{lead}/status', [LeadController::class , 'updateStatus'])->middleware('permission:leads.update');

                    // Documents
                    Route::get('{lead}/documents', [LeadDocumentController::class , 'index'])->middleware('permission:leads.view,leads.view_assigned');
                    Route::post('{lead}/documents', [LeadDocumentController::class , 'store'])->middleware('permission:leads.update');
                }
                );

                Route::get('documents/{document}/download', [LeadDocumentController::class , 'download'])->middleware('permission:leads.view,leads.view_assigned');
                Route::delete('documents/{document}', [LeadDocumentController::class , 'destroy'])->middleware('permission:leads.update');

                // Courses
                Route::apiResource('courses', CourseController::class);

                // Enrollments
                Route::apiResource('enrollments', EnrollmentController::class)
                    ->except(['destroy'])
                    ->middleware(['index' => 'permission:enrollments.view', 'show' => 'permission:enrollments.view', 'store' => 'permission:enrollments.create', 'update' => 'permission:enrollments.create']);

                // Hiring Companies
                Route::apiResource('hiring-companies', HiringCompanyController::class);

                // Placement & Career Tracking
                Route::group(['prefix' => 'placements'], function () {
                    Route::get('report', [PlacementController::class , 'report']);
                    Route::post('{enrollment}/mock', [PlacementController::class , 'storeMock']);
                    Route::post('{enrollment}/interview', [PlacementController::class , 'storeInterview']);
                    Route::post('{enrollment}/success', [PlacementController::class , 'storePlacement']);
                    Route::patch('{enrollment}/status', [PlacementController::class , 'updateStatus']);
                }
                );

                // Payments
                Route::apiResource('payments', PaymentController::class)
                    ->only(['index', 'store', 'show'])
                    ->middleware(['index' => 'permission:payments.view', 'show' => 'permission:payments.view', 'store' => 'permission:payments.create']);

                // Expenses
                Route::get('expense-categories', [ExpenseController::class , 'categories']);
                Route::apiResource('expenses', ExpenseController::class);
                Route::post('expenses/{expense}/approve', [ExpenseController::class , 'approve'])->middleware('permission:expenses.approve');

                // Bulk Emails
                Route::group(['prefix' => 'bulk-emails'], function () {
                    Route::get('/', [BulkEmailController::class , 'index'])->middleware('permission:leads.bulk_email_history');
                    Route::get('counts', [BulkEmailController::class , 'getCounts'])->middleware('permission:leads.bulk_email');
                    Route::post('send', [BulkEmailController::class , 'send'])->middleware('permission:leads.bulk_email');
                }
                );

                // Salary Structures
                Route::group(['prefix' => 'salary-structures', 'middleware' => 'permission:payroll.manage'], function () {
                    Route::get('/', [SalaryStructureController::class , 'index']);
                    Route::get('{user}', [SalaryStructureController::class , 'show']);
                    Route::post('/', [SalaryStructureController::class , 'store']);
                }
                );

                // Attendance
                Route::group(['prefix' => 'attendance'], function () {
                    Route::get('my-today', [AttendanceSystemController::class , 'myToday']);
                    Route::get('my-history', [AttendanceSystemController::class , 'myHistory']);
                    Route::post('check-in', [AttendanceSystemController::class , 'checkIn']);
                    Route::post('check-out', [AttendanceSystemController::class , 'checkOut']);

                    Route::get('/', [AttendanceSystemController::class , 'index'])->middleware('permission:attendance.view');
                    Route::post('mark', [AttendanceSystemController::class , 'mark'])->middleware('permission:attendance.mark');
                    Route::post('bulk-mark', [AttendanceSystemController::class , 'bulkMark'])->middleware('permission:attendance.mark');
                    Route::post('mark-sundays', [AttendanceSystemController::class , 'markSundays'])->middleware('permission:attendance.mark');
                }
                );

                // Payroll
                Route::group(['prefix' => 'payroll'], function () {
                    Route::get('/', [PayrollController::class , 'index'])->middleware('permission:payroll.view');
                    Route::get('summary', [PayrollController::class , 'monthSummary'])->middleware('permission:payroll.view');
                    Route::post('generate', [PayrollController::class , 'generate'])->middleware('permission:payroll.generate');
                    Route::post('preview', [PayrollController::class , 'preview'])->middleware('permission:payroll.generate');
                    Route::get('{payroll}', [PayrollController::class , 'show'])->middleware('permission:payroll.view');
                    Route::post('{payroll}/approve', [PayrollController::class , 'approve'])->middleware('permission:payroll.approve');
                    Route::post('{payroll}/pay', [PayrollController::class , 'markPaid'])->middleware('permission:payroll.approve');
                    Route::post('bulk-approve', [PayrollController::class , 'bulkApprove'])->middleware('permission:payroll.approve');
                    Route::post('bulk-pay', [PayrollController::class , 'bulkPay'])->middleware('permission:payroll.approve');
                }
                );

                // BDE Performance Scorecard
                Route::group(['prefix' => 'bde-scorecard'], function () {
                    Route::get('leaderboard', [BdeScorecardController::class , 'leaderboard']);
                    Route::get('{userId}', [BdeScorecardController::class , 'show']);
                    Route::get('{userId}/trend', [BdeScorecardController::class , 'trend']);
                }
                )->middleware('permission:reports.view');

                // Notifications
                Route::group(['prefix' => 'notifications'], function () {
                    Route::get('/', [NotificationController::class , 'index']);
                    Route::get('unread-count', [NotificationController::class , 'unreadCount']);
                    Route::post('{id}/read', [NotificationController::class , 'markAsRead']);
                    Route::post('read-all', [NotificationController::class , 'markAllAsRead']);
                }
                );

                // Reports
                Route::group(['prefix' => 'reports'], function () {
                    Route::get('financial-summary', [ReportController::class , 'financialSummary'])->middleware('permission:reports.view');
                    Route::get('revenue-by-course', [ReportController::class , 'revenueByCourse'])->middleware('permission:reports.view');
                    Route::get('revenue-by-bde', [ReportController::class , 'revenueByBde'])->middleware('permission:reports.view');
                    Route::get('export', [ReportController::class , 'export'])->middleware('permission:reports.view');
                }
                );

                // Leave Management
                Route::group(['prefix' => 'leaves'], function () {
                    Route::get('my-leaves', [LeaveController::class , 'myLeaves']);
                    Route::get('my-balance', [LeaveController::class , 'myBalance']);
                    Route::post('apply', [LeaveController::class , 'apply']);
                    Route::post('{leaveRequest}/cancel', [LeaveController::class , 'cancel']);

                    Route::get('pending', [LeaveController::class , 'pending'])->middleware('permission:leaves.approve');
                    Route::get('all', [LeaveController::class , 'index'])->middleware('permission:leaves.view');
                    Route::post('{leaveRequest}/approve', [LeaveController::class , 'approve'])->middleware('permission:leaves.approve');
                    Route::post('{leaveRequest}/reject', [LeaveController::class , 'reject'])->middleware('permission:leaves.approve');
                }
                );

                Route::group(['prefix' => 'activity-logs'], function () {
                    Route::get('/', [ActivityLogController::class , 'index'])->middleware('permission:reports.view');
                    Route::get('model/{modelType}/{modelId}', [ActivityLogController::class , 'forModel'])
                        ->middleware('permission:reports.view,leads.view,leads.view_assigned');
                }
                );

                // Settings & Admin
                Route::get('company', [CompanyController::class , 'show']);
                Route::post('company', [CompanyController::class , 'update'])->middleware('role:super_admin');

                Route::get('users/bdes', [UserController::class , 'getBdes']);
                Route::apiResource('users', UserController::class)->middleware('permission:users.view');
                Route::apiResource('roles', RoleController::class)->middleware('permission:roles.view');
                Route::apiResource('permissions', PermissionController::class)->middleware('permission:roles.view');
            }
            );
        });
