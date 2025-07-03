<div class="text-orange-dark d-flex align-items-center gap-px-4">
    <span class="cnumber">5</span>
    <h5 class="pt-2">SIGNPOST AND ACCESSORIES</h5>
</div>
<div class="px-4">
    <div class="row">

        <div class="col-12 col-md-4 col-lg-4 mt-2">
            <label for="repair_order_select_post" class="text-primary text-center d-block text-center">
                <span class="blue-label">Post Repair</span>
            </label>
            <div class="form-check form-check pl-4 py-2" style="background-color: #ced4da;">
                <input type="radio" id="repairOrderPost"  class="form-check-input" checked>
                <label class="form-check-label text-dark" for="repairOrderPost">Post name here</label>
            </div>
            <div class="list-container list-container-posts height-rem-11" id="repairOptionsContainer" >
                <div class="form-check d-flex justify-content-between">
                    <input
                        type="checkbox"
                        name="repair_options_post[]"
                        value="{{ $serviceSettings->repair_replace_post ?? 0 }}"
                        class="form-check-input"
                        id="repair_replace_post"
                    >
                    <label class="form-check-label text-dark" for="post_option_0">Replace/Repair Post</label>
                    <span >${{ $serviceSettings->repair_replace_post ?? 0 }}</span>
                </div>
                <div class="form-check d-flex justify-content-between">
                    <input
                        type="checkbox"
                        name="repair_options_post[]"
                        value="{{ $serviceSettings->relocate_post ?? 0 }}"
                        class="form-check-input"
                        id="relocate_post"
                    >
                    <label class="form-check-label text-dark" for="post_option_1">Relocate Post</label>
                    <span >${{ $serviceSettings->relocate_post ?? 0 }}</span>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4 col-lg-4 mt-2">
            <label for="repair_order_select_sign" class="text-primary d-block text-center"><span
                    class="blue-label">Swap Sign Panel</span></label>
            <div class="form-check form-check pl-4 py-2" id="selectedPanel" style="background-color: #ced4da; display:none;">
                <input type="radio" id="repairOrderPanel" class="form-check-input" checked>
                <label class="form-check-label text-dark" for="repairOrderPanel">Panel name here</label>
            </div>
            <div class="list-container-2">
            </div>
            <div class="list-container list-container-signs position-relative">

            </div>
        </div>
        <div class="col-12 col-md-4 col-lg-4 mt-2">
            <label for="repair_order_select_accessories" class="text-primary d-block text-center"><span
                    class="blue-label">Change Accessories</span></label>
            <div class="list-container list-container-accessories-repair">

            </div>
        </div>
        <div class="col-12 repair-order-preview-images d-flex justify-content-center align-items-center flex-wrap py-2"
            style="gap:10px">
            <img class="max-width-125px max-height-113px order-preview-imgs post-image d-none" id="repair_post_image_preview"
                src="{{ url('/private/image/post/0') }}">
            <img class="max-width-125px max-height-113px order-preview-imgs panel-image d-none" id="repair_sign_image_preview"
                src="{{ url('/private/image/panel/0') }}">
        </div>
    </div>
</div>
