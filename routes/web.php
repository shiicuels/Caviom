<?php

use App\Http\Controllers\Charity\CharityController;
use App\Http\Controllers\Charity\BeneficiaryController;
use App\Http\Controllers\Charity\Beneficiary2Controller;
use App\Http\Controllers\Charity\Beneficiary3Controller;
use App\Http\Controllers\Charity\BenefactorController;
use App\Http\Controllers\Charity\VolunteerController;
use App\Http\Controllers\Charity\StarTokenController;
use App\Http\Controllers\RootAdmin\AdminController;
use App\Http\Controllers\Charity\AuditLogController;
use App\Http\Controllers\Charity\GiftGivingController;
use App\Http\Controllers\Charity\NotificationController;
use App\Http\Controllers\Charity\UserController;
use App\Http\Controllers\Charity\PublicProfile\ProfileController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\RootAdmin\AuditLogController as RootAdminAuditLogController;
use App\Http\Controllers\RootAdmin\CharitableOrganizationController;
use App\Http\Controllers\RootAdmin\FeaturedProjectController;
use App\Http\Controllers\RootAdmin\NotifierController;
use App\Http\Controllers\RootAdmin\OrderController;
use App\Http\Controllers\RootAdmin\UserController as RootAdminUserController;
use App\Http\Controllers\Charity\LeadController;
use App\Http\Controllers\Charity\ProspectController;
use App\Http\Controllers\Charity\ProjectController;

use App\Http\Controllers\Charity\PublicProfile\FeaturedProjectController as CharityFeaturedProjectController;
use Illuminate\Support\Facades\Route;

# Public Pages
Route::controller(PublicController::class)->group(function () {
    Route::get('/', 'showHome')->name('home');
    Route::get('/about', 'showAbout')->name('about');
    Route::get('/services', 'showServices')->name('services');
    Route::get('/contact', 'showContact')->name('contact');
    Route::post('/Donate/{code}', 'Donate')->name('store.donate');

    # Charity Public Profile Pages
    Route::name('charities')->prefix('/charitable-organizations')->middleware(['prevent-back-history'])->group(function () {

        # Show All Charitable Organizations
        Route::get('/', 'showAllCharities')->name('.all');

        # View Specific Charitable Organization
        Route::get('/{code}', 'viewCharity')->name('.view');

        # View Specific Featured Project of a Charitable Organization
        Route::get('/featured-project/{code}', 'viewFeaturedProject')->name('.feat-proj.view');
    });
});

# Charity Group Controller
Route::controller(CharityController::class)->middleware(['auth', 'verified', 'prevent-back-history'])->group(function () {

    # Dashboard
    Route::prefix('/charity')->group(function () {
        Route::get('/dashboard', 'showDashboard')->name('dashboard');
    });
    # Notifications
    Route::prefix('/notifications')->group(function () {
        Route::get('/', 'showNotifications')->name('user.notifications.all');
        Route::get('/19caf827-1ba2-4a16-836a-d3d48643ca0a', 'viewNotification')->name('user.notifications.view');
    });
    # User Profile
    Route::prefix('/profile')->group(function () {
        Route::get('/', 'showProfile')->name('user.profile');
        Route::get('/edit', 'editProfile')->name('user.profile.edit');
        Route::post('/store', 'storeProfile')->name('user.profile.store');
    });
    # Change Password
    Route::prefix('/password')->group(function () {
        Route::get('/change', 'editPassword')->name('user.password.change');
        Route::post('/store', 'storePassword')->name('user.password.store');
    });
});

# Logout
Route::get('/user/logout', [CharityController::class, 'destroy'])->name('user.logout');

