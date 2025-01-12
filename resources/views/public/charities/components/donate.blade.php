<h1 class="text-center display-3 my-5">Donate</h1>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>

@if ($charity->verification_status == 'Verified')
<div class="row justify-content-center">
    <div class="col-8">
        <div class="table-responsive">
            <table class="table table-bordered table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="1">No</th>
                        <th>Mode of Donation</th>
                        <th>Account Name</th>
                        <th>Account Number</th>
                    </tr>
                </thead>
                <tbody>
                @if ($charity->donationModes->count() == 0) -->
                <tr>
                    <td colspan="4" class="text-center small text-muted">
                        This charitable organization currently has no mode of donation
                    </td>
                </tr>
                @endif
                @foreach ($charity->donationModes as $key => $item)
                <tr>
                    <th scope="row">{{$key+1}}</th>
                    <td>{{$item->mode}}</td>
                    <td>{{$item->account_name}}</td>
                    <td>{{$item->account_no}}</td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>


<h4 class="text-center display-6 mt-5">Let them know you donated or send them a message</h4>

<div class="row justify-content-center">
    <!-- Preview of Photo -->
    <div class="col-2 pt-4">
        <img class="rounded img-fluid" src="{{ asset('backend/assets/images/placeholder-image.jpg') }}" id="showImage" alt="Avatar">
    </div>
    <div class="col-8">
        <form action="{{route('store.donate', $charity->code)}}" method="POST" class="my-5" enctype="multipart/form-data" id="submitDonateForm">
            @csrf
            <div class="form-group mb-3 row">
                <!-- Profile Photo -->
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="proof_of_payment_photo" class="form-label">
                            Proof of Donation (Optional)
                            <span data-bs-toggle="tooltip" data-bs-placement="bottom" title="Recommended image file size of not exceeding 2mb."
                                data-bs-original-title="yes">
                                <i class="mdi mdi-information-outline"></i>
                            </span>
                        </label>
                        <input class="form-control" name="proof_of_payment_photo" id="proof_of_payment_photo" type="file">
                        @error('proof_of_payment_photo')
                        <div class="text-danger small">
                            {{ $message }}
                        </div>
                        @enderror
                    </div>
                </div>

                <!-- Email Address -->
                <div class="col-md-6">
                    <label for="email" class="form-label">*Email Address</label>
                    <input class="form-control" name="email" id="email" value="{{ old('email') }}" required>
                    @error('email')
                    <div class="text-danger small">
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
                        <input type="text" class="form-control" name="first_name" id="first_name" value="{{ old('first_name') }}" required>
                        @error('first_name')
                        <div class="text-danger small">
                            {{ $message }}
                        </div>
                        @enderror
                    </div>
                </div>

                <!-- Middle Name -->
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="middle_name" class="form-label">Middle Name</label>
                        <input type="text" class="form-control" name="middle_name" id="middle_name" value="{{ old('middle_name') }}">
                        @error('middle_name')
                        <div class="text-danger small">
                            {{ $message }}
                        </div>
                        @enderror
                    </div>
                </div>

                <!-- Last Name -->
                <div class="col-md-4">
                    <label for="last_name" class="form-label">*Last Name</label>
                    <input type="text" class="form-control" name="last_name" id="last_name" value="{{ old('last_name') }}" required>
                    @error('last_name')
                    <div class="text-danger small">
                        {{ $message }}
                    </div>
                    @enderror
                </div>
            </div>

            <div class="form-group mb-3 row">
                <!-- Mode of Donation -->
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="mode_of_donation" class="form-label">*Mode of Donation Used</label>
                        <select class="form-select select2-search-disable" name="mode_of_donation" id="mode_of_donation" aria-label="Select method" required>
                            <option selected>Select mode of donation...</option>

                            <!-- For each mode of donations of Charitable Organization, display each option as item -->
                            @foreach ($charity->donationModes as $donationMode)
                            <option value="{{$donationMode->mode}}" {{old('mode_of_donation')==$donationMode->mode?'selected':''}}>{{$donationMode->mode}}</option>
                            @endforeach
                        </select>
                        @error('mode_of_donation')
                            <div class="text-danger small">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>
                </div>

                <!-- Amount -->
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="amount" class="form-label">*Amount Donated</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text" id="peso_currency">Php</span>
                            </div>
                            <input class="form-control input-mask" data-inputmask="'alias': 'numeric',
                                'digits': 2, 'digitsOptional': false, 'placeholder': '0'" name="amount"
                                id="amount" value="{{ old('amount') }}">
                        </div>
                        @error('amount')
                            <div class="text-danger small">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>
                </div>

                <!-- Date of Payment -->
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="paid_at" class="form-label">Date of Payment</label>
                        <input type="datetime-local" class="form-control" name="paid_at" id="paid_at" value="{{ old('paid_at') }}"
                            min="{{Carbon\Carbon::now()->subYears(3)->startOfYear()}}" max="{{Carbon\Carbon::now()}}">
                        @error('paid_at')
                        <div class="text-danger small">
                            {{ $message }}
                        </div>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Message -->
            <label for="message" class="form-label">Message</label>
            <textarea class="form-control" name="message" id="textarea" rows="4" maxlength="500"
                placeholder="Max. of 500 Characters only...">{{old('message')}}</textarea>
            @error('message')
                <div class="text-danger small">
                    {{ $message }}
                </div>
            @enderror

            <!-- Put captcha test here -->
            <div name="captcha" class="mt-5">
                {!! NoCaptcha::renderJs() !!}
                {!! NoCaptcha::display() !!}
                @error('g-recaptcha-response')
                    <div class="text-danger small">
                        {{ $message }}
                    </div>
                @enderror
            </div>


            <button type="button" data-bs-toggle="modal" data-bs-target="#confirmModal" class="btn btn-rounded btn-dark waves-effect waves-light w-lg float-end mt-3 mb-5">Submit</button>

            <!-- Confirm Modal of Submit button -->
            <div id="confirmModal" class="modal fade" tabindex="-1" aria-labelledby="myModalLabel" aria-hidden="true" style="display: none;">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="myModalLabel"><i class="mdi mdi-alert-outline me-2"></i> Warning</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p>Please double check the provided information. Continue?</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light waves-effect w-sm" data-bs-dismiss="modal">No</button>
                            <button type="submit" form="submitDonateForm" class="btn btn-dark waves-effect waves-light w-sm">Yes</button>
                        </div>
                    </div><!-- /.modal-content -->
                </div><!-- /.modal-dialog -->
            </div>

        </form>
    </div>
</div>
@else
<div class="row justify-content-center">
    <div class="col-8">
        <p class="mb-3">
            Sorry, Caviom prohibits unverified Charitable Organizations from posting their mode of donation(s).
            This is to ensure that Caviom will be free from fraudulent activities and not by any manner of means be used as a tool to deceive
            people nor cause any harm.
        </p>
        <p class="mb-5">
            You may still reach out to them through their provided contact information.
            However, Caviom will not be liable for any malicious, fraudulent, or deceitful transactions made outside its platform.
        </p>
    </div>
</div>
@endif

<script type="text/javascript">
    $(document).ready(function() {
        $('#proof_of_payment_photo').change(function(e) {
            var reader = new FileReader();
            reader.onload = function(e) {
                $('#showImage').attr('src', e.target.result);
            }
            reader.readAsDataURL(e.target.files['0']);
        })
    });
</script>
<!-- End if -->