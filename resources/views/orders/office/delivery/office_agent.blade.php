<div class="text-orange-dark d-flex align-items-center gap-px-4">
    <span class="cnumber">1</span>
    <h5 class="pt-2">OFFICE AND AGENT</h5>
</div>
<div class="px-4">
    <div class="row">
        <div class="col-12 col-md-6 col-lg-6 mt-2">
            <div class="d-flex justify-content-start align-items-center px-2">
                <label for="delivery_office" class="text-dark m-0"><b>OFFICE: </b></label>
                <input class="form-control ml-2" type="text" id="delivery_office" disabled value="{{auth()->user()->office->user->name}}">
            </div>
        </div>
        <div class="col-12 col-md-6 col-lg-6 mt-2">
            <div class="d-flex justify-content-start align-items-center px-2">
                <input type="hidden" id="officeId" value="{{auth()->user()->office->id}}">
                <label for="delivery_agent" class="text-dark m-0"><b>AGENT: </b></label>
                <select class="form-control ml-2" name="delivery_agent" id="delivery_agent">
                    <option value="">Select Agent</option>
                    @foreach ($agents as $agent)
                        <option value="{{ $agent->id }}">
                            {{ $agent->last_name }}, {{ $agent->first_name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
</div>
