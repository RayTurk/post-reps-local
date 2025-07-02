import InstallPost from "./install_post";
import Order from "./order";
import helper from './helper';

let dashboard = {

    userRole: {
        superAdmin: 1,
        office: 2,
        agent: 3,
        installer: 4
    },

    init() {
        if($('#userRole').val() == this.userRole.superAdmin){
            if (! helper.urlContains('/delivery')
                && ! helper.urlContains('/repair')
                && ! helper.urlContains('/removal')
            ) {
                InstallPost.init();
                Order.init();
            }
        }

        $('.order-repair').on('click', () => {
            window.location.href = `${helper.getSiteUrl()}/repair`;
        });

        $('.order-removal').on('click', () => {
            window.location.href = `${helper.getSiteUrl()}/removal`;
        });

        $('.order-delivery').on('click', () => {
            window.location.href = `${helper.getSiteUrl()}/delivery`;
        });

        $('.order-status').on('click', () => {
            window.location.href = `${helper.getSiteUrl()}/order/status`;
        });
    }
};

$(() => {
    dashboard.init();
});
