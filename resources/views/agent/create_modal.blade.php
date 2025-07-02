<!-- Button trigger modal -->
<button type="button" class="btn btn-primary" data-toggle="modal" data-target="#createAgentFormModal">
    Add New Agent
</button>

<!-- Modal -->
<form id="createAgentForm" method="POST" action="{{ route('agents.store') }}" files=true
    enctype="multipart/form-data">

    <div class="modal fade" id="createAgentFormModal" tabindex="-1" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content auth-card">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Create Agent</h5>
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
                                    <label for="first_name"><b>Agent First Name</b></label>
                                    <input type="text" id="first_name"
                                        class="form-control  @error('first_name') is-invalid @enderror" name="first_name"
                                        value="{{ old('first_name') }}" required>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-6 col-lg-6">
                            <div class="form-group">
                                <div>
                                    <label for="last_name"><b>Agent Last Name</b></label>
                                    <input type="text" id="last_name"
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
                                    <label for="address"><b>Agent Street Address</b></label>
                                    <input type="text" id="street_address"
                                        class="form-control  @error('address') is-invalid @enderror" placeholder=""
                                        name="address" required value="{{ old('address') }}">
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-6 col-lg-6">
                            <div class="form-group">
                                <div>
                                    <label for="email"><b>Agent Email</b></label>
                                    <input type="email" id="email"
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
                                    <label for="city"><b>Agent City</b></label>
                                    <input type="text" id="city"
                                        class="form-control  @error('city') is-invalid @enderror" placeholder=""
                                        name="city" required value="{{ old('city') }}">
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-6 col-lg-6">

                            <div class="form-group">
                                <div>
                                    <label for="phone"><b>Agent Phone Number</b></label>
                                    <input type="text" id="phone"
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
                                    <label for="state"><b>Agent State</b></label>
                                    <select id="state" required
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
                                    <label for="email"><b>Agent Zipcode</b></label>
                                    <input type="text" id="zipcode"
                                        class="form-control @error('zipcode') is-invalid @enderror zipcode"
                                        name="zipcode" value="{{ old('zipcode') }}" required />
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-6 col-lg-6">
                            <div class="form-group">
                                <div>
                                    <label for="email"><b>RE license #</b></label>
                                    <input type="text" id="re_license"
                                        class="form-control @error('re_license') is-invalid @enderror " name="re_license"
                                        value="{{ old('re_license') }}" />
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-1">
                        <div class="col-12 col-md-3 col-lg-3">
                            <div class="form-group">
                                <div>
                                    <label for="agent_office"><b>Agent Office</b></label>
                                    <select id="agent_office" required
                                        class="form-control  @error('agent_office') is-invalid @enderror" placeholder=""
                                        name="agent_office">
                                        @if (count($states))
                                            @foreach ($offices as $office)
                                                <option value="{{ $office->id }}" @if (old('office') === $office->id) selected @endif>
                                                    {{ $office->name }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" name="createAgentForm" value="true" class="btn btn-success"
                        id="submitCreateAgentFormButton">Save</button>
                </div>
            </div>
        </div>
    </div>

</form>