# User Notifications
Route::name('notifications')->middleware(['auth', 'verified', 'prevent-back-history'])->prefix('/notifications')->group(function () {
    # Retrieve All Notifications of User
    Route::get('/', [NotificationController::class, 'AllNotification'])->name('.all');

    # View Notification via $code
    Route::get('/{code}', [NotificationController::class, 'ViewNotification'])->name('.view');

    # Delete Notification
    Route::get('/delete/{code}', [NotificationController::class, 'DeleteNotification'])->name('.delete');

    # Fetch last 3 notifications
    Route::get('/fetchtthreenotification', [NotificationController::class, 'NotificationsData'])->name('.fetch');
});

# Charity Users Group
Route::middleware(['auth', 'verified', 'prevent-back-history', 'charity.user'])->group(function () {

    Route::prefix('/charity')->group(function () {
        # Donors and Donations Group
        Route::prefix('/donors-and-donations')->group(function () {

            # All Leads
            Route::get('/leads', [LeadController::class, 'AllLeads'])->name('leads.all');

            # View Leads
            Route::get('/leads/{code}', [LeadController::class, 'ViewLead'])->name('leads.view');

            # Deleting Leads
            Route::get('/leads/delete/{code}', [LeadController::class, 'DeleteLead'])->name('leads.delete');

            # Add Leads to Prospects
            Route::get('/leads/update/prospect/{code}', [LeadController::class, 'MoveAsProspect'])->name('move.to.prospect');


            # All Prospects
            Route::get('/prospects', [ProspectController::class, 'AllProspect'])->name('prospects.all');

            # View Prospect
            Route::get('/prospects/view/{code}', [ProspectController::class, 'ViewProspect'])->name('prospects.view');

            # Move Prospects back to Leads
            Route::get('/leads/move/back/leads/{code}', [ProspectController::class, 'MoveToLeads'])->name('move.back.leads');

            # Export Donation Report with PDF
            Route::get('/export/DonationReport', [ProspectController::class, 'GenerateDonationReport'])->name('generate.donation.report');

            # Update the remarks of Prospects.
            Route::post('/prospect/add/remarks/{code}', [ProspectController::class, 'AddRemarks'])->name('add.remarks');

            # Add Prospect to Opportunity (as Volunteer)
            Route::get('/prospects/add/opportunity/{code}', [ProspectController::class, 'AddasOpportunityBenefactor'])->name('add.to.benefactor');

            # Add Prospect to Opportunity (as Benefactor)
            Route::get('/prospects/add/volunteer/{code}', [ProspectController::class, 'AddasOpportunityVolunteer'])->name('add.to.volunteer');
        });

        # Our Charitable Organization
        Route::name('charity.')->prefix('/our-charitable-org')->group(function () {

            # Public Profile - Only Charity Admins can access the ff:
            Route::name('profile')->prefix('/profile')->middleware(['charity.admin'])->group(function () {

                # Public Profile
                Route::controller(ProfileController::class)->group(function () {

                    # Show Public Profile Controls
                    Route::get('', 'showProfileIndex');

                    # Setup Public Profile
                    Route::get('/setup', 'setupProfile')->name('.setup');

                    # Save Public Profile
                    Route::post('primary-info/save', 'storePrimaryInfo')->name('.store_primary');
                    Route::post('secondary-info/save', 'storeSecondaryInfo')->name('.store_secondary');
                    Route::post('awards/save', 'storeAwards')->name('.store_awards');
                    Route::get('awards/delete/{id}', 'destroyAward')->name('.destroy_awards');
                    Route::post('programs/save', 'storePrograms')->name('.store_programs');
                    Route::get('programs/delete/{id}', 'destroyProgram')->name('.destroy_programs');
                    Route::post('donation-mode/save', 'storeDonationModes')->name('.store_donations');
                    Route::get('donation-mod/delete/{id}', 'destroyDonationModes')->name('.destroy_donation_mode');

                    # Publish public profile
                    Route::post('publish', 'publishProfile')->name('.publish');

                    # Save Profile Cover Photos using Dropzone
                    Route::get('/cover_photos/gallery', 'getImages')->name('.cover_photos.get');
                    Route::post('/cover_photos/gallery', 'destroy')->name('.cover_photo.delete');
                    Route::post('/cover_photos/save', 'dropZoneCoverPhotos')->name('.cover_photos.save');

                    # Apply for Verification (for Unverified)
                    Route::get('/apply-for-verification', 'applyVerification')->name('.verify');
                    Route::post('/submit-requirements', 'submitRequirements')->name('.apply');

                    # To add: Re-apply for Verification (for Declined)
                    Route::get('/reapply-for-verification', 'reapplyVerification')->name('.reverify');


                    # To add: Set profile_status to Hidden - middleware('profile.set')->


                    # To add: Set profile_status to Visible - middleware('profile.set')-> and $charity->profile_status != 'Locked' only.
                });


                # Featured Projects
                Route::middleware('profile.set')->name('.feat-project')->prefix('/featured-project')->controller(CharityFeaturedProjectController::class)->group(function () {

                    # All Featured Project
                    Route::get('/all', 'AllFeaturedProject')->name('.all');

                    # View Featured Project
                    Route::get('/view/{code}', 'ViewFeaturedProject')->name('.view');

                    # Create New Featured Project
                    Route::get('/new', 'NewFeaturedProject')->name('.new');

                    # Store New Freatured Project
                    Route::post('/store/new', 'StoreNewFeaturedProject')->name('.new.store');

                    # Add Featured Project (from Existing Gift Giving Project)
                    Route::get('/add/gift/{code}', 'AddExistedGiftFeaturedProject')->name('.add.gift');

                    # Store Featured Project (from Existing Gift Giving Project)
                    Route::post('/store/add/gift', 'StoreExistedGiftFeaturedProject')->name('.add.gift.store');

                    # Add Featured Project (from Existing Task based Project)
                    Route::get('/add/project/{code}', 'AddExistedTaskFeaturedProject')->name('.add.project');

                    # Store Featured Project (from Existing Task based Project)
                    Route::post('/store/add/project/{code}', 'StoreExistedProjectFeaturedProject')->name('.add.project.store');
                });
            });

            # Projects
            Route::name('projects')->prefix('/projects')->group(function () {

                # All Projects
                Route::get('/', [ProjectController::class, 'AllProject'])->name('.all');

                # View Project
                Route::get('/view/{code}', [ProjectController::class, 'ViewProject'])->name('.view');

                # Charity Admin only
                Route::middleware('charity.admin')->group(function () {
                    # Add new Project
                    Route::get('/add', [ProjectController::class, 'AddProject'])->name('.add');

                    # Store new project
                    Route::post('/store', [ProjectController::class, 'StoreProject'])->name('.store');

                    // Route::get('/edit/1a2267d9-3f39-4ef7-b6aa-5884f6b8e606', function () {
                    //     return view('charity.main.projects.edit');
                    // })->name('.edit');

                    # Edit Project
                    Route::get('/edit/{code}', [ProjectController::class, 'EditProject'])->name('.edit');

                    # Update
                    Route::post('/update/{code}', [ProjectController::class, 'UpdateProject'])->name('.update');


                    # Delete Project
                    Route::get('/delete/{code}', [ProjectController::class, 'DeleteProject'])->name('.delete');
                });

                # Tasks
                Route::name('.tasks')->prefix('/tasks')->group(function () {
                    // Route::get('/all/{code}', [ProjectController::class, 'Alltask'])->name('.all');

                    // Route::get('/c6e9df80-22c6-4829-a2f1-bad342699e7b', function () {
                    //     return view('charity.main.projects.tasks.view');
                    // })->name('.view');
                    Route::get('/view/{code}', [ProjectController::class, 'ViewTask'])->name('.view');

                    Route::post('/store/task/{code}', [ProjectController::class, 'StoreTask'])->name('.store');

                    # Delete Task
                    Route::get('/delete/{code}', [ProjectController::class, 'DeleteTask'])->name('.delete');

                    # Update Task
                    Route::post('/update/task/{code}', [ProjectController::class, 'UpdateTask'])->name('.update');
                });
                // Add Task
                // Edit Task (Assigned_to Only)
                // Delete Task (Charity admin / Assigned_by Only)
            });


            # Users
            Route::name('users')->prefix('/users')->group(function () {

                # View All user
                Route::get('/', [UserController::class, 'AllUser']);

                # Backup  User
                Route::get('/export', [UserController::class, 'BackupUser'])->name('.export');


                # Charity Admins Only
                Route::middleware(['charity.admin'])->group(function () {

                    # Add User
                    Route::get('/add', [UserController::class, 'UnlockUser'])->name('.add');

                    # Store User
                    Route::post('/store', [UserController::class, 'StoreUser'])->name('.store');

                    # Resend Verification Link
                    Route::post('/{code}/resend-link', [UserController::class, 'resendVerificationLink'])->name('.resend');

                    # Delete (Pending Only) User
                    Route::get('/delete/{code}', [UserController::class, 'DeleteUser'])->name('.delete');
                });


                Route::middleware('charity.admin')->group(function () { // Add middleware: Selected account must be pending (account not yet setup)
                    // To add - Route::get() for deleting pending user accounts permanently (non-refundable).
                });

                # View User Detail
                Route::get('/{code}', [UserController::class, 'ViewUserDetail'])->name('.view');
            });

            # Beneficiaries Part 1
            Route::name('beneficiaries')->prefix('/beneficiaries')->group(function () {

                # Retrieve All Beneficiaries of Charitable Organization
                Route::get('/', [BeneficiaryController::class, 'index'])->name('.all');

                # View A Specific Record from Beneficiaries
                Route::get('/view/{beneficiaries:code}', [BeneficiaryController::class, 'show'])->name('.show');

                # Create A Beneficiary Record
                Route::get('/create', [BeneficiaryController::class, 'create'])->name('.create');

                # About to Store the New Beneficiary Record
                Route::post('/store', [BeneficiaryController::class, 'store'])->name('.store');

                # Delete A Beneficiary Record
                Route::get('/delete/{beneficiaries:code}', [BeneficiaryController::class, 'delete'])->name('.delete');

                # Edit: Choose Which Part to Edit from Part1 - Part3
                Route::post('/editPart/{beneficiaries:code}', [BeneficiaryController::class, 'editPart'])
                    ->name('.editPart');

                # Edit: User chose part 1 to edit
                Route::get('/edit/{beneficiaries:code}', [BeneficiaryController::class, 'edit'])->name('.edit');

                # About to Update the Edit Beneficiary Record
                Route::post('/update/{beneficiary:code}', [BeneficiaryController::class, 'update'])->name('.update');
            });

            # Beneficiaries Part 2
            Route::name('beneficiaries2')->prefix('/beneficiaries')->group(function () {

                # Create A Family Info Record To A Beneficiary
                Route::get('/create-part2/{beneficiaries:code}', [Beneficiary2Controller::class, 'createPart2'])
                    ->name('.createPart2');

                # About to Store the New Family Info Record To A Beneficiary
                Route::post('/store-part2/{beneficiaries:code}', [Beneficiary2Controller::class, 'storePart2'])
                    ->name('.storePart2');

                # Delete A New Family Info Record From A Beneficiary
                Route::post('/destroy-part2/{id}/{beneficiary_code}', [Beneficiary2Controller::class, 'destroyPart2'])
                    ->name('.destroyPart2');

                # Retrieve the Family Info that is about to be edited
                Route::post('/update-part2/{id}/{beneficiary_code}', [Beneficiary2Controller::class, 'updatePart2'])
                    ->name('.updatePart2');

                # Edit: User chose part 2 to edit
                Route::get('/edit-part2/{beneficiaries:code}', [Beneficiary2Controller::class, 'editPart2'])
                    ->name('.editPart2');
            });

            # Beneficiaries Part 3
            Route::name('beneficiaries3')->prefix('/beneficiaries')->group(function () {

                # Create The Background Information To A Beneficiary
                Route::get('/create-part3/{beneficiaries:code}', [Beneficiary3Controller::class, 'createPart3'])
                    ->name('.createPart3');

                # About to Store the Background Information Record To A Beneficiary
                Route::post('/store-part3/{beneficiaries:code}', [Beneficiary3Controller::class, 'storePart3'])
                    ->name('.storePart3');

                # Edit: User chose part 3 to edit
                Route::get('/edit-part3/{beneficiaries:code}', [Beneficiary3Controller::class, 'editPart3'])
                    ->name('.editPart3');

                # About to Update the Edit Beneficiary Record
                Route::post('/update-part3/{beneficiary:code}', [Beneficiary3Controller::class, 'update'])->name('.update');


                # Backup Beneficiaries
                Route::get('/export', [Beneficiary3Controller::class, 'BackupBeneficiary'])->name('.export');

                # Export Beneficiaries with PDF
                Route::get('/export/pdf/{code}', [Beneficiary3Controller::class, 'GeneratePDF'])->name('generate.pdf');

                # Export Beneficiaries with PDF (with blank page)
                Route::get('/export/pdf/blank/{code}', [Beneficiary3Controller::class, 'GeneratePDFblank'])->name('generate.pdf.blank');
            });

            # Benefactors
            Route::name('benefactors')->prefix('/benefactors')->group(function () {

                # Retrieve All Benefactors of Charitable Organization
                Route::get('/', [BenefactorController::class, 'index'])->name('.all');

                # View A Specific Record from Benefactors
                Route::get('/view/{benefactors:code}', [BenefactorController::class, 'show'])->name('.view');

                # Create A Benefactor Record
                Route::get('/create', [BenefactorController::class, 'create'])->name('.create');

                # About to Store the New Benefactor Record
                Route::post('/store', [BenefactorController::class, 'store'])->name('.store');

                # Delete A Benefactor Record
                Route::get('/delete/{benefactors:code}', [BenefactorController::class, 'delete'])->name('.delete');

                # Edit A Benefactor Record
                Route::get('/edit/{benefactors:code}', [BenefactorController::class, 'edit'])->name('.edit');

                # About to Update the Edit Benefactor Record
                Route::post('/update/{benefactors:code}', [BenefactorController::class, 'update'])->name('.update');

                # Backup Benefactor
                Route::get('/export', [BenefactorController::class, 'BackupBenefactor'])->name('.export');
            });

            # Volunteers
            Route::name('volunteers')->prefix('/volunteers')->group(function () {

                # Retrieve All Volunteers of Charitable Organization
                Route::get('/', [VolunteerController::class, 'index'])->name('.all');

                # View A Specific Record from Volunteers
                Route::get('/view/{volunteers:code}', [VolunteerController::class, 'show'])->name('.view');

                # Create A Volunteer Record
                Route::get('/create', [VolunteerController::class, 'create'])->name('.create');

                # About to Store the New Volunteer Record
                Route::post('/store', [VolunteerController::class, 'store'])->name('.store');

                # Delete A Volunteer Record
                Route::get('/delete/{volunteers:code}', [VolunteerController::class, 'delete'])->name('.delete');

                # Edit A Volunteer Record
                Route::get('/edit/{volunteers:code}', [VolunteerController::class, 'edit'])->name('.edit');

                # About to Update the Edit Volunteer Record
                Route::post('/update/{volunteers:code}', [VolunteerController::class, 'update'])->name('.update');

                # Backup Volunteer
                Route::get('/export', [VolunteerController::class, 'BackupVolunteer'])->name('.export');
            });
        });

        # Gift Givings
        Route::name('gifts.')->prefix('/gift-givings')->group(function () {

            # Retrieve all Gift Givings of Charitable Organization
            Route::get('/all', [GiftGivingController::class, 'AllGiftGiving'])->name('all');

            # View Gift Giving Details
            Route::get('/view/{code}', [GiftGivingController::class, 'ViewGiftGivingProjectDetail'])->name('view');

            # Add Beneficiary to Gift Giving (via Dropdown)
            Route::post('/store/beneficiaries/{code}', [GiftGivingController::class, 'StoreSelectedBeneficiary'])->name('store.selected.beneficiaries');

            # Add Beneficiary to Gift Giving (via Input Text)
            Route::post('/store/custom/beneficiaries/{code}', [GiftGivingController::class, 'StoreCustomBeneficiary'])->name('store.custom.selected.beneficiaries');

            # Remove Beneficiary from Gift Giving
            Route::get('/delete/beneficiaries/{code}', [GiftGivingController::class, 'DeleteGiftGivingBeneficiaries'])->name('delete.selected.beneficiaries');

            # Generate tickets for a Gift Giving
            Route::get('/generate/ticket/{code}', [GiftGivingController::class, 'GenerateTicket'])->name('generate.ticket');

            # Charity Admin only
            Route::middleware('charity.admin')->group(function () {

                # Create Gift Giving (Form)
                Route::get('/add', [GiftGivingController::class, 'AddGiftGiving'])->name('add');

                # Store new Gift Giving Project
                Route::post('/store', [GiftGivingController::class, 'StoreGiftGiving'])->name('store');

                # (TO DO) Feature Gift Giving
                Route::get('/featured/new/4d4666bb-554d-40b0-9b23-48f653c21e1e', function () { // Add middleware that star tokens must be sufficient
                    return view('charity.main.projects.featured.add');
                })->name('.feature');
            });
        });

        # Audit Logs
        Route::name('audits.')->prefix('/audit-logs')->middleware('charity.admin')->group(function () {
            Route::get('/', [AuditLogController::class, 'AllAuditLogs'])->name('all');
            // Route::get('/139e93ef-7823-406c-8c4f-00294d1e3b64', function () {
            //     return view('charity.audits.view');
            // })->name('view');
        });

        # Star Tokens
        Route::name('star.tokens.')->prefix('/star-tokens')->middleware('charity.admin')->group(function () {

            # View Balance Page
            Route::get('/', [StarTokenController::class, 'index'])->name('balance');

            # View Transaction History or Pending Orders
            Route::get('/history', [StarTokenController::class, 'viewTransactionHistory'])->name('history');

            # View Step-by-step Order Process
            Route::get('/order', [StarTokenController::class, 'order'])->name('order');

            # About To Store A New Order
            Route::post('/store', [StarTokenController::class, 'store'])->name('store');

            # View A Specific Transaction Record
            Route::get('/view/{orders:code}', [StarTokenController::class, 'show'])->name('view');
        });
    });
});


