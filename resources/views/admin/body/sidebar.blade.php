@php
    $avatar = 'upload/avatar_img/'.Auth::user()->profile_image;
    $defaultAvatar = 'upload/avatar_img/no_avatar.png';
    $pending_orders = App\Models\Admin\order::where('status', 'Pending')->count();
    $pending_projects = App\Models\Admin\FeaturedProject::where('approval_status', 'Pending')->count();
    $pending_orgs = App\Models\CharitableOrganization::where('verification_status', 'Pending')->count();
@endphp

<div class="vertical-menu" style="background-color: #3c4661;">

    <div data-simplebar class="h-100">

        <!-- User details -->
        <div class="user-profile text-center mt-4">
            <div>
                <img src="{{ Auth::user()->profile_image ? url($avatar):url($defaultAvatar) }}"
                    alt="Profile Picture" class="rounded-circle me-2" width="100" data-holder-rendered="true">
            </div>
            <div class="mt-3">
                <h4 class="font-size-16 mb-1">ID No. {{ Auth::user()->info->organizational_id_no }}</h4>
                <span class="badge bg-secondary">{{ Auth::user()->role }}</span>
            </div>
        </div>

        <!--- Sidemenu -->
        <div id="sidebar-menu">
            <!-- Left Menu Start -->
            <ul class="metismenu list-unstyled" id="side-menu">

                <li class="menu-title">Home</li>

                <li>
                    <a href="{{ route('admin.panel') }}" class="waves-effect">
                        <i class="ri-home-4-line"></i>
                        <span>Admin Panel</span>
                    </a>
                </li>

                <li class="menu-title">Menu</li>

                <li class="{{ Request::routeIs('admin.charities*')?'mm-active':'' }}">
                    <a href="{{route('admin.charities.all')}}" class="waves-effect">
                        <i class="ri-bank-line"></i>
                        <span>Charitable Orgs</span>
                        @unless($pending_orgs == 0)
                        <span class="badge rounded-pill bg-warning float-end">{{$pending_orgs>99?'99+':$pending_orgs}}</span>
                        @endunless
                    </a>
                </li>

                <li class="{{ Request::routeIs('admin.orders*')?'mm-active':'' }}">
                    <a href="{{route('admin.orders.all')}}" class="waves-effect">
                        <i class="ri-shopping-cart-2-line"></i>
                        <span>Star Token Orders</span>
                        @unless($pending_orders == 0)
                        <span class="badge rounded-pill bg-danger float-end">{{$pending_orders>99?'99+':$pending_orders}}</span>
                        @endunless
                    </a>
                </li>

                <li class="{{ Request::routeIs('admin.feat-projects*')?'mm-active':'' }}">
                    <a href="{{ route('admin.feat-projects.all') }}" class="waves-effect">
                        <i class="ri-heart-add-line"></i>
                        <span>Featured Projects</span>
                        @unless($pending_projects == 0)
                        <span class="badge rounded-pill bg-danger float-end">{{$pending_projects>99?'99+':$pending_projects}}</span>
                        @endunless
                    </a>
                </li>

                <li class="{{ Request::routeIs('admin.users*')?'mm-active':'' }}">
                    <a href="{{ route('admin.users') }}" class="waves-effect">
                        <i class="ri-admin-line"></i>
                        <span>Admin User Accounts</span>
                    </a>
                </li>

                <li class="{{ Request::routeIs('admin.audit-logs*')?'mm-active':'' }}">
                    <a href="{{ route('admin.audit-logs') }}" class="waves-effect">
                        <i class="ri-file-search-line"></i>
                        <span>Audit Logs</span>
                    </a>
                </li>

                <li class="{{ Request::routeIs('admin.notifiers*')?'mm-active':'' }}">
                    <a href="{{ Route('admin.notifiers') }}" class="waves-effect">
                        <i class="ri-notification-2-line"></i>
                        <span>Notifiers</span>
                    </a>
                </li>


            </ul>
        </div>
        <!-- Sidebar -->
    </div>
</div>