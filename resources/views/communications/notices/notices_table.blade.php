<table class="table table-borderless">
    <thead>
      <tr>
        <th scope="col"></th>
        <th scope="col" class="text-left h5 font-weight-bold width-px-120">Start Date</th>
        <th scope="col" class="text-left h5 font-weight-bold width-px-140">End Date</th>
        <th scope="col" class="text-left h5 font-weight-bold width-px-150">Subject</th>
        <th scope="col" class="text-left h5 font-weight-bold">Notice Details</th>
      </tr>
    </thead>
    <tbody>
        {{-- ACTIVE NOTICES --}}
        @if ($notices->isNotEmpty())
            @foreach ($notices as $notice)
                <tr>
                    <th class="align-middle width-px-50" scope="row" id="deleteNotice" data-noticeid="{{ $notice->id }}">
                        <form action="" method="POST" id="deleteForm">
                            @csrf
                            @method("DELETE")
                            <input type="hidden" name="id" value="{{ $notice->id }}">
                            <button type="submit" class="btn"><i class="fas fa-trash-alt text-danger"></i></button>
                        </form>
                    </th>
                    <td data-toggle="modal" data-target="#noticeModal" data-noticeid="{{ $notice->id }}" id="editNotice" class="border border-2 table-light border-right-0 border-primary">{{ $notice->start_date->format('m/d/Y') }}</td>
                    <td data-toggle="modal" data-target="#noticeModal" data-noticeid="{{ $notice->id }}" id="editNotice" class="border border-2 table-light border-right-0 border-left-0 border-primary">{{ $notice->end_date->format('m/d/Y') }}</td>
                    <td data-toggle="modal" data-target="#noticeModal" data-noticeid="{{ $notice->id }}" id="editNotice" class="border border-2 table-light border-right-0 border-left-0 border-primary">{{ $notice->subject }}</td>
                    <td data-toggle="modal" data-target="#noticeModal" data-noticeid="{{ $notice->id }}" id="editNotice" class="border border-2 table-light border-right-1 border-left-0 border-primary">{{ Str::words($notice->details, 35, ">>>") }}</td>
                </tr>
            @endforeach
        @endif

        @if ($expiredNotices->isNotEmpty())
            @if ($notices->isNotEmpty())
                <tr>
                    <td colspan="5"><hr class="solid"/></td>
                </tr>
            @endif
            {{-- EXPIRED NOTICES --}}
            @foreach ($expiredNotices as $expiredNotice)
                <tr>
                    <th id="deleteNotice" data-noticeid="{{ $expiredNotice->id }}" class="align-middle width-px-50" scope="row">
                        <form action="" method="POST" id="deleteForm">
                            @csrf
                            @method("DELETE")
                            <button type="submit" class="btn"><i class="fas fa-trash-alt text-danger"></i></button>
                        </form>
                    </th>
                    <td data-toggle="modal" data-target="#noticeDetailsModal" data-noticeid="{{ $expiredNotice->id }}" id="loadNoticeDetails" class="border border-2 table-secondary border-right-0 border-primary">{{ $expiredNotice->start_date->format('m/d/Y') }}</td>
                    <td data-toggle="modal" data-target="#noticeDetailsModal" data-noticeid="{{ $expiredNotice->id }}" id="loadNoticeDetails" class="border border-2 table-secondary border-right-0 border-left-0 border-primary">{{ $expiredNotice->end_date->format('m/d/Y') }}</td>
                    <td data-toggle="modal" data-target="#noticeDetailsModal" data-noticeid="{{ $expiredNotice->id }}" id="loadNoticeDetails" class="border border-2 table-secondary border-right-0 border-left-0 border-primary">{{ $expiredNotice->subject }}</td>
                    <td data-toggle="modal" data-target="#noticeDetailsModal" data-noticeid="{{ $expiredNotice->id }}" id="loadNoticeDetails" class="border border-2 table-secondary border-right-1 border-left-0 border-primary w-50">{{ Str::words($expiredNotice->details, 35, ">>>") }}</td>
                </tr>
            @endforeach
        @endif
    </tbody>
</table>
