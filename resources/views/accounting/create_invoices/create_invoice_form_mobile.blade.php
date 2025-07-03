<form class="create-invoice-form" action="{{ url('/accounting/create/invoices') }}" method="POST">
    @csrf
    <div class="form-group row">
        <div class="col-sm-6">

            <div class="form-check">
                <input class="form-check-input" type="radio" name="process_all_accounts"
                    id="allAccountsRadioMobile" value="1" checked>
                <label class="form-check-label font-weight-bold" for="allAccountsRadioMobile">
                    Process for All Accounts
                </label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="process_all_accounts"
                    id="individualAccountRadioMobile" value="0">
                <label class="form-check-label font-weight-bold"
                    for="individualAccountRadioMobile">
                    Process for Individual Account
                </label>
            </div>

            <div class="form-group form-inline">
                <label for="office">OFFICE:</label>
                <select name="create_invoice_office"
                    class="form-control form-control-sm w-75 ml-3" id="createInvoiceOffice" required>
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
                <label for="agent">AGENT:</label>
                <select name="create_invoice_agent"
                    class="form-control form-control-sm w-75 ml-3" id="createInvoiceAgent">
                    <option value=""></option>
                </select>
            </div>

            <div class="form-group form-inline ml-4 mt-3">
                <input type="checkbox" name="send_invoice_email" id="sendInvoiceEmail" class="form-control w-h-px-25 mr-1">
                <label for="agent">Send Invoice Email Notification</label>
            </div>
        </div>
        <div class="col-sm-6 text-center">
            <h6 class="">DATE RANGE</h6>
            <div class="form-group form-inline">
                <label for="from_date">FROM</label>
                <input type="text" class="form-control form-control-sm " name="from_date"
                    id="from_date_mobile" required autocomplete="off">
            </div>
            <div class="form-group form-inline">
                <label for="to_date" class="pr-4">TO</label>
                <input type="text" class="form-control form-control-sm " name="to_date"
                    id="to_date_mobile" required autocomplete="off">
            </div>

            <div class="text-center">
                <button class="btn btn-orange text-white rounded-pill font-weight-bold"
                    type="submit">CREATE ORDER INVOICES</button>
            </div>
        </div>
    </div>
</form>
