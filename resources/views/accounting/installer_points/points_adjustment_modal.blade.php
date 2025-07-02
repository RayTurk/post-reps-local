<!-- Modal -->
<div class="modal fade" id="editPointsModal" tabindex="-1" aria-labelledby="editPointsModal" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content auth-card">
            <div class="modal-header text-center">
                <h5 class="modal-title font-weight-bold w-100" id="editPointsModal">EDIT POINTS</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="w-100">
                    <form action="" id="pointsAdjustmentForm" method="POST">
                        @csrf
                        <div class="form-group">
                            <label for="pointsAdjustment">Points</label>
                            <input type="number" step="0.01" name="points_adjustment_qty" class="width-px-160 form-control text-right" id="pointsAdjustment" required>
                        </div>
                        <div class="text-center">
                            <button type="submit" class="mt-3 btn btn-orange font-weigth-bold text-white" id="pointsAdjustmentSubmit">Submit</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
