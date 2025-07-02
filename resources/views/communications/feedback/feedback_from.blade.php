@extends('layouts.public')

@section('content')
    <div class="container mt-5 mb-5">
        <div class="row justify-content-center mt-5">
            <div class="col-md-8">
                <div class="card public-card mt-5 mb-5">
                    <div class="card-header">
                        <h5 class="text-white">How did we do?</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex flex-column flex-lg-row justify-content-between">
                            <p class="text-white font-weight-bold h5">Order #: {{ $order->order_number }}</p>
                            <p class="text-white h5"><span class="font-weight-bold">Address:</span>
                                @if ($order->order)
                                    {{ $order->order->address }}
                                @endif {{ $order->address }}
                            </p>
                        </div>

                        @if (session('success'))
                            <div class="alert bg-success pt-4 pb-4 mt-4 mb-4 text-center text-white h5 font-weight-bold" role="alert">
                                {!! session('success') !!}
                            </div>
                        @else
                            <form method="POST" action="/order/{{ request('type') }}/{{ $order->id }}/feedback">
                                @csrf

                                <div class="form-group row">
                                    <div class="col-md-12 text-center mt-4">
                                        <input id="rating" name="rating" type="number"
                                            class="@error('rating') is-invalid @enderror" value="{{ old('rating') }}"
                                            required>

                                        @error('rating')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="feedback" class="col-md-4 text-white font-weight-bold h5">Your feedback</label>

                                    <div class="col-md-12">
                                        <textarea class="form-control @error('email') is-invalid @enderror" name="feedback" id="feedback" rows="5" required
                                            placeholder="Max 500 characters">{{ old('feedback') }}</textarea>

                                        @error('feedback')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="form-group row mb-0">
                                    <div class="col-md-12 text-center">
                                        <button type="submit"
                                            class="btn btn-lg btn-orange font-weight-bold text-white pr-4 pl-4">
                                            Submit
                                        </button>
                                    </div>
                                </div>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="footer-separator"></div>
@endsection

@section('page_scripts')
    <script src="{{ mix('/js/feedback-communication.js') }}" defer></script>
@endsection
