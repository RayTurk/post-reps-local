@extends('layouts.public')

@section('content')
    <style>
        .swiper-button-prev,
        .swiper-button-next {
            display: none;
        }

        .swiper-pagination-bullet-active {
            background-color: #134185;
        }

        @media only screen and (max-width: 912px) {
            .swiper-pagination {
            display: none;
        }
        }

    </style>

    <div class="container-fluid" style="width: 101%;">
        <!-- Swiper -->
        <div class="swiper mySwiper">
            <div class="swiper-text" id="slideIndex0">
                <div class="swiper-text-2">SERVICING THE GREATER BOISE AREA</div>
                <div class="swiper-text-3">Real Estate Sign and Post installation services. We even store your signs, so you
                    don't have to.</div>
            </div>
            <div class="swiper-text" id="slideIndex1" style="display: none;">
                <div class="swiper-text-2">SATISFACTION GUARANTEED</div>
                <div class="swiper-text-3">Your satisfaction is key to the work that we do, and we back it with a guarantee.
                </div>
            </div>
            <div class="swiper-text" id="slideIndex2" style="display: none;">
                <div class="swiper-text-2">NEVER BUY POSTS AGAIN</div>
                <div class="swiper-text-3">Our sturdy handcrafted posts are all part of the service, never worry about
                    buying or storing your posts again.</div>
            </div>
            <div class="swiper-text" id="slideIndex3" style="display: none;">
                <div class="swiper-text-2">PROVIDING SERVICE FOR ALL YOUR NEEDS</div>
                <div class="swiper-text-3">Servicing everything from existing homes to new construction. Were here to take
                    care of your signpost installation needs.</div>
            </div>
            <div class="swiper-text" id="slideIndex4" style="display: none;">
                <div class="swiper-text-2">VACANT LAND LISTINGS</div>
                <div class="swiper-text-3">PostReps provides service for commercial developments and vacant land listings as well.</div>
            </div>

            <div class="swiper-buttons">
                <div class="d-flex justify-content-between">
                    <a href="#whyUs" class="btn btn-orange-lg btn-sm ">WHY CHOOSE US</a>
                    <a href="#ourServices" class="btn btn-dark-blue-lg ml-3 btn-sm">OUR SERVICES</a>
                </div>
            </div>

            <div class="swiper-wrapper">
                <div class="swiper-slide">
                    <img class="img-fluid" src="{{ asset('/storage/images/slide2.jpg') }}" alt="slide 1">
                </div>
                <div class="swiper-slide">
                    <img class="img-fluid" src="{{ asset('/storage/images/slide3.jpg') }}" alt="slide 2">
                </div>
                <div class="swiper-slide">
                    <img class="img-fluid" src="{{ asset('/storage/images/slide1.jpg') }}" alt="slide 3">
                </div>
                <div class="swiper-slide">
                    <img class="img-fluid" src="{{ asset('/storage/images/slide4.jpg') }}" alt="slide 4">
                </div>
                <div class="swiper-slide">
                    <img class="img-fluid" src="{{ asset('/storage/images/slide5.jpg') }}" alt="slide 5">
                </div>
            </div>
            <div class="swiper-button-next"></div>
            <div class="swiper-button-prev"></div>
            <div class="swiper-pagination"></div>
        </div>
    </div>

    <div class="container">
        <div class="row mt-5">
            <div class="col-md-4 text-right">
                <img class="img-fluid pt-2 hide-on-mobile" src="{{ asset('/storage/images/signpost1.jpg') }}" alt="image">
            </div>
            <div class="col-md-8 text-left pl-5">
                <div class="text-center">
                    <h2>More Than Just Another <br>Sign Post Installation Company</h2>
                </div>
                <div class="text-block">
                    <p>Post Reps is the best source for real estate sign post installation services in the Boise Idaho area.
                        We are built upon a dependable service that provides quality sign installation and storage. The
                        PostReps team is built of dependable, professional, and respectful representatives ready right now
                        to service and install that next sign. You will discover that we cover more areas and thus more
                        properties than any other sign post installation company around!</p>

                    <p>Yard signs are often the first point of contact buyers have. We offer the clean, prompt, powerful,
                        and professional service that you should expect. It is our goal to always make you look
                        professional. We handle the storage, installation, and removal of signs so that you can take care of
                        your customers and stop having to worry about where to place those signs.</p>

                    <p>All posts are clean and inspected for quality each time a post is installed. Sometimes we may be able
                        to install signs the same day but we guarantee signs are installed by the specified date selected for
                        all standard coverage areas. Post Reps takes pride in providing the quickest and most dependable response 
						times around.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid light-blue-bg mt-5 pt-4 pb-5" id="whyUs">
        <div class="text-center mt-2">
            <h2>Why Choose Us</h2>
        </div>
        <div class="row mt-3 pl-4 pr-1">
            <div class="col-md-2 "></div>
            <div class="col-md-4">
                <div class="row">
                    <div class="col-md-1 col-1 px-1 text-right pt-0">
                        <span class="list-numbering pt-0">&#8226;</span>
                    </div>
                    <div class="col-md-11 col-11 text-left pl-1 pt-2">
                        <h3 class="text-orange">Next Business Day Guarantee</h3>
                        <span class="text-block">Orders received by 6:00pm will be installed the following business day
                            for our Standard Coverage Area.</span>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="row">
                    <div class="col-md-1 col-1 px-1 text-right pt-0">
                        <span class="list-numbering pt-0">&#8226;</span>
                    </div>
                    <div class="col-md-11 col-11 text-left pl-1 pt-2">
                        <h3 class="text-orange">Handcrafted Wood Posts</h3>
                        <span class="text-block">Our posts are custom built wood posts. Unlike a metal or vinyl post, if
                            our posts get scuffed up, we repair and repaint them to look like new.</span>
                    </div>
                </div>
            </div>
            <div class="col-md-2 "></div>
        </div>

        <div class="row mt-3 pl-4 pr-1">
            <div class="col-md-2 "></div>
            <div class="col-md-4">
                <div class="row">
                    <div class="col-md-1 col-1 px-1 text-right pt-0">
                        <span class="list-numbering pt-0">&#8226;</span>
                    </div>
                    <div class="col-md-11 col-11 text-left pl-1 pt-2">
                        <h3 class="text-orange">Extended Outlying Service Areas</h3>
                        <span class="text-block">For an additional trip fee, we cover even most of the more distant
                            cities and towns in the Treasure Valley.</span>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="row">
                    <div class="col-md-1 col-1 px-1 text-right pt-0">
                        <span class="list-numbering pt-0">&#8226;</span>
                    </div>
                    <div class="col-md-11 col-11 text-left pl-1 pt-2">
                        <h3 class="text-orange">We Store Your Signs</h3>
                        <span class="text-block">While you always have the option to keep your own panels, we will
                            gladly store them and attach them for you on each install.</span>
                    </div>
                </div>
            </div>
            <div class="col-md-2 "></div>
        </div>

        <div class="row mt-3 pl-4 pr-1">
            <div class="col-md-2 "></div>
            <div class="col-md-4">
                <div class="row">
                    <div class="col-md-1 col-1 px-1 text-right pt-0">
                        <span class="list-numbering pt-0">&#8226;</span>
                    </div>
                    <div class="col-md-11 col-11 text-left pl-1 pt-2">
                        <h3 class="text-orange">Online Ordering and Tracking</h3>
                        <span class="text-block">Placing your orders on-line and then tracking them to see when they
                            will be installed has never been easier!</span>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="row">
                    <div class="col-md-1 col-1 px-1 text-right pt-0">
                        <span class="list-numbering pt-0">&#8226;</span>
                    </div>
                    <div class="col-md-11 col-11 text-left pl-1 pt-2">
                        <h3 class="text-orange">Three Weeks Satisfaction</h3>
                        <span class="text-block">If you are disappointed with an installation, we will repair it,
                            repaint it, move it, or replace it.</span>
                    </div>
                </div>
            </div>
            <div class="col-md-2 "></div>
        </div>

        <div class="row mt-3 pl-4 pr-1">
            <div class="col-md-2 "></div>
            <div class="col-md-4">
                <div class="row">
                    <div class="col-md-1 col-1 px-1 text-right pt-0">
                        <span class="list-numbering pt-0">&#8226;</span>
                    </div>
                    <div class="col-md-11 col-11 text-left pl-1 pt-2">
                        <h3 class="text-orange">Email and Photo Verification</h3>
                        <span class="text-block">Email notifications are sent out to all our customers with each order.
                            Photo Verifications are available to share with your clients!</span>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="row">
                    <div class="col-md-1 col-1 px-1 text-right pt-0">
                        <span class="list-numbering pt-0">&#8226;</span>
                    </div>
                    <div class="col-md-11 col-11 text-left pl-1 pt-2">
                        <h3 class="text-orange">Several Optional Accessories</h3>
                        <span class="text-block">Post Reps continues to add new accessories that help you market your
                            listings. We offer flier boxes, solar lights, additional clips and more.</span>
                    </div>
                </div>
            </div>
            <div class="col-md-2 "></div>
        </div>

        <div class="row mt-3 pl-4 pr-1">
            <div class="col-md-2 "></div>
            <div class="col-md-4">
                <div class="row">
                    <div class="col-md-1 col-1 px-1 text-right pt-0">
                        <span class="list-numbering pt-0">&#8226;</span>
                    </div>
                    <div class="col-md-11 col-11 text-left pl-1 pt-2">
                        <h3 class="text-orange">Never Purchase a Post Again</h3>
                        <span class="text-block">With our service, we supply the post. Never worry about where to store
                            the posts again! We always have a supply of posts.</span>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="row">
                    <div class="col-md-1 col-1 px-1 text-right pt-0">
                        <span class="list-numbering pt-0">&#8226;</span>
                    </div>
                    <div class="col-md-11 col-11 text-left pl-1 pt-2">
                        <h3 class="text-orange">Credit Card or Terms</h3>
                        <span class="text-block">Most agents will use an on-line credit card processing system but we
                            also can offer terms for qualified agents and offices.</span>
                    </div>
                </div>
            </div>
            <div class="col-md-2 "></div>
        </div>
    </div>

    <div class="container mt-5 mb-5" id="ourServices">
        <div class="text-center mt-2">
            <h2>Our Services</h2>
        </div>
        <div class="row mt-3">
            <div class="col-md-4 pt-3">
                <div class="card border-orange service-card">
                    <div class="card-body py-4">
                        <div class="row">
                            <div class="col-md-4 text-center">
                                <span class="icon-wrapper" style="padding: 12px 23px 9px 23px;">
                                    <i class="fas fa-map-marker-alt"></i>
                                </span>
                            </div>
                            <div class="col-md-8 text-center pt-1">
                                <h3>Installation</h3>
                                <div class="text-block text-left">Installation of sign posts at one of your listings</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 text-center pt-3">
                <div class="card border-orange service-card">
                    <div class="card-body py-4">
                        <div class="row">
                            <div class="col-md-4 text-center">
                                <span class="icon-wrapper">
                                    <i class="fas fa-tools"></i>
                                </span>
                            </div>
                            <div class="col-md-8 text-center pt-1">
                                <h3>Repair</h3>
                                <div class="text-block text-left">Repair an existing sign post from one of your listings
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 text-center pt-3">
                <div class="card border-orange service-card">
                    <div class="card-body py-4">
                        <div class="row">
                            <div class="col-md-4 text-center">
                                <span class="icon-wrapper">
                                    <i class="far fa-arrow-alt-circle-down"></i>
                                </span>
                            </div>
                            <div class="col-md-8 text-center pt-1">
                                <h3>Removal</h3>
                                <div class="text-block text-left">Remove sign post from one of your listings
                                    <span style="visibility: hidden;">ssdsd</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-center mt-3">
            <a href="{{ url('/our-services') }}" class="btn btn-dark-blue btn-lg">
                See All Services &nbsp;<i class="fas fa-long-arrow-alt-right pt-1"></i>
            </a>
        </div>
    </div>

    {{-- What our customers say --}}
    @include('feedbacks_section')

    {{-- Lastest news --}}
    @include('news_section')

@endsection
