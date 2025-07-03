import helper from "./helper";
import communication from "./communication";

const NoticeCommunication = {
    init() {
        communication.init();
        console.log('Notices page');
        this.createNotice();
        this.editNotice();
        this.loadNoticeDetails();
        this.deleteNotice();
        $('#start_date').datepicker({
            dateFormat: 'm/d/yy',
            minDate: new Date(),
        });
        $('#end_date').datepicker({
            dateFormat: 'm/d/yy',
            minDate: new Date(),
        });
        $('#detail_start_date').datepicker({
            dateFormat: 'm/d/yy',
            minDate: new Date(),
        });
        $('#detail_end_date').datepicker({
            dateFormat: 'm/d/yy',
            minDate: new Date(),
        });
    },

    createNotice() {
        document.querySelectorAll('#createNotice').forEach((item) => {
            item.addEventListener('click', (event) => {
                let modal = $('#noticeModal');
                let form = $("#noticeForm");
                form.attr('action', `${helper.getSiteUrl()}/communications/notices`);
                event.preventDefault();
                modal.find('.modal-title').text('Create Notice');
                this.resetModal(modal);
            });
        });
    },

    editNotice() {
        document.querySelectorAll('#editNotice').forEach((item) => {
            item.addEventListener('click', (event) => {
                helper.showLoader();
                let noticeid = event.currentTarget.dataset.noticeid;
                let modal = $('#noticeModal');
                let form = $("#noticeForm");
                form.attr('action', `${helper.getSiteUrl()}/communications/notices/${noticeid}`);
                event.preventDefault();
                modal.find('.modal-title').text('Edit Notice');
                this.resetModal(modal);
                $.get(`${helper.getSiteUrl()}/communications/notices/${noticeid}`).done((notice) => {
                    modal.find('#start_date').val(helper.formatDateUsa(notice.start_date));
                    modal.find('#end_date').val(helper.formatDateUsa(notice.end_date));
                    modal.find('#subject').val(notice.subject);
                    modal.find('#details').val(notice.details);
                    helper.hideLoader();
                });
            });
        });
    },

    loadNoticeDetails() {
        document.querySelectorAll('#loadNoticeDetails').forEach((item) => {
            item.addEventListener('click', (event) => {
                helper.showLoader();
                let noticeid = event.currentTarget.dataset.noticeid;
                event.preventDefault();
                var modal = $('#noticeDetailsModal');
                this.resetModal();
                $.get(`${helper.getSiteUrl()}/communications/notices/${noticeid}`).done((notice) => {
                    modal.find('#detail_start_date').val(helper.formatDateUsa(notice.start_date));
                    modal.find('#detail_end_date').val(helper.formatDateUsa(notice.end_date));
                    modal.find('#subject').val(notice.subject);
                    modal.find('#details').val(notice.details);
                    helper.hideLoader();
                });
            });
        });
    },


    deleteNotice() {
        document.querySelectorAll('#deleteNotice').forEach((item) => {
            item.addEventListener('click', (event) => {
                event.preventDefault();
                let noticeid = event.currentTarget.dataset.noticeid;
                let form = $("#deleteForm");
                form.attr('action', `${helper.getSiteUrl()}/communications/notices/${noticeid}`);
                helper.confirm(
                    "",
                    "",
                    () => {
                        form.submit();
                    },
                    () => { "" }
                );
            });
        });
    },

    resetModal() {
        document.getElementById("noticeDetailsForm").reset();
        document.getElementById("noticeForm").reset();
    },
}

$(() => {
    NoticeCommunication.init();
});
