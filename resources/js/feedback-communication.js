import helper from "./helper";
import communication from "./communication";

const FeedbackCommunication = {
    init() {
        communication.init();
        console.log('Feedback page');
        this.onCheckboxChange();
        $("#rating").rating({
            'emptyStar' : '<i class="far fa-star text-white"></i>',
            'filledStar' : '<i class="fas fa-star"></i>',
            'step' : 1,
            'showCaption' : false,
        });

        $("[id=ratingFeedbackPage]").rating({
            'size' : 'sm',
            'emptyStar' : '<i class="far fa-star text-dark"></i>',
            'filledStar' : '<i class="fas fa-star"></i>',
            'step' : 1,
            'showCaption' : false,
        });
    },

    onCheckboxChange() {
        $('[id=publish_checkbox]').on('change', (event) => {
            let form = event.currentTarget.closest("form");
            let formData = new FormData(form);
            let id = formData.get("orderId");
            $.ajax({
                type: "POST",
                url: helper.getSiteUrl(`/communications/feedback/${id}`),
                data: formData,
                processData: false,  // tell jQuery not to process the data
                contentType: false,  // tell jQuery not to set contentType
                success: (response) => {},
                error: (response) => {}
            });
        });
    },

}

$(() => {
    FeedbackCommunication.init();
});