<div class="card public-card m-3">
    <div class="card-header"><h5 class="text-white">Please fill out the form below with your request.</h5></div>
    <div class="card-body">
        <form method="POST" action="{{ url('contact') }}" id="contactFormMobile">
            <input type="hidden" name="recaptcha_token" id="recaptchaTokenMobile">
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
                    <button type="submit" disabled id="submitBtnMobile"
                        class="btn btn-orange font-weight-bold text-white">
                        SEND MESSAGE
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