# Admin Public Page
Route::controller(AdminController::class)->group(function () {
    # Login
    Route::get('/admin/login', 'adminLogin')->name('admin.login');

    # Logout
    Route::get('/logout', 'destroy')->name('admin.logout');
});


# Root Admin Group Controller
Route::controller(AdminController::class)->prefix('/admin')->name('admin.')->middleware(['auth', 'verified', 'prevent-back-history', 'admin.only'])->group(function () {

    # Admin Panel
    Route::get('/panel', 'showAdminPanel')->name('panel');

    # Admin Profile
    Route::prefix('/profile')->group(function () {
        Route::get('/', 'showProfile')->name('profile');
        Route::get('/edit', 'editProfile')->name('profile.edit');
        Route::post('/store', 'storeProfile')->name('profile.store');
    });

    # Change Password
    Route::prefix('/password')->group(function () {
        Route::get('/change', 'editPassword')->name('password.change');
        Route::post('/store', 'storePassword')->name('password.store');
    });


    # Charitable Organizations (Verify Profiles)
    Route::name('charities')->prefix('/charitable-organizations')->group(function () {

        # All Charities Organization
        Route::get('/', [CharitableOrganizationController::class, 'AllCharityOrganization'])->name('.all');

        # View Organization Detail
        Route::get('/view/{code}', [CharitableOrganizationController::class, 'ViewCharityOrganization'])->name('.view');

        # Update Profile Settings
        Route::post('/profile/setting/{code}', [CharitableOrganizationController::class, 'CharityProfileSetting'])->name('.profile.update');

        Route::name('.users')->prefix('/users')->group(function () {
            # View Individual Charity User
            Route::get('/{code}', [CharitableOrganizationController::class, 'ViewCharityUserDetail'])->name('.view');

            # Edit Individual Charity User
            Route::get('/edit/{code}', [CharitableOrganizationController::class, 'EditCharityUserDetail'])->name('.edit');

            # To add: (POST) Update User
            Route::post('/edit/{code}', [CharitableOrganizationController::class, 'UpdateCharityUserDetail'])->name('.update');
        });

        # Send Notification in View Charity
        Route::post('/send/notification/{id}', [CharitableOrganizationController::class, 'SendNotification'])->name('.send.notifcation');
    });

    # Star Token Orders
    Route::name('orders')->prefix('/orders')->group(function () {

        # All  Order
        Route::get('/all', [OrderController::class, 'AllOrders'])->name('.all');

        # View Order
        Route::get('/view/{code}', [OrderController::class, 'ViewOrder'])->name('.view');

        # Reject Star Token/Subscription Order
        Route::post('/Reject/{code}', [OrderController::class, 'RejectOrder'])->name('.reject');

        # Approve Star Token/Subscription Order
        Route::get('/Approved/{code}', [OrderController::class, 'ApprovedOrder'])->name('.approved');

        # Delete Confirmed/Rejected Order
        Route::get('/Delete/{code}', [OrderController::class, 'DeleteOrder'])->name('.delete');
    });

    # Featured Projects
    Route::name('feat-projects')->prefix('/featured-projects')->group(function () {

        Route::get('/', [FeaturedProjectController::class, 'AllFeaturedProject'])->name('.all');

        Route::get('/view/{code}', [FeaturedProjectController::class, 'ViewFeaturedProject'])->name('.view');

        # Approve
        Route::get('/Approved/{code}', [FeaturedProjectController::class, 'ApproveFeaturedProject'])->name('.approve');

        # Reject
        Route::post('/Reject/{code}', [FeaturedProjectController::class, 'RejectFeaturedProject'])->name('.reject');
    });

    # Admin User Accounts
    Route::name('users')->prefix('/users')->controller(RootAdminUserController::class)->group(function () {
        Route::get('/', 'allAdminUsers');
        Route::get('/add', 'addAdminUser')->name('.add');
        Route::post('/store', 'storeAdminUser')->name('.store');
        Route::get('/{code}', 'viewAdminUser')->name('.view');
    });

    # Audit Logs
    Route::name('audit-logs')->prefix('/audit-logs')->group(function () {
        Route::get('/', [RootAdminAuditLogController::class, 'viewAllAudits']);
    });
});

# Notifiers
Route::controller(NotifierController::class)->prefix('/admin/notifiers')->middleware(['auth', 'verified', 'prevent-back-history', 'admin.only'])
    ->group(function () {

        # All notifier
        Route::get('/', 'AllNotifier')->name('admin.notifiers');

        # Add notifier
        Route::get('/add',  'AddNotifier')->name('admin.notifiers.add');

        # Store Notifier
        Route::post('/store',  'StoreNotifier')->name('admin.notifiers.store');

        # View Notifier
        Route::get('/view/{id}',  'ViewNotifier')->name('admin.notifiers.view');

        # Edit Notifier
        Route::get('/edit/{id}',  'EditNotifier')->name('admin.notifiers.edit');

        # Update Notifier
        Route::post('/update/{id}', 'UpdateNotifier')->name('admin.notifiers.update');

        # Delete Notifier
        Route::get('/delete/{id}}', 'DeleteNotifier')->name('admin.notifiers.delete');
    });

require __DIR__ . '/auth.php';
