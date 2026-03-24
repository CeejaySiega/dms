<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\dashboard\Analytics;
use App\Http\Controllers\layouts\WithoutMenu;
use App\Http\Controllers\layouts\WithoutNavbar;
use App\Http\Controllers\layouts\Fluid;
use App\Http\Controllers\layouts\Container;
use App\Http\Controllers\layouts\Blank;
use App\Http\Controllers\pages\AccountSettingsAccount;
use App\Http\Controllers\pages\AccountSettingsNotifications;
use App\Http\Controllers\pages\AccountSettingsConnections;
use App\Http\Controllers\pages\MiscError;
use App\Http\Controllers\pages\MiscUnderMaintenance;
use App\Http\Controllers\authentications\LoginBasic;
use App\Http\Controllers\authentications\RegisterBasic;
use App\Http\Controllers\authentications\ForgotPasswordBasic;
use App\Http\Controllers\cards\CardBasic;
use App\Http\Controllers\user_interface\Accordion;
use App\Http\Controllers\user_interface\Alerts;
use App\Http\Controllers\user_interface\Badges;
use App\Http\Controllers\user_interface\Buttons;
use App\Http\Controllers\user_interface\Carousel;
use App\Http\Controllers\user_interface\Collapse;
use App\Http\Controllers\user_interface\Dropdowns;
use App\Http\Controllers\user_interface\Footer;
use App\Http\Controllers\user_interface\ListGroups;
use App\Http\Controllers\user_interface\Modals;
use App\Http\Controllers\user_interface\Navbar;
use App\Http\Controllers\user_interface\Offcanvas;
use App\Http\Controllers\user_interface\PaginationBreadcrumbs;
use App\Http\Controllers\user_interface\Progress;
use App\Http\Controllers\user_interface\Spinners;
use App\Http\Controllers\user_interface\TabsPills;
use App\Http\Controllers\user_interface\Toasts;
use App\Http\Controllers\user_interface\TooltipsPopovers;
use App\Http\Controllers\user_interface\Typography;
use App\Http\Controllers\extended_ui\PerfectScrollbar;
use App\Http\Controllers\extended_ui\TextDivider;
use App\Http\Controllers\icons\Boxicons;
use App\Http\Controllers\form_elements\BasicInput;
use App\Http\Controllers\form_elements\InputGroups;
use App\Http\Controllers\form_layouts\VerticalForm;
use App\Http\Controllers\form_layouts\HorizontalForm;
use App\Http\Controllers\tables\Basic as TablesBasic;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\ArchiveDocumentController;
use App\Http\Controllers\ReceivedDocumentController;
use App\Http\Controllers\SentDocumentController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\AssignUserController;
use App\Http\Controllers\UserActivityLogController;

// Guest Routes (Login Page)
Route::middleware('guest')->group(function () {
    Route::get('/', [LoginBasic::class, 'index'])->name('auth-login-basic');
    Route::get('/login', [LoginBasic::class, 'index'])->name('login');
    Route::post('/login', [LoginBasic::class, 'login'])->name('login.post');
    Route::get('/auth/register-basic', [RegisterBasic::class, 'index'])->name('auth-register-basic');
    Route::get('/auth/forgot-password-basic', [ForgotPasswordBasic::class, 'index'])->name('auth-reset-password-basic');
});

