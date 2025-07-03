import helper from "./helper";
import Order from "./order";

const OrderDetails = {
    viewInstallDetails(orderId, orderType) {
        helper.showLoader();

        const url = `${helper.getSiteUrl()}/order/${orderId}/${orderType}`;
        $.get(url)
        .done(order => {
            let agent = '';
            let office = order.office.user.name;
            let officePhone = order.office.user.phone;
            let agentPhone = '';
            if (order.agent) {
                agent = order.agent.user.name;
                agentPhone = order.agent.user.phone;
            }

            let propertyType = '';
            switch (order.property_type) {
                case 1:
                    propertyType = "Existing Home/Condo";
                    break;
                case 2:
                    propertyType = "New Construction";
                    break;
                case 3:
                    propertyType = "Vacant Land";
                    break;
                case 4:
                    propertyType = "Commercial/Industrial";
                    break;
            }

            if (order.desired_date_type == Order.date.asap) {
                order.service_date = 'Rush Order';
            } else {
                order.service_date = helper.formatDateUsa(order.desired_date);
            }

            let post = '';
            let post_image = '';
            let panel_image = '';
            let panel = '';
            let accessories = '';
            let accessories_images = '';

            post = order.post.post_name
            post_image = `<img style="max-width: 4.8rem; max-height: 5rem;" src="${helper.getSiteUrl(`/private/image/post/${order.post.image_path}`)}">`;

            if(order.panel) {
                panel_image = `<img style="max-width: 4.8rem; max-height: 5rem;" src="${helper.getSiteUrl(`/private/image/panel/${order.panel.image_path}`)}">`;
                panel = order.panel.panel_name
            }else if(order.agent_own_sign) {
                panel = `Agent will Hang Own Sign`;
            }else if (order.sign_at_property) {
                panel = `Sign Left at Property`;
            }

            if (order.accessories) {
                $.each(order.accessories, (i, orderAcessory) => {
                    accessories += `${orderAcessory.accessory.accessory_name}, `;

                    accessories_images += `<img class="ml-1" style="max-width: 4.6rem; max-height: 4.8rem;" src="${helper.getSiteUrl(`/private/image/accessory/${orderAcessory.accessory.image}`)}">`;
                });
                accessories = accessories.replace(/\,\s+$/, '');
            }

            let installer_name = '';
            if (order.installer) {
                installer_name = order.installer.name;
                $('#OrderDetailsInstallerName').removeClass('d-none');
            } else {
                $('#OrderDetailsInstallerName').addClass('d-none');
            }

            let date_completed = '';
            if (order.status == Order.status_completed) {
                date_completed = helper.formatDateTime(order.date_completed);
                $('#OrderDetailsInstallerDateCompleted').removeClass('d-none');
            } else {
                $('#OrderDetailsInstallerDateCompleted').addClass('d-none');
            }

            let installer_comments = '';
            if (order.installer_comments) {
                installer_comments = order.installer_comments.replace(/undefined/g, '');
                $('#OrderDetailsInstallerComments').removeClass('d-none');
            } else {
                $('#OrderDetailsInstallerComments').addClass('d-none');
            }

            let attachments = '';
            if (order.attachments) {
                $.each(order.attachments, (i, attachment) => {
                    attachments += `<a
                        href="${helper.getSiteUrl(`/private/document/file/${attachment.name}`)}"
                        target="_blank"
                    >${attachment.name.substr(0, 40)}</a><br>`;
                });
                $('#OrderDetailsAttachments').removeClass('d-none');
            } else {
                $('#OrderDetailsAttachments').addClass('d-none');
            }

            let installer_photos = '';
            if (order.photo1) {
                installer_photos += `<a
                    class="ml-2"
                    href="${helper.getSiteUrl(`/private/image/accessory/${order.photo1}`)}"
                    target="_blank"
                >
                    <img style="max-width: 4.6rem; max-height: 4.8rem;" src="${helper.getSiteUrl(`/private/image/accessory/${order.photo1}`)}">
                </a>`;
            }
            if (order.photo2) {
                installer_photos += `<a
                    class="ml-2"
                    href="${helper.getSiteUrl(`/private/image/accessory/${order.photo2}`)}"
                    target="_blank"
                >
                    <img style="max-width: 4.6rem; max-height: 4.8rem;" src="${helper.getSiteUrl(`/private/image/accessory/${order.photo2}`)}">
                </a>`;
            }
            if (order.photo3) {
                installer_photos += `<a
                    class="ml-2"
                    href="${helper.getSiteUrl(`/private/image/accessory/${order.photo3}`)}"
                    target="_blank"
                >
                    <img style="max-width: 4.6rem; max-height: 4.8rem;" src="${helper.getSiteUrl(`/private/image/accessory/${order.photo3}`)}">
                </a>`;
            }

            if (order.photo1 || order.photo2 || order.photo3) {
                $('#OrderDetailsInstallerPhotos').removeClass('d-none');
            } else {
                $('#OrderDetailsInstallerPhotos').addClass('d-none');
            }

            const fields = {
                created_at: helper.formatDateTime(order.created_at),
                office: `${office} <a href="tel:${officePhone}" class="font-weight-bold">${officePhone}</a>`,
                agent: `${agent} <a href="tel:${agentPhone}" class="font-weight-bold">${agentPhone}</a>`,
                address: order.address,
                property_type: propertyType,
                service_date: order.service_date,
                status: Order.getStatus(order.status, orderType),
                post: post,
                panel: panel,
                post_image: post_image,
                panel_image: panel_image,
                accessories: accessories,
                accessories_images: accessories_images,
                comments: order.comment,
                installer_name: installer_name,
                date_completed: date_completed,
                installer_comments: installer_comments,
                attachments: attachments,
                installer_photos: installer_photos,
                order_total: `$${parseFloat(order.total).toFixed(2)}`,
            };

            $('#orderDetailsModalBody').find('span').each( (i, el) => {
                const elem = $(el);

                $.each(fields, (attr, val) => {
                    if (elem[0].hasAttribute(attr)) {
                        elem.html(val);
                    }
                });
            });

            $('#orderDetailsHeader').html(`Install Order Details - #${order.order_number}`);

            helper.hideLoader('orderDetailsModal');
        })
        .fail(res => {
            helper.hideLoader();
            helper.alertError(helper.serverErrorMessage());
        })
    },

    viewRepairDetails(orderId, orderType) {
        helper.showLoader();

        const url = `${helper.getSiteUrl()}/order/${orderId}/${orderType}`;
        $.get(url)
        .done(order => {

            const installOrder = order.order;

            let agent = '';
            let office = installOrder.office.user.name;
            let officePhone = installOrder.office.user.phone;
            let agentPhone = '';
            if (installOrder.agent) {
                agent = installOrder.agent.user.name;
                agentPhone = installOrder.agent.user.phone;
            }

            let propertyType = '';
            switch (installOrder.property_type) {
                case 1:
                    propertyType = "Existing Home/Condo";
                    break;
                case 2:
                    propertyType = "New Construction";
                    break;
                case 3:
                    propertyType = "Vacant Land";
                    break;
                case 4:
                    propertyType = "Commercial/Industrial";
                    break;
            }

            if (order.service_date_type == Order.date.asap) {
                order.service_date = 'Rush Order';
            } else {
                order.service_date = helper.formatDateUsa(order.service_date);
            }

            let post = '';
            let post_image = '';
            let panel_image = '';
            let panel = '';
            let accessories = '';
            let accessories_images = '';

            post = installOrder.post.post_name
            post_image = `<img style="max-width: 4.8rem; max-height: 5rem;" src="${helper.getSiteUrl(`/private/image/post/${installOrder.post.image_path}`)}">`;

            if( ! order.panel) {
                if ( ! $.isEmptyObject(installOrder.panel)) {
                    panel_image = `<img style="max-width: 4.8rem; max-height: 5rem;" src="${helper.getSiteUrl(`/private/image/panel/${installOrder.panel.image_path}`)}">`;
                    panel = installOrder.panel.panel_name
                }else if(installOrder.agent_own_sign) {
                    panel = `Agent will Hang Own Sign`;
                }else if (installOrder.sign_at_property) {
                    panel = `Sign Left at Property`;
                }
            } else {
                panel_image = `<img style="max-width: 4.8rem; max-height: 5rem;" src="${helper.getSiteUrl(`/private/image/panel/${order.panel.image_path}`)}">`;
                panel = order.panel.panel_name
            }

            if( order.panel) {
                panel_image = `<img style="max-width: 4.8rem; max-height: 5rem;" src="${helper.getSiteUrl(`/private/image/panel/${order.panel.image_path}`)}">`;
                panel = order.panel.panel_name
            }else if(order.agent_own_sign) {
                panel = `Agent will Hang Own Sign`;
            }else if (order.sign_at_property) {
                panel = `Sign Left at Property`;
            }

            if ($.isEmptyObject(order.accessories)) {
                if ( ! $.isEmptyObject(installOrder.accessories)) {
                    $.each(installOrder.accessories, (i, orderAcessory) => {
                        accessories += `${orderAcessory.accessory.accessory_name}, `;

                        accessories_images += `<img class="ml-1" style="max-width: 4.6rem; max-height: 4.8rem;" src="${helper.getSiteUrl(`/private/image/accessory/${orderAcessory.accessory.image}`)}">`;
                    });
                    accessories = accessories.replace(/\,\s+$/, '');
                }
            } else {
                $.each(order.accessories, (i, orderAcessory) => {
                    accessories += `${orderAcessory.accessory.accessory_name}, `;

                    accessories_images += `<img class="ml-1" style="max-width: 4.6rem; max-height: 4.8rem;" src="${helper.getSiteUrl(`/private/image/accessory/${orderAcessory.accessory.image}`)}">`;
                });
                accessories = accessories.replace(/\,\s+$/, '');
            }

            let installer_name = '';
            if (order.installer) {
                installer_name = order.installer.name;
                $('#OrderDetailsInstallerName').removeClass('d-none');
            } else {
                $('#OrderDetailsInstallerName').addClass('d-none');
            }

            let date_completed = '';
            if (order.status == Order.status_completed) {
                date_completed = helper.formatDateTime(order.date_completed);
                $('#OrderDetailsInstallerDateCompleted').removeClass('d-none');
            } else {
                $('#OrderDetailsInstallerDateCompleted').addClass('d-none');
            }

            let installer_comments = '';
            if (order.installer_comments) {
                installer_comments = order.installer_comments.replace(/undefined/g, '');
                $('#OrderDetailsInstallerComments').removeClass('d-none');
            } else {
                $('#OrderDetailsInstallerComments').addClass('d-none');
            }

            let attachments = '';
            if (order.attachments) {
                $.each(order.attachments, (i, attachment) => {
                    attachments += `<a
                        href="${helper.getSiteUrl(`/private/document/file/${attachment.name}`)}"
                        target="_blank"
                    >${attachment.name.substr(0, 40)}</a><br>`;
                });
                $('#OrderDetailsAttachments').removeClass('d-none');
            } else {
                $('#OrderDetailsAttachments').addClass('d-none');
            }

            let installer_photos = '';
            if (order.photo1) {
                installer_photos += `<a
                    class="ml-2"
                    href="${helper.getSiteUrl(`/private/image/accessory/${order.photo1}`)}"
                    target="_blank"
                >
                    <img style="max-width: 4.6rem; max-height: 4.8rem;" src="${helper.getSiteUrl(`/private/image/accessory/${order.photo1}`)}">
                </a>`;
            }
            if (order.photo2) {
                installer_photos += `<a
                    class="ml-2"
                    href="${helper.getSiteUrl(`/private/image/accessory/${order.photo2}`)}"
                    target="_blank"
                >
                    <img style="max-width: 4.6rem; max-height: 4.8rem;" src="${helper.getSiteUrl(`/private/image/accessory/${order.photo2}`)}">
                </a>`;
            }
            if (order.photo3) {
                installer_photos += `<a
                    class="ml-2"
                    href="${helper.getSiteUrl(`/private/image/accessory/${order.photo3}`)}"
                    target="_blank"
                >
                    <img style="max-width: 4.6rem; max-height: 4.8rem;" src="${helper.getSiteUrl(`/private/image/accessory/${order.photo3}`)}">
                </a>`;
            }

            if (order.photo1 || order.photo2 || order.photo3) {
                $('#OrderDetailsInstallerPhotos').removeClass('d-none');
            } else {
                $('#OrderDetailsInstallerPhotos').addClass('d-none');
            }

            const fields = {
                created_at: helper.formatDateTime(order.created_at),
                office: `${office} <a href="tel:${officePhone}" class="font-weight-bold">${officePhone}</a>`,
                agent: `${agent} <a href="tel:${agentPhone}" class="font-weight-bold">${agentPhone}</a>`,
                address: installOrder.address,
                property_type: propertyType,
                service_date: order.service_date,
                status: Order.getStatus(order.status, orderType),
                post: post,
                panel: panel,
                post_image: post_image,
                panel_image: panel_image,
                accessories: accessories,
                accessories_images: accessories_images,
                comments: order.comment,
                installer_name: installer_name,
                date_completed: date_completed,
                installer_comments: installer_comments,
                attachments: attachments,
                installer_photos: installer_photos,
                order_total: `$${parseFloat(order.total).toFixed(2)}`,
            };

            $('#orderDetailsModalBody').find('span').each( (i, el) => {
                const elem = $(el);

                $.each(fields, (attr, val) => {
                    if (elem[0].hasAttribute(attr)) {
                        elem.html(val);
                    }
                });
            });

            $('#orderDetailsHeader').html(`Repair Order Details - #${order.order_number}`);

            helper.hideLoader('orderDetailsModal');
        })
        .fail(res => {
            helper.hideLoader();
            helper.alertError(helper.serverErrorMessage());
        })
    },

    viewRemovalDetails(orderId, orderType) {
        helper.showLoader();

        const url = `${helper.getSiteUrl()}/order/${orderId}/${orderType}`;
        $.get(url)
        .done(order => {

            const installOrder = order.order;

            let repairOrder = {};
            if (installOrder.repair) {
                repairOrder = installOrder.repair;
            }

            let agent = '';
            let office = installOrder.office.user.name;
            let officePhone = installOrder.office.user.phone;
            let agentPhone = '';
            if (installOrder.agent) {
                agent = installOrder.agent.user.name;
                agentPhone = installOrder.agent.user.phone;
            }

            let propertyType = '';
            switch (installOrder.property_type) {
                case 1:
                    propertyType = "Existing Home/Condo";
                    break;
                case 2:
                    propertyType = "New Construction";
                    break;
                case 3:
                    propertyType = "Vacant Land";
                    break;
                case 4:
                    propertyType = "Commercial/Industrial";
                    break;
            }

            if (order.service_date_type == Order.date.asap) {
                order.service_date = 'Rush Order';
            } else {
                order.service_date = helper.formatDateUsa(order.service_date);
            }

            let post = '';
            let post_image = '';
            let panel_image = '';
            let panel = '';
            let accessories = '';
            let accessories_images = '';

            post = installOrder.post.post_name
            post_image = `<img style="max-width: 4.8rem; max-height: 5rem;" src="${helper.getSiteUrl(`/private/image/post/${installOrder.post.image_path}`)}">`;

            if ($.isEmptyObject(repairOrder)) {
                if ( ! $.isEmptyObject(installOrder.panel)) {
                    panel_image = `<img style="max-width: 4.8rem; max-height: 5rem;" src="${helper.getSiteUrl(`/private/image/panel/${installOrder.panel.image_path}`)}">`;
                    panel = installOrder.panel.panel_name
                }else if(installOrder.agent_own_sign) {
                    panel = `Agent will Hang Own Sign`;
                }else if (installOrder.sign_at_property) {
                    panel = `Sign Left at Property`;
                }
            } else {
                if ( ! $.isEmptyObject(repairOrder.panel)) {
                    panel_image = `<img style="max-width: 4.8rem; max-height: 5rem;" src="${helper.getSiteUrl(`/private/image/panel/${repairOrder.panel.image_path}`)}">`;
                    panel = repairOrder.panel.panel_name
                } else {
                    if ( ! $.isEmptyObject(installOrder.panel)) {
                        panel_image = `<img style="max-width: 4.8rem; max-height: 5rem;" src="${helper.getSiteUrl(`/private/image/panel/${installOrder.panel.image_path}`)}">`;
                        panel = installOrder.panel.panel_name
                    }else if(installOrder.agent_own_sign) {
                        panel = `Agent will Hang Own Sign`;
                    }else if (installOrder.sign_at_property) {
                        panel = `Sign Left at Property`;
                    }
                }
            }

            if ($.isEmptyObject(repairOrder)) {
                if ( ! $.isEmptyObject(installOrder.accessories)) {
                    $.each(installOrder.accessories, (i, orderAcessory) => {
                        accessories += `${orderAcessory.accessory.accessory_name}, `;

                        accessories_images += `<img class="ml-1" style="max-width: 4.6rem; max-height: 4.8rem;" src="${helper.getSiteUrl(`/private/image/accessory/${orderAcessory.accessory.image}`)}">`;
                    });
                    accessories = accessories.replace(/\,\s+$/, '');
                }
            } else {
                if ( ! $.isEmptyObject(repairOrder.accessories)) {
                    $.each(repairOrder.accessories, (i, orderAcessory) => {
                        accessories += `${orderAcessory.accessory.accessory_name}, `;

                        accessories_images += `<img class="ml-1" style="max-width: 4.6rem; max-height: 4.8rem;" src="${helper.getSiteUrl(`/private/image/accessory/${orderAcessory.accessory.image}`)}">`;
                    });
                    accessories = accessories.replace(/\,\s+$/, '');
                } else {
                    $.each(installOrder.accessories, (i, orderAcessory) => {
                        accessories += `${orderAcessory.accessory.accessory_name}, `;

                        accessories_images += `<img class="ml-1" style="max-width: 4.6rem; max-height: 4.8rem;" src="${helper.getSiteUrl(`/private/image/accessory/${orderAcessory.accessory.image}`)}">`;
                    });
                    accessories = accessories.replace(/\,\s+$/, '');
                }
            }

            $('#installItems').removeClass('d-none');
            $('#deliveryItems').addClass('d-none');

            let installer_name = '';
            if (order.installer) {
                installer_name = order.installer.name;
                $('#OrderDetailsInstallerName').removeClass('d-none');
            } else {
                $('#OrderDetailsInstallerName').addClass('d-none');
            }

            let date_completed = '';
            if (order.status == Order.status_completed) {
                date_completed = helper.formatDateTime(order.date_completed);
                $('#OrderDetailsInstallerDateCompleted').removeClass('d-none');
            } else {
                $('#OrderDetailsInstallerDateCompleted').addClass('d-none');
            }

            let installer_comments = '';
            if (order.installer_comments) {
                installer_comments = order.installer_comments.replace(/undefined/g, '');
                $('#OrderDetailsInstallerComments').removeClass('d-none');
            } else {
                $('#OrderDetailsInstallerComments').addClass('d-none');
            }

            let attachments = '';
            if (order.attachments) {
                $.each(order.attachments, (i, attachment) => {
                    attachments += `<a
                        href="${helper.getSiteUrl(`/private/document/file/${attachment.name}`)}"
                        target="_blank"
                    >${attachment.name.substr(0, 40)}</a><br>`;
                });
                $('#OrderDetailsAttachments').removeClass('d-none');
            } else {
                $('#OrderDetailsAttachments').addClass('d-none');
            }

            let installer_photos = '';
            if (order.photo1) {
                installer_photos += `<a
                    class="ml-2"
                    href="${helper.getSiteUrl(`/private/image/accessory/${order.photo1}`)}"
                    target="_blank"
                >
                    <img style="max-width: 4.6rem; max-height: 4.8rem;" src="${helper.getSiteUrl(`/private/image/accessory/${order.photo1}`)}">
                </a>`;
            }
            if (order.photo2) {
                installer_photos += `<a
                    class="ml-2"
                    href="${helper.getSiteUrl(`/private/image/accessory/${order.photo2}`)}"
                    target="_blank"
                >
                    <img style="max-width: 4.6rem; max-height: 4.8rem;" src="${helper.getSiteUrl(`/private/image/accessory/${order.photo2}`)}">
                </a>`;
            }
            if (order.photo3) {
                installer_photos += `<a
                    class="ml-2"
                    href="${helper.getSiteUrl(`/private/image/accessory/${order.photo3}`)}"
                    target="_blank"
                >
                    <img style="max-width: 4.6rem; max-height: 4.8rem;" src="${helper.getSiteUrl(`/private/image/accessory/${order.photo3}`)}">
                </a>`;
            }

            if (order.photo1 || order.photo2 || order.photo3) {
                $('#OrderDetailsInstallerPhotos').removeClass('d-none');
            } else {
                $('#OrderDetailsInstallerPhotos').addClass('d-none');
            }

            const fields = {
                created_at: helper.formatDateTime(order.created_at),
                office: `${office} <a href="tel:${officePhone}" class="font-weight-bold">${officePhone}</a>`,
                agent: `${agent} <a href="tel:${agentPhone}" class="font-weight-bold">${agentPhone}</a>`,
                address: installOrder.address,
                property_type: propertyType,
                service_date: order.service_date,
                status: Order.getStatus(order.status, orderType),
                post: post,
                panel: panel,
                post_image: post_image,
                panel_image: panel_image,
                accessories: accessories,
                accessories_images: accessories_images,
                comments: order.comment,
                installer_name: installer_name,
                date_completed: date_completed,
                installer_comments: installer_comments,
                attachments: attachments,
                installer_photos: installer_photos,
                order_total: `$${parseFloat(order.total).toFixed(2)}`,
            };

            $('#orderDetailsModalBody').find('span').each( (i, el) => {
                const elem = $(el);

                $.each(fields, (attr, val) => {
                    if (elem[0].hasAttribute(attr)) {
                        elem.html(val);
                    }
                });
            });

            $('#orderDetailsHeader').html(`Removal Order Details - #${order.order_number}`);

            helper.hideLoader('orderDetailsModal');
        })
        .fail(res => {
            helper.hideLoader();
            helper.alertError(helper.serverErrorMessage());
        })
    },

    viewDeliveryDetails(orderId, orderType) {
        helper.showLoader();

        const url = `${helper.getSiteUrl()}/order/${orderId}/${orderType}`;
        $.get(url)
        .done(order => {
            let agent = '';
            let office = order.office.user.name;
            let officePhone = order.office.user.phone;
            let agentPhone = '';
            if (order.agent) {
                agent = order.agent.user.name;
                agentPhone = order.agent.user.phone;
            }

            if (order.service_date_type == Order.date.asap) {
                order.service_date = 'Rush Order';
            } else {
                order.service_date = helper.formatDateUsa(order.service_date);
            }

            let pickups = '';
            if ( ! $.isEmptyObject(order.pickups)) {
                $.each(order.pickups, (i, pickup) => {
                    pickups += `${pickup.quantity} ${pickup.panel.panel_name}<br>`;
                });
            }

            let dropoffs = '';
            if ( ! $.isEmptyObject(order.dropoffs)) {
                $.each(order.dropoffs, (i, dropoff) => {
                    dropoffs += `${dropoff.quantity} ${dropoff.panel.panel_name}<br>`;
                });
            }

            let installer_name = '';
            if (order.installer) {
                installer_name = order.installer.name;
                $('#deliveryDetailsInstallerName').removeClass('d-none');
            } else {
                $('#deliveryDetailsInstallerName').addClass('d-none');
            }

            let date_completed = '';
            if (order.status == Order.status_completed) {
                date_completed = helper.formatDateTime(order.date_completed);
                $('#deliveryDetailsInstallerDateCompleted').removeClass('d-none');
            } else {
                $('#deliveryDetailsInstallerDateCompleted').addClass('d-none');
            }

            let installer_comments = '';
            if (order.installer_comments) {
                installer_comments = order.installer_comments.replace(/undefined/g, '');
                $('#deliveryDetailsInstallerComments').removeClass('d-none');
            } else {
                $('#deliveryDetailsInstallerComments').addClass('d-none');
            }

            let installer_photos = '';
            if (order.photo1) {
                installer_photos += `<a
                    class="ml-2"
                    href="${helper.getSiteUrl(`/private/image/accessory/${order.photo1}`)}"
                    target="_blank"
                >
                    <img style="max-width: 4.6rem; max-height: 4.8rem;" src="${helper.getSiteUrl(`/private/image/accessory/${order.photo1}`)}">
                </a>`;
            }
            if (order.photo2) {
                installer_photos += `<a
                    class="ml-2"
                    href="${helper.getSiteUrl(`/private/image/accessory/${order.photo2}`)}"
                    target="_blank"
                >
                    <img style="max-width: 4.6rem; max-height: 4.8rem;" src="${helper.getSiteUrl(`/private/image/accessory/${order.photo2}`)}">
                </a>`;
            }
            if (order.photo3) {
                installer_photos += `<a
                    class="ml-2"
                    href="${helper.getSiteUrl(`/private/image/accessory/${order.photo3}`)}"
                    target="_blank"
                >
                    <img style="max-width: 4.6rem; max-height: 4.8rem;" src="${helper.getSiteUrl(`/private/image/accessory/${order.photo3}`)}">
                </a>`;
            }

            if (order.photo1 || order.photo2 || order.photo3) {
                $('#deliveryDetailsInstallerPhotos').removeClass('d-none');
            } else {
                $('#deliveryDetailsInstallerPhotos').addClass('d-none');
            }

            const fields = {
                created_at: helper.formatDateTime(order.created_at),
                office: `${office} <a href="tel:${officePhone}" class="font-weight-bold">${officePhone}</a>`,
                agent: `${agent} <a href="tel:${agentPhone}" class="font-weight-bold">${agentPhone}</a>`,
                address: order.address,
                service_date: order.service_date,
                status: Order.getStatus(order.status, orderType),
                comments: order.comment,
                installer_name: installer_name,
                date_completed: date_completed,
                installer_comments: installer_comments,
                installer_photos: installer_photos,
                order_total: `$${parseFloat(order.total).toFixed(2)}`,
                pickups: pickups,
                dropoffs: dropoffs
            };

            $('#deliveryDetailsModalBody').find('span').each( (i, el) => {
                const elem = $(el);

                $.each(fields, (attr, val) => {
                    if (elem[0].hasAttribute(attr)) {
                        elem.html(val);
                    }
                });
            });

            $('#deliveryDetailsHeader').html(`Delivery Order Details - #${order.order_number}`);

            helper.hideLoader('deliveryDetailsModal');
        })
        .fail(res => {
            helper.hideLoader();
            helper.alertError(helper.serverErrorMessage());
        })
    },


}

export default OrderDetails;
