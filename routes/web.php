<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\ClientMiddleware;
use App\Http\Middleware\OwnerMiddleware;
use App\Http\Middleware\FreelancerMiddleware;
use App\Http\Middleware\StudioPhotographerMiddleware;
use App\Http\Middleware\StudioHRMiddleware;
use App\Http\Middleware\StudioFinanceMiddleware;

// Payment Webhook Routes (unauthenticated, CSRF-exempt via bootstrap/app.php) ========================================================================================
Route::post('/webhook/paymongo', [\App\Http\Controllers\Client\BookingController::class, 'handleWebhook'])->name('webhook.paymongo');
Route::post('/webhook/stripe',   [\App\Http\Controllers\Client\BookingController::class, 'handleStripeWebhook'])->name('webhook.stripe');

// Auth Routes =========================================================================================================================================================
Route::prefix('auth')->group(function () {

    Route::get('/login',                            [\App\Http\Controllers\Auth\AuthController::class, 'index'])->name('login');
    Route::get('/register',                         [\App\Http\Controllers\Auth\AuthController::class, 'register'])->name('register');
    Route::get('/verify',                           [\App\Http\Controllers\Auth\AuthController::class, 'verify'])->name('verify');
    Route::post('/register',                        [\App\Http\Controllers\Auth\AuthController::class, 'store'])->name('auth.register.store');
    Route::post('/login',                           [\App\Http\Controllers\Auth\AuthController::class, 'login'])->name('auth.login.store');
    Route::post('/logout',                          [\App\Http\Controllers\Auth\AuthController::class, 'logout'])->name('auth.logout');
    Route::get('/verify-email/{token}',             [\App\Http\Controllers\Auth\AuthController::class, 'verifyEmail'])->name('auth.verify.email');

});

