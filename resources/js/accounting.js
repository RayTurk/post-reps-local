import helper from './helper'

const accounting = {

    init() {

        if (helper.urlContains('/accounting')) {
            if (helper.urlContains('analytics')) {
                $('[id=accountingAnalytics]').addClass('order-tab-active');
                $('[id=accountingUnpaidInvoices]').removeClass('order-tab-active');
                $('[id=accountingPayments]').removeClass('order-tab-active');
                $('[id=accountingCreateInvoices]').removeClass('order-tab-active');
                $('[id=accountingTransactionSummary]').removeClass('order-tab-active');
            }
            if (helper.urlContains('unpaid/invoices')) {
                $('[id=accountingAnalytics]').removeClass('order-tab-active');
                $('[id=accountingUnpaidInvoices]').addClass('order-tab-active');
                $('[id=accountingPayments]').removeClass('order-tab-active');
                $('[id=accountingCreateInvoices]').removeClass('order-tab-active');
                $('[id=accountingTransactionSummary]').removeClass('order-tab-active');
            }
            if (helper.urlContains('payments')) {
                $('[id=accountingAnalytics]').removeClass('order-tab-active');
                $('[id=accountingUnpaidInvoices]').removeClass('order-tab-active');
                $('[id=accountingPayments]').addClass('order-tab-active');
                $('[id=accountingCreateInvoices]').removeClass('order-tab-active');
                $('[id=accountingTransactionSummary]').removeClass('order-tab-active');
            }
            if (helper.urlContains('create/invoices')) {
                $('[id=accountingAnalytics]').removeClass('order-tab-active');
                $('[id=accountingUnpaidInvoices]').removeClass('order-tab-active');
                $('[id=accountingPayments]').removeClass('order-tab-active');
                $('[id=accountingCreateInvoices]').addClass('order-tab-active');
                $('[id=accountingTransactionSummary]').removeClass('order-tab-active');
            }
            if (helper.urlContains('transaction/summary')) {
                $('[id=accountingAnalytics]').removeClass('order-tab-active');
                $('[id=accountingUnpaidInvoices]').removeClass('order-tab-active');
                $('[id=accountingPayments]').removeClass('order-tab-active');
                $('[id=accountingCreateInvoices]').removeClass('order-tab-active');
                $('[id=accountingTransactionSummary]').addClass('order-tab-active');
            }
            if (helper.urlContains('/settings')) {
                $('[id=accountingAnalytics]').removeClass('order-tab-active');
                $('[id=accountingUnpaidInvoices]').removeClass('order-tab-active');
                $('[id=accountingPayments]').removeClass('order-tab-active');
                $('[id=accountingCreateInvoices]').removeClass('order-tab-active');
                $('[id=accountingTransactionSummary]').removeClass('order-tab-active');
                $('[id=accountingSettings]').addClass('order-tab-active');
            }
            if (helper.urlContains('/manage-cards')) {
                $('[id=accountingAnalytics]').removeClass('order-tab-active');
                $('[id=accountingUnpaidInvoices]').removeClass('order-tab-active');
                $('[id=accountingPayments]').removeClass('order-tab-active');
                $('[id=accountingCreateInvoices]').removeClass('order-tab-active');
                $('[id=accountingTransactionSummary]').removeClass('order-tab-active');
                $('[id=accountingSettings]').removeClass('order-tab-active');
                $('[id=accountingManageCards]').addClass('order-tab-active');
            }
            if (helper.urlContains('/installer-points')) {
                $('[id=accountingAnalytics]').removeClass('order-tab-active');
                $('[id=accountingUnpaidInvoices]').removeClass('order-tab-active');
                $('[id=accountingPayments]').removeClass('order-tab-active');
                $('[id=accountingCreateInvoices]').removeClass('order-tab-active');
                $('[id=accountingTransactionSummary]').removeClass('order-tab-active');
                $('[id=accountingSettings]').removeClass('order-tab-active');
                $('[id=accountingManageCards]').removeClass('order-tab-active');
                $('[id=accountingInstallerPoints]').addClass('order-tab-active');
            }
        }

    }

};

$(() => {
    accounting.init();
});

export default accounting;
