import helper from './helper'

const communication = {

    init() {

        if (window.location.href.indexOf('/communications') != -1) {
            console.log('communications page');
            if (
                window.location.href.indexOf('emails') == -1
                && window.location.href.indexOf('feedback') == -1
            ) {
                $('[id=communicationsEmails]').removeClass('order-tab-active');
                $('[id=communicationsNotices]').addClass('order-tab-active');
                $('[id=communicationsFeedback]').removeClass('order-tab-active');
            }
            if (
                window.location.href.indexOf('emails') != -1
                && window.location.href.indexOf('feedback') == -1
            ) {
                $('[id=communicationsEmails]').addClass('order-tab-active');
                $('[id=communicationsNotices]').removeClass('order-tab-active');
                $('[id=communicationsFeedback]').removeClass('order-tab-active');
            }

            if (window.location.href.indexOf('feedback') != -1) {
                $('[id=communicationsEmails]').removeClass('order-tab-active');
                $('[id=communicationsNotices]').removeClass('order-tab-active');
                $('[id=communicationsFeedback]').addClass('order-tab-active');
            }
        }

    }

};

$(() => {
    communication.init();
});

export default communication;