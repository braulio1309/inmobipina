<?php

use App\Http\Controllers\App\Roles\RoleController;
use App\Http\Controllers\App\Users\UserController;
use App\Http\Controllers\App\Users\AppUserController;
use App\Http\Controllers\App\Users\UserRoleController;
use App\Http\Controllers\App\Users\UserSocialLinkController;
use App\Http\Controllers\App\Auth\AuthenticateUserController;
use App\Http\Controllers\App\Notification\NotificationController;
use App\Http\Controllers\App\Settings\ReCaptchaSettingController;
use App\Http\Controllers\App\PaymentMethod\StripeController;
use App\Http\Controllers\PropertyController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ActivityController;
use App\Http\Controllers\OperationController;
use App\Http\Controllers\App\ReportController;


Route::view('/properties', 'properties.index');
Route::view('/properties/create', 'properties.create');
Route::view('/clients', 'clients.index');
Route::view('/clients/create', 'clients.create');

Route::view('/operations', 'operations.index');
Route::view('/create/operations', 'operations.create');

Route::view('/activities', 'activities.index');

Route::view('/report/advisor', 'reports.advisor');
Route::group(['prefix' => 'app/reports'], function () {
    Route::get('/advisor', [ReportController::class, 'getAdvisorReports']);
    Route::get('/advisors', [ReportController::class, 'getAdvisors']);
});
Route::get('property/listar', [PropertyController::class, 'listado'])->name('property.listar');
Route::post('property/create', [PropertyController::class, 'create'])->name('property.crear');
Route::post('edit/property/{id}', [PropertyController::class, 'edit'])->name('property.edit');

Route::get('client/listar', [ClientController::class, 'listado'])->name('client.listar');
Route::post('client/create', [ClientController::class, 'create'])->name('client.crear');
Route::post('edit/client/{id}', [ClientController::class, 'edit'])->name('client.edit');

Route::get('activities/listar', [ActivityController::class, 'listado'])->name('Activity.listar');
Route::post('activities/create', [ActivityController::class, 'create'])->name('Activity.crear');
Route::post('edit/activities/{id}', [ActivityController::class, 'edit'])->name('Activity.edit');

Route::get('operations/listar', [OperationController::class, 'listado'])->name('operation.listar');
Route::post('operations/create', [OperationController::class, 'create'])->name('operations.crear');
Route::post('edit/operations/{id}', [OperationController::class, 'edit'])->name('operations.edit');
Route::get('/operations/form-data', [\App\Http\Controllers\OperationController::class, 'formData']);

Route::post('/users', [App\Http\Controllers\UserController::class, 'store']);
Route::post('/users/edit/{id}', [App\Http\Controllers\UserController::class, 'update']);
Route::get('/users/{id}', [App\Http\Controllers\UserController::class, 'showWithDiets']);

Route::get('check', function (){
    dd(redirect(config('app.url') . '/payment-view'));
});
//Route::get('/user/registration', [AuthenticateUserController::class, 'registerView']);

Route::get('/reset/password', [AuthenticateUserController::class, 'resetPasswordView']);
Route::view('/my-profile', 'user.profile');
Route::view('/users-and-roles', 'user-roles.user-roles')->name('user-role.list');
Route::view('/create-user', 'user.create')->name('user.create');
Route::view('/edit-user/{id}', 'user.edit')->name('user.edit');

//User
Route::resource('user-list', UserController::class);

// update user name
Route::post('/update-user-name/{id}', [UserController::class, 'updateUserName']);

// role
Route::get('users/{role}', [RoleController::class, 'getUsersByRole']);

// user
Route::get('all-users', [UserController::class, 'getUsers']);

//users
Route::get('get/users', [AppUserController::class, 'index']);

// role_user
Route::post('attach-user/{role}', [UserRoleController::class, 'store']);
// Route::delete('attach-user/{id}',[UserRoleController::class,'destroy']);

// Sample Pages Routes
Route::view('/blank-page', 'sample-pages.sample');

// Payment Methods
Route::view('/payment-view', 'sample-pages.payment-view');

// roles
Route::get('all-roles', [RoleController::class, 'getAllRoles']);

// ALl Notifications page
Route::get('/all-notifications', [NotificationController::class, 'view']);

Route::get('login-user', [AuthenticateUserController::class, 'show'])
    ->name('user.login-user');

Route::post('change-social-link', [UserSocialLinkController::class, 'update'])
    ->name('user.change-social-link');

//get captcha
Route::get('/get-re-captcha-setting', [ReCaptchaSettingController::class, 'getReCaptchaSettings'])
    ->name('re-captcha-settings.get');


Route::get('stripe', [StripeController::class, 'stripe']);
Route::post('stripe', [StripeController::class, 'stripePost'])->name('stripe.post');