@extends('layouts.public')

@section('content')
<div class="container mt-5 mb-5">
    @include('layouts.includes.alerts')

    <div class="row justify-content-center mt-5">
        <div class="col-md-8">
            <div class="card public-card mt-5 mb-5">
                <div class="card-header"><h5 class="text-white">{{ __('Login') }}</h5></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('login') }}" id="loginForm">
                        <input type="hidden" name="recaptcha_token" id="recaptchaToken">
                        @csrf

                        <div class="form-group row">
                            <label for="email" class="col-md-4 col-form-label text-md-right text-white">EMAIL ADDRESS</label>

                            <div class="col-md-6">
                                <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus>

                                @error('email')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="password" class="col-md-4 col-form-label text-md-right  text-white">PASSWORD</label>

                            <div class="col-md-6">
                                <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="current-password">

                                @error('password')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <div class="col-md-6 offset-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>

                                    <label class="form-check-label text-white" for="remember">
                                        {{ __('Remember Me') }}
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="form-group row mb-0">
                            <div class="col-md-8 offset-md-4">
                                <button type="submit" class="btn btn-orange font-weight-bold text-white" id="submitBtn" disabled>
                                    SIGN IN
                                </button>

                                @if (Route::has('password.request'))
                                    <a class="btn btn-link text-white" href="{{ route('password.request') }}">
                                        {{ __('Forgot Your Password?') }}
                                    </a>
                                @endif
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

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
            document.getElementById('loginForm').addEventListener('submit', function(e) {
                disableSubmitBtn();
                changeSubmitBtnText('SIGNING IN...');
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
