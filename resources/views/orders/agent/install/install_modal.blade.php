<!-- Modal -->
<div class="modal fade" id="install_post_modal" data-keyboard="true" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content auth-card">
            <div class="modal-header">
                <h5 class="modal-title">Install Order</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="{{ route('install_post') }}" enctype="multipart/form-data" method="post">
                    @csrf
                    <div class="row">
                        {{-- office and agent --}}
                        <div class="col-12">
                            @include('orders.agent.install.office_and_agent')
                        </div>
                        {{-- property information --}}
                        <div class="col-12  mt-4">
                            @include('orders.agent.install.property_information')
                        </div>
                        {{-- desired date --}}
                        <div class="col-12  mt-4">
                            @include('orders.agent.install.desired_date')
                        </div>
                        {{-- signpost and  accessorries --}}
                        <div class="col-12   mt-4">
                            @include('orders.agent.install.signpost_and_accessories')
                        </div>
                        {{-- Comment --}}
                        <div class="col-12   mt-4">
                            @include('orders.agent.install.comment')
                        </div>
                        {{-- Attachments --}}
                        <div class="col-12   mt-5" id="attachments">
                            @include('orders.agent.install.attachments')
                        </div>
                        {{-- Adjustments --}}
                        <div class="col-12   mt-5" id="attachments">
                            @include('orders.agent.install.pricing_adjustment_modal')
                        </div>
                        {{-- Footer --}}
                        <div class="col-12   mt-4">
                            @include('orders.agent.install.install_post_footer')
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
            </div>
        </div>
    </div>
</div>