// Authenticated Routes ================================================================================================================================================
Route::middleware(['auth'])->group(function () {

    // Notifications Routes ================================================================================================================================================
    Route::prefix('notifications')->group(function () {
        
        Route::get('/unread-count',  [\App\Http\Controllers\NotificationController::class, 'getUnreadCount'])->name('notifications.unread-count');
        Route::get('/recent',        [\App\Http\Controllers\NotificationController::class, 'getRecent'])->name('notifications.recent');
        Route::get('/',              [\App\Http\Controllers\NotificationController::class, 'index'])->name('notifications.index');
        Route::post('/{id}/read',    [\App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('notifications.mark-read');
        Route::post('/read-all',     [\App\Http\Controllers\NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');
        Route::delete('/{id}',       [\App\Http\Controllers\NotificationController::class, 'destroy'])->name('notifications.destroy');

    });

    // Profile =================================================================================================================================================================
    Route::prefix('profile')->group(function () {

        Route::get('/data',               [\App\Http\Controllers\GeneralProfileController::class, 'getUserData'])->name('profile.data');
        Route::post('/update',            [\App\Http\Controllers\GeneralProfileController::class, 'update'])->name('profile.update');

    });

    // Admin Routes ========================================================================================================================================================
    Route::prefix('admin')->middleware([AdminMiddleware::class])->group(function () {

        // Profile
        Route::get('/profile',                      [\App\Http\Controllers\GeneralProfileController::class, 'admin'])->name('admin.profile');

        // Dashboard
        Route::get('/dashboard',                    [\App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('admin.dashboard');
        Route::get('/dashboard/filter',             [\App\Http\Controllers\Admin\DashboardController::class, 'filter'])->name('admin.dashboard.filter');
        Route::get('/dashboard/export',             [\App\Http\Controllers\Admin\DashboardController::class, 'export'])->name('admin.dashboard.export');

        // Manage Users         
        Route::get('/view/users',                   [\App\Http\Controllers\Admin\UserController::class, 'index'])->name('admin.user.index');
        Route::get('/users/data',                   [\App\Http\Controllers\Admin\UserController::class, 'getUsers'])->name('admin.user.data');
        Route::get('/users/{id}/details',           [\App\Http\Controllers\Admin\UserController::class, 'getUserDetails'])->name('admin.user.details');
        Route::delete('/users/{id}',                [\App\Http\Controllers\Admin\UserController::class, 'deleteUser'])->name('admin.user.delete');

        // Manage Studio            
        Route::get('/view/studio',                  [\App\Http\Controllers\Admin\StudioController::class, 'index'])->name('admin.studio.index');
        Route::get('/pending/studio',               [\App\Http\Controllers\Admin\StudioController::class, 'pending'])->name('admin.studio.pending');
        Route::post('/studio/{id}/approve',         [\App\Http\Controllers\Admin\StudioController::class, 'approve'])->name('admin.studio.approve');
        Route::post('/studio/{id}/reject',          [\App\Http\Controllers\Admin\StudioController::class, 'reject'])->name('admin.studio.reject');
        Route::delete('/studio/{id}',               [\App\Http\Controllers\Admin\StudioController::class, 'destroy'])->name('admin.studio.destroy');
    
        // Manage Freelancer    
        Route::get('/view/freelancers',             [\App\Http\Controllers\Admin\FreelancerController::class, 'index'])->name('admin.freelancer.index');
        Route::get('/freelancers/data',             [\App\Http\Controllers\Admin\FreelancerController::class, 'getFreelancers'])->name('admin.freelancer.data');
        Route::get('/freelancers/{id}/details',     [\App\Http\Controllers\Admin\FreelancerController::class, 'getFreelancerDetails'])->name('admin.freelancer.details');
    
        // Manage Categories    
        Route::get('/view/categories',              [\App\Http\Controllers\Admin\CategoriesController::class, 'index'])->name('admin.categories.index');
        Route::get('/create/categories',            [\App\Http\Controllers\Admin\CategoriesController::class, 'create'])->name('admin.categories.create');
        Route::post('/categories',                  [\App\Http\Controllers\Admin\CategoriesController::class, 'store'])->name('admin.categories.store');
        Route::put('/categories/{id}',              [\App\Http\Controllers\Admin\CategoriesController::class, 'update'])->name('admin.categories.update');
        Route::delete('/categories/{id}',           [\App\Http\Controllers\Admin\CategoriesController::class, 'destroy'])->name('admin.categories.delete');
        
        // Manage Locations         
        Route::get('/view/location',                [\App\Http\Controllers\Admin\LocationController::class, 'index'])->name('admin.location.index');
        Route::get('/create/location',              [\App\Http\Controllers\Admin\LocationController::class, 'create'])->name('admin.location.create');
        Route::post('/location',                    [\App\Http\Controllers\Admin\LocationController::class, 'store'])->name('admin.location.store');
        Route::get('/location/{id}',                [\App\Http\Controllers\Admin\LocationController::class, 'show'])->name('admin.location.show');
        Route::get('/location/{id}/edit',           [\App\Http\Controllers\Admin\LocationController::class, 'edit'])->name('admin.location.edit');
        Route::put('/location/{id}',                [\App\Http\Controllers\Admin\LocationController::class, 'update'])->name('admin.location.update');
        Route::delete('/location/{id}',             [\App\Http\Controllers\Admin\LocationController::class, 'destroy'])->name('admin.location.destroy');
        Route::get('/location/data/all',            [\App\Http\Controllers\Admin\LocationController::class, 'getLocations'])->name('admin.location.data');

        // Manage Subscription
        Route::get('/view/subscription',            [\App\Http\Controllers\Admin\SubscriptionController::class, 'index'])->name('admin.subscription.index');
        Route::get('/create/subscription',          [\App\Http\Controllers\Admin\SubscriptionController::class, 'create'])->name('admin.subscription.create');
        Route::post('/subscription',                [\App\Http\Controllers\Admin\SubscriptionController::class, 'store'])->name('admin.subscription.store');
        Route::get('/subscription/{id}',            [\App\Http\Controllers\Admin\SubscriptionController::class, 'show'])->name('admin.subscription.show');
        Route::get('/subscription/{id}/edit',       [\App\Http\Controllers\Admin\SubscriptionController::class, 'edit'])->name('admin.subscription.edit');
        Route::put('/subscription/{id}',            [\App\Http\Controllers\Admin\SubscriptionController::class, 'update'])->name('admin.subscription.update');
        Route::delete('/subscription/{id}',         [\App\Http\Controllers\Admin\SubscriptionController::class, 'destroy'])->name('admin.subscription.delete');
        Route::get('/subscription/data/all',        [\App\Http\Controllers\Admin\SubscriptionController::class, 'getPlans'])->name('admin.subscription.data');

    });

    // Studio Owner Routes =================================================================================================================================================
    Route::prefix('owner')->middleware([OwnerMiddleware::class])->group(function () {

        // Profile
        Route::get('/profile',                                  [\App\Http\Controllers\GeneralProfileController::class, 'owner'])->name('owner.profile');

        // Dashboard
        Route::get('/dashboard',                                [\App\Http\Controllers\StudioOwner\DashboardController::class, 'index'])->name('owner.dashboard');
        Route::get('/dashboard/filter',                         [\App\Http\Controllers\StudioOwner\DashboardController::class, 'filter'])->name('owner.dashboard.filter');
        Route::get('/dashboard/export',                         [\App\Http\Controllers\StudioOwner\DashboardController::class, 'export'])->name('owner.dashboard.export');

        // Manage Studio - Limit
        Route::middleware(['check.studio.limit'])->group(function () {

            Route::get('/create/studio',                        [\App\Http\Controllers\StudioOwner\StudioController::class, 'create'])->name('owner.studio.create');
            Route::post('/studio',                              [\App\Http\Controllers\StudioOwner\StudioController::class, 'store'])->name('owner.studio.store');
            
        });

        // Manage Studio                            
        Route::get('/view/studio',                                  [\App\Http\Controllers\StudioOwner\StudioController::class, 'index'])->middleware('permission:owner.studios.manage')->name('owner.studio.index');
        Route::get('/edit/studio/{id}',                             [\App\Http\Controllers\StudioOwner\StudioController::class, 'edit'])->middleware('permission:owner.studios.manage')->name('owner.studio.edit');
        Route::put('/studio/{id}',                                  [\App\Http\Controllers\StudioOwner\StudioController::class, 'update'])->middleware('permission:owner.studios.manage')->name('owner.studio.update');
        Route::get('/studio/barangays/{municipality}',              [\App\Http\Controllers\StudioOwner\StudioController::class, 'getBarangays'])->middleware('permission:owner.studios.manage')->name('owner.studio.get-barangays');
        Route::delete('/studio/{id}',                               [\App\Http\Controllers\StudioOwner\StudioController::class, 'destroy'])->middleware('permission:owner.studios.manage')->name('owner.studio.destroy');

        // Manage Bookings                          
        Route::get('/view/bookings',                                [\App\Http\Controllers\StudioOwner\BookingController::class, 'index'])->middleware('permission:owner.bookings.manage')->name('owner.booking.index');
        Route::get('/booking/history',                              [\App\Http\Controllers\StudioOwner\BookingController::class, 'history'])->middleware('permission:owner.bookings.manage')->name('owner.booking.history');
        Route::get('/bookings/{id}/details',                        [\App\Http\Controllers\StudioOwner\BookingController::class, 'getBookingDetails'])->middleware('permission:owner.bookings.manage')->name('owner.booking.details');
        Route::get('/bookings/{id}/available-photographers',        [\App\Http\Controllers\StudioOwner\BookingController::class, 'getAvailablePhotographers'])->middleware('permission:owner.bookings.manage')->name('owner.booking.available.photographers');
        Route::post('/bookings/{id}/assign-photographers',          [\App\Http\Controllers\StudioOwner\BookingController::class, 'assignPhotographers'])->middleware('permission:owner.bookings.manage')->name('owner.booking.assign.photographers');
        Route::delete('/assignments/{id}',                          [\App\Http\Controllers\StudioOwner\BookingController::class, 'removePhotographerAssignment'])->middleware('permission:owner.bookings.manage')->name('owner.booking.remove.assignment');
        Route::put('/assignments/{id}/status',                      [\App\Http\Controllers\StudioOwner\BookingController::class, 'updateAssignmentStatus'])->middleware('permission:owner.bookings.manage')->name('owner.booking.update.assignment.status');
        Route::put('/bookings/{id}/status',                         [\App\Http\Controllers\StudioOwner\BookingController::class, 'updateStatus'])->middleware('permission:owner.bookings.manage')->name('owner.booking.update.status');
        Route::put('/bookings/{id}/complete',                       [\App\Http\Controllers\StudioOwner\BookingController::class, 'completeBooking'])->middleware('permission:owner.bookings.manage')->name('owner.booking.complete');

        // Manage Online Gallery
        Route::get('/view/online-gallery',                          [\App\Http\Controllers\StudioOwner\OnlineGalleryController::class, 'index'])->middleware('permission:owner.online-gallery.manage')->name('owner.online-gallery.index');
        Route::get('/online-gallery/completed-bookings',            [\App\Http\Controllers\StudioOwner\OnlineGalleryController::class, 'getCompletedBookings'])->middleware('permission:owner.online-gallery.manage')->name('owner.online-gallery.completed-bookings');
        Route::get('/online-gallery/{bookingId}/details',           [\App\Http\Controllers\StudioOwner\OnlineGalleryController::class, 'getGalleryDetails'])->middleware('permission:owner.online-gallery.manage')->name('owner.online-gallery.details');
        Route::post('/online-gallery/{bookingId}/upload',           [\App\Http\Controllers\StudioOwner\OnlineGalleryController::class, 'uploadImages'])->middleware('permission:owner.online-gallery.manage')->name('owner.online-gallery.upload');
        Route::delete('/online-gallery/{galleryId}/image',          [\App\Http\Controllers\StudioOwner\OnlineGalleryController::class, 'deleteImage'])->middleware('permission:owner.online-gallery.manage')->name('owner.online-gallery.delete-image');
        Route::delete('/online-gallery/{galleryId}',                [\App\Http\Controllers\StudioOwner\OnlineGalleryController::class, 'deleteGallery'])->middleware('permission:owner.online-gallery.manage')->name('owner.online-gallery.delete');
        Route::put('/online-gallery/{galleryId}',                   [\App\Http\Controllers\StudioOwner\OnlineGalleryController::class, 'updateGallery'])->middleware('permission:owner.online-gallery.manage')->name('owner.online-gallery.update');
        
        // Manage Studio Schedule                       
        Route::get('/view/schedules',                               [\App\Http\Controllers\StudioOwner\StudioScheduleController::class, 'index'])->middleware('permission:owner.schedules.manage')->name('owner.studio-schedule.index');
        Route::get('/setup/studio-schedules',                       [\App\Http\Controllers\StudioOwner\StudioScheduleController::class, 'setupStudioSchedule'])->middleware('permission:owner.schedules.manage')->name('owner.setup-studio-schedules');
        Route::post('/store/studio-schedules',                      [\App\Http\Controllers\StudioOwner\StudioScheduleController::class, 'store'])->middleware('permission:owner.schedules.manage')->name('owner.studio-schedule.store');
        Route::delete('/delete/studio-schedules/{id}',              [\App\Http\Controllers\StudioOwner\StudioScheduleController::class, 'destroy'])->middleware('permission:owner.schedules.manage')->name('owner.studio-schedule.destroy');

        // Studio Members                           
        Route::get('/view/members',                                 [\App\Http\Controllers\StudioOwner\StudioMemberController::class, 'index'])->middleware('permission:owner.members.manage')->name('owner.members.index');
        Route::get('/invite/members',                               [\App\Http\Controllers\StudioOwner\StudioMemberController::class, 'invite'])->middleware('permission:owner.members.manage')->name('owner.members.invite');
        Route::get('/apply/members',                                [\App\Http\Controllers\StudioOwner\StudioMemberController::class, 'apply'])->middleware('permission:owner.members.manage')->name('owner.members.apply');
        Route::post('/members/invite',                              [\App\Http\Controllers\StudioOwner\StudioMemberController::class, 'store'])->middleware('permission:owner.members.manage')->name('owner.members.invite.store');
        Route::post('/members/{id}/cancel',                         [\App\Http\Controllers\StudioOwner\StudioMemberController::class, 'cancel'])->middleware('permission:owner.members.manage')->name('owner.members.cancel');
        Route::get('/members/freelancer/{id}/details',              [\App\Http\Controllers\StudioOwner\StudioMemberController::class, 'getFreelancerDetails'])->middleware('permission:owner.members.manage')->name('owner.members.freelancer.details');

        // Studio Photographers 
        Route::get('/view/studio-photographers',                    [\App\Http\Controllers\StudioOwner\StudioPhotographersController::class, 'index'])->middleware('permission:owner.photographers.manage')->name('owner.studio-photographers.index');
        Route::get('/create/studio-photographers',                  [\App\Http\Controllers\StudioOwner\StudioPhotographersController::class, 'create'])->middleware('permission:owner.photographers.manage')->name('owner.studio-photographers.create');
        Route::post('/studio-photographers',                        [\App\Http\Controllers\StudioOwner\StudioPhotographersController::class, 'store'])->middleware('permission:owner.photographers.manage')->name('owner.studio-photographers.store');
        Route::get('/studio-photographers/{id}',                    [\App\Http\Controllers\StudioOwner\StudioPhotographersController::class, 'show'])->middleware('permission:owner.photographers.manage')->name('owner.studio-photographers.show');
        Route::get('/studio/{id}/services',                         [\App\Http\Controllers\StudioOwner\StudioPhotographersController::class, 'getStudioServices'])->middleware('permission:owner.photographers.manage')->name('owner.studio.services');

        // Manage Category Services                         
        Route::get('/view/services',                                [\App\Http\Controllers\StudioOwner\ServicesController::class, 'index'])->middleware('permission:owner.services.manage')->name('owner.services.index');
        Route::get('/create/services',                              [\App\Http\Controllers\StudioOwner\ServicesController::class, 'create'])->middleware('permission:owner.services.manage')->name('owner.services.create');
        Route::post('/services',                                    [\App\Http\Controllers\StudioOwner\ServicesController::class, 'store'])->middleware('permission:owner.services.manage')->name('owner.services.store');
        Route::get('/services/{id}',                                [\App\Http\Controllers\StudioOwner\ServicesController::class, 'show'])->middleware('permission:owner.services.manage')->name('owner.services.show');
        Route::get('/services/{id}/edit',                           [\App\Http\Controllers\StudioOwner\ServicesController::class, 'edit'])->middleware('permission:owner.services.manage')->name('owner.services.edit');
        Route::put('/services/{id}',                                [\App\Http\Controllers\StudioOwner\ServicesController::class, 'update'])->middleware('permission:owner.services.manage')->name('owner.services.update');
        Route::delete('/services/{id}',                             [\App\Http\Controllers\StudioOwner\ServicesController::class, 'destroy'])->middleware('permission:owner.services.manage')->name('owner.services.destroy');
        Route::get('/services/data/get',                            [\App\Http\Controllers\StudioOwner\ServicesController::class, 'getServices'])->middleware('permission:owner.services.manage')->name('owner.services.data');

        // Manage Packages  
        Route::get('/view/packages',                                [\App\Http\Controllers\StudioOwner\PackagesController::class, 'index'])->middleware('permission:owner.packages.manage')->name('owner.packages.index');
        Route::get('/create/packages',                              [\App\Http\Controllers\StudioOwner\PackagesController::class, 'create'])->middleware('permission:owner.packages.manage')->name('owner.packages.create');
        Route::post('/packages',                                    [\App\Http\Controllers\StudioOwner\PackagesController::class, 'store'])->middleware('permission:owner.packages.manage')->name('owner.packages.store');
        Route::get('/packages/lists',                               [\App\Http\Controllers\StudioOwner\PackagesController::class, 'list'])->middleware('permission:owner.packages.manage')->name('owner.packages.list');
        Route::get('/packages/{package}',                           [\App\Http\Controllers\StudioOwner\PackagesController::class, 'show'])->middleware('permission:owner.packages.manage')->name('owner.packages.show');

        // Manage Subscription  
        Route::get('/view/subscription',                            [\App\Http\Controllers\StudioOwner\SubscriptionController::class, 'index'])->middleware('permission:owner.subscription.manage')->name('owner.subscription.index');
        Route::get('/view/status',                                  [\App\Http\Controllers\StudioOwner\SubscriptionController::class, 'status'])->middleware('permission:owner.subscription.manage')->name('owner.subscription.status');
        Route::get('/subscription/{id}',                            [\App\Http\Controllers\StudioOwner\SubscriptionController::class, 'show'])->middleware('permission:owner.subscription.manage')->name('owner.subscription.show');
        Route::post('/subscription/subscribe',                      [\App\Http\Controllers\StudioOwner\SubscriptionController::class, 'subscribe'])->middleware('permission:owner.subscription.manage')->name('owner.subscription.subscribe');
        Route::get('/subscription/history/data',                    [\App\Http\Controllers\StudioOwner\SubscriptionController::class, 'history'])->middleware('permission:owner.subscription.manage')->name('owner.subscription.history');
        Route::get('/subscription/status/data',                     [\App\Http\Controllers\StudioOwner\SubscriptionController::class, 'getStatusData'])->middleware('permission:owner.subscription.manage')->name('owner.subscription.status.data');
        Route::post('/subscription/{id}/cancel',                    [\App\Http\Controllers\StudioOwner\SubscriptionController::class, 'cancel'])->middleware('permission:owner.subscription.manage')->name('owner.subscription.cancel');
        Route::get('/subscription/{id}/details',                    [\App\Http\Controllers\StudioOwner\SubscriptionController::class, 'getSubscriptionDetails'])->middleware('permission:owner.subscription.manage')->name('owner.subscription.details');
        Route::get('/subscription/verify/{reference}',              [\App\Http\Controllers\StudioOwner\SubscriptionController::class, 'verifyPayment'])->middleware('permission:owner.subscription.manage')->name('owner.subscription.verify');
        Route::get('/subscription/success/{reference}',             [\App\Http\Controllers\StudioOwner\SubscriptionController::class, 'paymentSuccess'])->middleware('permission:owner.subscription.manage')->name('owner.subscription.success');
        Route::get('/subscription/failed/{reference}',              [\App\Http\Controllers\StudioOwner\SubscriptionController::class, 'paymentFailed'])->middleware('permission:owner.subscription.manage')->name('owner.subscription.failed');

        // Manage Employee  
        Route::get('/view/employee',                                [\App\Http\Controllers\StudioOwner\EmployeeController::class, 'index'])->middleware('permission:owner.employees.manage')->name('owner.employee.index');
        Route::get('/create/employee',                              [\App\Http\Controllers\StudioOwner\EmployeeController::class, 'create'])->middleware('permission:owner.employees.manage')->name('owner.employee.create');
        Route::post('/employee',                                    [\App\Http\Controllers\StudioOwner\EmployeeController::class, 'store'])->middleware('permission:owner.employees.manage')->name('owner.employee.store');
        Route::get('/employees/data',                               [\App\Http\Controllers\StudioOwner\EmployeeController::class, 'getEmployees'])->middleware('permission:owner.employees.manage')->name('owner.employee.data');
        Route::get('/employee/categories',                          [\App\Http\Controllers\StudioOwner\EmployeeController::class, 'getCategories'])->middleware('permission:owner.employees.manage')->name('owner.employee.categories');
        Route::get('/employee/{id}',                                [\App\Http\Controllers\StudioOwner\EmployeeController::class, 'show'])->middleware('permission:owner.employees.manage')->name('owner.employee.show');
        Route::put('/employee/{id}/status',                         [\App\Http\Controllers\StudioOwner\EmployeeController::class, 'updateStatus'])->middleware('permission:owner.employees.manage')->name('owner.employee.update-status');
        Route::put('/employee/{id}/schedule',                       [\App\Http\Controllers\StudioOwner\EmployeeController::class, 'updateSchedule'])->middleware('permission:owner.employees.manage')->name('owner.employee.update-schedule');
        Route::delete('/employee/{id}',                             [\App\Http\Controllers\StudioOwner\EmployeeController::class, 'destroy'])->middleware('permission:owner.employees.manage')->name('owner.employee.destroy');
        Route::get('/employee/{studioId}/services/{categoryId}',    [\App\Http\Controllers\StudioOwner\EmployeeController::class, 'getServicesByCategory'])->middleware('permission:owner.employees.manage')->name('owner.employee.services-by-category');

        // HR Leave Requests
        Route::get('/hr-leave-requests',                            [\App\Http\Controllers\StudioOwner\LeaveRequestController::class, 'index'])->middleware('permission:owner.leave-requests.manage')->name('owner.hr-leave-requests.index');
        Route::get('/hr-leave-requests/{id}',                       [\App\Http\Controllers\StudioOwner\LeaveRequestController::class, 'show'])->middleware('permission:owner.leave-requests.manage')->name('owner.hr-leave-requests.show');
        Route::post('/hr-leave-requests/{id}/{action}',             [\App\Http\Controllers\StudioOwner\LeaveRequestController::class, 'process'])->middleware('permission:owner.leave-requests.manage')->name('owner.hr-leave-requests.process');
        Route::get('/hr-overtime-requests',                         [\App\Http\Controllers\StudioOwner\OvertimeRequestController::class, 'index'])->middleware('permission:owner.overtime-requests.manage')->name('owner.hr-overtime-requests.index');
        Route::get('/hr-overtime-requests/{id}',                    [\App\Http\Controllers\StudioOwner\OvertimeRequestController::class, 'show'])->middleware('permission:owner.overtime-requests.manage')->name('owner.hr-overtime-requests.show');
        Route::post('/hr-overtime-requests/{id}/{action}',          [\App\Http\Controllers\StudioOwner\OvertimeRequestController::class, 'process'])->middleware('permission:owner.overtime-requests.manage')->name('owner.hr-overtime-requests.process');

        // Procurement
        Route::get('/procurement',                                  [\App\Http\Controllers\StudioOwner\ProcurementApprovalController::class, 'index'])->middleware('permission:owner.procurement.view,owner.procurement.approve,owner.procurement.report')->name('owner.procurement.index');
        Route::get('/procurement/{id}',                             [\App\Http\Controllers\StudioOwner\ProcurementApprovalController::class, 'show'])->middleware('permission:owner.procurement.view,owner.procurement.approve,owner.procurement.report')->name('owner.procurement.show');
        Route::post('/procurement/{id}/{action}',                   [\App\Http\Controllers\StudioOwner\ProcurementApprovalController::class, 'process'])->middleware('permission:owner.procurement.approve')->name('owner.procurement.process');

        // Manage Roles
        Route::get('/view/roles',                                   [\App\Http\Controllers\StudioOwner\RoleController::class, 'index'])->middleware('permission:owner.roles.manage')->name('owner.role.index');
        Route::get('/roles/data',                                   [\App\Http\Controllers\StudioOwner\RoleController::class, 'getRoles'])->middleware('permission:owner.roles.manage')->name('owner.role.data');
        Route::post('/roles',                                       [\App\Http\Controllers\StudioOwner\RoleController::class, 'store'])->middleware('permission:owner.roles.manage')->name('owner.role.store');
        Route::get('/roles/{id}',                                   [\App\Http\Controllers\StudioOwner\RoleController::class, 'show'])->middleware('permission:owner.roles.manage')->name('owner.role.show');
        Route::put('/roles/{id}',                                   [\App\Http\Controllers\StudioOwner\RoleController::class, 'update'])->middleware('permission:owner.roles.manage')->name('owner.role.update');
        Route::put('/roles/{id}/permissions',                       [\App\Http\Controllers\StudioOwner\RoleController::class, 'updatePermissions'])->middleware('permission:owner.roles.manage')->name('owner.role.update-permissions');
        Route::delete('/roles/{id}',                                [\App\Http\Controllers\StudioOwner\RoleController::class, 'destroy'])->middleware('permission:owner.roles.manage')->name('owner.role.destroy');
        Route::post('/roles/{id}/toggle-status',                    [\App\Http\Controllers\StudioOwner\RoleController::class, 'toggleStatus'])->middleware('permission:owner.roles.manage')->name('owner.role.toggle-status');

        // Manage Permissions
        Route::get('/view/permissions',                             [\App\Http\Controllers\StudioOwner\PermissionController::class, 'index'])->middleware('permission:owner.permissions.manage')->name('owner.permission.index');
        Route::get('/permissions/data',                             [\App\Http\Controllers\StudioOwner\PermissionController::class, 'getPermissions'])->middleware('permission:owner.permissions.manage')->name('owner.permission.data');
        Route::get('/permissions/all',                              [\App\Http\Controllers\StudioOwner\PermissionController::class, 'getAllPermissions'])->middleware('permission:owner.permissions.manage')->name('owner.permission.all');
        Route::post('/permissions',                                 [\App\Http\Controllers\StudioOwner\PermissionController::class, 'store'])->middleware('permission:owner.permissions.manage')->name('owner.permission.store');
        Route::get('/permissions/{id}',                             [\App\Http\Controllers\StudioOwner\PermissionController::class, 'show'])->middleware('permission:owner.permissions.manage')->name('owner.permission.show');
        Route::put('/permissions/{id}',                             [\App\Http\Controllers\StudioOwner\PermissionController::class, 'update'])->middleware('permission:owner.permissions.manage')->name('owner.permission.update');
        Route::delete('/permissions/{id}',                          [\App\Http\Controllers\StudioOwner\PermissionController::class, 'destroy'])->middleware('permission:owner.permissions.manage')->name('owner.permission.destroy');
        Route::post('/permissions/{id}/toggle-status',              [\App\Http\Controllers\StudioOwner\PermissionController::class, 'toggleStatus'])->middleware('permission:owner.permissions.manage')->name('owner.permission.toggle-status');

        // Manage Payroll  
        Route::get('/payroll-settings',                             [\App\Http\Controllers\StudioOwner\PayrollSettingsController::class, 'index'])->middleware('permission:owner.payroll.manage')->name('owner.payroll-settings.index');
        Route::get('/payroll-settings/create',                      [\App\Http\Controllers\StudioOwner\PayrollSettingsController::class, 'create'])->middleware('permission:owner.payroll.manage')->name('owner.payroll-settings.create');
        Route::post('/payroll-settings',                            [\App\Http\Controllers\StudioOwner\PayrollSettingsController::class, 'store'])->middleware('permission:owner.payroll.manage')->name('owner.payroll-settings.store');
        Route::get('/payroll-settings/employees',                   [\App\Http\Controllers\StudioOwner\PayrollSettingsController::class, 'getEmployees'])->middleware('permission:owner.payroll.manage')->name('owner.payroll-settings.employees');
        Route::get('/payroll-settings/data',                        [\App\Http\Controllers\StudioOwner\PayrollSettingsController::class, 'getPayrollSettings'])->middleware('permission:owner.payroll.manage')->name('owner.payroll-settings.data');
        Route::get('/payroll-settings/{id}',                        [\App\Http\Controllers\StudioOwner\PayrollSettingsController::class, 'show'])->middleware('permission:owner.payroll.manage')->name('owner.payroll-settings.show');
        Route::get('/payroll-settings/{id}/edit',                   [\App\Http\Controllers\StudioOwner\PayrollSettingsController::class, 'edit'])->middleware('permission:owner.payroll.manage')->name('owner.payroll-settings.edit');
        Route::put('/payroll-settings/{id}',                        [\App\Http\Controllers\StudioOwner\PayrollSettingsController::class, 'update'])->middleware('permission:owner.payroll.manage')->name('owner.payroll-settings.update');
        Route::put('/payroll-settings/{id}/status',                 [\App\Http\Controllers\StudioOwner\PayrollSettingsController::class, 'updateStatus'])->middleware('permission:owner.payroll.manage')->name('owner.payroll-settings.status');
        Route::delete('/payroll-settings/{id}',                     [\App\Http\Controllers\StudioOwner\PayrollSettingsController::class, 'destroy'])->middleware('permission:owner.payroll.manage')->name('owner.payroll-settings.destroy');

        // Manage Chatbot
        Route::prefix('chatbot')->middleware('permission:owner.chatbot.manage')->name('owner.chatbot.')->group(function () {
            Route::get('/config',                                   [\App\Http\Controllers\StudioOwner\ChatbotConfigController::class, 'index'])->name('config');
            Route::get('/config/data',                              [\App\Http\Controllers\StudioOwner\ChatbotConfigController::class, 'getConfig'])->name('config.get');
            Route::post('/config/save',                             [\App\Http\Controllers\StudioOwner\ChatbotConfigController::class, 'saveConfig'])->name('config.save');
            Route::post('/config/toggle',                           [\App\Http\Controllers\StudioOwner\ChatbotConfigController::class, 'toggleStatus'])->name('config.toggle');
            
            Route::get('/intents',                                  [\App\Http\Controllers\StudioOwner\ChatbotConfigController::class, 'getIntents'])->name('intents.get');
            Route::post('/intents',                                 [\App\Http\Controllers\StudioOwner\ChatbotConfigController::class, 'storeIntent'])->name('intents.store');
            Route::get('/intents/{id}',                             [\App\Http\Controllers\StudioOwner\ChatbotConfigController::class, 'getIntent'])->name('intents.show');
            Route::put('/intents/{id}',                             [\App\Http\Controllers\StudioOwner\ChatbotConfigController::class, 'updateIntent'])->name('intents.update');
            Route::delete('/intents/{id}',                          [\App\Http\Controllers\StudioOwner\ChatbotConfigController::class, 'deleteIntent'])->name('intents.delete');
            Route::post('/intents/{id}/toggle',                     [\App\Http\Controllers\StudioOwner\ChatbotConfigController::class, 'toggleIntentStatus'])->name('intents.toggle');
            
            Route::get('/conversations',                            [\App\Http\Controllers\StudioOwner\ChatbotConfigController::class, 'getConversations'])->name('conversations');
            Route::get('/conversations/{id}',                       [\App\Http\Controllers\StudioOwner\ChatbotConfigController::class, 'getConversationDetails'])->name('conversations.details');
        });

        // Inquiries                            
        Route::get('/view/inquiries',                              [\App\Http\Controllers\StudioOwner\InquiryController::class, 'index'])->middleware('permission:owner.inquiries.manage')->name('owner.inquiries.index');
    });

    // Studio HR Routes ====================================================================================================================================================
    Route::prefix('studio-hr')->middleware([StudioHRMiddleware::class])->group(function () {

        // Profile
        Route::get('/profile',                                      [\App\Http\Controllers\GeneralProfileController::class, 'studioHR'])->name('studio-hr.profile');

        // Dashboard
        Route::get('/dashboard',                                    [\App\Http\Controllers\StudioHR\DashboardController::class, 'index'])->middleware('permission:studio-hr.dashboard.view')->name('studio-hr.dashboard');
        Route::get('/dashboard/filter',                             [\App\Http\Controllers\StudioHR\DashboardController::class, 'filter'])->middleware('permission:studio-hr.dashboard.view')->name('studio-hr.dashboard.filter');
        Route::get('/dashboard/export',                             [\App\Http\Controllers\StudioHR\DashboardController::class, 'export'])->middleware('permission:studio-hr.dashboard.view')->name('studio-hr.dashboard.export');

        // Leave Requests
        Route::get('/leave-requests/create',                        [\App\Http\Controllers\StudioHR\LeaveRequestController::class, 'create'])->middleware('permission:studio-hr.leave-requests.manage')->name('studio-hr.leave-requests.create');
        Route::get('/leave-requests',                               [\App\Http\Controllers\StudioHR\LeaveRequestController::class, 'index'])->middleware('permission:studio-hr.leave-requests.manage')->name('studio-hr.leave-requests.index');
        Route::post('/leave-requests',                              [\App\Http\Controllers\StudioHR\LeaveRequestController::class, 'store'])->middleware('permission:studio-hr.leave-requests.manage')->name('studio-hr.leave-requests.store');
        Route::get('/leave-requests/{id}',                          [\App\Http\Controllers\StudioHR\LeaveRequestController::class, 'show'])->middleware('permission:studio-hr.leave-requests.manage')->name('studio-hr.leave-requests.show');
        Route::put('/leave-requests/{id}',                          [\App\Http\Controllers\StudioHR\LeaveRequestController::class, 'update'])->middleware('permission:studio-hr.leave-requests.manage')->name('studio-hr.leave-requests.update');
        Route::post('/leave-requests/{id}/cancel',                  [\App\Http\Controllers\StudioHR\LeaveRequestController::class, 'cancel'])->middleware('permission:studio-hr.leave-requests.manage')->name('studio-hr.leave-requests.cancel');
        Route::delete('/leave-requests/{id}',                       [\App\Http\Controllers\StudioHR\LeaveRequestController::class, 'destroy'])->middleware('permission:studio-hr.leave-requests.manage')->name('studio-hr.leave-requests.destroy');
        Route::get('/employees-leave-requests',                     [\App\Http\Controllers\StudioHR\LeaveRequestController::class, 'employeesIndex'])->middleware('permission:studio-hr.leave-requests.manage')->name('studio-hr.employees-leave-requests.index');
        Route::get('/employees-leave-requests/{id}',                [\App\Http\Controllers\StudioHR\LeaveRequestController::class, 'employeeShow'])->middleware('permission:studio-hr.leave-requests.manage')->name('studio-hr.employees-leave-requests.show');
        Route::post('/employees-leave-requests/{id}/{action}',      [\App\Http\Controllers\StudioHR\LeaveRequestController::class, 'process'])->middleware('permission:studio-hr.leave-requests.manage')->name('studio-hr.employees-leave-requests.process');
        Route::get('/overtime-requests/create',                     [\App\Http\Controllers\StudioHR\OvertimeRequestController::class, 'create'])->middleware('permission:studio-hr.overtime-requests.manage')->name('studio-hr.overtime-requests.create');
        Route::get('/overtime-requests',                            [\App\Http\Controllers\StudioHR\OvertimeRequestController::class, 'index'])->middleware('permission:studio-hr.overtime-requests.manage')->name('studio-hr.overtime-requests.index');
        Route::post('/overtime-requests',                           [\App\Http\Controllers\StudioHR\OvertimeRequestController::class, 'store'])->middleware('permission:studio-hr.overtime-requests.manage')->name('studio-hr.overtime-requests.store');
        Route::get('/overtime-requests/{id}',                       [\App\Http\Controllers\StudioHR\OvertimeRequestController::class, 'show'])->middleware('permission:studio-hr.overtime-requests.manage')->name('studio-hr.overtime-requests.show');
        Route::put('/overtime-requests/{id}',                       [\App\Http\Controllers\StudioHR\OvertimeRequestController::class, 'update'])->middleware('permission:studio-hr.overtime-requests.manage')->name('studio-hr.overtime-requests.update');
        Route::post('/overtime-requests/{id}/cancel',               [\App\Http\Controllers\StudioHR\OvertimeRequestController::class, 'cancel'])->middleware('permission:studio-hr.overtime-requests.manage')->name('studio-hr.overtime-requests.cancel');
        Route::delete('/overtime-requests/{id}',                    [\App\Http\Controllers\StudioHR\OvertimeRequestController::class, 'destroy'])->middleware('permission:studio-hr.overtime-requests.manage')->name('studio-hr.overtime-requests.destroy');
        Route::get('/employees-overtime-requests',                  [\App\Http\Controllers\StudioHR\OvertimeRequestController::class, 'employeesIndex'])->middleware('permission:studio-hr.overtime-requests.manage')->name('studio-hr.employees-overtime-requests.index');
        Route::get('/employees-overtime-requests/{id}',             [\App\Http\Controllers\StudioHR\OvertimeRequestController::class, 'employeeShow'])->middleware('permission:studio-hr.overtime-requests.manage')->name('studio-hr.employees-overtime-requests.show');
        Route::post('/employees-overtime-requests/{id}/{action}',   [\App\Http\Controllers\StudioHR\OvertimeRequestController::class, 'process'])->middleware('permission:studio-hr.overtime-requests.manage')->name('studio-hr.employees-overtime-requests.process');

        // Manage Employee
        Route::get('/view/employee',                                [\App\Http\Controllers\StudioHR\EmployeeController::class, 'index'])->middleware('permission:studio-hr.employees.view,studio-hr.employee.create,studio-hr.employee.edit,studio-hr.employee.delete')->name('studio-hr.employee.index');
        Route::get('/create/employee',                              [\App\Http\Controllers\StudioHR\EmployeeController::class, 'create'])->middleware('permission:studio-hr.employee.create')->name('studio-hr.employee.create');
        Route::post('/employee',                                    [\App\Http\Controllers\StudioHR\EmployeeController::class, 'store'])->middleware('permission:studio-hr.employee.create')->name('studio-hr.employee.store');
        Route::get('/employees/data',                               [\App\Http\Controllers\StudioHR\EmployeeController::class, 'getEmployees'])->middleware('permission:studio-hr.employees.view,studio-hr.employee.create,studio-hr.employee.edit,studio-hr.employee.delete')->name('studio-hr.employee.data');
        Route::get('/employee/categories',                          [\App\Http\Controllers\StudioHR\EmployeeController::class, 'getCategories'])->middleware('permission:studio-hr.employee.create')->name('studio-hr.employee.categories');
        Route::get('/employee/{id}',                                [\App\Http\Controllers\StudioHR\EmployeeController::class, 'show'])->middleware('permission:studio-hr.employees.view,studio-hr.employee.edit,studio-hr.employee.delete')->name('studio-hr.employee.show');
        Route::put('/employee/{id}/status',                         [\App\Http\Controllers\StudioHR\EmployeeController::class, 'updateStatus'])->middleware('permission:studio-hr.employee.edit')->name('studio-hr.employee.update-status');
        Route::put('/employee/{id}/schedule',                       [\App\Http\Controllers\StudioHR\EmployeeController::class, 'updateSchedule'])->middleware('permission:studio-hr.schedules.manage')->name('studio-hr.employee.update-schedule');
        Route::delete('/employee/{id}',                             [\App\Http\Controllers\StudioHR\EmployeeController::class, 'destroy'])->middleware('permission:studio-hr.employee.delete')->name('studio-hr.employee.destroy');
        Route::get('/employee/{studioId}/services/{categoryId}',    [\App\Http\Controllers\StudioHR\EmployeeController::class, 'getServicesByCategory'])->middleware('permission:studio-hr.employee.create')->name('studio-hr.employee.services-by-category');

        // Manage Payroll  
        Route::get('/payroll-settings',                            [\App\Http\Controllers\StudioHR\PayrollSettingsController::class, 'index'])->middleware('permission:studio-hr.payroll.view,studio-hr.payroll.manage')->name('studio-hr.payroll-settings.index');
        Route::get('/payroll-settings/create',                     [\App\Http\Controllers\StudioHR\PayrollSettingsController::class, 'create'])->middleware('permission:studio-hr.payroll.create,studio-hr.payroll.manage')->name('studio-hr.payroll-settings.create');
        Route::post('/payroll-settings',                           [\App\Http\Controllers\StudioHR\PayrollSettingsController::class, 'store'])->middleware('permission:studio-hr.payroll.create,studio-hr.payroll.manage')->name('studio-hr.payroll-settings.store');
        Route::get('/payroll-settings/employees',                  [\App\Http\Controllers\StudioHR\PayrollSettingsController::class, 'getEmployees'])->middleware('permission:studio-hr.payroll.view,studio-hr.payroll.create,studio-hr.payroll.manage')->name('studio-hr.payroll-settings.employees');
        Route::get('/payroll-settings/data',                       [\App\Http\Controllers\StudioHR\PayrollSettingsController::class, 'getPayrollSettings'])->middleware('permission:studio-hr.payroll.view,studio-hr.payroll.manage')->name('studio-hr.payroll-settings.data');
        Route::get('/payroll-settings/{id}',                       [\App\Http\Controllers\StudioHR\PayrollSettingsController::class, 'show'])->middleware('permission:studio-hr.payroll.view,studio-hr.payroll.manage')->name('studio-hr.payroll-settings.show');
        Route::get('/payroll-settings/{id}/edit',                  [\App\Http\Controllers\StudioHR\PayrollSettingsController::class, 'edit'])->middleware('permission:studio-hr.payroll.edit,studio-hr.payroll.update,studio-hr.payroll.manage')->name('studio-hr.payroll-settings.edit');
        Route::put('/payroll-settings/{id}',                       [\App\Http\Controllers\StudioHR\PayrollSettingsController::class, 'update'])->middleware('permission:studio-hr.payroll.edit,studio-hr.payroll.update,studio-hr.payroll.manage')->name('studio-hr.payroll-settings.update');
        Route::put('/payroll-settings/{id}/status',                [\App\Http\Controllers\StudioHR\PayrollSettingsController::class, 'updateStatus'])->middleware('permission:studio-hr.payroll.edit,studio-hr.payroll.update,studio-hr.payroll.manage')->name('studio-hr.payroll-settings.status');
        Route::delete('/payroll-settings/{id}',                    [\App\Http\Controllers\StudioHR\PayrollSettingsController::class, 'destroy'])->middleware('permission:studio-hr.payroll.delete,studio-hr.payroll.manage')->name('studio-hr.payroll-settings.destroy');
        Route::post('/payroll-settings/bulk-store',                [\App\Http\Controllers\StudioHR\PayrollSettingsController::class, 'bulkStore'])->middleware('permission:studio-hr.payroll.create,studio-hr.payroll.manage')->name('studio-hr.payroll-settings.bulk-store');

        // Generate Payroll
        Route::get('/generate-payroll',                            [\App\Http\Controllers\StudioHR\GeneratePayrollController::class, 'index'])->middleware('permission:studio-hr.payroll.view,studio-hr.payroll.manage,studio-hr.generate-payroll.manage')->name('studio-hr.generate-payroll.index');
        Route::get('/generate-payroll/employees',                  [\App\Http\Controllers\StudioHR\GeneratePayrollController::class, 'getEmployees'])->middleware('permission:studio-hr.payroll.view,studio-hr.payroll.manage,studio-hr.generate-payroll.manage')->name('studio-hr.generate-payroll.employees');
        Route::post('/generate-payroll',                           [\App\Http\Controllers\StudioHR\GeneratePayrollController::class, 'store'])->middleware('permission:studio-hr.payroll.create,studio-hr.payroll.manage,studio-hr.generate-payroll.manage')->name('studio-hr.generate-payroll.store');
        Route::get('/generate-payroll/{id}',                       [\App\Http\Controllers\StudioHR\GeneratePayrollController::class, 'show'])->middleware('permission:studio-hr.payroll.view,studio-hr.payroll.manage,studio-hr.generate-payroll.manage')->name('studio-hr.generate-payroll.show');

        // Attendance
        Route::get('/view/attendance',                              [\App\Http\Controllers\StudioHR\EmployeeAttendanceController::class, 'index'])->middleware('permission:studio-hr.attendance.view')->name('studio-hr.attendance.index');
        Route::get('/attendance/current-time',                      [\App\Http\Controllers\StudioHR\EmployeeAttendanceController::class, 'getCurrentTime'])->middleware('permission:studio-hr.attendance.view')->name('studio-hr.attendance.current-time');
        Route::get('/attendance/schedule',                          [\App\Http\Controllers\StudioHR\EmployeeAttendanceController::class, 'getEmployeeSchedule'])->middleware('permission:studio-hr.attendance.view')->name('studio-hr.attendance.schedule');
        Route::post('/attendance/check-in',                         [\App\Http\Controllers\StudioHR\EmployeeAttendanceController::class, 'checkIn'])->middleware('permission:studio-hr.attendance.view')->name('studio-hr.attendance.check-in');
        Route::post('/attendance/check-out',                        [\App\Http\Controllers\StudioHR\EmployeeAttendanceController::class, 'checkOut'])->middleware('permission:studio-hr.attendance.view')->name('studio-hr.attendance.check-out');
        Route::get('/attendance/today',                             [\App\Http\Controllers\StudioHR\EmployeeAttendanceController::class, 'getTodaysAttendance'])->middleware('permission:studio-hr.attendance.view')->name('studio-hr.attendance.today');
        Route::get('/attendance/history',                           [\App\Http\Controllers\StudioHR\EmployeeAttendanceController::class, 'getAttendanceHistory'])->middleware('permission:studio-hr.attendance.view')->name('studio-hr.attendance.history');
        Route::get('/attendance/stats',                             [\App\Http\Controllers\StudioHR\EmployeeAttendanceController::class, 'getAttendanceStats'])->middleware('permission:studio-hr.attendance.view')->name('studio-hr.attendance.stats');
        Route::get('/attendance/{id}/details',                      [\App\Http\Controllers\StudioHR\EmployeeAttendanceController::class, 'getAttendanceDetails'])->middleware('permission:studio-hr.attendance.view')->name('studio-hr.attendance.details');

        // Procurement
        Route::get('/procurement/create',                           [\App\Http\Controllers\StudioHR\ProcurementRequestController::class, 'create'])->middleware('permission:studio-hr.procurement.manage')->name('studio-hr.procurement.create');
        Route::get('/procurement',                                  [\App\Http\Controllers\StudioHR\ProcurementRequestController::class, 'index'])->middleware('permission:studio-hr.procurement.manage')->name('studio-hr.procurement.index');
        Route::post('/procurement',                                 [\App\Http\Controllers\StudioHR\ProcurementRequestController::class, 'store'])->middleware('permission:studio-hr.procurement.manage')->name('studio-hr.procurement.store');
        Route::get('/procurement/{id}/edit',                        [\App\Http\Controllers\StudioHR\ProcurementRequestController::class, 'edit'])->middleware('permission:studio-hr.procurement.manage')->name('studio-hr.procurement.edit');
        Route::get('/procurement/{id}',                             [\App\Http\Controllers\StudioHR\ProcurementRequestController::class, 'show'])->middleware('permission:studio-hr.procurement.manage')->name('studio-hr.procurement.show');
        Route::put('/procurement/{id}',                             [\App\Http\Controllers\StudioHR\ProcurementRequestController::class, 'update'])->middleware('permission:studio-hr.procurement.manage')->name('studio-hr.procurement.update');
        Route::post('/procurement/{id}/cancel',                     [\App\Http\Controllers\StudioHR\ProcurementRequestController::class, 'cancel'])->middleware('permission:studio-hr.procurement.manage')->name('studio-hr.procurement.cancel');
        Route::post('/procurement/{id}/confirm-receipt',            [\App\Http\Controllers\StudioHR\ProcurementRequestController::class, 'confirmReceipt'])->middleware('permission:studio-hr.procurement.manage')->name('studio-hr.procurement.confirm-receipt');
    });

    // Studio Finance Routes ===============================================================================================================================================
    Route::prefix('studio-finance')->middleware([StudioFinanceMiddleware::class])->group(function () {

        // Redirect to Dashboard
        Route::get('/',                                             function () {
            return redirect()->route('studio-finance.dashboard');
        })->name('studio-finance.index');

        // Profile
        Route::get('/profile',                                      [\App\Http\Controllers\GeneralProfileController::class, 'studioFinance'])->name('studio-finance.profile');

        // Dashboard
        Route::get('/dashboard',                                    [\App\Http\Controllers\Finance\DashboardController::class, 'index'])->middleware('permission:studio-finance.dashboard.view')->name('studio-finance.dashboard');
        Route::get('/dashboard/filter',                             [\App\Http\Controllers\Finance\DashboardController::class, 'filter'])->middleware('permission:studio-finance.dashboard.view')->name('studio-finance.dashboard.filter');
        Route::get('/dashboard/export',                             [\App\Http\Controllers\Finance\DashboardController::class, 'export'])->middleware('permission:studio-finance.dashboard.view')->name('studio-finance.dashboard.export');

        // Leave Requests
        Route::get('/leave-requests/create',                        [\App\Http\Controllers\Finance\LeaveRequestController::class, 'create'])->middleware('permission:studio-finance.leave-requests.manage')->name('studio-finance.leave-requests.create');
        Route::get('/leave-requests',                               [\App\Http\Controllers\Finance\LeaveRequestController::class, 'index'])->middleware('permission:studio-finance.leave-requests.manage')->name('studio-finance.leave-requests.index');
        Route::post('/leave-requests',                              [\App\Http\Controllers\Finance\LeaveRequestController::class, 'store'])->middleware('permission:studio-finance.leave-requests.manage')->name('studio-finance.leave-requests.store');
        Route::get('/leave-requests/{id}',                          [\App\Http\Controllers\Finance\LeaveRequestController::class, 'show'])->middleware('permission:studio-finance.leave-requests.manage')->name('studio-finance.leave-requests.show');
        Route::put('/leave-requests/{id}',                          [\App\Http\Controllers\Finance\LeaveRequestController::class, 'update'])->middleware('permission:studio-finance.leave-requests.manage')->name('studio-finance.leave-requests.update');
        Route::post('/leave-requests/{id}/cancel',                  [\App\Http\Controllers\Finance\LeaveRequestController::class, 'cancel'])->middleware('permission:studio-finance.leave-requests.manage')->name('studio-finance.leave-requests.cancel');
        Route::delete('/leave-requests/{id}',                       [\App\Http\Controllers\Finance\LeaveRequestController::class, 'destroy'])->middleware('permission:studio-finance.leave-requests.manage')->name('studio-finance.leave-requests.destroy');
        Route::get('/overtime-requests/create',                     [\App\Http\Controllers\Finance\OvertimeRequestController::class, 'create'])->middleware('permission:studio-finance.overtime-requests.manage')->name('studio-finance.overtime-requests.create');
        Route::get('/overtime-requests',                            [\App\Http\Controllers\Finance\OvertimeRequestController::class, 'index'])->middleware('permission:studio-finance.overtime-requests.manage')->name('studio-finance.overtime-requests.index');
        Route::post('/overtime-requests',                           [\App\Http\Controllers\Finance\OvertimeRequestController::class, 'store'])->middleware('permission:studio-finance.overtime-requests.manage')->name('studio-finance.overtime-requests.store');
        Route::get('/overtime-requests/{id}',                       [\App\Http\Controllers\Finance\OvertimeRequestController::class, 'show'])->middleware('permission:studio-finance.overtime-requests.manage')->name('studio-finance.overtime-requests.show');
        Route::put('/overtime-requests/{id}',                       [\App\Http\Controllers\Finance\OvertimeRequestController::class, 'update'])->middleware('permission:studio-finance.overtime-requests.manage')->name('studio-finance.overtime-requests.update');
        Route::post('/overtime-requests/{id}/cancel',               [\App\Http\Controllers\Finance\OvertimeRequestController::class, 'cancel'])->middleware('permission:studio-finance.overtime-requests.manage')->name('studio-finance.overtime-requests.cancel');
        Route::delete('/overtime-requests/{id}',                    [\App\Http\Controllers\Finance\OvertimeRequestController::class, 'destroy'])->middleware('permission:studio-finance.overtime-requests.manage')->name('studio-finance.overtime-requests.destroy');

        // Attendance
        Route::get('/view/attendance',                              [\App\Http\Controllers\Finance\AttendanceController::class, 'index'])->middleware('permission:studio-finance.attendance.view')->name('studio-finance.attendance.index');
        Route::get('/attendance/current-time',                      [\App\Http\Controllers\Finance\AttendanceController::class, 'getCurrentTime'])->middleware('permission:studio-finance.attendance.view')->name('studio-finance.attendance.current-time');
        Route::get('/attendance/schedule',                          [\App\Http\Controllers\Finance\AttendanceController::class, 'getFinanceSchedule'])->middleware('permission:studio-finance.attendance.view')->name('studio-finance.attendance.schedule');
        Route::post('/attendance/check-in',                         [\App\Http\Controllers\Finance\AttendanceController::class, 'checkIn'])->middleware('permission:studio-finance.attendance.view')->name('studio-finance.attendance.check-in');
        Route::post('/attendance/check-out',                        [\App\Http\Controllers\Finance\AttendanceController::class, 'checkOut'])->middleware('permission:studio-finance.attendance.view')->name('studio-finance.attendance.check-out');
        Route::get('/attendance/{id}/details',                      [\App\Http\Controllers\Finance\AttendanceController::class, 'getAttendanceDetails'])->middleware('permission:studio-finance.attendance.view')->name('studio-finance.attendance.details');

        // Payroll Approvals
        Route::get('/payroll-approvals',                            [\App\Http\Controllers\Finance\PayrollApprovalController::class, 'index'])->middleware('permission:studio-finance.payroll.view,studio-finance.payroll.approve,studio-finance.payroll.reject,studio-finance.payroll.manage')->name('studio-finance.payroll-approvals.index');
        Route::get('/payroll-approvals/{id}',                       [\App\Http\Controllers\Finance\PayrollApprovalController::class, 'show'])->middleware('permission:studio-finance.payroll.view,studio-finance.payroll.approve,studio-finance.payroll.reject,studio-finance.payroll.manage')->name('studio-finance.payroll-approvals.show');
        Route::post('/payroll-approvals/{id}/{action}',             [\App\Http\Controllers\Finance\PayrollApprovalController::class, 'update'])->middleware('permission:studio-finance.payroll.approve,studio-finance.payroll.reject,studio-finance.payroll.manage')->name('studio-finance.payroll-approvals.update');

        // Procurement
        Route::get('/procurement',                                  [\App\Http\Controllers\Finance\ProcurementController::class, 'index'])->middleware('permission:studio-finance.procurement.view,studio-finance.procurement.review,studio-finance.procurement.order,studio-finance.procurement.payment')->name('studio-finance.procurement.index');
        Route::get('/procurement/{id}',                             [\App\Http\Controllers\Finance\ProcurementController::class, 'show'])->middleware('permission:studio-finance.procurement.view,studio-finance.procurement.review,studio-finance.procurement.order,studio-finance.procurement.payment')->name('studio-finance.procurement.show');
        Route::post('/procurement/{id}/review',                     [\App\Http\Controllers\Finance\ProcurementController::class, 'review'])->middleware('permission:studio-finance.procurement.review')->name('studio-finance.procurement.review');
        Route::post('/procurement/{id}/purchase-order',             [\App\Http\Controllers\Finance\ProcurementController::class, 'storePurchaseOrder'])->middleware('permission:studio-finance.procurement.order')->name('studio-finance.procurement.purchase-order');
        Route::post('/procurement/{id}/delivery',                   [\App\Http\Controllers\Finance\ProcurementController::class, 'recordDelivery'])->middleware('permission:studio-finance.procurement.order')->name('studio-finance.procurement.delivery');
        Route::post('/procurement/{id}/process-return',             [\App\Http\Controllers\Finance\ProcurementController::class, 'processReturn'])->middleware('permission:studio-finance.procurement.order')->name('studio-finance.procurement.process-return');
        Route::post('/procurement/{id}/replacement-delivery',       [\App\Http\Controllers\Finance\ProcurementController::class, 'recordReplacementDelivery'])->middleware('permission:studio-finance.procurement.order')->name('studio-finance.procurement.replacement-delivery');
        Route::post('/procurement/{id}/payment',                    [\App\Http\Controllers\Finance\ProcurementController::class, 'recordPayment'])->middleware('permission:studio-finance.procurement.payment')->name('studio-finance.procurement.payment');
        Route::post('/procurement/{id}/complete',                   [\App\Http\Controllers\Finance\ProcurementController::class, 'complete'])->middleware('permission:studio-finance.procurement.payment')->name('studio-finance.procurement.complete');
    });

    // Freelancer Routes ===================================================================================================================================================
    Route::prefix('freelancer')->middleware([FreelancerMiddleware::class])->group(function () {

        // Profile
        Route::get('/profile',                              [\App\Http\Controllers\GeneralProfileController::class, 'freelancer'])->name('freelancer.profile');

        // Dashboard
        Route::get('/dashboard',                            [\App\Http\Controllers\Freelancer\DashboardController::class, 'index'])->name('freelancer.dashboard');

        // Profile
        Route::get('view/profile',                          [\App\Http\Controllers\Freelancer\ProfileController::class, 'index'])->name('freelancer.profile.index');
        Route::get('setup/profile',                         [\App\Http\Controllers\Freelancer\ProfileController::class, 'setup'])->name('freelancer.profile.setup');
        Route::post('profile/store',                        [\App\Http\Controllers\Freelancer\ProfileController::class, 'store'])->name('freelancer.profile.store');
        Route::post('profile/get-barangays',                [\App\Http\Controllers\Freelancer\ProfileController::class, 'getBarangays'])->name('freelancer.profile.get-barangays');

        // Services
        Route::get('/view/services',                        [\App\Http\Controllers\Freelancer\ServicesController::class, 'index'])->name('freelancer.services.index');
        Route::get('/create/services',                      [\App\Http\Controllers\Freelancer\ServicesController::class, 'create'])->name('freelancer.services.create');
        Route::post('/services',                            [\App\Http\Controllers\Freelancer\ServicesController::class, 'store'])->name('freelancer.services.store');
        Route::get('/services/{id}',                        [\App\Http\Controllers\Freelancer\ServicesController::class, 'show'])->name('freelancer.services.show');
        Route::get('/services/{id}/edit',                   [\App\Http\Controllers\Freelancer\ServicesController::class, 'edit'])->name('freelancer.services.edit');
        Route::put('/services/{id}',                        [\App\Http\Controllers\Freelancer\ServicesController::class, 'update'])->name('freelancer.services.update');
        Route::delete('/services/{id}',                     [\App\Http\Controllers\Freelancer\ServicesController::class, 'destroy'])->name('freelancer.services.destroy');
        Route::get('/services/categories/get',              [\App\Http\Controllers\Freelancer\ServicesController::class, 'getCategories'])->name('freelancer.services.categories');

        // Packages
        Route::get('/view/packages',                        [\App\Http\Controllers\Freelancer\PackagesController::class, 'index'])->name('freelancer.packages.index');
        Route::get('/create/packages',                      [\App\Http\Controllers\Freelancer\PackagesController::class, 'create'])->name('freelancer.packages.create');
        Route::post('/packages',                            [\App\Http\Controllers\Freelancer\PackagesController::class, 'store'])->name('freelancer.packages.store');
        Route::get('/packages/lists',                       [\App\Http\Controllers\Freelancer\PackagesController::class, 'list'])->name('freelancer.packages.list');
        Route::get('/packages/data',                        [\App\Http\Controllers\Freelancer\PackagesController::class, 'getPackages'])->name('freelancer.packages.data');
        Route::get('/packages/categories',                  [\App\Http\Controllers\Freelancer\PackagesController::class, 'getCategories'])->name('freelancer.packages.categories');
        Route::delete('/packages/{id}',                     [\App\Http\Controllers\Freelancer\PackagesController::class, 'destroy'])->name('freelancer.packages.destroy');
        Route::get('/packages/{id}',                        [\App\Http\Controllers\Freelancer\PackagesController::class, 'show'])->name('freelancer.packages.show');
        
        // Member Invitations
        Route::get('view/member-invitation',                [\App\Http\Controllers\Freelancer\MemberInvitationController::class, 'index'])->name('freelancer.invitation.index');
        Route::get('invitation/{id}/details',               [\App\Http\Controllers\Freelancer\MemberInvitationController::class, 'getInvitationDetails'])->name('freelancer.invitation.details');
        Route::post('invitation/{id}/accept',               [\App\Http\Controllers\Freelancer\MemberInvitationController::class, 'accept'])->name('freelancer.invitation.accept');
        Route::post('invitation/{id}/reject',               [\App\Http\Controllers\Freelancer\MemberInvitationController::class, 'reject'])->name('freelancer.invitation.reject');

        // Manage Bookings                          
        Route::get('/view/bookings',                        [\App\Http\Controllers\Freelancer\BookingController::class, 'index'])->name('freelancer.booking.index');
        Route::get('/booking/history',                      [\App\Http\Controllers\Freelancer\BookingController::class, 'history'])->name('freelancer.booking.history');
        Route::get('/bookings/{id}/details',                [\App\Http\Controllers\Freelancer\BookingController::class, 'getBookingDetails'])->name('freelancer.booking.details');
        Route::put('/bookings/{id}/status',                 [\App\Http\Controllers\Freelancer\BookingController::class, 'updateStatus'])->name('freelancer.booking.update.status');
        Route::put('/bookings/{id}/payment-status',         [\App\Http\Controllers\Freelancer\BookingController::class, 'updatePaymentStatus'])->name('freelancer.booking.update.payment.status');

        // Manage Online Gallery
        Route::get('/view/online-gallery',                  [\App\Http\Controllers\Freelancer\OnlineGalleryController::class, 'index'])->name('freelancer.online-gallery.index');
        Route::get('/online-gallery/{bookingId}/details',   [\App\Http\Controllers\Freelancer\OnlineGalleryController::class, 'getGalleryDetails'])->name('freelancer.online-gallery.details');
        Route::post('/online-gallery/{bookingId}/upload',   [\App\Http\Controllers\Freelancer\OnlineGalleryController::class, 'uploadImages'])->name('freelancer.online-gallery.upload');
        Route::delete('/online-gallery/{galleryId}/image',  [\App\Http\Controllers\Freelancer\OnlineGalleryController::class, 'deleteImage'])->name('freelancer.online-gallery.delete-image');
        Route::delete('/online-gallery/{galleryId}',        [\App\Http\Controllers\Freelancer\OnlineGalleryController::class, 'deleteGallery'])->name('freelancer.online-gallery.delete');
        Route::put('/online-gallery/{galleryId}',           [\App\Http\Controllers\Freelancer\OnlineGalleryController::class, 'updateGallery'])->name('freelancer.online-gallery.update');

    });

    // Studio-Photographer  =======================================================================================================================================================
    Route::prefix('studio-photographer')->middleware([StudioPhotographerMiddleware::class])->group(function () {

        // Profile
        Route::get('/profile',                                  [\App\Http\Controllers\GeneralProfileController::class, 'studioPhotographer'])->name('studio-photographer.profile');

        // Dashboard
        Route::get('/dashboard',                        [\App\Http\Controllers\StudioPhotographer\DashboardController::class, 'index'])->middleware('permission:studio-photographer.dashboard.view')->name('studio-photographer.dashboard');
        Route::get('/dashboard/filter',                 [\App\Http\Controllers\StudioPhotographer\DashboardController::class, 'filter'])->middleware('permission:studio-photographer.dashboard.view')->name('studio-photographer.dashboard.filter');
        Route::get('/dashboard/export',                 [\App\Http\Controllers\StudioPhotographer\DashboardController::class, 'export'])->middleware('permission:studio-photographer.dashboard.view')->name('studio-photographer.dashboard.export');

        // Leave Requests
        Route::get('/leave-requests/create',            [\App\Http\Controllers\StudioPhotographer\LeaveRequestController::class, 'create'])->middleware('permission:studio-photographer.leave-requests.manage')->name('studio-photographer.leave-requests.create');
        Route::get('/leave-requests',                   [\App\Http\Controllers\StudioPhotographer\LeaveRequestController::class, 'index'])->middleware('permission:studio-photographer.leave-requests.manage')->name('studio-photographer.leave-requests.index');
        Route::post('/leave-requests',                  [\App\Http\Controllers\StudioPhotographer\LeaveRequestController::class, 'store'])->middleware('permission:studio-photographer.leave-requests.manage')->name('studio-photographer.leave-requests.store');
        Route::get('/leave-requests/{id}',              [\App\Http\Controllers\StudioPhotographer\LeaveRequestController::class, 'show'])->middleware('permission:studio-photographer.leave-requests.manage')->name('studio-photographer.leave-requests.show');
        Route::put('/leave-requests/{id}',              [\App\Http\Controllers\StudioPhotographer\LeaveRequestController::class, 'update'])->middleware('permission:studio-photographer.leave-requests.manage')->name('studio-photographer.leave-requests.update');
        Route::post('/leave-requests/{id}/cancel',      [\App\Http\Controllers\StudioPhotographer\LeaveRequestController::class, 'cancel'])->middleware('permission:studio-photographer.leave-requests.manage')->name('studio-photographer.leave-requests.cancel');
        Route::delete('/leave-requests/{id}',           [\App\Http\Controllers\StudioPhotographer\LeaveRequestController::class, 'destroy'])->middleware('permission:studio-photographer.leave-requests.manage')->name('studio-photographer.leave-requests.destroy');
        Route::get('/overtime-requests/create',         [\App\Http\Controllers\StudioPhotographer\OvertimeRequestController::class, 'create'])->middleware('permission:studio-photographer.overtime-requests.manage')->name('studio-photographer.overtime-requests.create');
        Route::get('/overtime-requests',                [\App\Http\Controllers\StudioPhotographer\OvertimeRequestController::class, 'index'])->middleware('permission:studio-photographer.overtime-requests.manage')->name('studio-photographer.overtime-requests.index');
        Route::post('/overtime-requests',               [\App\Http\Controllers\StudioPhotographer\OvertimeRequestController::class, 'store'])->middleware('permission:studio-photographer.overtime-requests.manage')->name('studio-photographer.overtime-requests.store');
        Route::get('/overtime-requests/{id}',           [\App\Http\Controllers\StudioPhotographer\OvertimeRequestController::class, 'show'])->middleware('permission:studio-photographer.overtime-requests.manage')->name('studio-photographer.overtime-requests.show');
        Route::put('/overtime-requests/{id}',           [\App\Http\Controllers\StudioPhotographer\OvertimeRequestController::class, 'update'])->middleware('permission:studio-photographer.overtime-requests.manage')->name('studio-photographer.overtime-requests.update');
        Route::post('/overtime-requests/{id}/cancel',   [\App\Http\Controllers\StudioPhotographer\OvertimeRequestController::class, 'cancel'])->middleware('permission:studio-photographer.overtime-requests.manage')->name('studio-photographer.overtime-requests.cancel');
        Route::delete('/overtime-requests/{id}',        [\App\Http\Controllers\StudioPhotographer\OvertimeRequestController::class, 'destroy'])->middleware('permission:studio-photographer.overtime-requests.manage')->name('studio-photographer.overtime-requests.destroy');

        // Attendance
        Route::get('/view/attendance',                  [\App\Http\Controllers\StudioPhotographer\PhotographerAttendanceController::class, 'index'])->middleware('permission:studio-photographer.attendance.view')->name('studio-photographer.attendance.index');
        Route::get('/attendance/current-time',          [\App\Http\Controllers\StudioPhotographer\PhotographerAttendanceController::class, 'getCurrentTime'])->middleware('permission:studio-photographer.attendance.view')->name('studio-photographer.attendance.current-time');
        Route::get('/attendance/schedule',              [\App\Http\Controllers\StudioPhotographer\PhotographerAttendanceController::class, 'getPhotographerSchedule'])->middleware('permission:studio-photographer.attendance.view')->name('studio-photographer.attendance.schedule');
        Route::post('/attendance/check-in',             [\App\Http\Controllers\StudioPhotographer\PhotographerAttendanceController::class, 'checkIn'])->middleware('permission:studio-photographer.attendance.view')->name('studio-photographer.attendance.check-in');
        Route::post('/attendance/check-out',            [\App\Http\Controllers\StudioPhotographer\PhotographerAttendanceController::class, 'checkOut'])->middleware('permission:studio-photographer.attendance.view')->name('studio-photographer.attendance.check-out');
        Route::get('/attendance/{id}/details',          [\App\Http\Controllers\StudioPhotographer\PhotographerAttendanceController::class, 'getAttendanceDetails'])->middleware('permission:studio-photographer.attendance.view')->name('studio-photographer.attendance.details');

        // Assigned Studio
        Route::get('/view/studio',                      [\App\Http\Controllers\StudioPhotographer\AssignedStudioController::class, 'index'])->middleware('permission:studio-photographer.studio.view')->name('studio-photographer.studio.index');
        Route::get('/studio/{id}/details',              [\App\Http\Controllers\StudioPhotographer\AssignedStudioController::class, 'getStudioDetails'])->middleware('permission:studio-photographer.studio.view')->name('studio-photographer.studio.details');

        // Assigned Studio
        Route::get('/assigned-bookings',                [\App\Http\Controllers\StudioPhotographer\AssignedBookingController::class, 'index'])->middleware('permission:studio-photographer.bookings.view')->name('assigned.bookings');
        Route::get('/assignment/{id}/details',          [\App\Http\Controllers\StudioPhotographer\AssignedBookingController::class, 'getBookingDetails'])->middleware('permission:studio-photographer.bookings.view')->name('assignment.details');
        Route::post('/assignment/{id}/update-status',   [\App\Http\Controllers\StudioPhotographer\AssignedBookingController::class, 'updateAssignmentStatus'])->middleware('permission:studio-photographer.assignment.update_status')->name('assignment.update-status');

        // Manage Online Gallery
        Route::get('/view/online-gallery',                  [\App\Http\Controllers\StudioPhotographer\OnlineGalleryController::class, 'index'])->middleware('permission:studio-photographer.online_gallery.view,studio-photographer.online_gallery.create,studio-photographer.online_gallery.update,studio-photographer.online_gallery.delete')->name('studio-photographer.online-gallery.index');
        Route::get('/online-gallery/{bookingId}/details',   [\App\Http\Controllers\StudioPhotographer\OnlineGalleryController::class, 'getGalleryDetails'])->middleware('permission:studio-photographer.online_gallery.view,studio-photographer.online_gallery.update,studio-photographer.online_gallery.delete')->name('studio-photographer.online-gallery.details');
        Route::post('/online-gallery/{bookingId}/upload',   [\App\Http\Controllers\StudioPhotographer\OnlineGalleryController::class, 'uploadImages'])->middleware('permission:studio-photographer.online_gallery.create')->name('studio-photographer.online-gallery.upload');
        Route::delete('/online-gallery/{galleryId}/image',  [\App\Http\Controllers\StudioPhotographer\OnlineGalleryController::class, 'deleteImage'])->middleware('permission:studio-photographer.online_gallery.delete')->name('studio-photographer.online-gallery.delete-image');
        Route::delete('/online-gallery/{galleryId}',        [\App\Http\Controllers\StudioPhotographer\OnlineGalleryController::class, 'deleteGallery'])->middleware('permission:studio-photographer.online_gallery.delete')->name('studio-photographer.online-gallery.delete');
        Route::put('/online-gallery/{galleryId}',           [\App\Http\Controllers\StudioPhotographer\OnlineGalleryController::class, 'updateGallery'])->middleware('permission:studio-photographer.online_gallery.update')->name('studio-photographer.online-gallery.update');

        // Procurement
        Route::get('/procurement/create',                   [\App\Http\Controllers\StudioPhotographer\ProcurementRequestController::class, 'create'])->middleware('permission:studio-photographer.procurement.manage')->name('studio-photographer.procurement.create');
        Route::get('/procurement',                          [\App\Http\Controllers\StudioPhotographer\ProcurementRequestController::class, 'index'])->middleware('permission:studio-photographer.procurement.manage')->name('studio-photographer.procurement.index');
        Route::post('/procurement',                         [\App\Http\Controllers\StudioPhotographer\ProcurementRequestController::class, 'store'])->middleware('permission:studio-photographer.procurement.manage')->name('studio-photographer.procurement.store');
        Route::get('/procurement/{id}/edit',                [\App\Http\Controllers\StudioPhotographer\ProcurementRequestController::class, 'edit'])->middleware('permission:studio-photographer.procurement.manage')->name('studio-photographer.procurement.edit');
        Route::get('/procurement/{id}',                     [\App\Http\Controllers\StudioPhotographer\ProcurementRequestController::class, 'show'])->middleware('permission:studio-photographer.procurement.manage')->name('studio-photographer.procurement.show');
        Route::put('/procurement/{id}',                     [\App\Http\Controllers\StudioPhotographer\ProcurementRequestController::class, 'update'])->middleware('permission:studio-photographer.procurement.manage')->name('studio-photographer.procurement.update');
        Route::post('/procurement/{id}/cancel',             [\App\Http\Controllers\StudioPhotographer\ProcurementRequestController::class, 'cancel'])->middleware('permission:studio-photographer.procurement.manage')->name('studio-photographer.procurement.cancel');
        Route::post('/procurement/{id}/confirm-receipt',    [\App\Http\Controllers\StudioPhotographer\ProcurementRequestController::class, 'confirmReceipt'])->middleware('permission:studio-photographer.procurement.manage')->name('studio-photographer.procurement.confirm-receipt');

    });

    // Client Routes =======================================================================================================================================================
    Route::prefix('client')->middleware([ClientMiddleware::class])->group(function () {

        // Profile
        Route::get('/profile',                                  [\App\Http\Controllers\GeneralProfileController::class, 'client'])->name('client.profile');

        // Dashboard
        Route::get('/dashboard',                                [\App\Http\Controllers\Client\DashboardController::class, 'index'])->name('client.dashboard');
        Route::post('/dashboard/filter',                        [\App\Http\Controllers\Client\DashboardController::class, 'filter'])->name('client.dashboard.filter');

        // Booking Details
        Route::get('/booking-details/{type}/{id}',              [\App\Http\Controllers\Client\BookingDetailsController::class, 'index'])->name('client.booking-details');

        // Client Bookings
        Route::get('/view/my-bookings',                         [\App\Http\Controllers\Client\MyBookingsController::class, 'index'])->name('client.my-bookings.index');
        Route::get('/view/bookings-history',                    [\App\Http\Controllers\Client\MyBookingsController::class, 'history'])->name('client.my-bookings.history');
        Route::get('/bookings/{id}/details',                    [\App\Http\Controllers\Client\MyBookingsController::class, 'getBookingDetails'])->name('client.booking.details');
        Route::post('/bookings/{id}/cancel',                    [\App\Http\Controllers\Client\MyBookingsController::class, 'cancelBooking'])->name('client.booking.cancel');
        Route::get('/bookings/{id}/payment-details',            [\App\Http\Controllers\Client\MyBookingsController::class, 'getPaymentDetails'])->name('client.booking.payment.details');
        Route::post('/bookings/{id}/balance-payment',           [\App\Http\Controllers\Client\MyBookingsController::class, 'initializeBalancePayment'])->name('client.booking.balance.payment');
        Route::post('/confirm-photographer/{assignmentId}',     [\App\Http\Controllers\Client\MyBookingsController::class, 'confirmPhotographerOnSite'])->name('client.confirm-photographer');
        Route::get('/pending-confirmations',                    [\App\Http\Controllers\Client\MyBookingsController::class, 'getPendingConfirmations'])->name('client.pending-confirmations');

        // Online Gallery
        Route::get('/view/online-gallery',              [\App\Http\Controllers\Client\OnlineGalleryController::class, 'index'])->name('client.online-gallery.index');
        Route::get('/online-gallery/{id}/{type}',       [\App\Http\Controllers\Client\OnlineGalleryController::class, 'getGalleryDetails'])->name('client.online-gallery.details');

        // Studio Reviews
        Route::get('/reviews/create/{bookingId}',               [\App\Http\Controllers\Client\StudioRatingController::class, 'create'])->name('client.reviews.create');
        Route::post('/reviews/preset-reviews',                  [\App\Http\Controllers\Client\StudioRatingController::class, 'getPresetReviews'])->name('client.reviews.preset');
        Route::post('/reviews',                                 [\App\Http\Controllers\Client\StudioRatingController::class, 'store'])->name('client.reviews.store');
        Route::get('/reviews/check/{bookingId}',                [\App\Http\Controllers\Client\StudioRatingController::class, 'checkCanReview'])->name('client.reviews.check');
        Route::get('/studio/{studioId}/reviews',                [\App\Http\Controllers\Client\StudioRatingController::class, 'getStudioReviews'])->name('client.studio-reviews');

        // Freelancer Reviews
        Route::get('/freelancer-reviews/create/{bookingId}',    [\App\Http\Controllers\Client\FreelancerRatingController::class, 'create'])->name('client.freelancer-reviews.create');
        Route::post('/freelancer-reviews/preset-reviews',       [\App\Http\Controllers\Client\FreelancerRatingController::class, 'getPresetReviews'])->name('client.freelancer-reviews.preset');
        Route::post('/freelancer-reviews',                      [\App\Http\Controllers\Client\FreelancerRatingController::class, 'store'])->name('client.freelancer-reviews.store');
        Route::get('/freelancer-reviews/check/{bookingId}',     [\App\Http\Controllers\Client\FreelancerRatingController::class, 'checkCanReview'])->name('client.freelancer-reviews.check');
        Route::get('/freelancer/{freelancerId}/reviews',        [\App\Http\Controllers\Client\FreelancerRatingController::class, 'getFreelancerReviews'])->name('client.freelancer-reviews'); 

        // Booking Process
        Route::get('/booking-form/{type}/{id}',                 [\App\Http\Controllers\Client\BookingController::class, 'create'])->name('client.booking-forms');
        Route::post('/bookings',                                [\App\Http\Controllers\Client\BookingController::class, 'store'])->name('client.bookings.store');
        Route::post('/bookings/packages',                       [\App\Http\Controllers\Client\BookingController::class, 'getPackages'])->name('client.bookings.packages');
        Route::post('/bookings/check-availability',             [\App\Http\Controllers\Client\BookingController::class, 'checkAvailability'])->name('client.bookings.check-availability');
        Route::post('/bookings/calendar-availability',          [\App\Http\Controllers\Client\BookingController::class, 'getCalendarAvailability'])->name('client.bookings.calendar-availability');
        Route::post('/bookings/summary',                        [\App\Http\Controllers\Client\BookingController::class, 'getSummary'])->name('client.bookings.summary');
        Route::post('/locations/barangays',                     [\App\Http\Controllers\Client\BookingController::class, 'getBarangays'])->name('client.locations.barangays');
        
        // Booking Process / Payment
        Route::post('/payments/initialize',                     [\App\Http\Controllers\Client\BookingController::class, 'initializePayment'])->name('client.payments.initialize');
        Route::get('/payment/verify/{reference}',               [\App\Http\Controllers\Client\BookingController::class, 'verifyPayment'])->name('client.payment.verify');
        Route::get('/payment/success/{reference}',              [\App\Http\Controllers\Client\BookingController::class, 'paymentSuccess'])->name('client.payment.success');
        Route::get('/payment/failed/{reference}',               [\App\Http\Controllers\Client\BookingController::class, 'paymentFailed'])->name('client.payment.failed');

        // Budget
        Route::get('/budget',                                   [\App\Http\Controllers\Client\BudgetController::class, 'index'])->name('client.budget.index');
        Route::get('/budget/data',                              [\App\Http\Controllers\Client\BudgetController::class, 'getBudgets'])->name('client.budget.data');
        Route::post('/budget',                                  [\App\Http\Controllers\Client\BudgetController::class, 'store'])->name('client.budget.store');
        Route::get('/budget/{id}',                              [\App\Http\Controllers\Client\BudgetController::class, 'show'])->name('client.budget.show');
        Route::put('/budget/{id}',                              [\App\Http\Controllers\Client\BudgetController::class, 'update'])->name('client.budget.update');
        Route::delete('/budget/{id}',                           [\App\Http\Controllers\Client\BudgetController::class, 'destroy'])->name('client.budget.destroy');
        Route::post('/budget/{id}/toggle-status',               [\App\Http\Controllers\Client\BudgetController::class, 'toggleStatus'])->name('client.budget.toggle');
        Route::get('/budget/categories/list',                   [\App\Http\Controllers\Client\BudgetController::class, 'getCategories'])->name('client.budget.categories');
        Route::get('/budget/statistics/data',                   [\App\Http\Controllers\Client\BudgetController::class, 'getStatistics'])->name('client.budget.statistics');

        // Chatbot
        Route::prefix('chatbot')->name('chatbot.')->group(function () {
            Route::get('/config',                               [\App\Http\Controllers\Client\ChatbotController::class, 'getConfig'])->name('config');
            Route::post('/start',                               [\App\Http\Controllers\Client\ChatbotController::class, 'startChat'])->name('start');
            Route::post('/message',                             [\App\Http\Controllers\Client\ChatbotController::class, 'sendMessage'])->name('message');
            Route::post('/end',                                 [\App\Http\Controllers\Client\ChatbotController::class, 'endChat'])->name('end');
            Route::get('/history',                              [\App\Http\Controllers\Client\ChatbotController::class, 'getHistory'])->name('history');
            Route::post('/helpful',                             [\App\Http\Controllers\Client\ChatbotController::class, 'markHelpful'])->name('helpful');
            Route::post('/not-helpful',                         [\App\Http\Controllers\Client\ChatbotController::class, 'markNotHelpful'])->name('not-helpful');
        });
    });

    // Home redirect based on authentication
    Route::get('/', function () {
        if (auth()->check()) {
            $user = auth()->user();
            $routes = [
                'admin' => 'admin.dashboard',
                'owner' => 'owner.dashboard',
                'freelancer' => 'freelancer.dashboard',
                'client' => 'client.dashboard',
                'studio-photographer' => 'studio-photographer.dashboard',
                'studio-hr' => 'studio-hr.dashboard',
                'studio-finance' => 'studio-finance.dashboard',
            ];
            
            return redirect()->route($routes[$user->role] ?? 'login');
        }
        
        return redirect()->route('login');
    });
    
});

// Fallback route for 404 errors (MUST BE AT THE END)
Route::fallback(function () {
    if (auth()->check()) {
        // Redirect authenticated users to their dashboard
        $user = auth()->user();
        $routes = [
            'admin' => 'admin.dashboard',
            'owner' => 'owner.dashboard',
            'freelancer' => 'freelancer.dashboard',
            'client' => 'client.dashboard',
            'studio-photographer' => 'studio-photographer.dashboard',
            'studio-hr' => 'studio-hr.dashboard',
            'studio-finance' => 'studio-finance.dashboard',
        ];
        
        return redirect()->route($routes[$user->role] ?? 'login');
    }
    
    // Redirect unauthenticated users to login
    return redirect()->route('login')->with('error', 'Page not found.');
});
