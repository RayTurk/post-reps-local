@extends('layouts.public')

@section('content')
    <div class="container">
        <div class="text-center mt-5"><h3>Contact Us</h3></div>

        @include('layouts.includes.alerts')

        <div class="row justify-content-center" style="margin-top: -20px;">
            <div class="col-md-8">
                <div class="card public-card mt-5 mb-5">
                    <div class="card-header"><h5 class="text-white">Please fill out the form below with your request.</h5></div>
                    <div class="card-body">
                        <form method="POST" action="{{ url('contact') }}" id="contactForm">
                            <input type="hidden" name="recaptcha_token" id="recaptchaToken">
                            @csrf

                            <div class="form-group row">
                                <label for="email" class="col-md-4 col-form-label text-md-right text-white">YOUR NAME</label>
                                <div class="col-md-6">
                                    <input id="name" type="text" class="form-control @error('name') is-invalid @enderror"
                                    name="name" value="{{ old('name') }}" required autocomplete="name"
                                    autofocus>
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
                                    <input id="email" type="email" class="form-control @error('email') is-invalid @enderror"
                                    name="email" value="{{ old('email') }}" required
                                    autocomplete="email" autofocus>

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
                                    <textarea class="form-control" name="message" id="message"
                                    cols="30" rows="9" required></textarea>
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
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @section('recaptcha_scripts')
        <script src="https://www.google.com/recaptcha/api.js?render={{config('recaptcha.site_key')}}"></script>

        <script type="text/javascript">
            let siteKey = "{{config('recaptcha.site_key')}}";

            setTimeout(function() {
                grecaptcha.ready(function() {
                    grecaptcha.execute(siteKey, {action: 'submit'}).then(function(token) {
                        if (token) {
                            document.getElementById('recaptchaToken').value = token;

                            //enable contact form submit button
                            enableSubmitBtn();
                        }
                    });
                });
            }, 2000);

            function enableSubmitBtn() {
                document.getElementById('submitBtn').disabled = false;
            }
        </script>

    @endsection
    @section('page_scripts')
        <script type="text/javascript">
            document.getElementById('contactForm').addEventListener('submit', function(e) {
                disableSubmitBtn();
                changeSubmitBtnText('SENDING...');
            });

            function disableSubmitBtn() {
                document.getElementById('submitBtn').disabled = true;
            }
            function changeSubmitBtnText(text) {
                document.getElementById('submitBtn').innerHTML = text;
            }
        </script>
    @endsection
@endsection
