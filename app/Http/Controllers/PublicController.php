<?php

namespace App\Http\Controllers;

use App\Models\CharitableOrganization;
use App\Models\Charity\Public\Lead;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PublicController extends Controller
{
    public function showHome()
    {
        return view('public.v2.index');
    }
    public function showAbout()
    {
        return view('public.v2.pages.about');
    }
    public function showServices()
    {
        return view('public.v2.pages.services');
    }
    public function showContact()
    {
        return view('public.v2.pages.contact');
    }
    public function showAllCharities()
    {
        return view('public.v2.pages.charities');
    }
    public function viewCharity(/*$code*/) // Uncomment the $code here
    {
        $code = 'c3d1d1c5-6665-4b68-ad54-ceeb973a9348'; // This is just a test sample uuid and this line should be REMOVED.
        $charity = CharitableOrganization::where('code', $code)->firstOrfail();

        if ($charity->profile_status != 'Visible') {
            $notification = array(
                'message' => 'Sorry, the Public Profile of this Charitable Organization might be Locked or Hidden.',
                'alert-type' => 'error',
            );

            return redirect()->back()->with($notification);
        }

        $charity->view_count += 1;
        $charity->save();

        return view('public.charities.view', compact('charity'));
    }
    public function viewFeaturedProject()
    {
        return view('public.charities.components.feat-projects.view');
    }

    public function Donate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'proof_of_payment_photo' => 'nullable|mimes:jpg,png,jpeg|max:2048|file',
            'email' => ['required', 'string', 'email:rfc,dns', 'max:255'],
            'first_name' => ['required', 'string', 'min:2', 'max:64', 'regex:/^[a-zA-Z ñ,-.\']*$/'],
            'last_name' => ['required', 'string', 'min:2', 'max:64', 'regex:/^[a-zA-Z ñ,-.\']*$/'],
            'middle_name' => ['nullable', 'string', 'min:1', 'max:64', 'regex:/^[a-zA-Z ñ,-.\']*$/'],
            'mode_of_donation' => ['required',Rule::in(['BDO', 'GCash'])], // Must be in array of Charitable Organization's mode of payments only.
            'amount' => 'required|numeric|between:0,999999.99',
            'paid_at' => 'required',
            'message'=> 'nullable|max:512',
            'g-recaptcha-response' => 'required|captcha'
        ], [
            'g-recaptcha-response.captcha' => 'Captcha error! Please try again',
            'g-recaptcha-response.required' => 'Please verify that you are not a robot.',

        ]);

        # Return error toastr if validate request failed
        if ($validator->fails()) {
            $toastr = array(
                'message' => $validator->errors()->first() . ' Please try again.',
                'alert-type' => 'error'
            );
            return redirect()->back()->withInput()->withErrors($validator->errors())->with($toastr);
        }


        $donate = new Lead;
        $donate->code =Str::uuid()->toString();
        $donate->charitable_organization_id = 4;    //hardcoded because unfished public profile

        # Insert Proof of Payment photo
        if ($request->file('proof_of_payment_photo')) {
            $file = $request->file('proof_of_payment_photo');
            $filename = date('YmdHi') . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('upload/charitable_org/donates/'), $filename);
            $donate->proof_of_payment_photo = $filename;
        }
        $donate->amount = $request->amount;
        $donate->mode_of_donation = $request->mode_of_donation;
        $donate->message = $request->message;
        $donate->first_name = $request->first_name;
        $donate->last_name = $request->last_name;
        $donate->middle_name = $request->middle_name;
        $donate->email_address = $request->email;
        $donate->paid_at = $request->paid_at;
        $donate->created_at = Carbon::now();

        $donate->save();

        $notification = array(
            'message' => 'Donation Uploaded Successfully',
            'alert-type' => 'success'
        );

        return redirect()->back()->with($notification);

    }
}
