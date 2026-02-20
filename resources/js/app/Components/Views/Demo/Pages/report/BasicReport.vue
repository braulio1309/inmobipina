<template>
    <div class="--content-wrapper">
        <div class="card card-with-shadow border-0 h-100">
            <div class="card-body pt-primary">
                <div class="chart-container position-relative min-height-380"
                     :class="{'loading-opacity':preloader}">
                    <app-overlay-loader v-if="preloader"/>
                    <app-chart v-else
                               type="horizontal-line-chart"
                               :height="380"
                               :labels="reportChart.labels"
                               :data-sets="reportChart.dataSets"/>
                </div>

                <hr class="mx-minus-primary my-primary">
                <app-table
                    class="remove-datatable-x-padding"
                    :id="reportTableId"
                    :options="options"
                />
            </div>
        </div>
    </div>
</template>

<script>
import {FormMixin} from "../../../../../../core/mixins/form/FormMixin";
import {TOP_SELLERS_REPORT} from "../../../../../Config/ApiUrl";


export default {
    name: "Report",
    mixins: [FormMixin],

    inject: {
        reportFilters: {
            default: () => ({ startDate: '', endDate: '', reportUnit: 'count' }),
        },
    },

    data() {
        return {
            preloader: false,
            // Chart and table configuration
            reportChart: {
                labels: [],
                dataSets: [
                    {
                        barPercentage: 0.5,
                        barThickness: 30,
                        backgroundColor: [],
                        data: []
                    }
                ]
            },

            // Report Table
            reportTableId: 'report-table-demo',
            options: {
                url: TOP_SELLERS_REPORT,
                tableShadow: false,
                datatableWrapper: false,
                showFilter: false,
                showSearch: false,
                managePagination: false,
                queryParams: {
                    start_date: '',
                    end_date: ''
                },
                afterRequestSuccess: ({data}) => {
                    this.setupChartForTable(data);
                },
                columns: [
                    {
                        title: this.$t('reports.name'),
                        type: 'text',
                        key: 'name',
                    },
                    {
                        title: this.$t('reports.count'),
                        type: 'text',
                        key: 'count'
                    },
                    {
                        title: this.$t('reports.value'),
                        type: 'custom-html',
                        key: 'value',
                        modifier: (value) => {
                            return this.formatCurrency(value);
                        }
                    }
                ],
            }
        }
    },
    watch: {
        'reportFilters.reportUnit': {
            handler: 'reportByOrder'
        },
        'reportFilters.startDate': {
            handler: 'loadReportData'
        },
        'reportFilters.endDate': {
            handler: 'loadReportData'
        },
    },
    created() {
        this.preloader = true;
    },
    methods: {
        setupChartForTable({data}){
            this.reportChartData = data;
            this.getChartData();
        },
        reportByOrder() {
            this.getChartData()
        },
        getChartData() {
            this.preloader = true;
            this.reportChart.labels = [];
            this.reportChart.dataSets[0].backgroundColor = [];
            this.reportChart.dataSets[0].data = [];
            
            // Take only top 10 for chart
            const top10Data = this.reportChartData.slice(0, 10);
            
            let midIndex = Math.ceil(top10Data.length / 2);
            top10Data.forEach((item, index) => {
                if (index === midIndex) {
                    this.reportChart.labels.push(this.$t('average'));
                    this.reportChart.dataSets[0].backgroundColor.push('#4FE892');
                    this.reportChart.dataSets[0].data.push(this.getAverageValue());
                }
                this.reportChart.labels.push(item.name);
                this.reportChart.dataSets[0].backgroundColor.push('#2E69FF');
                this.reportChart.dataSets[0].data.push(item[this.reportFilters.reportUnit]);
            });
            setTimeout(() => {
                this.preloader = false
            });
        },
        getAverageValue() {
            if (!this.reportChartData || this.reportChartData.length === 0) {
                return 0;
            }
            const top10Data = this.reportChartData.slice(0, 10);
            let list = _.map(top10Data, this.reportFilters.reportUnit),
                total = list.reduce((result, item) => Number(result) + Number(item), 0);
            return total/list.length;
        },
        loadReportData() {
            // Update query params for the table using global filters
            this.options.queryParams.start_date = this.reportFilters.startDate;
            this.options.queryParams.end_date = this.reportFilters.endDate;
            
            // Reload the table data
            this.$hub.$emit(`reload-${this.reportTableId}`);
        },
        formatCurrency(value) {
            if (!value && value !== 0) return '-';
            const locale = this.$i18n && this.$i18n.locale ? 
                (this.$i18n.locale === 'es' ? 'es-ES' : 'en-US') : 
                'en-US';
            return new Intl.NumberFormat(locale, {
                style: 'currency',
                currency: 'USD',
                minimumFractionDigits: 2
            }).format(value);
        }
    }
}
</script>
