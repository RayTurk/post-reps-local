<div class="card public-card m-5">
    
	<div class="card-header"><h5 class="text-white">To send an email request, please fill out the form below:</h5></div>
    <div class="card-body">
        <form method="POST" action="{{ url('/contact') }}" id="contactForm">
            <input type="hidden" name="recaptcha_token" id="recaptchaToken">
            @csrf

            <div class="form-group row">
                <label for="email" class="col-md-4 col-form-label text-md-right text-white">YOUR NAME</label>
                <div class="col-md-6">
                    <input id="name" type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ $user->name }}" required autocomplete="name" autofocus>
                    @error('name')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
            </div>

            <div class="form-group row">
                <label for="email" class="col-md-4 col-form-label text-md-right text-white">EMAIL ADDRESS</label>
                <div class="col-md-6">
                    <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ $user->email }}" required readonly autofocus>

                    @error('email')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
            </div>

            <div class="form-group row">
                <label for="password" class="col-md-4 col-form-label text-md-right  text-white">YOUR MESSAGE</label>
                <div class="col-md-6">
                    <textarea class="form-control" name="message" id="message" cols="30" rows="9" required></textarea>
                    @error('message')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
            </div>


            <div class="form-group row mb-0">
                <div class="col-md-8 offset-md-4">
                    <button type="submit" disabled id="submitBtn"
                        class="btn btn-orange font-weight-bold text-white">
                        SEND MESSAGE
                    </button>
                </div>
            </div>
			<div class="form-group row mt-5">
                <div class="col-md-6 offset-md-4">
                    <p class="text-black font-weight-normal h5 mb-0 pb-0">
						If you have an urgent need, please call our office.</p>
					<p class="text-white font-weight-bold h5 mb-0 pb-0">
						<strong>OFFICE NUMBER: (208)546-5546</strong></p>
                    <p class="text-black font-weight-normal h5 mb-0 pb-0">
						Office Hours: 8:00AM to 5:30PM Monday – Friday
                    </p>
                </div>
            </div>
        </form>
    </div>
</div>