// Google Authentication Routes
Route::get('/auth/google', [GoogleAuthController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');

// Protected Routes (Require Authentication)
Route::middleware('auth')->group(function () {
        // User Activity Logs
        Route::get('/user/activity-logs', [UserActivityLogController::class, 'index'])->name('user.activity-logs');
        Route::delete('/user/activity-logs/delete', [UserActivityLogController::class, 'deleteAll'])->name('user.activity-logs.delete');

    Route::get('/dashboard', [Analytics::class, 'index'])->name('dashboard-analytics');
    
    // Logout
    Route::post('/logout', [LoginBasic::class, 'logout'])->name('logout');

    // Admin Activity Logs
    Route::get('/admin/activity-logs', [\App\Http\Controllers\AdminActivityLogController::class, 'index'])->name('admin.activity-logs');

    // layout
    Route::get('/layouts/without-menu', [WithoutMenu::class, 'index'])->name('layouts-without-menu');
    Route::get('/layouts/without-navbar', [WithoutNavbar::class, 'index'])->name('layouts-without-navbar');
    Route::get('/layouts/fluid', [Fluid::class, 'index'])->name('layouts-fluid');
    Route::get('/layouts/container', [Container::class, 'index'])->name('layouts-container');
    Route::get('/layouts/blank', [Blank::class, 'index'])->name('layouts-blank');

    // pages
    Route::get('/pages/account-settings-account', [AccountSettingsAccount::class, 'index'])->name('pages-account-settings-account');
    Route::get('/pages/account-settings-notifications', [AccountSettingsNotifications::class, 'index'])->name('pages-account-settings-notifications');
    Route::get('/pages/account-settings-connections', [AccountSettingsConnections::class, 'index'])->name('pages-account-settings-connections');
    Route::get('/pages/misc-error', [MiscError::class, 'index'])->name('pages-misc-error');
    Route::get('/pages/misc-under-maintenance', [MiscUnderMaintenance::class, 'index'])->name('pages-misc-under-maintenance');

    // cards
    Route::get('/cards/basic', [CardBasic::class, 'index'])->name('cards-basic');

    // User Interface
    Route::get('/ui/accordion', [Accordion::class, 'index'])->name('ui-accordion');
    Route::get('/ui/alerts', [Alerts::class, 'index'])->name('ui-alerts');
    Route::get('/ui/badges', [Badges::class, 'index'])->name('ui-badges');
    Route::get('/ui/buttons', [Buttons::class, 'index'])->name('ui-buttons');
    Route::get('/ui/carousel', [Carousel::class, 'index'])->name('ui-carousel');
    Route::get('/ui/collapse', [Collapse::class, 'index'])->name('ui-collapse');
    Route::get('/ui/dropdowns', [Dropdowns::class, 'index'])->name('ui-dropdowns');
    Route::get('/ui/footer', [Footer::class, 'index'])->name('ui-footer');
    Route::get('/ui/list-groups', [ListGroups::class, 'index'])->name('ui-list-groups');
    Route::get('/ui/modals', [Modals::class, 'index'])->name('ui-modals');
    Route::get('/ui/navbar', [Navbar::class, 'index'])->name('ui-navbar');
    Route::get('/ui/offcanvas', [Offcanvas::class, 'index'])->name('ui-offcanvas');
    Route::get('/ui/pagination-breadcrumbs', [PaginationBreadcrumbs::class, 'index'])->name('ui-pagination-breadcrumbs');
    Route::get('/ui/progress', [Progress::class, 'index'])->name('ui-progress');
    Route::get('/ui/spinners', [Spinners::class, 'index'])->name('ui-spinners');
    Route::get('/ui/tabs-pills', [TabsPills::class, 'index'])->name('ui-tabs-pills');
    Route::get('/ui/toasts', [Toasts::class, 'index'])->name('ui-toasts');
    Route::get('/ui/tooltips-popovers', [TooltipsPopovers::class, 'index'])->name('ui-tooltips-popovers');
    Route::get('/ui/typography', [Typography::class, 'index'])->name('ui-typography');

    // extended ui
    Route::get('/extended/ui-perfect-scrollbar', [PerfectScrollbar::class, 'index'])->name('extended-ui-perfect-scrollbar');
    Route::get('/extended/ui-text-divider', [TextDivider::class, 'index'])->name('extended-ui-text-divider');

    // icons
    Route::get('/icons/boxicons', [Boxicons::class, 'index'])->name('icons-boxicons');

    // form elements
    Route::get('/forms/basic-inputs', [BasicInput::class, 'index'])->name('forms-basic-inputs');
    Route::get('/forms/input-groups', [InputGroups::class, 'index'])->name('forms-input-groups');

    // form layouts
    Route::get('/form/layouts-vertical', [VerticalForm::class, 'index'])->name('form-layouts-vertical');
    Route::get('/form/layouts-horizontal', [HorizontalForm::class, 'index'])->name('form-layouts-horizontal');

    // tables
    Route::get('/tables/basic', [TablesBasic::class, 'index'])->name('tables-basic');

    // user management
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::post('/users/{user}/change-role', [UserController::class, 'changeRole'])->name('users.changeRole');
    Route::post('/users/create-test-user', [UserController::class, 'createTestUser'])->name('users.create-test-user');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

    // profile routes
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [UserController::class, 'viewProfile'])->name('view');
        Route::get('/edit', [UserController::class, 'editProfile'])->name('edit');
        Route::put('/update', [UserController::class, 'updateProfile'])->name('update');
    });

    // group management
    Route::prefix('groups')->name('groups.')->group(function () {
        Route::get('/', [GroupController::class, 'index'])->name('index');
        Route::post('/', [GroupController::class, 'store'])->name('store');
        
        // Assign users routes (must come before {group} routes)
        Route::get('/assign/{group}', [AssignUserController::class, 'show'])->name('assign.show');
        Route::get('/assign/{group}/members', [AssignUserController::class, 'getMembers'])->name('assign.getMembers');
        Route::get('/assign/{group}/users', [AssignUserController::class, 'getUsers'])->name('assign.getUsers');
        Route::post('/assign/{group}', [AssignUserController::class, 'assignUsers'])->name('assign.users');
        Route::delete('/assign/{group}', [AssignUserController::class, 'removeUsers'])->name('assign.removeUsers');
        Route::post('/assign/bulk-assign', [AssignUserController::class, 'bulkAssign'])->name('assign.bulk');
        
        // Generic group routes (must come after specific routes)
        Route::put('/{group}', [GroupController::class, 'update'])->name('update');
        Route::delete('/{group}', [GroupController::class, 'destroy'])->name('destroy');
    });

    // document management
    Route::prefix('documents')->name('documents.')->group(function () {
            // Unsend for individual send
        Route::delete('/{document}/unsend-individual', [SentDocumentController::class, 'unsendIndividual'])->name('unsend-individual');
        // Route::delete('/{document}/unsend-all', [SentDocumentController::class, 'unsendAll'])->name('unsend-all');
        Route::get('/send', [DocumentController::class, 'create'])->name('send');
        Route::post('/review', [DocumentController::class, 'review'])->name('review');
        Route::get('/review', [DocumentController::class, 'showReview'])->name('show-review');
        Route::get('/send/individual', [DocumentController::class, 'sendIndividual'])->name('send-individual');
        Route::get('/send/group', [DocumentController::class, 'sendGroup'])->name('send-group');
        Route::post('/store', [DocumentController::class, 'store'])->name('store');
        Route::post('/store-group', [DocumentController::class, 'storeGroup'])->name('store-group');
        Route::get('/receipt/{documentId}', [DocumentController::class, 'showReceipt'])->name('receipt');
        Route::get('/incoming', [ReceivedDocumentController::class, 'index'])->name('incoming');
        Route::get('/received', [ReceivedDocumentController::class, 'received'])->name('received');
        Route::get('/pending-count', [ReceivedDocumentController::class, 'getPendingCount'])->name('pending-count');
        Route::get('/pending-documents', [ReceivedDocumentController::class, 'getPendingDocuments'])->name('pending-documents');
        Route::get('/received-by-others', [ReceivedDocumentController::class, 'getReceivedByOthersNotifications'])->name('received-by-others');
        Route::get('/forwarded-by-others', [ReceivedDocumentController::class, 'getForwardedByOthersNotifications'])->name('forwarded-by-others');
        Route::post('/{document}/approve', [ReceivedDocumentController::class, 'approve'])->name('approve');
        Route::post('/{document}/receive', [ReceivedDocumentController::class, 'receive'])->name('receive');
        Route::post('/{document}/disapprove', [ReceivedDocumentController::class, 'disapprove'])->name('disapprove');
        Route::post('/mark-as-read/{recipientId}', [ReceivedDocumentController::class, 'markAsRead'])->name('mark-as-read');
        Route::delete('/received/{receivedDocument}', [ReceivedDocumentController::class, 'deleteReceived'])->name('received.delete');
        Route::get('/sent', [SentDocumentController::class, 'sent'])->name('sent');
        Route::get('/archived', [ArchiveDocumentController::class, 'index'])->name('archived');
        Route::get('/restored', [ArchiveDocumentController::class, 'restored'])->name('restored');
        Route::get('/all', [DocumentController::class, 'all'])->name('all');
        Route::get('/stats', [DocumentController::class, 'getStats'])->name('stats');
        Route::get('/search', [DocumentController::class, 'search'])->name('search');
        Route::get('/forward/{documentId}', [DocumentController::class, 'forwardForm'])->name('forward.form');
        Route::post('/forward/{documentId}', [DocumentController::class, 'forwardStore'])->name('forward.store');
        


        //Sent/archived document management routes (must come after other document routes to avoid conflicts)
        Route::delete('/{document}/delete-document', [SentDocumentController::class, 'deleteDocument'])->name('delete-document');
        Route::delete('/{document}/recipients/{recipient}', [SentDocumentController::class, 'unsendRecipient'])->name('unsend-recipient');
        Route::get('/{document}', [DocumentController::class, 'show'])->name('show');
        Route::get('/{document}/download', [DocumentController::class, 'download'])->name('download');
        Route::post('/{document}/archive', [ArchiveDocumentController::class, 'archive'])->name('archive');
        Route::post('/{document}/archive-receiver', [ArchiveDocumentController::class, 'archiveAsReceiver'])->name('archive-receiver');
        Route::post('/archives/{archive}/restore', [ArchiveDocumentController::class, 'restore'])->name('restore');
        Route::post('/archives/{archive}/soft-delete', [ArchiveDocumentController::class, 'softDeleteArchive'])->name('soft-delete-archive');
        Route::get('/{document}/trail/data', [SentDocumentController::class, 'trailData'])->name('trail.data');
        Route::delete('/{document}', [SentDocumentController::class, 'delete'])->name('delete');
        Route::delete('/archives/{archive}/permanent', [ArchiveDocumentController::class, 'destroy'])->name('permanent-delete');
    });

    // workflow
    Route::prefix('workflow')->name('workflow.')->group(function () {
        Route::view('/document-trail', 'content.workflow.document-trail')->name('document-trail');
    });
});
