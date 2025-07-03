<section class="container-fluid light-blue-bg mt-5 mb-5 pt-4 pb-5 testimonial-container">
    <div class="text-center mt-2">
        <h2>What Our Customers Say</h2>
    </div>
    <div class="container">
        <div class="testimonial-swiper position-relative">
            <div class="swiper-wrapper">
                @if ($orders->isNotEmpty())
                    @foreach ($orders as $order)
                        <div class="swiper-slide">
                            <div class="col-md-6 col-10 text-center pt-3">
                                <div class="card border-blue testimonial-card">
                                    <div class="card-body py-4">
                                        <div class="row">
                                            <div class="col-md-3 d-flex justify-content-center">
                                                <img class="testimonial-avatar img-fluid height-px-100"
                                                    src="{{ asset('/storage/images/avatar_placeholder.jpg') }}" alt="Pic">
                                            </div>
                                            <div class="col-md-9 text-center">
                                                <h4>{{ isset($order->agent_first_name)
                                                    ? $order->agent_first_name.' '.substr($order->agent_last_name, 0, 1).'.'
                                                    : $order->office_name
                                                }}</h4>
                                                <div class="text-block text-left">
                                                    {{$order->feedback}}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="swiper-slide">
                        <div class="col-md-6 col-10 text-center pt-3">
                            <div class="card border-blue testimonial-card">
                                <div class="card-body py-4">
                                    <div class="row">
                                        <div class="col-md-12 text-center">
                                            <h4>There are no feedbacks to show</h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
            <div class="col-md-9 col-6 pt-2 text-right prev-arrow position-absolute">
                <i class="fa fa-arrow-left"></i>
            </div>
            <div class="col-md-9 col-6 pt-2 text-left next-arrow position-absolute">
                <i class="fa fa-arrow-right"></i>
            </div>
        </div>
    </div>
</section>
