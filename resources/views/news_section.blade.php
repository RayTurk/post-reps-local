<section class="container mt-5 mb-5 pb-5">
    <div class="text-center mt-2">
        <h2>Latest News</h2>
    </div>

    @if ($notices->isNotEmpty())
        @foreach ($notices as $notice)
            <div class="d-flex justify-content-left mb-4">
                <div class="badge badge-info text-white my-auto py-2 px-3 txt-1rem">{{ $notice->start_date->format('m/d/Y') }}</div>
                <div class="text-block pl-3">
                    <strong>{{ $notice->subject }}</strong><br>
                    {{ $notice->details }}
                </div>
            </div>
        @endforeach
    @else
        <div class="d-flex justify-content-center mb-4 mt-4">
            <p class="h5 font-weight-bold">There are no recent news to show.</p>
        </div>
    @endif

</section>
