<div class="table-responsive">
    <table class="table table-hover">
        <thead>
            <th class="width-px-50">FUTURE TRANSFERS</th>
            <th class="width-px-150">Date</th>
            <th class="width-px-50">Amount</th>
        </thead>
        <tbody>
            @if ($futureTransactions->isNotEmpty())
                @php $limit = 0; @endphp
                @foreach ($futureTransactions as $transaction)
                    @php $limit++; @endphp
                    <tr>
                        <td></td>
                        <td class="text-center">
                            {{$transaction->settlement_date->format('m/d/Y')}}
                        </td>
                        <td class="text-center"> ${{$transaction->total}}</td>
                    </tr>
                @endforeach
            @endif
        </tbody>
        @if (isset($limit) && $limit > 10)
            @php $limit = $limit + 10; @endphp
            <tfoot>
                <tr>
                    <td><a href="{{url('/accounting/transaction/summary/')}}/{{$limit}}" class="btn btn-primary text-white">Load more</a></td>
                </tr>
            </tfoot>
        @endif
    </table>
</div>

<div class="table-responsive mt-4">
    <table class="table table-hover">
        <thead>
        <th class="width-px-50">RECENT TRANSFERS</th>
            <th class="width-px-150">Date</th>
            <th class="width-px-50">Amount</th>
        </thead>
        <tbody>
            @if ($recentTransactions->isNotEmpty())
                @php $limit = 0; @endphp
                @foreach ($recentTransactions as $transaction)
                    @php $limit++; @endphp
                    <tr>
                        <td></td>
                        <td class="text-center">
                            {{$transaction->settlement_date->format('m/d/Y')}}
                        </td>
                        <td class="text-center"> ${{$transaction->total}}</td>
                    </tr>
                @endforeach
            @endif
        </tbody>
        @if (isset($limit) && $limit > 10)
            @php $limit = $limit + 10; @endphp
            <tfoot>
                <tr>
                    <td><a href="{{url('/accounting/transaction/summary/')}}/{{$limit}}" class="btn btn-primary text-white">Load more</a></td>
                </tr>
            </tfoot>
        @endif
    </table>
</div>

