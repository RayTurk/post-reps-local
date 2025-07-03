<div class="reources-bar-icons">
    <a href="{{ url('/order/status') }}" title="Orders">
        <div class="row resources-bar-menu-item mt-3 order-status">
            <div class="col-md-2 text-center">
                <img src="{{ asset('/images/Orders_Icon.png') }}">
            </div>
        </div>
    </a>

    <a href="{{ url('/accounting') }}" title="Accounting">
        <div class="row resources-bar-menu-item mt-3 accounting">
            <div class="col-md-2 text-center">
                <img src="{{ asset('/storage/images/Financial_Icon.png') }}">
            </div>
        </div>
    </a>
    @can('Admin', auth()->user())
    <a href="{{ route('services.index') }}" title="Services">
        <div class="row resources-bar-menu-item mt-3 service-settings">
            <div class="col-md-2 text-center">
                <img src="{{ asset('/storage/images/Area_Icon.png') }}">
            </div>
        </div>
    </a>

    <div class="row resources-bar-menu-item mt-3 users">
        <a href="{{ route('users.index') }}" title="Users">
            <div class="col-md-2 text-center">
                <img src="{{ asset('/storage/images/Users_Icon.png') }}">
            </div>
        </a>
    </div>

    <a href="{{ route('inventories.index') }}" title="Inventory">
        <div class="row resources-bar-menu-item mt-3 inventory">
            <div class="col-md-2 text-center">
                <img src="{{ asset('/storage/images/Inventory_Icon.png') }}">
            </div>
        </div>
    </a>

    <a href="{{ url('communications/notices') }}" title="Communications">
        <div class="row resources-bar-menu-item mt-3 communication">
            <div class="col-md-2 text-center">
                <img src="{{ asset('/images/Com_Icon.png') }}">
            </div>
        </div>
    </a>

    @elsecan('Office', auth()->user())

    <a href="{{ route('office.users.index') }}" title="Users">
        <div class="row resources-bar-menu-item mt-3 users">
            <div class="col-md-2 text-center">
                <img src="{{ asset('/storage/images/Users_Icon.png') }}">
            </div>
        </div>
    </a>

    <a href="{{ route('office.inventories.index') }}" title="Inventory">
        <div class="row resources-bar-menu-item mt-3 inventory">
            <div class="col-md-2 text-center">
                <img src="{{ asset('/storage/images/Inventory_Icon.png') }}">
            </div>
        </div>
    </a>
    
    @elsecan('Agent', auth()->user())
    
    <a href="{{ route('agent.inventories.index') }}" title="Inventory">
        <div class="row resources-bar-menu-item mt-3 inventory">
            <div class="col-md-2 text-center">
                <img src="{{ asset('/storage/images/Inventory_Icon.png') }}">
            </div>
        </div>
    </a>  

    @endCan
    <a href="{{ url('/contact-us') }}" class="row resources-bar-menu-item mt-3 contact-us" title="Contact Us">
        <div class="col-md-2 text-center">
            <img src="{{ asset('/storage/images/Question_Icon.png') }}">
        </div>
    </a>

    <a href="{{ url('/settings') }}" class="row resources-bar-menu-item mt-3 account-settings" title="Settings">
        <div class="col-md-2 text-center">
            <img src="{{ asset('/storage/images/settings_Icon.png') }}">
        </div>
    </a>

<!--     <a href="{{ url('regions') }}" title="Regions">
        <div class="row resources-bar-menu-item mt-3 inventory">
            <div class="col-md-2 img-div">
                <img src="{{ asset('/storage/images/Region.png') }}">
            </div>
        </div>
    </a> -->
</div>
