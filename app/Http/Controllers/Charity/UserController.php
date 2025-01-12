<?php

namespace App\Http\Controllers\Charity;

use App\Http\Controllers\Controller;
use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\AuditLog;
use App\Models\CharitableOrganization;
use App\Models\UserInfo;
use App\Models\Notification;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rules;
use Illuminate\Support\Str;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

# For Generating Excel
use App\Exports\UsersExport;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class UserController extends Controller
{
    public function AllUser()
    {
        $Users = User::where('charitable_organization_id', Auth::user()->charity->id)->get();

        return view('charity.main.users.all', compact('Users'));
    }

    public function UnlockUser()
    {
        # Checks if user has at least 1500 Star Tokens
        if (Auth::user()->charity->star_tokens < 1500) {
            $toastr = array(
                'message' => 'Sorry, your Charitable Organization does not have sufficient Star Tokens.',
                'alert-type' => 'error'
            );

            return redirect()->back()->with($toastr);
        } else {
            return view('charity.main.users.add');
        }
    }

    public function StoreUser(Request $request)
    {
        $request->validate([
            'role' => ['required', Rule::in(['Charity Admin', 'Charity Associate'])],
        ], [
            // 'role.regex' => 'The role must only be either Charity Admin or Charity Associate',
        ]);


        # Retrieve role then set the cost for validation of Star Tokens
        $cost = 0;
        if ($request->role == 'Charity Admin') {
            $cost = 2000;
        } elseif ($request->role == 'Charity Associate') {
            $cost = 1500;
        } else {
            $toastr = array(
                'message' => 'The User Role must only be either Charity Admin or Charity Associate.',
                'alert-type' => 'error'
            );
            return redirect()->back()->with($toastr);
        }

        # Check if Charity has sufficient Star Tokens with the chosen role
        if (Auth::user()->charity->star_tokens < $cost) {
            $toastr = array(
                'message' => 'Sorry, your Charitable Organization does not have sufficient Star Tokens.',
                'alert-type' => 'error'
            );

            return redirect()->back()->with($toastr);
        }


        # Validation of Form
        $request->validate([

            # For user table fields
            'profile_image' => ['nullable', 'mimes:jpg,png,jpeg', 'max:2048', 'file'],
            'email' => ['required', 'string', 'email:rfc,dns', 'max:255', 'unique:users'],
            'username' => ['required', 'alpha_dash', 'string', 'min:6', 'max:20', 'unique:users'],
            'password' => ['required', 'max:20', Rules\Password::defaults()],
            'confirm_password' => ['required', 'same:password'],

            # For user info table fields
            'organizational_id_no' =>  ['nullable', 'integer', 'numeric', 'min:100', 'max:9999999999', 'unique:user_infos'],
            'first_name' => ['required', 'string', 'min:2', 'max:64', 'regex:/^[a-zA-Z ñ,-.\']*$/'],
            'last_name' => ['required', 'string', 'min:2', 'max:64', 'regex:/^[a-zA-Z ñ,-.\']*$/'],
            'middle_name' => ['nullable', 'string', 'min:1', 'max:64', 'regex:/^[a-zA-Z ñ,-.\']*$/'],
            'work_position' => ['required', 'string', 'min:2', 'max:64', 'regex:/^[a-zA-Z ñ,-.\']*$/'],
            'cel_no' => ['required', 'regex:/(09)[0-9]{9}/'],
            'tel_no' => ['nullable', 'regex:/(8)[0-9]{7}/'],

            # For address table fields
            'address_line_one' => ['required', 'string', 'min:5', 'max:128'],
            'address_line_two' => ['nullable', 'string', 'min:5', 'max:128'],
            'province' => ['required', 'string', 'min:3', 'max:64'],
            'city' => ['required', 'string', 'min:3', 'max:64'],
            'barangay' => ['required', 'string', 'min:3', 'max:64'],
            'postal_code' =>  ['required', 'integer', 'digits:4'],

        ], [
            'profile_image.max' => 'Your profile picture must not exceed the file size of 2mb.',
            'first_name.regex' => 'The first name field must not include number/s.',
            'middle_name.regex' => 'The middle name field must not include number/s.',
            'work_position.regex' => 'Work position must not include number(s) or must be a valid format.',
            'last_name.regex' => 'The last name field must not include number/s.',
            'cel_no.regex' => 'The cel no format must be followed. Ex. 09981234567',
            'tel_no.regex' => 'The tel no format must be followed. Ex. 82531234',
        ]);

        # Store Data to address table
        $address = new Address;
        $address->type = 'Present';
        $address->address_line_one = $request->address_line_one;
        $address->address_line_two = $request->address_line_two;
        $address->region = $request->region;
        $address->province = $request->province;
        $address->city = $request->city;
        $address->postal_code = $request->postal_code;
        $address->barangay = $request->barangay;
        $address->created_at = Carbon::now();
        $address->save();


        # Store Data to users table
        $user = new User;
        $user->code = Str::uuid()->toString();
        $user->email = $request->email;
        $user->username = $request->username;
        $user->password = Hash::make($request->password);
        $user->role = $request->role;
        $user->charitable_organization_id = Auth::user()->charitable_organization_id;
        $user->status = 'Pending Unlock';

        # Insert Profile Picture
        if ($request->file('profile_image')) {
            $file = $request->file('profile_image');
            $filename = date('YmdHi') . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('upload/avatar_img/'), $filename);
            $user->profile_image = $filename;
        }

        # Store Data to Users table (Continued)
        $user->created_at = Carbon::now();
        $user->save();

        # Store Data to User Infos table
        $user_info = new UserInfo;
        $user_info->user_id = $user->id;
        $user_info->first_name = $request->first_name;
        $user_info->middle_name = $request->middle_name;
        $user_info->last_name = $request->last_name;
        $user_info->cel_no = $request->cel_no;
        $user_info->tel_no = $request->tel_no;
        $user_info->work_position = $request->work_position;

        # Auto-generate an organizational_id_no if it was not provided in the form
        if (!$request->organizational_id_no) {
            $user_info->organizational_id_no = $this->generateIdNo();
        } else {
            $user_info->organizational_id_no = $request->organizational_id_no;
        }

        # Store Data to User Infos Table (Continued)
        $user_info->address_id = $address->id;
        $user_info->updated_at = Carbon::now();
        $user_info->save();


        # Star Token Deduction
        $current_bal = CharitableOrganization::findOrFail(Auth::user()->charitable_organization_id);
        $current_bal->star_tokens = $current_bal->star_tokens - $cost;
        $current_bal->save();

        # Create a New Event (registration) where an email verification will be sent. (TEMPORARILY REMOVED)
        // event(new Registered($user));

        # Send Notification
        $users = User::where('charitable_organization_id', Auth::user()->charitable_organization_id)->where('status', 'Active')->get();
        foreach ($users as $item) {
            $notif = new Notification;
            $notif->code = Str::uuid()->toString();
            $notif->user_id = $item->id;
            $notif->category = 'User';
            $notif->subject = 'Unlocked Account';
            $notif->message = 'A new pending ' . Auth::user()->role . ' account [' . $user->email . '] has been added by [' .
                Auth::user()->info->first_name . ' ' . Auth::user()->info->last_name . '] using ' . $cost . ' Star Tokens.';
            $notif->icon = 'mdi mdi-account-plus';
            $notif->color = 'success';
            $notif->created_at = Carbon::now();
            $notif->save();
        }

        # Create Audit Logs
        $log = new AuditLog;
        $log->user_id = Auth::user()->id;
        $log->action_type = 'UNLOCK ACCOUNT';
        $log->charitable_organization_id = Auth::user()->charitable_organization_id;
        $log->table_name = 'User, UserInfo, Address';
        $log->record_id = $user->code;
        $log->action = 'Charity Admin unlocked a new ' . $request->role . ' account [' . $request->first_name . ' ' . $request->last_name . '] using ' . $cost . ' Star Tokens.';
        $log->performed_at = Carbon::now();
        $log->save();


        # Success Toastr Message display
        $toastr = array(
            'message' => 'The Pending User has been successfully created.',
            'alert-type' => 'success'
        );

        return redirect()->route('charity.users')->with($toastr);
    }

    private function generateIdNo()
    {
        $id_no_exist = true;
        $id_no = null;
        while ($id_no_exist) {
            $id_no = Carbon::now()->format('Y') . substr(hexdec(uniqid()), 0, 6);   // ID No. = Current year (YYYY) + Random 6 numbers
            $user_found = UserInfo::where('organizational_id_no', $id_no)->first(); // Generated ID No. must not yet exist in the DB
            if (!$user_found) {
                return $id_no;
                $id_no_exist = false; // Ends the loop if the Generated ID No. is already unique.
            }
        }
    }

    public function ViewUserDetail($code)
    {
        $User = User::where('code', $code)->firstOrFail();

        # Retrieved User must be in the same charitable org; Else, return back with error message
        if (($User->charitable_organization_id == Auth::user()->charitable_organization_id)) {
            return view('charity.main.users.view', compact('User'));
        } else {
            $toastr = array(
                'message' => 'Sorry, Users can only access their own charity records..',
                'alert-type' => 'error'
            );

            return redirect()->back()->with($toastr);
        }

        return view('charity.main.users.view');
    }

    public function DeleteUser($code)
    {
        # Retrieve first the selected Pending User
        $User = User::where('code', $code)->firstOrFail();

        # User status must be pending or inactive to delete
        if (!($User->status == "Pending Unlock" || $User->status == "Inactive")) {

            $toastr = array(
                'message' => 'Sorry, only pending or inactive users accounts can be deleted.',
                'alert-type' => 'error'
            );

            return redirect()->back()->with($toastr);
        }

        # Retrieved User must be in the same charitable org; Else, return back with error message
        if (!$User->charitable_organization_id == Auth::user()->charitable_organization_id) {
            $toastr = array(
                'message' => 'Sorry, Users can only access their own charity records..',
                'alert-type' => 'error'
            );

            return redirect()->back()->with($toastr);
        }

        # Delete old Profile Image if exists
        $oldImg = $User->profile_image;
        if ($oldImg) unlink(public_path('upload/avatar_img/') . $oldImg);

        # Temporarily Store address_id, firstname, email, and lastname before deleting their records in: Address, User, and User info records
        $address_id = $User->info->address_id;
        $fname = $User->info->first_name;
        $lname = $User->info->last_name;
        $email = $User->email;
        $User->delete();
        Address::findOrFail($address_id)->delete();


        # Send Notification
        $users = User::where('charitable_organization_id', Auth::user()->charitable_organization_id)->where('status', 'Active')->get();
        foreach ($users as $user) {
            $notif = new Notification;
            $notif->code = Str::uuid()->toString();
            $notif->user_id = $user->id;
            $notif->category = 'User';
            $notif->subject = 'Removed Pending Account';
            $notif->message = 'The pending ' . Auth::user()->role . ' account [' . $email . '] has been deleted permanently.';
            $notif->icon = 'mdi mdi-account-minus';
            $notif->color = 'danger';
            $notif->created_at = Carbon::now();
            $notif->save();
        }

        # Create Audit Logs
        $log = new AuditLog;
        $log->user_id = Auth::user()->id;
        $log->action_type = 'DELETE';
        $log->charitable_organization_id = Auth::user()->charitable_organization_id;
        $log->table_name = 'User, UserInfo, Address';
        $log->record_id = $code;
        $log->action = 'Charity Admin deleted Pending User [' . $fname . ' ' . $lname . '] permanently.';
        $log->performed_at = Carbon::now();
        $log->save();

        $toastr = array(
            'message' => 'Selected Pending User Account has been removed successfully.',
            'alert-type' => 'success'
        );


        return to_route('charity.users')->with($toastr);
    }

    public function BackupUser()
    {
        # Send Notification to each user in their Charitable Organizations
        $users = User::where('charitable_organization_id', Auth::user()->charitable_organization_id)->where('status', 'Active')->get();
        foreach ($users as $user) {
            $notif = new Notification;
            $notif->code = Str::uuid()->toString();
            $notif->user_id = $user->id;
            $notif->category = 'User';
            $notif->subject = 'Backup Users';
            $notif->message = Auth::user()->role . ' [' . Auth::user()->info->first_name . ' ' . Auth::user()->info->last_name .
                '] has attempted to back up a copy of Users from [' . Auth::user()->charity->name . '] into an Excel File.';
            $notif->icon = 'mdi mdi-file-download';
            $notif->color = 'warning';
            $notif->created_at = Carbon::now();
            $notif->save();
        }

        # Create Audit Logs
        $log = new AuditLog;
        $log->user_id = Auth::user()->id;
        $log->action_type = 'GENERATE EXCEL';
        $log->charitable_organization_id = Auth::user()->charitable_organization_id;
        $log->table_name = 'User, UserInfo, Address';
        $log->record_id = null;
        $log->action = Auth::user()->role . ' generated Excel to backup all Users in ' . Auth::user()->charity->name;
        $log->performed_at = Carbon::now();
        $log->save();


        return Excel::download(new UsersExport, Auth::user()->charity->name . ' - Users.xlsx');
    }

    public function resendVerificationLink(Request $request, $code)
    {
        $user = User::where('code', $code)->firstOrFail();

        if ($user->hasVerifiedEmail()) {

            $toastr = array(
                'message' => 'Selected Pending User Account has already been verified.',
                'alert-type' => 'warning'
            );

            return back()->with($toastr);
        }

        $user->sendEmailVerificationNotification();

        $toastr = array(
            'message' => 'A verification link has been sent to this user\'s email address.',
            'alert-type' => 'success'
        );

        return back()->with($toastr);
    }
}
