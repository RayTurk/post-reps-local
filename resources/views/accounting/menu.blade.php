<div class="d-flex justify-content-start overflow-x-auto overflow-y-auto">
    @can('Admin', auth()->user())
    <a
        href="{{url('/accounting/')}}"
        class="order-tab-active btn btn-primary btn-sm width-px-200 font-weight-bold font-px-17"
        id="accountingAnalytics"
    >Accounting Analytics</a>
    @endCan
    <a
        href="{{url('/accounting/unpaid/invoices')}}"
        class="{{auth()->user()->role == auth()->user()::ROLE_OFFICE ? 'order-tab-active' : ''}} btn btn-primary btn-sm ml-1 width-px-200 font-weight-bold font-px-17"
        id="accountingUnpaidInvoices"
    >Unpaid Invoices</a>
    <a
        href="{{url('/accounting/payments')}}"
        class="btn btn-primary btn-sm ml-1 width-px-150 font-weight-bold font-px-17"
        id="accountingPayments"
    >Payments</a>
    @cannot('Admin', auth()->user())
    <a
        href="{{url('/accounting/manage-cards')}}"
        class="btn btn-primary btn-sm ml-1 width-px-200 font-weight-bold font-px-17"
        id="accountingManageCards"
    >Manage Credit Cards</a>
    @endcannot
    @can('Admin', auth()->user())
    <a
        href="{{url('/accounting/create/invoices')}}"
        class="btn btn-primary btn-sm ml-1 width-px-150 font-weight-bold font-px-17"
        id="accountingCreateInvoices"
    >Create Invoices</a>
    <a
        href="{{url('/accounting/transaction/summary')}}"
        class="btn btn-primary btn-sm ml-1 width-px-200 font-weight-bold font-px-17"
        id="accountingTransactionSummary"
    >Transaction Summary</a>
    <a
        href="{{url('/accounting/settings')}}"
        class="btn btn-primary btn-sm ml-1 width-px-200 font-weight-bold font-px-17"
        id="accountingSettings"
    >Settings</a>
    <a
        href="{{url('/accounting/installer-points')}}"
        class="btn btn-primary btn-sm ml-1 width-px-200 font-weight-bold font-px-17"
        id="accountingInstallerPoints"
    >Installer Points</a>
    @endCan
</div>
