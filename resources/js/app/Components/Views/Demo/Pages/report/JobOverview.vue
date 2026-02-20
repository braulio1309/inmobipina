<template>
    <div>
        <app-breadcrumb 
            :page-title="$t('reports.advisor_reports')" 
            :directory="$t('reports.title')" 
            :icon="'chart-line'"
        />

        <div class="container-fluid p-0 mt-4">
            
            <div v-if="isAdmin" class="row mb-4">
                <div class="col-12">
                    <div class="card card-with-shadow border-0">
                        <div class="card-body py-3 d-flex align-items-center justify-content-between">
                            <label class="mb-0 fw-bold me-3">{{ $t('reports.select_advisor') }}:</label>
                            <app-input class="col-sm-8"
                               :placeholder="$t('search_and_select')"
                               type="search-select"
                               :list="advisors"
                               @change="loadReports"
                               v-model="selectedAdvisorId"/>
                        </div>
                    </div>
                </div>
            </div>

            <!--<app-overlay-loader v-if="preloader"  />-->
            
            <div >
                
                <div class="row mb-3">
                    <div class="col-12">
                        <h5 class="text-primary">
                            {{ $t('reports.reports_for') }}: <strong>{{ reports.advisor.name }}</strong>
                        </h5>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12 col-md-4">
                        <div class="card card-with-shadow border-0 mb-primary">
                            <div class="card-body d-flex justify-content-center align-items-center">
                                <div class="text-center w-100">
                                    <div class="text-muted mb-2 text-uppercase small">{{ $t('reports.sales') }}</div>
                                    <div class="h1 mb-0 text-success">{{ reports.metrics.sales_count }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-12 col-md-4">
                        <div class="card card-with-shadow border-0 mb-primary">
                            <div class="card-body d-flex justify-content-center align-items-center">
                                <div class="text-center w-100">
                                    <div class="text-muted mb-2 text-uppercase small">{{ $t('reports.reservations') }}</div>
                                    <div class="h1 mb-0 text-info">{{ reports.metrics.reservations_count }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-12 col-md-4">
                        <div class="card card-with-shadow border-0 mb-primary">
                            <div class="card-body d-flex justify-content-center align-items-center">
                                <div class="text-center w-100">
                                    <div class="text-muted mb-2 text-uppercase small">{{ $t('reports.total_activities') }}</div>
                                    <div class="h1 mb-0 text-primary">{{ reports.metrics.total_activities }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mb-primary">
                    <div class="col-12 col-md-6">
                        <div class="card card-with-shadow border-0">
                            <div class="card-body d-flex justify-content-center align-items-center">
                                <div class="text-center w-100">
                                    <div class="text-muted mb-2 text-uppercase small">{{ $t('reports.my_commission') }}</div>
                                    <div class="h1 mb-0 text-warning">${{ formatCurrency(reports.metrics.total_advisor_commission) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-primary">
                    
                    <div class="col-12 col-lg-6">
                        <div class="card card-with-shadow border-0 h-100">
                            <div class="card-body">
                                <h4 class="card-title mb-4">{{ $t('reports.activities_by_type') }}</h4>
                                <app-chart 
                                    v-if="activitiesChart.labels.length"
                                    class="mb-primary" 
                                    type="horizontal-line-chart" 
                                    :height="280"
                                    :labels="activitiesChart.labels" 
                                    :data-sets="activitiesChart.dataSet" 
                                />
                                <div v-else class="text-center text-muted py-5">
                                    {{ $t('reports.no_activities') }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-lg-6">
                        <div class="card card-with-shadow border-0 h-100">
                            <div class="card-body">
                                <h4 class="card-title mb-4">{{ $t('reports.performance_summary') }}</h4>
                                <app-chart 
                                    class="mb-primary" 
                                    type="bar-chart" 
                                    :height="280" 
                                    :labels="metricsSummaryChart.labels"
                                    :data-sets="metricsSummaryChart.dataSet" 
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { colorArray } from '../../../../../../app/Helpers/ColorHelper';
import { FormMixin } from '../../../../../../core/mixins/form/FormMixin.js';

export default {
    name: 'JobOverview',
    mixins: [FormMixin],

    inject: {
        reportFilters: {
            default: () => ({ startDate: '', endDate: '', reportUnit: 'count' }),
        },
    },

    data() {
        return {
            reports: {
                advisor: { name: '' },
                metrics: {
                    sales_count: 0,
                    reservations_count: 0,
                    total_activities: 0,
                    total_advisor_commission: 0,
                    activities_by_type: {},
                    demonstrations_count: 0,
                    closures_count: 0,
                    properties_count: 0,
                }
            },
            advisors: [],
            selectedAdvisorId: '',
            isAdmin: false,
            loadingAdvisors: false,
            
            activitiesChart: {
                labels: [],
                dataSet: []
            },
            metricsSummaryChart: {
                labels: [],
                dataSet: []
            }
        }
    },

    mounted() {
       
        this.loadAdvisors();
        
        this.loadReports();
    },

    watch: {
        selectedAdvisorId() {
            this.loadReports();
        },
        'reportFilters.startDate': 'loadReports',
        'reportFilters.endDate': 'loadReports',
    },

    methods: {
        genChartData(data) {
            return [
                {
                    barPercentage: 0.5,
                    barThickness: 25,
                    borderWidth: 1,
                    borderColor: colorArray.slice(0, data.length),
                    backgroundColor: colorArray.slice(0, data.length),
                    data: data.map(i => i.value)
                }
            ]
        },

        async loadAdvisors() {
            this.loadingAdvisors = true;
            this.axiosGet('/app/reports/advisors')
                .then(response => {
                    const data = response.data;
                    this.advisors = Array.isArray(data) ? data : [];
                })
                .catch(e => console.error(e))
                .finally(() => {
                    this.loadingAdvisors = false;
                });
        },

        async loadReports() {
            this.preloader = true;
            const params = {};
            
            if (this.isAdmin && this.selectedAdvisorId) {
                params.user_id = this.selectedAdvisorId;
            }
            if (this.reportFilters.startDate) {
                params.start_date = this.reportFilters.startDate;
            }
            if (this.reportFilters.endDate) {
                params.end_date = this.reportFilters.endDate;
            }

            this.axiosGet('/app/reports/advisor', { params })
                .then(response => {
                    this.reports = response.data;
                    if (response.data.metrics && response.data.metrics.activities_by_type) {
                        this.processChartData(response.data.metrics);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    this.$toastr.e(this.$t('reports.error_loading'));
                })
                .finally(() => {
                    this.preloader = false;
                });
        },

        processChartData(metrics) {
            const activityKeys = Object.keys(metrics.activities_by_type);
            const activityValues = Object.values(metrics.activities_by_type).map(val => ({ value: val }));

            this.activitiesChart.labels = activityKeys.map(key => 
                key.charAt(0).toUpperCase() + key.slice(1)
            );
            this.activitiesChart.dataSet = this.genChartData(activityValues);

            const summaryData = [
                { label: this.$t('reports.demonstrations'), value: metrics.demonstrations_count },
                { label: this.$t('reports.closures'), value: metrics.closures_count },
                { label: this.$t('reports.sales'), value: metrics.sales_count },
                { label: this.$t('reports.reservations'), value: metrics.reservations_count },
                { label: this.$t('reports.properties_captured'), value: metrics.properties_count }
            ];

            this.metricsSummaryChart.labels = summaryData.map(d => d.label);
            this.metricsSummaryChart.dataSet = this.genChartData(summaryData.map(d => ({ value: d.value })));
        },

        formatCurrency(val) {
            return (parseFloat(val) || 0).toFixed(2);
        }
    }
}
</script>

<style scoped>
/* Ajustes menores para asegurar que se vea bien en móviles */
.mt-primary {
    margin-top: 1.5rem;
}
.mb-primary {
    margin-bottom: 1.5rem;
}
</style>