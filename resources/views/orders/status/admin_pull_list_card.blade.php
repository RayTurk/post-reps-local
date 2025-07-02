<div class="container-fluid text-center height-px-30 pt-1 font-px-16 font-weight-bold text-white"
    style="background-color: #4f5866"
>
POSTS
</div>
@if (count($pullList['postsPullList']))
    @foreach ($pullList['postsPullList'] as $postsPullList)
        @if ( isset($postsPullList['post_name']) )
            <div class="row px-3 mt-2 mb-0">
                <div class="col-md-1 col-1 pt-4">
                    <input
                        type="checkbox"
                        class="form-control w-h-px-30 pull-list-item"
                        data-name= "{{$postsPullList['post_name']}}"
                    >
                </div>
                <div class="col-md-1 col-3 text-right">
                    <span class="font-weight-bold font-px-14">QTY</span>
                    <span class="font-weight-bold font-px-42">
                        {{$postsPullList['post_qty']}}
                    </span>
                </div>
                <div class="col-md-2 col-2 pl-0 text-center">
                    <img
                        src="{{url('/private/image/post/')}}/{{$postsPullList['image_path']}}"
                        alt="{{$postsPullList['post_name']}}"
                        style="max-width: 4.6rem; max-height: 4.8rem;"
                    >
                </div>
                <div class="col-5">
                    <span class="font-weight-bold font-px-22">
                        {{$postsPullList['post_name']}}
                    </span><br>
                    <span class="font-weight-bold font-px-17">
                        Inventory: {{$postsPullList['inventory']}}
                    </span><br>
                    <span class="font-px-16">
                        Removals: {{$postsPullList['removal_qty']}}
                    </span>
                </div>
            </div>
        @endif

        @if ( ! $loop->last)
            <div class="row pt-0" style="margin-top: -15px; margin-bottom: -15px;">
                <div class="col-12 px-4 pt-0">
                    <hr style="border-top: 2px solid #4f5866;">
                </div>
            </div>
        @endif
    @endforeach
@endif

<div
    class="container-fluid text-center height-px-30 pt-1 font-px-16 font-weight-bold text-white"
    style="background-color: #4f5866"
>
    SIGNS
</div>
@if (count($pullList['signsPullList']))
    @foreach ($pullList['signsPullList'] as $signsPullList)
        @if ( isset($signsPullList['panel_name']) )
            <div class="row px-3 mt-1 mb-0">
                <div class="col-md-1 col-1 pt-4">
                    <input
                        type="checkbox"
                        class="form-control w-h-px-30 pull-list-item"
                        data-name= "{{$signsPullList['panel_name']}}"
                    >
                </div>
                <div class="col-md-1 col-3 text-right">
                    <span class="font-weight-bold font-px-14">QTY</span>
                    <span class="font-weight-bold font-px-42">
                        {{$signsPullList['panel_qty']}}
                    </span>
                </div>
                <div class="col-md-2 col-2 pl-0 text-center">
                    <img
                        src="{{url('/private/image/panel/')}}/{{$signsPullList['image_path']}}"
                        alt="{{$signsPullList['panel_name']}}"
                        style="max-width: 4.6rem; max-height: 4.8rem;"
                    >
                </div>
                <div class="col-5">
                    <span class="font-weight-bold font-px-22">
                        {{$signsPullList['panel_name']}}
                    </span>
                    @if ($signsPullList['panel_id_number'])
                        <br>
                        <h2 style="font-size: 1.3rem;">ID: {{$signsPullList['panel_id_number']}}</h2>
                    @endif
                    @if (! $signsPullList['panel_id_number'])
                        <br>
                    @endif
                    <span class="font-weight-bold font-px-17">
                        Inventory: {{$signsPullList['inventory']}}
                    </span><br>
                    <span class="font-px-16">
                        Removals: {{$signsPullList['removal_qty']}}
                    </span>
                </div>
            </div>
        @endif

        @if ( ! $loop->last)
            <div class="row pt-0 pb-0" style="margin-top: -15px; margin-bottom: -15px;">
                <div class="col-12 px-4 pt-0 pb-0">
                    <hr style="border-top: 2px solid #4f5866;">
                </div>
            </div>
        @endif
    @endforeach
@endif

<div
    class="container-fluid text-center height-px-30 pt-1 font-px-16 font-weight-bold text-white"
    style="background-color: #4f5866"
>
    ACCESSORIES
</div>
@if (count($pullList['accessoriesPullList']))
    @foreach ($pullList['accessoriesPullList'] as $accessoriesPullList)
        @if ( isset($accessoriesPullList['accessory_name']) )
            <div class="row px-3 mt-1 mb-0">
                <div class="col-md-1 col-1 pt-4">
                    <input
                        type="checkbox"
                        class="form-control w-h-px-30 pull-list-item text-right"
                        data-name= "{{$accessoriesPullList['accessory_name']}}"
                    >
                </div>
                <div class="col-md-1 col-3 text-right">
                    <span class="font-weight-bold font-px-14">QTY</span>
                    <span class="font-weight-bold font-px-42">
                        {{$accessoriesPullList['accessory_qty']}}
                    </span>
                </div>
                <div class="col-md-2 col-2 pl-0 text-center">
                    <img
                        src="{{url('/private/image/accessory/')}}/{{$accessoriesPullList['image_path']}}"
                        alt="{{$accessoriesPullList['accessory_name']}}"
                        style="max-width: 4.6rem; max-height: 4.8rem;"
                    >
                </div>
                <div class="col-5">
                    <span class="font-weight-bold font-px-22">
                        {{$accessoriesPullList['accessory_name']}}
                    </span>
                    @if ( ! $accessoriesPullList['show_agent_office'])
                        <br>
                        <span class="font-px-16">
                            Removals: {{$accessoriesPullList['removal_qty']}}
                        </span>
                    @else
                        @foreach ($accessoriesPullList['agent_office_list'] as $list)
                            @if (isset($list['name']) && isset($list['accessory_qty']))
                                <br>
                                <span class="font-px-16">
                                    {{$list['accessory_qty']}} - {{$list['name']}}
                                    @if (isset($list['removal_qty']))
                                        ({{$list['removal_qty']}})
                                    @else
                                        (0)
                                    @endif
                                </span>
                            @endif
                        @endforeach
                    @endif
                </div>
            </div>
        @endif

        @if ( ! $loop->last)
            <div class="row pt-0 pb-0" style="margin-top: -15px; margin-bottom: -15px;">
                <div class="col-12 px-4 pt-0 pb-0">
                    <hr style="border-top: 2px solid #4f5866;">
                </div>
            </div>
        @endif
    @endforeach
@endif
