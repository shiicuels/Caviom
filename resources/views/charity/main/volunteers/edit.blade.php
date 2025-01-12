@extends('charity.charity_master')
@section('title', 'Edit Volunteer')
@section('charity')

    @php
        $avatar = 'upload/charitable_org/volunteer_photos/';
        $defaultAvatar = 'upload/charitable_org/volunteer_photos/no_avatar.png';
    @endphp

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <div class="page-content">
        <div class="container-fluid">

            <!-- start page title -->
            <div class="row">
                <div class="col-12">
                    <div class="p-2">
                        <h1 class="mb-0" style="color: #62896d"><strong>EDIT VOLUNTEER</strong></h1>
                        <ol class="breadcrumb m-0 p-0">
                            <li class="breadcrumb-item">Our Charitable Organization</li>
                            <li class="breadcrumb-item"><a href="{{ route('charity.volunteers.all') }}">Volunteers</a></li>
                            <li class="breadcrumb-item active">Edit</li>
                        </ol>

                        @include('charity.modals.volunteers')
                    </div>
                </div>
            </div>
            <!-- end page title -->

            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="p-4">
                                <a href="{{ url()->previous() }}" class="text-link">
                                    <i class="ri-arrow-left-line"></i> Go Back
                                </a>
                            </div>
                            <div class="text-center">
                                <div class="user-profile text-center mt-3">
                                    <div class="">
                                        <img src="{{ (!empty($volunteer->profile_photo))?url($avatar.$volunteer->profile_photo):url($defaultAvatar) }}"
                                             alt="Profile Picture" class="avatar-xl rounded-circle">
                                    </div>
                                    <div class="mt-3">
                                        <p class="text-muted mb-1"><span class="badge bg-light">ID No. {{ $volunteer->id }}</span></p>
                                        <h1 class="py-3" style="color: #62896d">
                                            <strong>
                                                {{ $volunteer->last_name . ', ' . $volunteer->first_name .' '. $volunteer->middle_name}}
                                            </strong>
                                        </h1>
                                    </div>
                                </div>
                            </div>
                            <div class="row px-5">
                                <dl class="row col-lg-6">
                                    <dt class="col-md-6"><h4 class="font-size-15"><strong>Date Added:</strong></h4></dt>
                                    <dt class="col-md-6">{{ Carbon\Carbon::parse($volunteer->created_at)->toFormattedDateString() }}</dt>
                                </dl>
                                <dl class="row col-lg-6">
                                    <dt class="col-md-6"><h4 class="font-size-15"><strong>Last Updated at:</strong></h4></dt>
                                    <dt class="col-md-6">{{ Carbon\Carbon::parse($volunteer->updated_at)->diffForHumans() }}</dt>
                                    <dt class="col-md-6"><h4 class="font-size-15"><strong>Last Updated by:</strong></h4></dt>
                                    <dt class="col-md-6">
                                        <a href="{{ ($volunteer->last_modified_by_id)?route('charity.users.view', $volunteer->lastModifiedBy->code):'#' }}">
                                            {{ ($volunteer->lastModifiedBy)? $volunteer->lastModifiedBy->username:'---' }}
                                        </a>
                                    </dt>
                                </dl>
                                <hr class="my-3">
                                <form method="POST" action="{{ route('charity.volunteers.update',  $volunteer->code) }}" enctype="multipart/form-data" class="form-horizontal">
                                    @csrf
                                    <h4 class="mt-4" style="color: #62896d">Personal Information</h4>

                                    <div class="form-group mb-3 row">
                                        <!-- Profile Photo -->
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="profile_image" class="form-label">
                                                    Profile Photo (Optional)
                                                    <span data-bs-toggle="tooltip" data-bs-placement="bottom" title="Recommended image resolution of 512x512. Must not
                                                    exceed 2mb." data-bs-original-title="yes">
                                                    <i class="mdi mdi-information-outline"></i>
                                                </span>
                                                </label>
                                                <input class="form-control" name="profile_photo" id="profile_photo" type="file" value="{{ old('profile_photo') }}">
                                                @error('profile_photo')
                                                <div class="text-danger">
                                                    {{ $message }}
                                                </div>
                                                @enderror
                                            </div>
                                        </div>

                                        <!-- Email Address -->
                                        <div class="col-md-6">
                                            <label for="email" class="form-label">*Email Address</label>
                                            <input class="form-control" name="email" id="email" value="{{ old('email', $volunteer->email_address) }}" required>
                                            @error('email')
                                            <div class="text-danger">
                                                {{ $message }}
                                            </div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="form-group mb-3 row">
                                        <!-- First Name -->
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="first_name" class="form-label">*First Name</label>
                                                <input type="text" class="form-control" name="first_name" id="first_name" value="{{ old('first_name', $volunteer->first_name) }}" required>
                                                @error('first_name')
                                                <div class="text-danger">
                                                    {{ $message }}
                                                </div>
                                                @enderror
                                            </div>
                                        </div>

                                        <!-- Middle Name -->
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="middle_name" class="form-label">Middle Name</label>
                                                <input type="text" class="form-control" name="middle_name" id="middle_name" value="{{ old('middle_name', $volunteer->middle_name) }}">
                                                @error('middle_name')
                                                <div class="text-danger">
                                                    {{ $message }}
                                                </div>
                                                @enderror
                                            </div>
                                        </div>

                                        <!-- Last Name -->
                                        <div class="col-md-4">
                                            <label for="last_name" class="form-label">*Last Name</label>
                                            <input type="text" class="form-control" name="last_name" id="last_name" value="{{ old('last_name', $volunteer->last_name) }}" required>
                                            @error('last_name')
                                            <div class="text-danger">
                                                {{ $message }}
                                            </div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="form-group mb-3 row">
                                        <!-- Cellphone -->
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="cel_no" class="form-label">Cellphone No.</label>
                                                <input class="form-control" name="cel_no" id="cel_no" type="tel" value="{{ old('cel_no', $volunteer->cel_no) }}"
                                                       placeholder="Ex. 09191234567">
                                                @error('cel_no')
                                                <div class="text-danger">
                                                    {{ $message }}
                                                </div>
                                                @enderror
                                            </div>
                                        </div>

                                        <!-- Telephone -->
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="tel_no" class="form-label">Telephone No.</label>
                                                <input class="form-control" name="tel_no" id="tel_no" type="tel" value="{{ old('tel_no', $volunteer->tel_no) }}"
                                                       placeholder="Ex. 82531234">
                                                @error('tel_no')
                                                <div class="text-danger">
                                                    {{ $message }}
                                                </div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group mb-3 row">
                                        <!-- Category -->
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="category" class="form-label">Category</label>
                                                <input class="form-control" name="category" id="category" type="text" value="{{ old('category', $volunteer->category) }}">
                                                @error('category')
                                                <div class="text-danger"><small>
                                                        {{ $message }}
                                                    </small></div>
                                                @enderror
                                            </div>
                                        </div>

                                        <!-- Label -->
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="label" class="form-label">Label</label>
                                                <input class="form-control" name="label" id="label" type="text" value="{{ old('label', $volunteer->label) }}">
                                                @error('label')
                                                <div class="text-danger"><small>
                                                        {{ $message }}
                                                    </small></div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>

                                    <h4 class="mt-5" style="color: #62896d">Current Address</h4>

                                    <!-- Address Line 1 -->
                                    <div class="form-group mb-3 row">
                                        <div class="col-12">
                                            <label for="address_line_one" class="form-label">*Address Line 1</label>
                                            <input class="form-control" name="address_line_one" id="address_line_one" type="text" value="{{ old('address_line_one', $volunteer->Address->address_line_one) }}" required>
                                            @error('address_line_one')
                                            <div class="text-danger">
                                                {{ $message }}
                                            </div>
                                            @enderror
                                        </div>
                                    </div>

                                    <!-- Address Line 2 -->
                                    <div class="form-group mb-3 row">
                                        <div class="col-12">
                                            <label for="address_line_two" class="form-label">Address Line 2 (Optional)</label>
                                            <input class="form-control" name="address_line_two" id="address_line_two" type="text" value="{{ old('address_line_two', $volunteer->Address->address_line_two) }}">
                                            @error('address_line_two')
                                            <div class="text-danger">
                                                {{ $message }}
                                            </div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="form-group mb-3 row">
                                        <!-- Region -->
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="region" class="form-label">*Region</label>
                                                <input class="form-control" name="region" id="region" type="text" value="{{ old('region', $volunteer->Address->region) }}" required>
                                                @error('region')
                                                <div class="text-danger">
                                                    {{ $message }}
                                                </div>
                                                @enderror
                                            </div>
                                        </div>

                                        <!-- Province -->
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="province" class="form-label">*Province</label>
                                                <input class="form-control" name="province" id="province" type="text" value="{{ old('province', $volunteer->Address->province) }}" required>
                                                @error('province')
                                                <div class="text-danger">
                                                    {{ $message }}
                                                </div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group mb-3 row">
                                        <!-- City -->
                                        <div class="col-md-5">
                                            <div class="form-group">
                                                <label for="city" class="form-label">City</label>
                                                <input class="form-control" name="city" id="city" type="text" value="{{ old('city', $volunteer->Address->city) }}">
                                                @error('city')
                                                <div class="text-danger">
                                                    {{ $message }}
                                                </div>
                                                @enderror
                                            </div>
                                        </div>

                                        <!-- Barangay -->
                                        <div class="col-md-5">
                                            <div class="form-group">
                                                <label for="barangay" class="form-label">Barangay</label>
                                                <input class="form-control" name="barangay" id="barangay" type="text" value="{{ old('barangay', $volunteer->Address->barangay) }}">
                                                @error('barangay')
                                                <div class="text-danger">
                                                    {{ $message }}
                                                </div>
                                                @enderror
                                            </div>
                                        </div>

                                        <!-- Postal Code -->
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label for="postal_code" class="form-label">*Postal Code</label>
                                                <input class="form-control" name="postal_code" id="postal_code" type="text" value="{{ old('postal_code', $volunteer->Address->postal_code) }}" required>
                                                @error('postal_code')
                                                <div class="text-danger">
                                                    {{ $message }}
                                                </div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row p-5">
                                        <ul class="list-inline mb-0 mt-4 float-end">
                                            <input type="submit" class="btn btn-dark btn-rounded w-lg waves-effect waves-light float-end" style="background-color: #62896d;" value="Save">
                                            <a class="btn list-inline-item float-end mx-4" href="{{ url()->previous() }}">Cancel</a>
                                        </ul>
                                    </div>
                                </form>
                            </div>
                        </div><!-- end cardbody -->
                    </div><!-- end card -->
                </div><!-- end col -->
            </div>
            <!-- end row -->
        </div>
    </div>

    <script type="text/javascript">
        $(document).ready(function() {
            $('#profile_image').change(function(e) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    $('#showImage').attr('src', e.target.result);
                }
                reader.readAsDataURL(e.target.files['0']);
            })
        })
    </script>
@endsection
