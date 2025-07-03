<p class="font-weight-bold h5">Add Recipents</p>
<form action="{{ route('communications.emails.sendCommunicationsEmail') }}" method="POST">
    @csrf
    <div class="d-flex flex-column justify-content-between form-group">

        <select class="selectpicker w-100 mb-2" id="officepicker" name="office" title="Select office" data-style="bg-white text-dark">
            <option value=true>All offices</option>
            @if ($offices->isNotEmpty())

                @foreach ($offices as $office)
                    <option value="{{ $office->id }}">{{ $office->user->name }}</option>
                @endforeach

            @endif
        </select>

        <select class="selectpicker w-100 mb-2" id="agentspicker" name="agents[]" multiple title="Select agent" data-actions-box="true" data-style="bg-white text-dark">
            <option value=true>All agents</option>
        </select>

        <select class="selectpicker w-100 mb-2" id="installerspicker" name="installers[]" multiple title="Internal staff" data-actions-box="true" data-style="bg-white text-dark">
            @if ($installers->isNotEmpty())

                @foreach ($installers as $installer)
                    <option value="{{ $installer->id }}">{{ $installer->name }}</option>
                @endforeach

            @endif
        </select>
    </div>
    <div class="form-group">
        <div class="form-group">
            <label for="subject">Subject</label>
            <input type="text" class="form-control w-100" name="subject" value="{{ old('subject') }}" id="subject" required>
        </div>
        <div class="form-group">
            <label for="message">Message</label>
            <textarea name="message" class="form-control" id="message" rows="5" required>{{ old('message') }}</textarea>
        </div>
    </div>
    <button type="submit" class="btn btn-orange text-white text-uppercase font-weight-bold width-px-180 rounded-pill">Send Message</button>
</form>
