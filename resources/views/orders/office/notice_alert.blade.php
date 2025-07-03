@foreach ($notices as $notice)
    <div class="alert alert-warning alert-dismissible fade show text-dark" role="alert">
        <b>{{$notice->subject}}</b><br>
        {{$notice->details}}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endforeach
