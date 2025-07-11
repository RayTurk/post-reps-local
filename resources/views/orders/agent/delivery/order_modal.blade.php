<!-- Modal -->
<div class="modal fade" id="deliveryOrderModal" data-keyboard="true" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content auth-card">
            <div class="modal-header">
                <h5 class="modal-title">Delivery Order</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="deliveryOrderForm" action="{{ url('delivery/store') }}" enctype="multipart/form-data" method="post">
                    @csrf
                    <div class="row">
                        {{-- office and agent --}}
                        <div class="col-12">
                            @include('orders.agent.delivery.office_agent')
                        </div>
                        {{-- property information --}}
                        <div class="col-12  mt-4">
                            @include('orders.agent.delivery.property_information')
                        </div>
                        {{-- desired date --}}
                        <div class="col-12 col-md-6 col-lg-6 mt-4">
                            @include('orders.agent.delivery.service_date')
                        </div>
                        {{-- signpost and  accessorries --}}
                        <div class="col-12   mt-4">
                            @include('orders.agent.delivery.posts_signs_accessories')
                        </div>
                        {{-- Comment --}}
                        <div class="col-12   mt-4">
                            @include('orders.agent.delivery.comment')
                        </div>
                        {{-- Footer --}}
                        <div class="col-12   mt-4">
                            @include('orders.agent.delivery.footer')
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
            </div>
        </div>
    </div>
</div>
