<div class="row mt-3" style="margin-right: 0; z-index: 100;">
    <div class="col-md-3"></div>

    <div class="col-md-3 col-12">
        @php
            use Carbon\Carbon;
            $today = Carbon::now();
            $tomorrow = Carbon::tomorrow();
            $formattedToday = 'Today - ' . $today->format('F jS, Y');
            $formattedTomorrow = 'Tomorrow - ' . $tomorrow->format('F j, Y');
            $todayVal = $today->format('Y-m-d');
            $tomorrowVal = $tomorrow->format('Y-m-d');

            //Get route date from selected date in Routes page
            if ( session('routeDate') !== null ) {
                $routeDate = session('routeDate');
            } else {
                $routeDate = $todayVal;
            }

            $installerId = 0;
            if ( session('installerId') !== null ) {
                $installerId = session('installerId');
            }
        @endphp
        <select class="pull-list-date-select form-control mt-1 mb-1 font-px-16">
            <option value="{{$todayVal}}" {{$routeDate == $todayVal ? 'selected' : ''}}>{{$formattedToday}}</option>
            <option value="{{$tomorrowVal}}" {{$routeDate == $tomorrowVal ? 'selected' : ''}}>{{$formattedTomorrow}}</option>
            @php
                for ($i = 0; $i < 5; $i++) {
                    // Increment the date by one more day
                    $dayAfterTomorrow = Carbon::tomorrow()->addDay();
                    $nextDate = $dayAfterTomorrow->addDay($i);

                    // Format the date as "Day, Month Day, Year"
                    $formattedDate = $nextDate->format('l, F jS, Y');
                    $value = $nextDate->format('Y-m-d');
                    $isSelected = $routeDate == $value ? "selected" : "";

                    echo '<option value="' . $value . '"' . $isSelected .'>' . $formattedDate . '</option>';
                }
            @endphp
        </select>
    </div>

    <div class="col-md-3 col-12">
        <select class="pull-list-installer-select form-control mt-1 mb-1 font-px-16">
            <option value="0" >All Routes</option>
            @if ($installers->isNotEmpty())
                @foreach ($installers as $installer)
                    <option value="{{$installer->id}}" {{$installerId == $installer->id ? 'selected' : ''}}>
                        {{$installer->name}} ({{substr($installer->first_name,0,1)}}{{substr($installer->last_name,0,1)}})
                    </option>
                @endforeach
            @endif
        </select>
    </div>

    <div class="col-md-3"></div>
</div>
