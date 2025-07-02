@if ($orders->isNotEmpty())
    @foreach ($orders as $order)
        <div class="card auth-card d-flex mt-1">
            <div class="card-body">
                <div class="d-flex justify-content-between flex-column">
                    <div class="w-100">
                        <p><span>{{ date('m/d/Y', strtotime($order->feedback_date)) }}</span> - <span>{{ $order->agent_name }},
                                {{ $order->office_name }}</span> - <span>Installer: {{ $order->installer_name }}</span>
                        </p>
                        @switch($order->order_type)
                            @case('install')
                                <p><span class="text-uppercase font-weight-bold h5 text-success">{{ $order->order_type }}</span>
                                    for <u class="text-primary font-weight-bold">{{ $order->address }}</u>
                                @break

                                @case('repair')
                                <p><span class="text-uppercase font-weight-bold h5 text-primary">{{ $order->order_type }}</span>
                                    for <u class="text-primary font-weight-bold">{{ $order->address }}</u>
                                @break

                                @case('removal')
                                <p><span class="text-uppercase font-weight-bold h5 text-danger">{{ $order->order_type }}</span>
                                    for <u class="text-primary font-weight-bold">{{ $order->address }}</u>
                                @break

                                @case('delivery')
                                <p><span class="text-uppercase font-weight-bold h5 text-orange">{{ $order->order_type }}</span>
                                    for <u class="text-primary font-weight-bold">{{ $order->address }}</u>
                                @break

                                @default
                            @endswitch
                        </p>
                        <p>{{ $order->feedback }}</p>
                    </div>
                    <div class="">
                        <form action="" id="publish_form">
                            @csrf
                            <div class="form-group p-0 m-0">
                                <input class="" id="ratingFeedbackPage" name="" type="number"
                                    value="{{ $order->rating }}" disabled>
                            </div>
                            <div class="form-check">
                                <input type="hidden" name="orderId" id="orderId" value="{{ $order->id }}">
                                <input type="hidden" name="orderType" id="orderType"
                                    value="{{ $order->order_type }}">
                                <input class="form-check-input" type="checkbox" data-id="{{ $order->id }}"
                                    id="publish_checkbox" name="publish_checkbox"
                                    {{ $order->feedback_published ? 'checked' : '' }}>
                                <label class="form-check-label text-dark h5" for="">
                                    Publish
                                </label>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
@else
    <div class="card auth-card d-flex mt-1">
        <div class="card-body text-center">
            <p>There are no feedbacks to show.</p>
        </div>
    </div>
@endif
