<?php

namespace App\Http\Controllers\Charity\PublicProfile;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\AuditLog;
use App\Models\CharitableOrganization;
use App\Models\Charity\Profile\ProfileCoverPhoto;
use App\Models\Charity\Profile\ProfilePrimaryInfo;
use App\Models\Charity\Profile\ProfileSecondaryInfo;
use App\Models\Charity\Profile\ProfileAward;
use App\Models\Charity\Profile\ProfileModeOfDonation;
use App\Models\Charity\Profile\ProfileProgram;
use App\Models\Charity\Profile\ProfileRequirement;
use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class ProfileController extends Controller
{
    public function showProfileIndex()
    {
        return view('charity.main.profile.index');
    }
    public function setupProfile()
    {
        $primaryInfo = ProfilePrimaryInfo::where('charitable_organization_id', Auth::user()->charitable_organization_id)->first();
        $secondaryInfo = ProfileSecondaryInfo::where('charitable_organization_id', Auth::user()->charitable_organization_id)->first();
        $awards = ProfileAward::where('charitable_organization_id', Auth::user()->charitable_organization_id)->take(5)->get();
        $programs = ProfileProgram::where('charitable_organization_id', Auth::user()->charitable_organization_id)->take(5)->get();
        $donationModes = ProfileModeOfDonation::where('charitable_organization_id', Auth::user()->charitable_organization_id)->take(5)->get();

        return view('charity.main.profile.setup', compact(['primaryInfo', 'secondaryInfo', 'awards', 'programs', 'donationModes']));
    }
    public function dropZoneCoverPhotos(Request $request)
    {
        # Get existing cover photos from DB
        $existing_photos = ProfileCoverPhoto::where('charitable_organization_id', Auth::user()->charitable_organization_id)->get();

        # Only add if not exceeding 5
        if ($existing_photos->count() < 5) {
            # Upload files to folder of cover photos
            $image = $request->file('file');
            $imageName = 'cover_photo_' . date('YmdHi') . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('upload/charitable_org/cover_photos/'), $imageName);

            # Update DB with the filename of cover photo
            $cover_photo = new ProfileCoverPhoto;
            $cover_photo->charitable_organization_id = Auth::user()->charitable_organization_id;
            $cover_photo->file_name = $imageName;
            $cover_photo->updated_at = Carbon::now();
            $cover_photo->save();
            return response()->json(['success' => $imageName]);
        }

        return response()->json(['success' => 'ERROR: Max of 5 photos has been reached.']);
    }
    public function getImages()
    {
        # Get existing cover photos from DB and display them on upload modal.
        $images = ProfileCoverPhoto::where('charitable_organization_id', Auth::user()->charitable_organization_id)->take(5)->get()->toArray();
        foreach ($images as $image) {
            $tableImages[] = $image['file_name'];
        }
        $storeFolder = public_path('upload/charitable_org/cover_photos');
        $file_path = public_path('upload/charitable_org/cover_photos/');
        $files = scandir($storeFolder);
        foreach ($files as $file) {
            if ($file != '.' && $file != '..' && in_array($file, $tableImages)) {
                $obj['name'] = $file;
                $file_path = public_path('upload/charitable_org/cover_photos/') . $file;
                $obj['size'] = filesize($file_path);
                $obj['path'] = url('upload/charitable_org/cover_photos/' . $file);
                $data[] = $obj;
            }
        }
        return response()->json($data);
    }
    public function destroy(Request $request)
    {
        $filename =  $request->get('filename');
        ProfileCoverPhoto::where('file_name', $filename)->delete();
        $path = public_path('upload/charitable_org/cover_photos/') . $filename;
        if (file_exists($path)) {
            unlink($path);
        }
        return response()->json(['success' => $filename]);
    }
    public function storePrimaryInfo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            # Basic Info
            'profile_photo' => ['nullable', 'mimes:jpg,png,jpeg', 'max:2048', 'file'],
            'category' => ['required', Rule::in(['Community Development', 'Education', 'Humanities', 'Health', 'Environment', 'Social Welfare', 'Corporate', 'Church', 'Livelihood', 'Sports Volunteerism'])],
            'tagline' => ['nullable', 'string', 'max:200'],
            'email' => ['required', 'string', 'email:rfc,dns', 'max:100'],
            'cel_no' => ['required', 'regex:/(09)[0-9]{9}/'], // 09 + (Any 9-digit number from 1-9)
            'tel_no' => ['nullable', 'regex:/(8)[0-9]{7}/'], // 8 + (Any 7-digit number from 1-9)

            # Address
            'address_line_one' => ['required', 'string', 'min:5', 'max:128'],
            'address_line_two' => ['nullable', 'string', 'min:5', 'max:128'],
            'province' => ['required', 'string', 'min:3', 'max:64'],
            'region' => ['required', 'string', 'min:3', 'max:64'],
            'city' => ['required', 'string', 'min:3', 'max:64'],
            'barangay' => ['required', 'string', 'min:3', 'max:64'],
            'postal_code' => ['required', 'integer', 'digits:4'],
        ], [
            'profile_photo.dimensions' => 'Profile photo of the Organization must be 1x1 in ratio.',
            'category.array' => 'Invalid category.',
            'cel_no.regex' => 'The cel no format must be followed. Ex. 09981234567',
            'tel_no.regex' => 'The tel no format must be followed. Ex. 82531234',
        ]);

        # Return error toastr if validate request failed
        if ($validator->fails()) {

            $toastr = array(
                'message' => $validator->errors()->first() . ' Please try again.',
                'alert-type' => 'error'
            );

            return redirect()->back()->withInput()->withErrors($validator->errors())->with($toastr);
        }

        # Retrieve Charity Data from DB
        $charity = CharitableOrganization::findOrFail(Auth::user()->charitable_organization_id);

        # Do this only when input field: profile_photo has a value
        if ($request->file('profile_photo')) {

            # Delete old Profile photo if exists
            $oldImg = $charity->profile_photo;
            if ($oldImg) unlink(public_path('upload/charitable_org/profile_photo/') . $oldImg);

            # Upload profile photo of the Charitable Organization to the server
            $file = $request->file('profile_photo');
            $filename = Str::limit($charity->name, 50, '') . ' - ' . date('YmdHi') . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('upload/charitable_org/profile_photo/'), $filename);

            # Link the profile photo to the Charity Data by updating DB
            $charity->profile_photo = $filename;
            $charity->save();
        }

        # Check if the Charity already has an existing address in profile_primary_info in DB.
        $profile_exists = ProfilePrimaryInfo::where('charitable_organization_id', $charity->id)->first();

        if ($profile_exists) {

            # Update Address
            $address = Address::find($profile_exists->address_id);
            $address->address_line_one = $request->address_line_one;
            $address->address_line_two = $request->address_line_two;
            $address->region = $request->region;
            $address->province = $request->province;
            $address->city = $request->city;
            $address->postal_code = $request->postal_code;
            $address->barangay = $request->barangay;
            $address->update();

            # Update Primary Info
            $profile_exists->update([
                'charitable_organization_id' => Auth::user()->charitable_organization_id,
                'category' => $request->category,
                'tagline' => $request->tagline,
                'email_address' => $request->email,
                'cel_no' => $request->cel_no,
                'tel_no' => $request->tel_no,
                'updated_at' => Carbon::now(),
            ]);

            # Audit Logs (Update)
            AuditLog::create([
                'user_id' => Auth::id(),
                'action_type' => 'UPDATE',
                'charitable_organization_id' => Auth::user()->charitable_organization_id,
                'table_name' => 'Profile Primary Info, Address',
                'record_id' => Auth::user()->charity->code,
                'action' => Auth::user()->role . ' updated ' . Auth::user()->charity->name . '\'s Public Profile primary information.',
                'performed_at' => Carbon::now(),
            ]);
        } else {

            # Create New Data to addresses table
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

            # Create New Profile Primary Info
            ProfilePrimaryInfo::create([
                'charitable_organization_id' => Auth::user()->charitable_organization_id,
                'address_id' => $address->id,
                'category' => $request->category,
                'tagline' => $request->tagline,
                'email_address' => $request->email,
                'cel_no' => $request->cel_no,
                'tel_no' => $request->tel_no,
                'updated_at' => Carbon::now(),
            ]);

            # Audit Logs (Insert)
            AuditLog::create([
                'user_id' => Auth::id(),
                'action_type' => 'INSERT',
                'charitable_organization_id' => Auth::user()->charitable_organization_id,
                'table_name' => 'Profile Primary Info, Address',
                'record_id' => Auth::user()->charity->code,
                'action' => Auth::user()->role . ' has set up ' . Auth::user()->charity->name . '\'s Public Profile primary information.',
                'performed_at' => Carbon::now(),
            ]);
        }

        # Throw success toastr
        $toastr = array(
            'message' => 'Profile has been updated successfully.',
            'alert-type' => 'success'
        );

        return redirect()->back()->withInput()->with($toastr);
    }
    public function storeAwards(Request $request)
    {
        $validator = Validator::make($request->all(), [
            # Awards
            'award_name' => ['required', 'string', 'max:200', 'min:10'],
            'file_link' => ['nullable', 'url', 'max:250', 'min:10'],
        ]);

        # Return error toastr if validate request failed
        if ($validator->fails()) {

            $toastr = array(
                'message' => $validator->errors()->first() . ' Please try again.',
                'alert-type' => 'error'
            );

            return redirect()->back()->withInput()->withErrors($validator->errors())->with($toastr);
        }

        # Return an error toastr if equal to or more than 5 awards have been added already.
        $awards = ProfileAward::where('charitable_organization_id', Auth::user()->charitable_organization_id)->get();
        if ($awards->count() >= 5) {
            $toastr = array(
                'message' => 'Only a maximum of 5 awards can be added.',
                'alert-type' => 'error'
            );

            return redirect()->back()->withInput()->withErrors($validator->errors())->with($toastr);
        }

        ProfileAward::create([
            'charitable_organization_id' => Auth::user()->charitable_organization_id,
            'name' => $request->award_name,
            'file_link' => $request->file_link,
        ]);

        $toastr = array(
            'message' => 'Award has been added successfully.',
            'alert-type' => 'success'
        );
        return redirect()->back()->with($toastr);
    }
    public function destroyAward($id)
    {
        $award = ProfileAward::findOrFail($id);
        if ($award->charitable_organization_id != Auth::user()->charitable_organization_id) {
            $toastr = array(
                'message' => 'You can only remove your own Charitable Organization\'s pre-existing award(s).',
                'alert-type' => 'error'
            );

            return redirect()->back()->withInput()->with($toastr);
        }

        $award->delete();

        $toastr = array(
            'message' => 'Selected award has been removed successfully.',
            'alert-type' => 'success'
        );
        return redirect()->back()->withInput()->with($toastr);
    }
    public function storeSecondaryInfo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            # Secondary Info
            'our_story' => ['required', 'string', 'max:1300', 'min:10'],
            'our_story_photo' => ['nullable', 'mimes:jpg,png,jpeg', 'max:2048', 'file'],
            'our_goal' => ['required', 'string', 'max:1300', 'min:10'],
            'our_goal_photo' => ['nullable', 'mimes:jpg,png,jpeg', 'max:2048', 'file'],

        ]);

        # Return error toastr if validate request failed
        if ($validator->fails()) {

            $toastr = array(
                'message' => $validator->errors()->first() . ' Please try again.',
                'alert-type' => 'error'
            );

            return redirect()->back()->withInput()->withErrors($validator->errors())->with($toastr);
        }

        # Check if the Charity already has an existing address in profile_secondary_info in DB.
        $profile_secondary_exists = ProfileSecondaryInfo::where('charitable_organization_id', Auth::user()->charitable_organization_id)->first();

        if ($profile_secondary_exists) {
            # Start Updating Database
            $profile_secondary_exists->charitable_organization_id = Auth::user()->charitable_organization_id;
            $profile_secondary_exists->our_story = $request->our_story;
            $profile_secondary_exists->our_goal = $request->our_goal;

            # Upload the image only when our_story_photo has value...
            if ($request->file('our_story_photo')) {

                # Delete old Story photo if exists
                $oldImg = $profile_secondary_exists->our_story_photo;
                if ($oldImg) unlink(public_path('upload/charitable_org/our_story/') . $oldImg);

                # Upload New Story photo to the server
                $file = $request->file('our_story_photo');
                $filename = 'Our_story_' . date('YmdHi') . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('upload/charitable_org/our_story/'), $filename);

                # Link the story photo to the Profile by updating DB
                $profile_secondary_exists->our_story_photo = $filename;
            }

            # Upload the image only when our_goal_photo has value...
            if ($request->file('our_goal_photo')) {

                # Delete old Story photo if exists
                $oldImg = $profile_secondary_exists->our_goal_photo;
                if ($oldImg) unlink(public_path('upload/charitable_org/our_goal/') . $oldImg);

                # Upload profile photo of the Charitable Organization to the server
                $file = $request->file('our_goal_photo');
                $filename = 'Our_goal_' . date('YmdHi') . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('upload/charitable_org/our_goal/'), $filename);

                # Link the profile photo to the Charity Data by updating DB
                $profile_secondary_exists->our_goal_photo = $filename;
            }

            $profile_secondary_exists->updated_at = Carbon::now();
            $profile_secondary_exists->save();

            # Audit Logs (Update)
            AuditLog::create([
                'user_id' => Auth::id(),
                'action_type' => 'UPDATE',
                'charitable_organization_id' => Auth::user()->charitable_organization_id,
                'table_name' => 'Profile Secondary Info',
                'record_id' => Auth::user()->charity->code,
                'action' => Auth::user()->role . ' updated ' . Auth::user()->charity->name . '\'s Public Profile secondary information.',
                'performed_at' => Carbon::now(),
            ]);
        } else {

            # Start Creating New Record in the Database
            $secondaryInfo = new ProfileSecondaryInfo;
            $secondaryInfo->charitable_organization_id = Auth::user()->charitable_organization_id;
            $secondaryInfo->our_story = $request->our_story;
            $secondaryInfo->our_goal = $request->our_goal;

            # Upload the image only when our_story_photo has value...
            if ($request->file('our_story_photo')) {

                # Upload profile photo of the Charitable Organization to the server
                $file = $request->file('our_story_photo');
                $filename = 'Our_story_' . date('YmdHi') . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('upload/charitable_org/our_story/'), $filename);

                # Link the profile photo to the Charity Data by updating DB
                $secondaryInfo->our_story_photo = $filename;
            }

            # Upload the image only when our_goal_photo has value...
            if ($request->file('our_goal_photo')) {

                # Upload profile photo of the Charitable Organization to the server
                $file = $request->file('our_goal_photo');
                $filename = 'Our_goal_' . date('YmdHi') . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('upload/charitable_org/our_goal/'), $filename);

                # Link the profile photo to the Charity Data by updating DB
                $secondaryInfo->our_goal_photo = $filename;
            }

            $secondaryInfo->updated_at = Carbon::now();
            $secondaryInfo->save();

            # Audit Logs (Update)
            AuditLog::create([
                'user_id' => Auth::id(),
                'action_type' => 'UPDATE',
                'charitable_organization_id' => Auth::user()->charitable_organization_id,
                'table_name' => 'Profile Secondary Info',
                'record_id' => Auth::user()->charity->code,
                'action' => Auth::user()->role . ' has set up ' . Auth::user()->charity->name . '\'s Public Profile secondary information.',
                'performed_at' => Carbon::now(),
            ]);
        }

        # Throw success toastr
        $toastr = array(
            'message' => 'Profile (Secondary Information) has been updated successfully.',
            'alert-type' => 'success'
        );

        return redirect()->back()->with($toastr);
    }
    public function storePrograms(Request $request)
    {
        $validator = Validator::make($request->all(), [
            # Programs
            'program_name' => ['required', 'string', 'max:200', 'min:4'],
            'program_photo' => ['nullable', 'mimes:jpg,png,jpeg', 'max:2048', 'file'],
            'program_description' => ['required', 'string', 'max:1300', 'min:10'],
        ]);

        # Return error toastr if validate request failed
        if ($validator->fails()) {
            $toastr = array(
                'message' => $validator->errors()->first() . ' Please try again.',
                'alert-type' => 'error'
            );
            return redirect()->back()->withInput()->withErrors($validator->errors())->with($toastr);
        }

        # Return an error toastr if equal to or more than 5 programs have been added already.
        $programs = ProfileProgram::where('charitable_organization_id', Auth::user()->charitable_organization_id)->get();
        if ($programs->count() >= 5) {
            $toastr = array(
                'message' => 'Only a maximum of 5 programs / activities can be added.',
                'alert-type' => 'error'
            );

            return redirect()->back()->withInput()->withErrors($validator->errors())->with($toastr);
        }

        # Start Creating records in DB
        $program = new ProfileProgram;
        $program->charitable_organization_id = Auth::user()->charitable_organization_id;
        $program->name = $request->program_name;
        $program->description = $request->program_description;

        # Upload the image only when our_story_photo has value...
        if ($request->file('program_photo')) {

            # Upload profile photo of the Charitable Organization to the server
            $file = $request->file('program_photo');
            $filename = 'program_' . date('YmdHi') . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('upload/charitable_org/programs/'), $filename);

            # Link the profile photo to the Charity Data by updating DB
            $program->program_photo = $filename;
        }

        $program->created_at = Carbon::now();
        $program->save();

        # Throw success toastr
        $toastr = array(
            'message' => 'Program / Activity has been added successfully.',
            'alert-type' => 'success'
        );

        return redirect()->back()->with($toastr);
    }
    public function destroyProgram($id)
    {
        $program = ProfileProgram::findOrFail($id);
        if ($program->charitable_organization_id != Auth::user()->charitable_organization_id) {
            $toastr = array(
                'message' => 'You can only remove your own Charitable Organization\'s pre-existing program(s).',
                'alert-type' => 'error'
            );

            return redirect()->back()->withInput()->with($toastr);
        }

        # Prevent the user from deleting the program if only one left is remaining.
        $programs = ProfileProgram::where('charitable_organization_id', Auth::user()->charitable_organization_id)->get();
        if ($programs->count() == 1) {
            $toastr = array(
                'message' => 'A minimum of one (1) program / activity is required.',
                'alert-type' => 'error'
            );

            return redirect()->back()->withInput()->with($toastr);
        }

        # Delete old Program photo if exists
        $oldImg = $program->program_photo;
        if ($oldImg) unlink(public_path('upload/charitable_org/programs/') . $oldImg);

        $program->delete();

        $toastr = array(
            'message' => 'Selected program has been removed successfully.',
            'alert-type' => 'success'
        );
        return redirect()->back()->withInput()->with($toastr);
    }
    public function storeDonationModes(Request $request)
    {
        $validator = Validator::make($request->all(), [
            # Programs
            'mode' => ['required', 'string', 'max:100', 'min:3'],
            'account_name' => ['required', 'string', 'min:5', 'max:120', 'regex:/^[a-zA-Z ñ,-.\']*$/'],
            'account_no' => ['required', 'regex:/^[0-9-]+$/', 'max:50', 'min:7'],
        ], [
            'account_name.regex' => 'Account name must be in valid format. (Ex: Juan Niño Cruz)',
            'account_no.regex' => 'Account number must only include numbers and/or dash. (Ex: 364-3364-77284)',
        ]);

        # Return error toastr if validate request failed
        if ($validator->fails()) {
            $toastr = array(
                'message' => $validator->errors()->first() . ' Please try again.',
                'alert-type' => 'error'
            );
            return redirect()->back()->withInput()->withErrors($validator->errors())->with($toastr);
        }

        # Return an error toastr if equal to or more than 5 donation modes have been added already.
        $donationModes = ProfileModeOfDonation::where('charitable_organization_id', Auth::user()->charitable_organization_id)->get();
        if ($donationModes->count() >= 5) {
            $toastr = array(
                'message' => 'Only a maximum of 5 modes of donation can be added.',
                'alert-type' => 'error'
            );

            return redirect()->back()->withInput()->withErrors($validator->errors())->with($toastr);
        }

        # Start Creating records in DB
        ProfileModeOfDonation::create([
            'charitable_organization_id' => Auth::user()->charitable_organization_id,
            'mode' => $request->mode,
            'account_name' => $request->account_name,
            'account_no' => $request->account_no,
        ]);

        $toastr = array(
            'message' => 'Mode of Donation has been added successfully.',
            'alert-type' => 'success'
        );
        return redirect()->back()->with($toastr);
    }
    public function destroyDonationModes($id)
    {
        $donationMode = ProfileModeOfDonation::findOrFail($id);
        if ($donationMode->charitable_organization_id != Auth::user()->charitable_organization_id) {
            $toastr = array(
                'message' => 'You can only remove your own Charitable Organization\'s pre-existing donation mode(s).',
                'alert-type' => 'error'
            );

            return redirect()->back()->withInput()->with($toastr);
        }

        # Prevent the user from deleting the mode of donation if only one left is remaining.
        $donationModes = ProfileModeOfDonation::where('charitable_organization_id', Auth::user()->charitable_organization_id)->get();
        if ($donationModes->count() == 1) {
            $toastr = array(
                'message' => 'A minimum of one (1) mode of donation is required.',
                'alert-type' => 'error'
            );

            return redirect()->back()->withInput()->with($toastr);
        }

        $donationMode->delete();

        $toastr = array(
            'message' => 'Selected Mode of Donation has been removed successfully.',
            'alert-type' => 'success'
        );
        return redirect()->back()->withInput()->with($toastr);
    }
    public function publishProfile(Request $request)
    {
        # Retrieve public profile data for validation
        $primaryInfo = ProfilePrimaryInfo::where('charitable_organization_id', Auth::user()->charitable_organization_id)->first();
        $secondaryInfo = ProfileSecondaryInfo::where('charitable_organization_id', Auth::user()->charitable_organization_id)->first();
        $programs = ProfileProgram::where('charitable_organization_id', Auth::user()->charitable_organization_id)->take(5)->get();
        $donationModes = ProfileModeOfDonation::where('charitable_organization_id', Auth::user()->charitable_organization_id)->take(5)->get();

        $validator = Validator::make($request->all(), [
            'is_agreed' => ['required']
        ], [
            'is_agreed.required' => 'You must agree first before you can publish your Public Profile.',
        ]);

        # Return error toastr if validate request failed
        if ($validator->fails()) {
            $toastr = array(
                'message' => $validator->errors()->first() . ' Please try again.',
                'alert-type' => 'error'
            );
            return redirect()->back()->with($toastr);
        } elseif ($primaryInfo == null) {
            $toastr = array(
                'message' => 'You have not yet setup primary information of your Charitable Organization. Kindly complete the forms and try again.',
                'alert-type' => 'error'
            );
            return redirect()->back()->with($toastr);
        } elseif ($secondaryInfo == null) {
            $toastr = array(
                'message' => 'You have not yet setup secondary information of your Charitable Organization. Kindly complete the forms and try again.',
                'alert-type' => 'error'
            );
            return redirect()->back()->with($toastr);
        } elseif ($programs->count() < 1) {
            $toastr = array(
                'message' => 'At least one (1) program / activity is required. Kindly add one and try again.',
                'alert-type' => 'error'
            );
            return redirect()->back()->with($toastr);
        } elseif ($donationModes->count() < 1) {
            $toastr = array(
                'message' => 'At least one (1) mode of donation is required. Kindly add one and try again.',
                'alert-type' => 'error'
            );
            return redirect()->back()->with($toastr);
        } else {

            # Retrieve the Charity record
            $charityStatus = Auth::user()->charity;

            # Set status of the Charity to visible
            $charityStatus->profile_status = 'Visible';
            $charityStatus->status_updated_at = Carbon::now();
            $charityStatus->save();

            # Audit Logs
            AuditLog::create([
                'user_id' => Auth::user()->id,
                'action_type' => 'UPDATE',
                'charitable_organization_id' => $charityStatus->id,
                'table_name' => 'Charitable Organization',
                'record_id' => $charityStatus->id,
                'action' => Auth::user()->role . ' published their Charitable Organization\'s Public Profile [' . $charityStatus->name . ']
                    and is now visible to the public.',
                'performed_at' => Carbon::now(),
            ]);

            # Notifications
            foreach ($charityStatus->users as $user) {
                $notif = new Notification;
                $notif->code = Str::uuid()->toString();
                $notif->user_id = $user->id;
                $notif->category = 'Public Profile';
                $notif->subject = 'Profile Status Published';
                $notif->message = $charityStatus->name . '\'s Public Profile was updated and published by ' . Auth::user()->role .
                    ' [ ' . Auth::user()->info->first_name . ' ' . Auth::user()->info->last_name . ' ]. It can now be viewed publicly.';
                $notif->icon = 'mdi mdi-cog';
                $notif->color = 'success';
                $notif->created_at = Carbon::now();
                $notif->save();
            }

            $toastr = array(
                'message' => 'Success! Your Charitable Organization\'s Profile is now visible to the public.',
                'alert-type' => 'success'
            );
            return to_route('charity.profile')->with($toastr);
        }
    }
    public function applyVerification()
    {
        # Only with unverified status can access this function
        if (Auth::user()->charity->verification_status != "Unverified") {
            $notification = array(
                'message' => 'Only unverified charitable organizations can apply for verification.',
                'alert-type' => 'error',
            );

            return to_route('charity.profile')->with($notification);
        }

        $requirements = ProfileRequirement::where('charitable_organization_id', Auth::user()->charitable_organization_id)->first();

        return view('charity.main.profile.verify', compact('requirements'));
    }
    public function submitRequirements(Request $request)
    {
        # Only with unverified status can access this function
        if (Auth::user()->charity->verification_status != "Unverified") {
            $notification = array(
                'message' => 'Only unverified charitable organizations can apply for verification.',
                'alert-type' => 'error',
            );

            return to_route('charity.profile')->with($notification);
        }

        # Validate request
        $validator = Validator::make($request->all(), [
            'sec_registration' => ['required', 'max:2048', 'mimes:jpg,png,jpeg', 'file'],
            'dswd_certificate' => ['required', 'max:2048', 'mimes:jpg,png,jpeg', 'file'],
            'valid_id' => ['required', 'max:2048', 'mimes:jpg,png,jpeg', 'file'],
            'photo_holding_id' => ['required', 'max:2048', 'mimes:jpg,png,jpeg', 'file'],
        ], [
            // Custom error messages...

        ]);

        # Return error toastr if validate request failed
        if ($validator->fails()) {
            $toastr = array(
                'message' => $validator->errors()->first() . ' Please try again.',
                'alert-type' => 'error'
            );

            return redirect()->back()->withInput()->withErrors($validator->errors())->with($toastr);
        }

        # Create profile requirements table if not exists
        $requirements = ProfileRequirement::firstOrNew([
            'charitable_organization_id' => Auth::user()->charitable_organization_id
        ]);

        # Upload SEC Registration
        $oldSEC = $requirements->sec_registration;
        if ($oldSEC) unlink(public_path('upload/charitable_org/requirements/') . $oldSEC);
        $sec = $request->file('sec_registration');
        $sec_filename = Str::uuid() . '.' . $sec->getClientOriginalExtension();
        $sec->move(public_path('upload/charitable_org/requirements/'), $sec_filename);
        $requirements->sec_registration = $sec_filename;

        # Upload DSWD Certificate
        $oldDWSD = $requirements->dswd_certificate;
        if ($oldDWSD) unlink(public_path('upload/charitable_org/requirements/') . $oldDWSD);
        $dswd = $request->file('dswd_certificate');
        $dswd_filename = Str::uuid() . '.' . $dswd->getClientOriginalExtension();
        $dswd->move(public_path('upload/charitable_org/requirements/'), $dswd_filename);
        $requirements->dswd_certificate = $dswd_filename;

        # Upload Valid ID
        $oldValidID = $requirements->valid_id;
        if ($oldValidID) unlink(public_path('upload/charitable_org/requirements/') . $oldValidID);
        $valid_id = $request->file('valid_id');
        $valid_id_filename = Str::uuid() . '.' . $valid_id->getClientOriginalExtension();
        $valid_id->move(public_path('upload/charitable_org/requirements/'), $valid_id_filename);
        $requirements->valid_id = $valid_id_filename;

        # Upload User's Photo Holding Valid ID
        $oldPhotoID = $requirements->photo_holding_id;
        if ($oldPhotoID) unlink(public_path('upload/charitable_org/requirements/') . $oldPhotoID);
        $photo_holding_id = $request->file('photo_holding_id');
        $photo_holding_id_filename = Str::uuid() . '.' . $photo_holding_id->getClientOriginalExtension();
        $photo_holding_id->move(public_path('upload/charitable_org/requirements/'), $photo_holding_id_filename);
        $requirements->photo_holding_id = $photo_holding_id_filename;

        # Update DB
        $requirements->submitted_by = Auth::id();
        $requirements->save();

        # Set verification_status of Charity from Unverified to Pending
        $charityStatus = Auth::user()->charity;
        $charityStatus->verification_status = 'Pending';
        $charityStatus->status_updated_at = Carbon::now();
        $charityStatus->save();

        # Send Notifications to all active Charity Users
        foreach ($charityStatus->users as $user) {
            $notif = new Notification;
            $notif->code = Str::uuid()->toString();
            $notif->user_id = $user->id;
            $notif->category = 'Public Profile';
            $notif->subject = 'Verification Request Sent';
            $notif->message = Auth::user()->role . ' [ ' . Auth::user()->info->first_name . ' ' . Auth::user()->info->last_name . ' ] has successfully
                applied for ' . $charityStatus->name . '\'s Verification Status. Please wait for Caviom to process and verify these documents.';
            $notif->icon = 'mdi mdi-check-decagram';
            $notif->color = 'info';
            $notif->created_at = Carbon::now();
            $notif->save();
        }

        # Audit Logs (Insert)
        AuditLog::create([
            'user_id' => Auth::id(),
            'action_type' => 'UPDATE',
            'charitable_organization_id' => Auth::user()->charitable_organization_id,
            'table_name' => 'Profile Requirement',
            'record_id' => Auth::user()->charity->code,
            'action' => Auth::user()->role . ' [ ' . Auth::user()->info->first_name . ' ' . Auth::user()->info->last_name . ' ]
                submitted Profile Requirements for their Verification Status.',
            'performed_at' => Carbon::now(),
        ]);

        $successToastr = array(
            'message' => 'Success! Please wait for the Caviom team to verify your submitted documents.',
            'alert-type' => 'success'
        );
        return to_route('charity.profile')->with($successToastr);
    }
    public function reapplyVerification()
    {
        # Only with declined applications can access this function
        if (Auth::user()->charity->verification_status != "Declined") {
            $notification = array(
                'message' => 'Only declined charitable organizations can re-apply for verification.',
                'alert-type' => 'error',
            );

            return to_route('charity.profile')->with($notification);
        }

        # Clear profile_requirements table where charity == Auth::user()->charity
        $requirements = ProfileRequirement::where('charitable_organization_id', Auth::user()->charitable_organization_id)->firstOrFail();
        unlink(public_path('upload/charitable_org/requirements/') . $requirements->sec_registration);
        unlink(public_path('upload/charitable_org/requirements/') . $requirements->dswd_certificate);
        unlink(public_path('upload/charitable_org/requirements/') . $requirements->valid_id);
        unlink(public_path('upload/charitable_org/requirements/') . $requirements->photo_holding_id);

        # Update DB
        $requirements->sec_registration = null;
        $requirements->dswd_certificate = null;
        $requirements->valid_id = null;
        $requirements->photo_holding_id = null;
        $requirements->save();

        # Update status of Charitable Organization from Declined back to Unverified
        $charityStatus = Auth::user()->charity;
        $charityStatus->verification_status = 'Unverified';
        $charityStatus->status_updated_at = Carbon::now();
        $charityStatus->save();

        # redirect to applyVerification function
        return to_route('charity.profile.verify');
    }
}
