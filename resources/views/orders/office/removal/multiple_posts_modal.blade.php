<!-- Modal -->
<div class="modal fade" id="multiplePostsModal" tabindex="-1" aria-labelledby="multiplePostsModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-orange ">
            <div class="modal-header bg-orange  d-flex justify-content-center">
                <h5 class="modal-title text-white" id="multiplePostsModalLabel"><i class="fas fa-exclamation-triangle"></i>
                    MULTIPLE POSTS DETECTED</h5>
            </div>
            <div class="modal-body ">
                <strong>
                    There are multiple posts installed at this address.
                    Would you like to remove all posts from this property?
                </strong>
                <div class="d-flex justify-content-around align-items-center mt-3">
                    <button multiple-posts-yes-button data-dismiss-modal="#multiplePostsModal"
                        class="btn btn-orange text-white width-rem-10"><strong>YES</strong></button>
                    <button multiple-posts-no-button data-dismiss-modal="#multiplePostsModal"
                        class="btn btn-primary text-white width-rem-10"><strong>NO</strong></button>
                </div>
            </div>

        </div>
    </div>
</div>
