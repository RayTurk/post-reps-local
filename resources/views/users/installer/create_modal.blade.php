<!-- Button trigger modal -->
<!-- Modal -->
<form id="createInstallerForm" method="POST" action="{{ url('installer/store') }}" files=true
    enctype="multipart/form-data">

    <div class="modal fade" id="createInstallerFormModal" tabindex="-1" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content auth-card">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Create Installer</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    @csrf
                    <div class="row mt-1">
                        <div class="col-12 col-md-6 col-lg-6">
                            <div class="form-group">
                                <div>
                                    <label for="first_name"><b>First Name</b></label>
                                    <input type="text" id="first_name" tabindex="1"
                                        class="form-control  @error('first_name') is-invalid @enderror" name="first_name"
                                        value="{{ old('first_name') }}" required>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-6 col-lg-6">
                            <div class="form-group">
                                <div>
                                    <label for="last_name"><b>Last Name</b></label>
                                    <input type="text" id="last_name" tabindex="2"
                                        class="form-control  @error('last_name') is-invalid @enderror"
                                        name="last_name" value="{{ old('last_name') }}">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-1">
                        <div class="col-12 col-md-6 col-lg-6">
                            <div class="form-group">
                                <div>
                                    <label for="address"><b>Street Address</b></label>
                                    <input type="text" id="street_address" tabindex="3"
                                        class="form-control  @error('address') is-invalid @enderror" placeholder=""
                                        name="address" required value="{{ old('address') }}">
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-6 col-lg-6">
                            <div class="form-group">
                                <div>
                                    <label for="email"><b>Email</b></label>
                                    <input type="email" id="email" tabindex="7"
                                        class="form-control @error('email') is-invalid @enderror " placeholder=""
                                        name="email" required value="{{ old('email') }}">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-1">
                        <div class="col-12 col-md-6 col-lg-6">
                            <div class="form-group">
                                <div>
                                    <label for="city"><b>City</b></label>
                                    <input type="text" id="city" tabindex="4"
                                        class="form-control  @error('city') is-invalid @enderror" placeholder=""
                                        name="city" required value="{{ old('city') }}">
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-6 col-lg-6">

                            <div class="form-group">
                                <div>
                                    <label for="phone"><b>Phone Number</b></label>
                                    <input type="text" id="phone" tabindex="8"
                                        class="form-control  @error('phone') is-invalid @enderror phones" name="phone"
                                        required value="{{ old('phone') }}">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-1">
                        <div class="col-12 col-md-3 col-lg-3">
                            <div class="form-group">
                                <div>
                                    <label for="state"><b>State</b></label>
                                    <select id="state" required tabindex="5"
                                        class="form-control  @error('state') is-invalid @enderror" placeholder=""
                                        name="state">
                                        <option value=""></option>
                                        @if (count($states))
                                            @foreach ($states as $code => $state)
                                                <option value="{{ $code }}" @if (old('state') === $code or $code === 'ID') selected @endif>
                                                    {{ $state }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-3 col-lg-3">
                            <div class="form-group">
                                <div>
                                    <label for="email"><b>Zipcode</b></label>
                                    <input type="text" id="zipcode" tabindex="6"
                                        class="form-control @error('zipcode') is-invalid @enderror zipcode"
                                        name="zipcode" value="{{ old('zipcode') }}" required />
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-3 col-lg-3">
                            <div class="form-group">
                                <div>
                                    <label for="hire_date"><b>Hire Date</b></label>
                                    <input type="text" id="hire_date" tabindex="9"
                                        class="form-control @error('hire_date') is-invalid @enderror date-input"
                                        name="hire_date"
                                        value="{{ old('hire_date') }}" />
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-3 col-lg-3 form-group">
                            <label for="pay_rate"><b>Pay Rate</b></label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">$</span>
                                </div>
                                <input type="number" id="pay_rate" tabindex="9"
                                    class="text-right form-control @error('pay_rate') is-invalid @enderror"
                                    name="pay_rate"
                                    value="{{ old('pay_rate') }}"
                                    step="0.01"
                                />
                            </div>
                        </div>
                    </div>
                    <div class="row mt-1">
                        <div class="col-12 col-md-3 col-lg-3">
                            <div class="form-group">
                                <div>
                                    <label for="inactive"><b>Account Status</b></label>
                                    <select id="inactive" class="form-control  @error('inactive') is-invalid @enderror"
                                        name="inactive" tabindex="10">
                                        <option value="0" @if (old('inactive') == 0) selected @endif><b class="text-muted">Active</b>
                                        </option>
                                        <option value="1" @if (old('inactive') == 1) selected @endif><b
                                                class="text-muted">Inactive</b></option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-3 col-lg-3">
                            <div class="form-group">
                                <div>
                                    <label for="routing_color"><b>Routing Color</b></label>
                                    <input type="color" id="routing_color" tabindex="9"
                                        class="width-px-55 p-1 form-control @error('routing_color') is-invalid @enderror"
                                        name="routing_color"
                                        value="{{ old('routing_color') }}"
                                        />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" name="createInstallerForm" value="true" class="btn btn-success"
                        id="submitCreateInstallerFormButton">Save</button>
                </div>
            </div>
        </div>
    </div>

</form>
