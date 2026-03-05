<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\ClientMiddleware;
use App\Http\Middleware\OwnerMiddleware;
use App\Http\Middleware\FreelancerMiddleware;
use App\Http\Middleware\StudioPhotographerMiddleware;
use App\Http\Middleware\StudioHRMiddleware;

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

        // Manage Studio - Limit
        Route::middleware(['check.studio.limit'])->group(function () {
            Route::get('/create/studio',                        [\App\Http\Controllers\StudioOwner\StudioController::class, 'create'])->name('owner.studio.create');
            Route::post('/studio',                              [\App\Http\Controllers\StudioOwner\StudioController::class, 'store'])->name('owner.studio.store');
        });

        // Manage Studio                            
        Route::get('/view/studio',                              [\App\Http\Controllers\StudioOwner\StudioController::class, 'index'])->name('owner.studio.index');
        Route::get('/studio/barangays/{municipality}',          [\App\Http\Controllers\StudioOwner\StudioController::class, 'getBarangays'])->name('owner.studio.get-barangays');
        Route::delete('/studio/{id}',                           [\App\Http\Controllers\StudioOwner\StudioController::class, 'destroy'])->name('owner.studio.destroy');

        // Manage Bookings                          
        Route::get('/view/bookings',                            [\App\Http\Controllers\StudioOwner\BookingController::class, 'index'])->name('owner.booking.index');
        Route::get('/booking/history',                          [\App\Http\Controllers\StudioOwner\BookingController::class, 'history'])->name('owner.booking.history');
        Route::get('/bookings/{id}/details',                    [\App\Http\Controllers\StudioOwner\BookingController::class, 'getBookingDetails'])->name('owner.booking.details');
        Route::get('/bookings/{id}/available-photographers',    [\App\Http\Controllers\StudioOwner\BookingController::class, 'getAvailablePhotographers'])->name('owner.booking.available.photographers');
        Route::post('/bookings/{id}/assign-photographers',      [\App\Http\Controllers\StudioOwner\BookingController::class, 'assignPhotographers'])->name('owner.booking.assign.photographers');
        Route::delete('/assignments/{id}',                      [\App\Http\Controllers\StudioOwner\BookingController::class, 'removePhotographerAssignment'])->name('owner.booking.remove.assignment');
        Route::put('/assignments/{id}/status',                  [\App\Http\Controllers\StudioOwner\BookingController::class, 'updateAssignmentStatus'])->name('owner.booking.update.assignment.status');
        Route::put('/bookings/{id}/status',                     [\App\Http\Controllers\StudioOwner\BookingController::class, 'updateStatus'])->name('owner.booking.update.status');
        Route::put('/bookings/{id}/complete',                   [\App\Http\Controllers\StudioOwner\BookingController::class, 'completeBooking'])->name('owner.booking.complete');

        // Manage Online Gallery
        Route::get('/view/online-gallery',                      [\App\Http\Controllers\StudioOwner\OnlineGalleryController::class, 'index'])->name('owner.online-gallery.index');
        Route::get('/online-gallery/completed-bookings',        [\App\Http\Controllers\StudioOwner\OnlineGalleryController::class, 'getCompletedBookings'])->name('owner.online-gallery.completed-bookings');
        Route::get('/online-gallery/{bookingId}/details',       [\App\Http\Controllers\StudioOwner\OnlineGalleryController::class, 'getGalleryDetails'])->name('owner.online-gallery.details');
        Route::post('/online-gallery/{bookingId}/upload',       [\App\Http\Controllers\StudioOwner\OnlineGalleryController::class, 'uploadImages'])->name('owner.online-gallery.upload');
        Route::delete('/online-gallery/{galleryId}/image',      [\App\Http\Controllers\StudioOwner\OnlineGalleryController::class, 'deleteImage'])->name('owner.online-gallery.delete-image');
        Route::delete('/online-gallery/{galleryId}',            [\App\Http\Controllers\StudioOwner\OnlineGalleryController::class, 'deleteGallery'])->name('owner.online-gallery.delete');
        Route::put('/online-gallery/{galleryId}',               [\App\Http\Controllers\StudioOwner\OnlineGalleryController::class, 'updateGallery'])->name('owner.online-gallery.update');
        
        // Manage Studio Schedule                       
        Route::get('/view/schedules',                           [\App\Http\Controllers\StudioOwner\StudioScheduleController::class, 'index'])->name('owner.studio-schedule.index');
        Route::get('/setup/studio-schedules',                   [\App\Http\Controllers\StudioOwner\StudioScheduleController::class, 'setupStudioSchedule'])->name('owner.setup-studio-schedules');
        Route::post('/store/studio-schedules',                  [\App\Http\Controllers\StudioOwner\StudioScheduleController::class, 'store'])->name('owner.studio-schedule.store');
        Route::delete('/delete/studio-schedules/{id}',          [\App\Http\Controllers\StudioOwner\StudioScheduleController::class, 'destroy'])->name('owner.studio-schedule.destroy');

        // Studio Members                           
        Route::get('/view/members',                             [\App\Http\Controllers\StudioOwner\StudioMemberController::class, 'index'])->name('owner.members.index');
        Route::get('/invite/members',                           [\App\Http\Controllers\StudioOwner\StudioMemberController::class, 'invite'])->name('owner.members.invite');
        Route::get('/apply/members',                            [\App\Http\Controllers\StudioOwner\StudioMemberController::class, 'apply'])->name('owner.members.apply');
        Route::post('/members/invite',                          [\App\Http\Controllers\StudioOwner\StudioMemberController::class, 'store'])->name('owner.members.invite.store');
        Route::post('/members/{id}/cancel',                     [\App\Http\Controllers\StudioOwner\StudioMemberController::class, 'cancel'])->name('owner.members.cancel');
        Route::get('/members/freelancer/{id}/details',          [\App\Http\Controllers\StudioOwner\StudioMemberController::class, 'getFreelancerDetails'])->name('owner.members.freelancer.details');

        // Studio Photographers 
        Route::get('/view/studio-photographers',                [\App\Http\Controllers\StudioOwner\StudioPhotographersController::class, 'index'])->name('owner.studio-photographers.index');
        Route::get('/create/studio-photographers',              [\App\Http\Controllers\StudioOwner\StudioPhotographersController::class, 'create'])->name('owner.studio-photographers.create');
        Route::post('/studio-photographers',                    [\App\Http\Controllers\StudioOwner\StudioPhotographersController::class, 'store'])->name('owner.studio-photographers.store');
        Route::get('/studio-photographers/{id}',                [\App\Http\Controllers\StudioOwner\StudioPhotographersController::class, 'show'])->name('owner.studio-photographers.show');
        Route::get('/studio/{id}/services',                     [\App\Http\Controllers\StudioOwner\StudioPhotographersController::class, 'getStudioServices'])->name('owner.studio.services');

        // Manage Category Services                         
        Route::get('/view/services',                            [\App\Http\Controllers\StudioOwner\ServicesController::class, 'index'])->name('owner.services.index');
        Route::get('/create/services',                          [\App\Http\Controllers\StudioOwner\ServicesController::class, 'create'])->name('owner.services.create');
        Route::post('/services',                                [\App\Http\Controllers\StudioOwner\ServicesController::class, 'store'])->name('owner.services.store');
        Route::get('/services/{id}',                            [\App\Http\Controllers\StudioOwner\ServicesController::class, 'show'])->name('owner.services.show');
        Route::get('/services/{id}/edit',                       [\App\Http\Controllers\StudioOwner\ServicesController::class, 'edit'])->name('owner.services.edit');
        Route::put('/services/{id}',                            [\App\Http\Controllers\StudioOwner\ServicesController::class, 'update'])->name('owner.services.update');
        Route::delete('/services/{id}',                         [\App\Http\Controllers\StudioOwner\ServicesController::class, 'destroy'])->name('owner.services.destroy');
        Route::get('/services/data/get',                        [\App\Http\Controllers\StudioOwner\ServicesController::class, 'getServices'])->name('owner.services.data');

        // Manage Packages  
        Route::get('/view/packages',                            [\App\Http\Controllers\StudioOwner\PackagesController::class, 'index'])->name('owner.packages.index');
        Route::get('/create/packages',                          [\App\Http\Controllers\StudioOwner\PackagesController::class, 'create'])->name('owner.packages.create');
        Route::post('/packages',                                [\App\Http\Controllers\StudioOwner\PackagesController::class, 'store'])->name('owner.packages.store');
        Route::get('/packages/lists',                           [\App\Http\Controllers\StudioOwner\PackagesController::class, 'list'])->name('owner.packages.list');
        Route::get('/packages/{package}',                       [\App\Http\Controllers\StudioOwner\PackagesController::class, 'show'])->name('owner.packages.show');

        // Manage Subscription  
        Route::get('/view/subscription',                        [\App\Http\Controllers\StudioOwner\SubscriptionController::class, 'index'])->name('owner.subscription.index');
        Route::get('/view/status',                              [\App\Http\Controllers\StudioOwner\SubscriptionController::class, 'status'])->name('owner.subscription.status');
        Route::get('/subscription/{id}',                        [\App\Http\Controllers\StudioOwner\SubscriptionController::class, 'show'])->name('owner.subscription.show');
        Route::post('/subscription/subscribe',                  [\App\Http\Controllers\StudioOwner\SubscriptionController::class, 'subscribe'])->name('owner.subscription.subscribe');
        Route::get('/subscription/history/data',                [\App\Http\Controllers\StudioOwner\SubscriptionController::class, 'history'])->name('owner.subscription.history');
        Route::get('/subscription/status/data',                 [\App\Http\Controllers\StudioOwner\SubscriptionController::class, 'getStatusData'])->name('owner.subscription.status.data');
        Route::post('/subscription/{id}/cancel',                [\App\Http\Controllers\StudioOwner\SubscriptionController::class, 'cancel'])->name('owner.subscription.cancel');
        Route::get('/subscription/{id}/details',                [\App\Http\Controllers\StudioOwner\SubscriptionController::class, 'getSubscriptionDetails'])->name('owner.subscription.details');
        Route::get('/subscription/verify/{reference}',          [\App\Http\Controllers\StudioOwner\SubscriptionController::class, 'verifyPayment'])->name('owner.subscription.verify');
        Route::get('/subscription/success/{reference}',         [\App\Http\Controllers\StudioOwner\SubscriptionController::class, 'paymentSuccess'])->name('owner.subscription.success');
        Route::get('/subscription/failed/{reference}',          [\App\Http\Controllers\StudioOwner\SubscriptionController::class, 'paymentFailed'])->name('owner.subscription.failed');

        // Manage Employee  
        Route::get('/view/employee',                                [\App\Http\Controllers\StudioOwner\EmployeeController::class, 'index'])->name('owner.employee.index');
        Route::get('/create/employee',                              [\App\Http\Controllers\StudioOwner\EmployeeController::class, 'create'])->name('owner.employee.create');
        Route::post('/employee',                                    [\App\Http\Controllers\StudioOwner\EmployeeController::class, 'store'])->name('owner.employee.store');
        Route::get('/employees/data',                               [\App\Http\Controllers\StudioOwner\EmployeeController::class, 'getEmployees'])->name('owner.employee.data');
        Route::get('/employee/categories',                          [\App\Http\Controllers\StudioOwner\EmployeeController::class, 'getCategories'])->name('owner.employee.categories');
        Route::get('/employee/{id}',                                [\App\Http\Controllers\StudioOwner\EmployeeController::class, 'show'])->name('owner.employee.show');
        Route::put('/employee/{id}/status',                         [\App\Http\Controllers\StudioOwner\EmployeeController::class, 'updateStatus'])->name('owner.employee.update-status');
        Route::put('/employee/{id}/permissions',                    [\App\Http\Controllers\StudioOwner\EmployeeController::class, 'updatePermissions'])->name('owner.employee.update-permissions');
        Route::put('/employee/{id}/schedule',                       [\App\Http\Controllers\StudioOwner\EmployeeController::class, 'updateSchedule'])->name('owner.employee.update-schedule');
        Route::delete('/employee/{id}',                             [\App\Http\Controllers\StudioOwner\EmployeeController::class, 'destroy'])->name('owner.employee.destroy');
        Route::get('/employee/{studioId}/services/{categoryId}',    [\App\Http\Controllers\StudioOwner\EmployeeController::class, 'getServicesByCategory'])->name('owner.employee.services-by-category');

        // Manage Payroll  
        Route::get('/payroll-settings',                            [\App\Http\Controllers\StudioOwner\PayrollSettingsController::class, 'index'])->name('owner.payroll-settings.index');
        Route::get('/payroll-settings/create',                     [\App\Http\Controllers\StudioOwner\PayrollSettingsController::class, 'create'])->name('owner.payroll-settings.create');
        Route::post('/payroll-settings',                           [\App\Http\Controllers\StudioOwner\PayrollSettingsController::class, 'store'])->name('owner.payroll-settings.store');
        Route::get('/payroll-settings/employees',                  [\App\Http\Controllers\StudioOwner\PayrollSettingsController::class, 'getEmployees'])->name('owner.payroll-settings.employees');
        Route::get('/payroll-settings/data',                       [\App\Http\Controllers\StudioOwner\PayrollSettingsController::class, 'getPayrollSettings'])->name('owner.payroll-settings.data');
        Route::get('/payroll-settings/{id}',                       [\App\Http\Controllers\StudioOwner\PayrollSettingsController::class, 'show'])->name('owner.payroll-settings.show');
        Route::get('/payroll-settings/{id}/edit',                  [\App\Http\Controllers\StudioOwner\PayrollSettingsController::class, 'edit'])->name('owner.payroll-settings.edit');
        Route::put('/payroll-settings/{id}',                       [\App\Http\Controllers\StudioOwner\PayrollSettingsController::class, 'update'])->name('owner.payroll-settings.update');
        Route::put('/payroll-settings/{id}/status',                [\App\Http\Controllers\StudioOwner\PayrollSettingsController::class, 'updateStatus'])->name('owner.payroll-settings.status');
        Route::delete('/payroll-settings/{id}',                    [\App\Http\Controllers\StudioOwner\PayrollSettingsController::class, 'destroy'])->name('owner.payroll-settings.destroy');
    });

    // Studio HR Routes ====================================================================================================================================================
    Route::prefix('studio-hr')->middleware([StudioHRMiddleware::class])->group(function () {

        // Profile
        Route::get('/profile',                                      [\App\Http\Controllers\GeneralProfileController::class, 'studioHR'])->name('studio-hr.profile');

        // Dashboard
        Route::get('/dashboard',                                    [\App\Http\Controllers\StudioHR\DashboardController::class, 'index'])->name('studio-hr.dashboard');

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
        Route::get('/profile',                                  [\App\Http\Controllers\GeneralProfileController::class, 'studio-photographer'])->name('studio-photographer.profile');

        // Dashboard
        Route::get('/dashboard',                        [\App\Http\Controllers\StudioPhotographer\DashboardController::class, 'index'])->name('studio-photographer.dashboard');

        // Assigned Studio
        Route::get('/view/studio',                      [\App\Http\Controllers\StudioPhotographer\AssignedStudioController::class, 'index'])->name('studio-photographer.studio.index');
        Route::get('/studio/{id}/details',              [\App\Http\Controllers\StudioPhotographer\AssignedStudioController::class, 'getStudioDetails'])->name('studio-photographer.studio.details');

        // Assigned Studio
        Route::get('/assigned-bookings',                [\App\Http\Controllers\StudioPhotographer\AssignedBookingController::class, 'index'])->name('assigned.bookings');
        Route::get('/assignment/{id}/details',          [\App\Http\Controllers\StudioPhotographer\AssignedBookingController::class, 'getBookingDetails'])->name('assignment.details');
        Route::post('/assignment/{id}/update-status',   [\App\Http\Controllers\StudioPhotographer\AssignedBookingController::class, 'updateAssignmentStatus'])->name('assignment.update-status');

        // Manage Online Gallery
        Route::get('/view/online-gallery',                  [\App\Http\Controllers\StudioPhotographer\OnlineGalleryController::class, 'index'])->name('studio-photographer.online-gallery.index');
        Route::get('/online-gallery/{bookingId}/details',   [\App\Http\Controllers\StudioPhotographer\OnlineGalleryController::class, 'getGalleryDetails'])->name('studio-photographer.online-gallery.details');
        Route::post('/online-gallery/{bookingId}/upload',   [\App\Http\Controllers\StudioPhotographer\OnlineGalleryController::class, 'uploadImages'])->name('studio-photographer.online-gallery.upload');
        Route::delete('/online-gallery/{galleryId}/image',  [\App\Http\Controllers\StudioPhotographer\OnlineGalleryController::class, 'deleteImage'])->name('studio-photographer.online-gallery.delete-image');
        Route::delete('/online-gallery/{galleryId}',        [\App\Http\Controllers\StudioPhotographer\OnlineGalleryController::class, 'deleteGallery'])->name('studio-photographer.online-gallery.delete');
        Route::put('/online-gallery/{galleryId}',           [\App\Http\Controllers\StudioPhotographer\OnlineGalleryController::class, 'updateGallery'])->name('studio-photographer.online-gallery.update');

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
                'studio-photographer' => 'studio-photographer.dashboard'
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
            'studio-photographer' => 'studio-photographer.dashboard'
        ];
        
        return redirect()->route($routes[$user->role] ?? 'login');
    }
    
    // Redirect unauthenticated users to login
    return redirect()->route('login')->with('error', 'Page not found.');
});