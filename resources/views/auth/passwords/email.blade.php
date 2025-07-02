@extends('layouts.public')

@section('content')
<div class="container mt-5 mb-5">
    @include('layouts.includes.alerts')

    <div class="row justify-content-center mb-5">
        <div class="col-md-8">
            <div class="card public-card">
                <div class="card-header"><h5 class="text-white">{{ __('Reset Password') }}</h5></div>

                <div class="card-body">
                    @if (session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('password.email') }}" id="emailForm">
                        <input type="hidden" name="recaptcha_token" id="recaptchaToken">
                        @csrf

                        <div class="form-group row">
                            <label for="email" class="col-md-4 col-form-label text-md-right">EMAIL ADDRESS</label>

                            <div class="col-md-6">
                                <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus >

                                @error('email')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row mb-0">
                            <div class="col-md-6 offset-md-4">
                                <button type="submit" class="btn btn-orange font-weight-bold text-white" id="submitBtn" disabled>
                                    SEND PASSWORD RESET LINK
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="footer-separator"></div>
<div class="footer-separator"></div>

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
            document.getElementById('emailForm').addEventListener('submit', function(e) {
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
