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
                      Use New Card
                    </label>
                  </div>
                  @error('payment_method')
                  <div class="invalid-feedback d-block">{{ $message }}</div>
                  @enderror
                </div>
              </div>
            </div>

            <!-- Saved Card Section -->
            <div id="savedCardSection" class="border p-3 mb-3" style="background-color: #f8f9fa; display: none;">
              <h6>Saved Card</h6>
              <div class="form-group">
                <label for="saved_cards">Select Saved Card <span class="text-danger">*</span></label>
                <select name="payment_profile_id" id="saved_cards" class="form-control @error('payment_profile_id') is-invalid @enderror">
                  <option value="">-- Select Agent First --</option>
                </select>
                @error('payment_profile_id')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>

            <!-- New Card Section -->
            <div id="newCardSection" class="border p-3 mb-3" style="background-color: #e9ecef; display: none;">
              <h6>New Card Information</h6>

              <!-- Card Number -->
              <div class="row">
                <div class="col-md-6">
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
                <div class="col-md-3">
                  <div class="form-group">
                    <label for="expire_month">Month <span class="text-danger">*</span></label>
                    <select name="expire_month" id="expire_month" class="form-control @error('expire_month') is-invalid @enderror">
                      <option value="">Month</option>
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
                <div class="col-md-3">
                  <div class="form-group">
                    <label for="expire_year">Year <span class="text-danger">*</span></label>
                    <select name="expire_year" id="expire_year" class="form-control @error('expire_year') is-invalid @enderror">
                      <option value="">Year</option>
                      @for($i = date('Y'); $i <= date('Y') + 10; $i++)
                        <option value="{{ $i }}" {{ old('expire_year') == $i ? 'selected' : '' }}>{{ $i }}</option>
                        @endfor
                    </select>
                    @error('expire_year')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>
                </div>
              </div>

              <!-- Security Code and Save Option -->
              <div class="row">
                <div class="col-md-3">
                  <div class="form-group">
                    <label for="card_code">CVV <span class="text-danger">*</span></label>
                    <input type="text" name="card_code" id="card_code"
                      class="form-control @error('card_code') is-invalid @enderror"
                      value="{{ old('card_code') }}" placeholder="123" maxlength="4">
                    @error('card_code')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>
                </div>
                <div class="col-md-9">
                  <div class="form-group">
                    <div class="form-check mt-4">
                      <input class="form-check-input" type="checkbox" name="save_card" id="save_card" value="1"
                        {{ old('save_card') ? 'checked' : '' }}>
                      <label class="form-check-label" for="save_card">
                        Save this card for future use
                      </label>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Billing Information -->
              <hr>
              <h6>Billing Information</h6>

              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label for="billing_name">Cardholder Name <span class="text-danger">*</span></label>
                    <input type="text" name="billing_name" id="billing_name"
                      class="form-control @error('billing_name') is-invalid @enderror"
                      value="{{ old('billing_name') }}" placeholder="John Doe">
                    @error('billing_name')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>
                </div>
              </div>

              <div class="row">
                <div class="col-md-8">
                  <div class="form-group">
                    <label for="billing_address">Address <span class="text-danger">*</span></label>
                    <input type="text" name="billing_address" id="billing_address"
                      class="form-control @error('billing_address') is-invalid @enderror"
                      value="{{ old('billing_address') }}" placeholder="123 Main Street">
                    @error('billing_address')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>
                </div>
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
              </div>

              <div class="row">
                <div class="col-md-3">
                  <div class="form-group">
                    <label for="billing_state">State <span class="text-danger">*</span></label>
                    <select name="billing_state" id="billing_state" class="form-control @error('billing_state') is-invalid @enderror">
                      <option value="">State</option>
                      <option value="AL" {{ old('billing_state') == 'AL' ? 'selected' : '' }}>Alabama</option>
                      <option value="AK" {{ old('billing_state') == 'AK' ? 'selected' : '' }}>Alaska</option>
                      <option value="AZ" {{ old('billing_state') == 'AZ' ? 'selected' : '' }}>Arizona</option>
                      <option value="CA" {{ old('billing_state') == 'CA' ? 'selected' : '' }}>California</option>
                      <option value="FL" {{ old('billing_state') == 'FL' ? 'selected' : '' }}>Florida</option>
                      <option value="NY" {{ old('billing_state') == 'NY' ? 'selected' : '' }}>New York</option>
                      <option value="TX" {{ old('billing_state') == 'TX' ? 'selected' : '' }}>Texas</option>
                    </select>
                    @error('billing_state')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>
                </div>
                <div class="col-md-3">
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
            </div>

            <!-- Submit Button -->
            <div class="row">
              <div class="col-12">
                <div class="form-group mt-3">
                  <button type="submit" id="submitBtn" class="btn btn-primary btn-lg font-weight-bold" disabled>
                    <i class="fas fa-credit-card mr-2"></i>
                    PROCESS CHARGE
                  </button>
                  <a href="{{ url('/accounting') }}" class="btn btn-secondary btn-lg ml-2 font-weight-bold">
                    <i class="fas fa-arrow-left mr-2"></i>
                    CANCEL
                  </a>
                </div>
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
  function waitForJQuery(callback) {
    if (typeof $ !== 'undefined') {
      callback();
    } else {
      setTimeout(function() {
        waitForJQuery(callback);
      }, 50);
    }
  }

  // Wait for both jQuery and DOM
  waitForJQuery(function() {
    $(document).ready(function() {
      console.log('Payment method fix loaded with jQuery:', $.fn.jquery);
      console.log('Payment radios found:', $('input[name="payment_method"]').length);

      let selectedPaymentProfileId = null;

      // Check if sections exist, if not create them
      if ($('#savedCardSection').length === 0) {
        console.log('Creating savedCardSection');
        $('input[name="payment_method"]').closest('.form-group').after(`
                <div id="savedCardSection" class="border p-3 mb-3" style="background-color: #f8f9fa; display: none;">
                    <h6>Saved Card</h6>
                    <div class="form-group">
                        <label for="saved_cards">Select Saved Card <span class="text-danger">*</span></label>
                        <select name="payment_profile_id" id="saved_cards" class="form-control">
                            <option value="">-- Select Agent First --</option>
                        </select>
                    </div>
                </div>
            `);
      }

      if ($('#newCardSection').length === 0) {
        console.log('Creating newCardSection');
        $('#savedCardSection').after(`
                <div id="newCardSection" class="border p-3 mb-3" style="background-color: #e9ecef; display: none;">
                    <h6>Credit Card Information</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Card Number <span class="text-danger">*</span></label>
                                <input type="text" name="card_number" id="card_number" class="form-control" placeholder="1234 5678 9012 3456" maxlength="19">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Month <span class="text-danger">*</span></label>
                                <select name="expire_month" id="expire_month" class="form-control">
                                    <option value="">Month</option>
                                    <option value="1">01</option><option value="2">02</option><option value="3">03</option>
                                    <option value="4">04</option><option value="5">05</option><option value="6">06</option>
                                    <option value="7">07</option><option value="8">08</option><option value="9">09</option>
                                    <option value="10">10</option><option value="11">11</option><option value="12">12</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Year <span class="text-danger">*</span></label>
                                <select name="expire_year" id="expire_year" class="form-control">
                                    <option value="">Year</option>
                                    <option value="2024">2024</option><option value="2025">2025</option>
                                    <option value="2026">2026</option><option value="2027">2027</option>
                                    <option value="2028">2028</option><option value="2029">2029</option>
                                    <option value="2030">2030</option><option value="2031">2031</option>
                                    <option value="2032">2032</option><option value="2033">2033</option>
                                    <option value="2034">2034</option><option value="2035">2035</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>CVV <span class="text-danger">*</span></label>
                                <input type="text" name="card_code" id="card_code" class="form-control" placeholder="123" maxlength="4">
                            </div>
                        </div>
                        <div class="col-md-9">
                            <div class="form-check mt-4">
                                <input type="checkbox" name="save_card" id="save_card" class="form-check-input" value="1">
                                <label class="form-check-label" for="save_card">Save this card for future use</label>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <h6>Billing Information</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Cardholder Name <span class="text-danger">*</span></label>
                                <input type="text" name="billing_name" id="billing_name" class="form-control" placeholder="John Doe">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label>Address <span class="text-danger">*</span></label>
                                <input type="text" name="billing_address" id="billing_address" class="form-control" placeholder="123 Main Street">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>City <span class="text-danger">*</span></label>
                                <input type="text" name="billing_city" id="billing_city" class="form-control" placeholder="Anytown">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>State <span class="text-danger">*</span></label>
                                <select name="billing_state" id="billing_state" class="form-control">
                                    <option value="">State</option>
                                    <option value="AL">Alabama</option><option value="AK">Alaska</option>
                                    <option value="AZ">Arizona</option><option value="CA">California</option>
                                    <option value="FL">Florida</option><option value="NY">New York</option>
                                    <option value="TX">Texas</option><option value="OH">Ohio</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>ZIP Code <span class="text-danger">*</span></label>
                                <input type="text" name="billing_zip" id="billing_zip" class="form-control" placeholder="12345" maxlength="10">
                            </div>
                        </div>
                    </div>
                </div>
            `);
      }

      // Payment method toggle - FORCE this to work
      function setupPaymentMethodToggle() {
        // Remove any existing handlers first
        $('input[name="payment_method"]').off('change.paymentmethod');

        // Add new handler
        $('input[name="payment_method"]').on('change.paymentmethod', function() {
          const method = $(this).val();
          console.log('Payment method changed to:', method);

          if (method === 'saved_card') {
            $('#savedCardSection').slideDown(200);
            $('#newCardSection').slideUp(200);
            console.log('Showing saved card section');
          } else if (method === 'new_card') {
            $('#savedCardSection').slideUp(200);
            $('#newCardSection').slideDown(200);
            console.log('Showing new card section');
          }

          // Small delay to let animations finish before validation
          setTimeout(checkFormValidity, 250);
        });

        // Also handle clicks directly on labels
        $('label[for="useSavedCard"], label[for="useNewCard"]').on('click', function() {
          const targetId = $(this).attr('for');
          const radio = $('#' + targetId);
          if (radio.length) {
            radio.prop('checked', true).trigger('change');
          }
        });
      }

      // Set up the toggle
      setupPaymentMethodToggle();

      // Force initial state after a short delay
      setTimeout(function() {
        const initialMethod = $('input[name="payment_method"]:checked').val() || 'saved_card';
        console.log('Setting initial method:', initialMethod);

        if (initialMethod === 'saved_card') {
          $('#savedCardSection').show();
          $('#newCardSection').hide();
        } else {
          $('#savedCardSection').hide();
          $('#newCardSection').show();
        }

        checkFormValidity();
      }, 300);

      // Agent selection
      $('#agent_id').on('change', function() {
        const agentId = $(this).val();
        const savedCardsSelect = $('#saved_cards');
        selectedPaymentProfileId = null;

        if (agentId) {
          savedCardsSelect.html('<option value="">Loading...</option>');

          $.get('/accounting/office/charge-agent/get-cards/' + agentId)
            .done(function(cards) {
              savedCardsSelect.html('<option value="">-- Select Card --</option>');
              if (cards && cards.length > 0) {
                $.each(cards, function(index, card) {
                  savedCardsSelect.append(
                    '<option value="' + card.payment_profile_id + '">' +
                    card.cardType + ' **** ' + card.cardNumber.slice(-4) +
                    ' (Exp: ' + card.expDate + ')</option>'
                  );
                });
              } else {
                savedCardsSelect.append('<option value="">No saved cards available</option>');
              }
              checkFormValidity();
            })
            .fail(function() {
              console.error('Failed to load cards');
              savedCardsSelect.html('<option value="">Error loading cards</option>');
              checkFormValidity();
            });
        } else {
          savedCardsSelect.html('<option value="">-- Select Agent First --</option>');
          checkFormValidity();
        }
      });

      // Card selection
      $(document).on('change', '#saved_cards', function() {
        selectedPaymentProfileId = $(this).val();
        console.log('Selected card:', selectedPaymentProfileId);
        checkFormValidity();
      });

      // Format card number as user types
      $(document).on('input', '#card_number', function() {
        let value = $(this).val().replace(/\s/g, '').replace(/\D/g, '');
        let formattedValue = value.replace(/(.{4})/g, '$1 ').trim();
        if (formattedValue.length > 19) formattedValue = formattedValue.substr(0, 19);
        $(this).val(formattedValue);
        checkFormValidity();
      });

      // Format amount
      $(document).on('input', '#amount', function() {
        let value = $(this).val();
        let formattedValue = value.replace(/[^0-9.]/g, '').match(/\\d+\\.?\\d{0,2}/)?.[0] || '';
        $(this).val(formattedValue);
        checkFormValidity();
      });

      // Simple form validation
      function checkFormValidity() {
        const agentSelected = $('#agent_id').val() !== '';
        const amountValid = parseFloat($('#amount').val() || 0) > 0;
        const paymentMethod = $('input[name="payment_method"]:checked').val();
        let paymentValid = false;

        if (paymentMethod === 'saved_card') {
          paymentValid = selectedPaymentProfileId && selectedPaymentProfileId !== '';
        } else if (paymentMethod === 'new_card') {
          const cardNumber = $('#card_number').val().replace(/\\s/g, '');
          const expMonth = $('#expire_month').val();
          const expYear = $('#expire_year').val();
          const cvv = $('#card_code').val();
          const billingName = $('#billing_name').val();
          const billingAddress = $('#billing_address').val();
          const billingCity = $('#billing_city').val();
          const billingState = $('#billing_state').val();
          const billingZip = $('#billing_zip').val();

          paymentValid = cardNumber.length >= 13 &&
            expMonth !== '' &&
            expYear !== '' &&
            cvv.length >= 3 &&
            billingName.length > 0 &&
            billingAddress.length > 0 &&
            billingCity.length > 0 &&
            billingState !== '' &&
            billingZip.length >= 5;
        }

        const isValid = agentSelected && amountValid && paymentValid;

        // Enable/disable submit button
        if ($('#submitBtn').length) {
          $('#submitBtn').prop('disabled', !isValid);
          if (isValid) {
            $('#submitBtn').removeClass('btn-secondary').addClass('btn-primary');
          } else {
            $('#submitBtn').removeClass('btn-primary').addClass('btn-secondary');
          }
        }

        console.log('Form validation:', {
          agentSelected: agentSelected,
          amountValid: amountValid,
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

        if (confirm(`Are you sure you want to charge ${amount.toFixed(2)} to ${agentName}?`)) {
          $('#submitBtn')
            .prop('disabled', true)
            .html('<i class="fas fa-spinner fa-spin"></i> Processing...');
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
    });
  });
</script>
@endsection