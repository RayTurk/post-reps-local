import helper from './helper'

const accountingAnalytics = {

    init() {
        this.buildChart();
        this.onYearChange();
    },

    onYearChange() {
        $('[id="analytics_year"').on('change', (e) => {
            const self = $(e.target);

            $('#analyticsYearForm').find('#yearInput').val(self.val());
            $('#analyticsYearForm').trigger('submit')
        });
    },

    buildChart() {
        let analyticsCanvas = document.getElementById("analyticsChart");

        Chart.defaults.font.family = "Teko";
        Chart.defaults.font.size = 18;
        Chart.defaults.color = "black";

        if (helper.isMobilePhone()) {
            analyticsCanvas = document.getElementById("analyticsChartMobile");
            Chart.defaults.font.size = 12;
        }

        const months = ["JAN", "FEB", "MAR", "APR", "MAY", "JUN", "JUL", "AUG", "SEP", "OCT", "NOV", "DEC"];
        const payments = JSON.parse(analyticsCanvas.dataset.payments);
        let data = [];
        $.each(months, (i, val) => {
            if (val in payments) {
                data[i] = payments[val];
            } else {
                data[i] = 0;
            }
        });

        const chartData = {
            labels: months,
            datasets: [{
                label: "Payments Received",
                data: data,
                borderColor: 'green',
                backgroundColor: 'green',
                pointStyle: 'rect',
                pointRadius: 10,
                pointHoverRadius: 15
            }]
        };

        const lineChart = new Chart(analyticsCanvas, {
            type: 'line',
            data: chartData,
            /*options: {
                plugins:{
                    legend: {
                        display: false
                    }
                }
            }*/
        });
    }
};

$(() => {
    accountingAnalytics.init();
});

export default accountingAnalytics;
