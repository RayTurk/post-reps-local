<!-- Modal -->
<div class="modal" id="noticeAcknowledgement" tabindex="-1" aria-labelledby="noticeAcknowledgement" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-orange" style="border-radius: 15px;">
            <div class="modal-header text-center bg-orange" style="border-top-left-radius: 10px; border-top-right-radius: 10px;">
                <h5 class="modal-title font-weight-bold text-uppercase text-white w-100" id="noticeAcknowledgementSubject">{{ $latestNotice->subject }}</h5>
            </div>
            <div class="modal-body" id="noticeAcknowledgementDetails">
                {{ $latestNotice->details }}
            </div>
            <div class="text-center">
                <button type="button" class="btn btn-primary font-weight-bold text-justify mb-4" data-dismiss="modal" id="understoodButton">UNDERSTOOD</button>
                <input type="hidden" value="{{$latestNotice->id}}" id="noticeAcknowledgementId">
            </div>
        </div>
    </div>
</div>