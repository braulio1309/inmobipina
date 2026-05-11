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
                <div class="d-flex justify-content-end mb-primary">
                    <button class="btn btn-primary" @click="exportReport">
                        Exportar lista
                    </button>
                </div>
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
import {ADVISOR_COMMISSIONS_REPORT, ADVISOR_COMMISSIONS_REPORT_EXPORT} from "../../../../../Config/ApiUrl";


export default {
    name: "Report",
    mixins: [FormMixin],

    inject: {
        reportFilters: {
            default: () => ({ startDate: '', endDate: '' }),
        },
    },

    data() {
        return {
            preloader: false,
            currentFilters: {
                startDate: '',
                endDate: '',
            },
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

            reportTableId: 'report-table-demo',
            options: {
                url: ADVISOR_COMMISSIONS_REPORT,
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
                        title: this.$t('reports.sales_count'),
                        type: 'text',
                        key: 'count'
                    },
                    {
                        title: this.$t('reports.amount_usd'),
                        type: 'custom-html',
                        key: 'value',
                        modifier: (value) => {
                            return this.formatCurrency(value);
                        }
                    },
                    {
                        title: 'Clientes asignados',
                        type: 'text',
                        key: 'assigned_clients_count'
                    }
                ],
            }
        }
    },
    watch: {
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
        syncFilters(filters = {}) {
            this.currentFilters.startDate = filters.startDate || '';
            this.currentFilters.endDate = filters.endDate || '';
        },
        setupChartForTable({data}){
            this.reportChartData = data;
            this.getChartData();
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
                    this.reportChart.labels.push(this.$t('reports.average'));
                    this.reportChart.dataSets[0].backgroundColor.push('#4FE892');
                    this.reportChart.dataSets[0].data.push(this.getAverageValue());
                }
                this.reportChart.labels.push(item.name);
                this.reportChart.dataSets[0].backgroundColor.push('#2E69FF');
                // Always display commission value in chart regardless of reportUnit
                this.reportChart.dataSets[0].data.push(item['value']);
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
            let list = _.map(top10Data, 'value'),
                total = list.reduce((result, item) => Number(result) + Number(item), 0);
            return total / list.length;
        },
        loadReportData() {
            this.options.queryParams.start_date = this.currentFilters.startDate;
            this.options.queryParams.end_date = this.currentFilters.endDate;
            this.$hub.$emit(`reload-${this.reportTableId}`);
        },
        exportReport() {
            const params = new URLSearchParams();

            if (this.currentFilters.startDate) {
                params.set('start_date', this.currentFilters.startDate);
            }

            if (this.currentFilters.endDate) {
                params.set('end_date', this.currentFilters.endDate);
            }

            const queryString = params.toString();
            const exportUrl = queryString
                ? `/${ADVISOR_COMMISSIONS_REPORT_EXPORT}?${queryString}`
                : `/${ADVISOR_COMMISSIONS_REPORT_EXPORT}`;

            window.open(exportUrl, '_blank');
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
    ,
    mounted() {
        this.syncFilters(this.reportFilters);
        this.$hub.$on('report-filters-changed', this.syncFilters);
        this.$hub.$on('report-filters-changed', this.loadReportData);
        this.loadReportData();
    },
    beforeDestroy() {
        this.$hub.$off('report-filters-changed', this.syncFilters);
        this.$hub.$off('report-filters-changed', this.loadReportData);
    }
}
</script>
