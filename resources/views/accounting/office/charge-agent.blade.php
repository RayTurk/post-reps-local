@extends('layouts.auth')

@section('pageTitle', 'Charge Agent Card')

@section('pageCss')
<style>
  .payment-form-section {
    background-color: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
  }

  .card-option {
    border: 2px solid #dee2e6;
    padding: 15px;
    border-radius: 5px;
    margin-bottom: 10px;
    cursor: pointer;
    transition: all 0.2s;
  }

  .card-option:hover {
    border-color: #007bff;
    background-color: #f0f8ff;
  }

  .card-option.selected {
    border-color: #007bff;
    background-color: #e7f3ff;
  }

  .new-card-form {
    display: none;
  }
</style>
@endsection

@section('content')
<div class="container-fluid">
  <div class="row">
    <div class="col-lg-8 col-md-10 mx-auto">
      <div class="card">
        <div class="card-header">
          <h3 class="card-title">Charge Agent Card</h3>
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

          <form method="POST" action="{{ route('office.charge.process') }}" id="chargeAgentForm">
            @csrf

            <!-- Agent Selection -->
            <div class="form-group">
              <label for="agent_id"><strong>Select Agent</strong> <span class="text-danger">*</span></label>
              <select name="agent_id" id="agent_id" class="form-control @error('agent_id') is-invalid @enderror" required>
                <option value="">-- Select Agent --</option>
                @foreach($agents as $agent)
                <option value="{{ $agent->id }}" {{ old('agent_id') == $agent->id ? 'selected' : '' }}>
                  {{ $agent->user->name }} - {{ $agent->user->email }}
                  @if(auth()->user()->role === auth()->user()::ROLE_SUPER_ADMIN)
                  (Office: {{ $agent->office->user->name ?? 'N/A' }})
                  @endif
                </option>
                @endforeach
              </select>
              @error('agent_id')
              <span class="invalid-feedback" role="alert">
                <strong>{{ $message }}</strong>
              </span>
              @enderror
            </div>

            <!-- Amount -->
            <div class="form-group">
              <label for="amount"><strong>Amount to Charge</strong> <span class="text-danger">*</span></label>
              <div class="input-group">
                <div class="input-group-prepend">
                  <span class="input-group-text">$</span>
                </div>
                <input type="number"
                  name="amount"
                  id="amount"
                  class="form-control @error('amount') is-invalid @enderror"
                  value="{{ old('amount') }}"
                  step="0.01"
                  min="0.01"
                  max="999999.99"
                  placeholder="0.00"
                  required>
              </div>
              @error('amount')
              <span class="invalid-feedback d-block" role="alert">
                <strong>{{ $message }}</strong>
              </span>
              @enderror
            </div>

            <!-- Description -->
            <div class="form-group">
              <label for="description"><strong>Description</strong></label>
              <textarea name="description"
                id="description"
                class="form-control @error('description') is-invalid @enderror"
                rows="3"
                placeholder="Enter a description for this charge (optional)">{{ old('description') }}</textarea>
              @error('description')
              <span class="invalid-feedback" role="alert">
                <strong>{{ $message }}</strong>
              </span>
              @enderror
            </div>

            <!-- Payment Method Section -->
            <div class="payment-form-section" id="paymentMethodSection" style="display: none;">
              <h5>Payment Method</h5>

              <input type="hidden" name="payment_method" id="payment_method" value="saved_card">

              <!-- Saved Cards -->
              <div id="savedCardsSection">
                <div class="mb-3">
                  <label><strong>Select a saved card:</strong></label>
                  <div id="savedCardsList">
                    <p class="text-muted">Loading saved cards...</p>
                  </div>
                </div>

                <div class="custom-control custom-checkbox mb-3">
                  <input type="checkbox" class="custom-control-input" id="useNewCard">
                  <label class="custom-control-label" for="useNewCard">Use a new card</label>
                </div>
              </div>

              <!-- New Card Form -->
              <div class="new-card-form" id="newCardForm">
                <h6 class="mb-3">Enter Card Information</h6>

                <div class="form-row">
                  <div class="col-md-12 mb-3">
                    <label for="card_number">Card Number <span class="text-danger">*</span></label>
                    <input type="text"
                      name="card_number"
                      id="card_number"
                      class="form-control"
                      placeholder="1234 5678 9012 3456"
                      maxlength="19">
                  </div>
                </div>

                <div class="form-row">
                  <div class="col-md-4 mb-3">
                    <label for="expire_month">Exp. Month <span class="text-danger">*</span></label>
                    <select name="expire_month" id="expire_month" class="form-control">
                      <option value="">MM</option>
                      @for($i = 1; $i <= 12; $i++)
                        <option value="{{ $i }}">{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}</option>
                        @endfor
                    </select>
                  </div>
                  <div class="col-md-4 mb-3">
                    <label for="expire_year">Exp. Year <span class="text-danger">*</span></label>
                    <select name="expire_year" id="expire_year" class="form-control">
                      <option value="">YYYY</option>
                      @for($i = date('Y'); $i <= date('Y') + 15; $i++)
                        <option value="{{ $i }}">{{ $i }}</option>
                        @endfor
                    </select>
                  </div>
                  <div class="col-md-4 mb-3">
                    <label for="card_code">Security Code <span class="text-danger">*</span></label>
                    <input type="text"
                      name="card_code"
                      id="card_code"
                      class="form-control"
                      placeholder="CVV"
                      maxlength="4">
                  </div>
                </div>

                <div class="form-row">
                  <div class="col-md-12 mb-3">
                    <label for="billing_name">Name on Card <span class="text-danger">*</span></label>
                    <input type="text" name="billing_name" id="billing_name" class="form-control">
                  </div>
                </div>

                <div class="form-row">
                  <div class="col-md-12 mb-3">
                    <label for="billing_address">Billing Address <span class="text-danger">*</span></label>
                    <input type="text" name="billing_address" id="billing_address" class="form-control">
                  </div>
                </div>

                <div class="form-row">
                  <div class="col-md-6 mb-3">
                    <label for="billing_city">City <span class="text-danger">*</span></label>
                    <input type="text" name="billing_city" id="billing_city" class="form-control">
                  </div>
                  <div class="col-md-3 mb-3">
                    <label for="billing_state">State <span class="text-danger">*</span></label>
                    <input type="text" name="billing_state" id="billing_state" class="form-control" maxlength="2">
                  </div>
                  <div class="col-md-3 mb-3">
                    <label for="billing_zip">ZIP <span class="text-danger">*</span></label>
                    <input type="text" name="billing_zip" id="billing_zip" class="form-control" maxlength="10">
                  </div>
                </div>

                <div class="custom-control custom-checkbox mb-3">
                  <input type="checkbox" class="custom-control-input" name="save_card" id="save_card" value="1">
                  <label class="custom-control-label" for="save_card">Save this card for future use</label>
                </div>
              </div>
            </div>

            <!-- Submit Button -->
            <div class="form-group mt-4">
              <button type="submit" class="btn btn-primary" id="submitBtn" disabled>
                <i class="fas fa-credit-card"></i> Process Charge
              </button>
              <a href="{{ route('dashboard') }}" class="btn btn-secondary">
                <i class="fas fa-times"></i> Cancel
              </a>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@section('pageJs')
