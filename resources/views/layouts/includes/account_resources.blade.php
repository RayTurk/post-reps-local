<div class="bar-title pt-2">ACCOUNT RESOURCES</div>

<div class="row resources-bar-menu-item mt-3 order-status" title="Orders">
    <div class="col-md-2 img-div">
        <img src="{{ asset('/images/Orders_Icon.png') }}">
    </div>
    <div class="col-md-10 pr-3 pt-3">
        <span class="item-title text-div">ORDERS</span>
    </div>
</div>

<a href="{{ url('/accounting') }}" title="Accounting">
    <div class="row resources-bar-menu-item mt-3 accounting">
        <div class="col-md-2 img-div">
            <img src="{{ asset('/storage/images/Financial_Icon.png') }}">
        </div>
        <div class="col-md-10 pr-3 pt-3">
            <span class="item-title text-div">ACCOUNTING</span>
        </div>
    </div>
</a>
@can('Admin', auth()->user())
<a href="{{ route('services.index') }}" title="Services">
    <div class="row resources-bar-menu-item mt-3 service-settings">
        <div class="col-md-2 img-div">
            <img src="{{ asset('/storage/images/Area_Icon.png') }}">
        </div>
        <div class="col-md-10 pr-3 pt-3">
            <span class="item-title text-div">SERVICES</span>
        </div>
    </div>
</a>

<a href="{{ route('users.index') }}" title="Users">
    <div class="row resources-bar-menu-item mt-3 users">
        <div class="col-md-2 img-div">
            <img src="{{ asset('/storage/images/Users_Icon.png') }}">
        </div>
        <div class="col-md-10 pr-3 pt-3">
            <span class="item-title text-div">USERS</span>
        </div>
    </div>
</a>

<a href="{{ route('inventories.index') }}" title="Inventory">
    <div class="row resources-bar-menu-item mt-3 inventory">
        <div class="col-md-2 img-div">
            <img src="{{ asset('/storage/images/Inventory_Icon.png') }}">
        </div>
        <div class="col-md-10 pr-3 pt-3">
            <span class="item-title text-div">INVENTORY</span>
        </div>
    </div>
</a>

<a href="{{ url('communications/notices') }}" title="Communications">
    <div class="row resources-bar-menu-item mt-3 communication">
        <div class="col-md-2 img-div">
            <img src="{{ asset('/images/Com_Icon.png') }}">
        </div>
        <div class="col-md-10 pr-3 pt-3">
            <span class="item-title text-div">COMMUNICATIONS</span>
        </div>
    </div>
</a>

@elsecan('Office', auth()->user())

<a href="{{ route('office.users.index') }}" title="Users">
    <div class="row resources-bar-menu-item mt-3 users">
        <div class="col-md-2 img-div">
            <img src="{{ asset('/storage/images/Users_Icon.png') }}">
        </div>
        <div class="col-md-10 pr-3 pt-3">
            <span class="item-title text-div">USERS</span>
        </div>
    </div>
</a>

<a href="{{ route('office.inventories.index') }}" title="Inventory">
    <div class="row resources-bar-menu-item mt-3 inventory">
        <div class="col-md-2 img-div">
            <img src="{{ asset('/storage/images/Inventory_Icon.png') }}">
        </div>
        <div class="col-md-10 pr-3 pt-3">
            <span class="item-title text-div">INVENTORY</span>
        </div>
    </div>
</a>

@elsecan('Agent', auth()->user())

<a href="{{ route('agent.inventories.index') }}" title="Inventory">
    <div class="row resources-bar-menu-item mt-3 inventory">
        <div class="col-md-2 img-div">
            <img src="{{ asset('/storage/images/Inventory_Icon.png') }}">
        </div>
        <div class="col-md-10 pr-3 pt-3">
            <span class="item-title text-div">INVENTORY</span>
        </div>
    </div>
</a>


@endCan
<a href="{{ url('contact-us') }}" class="row resources-bar-menu-item mt-3 contact-us" title="Contact Us">
    <div class="col-md-2 img-div">
        <img src="{{ asset('/storage/images/Question_Icon.png') }}">
    </div>
    <div class="col-md-10 pr-3 pt-3">
        <span class="item-title text-div">CONTACT US</span>
    </div>
</a>

<a href="{{ url('/settings') }}" class="row resources-bar-menu-item mt-3 account-settings" title="Settings">
    <div class="col-md-2 img-div">
        <img src="{{ asset('/storage/images/settings_Icon.png') }}">
    </div>
    <div class="col-md-10 pr-3 pt-3">
        <span class="item-title text-div">SETTINGS</span>
    </div>
</a>

<!-- <a href="{{ url('regions') }}" title="Regions">
    <div class="row resources-bar-menu-item mt-3 inventory">
        <div class="col-md-2 img-div">
            <img src="{{ asset('/storage/images/Region.png') }}">
        </div>
        <div class="col-md-10 pr-3 pt-3">
            <span class="item-title text-div">REGIONS</span>
        </div>
    </div>
</a> -->
