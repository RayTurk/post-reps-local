<div class="modal fade" id="exportToCsvModal" tabindex="-1" aria-labelledby="exportToCsvModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content auth-card">
        <div class="modal-header">
          <h5 class="modal-title" id="exportToCsvModalLabel">Export to CSV</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
            <form action="{{ url('/export/payments-csv') }}" method="GET">
                @csrf
                <div class="form-group form-inline">
                    <label for="selectOffice">OFFICE: </label>
                    <select id="selectOffice" name="export_to_csv_office" class="form-control w-75 ml-2" required>
                        <option value=""></option>
                        @if ($offices->isNotEmpty())
                            @foreach ($offices as $office)
                                <option value="{{ $office->id }}">{{ $office->user->name }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                </div>
                <div class="form-group form-inline">
                    <label for="selectAgent">AGENT: </label>
                    <select id="selectAgent" name="export_to_csv_agent" class="form-control w-75 ml-2">
                    {{-- <option>Disabled select</option> --}}
                    </select>
                </div>

                <h6 class="text-center mt-4">DATE RANGE</h6>
                <div class="form-group form-inline w-50 mx-auto">
                    <label for="from_date">FROM</label>
                    <input type="text" class="form-control form-control-sm ml-1" name="from_date"
                        id="from_date" required autocomplete="off">
                </div>
                <div class="form-group form-inline w-50 mx-auto">
                    <label for="to_date" class="pl-4">TO</label>
                    <input type="text" class="form-control form-control-sm ml-1" name="to_date"
                        id="to_date" required autocomplete="off">
                </div>

                <div class="form-group text-center mt-4">
                    {{-- <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button> --}}
                    <button type="submit" class="btn btn-orange text-white font-weight-bold rounded-pill export-to-csv">EXPORT TO CSV</button>
                </div>
            </form>
        </div>
      </div>
    </div>
  </div>