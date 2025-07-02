<div class="text-orange-dark d-flex align-items-center gap-px-4">
    <span class="cnumber">1</span>
    <h5 class="pt-2">OFFICE AND AGENT</h5>
</div>
<div class="px-4">
    <div class="row">
        <div class="col-12 col-md-6 col-lg-6 mt-2">
            <div class="d-flex justify-content-start align-items-center px-2">
                <label for="install_post_office" class="text-dark m-0"><b>OFFICE: </b></label>
                <input class="form-control ml-2" name="install_post_office"  type="text" disabled value="{{auth()->user()->agent->office->user->name}}" id="install_post_office">
            </div>
        </div>
        <div class="col-12 col-md-6 col-lg-6 mt-2">
            <div class="d-flex justify-content-start align-items-center px-2">
                <label for="install_post_agent" class="text-dark m-0"><b>AGENT: </b></label>
                <input class="form-control ml-2" name="install_post_agent" type="text" disabled value="{{auth()->user()->agent->user->name}}" id="install_post_agent">
                {{-- <select class="form-control   ml-2" disabled name="install_post_agent" id="install_post_agent">
                    <option value="{{ auth()->user()->agent->id }}">{{ auth()->user()->agent->user->last_name }}, {{ auth()->user()->agent->user->first_name }}</option>
                    <option value="">Select Agent</option>
                    @foreach ($agents as $agent)
                        <option value="{{ $agent->id }}">
                            {{ $agent->last_name }}, {{ $agent->first_name }}
                        </option>
                    @endforeach
                </select> --}}
            </div>
        </div>
    </div>
</div>
