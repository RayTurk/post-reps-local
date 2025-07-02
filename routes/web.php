<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

require_once 'email_verify_routes.php';

Route::get('/', 'HomeController@indexPage');

Route::get('/locations-served', function () {
    return view('locations');
});

Route::get('/locations-served/zones', 'HomeController@zones');

Route::get('/our-services', function () {
    return view('services');
});

Route::get('/contact', function () {
    return view('contact');
});

Route::get('/terms', function () {
    return view('terms');
});

Route::get('/privacy', function () {
    return view('privacy');
});

Route::post('/contact',  'HomeController@contact');

Route::get('/order/{type}/{id}/feedback', 'FeedbackController@create');
Route::post('/order/{type}/{id}/feedback', 'FeedbackController@store');


Auth::routes();
Route::post('/register', 'Auth\RegisterController@store');

Route::get('/account/inactive', function () {
    return view('auth.approve');
})->middleware(['auth', 'verified']);

Route::group(['middleware' => ['auth', 'verified'/*, 'approved'*/]], function () {
    Route::get('/current/user/role', 'UserController@getCurrentUserRole');
    Route::get('/current/user/balance', 'UserController@getCurrentUserBalance');
    Route::post('/change/password', 'UserController@changeAuthPassword');

    Route::get('/dashboard/{route_date?}', 'HomeController@index')->name('dashboard');
    Route::resource('regions', 'RegionController')->middleware(['can:manageLocations, App\Models\User']);

    //order routes
    Route::post('/install/post', 'OrderController@store')->name('install_post');
    Route::group(['as' => 'orders.'], function () {
        Route::get('/get/order/{order}', "OrderController@show")->name("get.order");
        Route::get('/order/{order}/cancel', 'OrderController@cancel');
        Route::get('/order/delete/file/{file}', "OrderController@deleteFile")->name("file.remove");
        Route::get('/order/{order}/mark-completed', 'OrderController@markCompleted');
        //Route::post('/order/delete/all', 'OrderController@deleteAll');
        Route::post('/order/status/delete/all', 'OrderController@deleteAllOrderStatus')->middleware(['can:Admin, App\Models\User']);
        Route::get('/order/email/{order}', 'OrderController@sendEmail');
        Route::get('/order/check/address/{address}/lat/{lat}/lng/{lng}/office/{office}/agent/{agent}/order/{orderId}', 'OrderController@checkOrderSameAddress');
        Route::get('/order/status/', 'OrderController@orderStatus');
        Route::get('/order/status/history', "OrderController@orderStatus");
        Route::get('/order/status/routes', 'OrderController@orderStatusRoutes')->middleware(['can:Admin, App\Models\User']);
        Route::get('/order/status/pull-list/{route_date?}/{installer_id?}', 'OrderController@orderStatusPullList')->middleware(['can:Admin, App\Models\User']);
        Route::post('/get/installer/orders', 'OrderController@getInstallerOrders');
        Route::post('/order/assign', 'OrderController@assignOrder');
        Route::post('/order/assign/update', 'OrderController@updateAssignedOrder');
        Route::post('/order/unassign', 'OrderController@unassignOrder')->middleware(['can:Admin, App\Models\User']);
        Route::post('/order/remove/stops', 'OrderController@removeStops')->middleware(['can:Admin, App\Models\User']);
        Route::post('/order/get/direction/', "OrderController@getDirection");
        Route::post('/get/installer/assigned/orders', 'OrderController@getInstallerAssignedOrders');
        Route::get('/installer/order/details/{order_id}/{order_type}', 'OrderController@installerOrderDetails')->middleware(['can:Installer, App\Models\User']);
        Route::get('/installer/map/view/{route_date?}', 'OrderController@installerMapView')->middleware(['can:Installer, App\Models\User']);
        Route::get('/installer/pull/list/{route_date?}', 'OrderController@installerPullList')->middleware(['can:Installer, App\Models\User']);
        Route::get('/installer/map/view/{type}/{order}/{route_date?}', 'OrderController@installerMapViewOrder')->middleware(['can:Installer, App\Models\User']);
        Route::get('/order/{id}/{type}', 'OrderController@getOrderByTypeAndId');
        Route::post('/order/action-needed/email', 'OrderController@sendActionNeededEmail');
    });

    //Repair orders
    Route::group(['middleware' => 'can:notInstaller, App\Models\User'], function () {
        Route::prefix('repair')->group(function () {
            Route::get('/', 'RepairOrderController@loadRepairPage');
            Route::get('/orders/datatable', 'RepairOrderController@repairOrdersDatatable');
            Route::get('/get/install-order/{order}', 'RepairOrderController@getOrderForRepairModal');
            Route::get('/get/zone/{order}', 'RepairOrderController@getRepairZone');
            Route::post('/store', 'RepairOrderController@store');
            Route::get('/get/order/{repair_order}', 'RepairOrderController@getOrder');
            Route::get('/order/email/{repair_order}', 'RepairOrderController@sendEmail');
            Route::post('/order/delete/all', 'RepairOrderController@deleteAll');
            Route::get('/order/{repair_order}/cancel', 'RepairOrderController@cancel');
            Route::get('/order/delete/file/{file}', "RepairOrderController@deleteFile")->name("file.remove");
            Route::get('/order/{repair_order}/mark-completed', 'RepairOrderController@markCompleted');
        });
    });

    //Removal orders
    Route::group(['middleware' => 'can:notInstaller, App\Models\User'], function () {
        Route::prefix('removal')->group(function () {
            Route::get('/', 'RemovalOrderController@loadPage');
            Route::get('/orders/datatable', 'RemovalOrderController@removalOrdersDatatable');
            Route::get('/get/install-order/{order}', 'RemovalOrderController@getOrderForRemovalModal');
            Route::get('/get/zone/{order}', 'RemovalOrderController@getRemovalZone');
            Route::post('/store', 'RemovalOrderController@store');
            Route::get('/get/order/{removal_order}', 'RemovalOrderController@getOrder');
            Route::get('/order/email/{removal_order}', 'RemovalOrderController@sendEmail');
            Route::post('/order/delete/all', 'RemovalOrderController@deleteAll');
            Route::get('/order/{removal_order}/cancel', 'RemovalOrderController@cancel');
            Route::get('/order/count/posts/address/{address}/lat/{lat}/lng/{lng}/office/{office}/agent/{agent}', 'RemovalOrderController@countPostsAtProperty');
            Route::get('/orders/same/property/{order}', 'RemovalOrderController@getOthersOrdersSameProperty');
            Route::get('/order/{removal_order}/mark-completed', 'RemovalOrderController@markCompleted');
            Route::get('/order/check/address/{address}/lat/{lat}/lng/{lng}/office/{office}/agent/{agent}/order/{orderId}', 'RemovalOrderController@checkOrderSameAddress');
        });
    });

    //Delivery orders
    Route::group(['middleware' => 'can:notInstaller, App\Models\User'], function () {
        Route::prefix('delivery')->group(function () {
            Route::get('/', 'DeliveryOrderController@index');
            Route::get('/orders/datatable', 'DeliveryOrderController@datatable');
            Route::get('/get/install-order/{order}', 'DeliveryOrderController@getOrderForDeliveryModal');
            Route::post('/get/zone', 'DeliveryOrderController@getDeliveryZone');
            Route::post('/store', 'DeliveryOrderController@store');
            Route::get('/get/order/{delivery_order}', 'DeliveryOrderController@getOrder');
            Route::get('/order/email/{delivery_order}', 'DeliveryOrderController@sendEmail');
            Route::post('/order/delete/all', 'DeliveryOrderController@deleteAll');
            Route::get('/order/{delivery_order}/cancel', 'DeliveryOrderController@cancel');
            Route::post('/add/panel', 'DeliveryOrderController@addNewSign');
            Route::get('/order/{delivery_order}/mark-completed', 'DeliveryOrderController@markCompleted');
        });
    });

    //payments
    Route::group(['middleware' => 'can:notInstaller, App\Models\User'], function () {
        Route::group(['as' => 'payments.'], function () {
            Route::get('/payment', "PaymentController@index");
            Route::post('/payment/pay', "PaymentController@pay")->name("pay");
            Route::get('payment/get-saved-cards/{user}', 'PaymentController@getSavedCards');
            Route::get('payment/get-agent-cards-visible-to-office/{agent}/{office}', 'PaymentController@getAgentCardsVisibleToOffice');
            Route::post('/payment/repair/pay', "PaymentController@payRepairOrder");
            Route::post('/payment/removal/pay', "PaymentController@payRemovalOrder");
            Route::post('/payment/delivery/pay', "PaymentController@payDeliveryOrder");
            Route::get('payment/get-office-cards-visible-to-agents/{office}/{officePayMethod}/{agentPayMethod}', 'PaymentController@getOfficeCardsVisibleToAgents');
        });
    });

    //====
    Route::get('/post/{post}/access/set/all/office/access/{access}', "PostController@toggleAcessAll");
    Route::get('/accessory/{accessory}/access/set/all/office/access/{access}', "AccessoryController@toggleAcessAll");

    Route::get('/get/agent/{agent}/panels', "AgentController@getAgentPanels");
    Route::get('/get/office/{office}/panels', "OfficeController@getOfficePanels");
    Route::get('/get/agent/{agent}/posts', 'AgentController@getAgentPosts');
    Route::get('/get/agent/{agent}/accessories', "AgentController@getAgentAcessories");
    Route::get('/get/office/{office}/posts', 'OfficeController@getOfficePosts');
    Route::get('/get/office/{office}/accessories', "OfficeController@getOfficeAccessories");

    //main group
    Route::group(['middleware' => 'can:Admin, App\Models\User'], function () {
        //users
        Route::resource('users', 'UserController');
        //related to users
        Route::resource('offices', 'OfficeController');
        Route::resource('agents', 'AgentController');
        //Office group
        Route::group(['as' => 'offices.'], function () {
            Route::get('/get/office/{office}', 'OfficeController@getOffice');
            Route::get('/get/office-cards/{office}', 'OfficeController@getOfficeCards');
            Route::post('/office-cards/remove/{payment_profile_id}/{office_id}', 'OfficeController@removeOfficeCard');
            Route::get('office/{office}/reset/password', 'OfficeController@resetPassword')->name('reset.password');
            //Route::post("/offices/delete/all", "OfficeController@destroyAll")->name('destroy.all');
            Route::post("/office/{office}/update/payment/method", "OfficeController@updatePaymentMethod")->name('update.payment.method');
            Route::post("/office/{office}/create/note", "OfficeController@storeNote")->name('note.store');
            Route::get("/office/{office}/agents", "OfficeController@agents")->name('get.agents');
            Route::get("/office/{office}/agents/json", "OfficeController@agentsJson")->name('get.agents.json');
            Route::get("/office/{office}/agents/order/by/name/json", "OfficeController@agentsJsonOrderByName")->name('get.agents.order.by.name.json');
            Route::post("/office/{office}/change/password", "OfficeController@changeOfficePassword");
        });

        //Communications
        Route::prefix('/communications')->group(function () {
            // Route::get('/', 'CommunicationsController@index')->name('communications.index');

            // Communications/notices
            Route::get('/notices', 'NoticeController@index')->name('communications.notices.index');
            Route::get('/notices/{id}', 'NoticeController@show')->name('communications.notices.show');
            Route::post('/notices', 'NoticeController@store')->name('communications.notices.store');
            Route::post('/notices/{id}', 'NoticeController@update')->name('communications.notices.update');
            Route::delete('/notices/{id}', 'NoticeController@destroy')->name('communications.notices.destroy');

            // Communications/emails
            Route::get('/emails', 'CommunicationsEmailController@index')->name('communications.emails.index');
            Route::post('/emails', 'CommunicationsEmailController@sendCommunicationsEmail')->name('communications.emails.sendCommunicationsEmail');

            // Communications/feedback
            Route::get('/feedback', 'FeedbackController@index');
            Route::post('/feedback/{id}', 'FeedbackController@update');
        });

        //Agent group
        Route::group(['as' => 'agents.'], function () {
            Route::get('agent/{agent}/reset/password', 'AgentController@resetPassword')->name('reset.password');
            Route::get('/get/agent/{agent}', 'AgentController@getAgent');
            Route::get('/get/agent-cards/{agent}', 'AgentController@getAgentCards');
            Route::post('/agent-cards/remove/{payment_profile_id}/{agent_id}', 'AgentController@removeAgentCard');
            //Route::post("/agents/delete/all", "AgentController@destroyAll")->name('destroy.all');
            Route::post("/agent/{agent}/update/payment/method", "AgentController@updatePaymentMethod")->name('update.payment.method');
            Route::post("/agent/{agent}/create/note", "AgentController@storeNote")->name('note.store');
            Route::post("/set/new/office/{office}/for/agent/{agent}", "AgentController@newOffice")->name('update.office');
            Route::get("/update/post/{post}/agent/{agent}/{column}/{value}", "AgentController@updatePostAgentColumn")->name('agent.update.column');
            Route::get("/update/post/agent/{post}/{agent}/{access}/{price}/{locked}", "AgentController@toggleLocked")->name('toggle.locked');
            Route::get("/update/posts/agent/{post}/{agent}/{access}/{price?}", "AgentController@toggleAccess")->name('toggle.access');
            Route::post("/agent/{agent}/change/password", "AgentController@changeAgentPassword");
        });

        //Installer Admin
        Route::post("/installer/store", "UserController@storeInstaller");
        Route::post("/installer/update/{user}", "UserController@updateInstaller");
        Route::get('/install/get/{user}', "UserController@getInstaller");
        Route::get('/installer/{user}/reset/password', "UserController@resetInstallerPassword");

        //inventories
        Route::resource('inventories', 'InventoryController');
        //related to inventories
        Route::resource('posts', 'PostController');
        Route::resource('panels', 'PanelController');
        Route::resource('panelSettings', 'PanelSettingController');
        Route::resource('accessories', 'AccessoryController');
        //Post group
        Route::group(['as' => 'posts.'], function () {
            Route::get('/get/post/{post}', 'PostController@getPost');
            //Route::post("/posts/delete/all", "PostController@destroyAll")->name('destroy.all');

            Route::get("/update/posts/{post}/{column}/{value}", "PostController@updateColumn")->name('update.column');

            Route::get("/update/post/{post}/office/{office}/{column}/{value}", "PostController@updatePostOfficeColumn")->name('post.office.update.column');
            Route::get("/update/post/office/{post}/{office}/{access}/{price}/{locked}", "PostController@toggleLocked")->name('toggle.locked');
            Route::get("/update/posts/office/{post}/{office}/{access}/{price?}", "PostController@toggleAccess")->name('toggle.access');
        });

        // Panels group
        Route::group(['as' => 'panels.'], function () {
            Route::get('/get/panel/{panel}', 'PanelController@getPanel');
            //Route::post("/panels/delete/all", "PanelController@destroyAll")->name('destroy.all');
            Route::get("/panel/{panel}/agents", "PanelController@agents")->name('panel.agents');
            Route::get("/panel/{panel}/all/agents", "PanelController@allAgents")->name('panel.agents');
        });

        // Accessories group
        Route::group(['as' => 'accessories.'], function () {
            Route::get('/get/accessory/{accessory}', 'AccessoryController@getAccessory');
            //Route::post("/accessories/delete/all", "AccessoryController@destroyAll")->name('destroy.all');
            Route::get("/update/accessory/{accessory}/{column}/{value}", "AccessoryController@updateColumn")->name('update.column');
            /////////////
            Route::get("/update/accessory/{accessory}/office/{office}/{column}/{value}", "AccessoryController@updateAccessoryOfficeColumn")->name('offices.update.column');
            Route::get("/update/accessory/office/{accessory}/{office}/{access}/{price}/{locked}", "AccessoryController@toggleLocked")->name('offices.toggle.locked');
            Route::get("/update/accessories/office/{accessory}/{office}/{access}/{price?}", "AccessoryController@toggleAccess")->name('offices.toggle.access');
            ////////////
            Route::get("/update/accessory/{accessory}/agent/{agent}/{column}/{value}", "AccessoryController@updateAccessoryAgentColumn")->name('agents.update.column');
            Route::get("/update/accessory/agent/{accessory}/{agent}/{access}/{price}/{locked}", "AccessoryController@AgenttoggleLocked")->name('agents.toggle.locked');
            Route::get("/update/accessories/agent/{accessory}/{agent}/{access}/{price?}", "AccessoryController@AgenttoggleAccess")->name('agents.toggle.access');
            Route::post('/store/douctment/accessory/{accessory}', "AccessoryController@storeDocuments");
        });
        //services
        Route::resource('services', 'ServiceController');
        Route::get("/update/column/service/settings/{column}/{value}", "ServiceSettingController@updateColumn")->name('serive.settings.update');
        Route::post('/post/global/settings', "ServiceSettingController@updatePostSettings");

        //zones
        Route::resource('zones', "ZoneController");
        Route::group(['as' => 'zones.'], function () {
            Route::post('/update/zone/{zone}', 'ZoneController@update');
            Route::post('/update/settings/zone/', 'ZoneController@updateSettings')->name('update.settings');
            Route::post('/update/zone/fee/{zone}', 'ZoneController@updateZoneFee')->name('update.zone.fee');
            Route::get("/delete/zone/{zone}", "ZoneController@delete")->name("delete");
        });

        //datatable private group
        Route::group(['as' => 'datatable.', 'prefix' => '/datatable'], function () {
            Route::get('/offices', 'OfficeController@datatable')->name('offices');
            Route::get('/post/offices', 'OfficeController@post_offices')->name('post.offices');
            Route::get('/agents', 'AgentController@datatable')->name('agents');
            Route::get('/post/agents', 'AgentController@post_agents')->name('agents.offices');
            Route::get('/posts', 'PostController@datatable')->name('posts');
            Route::get('/panels', 'PanelController@datatable')->name('panels');
            Route::get('/accessories', "AccessoryController@datatable")->name('accessories');
            Route::get('/accessory/offices', 'OfficeController@accessory_offices')->name('accessories.offices');
            Route::get('/accessory/agents', 'OfficeController@accessory_agents')->name('accessories.agents');
            Route::get('/orders', "OrderController@datatable")->name("orders");
            Route::get('/orders/status', "OrderController@datatableOrderStatusActive");
            Route::get('/order/status/history', "OrderController@datatableOrderStatusHistory");
            Route::get('/installers', 'UserController@datatableInstallers');
            Route::get('/unpaid/invoices', 'AccountingUnpaidInvoicesController@datatable');
            Route::get('/create/invoices', 'AccountingCreateInvoicesController@datatable');
            Route::get('/payments', 'AccountingPaymentsController@datatable');
            Route::get('/installer-points', 'AccountingInstallerPointsController@datatable');
            Route::get('/installer-payments', 'AccountingInstallerPointsController@paymentsDatatable');
        });

        //Accounting
        Route::group(['middleware' => 'can:notInstaller, App\Models\User'], function () {
            Route::prefix('accounting')->group(function () {
                Route::post('/analytics', 'AccountingAnalyticsController@index');
                Route::get('/unpaid/invoice/{id}/email', 'AccountingUnpaidInvoicesController@sendEmail');
                Route::get('/unpaid/invoice/{id}/email/history', 'AccountingUnpaidInvoicesController@emailHistory');
                Route::get('/create/invoices', 'AccountingCreateInvoicesController@index');
                Route::post('/create/invoices', 'AccountingCreateInvoicesController@store');
                Route::get('/settings', 'AccountingSettingsController@index');
                Route::get('/payments/reverse/balance/{id}', 'AccountingPaymentsController@reverseBalancePayment');
                Route::get('/payments/reverse/card/{id}', 'AccountingPaymentsController@reverseCardPayment');
                Route::get('/payments/reverse/check/{id}', 'AccountingPaymentsController@reverseCheckPayment');
                Route::post('/unpaid/invoices/adjustments', 'AccountingUnpaidInvoicesController@invoiceAdjustments');
                Route::get('/unpaid/invoice/remove/agent/{agent_id}/{invoice_id}', 'AccountingUnpaidInvoicesController@removeAgentFromInvoice');
                Route::get('/transaction/summary/{limit?}', 'AccountingTransactionSummaryController@index');
                Route::get('/installer-points', 'AccountingInstallerPointsController@index');
                Route::post('/installer-points/edit/{orderType}/{orderId}', 'AccountingInstallerPointsController@editPoints');
                Route::post('/installer-payments/create', 'AccountingInstallerPointsController@createPayment');
                Route::get('/installer-payments/{id}', 'AccountingInstallerPointsController@showPayment');
                Route::post('/installer-payments/edit/{id}', 'AccountingInstallerPointsController@editPayment');
                Route::post('/installer-payments/cancel/{id}', 'AccountingInstallerPointsController@cancelPayment');
                Route::post('/payments/reverse/card/partial/{id}', 'AccountingPaymentsController@reverseCardPaymentPartial');
            });

            Route::get('/admin/installer/order/details/{order_id}/{order_type}/{installerId}', 'OrderController@adminInstallerOrderDetails');
            Route::post('/admin/installer/complete/install/order', 'OrderController@adminInstallOrderComplete');
        });

    });

    Route::post('/installer/complete/install/order', 'OrderController@installOrderComplete');
    Route::post('/installer/complete/repair/order', 'OrderController@repairOrderComplete');
    Route::post('/installer/complete/removal/order', 'OrderController@removalOrderComplete');
    Route::post('/installer/complete/delivery/order', 'OrderController@deliveryOrderComplete');

    //holidays
    Route::group(['as' => 'holidays.'], function () {
        Route::get('/get/holidays', 'HolidayController@getAll');
        Route::post('/set/holidays', 'HolidayController@store');
    });

    Route::get('/get/zones/orderby/{column}/{type}', 'ZoneController@getZonesOrderBy');
    Route::get('/get/zone/settings', 'ZoneController@getSettings');

    Route::get('/datatable/office/orders/status', 'OfficeController@datatableRecentOrders');
    Route::get('/datatable/office/orders/status/active', 'OfficeController@datatableOrderStatusActive');
    Route::get('/datatable/office/orders/status/history', 'OfficeController@datatableOrderStatusHistory');
    Route::get('/datatable/office/orders/repair', 'OfficeController@datatableRepairOrders');
    Route::get('/datatable/office/orders/removal', 'OfficeController@datatableRemovalOrders');
    Route::get('/datatable/office/orders/delivery', 'OfficeController@datatableDeliveryOrders');
    Route::get('/datatable/office/accounting/unpaid/invoices', 'OfficeController@datatableUnpaidInvoices');
    Route::get('/datatable/office/accounting/payments', 'OfficeController@datatablePayments');

    Route::group(['middleware' => 'can:Office, App\Models\User'], function () {
        Route::get('/office-users', 'UserController@officeAgents')->name('office.users.index');
        Route::get('/datatable/office-agents', 'OfficeController@officeAgentsDatatable');
        Route::post('/office-agents/create', 'OfficeController@createOfficeAgent')->name('office.agents.store');
        Route::post('/office-agents/{agent}/update/payment/method', 'OfficeController@updateOfficeAgentPaymentMethod')->name('office.agents.update.payment.method');
        Route::get('/office-agents/get/agent/{agent}', 'OfficeController@getOfficeAgent');
        Route::patch('/office-agents/{agent}', 'OfficeController@updateOfficeAgent');
        Route::post('/office-agents/remove/{agent}', 'OfficeController@removeOfficeAgent');
    });

    Route::group(["middleware" => "can:OfficeOrAgent, App\Models\User"], function () {
        Route::get('/office/count-orders', 'OfficeController@countOrdersByDate');
        Route::get('/agent/count-orders', 'AgentController@countOrdersByDate');

        //Office inventories
        Route::get('/office/inventories', "OfficeController@inventories")->name('office.inventories.index');
        Route::get('/datatable/office/panels', "OfficeController@officePanelsDatatable");
        Route::get("/office/panel/{panel}/agents", "PanelController@agents")->name('office.panel.agents');

        //Agent inventories
        Route::get('/agent/inventories', "AgentController@inventories")->name('agent.inventories.index');
        Route::get('/datatable/agent/panels', "AgentController@agentPanelsDatatable");
        Route::get("/agent/panel/{panel}/agents", "PanelController@agents")->name('agent.panel.agents');
        Route::get("/agent/panel/{panel}/office", "PanelController@office")->name('agent.panel.office');
    });

    Route::get('/datatable/agent/orders/status', 'AgentController@datatableRecentOrders');
    Route::get('/datatable/agent/orders/repair', 'AgentController@datatableRepairOrders');
    Route::get('/datatable/agent/orders/removal', 'AgentController@datatableRemovalOrders');
    Route::get('/datatable/agent/orders/delivery', 'AgentController@datatableDeliveryOrders');
    Route::get('/datatable/agent/orders/status/active', 'AgentController@datatableOrderStatusActive');
    Route::get('/datatable/agent/orders/status/history', 'AgentController@datatableOrderStatusHistory');
    Route::get('/datatable/agent/accounting/unpaid/invoices', 'AgentController@datatableUnpaidInvoices');
    Route::get('/datatable/agent/accounting/payments', 'AgentController@datatablePayments');

    Route::post('/acknowledge/notice/{notice}', 'NoticeController@acknowledgeNotice');

    Route::get('/get/accessory/{accessory}/json', 'AccessoryController@getAccessoryJson');

    // Contact us
    Route::get('/contact-us', 'ContactController@index');

    Route::group(["middleware" => "can:notInstaller, App\Models\User"], function () {
        Route::get('/datatable/office/email-settings/{office}', 'OfficeController@emailSettingsDatatable');
        Route::post('/office/email-settings/add', 'OfficeController@addNewEmail');
        Route::post('/office/email-settings/update', 'OfficeController@updateNotification');
        Route::post('/office/email-settings/remove', 'OfficeController@removeEmail');

        Route::get('/datatable/agent/email-settings/{agent}', 'AgentController@emailSettingsDatatable');
        Route::post('/agent/email-settings/add', 'AgentController@addNewEmail');
        Route::post('/agent/email-settings/update', 'AgentController@updateNotification');
        Route::post('/agent/email-settings/remove', 'AgentController@removeEmail');
    });

    // Settings
    Route::get('/settings', 'SettingController@index')->middleware(['can:notInstaller, App\Models\User']);
    Route::put('/settings/{user}/update', 'SettingController@update')->middleware(['can:notInstaller, App\Models\User']);
    Route::group(["middleware" => "can:Agent, App\Models\User"], function () {
        Route::get('settings/agent/offices', 'OfficeController@datatable')->name('agent.change.offices');
        Route::post("settings/set/new/office/{office}/for/agent/{agent}", "AgentController@newOffice")->name('settings.update.office');
        Route::get("settings/agent", 'UserController@getAgent');
    });
    Route::group(["middleware" => "can:Office, App\Models\User"], function () {
        Route::get("settings/office", 'UserController@getOffice');
    });

    //Accounting
    Route::group(['middleware' => 'can:notInstaller, App\Models\User'], function () {
        Route::prefix('accounting')->group(function () {
            Route::get('/', 'AccountingController@index');
            Route::get('/unpaid/invoices', 'AccountingUnpaidInvoicesController@index');
            Route::get('/unpaid/invoice/{id}', 'AccountingUnpaidInvoicesController@show');
            Route::get('/payments', 'AccountingPaymentsController@index');
            Route::get('/invoice-view/{id}', 'InvoiceViewController@generatePDF');
            Route::get('/manage-cards', 'PaymentController@manageCards');
            Route::get('/manage-cards/remove/{payment_profile_id}', 'PaymentController@removeCard');
            Route::post('/manage-cards/add-card', 'PaymentController@addCard');
            Route::post('/manage-cards/toggle-visibility', 'PaymentController@officeToggleCardVisibility');
            Route::get('/unpaid/invoice/payer/{id}', 'AccountingUnpaidInvoicesController@getInvoicePayer');
            Route::post('/unpaid/invoices/payment', 'AccountingUnpaidInvoicesController@processPayment');
        });
    });

    //exports routes
    Route::group(['prefix' => '/export', 'as' => "export."], function () {
        Route::get('/agents', 'ExportImportController@agents')->name('agents');
        Route::get('/payments-csv', 'ExportImportController@paymentsToCsv')->name('payments.csv');
        Route::get('/payments-excel', 'ExportImportController@paymentsToExcel')->name('payments.excel');
        Route::get('/office/payments-csv', 'ExportImportController@paymentsToCsvOffice')->name('payments.csv.office');
        Route::get('/office/payments-excel', 'ExportImportController@paymentsToExcelOffice')->name('payments.excel.office');
        Route::get('/agent/payments-csv', 'ExportImportController@paymentsToCsvAgent')->name('payments.csv.agent');
        Route::get('/agent/payments-excel', 'ExportImportController@paymentsToExcelAgent')->name('payments.excel.agent');
    });
    //imports routes
    Route::group(['prefix' => '/import', 'as' => "import."], function () {
        Route::post('/agents', 'ExportImportController@importAgents')->name('agents');
    });

    //files routes
    Route::get('/private/image/{name}', 'PrivateFileController@getImage');
    Route::get('/private/image/office/{name}', 'PrivateFileController@getOfficeFile');
    Route::get('/private/image/post/{name}', 'PrivateFileController@getPostFile');
    Route::get('/private/image/panel/{name}', 'PrivateFileController@getPanelFile');
    Route::get('/private/image/accessory/{name}', 'PrivateFileController@getAccessoryFile');
    Route::get('/private/document/file/{name}', 'PrivateFileController@documentFile');
});

//datatable group
Route::group(['as' => 'datatable.public.', 'prefix' => '/datatable/public'], function () {
    Route::get('/offices', 'OfficeController@datatable_public')->name('offices');
});

// Route::view('/view/order','mail.order.created',['order'=> Order::get()->first()]);
