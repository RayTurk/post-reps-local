<label class="text-black font-rem-1">Add Recipents</label>
<form action="{{ route('communications.emails.sendCommunicationsEmail') }}" method="POST">
    @csrf
    <div class="d-flex flex-lg-row flex-column justify-content-between form-group">

        <select class="selectpicker w-25 mb-2" id="officepicker" name="office" title="Select office" data-style="bg-white text-dark">
            <option value=true>All offices</option>
            @if ($offices->isNotEmpty())

                @foreach ($offices as $office)
                    <option value="{{ $office->id }}">{{ $office->user->name }}</option>
                @endforeach

            @endif
        </select>

        <select class="selectpicker w-25 mb-2" id="agentspicker" name="agents[]" multiple title="Select agent" data-actions-box="true" data-style="bg-white text-dark">
            <option value=true>All agents</option>
        </select>

        <select class="selectpicker w-25 mb-2" id="installerspicker" name="installers[]" multiple title="Internal staff" data-actions-box="true" data-style="bg-white text-dark">
            @if ($installers->isNotEmpty())

                @foreach ($installers as $installer)
                    <option value="{{ $installer->id }}">{{ $installer->name }}</option>
                @endforeach

            @endif
        </select>

        <!-- From <br> PostReps &lt;noreply@postreps.com&gt; -->
    </div>
    <div class="form-group">
        <div class="form-group">
            <label for="subject">Subject</label>
            <input type="text" class="form-control w-50" name="subject" value="{{ old('subject') }}" id="subject"
                required>
        </div>
        <div class="form-group">
            <label for="message">Message</label>
            <textarea name="message" class="form-control" id="message" rows="5" required>{{ old('message') }}</textarea>
        </div>
    </div>
    <button type="submit" class="btn btn-orange text-white text-uppercase font-weight-bold width-px-180 rounded-pill">Send Message</button>
</form>
