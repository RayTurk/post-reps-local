<!-- Modal -->
<form id="editAgentForm" method="POST" files=true enctype="multipart/form-data">
    @method('PATCH')
    @csrf
    <div class="modal fade" id="editAgentFormModal" tabindex="-1" aria-labelledby="exampleModalLabel"
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

                    <div class="row mt-1">
                        <div class="col-12 col-md-6 col-lg-6">
                            <div class="form-group">
                                <div>
                                    <label for="address"><b>Agent Name</b></label>
                                    <input type="text" id="name"
                                        class="form-control  @error('name') is-invalid @enderror" name="name" required>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-6 col-lg-6">
                            <div class="form-group">
                                <div>
                                    <label for="primary_contact"><b>Agent Primary Contact Name</b></label>
                                    <input type="text" id="primary_contact"
                                        class="form-control  @error('primary_contact') is-invalid @enderror"
                                        name="primary_contact">
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
                                        name="address" required>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-6 col-lg-6">
                            <div class="form-group">
                                <div>
                                    <label for="phone"><b>Agent Phone Number</b></label>
                                    <input type="text" id="phone"
                                        class="form-control  @error('phone') is-invalid @enderror phones" name="phone"
                                        required>
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
                                        name="city" required>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-6 col-lg-6">
                            <div class="form-group">
                                <div>
                                    <label for="email"><b>Agent Email</b></label>
                                    <input type="email" id="email"
                                        class="form-control @error('email') is-invalid @enderror " placeholder=""
                                        name="email"  required>
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
                                                <option value="{{ $code }}" @if ($code === 'ID') selected @endif>
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
                                        name="zipcode" required />
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-6 col-lg-6">
                            <div class="form-group">
                                <div>
                                    <label for="email"><b>Agent Website</b></label>
                                    <input type="text" id="website"
                                        class="form-control @error('website') is-invalid @enderror " name="website" />
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-1">
                        <div class="col-12 col-md-3 col-lg-3">
                            <div class="form-group">
                                <div>
                                    <label for="inactive"><b>Account Status</b></label>
                                    <select id="inactive" class="form-control  @error('inactive') is-invalid @enderror"
                                        name="inactive">
                                        <option value="0"><b class="text-muted">Active</b>
                                        </option>
                                        <option value="1"><b class="text-muted">Inactive</b></option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-3 col-lg-3">
                            <div class="form-group">
                                <div>
                                    <label for="private"><b>Private/Public</b></label>
                                    <select id="private" class="form-control  @error('private') is-invalid @enderror"
                                        name="private">
                                        <option value="0"><b class="text-muted">Public</b>
                                        </option>
                                        <option value="1"><b class="text-muted">Private</b>
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-3 col-lg-3">
                            <div class="form-group">
                                <div>
                                    <label for="email"><b>Agent Name Abbreviation</b></label>
                                    <input type="text" id="name_abbreviation"
                                        class="form-control @error('name_abbreviation') is-invalid @enderror "
                                        name="name_abbreviation" />
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-3 col-lg-3">
                            <div class="form-group">
                                <div>
                                    <label for="edit_logo_image" class="btn btn-primary text-white btn-sm"><b>Upload
                                            Logo</b></label>
                                    <input type="file" name="edit_logo_image" style="display: none" id="edit_logo_image"
                                        accept="image/*" />
                                    <div class="edit_logo_preview"><img /></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-5 col-lg-5">
                            <div class="form-group">
                                <div>
                                    <label for="region_id"><b>Agent Region</b></label>
                                    <select id="region_id"
                                        class="form-control  @error('region_id') is-invalid @enderror" name="region_id">
                                        @foreach ($regions as $r)
                                            <option value="{{ $r->id }}"><b
                                                    class="text-muted">{{ $r->name }}</b>
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    {{-- <div class="row mt-4">
                    <div class="col-md-12 text-center">
                        <button type="submit" class="btn btn-success">Save</button>
                    </div>
                </div> --}}
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-success" id="submitEditAgentFormButton">Save</button>
                </div>
            </div>
        </div>
    </div>

</form>
