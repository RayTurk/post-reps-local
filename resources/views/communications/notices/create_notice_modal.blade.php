<div class="modal fade" id="noticeModal" data-keyboard="true" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content auth-card">
            <div class="modal-header">
                <h5 class="modal-title"></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="{{ route('communications.notices.store') }}" enctype="multipart/form-data" method="post" id="noticeForm">
                    @csrf
                    <div class="row">
                        {{-- Start date --}}
                        <div class="col-6 form-group">
                            <label class="col-form-label" for="start-date">Start Date</label>
                            <input class="form-control" type="text" name="start_date" id="start_date" value="{{ old('start_date') }}" autocomplete="off" required>
                        </div>
                        {{-- End date --}}
                        <div class="col-6 form-group">
                            <label class="col-form-label" for="end-date">End Date</label>
                            <input class="form-control" type="text" name="end_date" id="end_date" value="{{ old('end_date') }}" autocomplete="off" required>
                        </div>
                        {{-- Subject --}}
                        <div class="col-12 mt-4 form-group">
                            <label class="col-form-label" for="subject">Subject</label>
                            <input class="form-control" type="text" name="subject" id="subject" value="{{ old('subject') }}" required>
                        </div>
                        {{-- Details --}}
                        <div class="col-12 mt-4 form-group">
                            <label class="col-form-label" for="details">Notice Details</label>
                            <textarea class="form-control" name="details" id="details" cols="30" rows="10" required>{{ old('details') }}</textarea>
                        </div>
                        {{-- Footer --}}
                        <div class="col-12 mt-4 form-group d-flex justify-content-end">
                            <button type="submit" class="btn btn-success mr-4 pr-4 pl-4">Submit</button>
                            <button type="button" class="btn btn-secondary pr-4 pl-4" data-dismiss="modal">Cancel</button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
            </div>
        </div>
    </div>
</div>
