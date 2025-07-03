@if ($order->panels->count())
    @foreach($order->panels as $deliveryPanel)
        <div class="row px-3 mt-3">
            <div class="col-2 pt-4">
                <input
                    type="checkbox"
                    class="form-control w-h-px-30 delivery-items"
                    data-item-id="{{$deliveryPanel->panel->id}}"
                    data-action="{{$deliveryPanel->pickup_delivery == $order::PICKUP ? 'pickup' : 'dropoff'}}"
                    checked
                >
                <span class="fa fa-times delivery-out-of-inventory d-none"></span>
            </div>
            <div class="col-2 pl-0">
                <img
                    src="{{url('/private/image/panel/')}}/{{$deliveryPanel->panel->image_path}}"
                    alt="{{$deliveryPanel->panel->panel_name}}"
                    style="max-width: 4.6rem; max-height: 4.8rem;"
                >
            </div>
            <div class="col-6 pt-4">
                <span class="font-px-16">{{$deliveryPanel->panel->panel_name}}</span>
                <span class="font-px-14 font-weight-bold">
                    @if ($deliveryPanel->panel->id_number)
                        ID#{{$deliveryPanel->panel->id_number}}
                    @endif
                    @if ($deliveryPanel->pickup_delivery == $order::PICKUP)
                        &nbsp;(pick up)
                    @else
                        &nbsp;(drop off)
                    @endif
                </span>
            </div>
            <div class="col-2 text-center">
                <label class="text-dark">Qty</label><br>
                <input
                    type="number"
                    class="form-control width-px-55 height-px-30 text-center"
                    data-qty-item-id="{{$deliveryPanel->panel->id}}"
                    data-action="{{$deliveryPanel->pickup_delivery = $order::PICKUP ? 'pickup' : 'dropoff'}}"
                    value="{{$deliveryPanel->quantity}}"
                >
            </div>
        </div>
    @endforeach
@endif
