<template>
    <div class="--content-wrapper">
        <div class="card card-with-shadow border-0 h-100">
            <div class="card-header bg-transparent p-primary">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <div class="row">
                            <div class="col-md-6 mb-2 mb-md-0">
                                <label class="d-block mb-1">{{ $t('start_date') }}</label>
                                <app-input 
                                    type="date" 
                                    v-model="filters.startDate"
                                    @input="loadReportData"
                                />
                            </div>
                            <div class="col-md-6 mb-2 mb-md-0">
                                <label class="d-block mb-1">{{ $t('end_date') }}</label>
                                <app-input 
                                    type="date" 
                                    v-model="filters.endDate"
                                    @input="loadReportData"
                                />
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 d-flex justify-content-end align-items-center">
                        <p class="my-0 mr-2 p-0">{{ $t('order_report_by') }}</p>
                        <app-input type="radio-buttons" v-model="reportUnit" :list="unitList"/>
                    </div>
                </div>
            </div>
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
    data() {
        return {
            // Date filters
            filters: {
                startDate: '',
                endDate: ''
            },
            // Order report by unit
            preloader: false,
            reportUnit: 'count',
            unitList: [
                {
                    id: 'count',
                    value: this.$t('count')
                },
                {
                    id: 'value',
                    value: this.$t('value')
                }
            ],
            // Chart Static Value
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
                        title: this.$t('name'),
                        type: 'text',
                        key: 'name',
                    },
                    {
                        title: this.$t('count'),
                        type: 'text',
                        key: 'count'
                    },
                    {
                        title: this.$t('value'),
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
        reportUnit: {
            handler: 'reportByOrder'
        }
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
                this.reportChart.dataSets[0].data.push(item[this.reportUnit]);
            });
            setTimeout(() => {
                this.preloader = false
            });
        },
        getAverageValue() {
            if (!this.reportChartData || this.reportChartData.length === 0) {
                return 0;
            }
            let list = _.map(this.reportChartData.slice(0, 10), this.reportUnit),
                total = list.reduce((result, item) => Number(result) + Number(item), 0);
            return total/Math.min(this.reportChartData.length, 10);
        },
        loadReportData() {
            // Update query params for the table
            this.options.queryParams.start_date = this.filters.startDate;
            this.options.queryParams.end_date = this.filters.endDate;
            
            // Reload the table data
            this.$hub.$emit(`reload-${this.reportTableId}`);
        },
        formatCurrency(value) {
            if (!value && value !== 0) return '-';
            return new Intl.NumberFormat('es-ES', {
                style: 'currency',
                currency: 'USD',
                minimumFractionDigits: 2
            }).format(value);
        }
    }
}
</script>
