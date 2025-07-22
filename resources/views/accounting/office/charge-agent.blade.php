@extends('layouts.app')

@section('content')
<div class="container-fluid">
  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-header">
          <h3 class="card-title">
            <i class="fas fa-credit-card mr-2"></i>
            Charge Agent Card
          </h3>
        </div>

        <div class="card-body">
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
                      @if(auth()->user()->role === 'super_admin')
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
                  <label for="amount">Charge Amount <span class="text-danger">*</span></label>
                  <div class="input-group">
                    <div class="input-group-prepend">
                      <span class="input-group-text">$</span>
                    </div>
                    <input type="number" name="amount" id="amount" class="form-control @error('amount') is-invalid @enderror"
                      step="0.01" min="0.01" max="999999.99" placeholder="0.00" value="{{ old('amount') }}" required>
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
                    placeholder="Enter charge description" value="{{ old('description') }}">
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
                    <input class="form-check-input" type="radio" name="payment_method" id="useSavedCard" value="saved_card" checked>
                    <label class="form-check-label" for="useSavedCard">
                      Use Saved Card
                    </label>
                  </div>
                  <div class="form-check">
                    <input class="form-check-input" type="radio" name="payment_method" id="useNewCard" value="new_card">
                    <label class="form-check-label" for="useNewCard">
                      Use New Card
                    </label>
                  </div>
                </div>
              </div>
            </div>

            <!-- Saved Card Section -->
            <div id="savedCardSection">
              <div class="row">
                <div class="col-12">
                  <div class="form-group">
                    <label for="saved_cards">Select Saved Card</label>
                    <select name="payment_profile_id" id="saved_cards" class="form-control">
                      <option value="">-- Select Agent First --</option>
                    </select>
                    <small class="form-text text-muted">Available cards will appear after selecting an agent.</small>
                  </div>
                </div>
              </div>
            </div>

            <!-- New Card Section -->
            <div id="newCardSection" style="display: none;">
              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label for="card_number">Card Number <span class="text-danger">*</span></label>
                    <input type="text" name="card_number" id="card_number" class="form-control @error('card_number') is-invalid @enderror"
                      placeholder="1234 5678 9012 3456" maxlength="19" value="{{ old('card_number') }}" disabled>
                    @error('card_number')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>
                </div>

                <div class="col-md-3">
                  <div class="form-group">
                    <label for="expire_month">Exp Month <span class="text-danger">*</span></label>
                    <select name="expire_month" id="expire_month" class="form-control @error('expire_month') is-invalid @enderror" disabled>
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
                    <label for="expire_year">Exp Year <span class="text-danger">*</span></label>
                    <select name="expire_year" id="expire_year" class="form-control @error('expire_year') is-invalid @enderror" disabled>
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

              <div class="row">
                <div class="col-md-3">
                  <div class="form-group">
                    <label for="card_code">CVV <span class="text-danger">*</span></label>
                    <input type="text" name="card_code" id="card_code" class="form-control @error('card_code') is-invalid @enderror"
                      placeholder="123" maxlength="4" value="{{ old('card_code') }}" disabled>
                    @error('card_code')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>
                </div>

                <div class="col-md-9">
                  <div class="form-group">
                    <label for="billing_name">Cardholder Name <span class="text-danger">*</span></label>
                    <input type="text" name="billing_name" id="billing_name" class="form-control @error('billing_name') is-invalid @enderror"
                      placeholder="John Doe" value="{{ old('billing_name') }}" disabled>
                    @error('billing_name')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>
                </div>
              </div>

              <!-- Billing Address -->
              <div class="row">
                <div class="col-md-8">
                  <div class="form-group">
                    <label for="billing_address">Billing Address <span class="text-danger">*</span></label>
                    <input type="text" name="billing_address" id="billing_address" class="form-control @error('billing_address') is-invalid @enderror"
                      placeholder="123 Main St" value="{{ old('billing_address') }}" disabled>
                    @error('billing_address')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>
                </div>

                <div class="col-md-4">
                  <div class="form-group">
                    <label for="billing_city">City <span class="text-danger">*</span></label>
                    <input type="text" name="billing_city" id="billing_city" class="form-control @error('billing_city') is-invalid @enderror"
                      placeholder="Anytown" value="{{ old('billing_city') }}" disabled>
                    @error('billing_city')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>
                </div>
              </div>

              <div class="row">
                <div class="col-md-4">
                  <div class="form-group">
                    <label for="billing_state">State <span class="text-danger">*</span></label>
                    <select name="billing_state" id="billing_state" class="form-control @error('billing_state') is-invalid @enderror" disabled>
                      <option value="">-- Select State --</option>
                      @foreach(['AL','AK','AZ','AR','CA','CO','CT','DE','FL','GA','HI','ID','IL','IN','IA','KS','KY','LA','ME','MD','MA','MI','MN','MS','MO','MT','NE','NV','NH','NJ','NM','NY','NC','ND','OH','OK','OR','PA','RI','SC','SD','TN','TX','UT','VT','VA','WA','WV','WI','WY'] as $state)
                      <option value="{{ $state }}" {{ old('billing_state') == $state ? 'selected' : '' }}>{{ $state }}</option>
                      @endforeach
                    </select>
                    @error('billing_state')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>
                </div>

                <div class="col-md-4">
                  <div class="form-group">
                    <label for="billing_zip">ZIP Code <span class="text-danger">*</span></label>
                    <input type="text" name="billing_zip" id="billing_zip" class="form-control @error('billing_zip') is-invalid @enderror"
                      placeholder="12345" value="{{ old('billing_zip') }}" disabled>
                    @error('billing_zip')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>
                </div>

                <div class="col-md-4">
                  <div class="form-group">
                    <div class="form-check mt-4">
                      <input type="checkbox" name="save_card" id="save_card" class="form-check-input" value="1" disabled>
                      <label class="form-check-label" for="save_card">
                        Save this card for future use
                      </label>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Submit Button -->
            <div class="row">
              <div class="col-12">
                <div class="form-group">
                  <button type="submit" id="submitBtn" class="btn btn-primary btn-lg" disabled>
                    <i class="fas fa-credit-card mr-2"></i>
                    Process Charge
                  </button>
                  <a href="{{ url('/accounting') }}" class="btn btn-secondary btn-lg ml-2">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Cancel
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

