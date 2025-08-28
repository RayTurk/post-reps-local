@extends('layouts.auth')

@section('content')
<div class="container p-0">
  @if(session('success'))
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('success') }}
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
      <span aria-hidden="true">&times;</span>
    </button>
  </div>
  @endif

  @if(session('error'))
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
    {{ session('error') }}
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
      <span aria-hidden="true">&times;</span>
    </button>
  </div>
  @endif
</div>

<div class="container-fluid pl-4 mt-1 pr-4">
  <div class="row">
    <div class="col-12">
      <div class="card auth-card">
        <div class="card-header">
          <h6>CHARGE AGENT CARD</h6>
        </div>

        <div class="card-body">
          <form id="chargeAgentForm" method="POST" action="{{ route('office.charge.agent.process') }}">
            @csrf
            @if(auth()->user())
            <input type="hidden" name="debug_user_role" value="{{ auth()->user()->role }}">
            <input type="hidden" name="debug_user_id" value="{{ auth()->user()->id }}">
            @endif

            <!-- Agent Selection -->
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label for="agent_id">Select Agent <span class="text-danger">*</span></label>
                  <select name="agent_id" id="agent_id" class="form-control @error('agent_id') is-invalid @enderror" required>
                    <option value="">-- Select Agent --</option>
                    @foreach($agents as $agent)
                    <option value="{{ $agent->id }}" {{ old('agent_id') == $agent->id ? 'selected' : '' }}>
                      {{ $agent->user->name }}
                      @if(auth()->user()->role === 1)
                      ({{ $agent->office->user->name ?? 'No Office' }})
                      @endif
                    </option>
                    @endforeach
                  </select>
                  @error('agent_id')
                  <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label for="amount">Amount <span class="text-danger">*</span></label>
                  <div class="input-group">
                    <div class="input-group-prepend">
                      <span class="input-group-text">$</span>
                    </div>
                    <input type="number" name="amount" id="amount" step="0.01" min="0.01" max="999999.99"
                      class="form-control @error('amount') is-invalid @enderror"
                      value="{{ old('amount') }}" placeholder="0.00" required>
                  </div>
                  @error('amount')
                  <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>
              </div>
            </div>

            <!-- Description -->
            <div class="row">
              <div class="col-12">
                <div class="form-group">
                  <label for="description">Description (Optional)</label>
                  <input type="text" name="description" id="description" class="form-control @error('description') is-invalid @enderror"
                    value="{{ old('description') }}" placeholder="Enter charge description">
                  @error('description')
                  <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>
              </div>
            </div>

            <!-- Payment Method Selection -->
            <div class="row">
              <div class="col-12">
                <div class="form-group">
                  <label>Payment Method <span class="text-danger">*</span></label>
                  <div class="form-check">
                    <input class="form-check-input" type="radio" name="payment_method" id="useSavedCard" value="saved_card"
                      {{ old('payment_method', 'saved_card') == 'saved_card' ? 'checked' : '' }}>
                    <label class="form-check-label" for="useSavedCard">
                      Use Saved Card
                    </label>
                  </div>
                  <div class="form-check">
                    <input class="form-check-input" type="radio" name="payment_method" id="useNewCard" value="new_card"
                      {{ old('payment_method') == 'new_card' ? 'checked' : '' }}>
                    <label class="form-check-label" for="useNewCard">
                      Enter New Card
                    </label>
                  </div>
                  @error('payment_method')
                  <div class="invalid-feedback d-block">{{ $message }}</div>
                  @enderror
                </div>
              </div>
            </div>

            <!-- Saved Card Section -->
            <div id="savedCardSection" style="{{ old('payment_method', 'saved_card') == 'saved_card' ? 'display: block;' : 'display: none;' }}">
              <div class="row">
                <div class="col-12">
                  <div class="form-group">
                    <label for="payment_profile_id">Select Saved Card <span class="text-danger">*</span></label>
                    <select name="payment_profile_id" id="payment_profile_id" class="form-control @error('payment_profile_id') is-invalid @enderror">
                      <option value="">-- Select Agent First --</option>
                    </select>
                    @error('payment_profile_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="text-muted">Please select an agent first to view their saved cards.</small>
                  </div>
                </div>
              </div>
            </div>

            <!-- New Card Section -->
            <div id="newCardSection" style="{{ old('payment_method') == 'new_card' ? 'display: block;' : 'display: none;' }}">
              <!-- Card Information -->
              <div class="row">
                <div class="col-md-8">
                  <div class="form-group">
                    <label for="card_number">Card Number <span class="text-danger">*</span></label>
                    <input type="text" name="card_number" id="card_number"
                      class="form-control @error('card_number') is-invalid @enderror"
                      value="{{ old('card_number') }}" placeholder="1234 5678 9012 3456" maxlength="19">
                    @error('card_number')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <label for="card_code">Security Code <span class="text-danger">*</span></label>
                    <input type="text" name="card_code" id="card_code"
                      class="form-control @error('card_code') is-invalid @enderror"
                      value="{{ old('card_code') }}" placeholder="123" maxlength="4">
                    @error('card_code')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>
                </div>
              </div>

              <!-- Expiration -->
              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label for="expire_month">Expiration Month <span class="text-danger">*</span></label>
                    <select name="expire_month" id="expire_month" class="form-control @error('expire_month') is-invalid @enderror">
                      <option value="">-- Month --</option>
                      @for($i = 1; $i <= 12; $i++)
                        <option value="{{ $i }}" {{ old('expire_month') == $i ? 'selected' : '' }}>
                        {{ str_pad($i, 2, '0', STR_PAD_LEFT) }}
                        </option>
                        @endfor
                    </select>
                    @error('expire_month')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label for="expire_year">Expiration Year <span class="text-danger">*</span></label>
                    <select name="expire_year" id="expire_year" class="form-control @error('expire_year') is-invalid @enderror">
                      <option value="">-- Year --</option>
                      @for($i = date('Y'); $i <= date('Y') + 20; $i++)
                        <option value="{{ $i }}" {{ old('expire_year') == $i ? 'selected' : '' }}>{{ $i }}</option>
                        @endfor
                    </select>
                    @error('expire_year')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>
                </div>
              </div>

              <!-- Billing Information -->
              <h6 class="mt-3 mb-2">Billing Information</h6>
              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label for="billing_name">Name on Card <span class="text-danger">*</span></label>
                    <input type="text" name="billing_name" id="billing_name"
                      class="form-control @error('billing_name') is-invalid @enderror"
                      value="{{ old('billing_name') }}" placeholder="John Doe">
                    @error('billing_name')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label for="billing_address">Address <span class="text-danger">*</span></label>
                    <input type="text" name="billing_address" id="billing_address"
                      class="form-control @error('billing_address') is-invalid @enderror"
                      value="{{ old('billing_address') }}" placeholder="123 Main St">
                    @error('billing_address')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>
                </div>
              </div>

              <div class="row">
                <div class="col-md-4">
                  <div class="form-group">
                    <label for="billing_city">City <span class="text-danger">*</span></label>
                    <input type="text" name="billing_city" id="billing_city"
                      class="form-control @error('billing_city') is-invalid @enderror"
                      value="{{ old('billing_city') }}" placeholder="Anytown">
                    @error('billing_city')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <label for="billing_state">State <span class="text-danger">*</span></label>
                    <select name="billing_state" id="billing_state" class="form-control @error('billing_state') is-invalid @enderror">
                      <option value="">-- State --</option>
                      <option value="AL" {{ old('billing_state') == 'AL' ? 'selected' : '' }}>Alabama</option>
                      <option value="AK" {{ old('billing_state') == 'AK' ? 'selected' : '' }}>Alaska</option>
                      <option value="AZ" {{ old('billing_state') == 'AZ' ? 'selected' : '' }}>Arizona</option>
                      <option value="AR" {{ old('billing_state') == 'AR' ? 'selected' : '' }}>Arkansas</option>
                      <option value="CA" {{ old('billing_state') == 'CA' ? 'selected' : '' }}>California</option>
                      <option value="CO" {{ old('billing_state') == 'CO' ? 'selected' : '' }}>Colorado</option>
                      <option value="CT" {{ old('billing_state') == 'CT' ? 'selected' : '' }}>Connecticut</option>
                      <option value="DE" {{ old('billing_state') == 'DE' ? 'selected' : '' }}>Delaware</option>
                      <option value="FL" {{ old('billing_state') == 'FL' ? 'selected' : '' }}>Florida</option>
                      <option value="GA" {{ old('billing_state') == 'GA' ? 'selected' : '' }}>Georgia</option>
                      <option value="HI" {{ old('billing_state') == 'HI' ? 'selected' : '' }}>Hawaii</option>
                      <option value="ID" {{ old('billing_state') == 'ID' ? 'selected' : '' }}>Idaho</option>
                      <option value="IL" {{ old('billing_state') == 'IL' ? 'selected' : '' }}>Illinois</option>
                      <option value="IN" {{ old('billing_state') == 'IN' ? 'selected' : '' }}>Indiana</option>
                      <option value="IA" {{ old('billing_state') == 'IA' ? 'selected' : '' }}>Iowa</option>
                      <option value="KS" {{ old('billing_state') == 'KS' ? 'selected' : '' }}>Kansas</option>
                      <option value="KY" {{ old('billing_state') == 'KY' ? 'selected' : '' }}>Kentucky</option>
                      <option value="LA" {{ old('billing_state') == 'LA' ? 'selected' : '' }}>Louisiana</option>
                      <option value="ME" {{ old('billing_state') == 'ME' ? 'selected' : '' }}>Maine</option>
                      <option value="MD" {{ old('billing_state') == 'MD' ? 'selected' : '' }}>Maryland</option>
                      <option value="MA" {{ old('billing_state') == 'MA' ? 'selected' : '' }}>Massachusetts</option>
                      <option value="MI" {{ old('billing_state') == 'MI' ? 'selected' : '' }}>Michigan</option>
                      <option value="MN" {{ old('billing_state') == 'MN' ? 'selected' : '' }}>Minnesota</option>
                      <option value="MS" {{ old('billing_state') == 'MS' ? 'selected' : '' }}>Mississippi</option>
                      <option value="MO" {{ old('billing_state') == 'MO' ? 'selected' : '' }}>Missouri</option>
                      <option value="MT" {{ old('billing_state') == 'MT' ? 'selected' : '' }}>Montana</option>
                      <option value="NE" {{ old('billing_state') == 'NE' ? 'selected' : '' }}>Nebraska</option>
                      <option value="NV" {{ old('billing_state') == 'NV' ? 'selected' : '' }}>Nevada</option>
                      <option value="NH" {{ old('billing_state') == 'NH' ? 'selected' : '' }}>New Hampshire</option>
                      <option value="NJ" {{ old('billing_state') == 'NJ' ? 'selected' : '' }}>New Jersey</option>
                      <option value="NM" {{ old('billing_state') == 'NM' ? 'selected' : '' }}>New Mexico</option>
                      <option value="NY" {{ old('billing_state') == 'NY' ? 'selected' : '' }}>New York</option>
                      <option value="NC" {{ old('billing_state') == 'NC' ? 'selected' : '' }}>North Carolina</option>
                      <option value="ND" {{ old('billing_state') == 'ND' ? 'selected' : '' }}>North Dakota</option>
                      <option value="OH" {{ old('billing_state') == 'OH' ? 'selected' : '' }}>Ohio</option>
                      <option value="OK" {{ old('billing_state') == 'OK' ? 'selected' : '' }}>Oklahoma</option>
                      <option value="OR" {{ old('billing_state') == 'OR' ? 'selected' : '' }}>Oregon</option>
                      <option value="PA" {{ old('billing_state') == 'PA' ? 'selected' : '' }}>Pennsylvania</option>
                      <option value="RI" {{ old('billing_state') == 'RI' ? 'selected' : '' }}>Rhode Island</option>
                      <option value="SC" {{ old('billing_state') == 'SC' ? 'selected' : '' }}>South Carolina</option>
                      <option value="SD" {{ old('billing_state') == 'SD' ? 'selected' : '' }}>South Dakota</option>
                      <option value="TN" {{ old('billing_state') == 'TN' ? 'selected' : '' }}>Tennessee</option>
                      <option value="TX" {{ old('billing_state') == 'TX' ? 'selected' : '' }}>Texas</option>
                      <option value="UT" {{ old('billing_state') == 'UT' ? 'selected' : '' }}>Utah</option>
                      <option value="VT" {{ old('billing_state') == 'VT' ? 'selected' : '' }}>Vermont</option>
                      <option value="VA" {{ old('billing_state') == 'VA' ? 'selected' : '' }}>Virginia</option>
                      <option value="WA" {{ old('billing_state') == 'WA' ? 'selected' : '' }}>Washington</option>
                      <option value="WV" {{ old('billing_state') == 'WV' ? 'selected' : '' }}>West Virginia</option>
                      <option value="WI" {{ old('billing_state') == 'WI' ? 'selected' : '' }}>Wisconsin</option>
                      <option value="WY" {{ old('billing_state') == 'WY' ? 'selected' : '' }}>Wyoming</option>
                    </select>
                    @error('billing_state')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <label for="billing_zip">ZIP Code <span class="text-danger">*</span></label>
                    <input type="text" name="billing_zip" id="billing_zip"
                      class="form-control @error('billing_zip') is-invalid @enderror"
                      value="{{ old('billing_zip') }}" placeholder="12345" maxlength="10">
                    @error('billing_zip')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>
                </div>
              </div>

              <!-- Save Card Option -->
              <div class="row">
                <div class="col-12">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="save_card" id="save_card" value="1" {{ old('save_card') ? 'checked' : '' }}>
                    <label class="form-check-label" for="save_card">
                      Save this card for future use
                    </label>
                  </div>
                </div>
              </div>
            </div>

            <!-- Submit Button -->
            <div class="row mt-4">
              <div class="col-12 text-center">
                <button type="submit" id="submitBtn" class="btn btn-primary" disabled>
                  <i class="fas fa-credit-card"></i> Process Charge
                </button>
                <a href="{{ route('office.charge.agent') }}" class="btn btn-secondary ml-2">
                  <i class="fas fa-times"></i> Cancel
                </a>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@section('page_scripts')
<script>
  // Wait for jQuery to be available
  function initChargeAgentForm() {
    if (typeof jQuery === 'undefined') {
      setTimeout(initChargeAgentForm, 50);
      return;
    }

    $(document).ready(function() {
      // Initialize Select2 for agent dropdown with search
      $('#agent_id').select2({
        theme: 'bootstrap4',
        placeholder: '-- Select Agent --',
        allowClear: true,
        width: '100%'
      });

      // Enhanced search configuration for better UX
      $('#agent_id').select2({
        theme: 'bootstrap4',
        placeholder: '-- Select Agent --',
        allowClear: true,
        width: '100%',
        matcher: function(params, data) {
          // If there are no search terms, return all of the data
          if ($.trim(params.term) === '') {
            return data;
          }

          // Do not display the item if there is no 'text' property
          if (typeof data.text === 'undefined') {
            return null;
          }

          // `params.term` is the user's search term
          var searchTerm = params.term.toLowerCase();
          var dataText = data.text.toLowerCase();

          // Check if the text contains the term
          if (dataText.indexOf(searchTerm) > -1) {
            return data;
          }

          // Return `null` if the term should not be displayed
          return null;
        }
      });

      console.log('Select2 initialized for agent dropdown');

      // Payment method toggle
      $('input[name="payment_method"]').on('change', function() {
        const paymentMethod = $(this).val();

        if (paymentMethod === 'saved_card') {
          $('#savedCardSection').show();
          $('#newCardSection').hide();
          $('#payment_profile_id').prop('required', true);
          $('#newCardSection input, #newCardSection select').prop('required', false);
        } else {
          $('#savedCardSection').hide();
          $('#newCardSection').show();
          $('#payment_profile_id').prop('required', false);
          $('#newCardSection input[type!="checkbox"], #newCardSection select').prop('required', true);
        }

        checkFormValidity();
      });

      // Load agent cards when agent is selected
      $('#agent_id').on('change', function() {
        const agentId = $(this).val();

        if (agentId) {
          // Clear current cards
          $('#payment_profile_id').empty().append('<option value="">Loading cards...</option>');

          // Load agent's cards
          $.get(`{{ url('/accounting/office/charge-agent/get-cards') }}/${agentId}`)
            .done(function(cards) {
              $('#payment_profile_id').empty().append('<option value="">-- Select Card --</option>');

              if (cards.length > 0) {
                $.each(cards, function(index, card) {
                  $('#payment_profile_id').append(
                    `<option value="${card.payment_profile_id}">${card.cardType} ending in ${card.cardNumber.slice(-4)} (Exp: ${card.expDate})</option>`
                  );
                });
              } else {
                $('#payment_profile_id').append('<option value="" disabled>No saved cards found</option>');
              }

              checkFormValidity();
            })
            .fail(function() {
              $('#payment_profile_id').empty().append('<option value="" disabled>Error loading cards</option>');
            });
        } else {
          $('#payment_profile_id').empty().append('<option value="">-- Select Agent First --</option>');
        }

        checkFormValidity();
      });

      // Card number formatting
      $('#card_number').on('input', function() {
        let value = $(this).val().replace(/\D/g, '');
        let formattedValue = value.replace(/(\d{4})(?=\d)/g, '$1 ');
        $(this).val(formattedValue);
      });

      // Form validation
      function checkFormValidity() {
        const agentSelected = $('#agent_id').val();
        const amountEntered = parseFloat($('#amount').val()) > 0;
        const paymentMethod = $('input[name="payment_method"]:checked').val();

        let paymentValid = false;

        if (paymentMethod === 'saved_card') {
          paymentValid = $('#payment_profile_id').val() !== '';
        } else if (paymentMethod === 'new_card') {
          const cardNumber = $('#card_number').val().replace(/\s/g, '');
          const requiredFields = ['#expire_month', '#expire_year', '#card_code',
            '#billing_name', '#billing_address', '#billing_city', '#billing_state', '#billing_zip'
          ];

          const fieldsValid = requiredFields.every(field => $(field).val().trim() !== '');
          const cardValid = cardNumber.length >= 13 && cardNumber.length <= 19;

          paymentValid = fieldsValid && cardValid;
        }

        const isValid = agentSelected && amountEntered && paymentValid;

        $('#submitBtn').prop('disabled', !isValid);

        // Debug logging
        console.log({
          agentSelected: agentSelected,
          amountEntered: amountEntered,
          paymentMethod: paymentMethod,
          paymentValid: paymentValid,
          isValid: isValid
        });
      }

      // Monitor all input changes
      $(document).on('input change', '#amount, #card_number, #expire_month, #expire_year, #card_code, #billing_name, #billing_address, #billing_city, #billing_state, #billing_zip', function() {
        checkFormValidity();
      });

      // Form submission with confirmation
      $('#chargeAgentForm').on('submit', function(e) {
        e.preventDefault();

        const amount = parseFloat($('#amount').val());
        const agentName = $('#agent_id option:selected').text();

        // Debug: Log form data before submission
        const formData = new FormData(this);
        console.log('Form submission data:', Object.fromEntries(formData));

        if (confirm(`Are you sure you want to charge ${amount.toFixed(2)} to ${agentName}?`)) {
          $('#submitBtn')
            .prop('disabled', true)
            .html('<i class="fas fa-spinner fa-spin"></i> Processing...');

          // Submit the form
          this.submit();
        }
      });

      // Initial validation
      checkFormValidity();

      // Debug info
      console.log('Setup complete. Sections:', {
        savedCardSection: $('#savedCardSection').length,
        newCardSection: $('#newCardSection').length,
        paymentRadios: $('input[name="payment_method"]').length,
        agentSelect: $('#agent_id').length,
        amountInput: $('#amount').length
      });
    }); // Close $(document).ready
  } // Close initChargeAgentForm

  // Start initialization
  initChargeAgentForm();
</script>
@endsection