<script>
  $(document).ready(function() {
    let selectedPaymentProfileId = null;

    // Agent selection change
    $('#agent_id').on('change', function() {
      const agentId = $(this).val();

      if (agentId) {
        $('#paymentMethodSection').show();
        loadAgentCards(agentId);
        checkFormValidity();
      } else {
        $('#paymentMethodSection').hide();
        $('#submitBtn').prop('disabled', true);
      }
    });

    // Load agent's saved cards
    function loadAgentCards(agentId) {
      $('#savedCardsList').html('<p class="text-muted">Loading saved cards...</p>');

      $.ajax({
        url: `/accounting/charge-agent/cards/${agentId}`,
        method: 'GET',
        success: function(cards) {
          let html = '';

          if (cards.length > 0) {
            cards.forEach(function(card) {
              html += `
                            <div class="card-option" data-profile-id="${card.payment_profile_id}">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <i class="fas fa-credit-card"></i>
                                        <strong>${card.cardType}</strong> ${card.cardNumber}
                                    </div>
                                    <div>
                                        <small>Exp: ${card.expDate}</small>
                                    </div>
                                </div>
                            </div>
                        `;
            });
          } else {
            html = '<p class="text-muted">No saved cards found. You must use a new card.</p>';
            $('#useNewCard').prop('checked', true).trigger('change');
            $('#savedCardsSection').hide();
          }

          $('#savedCardsList').html(html);

          // Select first card by default if available
          if (cards.length > 0) {
            $('.card-option:first').click();
          }
        },
        error: function() {
          $('#savedCardsList').html('<p class="text-danger">Error loading cards. Please try again.</p>');
        }
      });
    }

    // Card selection
    $(document).on('click', '.card-option', function() {
      $('.card-option').removeClass('selected');
      $(this).addClass('selected');
      selectedPaymentProfileId = $(this).data('profile-id');
      $('input[name="payment_profile_id"]').remove();
      $('#chargeAgentForm').append(`<input type="hidden" name="payment_profile_id" value="${selectedPaymentProfileId}">`);
      $('#payment_method').val('saved_card');
      checkFormValidity();
    });

    // Toggle new card form
    $('#useNewCard').on('change', function() {
      if ($(this).is(':checked')) {
        $('#newCardForm').show();
        $('.card-option').removeClass('selected');
        selectedPaymentProfileId = null;
        $('input[name="payment_profile_id"]').remove();
        $('#payment_method').val('new_card');

        // Make new card fields required
        $('#newCardForm').find('input[type="text"], select').not('[name="save_card"]').prop('required', true);
      } else {
        $('#newCardForm').hide();
        $('#payment_method').val('saved_card');

        // Remove required from new card fields
        $('#newCardForm').find('input, select').prop('required', false);

        // Select first card if available
        if ($('.card-option').length > 0) {
          $('.card-option:first').click();
        }
      }
      checkFormValidity();
    });

    // Format card number input
    $('#card_number').on('input', function() {
      let value = $(this).val().replace(/\s/g, '');
      let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
      $(this).val(formattedValue);
    });

    // Check form validity
    function checkFormValidity() {
      const agentSelected = $('#agent_id').val() !== '';
      const amountValid = parseFloat($('#amount').val()) > 0;
      const paymentValid = $('#payment_method').val() === 'saved_card' ?
        selectedPaymentProfileId !== null :
        validateNewCardFields();

      $('#submitBtn').prop('disabled', !(agentSelected && amountValid && paymentValid));
    }

    // Validate new card fields
    function validateNewCardFields() {
      if (!$('#useNewCard').is(':checked')) return true;

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
    $('#amount, #card_number, #expire_month, #expire_year, #card_code, #billing_name, #billing_address, #billing_city, #billing_state, #billing_zip').on('input change', checkFormValidity);

    // Form submission
    $('#chargeAgentForm').on('submit', function(e) {
      e.preventDefault();

      const amount = parseFloat($('#amount').val());
      const agentName = $('#agent_id option:selected').text();

      if (confirm(`Are you sure you want to charge $${amount.toFixed(2)} to ${agentName}?`)) {
        $('#submitBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');
        this.submit();
      }
    });

    // Initialize form if agent is pre-selected
    if ($('#agent_id').val()) {
      $('#agent_id').trigger('change');
    }
  });
</script>
@endsection