@section('scripts')
<script>
  $(document).ready(function() {
    const savedCardsSelect = $('#saved_cards');
    const newCardSection = $('#newCardSection');
    const savedCardSection = $('#savedCardSection');
    const newCardFields = newCardSection.find('input, select');
    const submitBtn = $('#submitBtn');

    function toggleNewCardFields(enable) {
      newCardFields.prop('disabled', !enable);
    }

    function updateFormDisplay(paymentMethod) {
      if (paymentMethod === 'new_card') {
        savedCardSection.hide();
        newCardSection.show();
        toggleNewCardFields(true);
      } else {
        newCardSection.hide();
        savedCardSection.show();
        toggleNewCardFields(false);
      }
      checkFormValidity();
    }

    // Agent selection change
    $('#agent_id').on('change', function() {
      const agentId = $(this).val();
      savedCardsSelect.html('<option value="">Loading...</option>');
      checkFormValidity();

      if (agentId) {
        $.get(`{{ url('/accounting/office/charge-agent/get-cards') }}/${agentId}`)
          .done(function(cards) {
            savedCardsSelect.html('<option value="">-- Select Card --</option>');
            if (cards && cards.length > 0) {
              $.each(cards, function(index, card) {
                savedCardsSelect.append(`
                  <option value="${card.payment_profile_id}" data-auth-profile="${card.authorizenet_profile_id}">
                    ${card.cardType} **** ${card.cardNumber.slice(-4)} (Exp: ${card.expDate})
                  </option>
                `);
              });
              savedCardsSelect.prop('disabled', false);
            } else {
              savedCardsSelect.html('<option value="">No saved cards available</option>');
              savedCardsSelect.prop('disabled', true);
            }
          })
          .fail(function(xhr) {
            console.error('Error loading cards:', xhr.responseText);
            savedCardsSelect.html('<option value="">Error loading cards</option>');
            savedCardsSelect.prop('disabled', true);
          });
      } else {
        savedCardsSelect.html('<option value="">-- Select Agent First --</option>');
        savedCardsSelect.prop('disabled', true);
      }
    });

    // Payment method toggle
    $('input[name="payment_method"]').on('change', function() {
      updateFormDisplay($(this).val());
    });

    // Format card number
    $('#card_number').on('input', function() {
      let value = $(this).val().replace(/\s/g, '').replace(/\D/g, '');
      let formattedValue = value.replace(/(.{4})/g, '$1 ').trim();
      if (formattedValue.length > 19) formattedValue = formattedValue.substr(0, 19);
      $(this).val(formattedValue);
    });

    // Check form validity
    function checkFormValidity() {
      const agentSelected = $('#agent_id').val() !== '';
      const amountValid = parseFloat($('#amount').val()) > 0;
      const paymentMethod = $('input[name="payment_method"]:checked').val();
      let paymentValid = false;

      if (paymentMethod === 'saved_card') {
        paymentValid = savedCardsSelect.val() !== '' && savedCardsSelect.val() !== null;
      } else {
        paymentValid = validateNewCardFields();
      }

      submitBtn.prop('disabled', !(agentSelected && amountValid && paymentValid));
    }

    // Validate new card fields
    function validateNewCardFields() {
      if (!$('#useNewCard').is(':checked')) return false;

      const requiredFields = [
        '#card_number', '#expire_month', '#expire_year',
        '#card_code', '#billing_name', '#billing_address',
        '#billing_city', '#billing_state', '#billing_zip'
      ];

      for (let field of requiredFields) {
        if (!$(field).val()) return false;
      }
      return true;
    }

    // Monitor form changes
    $('#chargeAgentForm').on('input change', 'input, select', checkFormValidity);


    // Form submission
    $('#chargeAgentForm').on('submit', function(e) {
      e.preventDefault();

      const amount = parseFloat($('#amount').val());
      const agentName = $('#agent_id option:selected').text().trim();

      if (confirm(`Are you sure you want to charge $${amount.toFixed(2)} to ${agentName}?`)) {
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');
        this.submit();
      }
    });

    // Initial setup
    updateFormDisplay($('input[name="payment_method"]:checked').val());
    $('#agent_id').trigger('change');
  });
</script>
@endsection