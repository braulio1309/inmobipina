<template>
    <div>
        <app-breadcrumb 
            :page-title="$t('reports.advisor_reports')" 
            :directory="$t('reports.title')" 
            :icon="'chart-line'"
        />

        <div class="container-fluid p-0 mt-4">
            
            <div v-if="canFilterAdvisors" class="row mb-4">
                <div class="col-12">
                    <div class="card card-with-shadow border-0">
                        <div class="card-body py-3 d-flex align-items-center justify-content-between">
                            <label class="mb-0 fw-bold me-3">{{ $t('reports.select_advisor') }}:</label>
                            <app-input class="col-sm-8"
                               :placeholder="$t('search_and_select')"
                               type="search-select"
                               :list="advisors"
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

                <div class="row mb-primary">
                    <div class="col-12">
                        <div class="card card-with-shadow border-0">
                            <div class="card-body">
                                <h4 class="card-title mb-3">Resumen de rendimiento por actividad</h4>
                                <div class="activity-cards-grid">
                                    <div
                                        v-for="(activity, index) in activityMetricCards"
                                        :key="`activity-metric-${activity.type}`"
                                        class="activity-metric-card"
                                    >
                                        <div class="activity-metric-icon" :style="{ background: getActivityMetricGradient(index) }">
                                            <i :class="getActivityIcon(activity.type)"></i>
                                        </div>
                                        <div class="activity-metric-content">
                                            <div class="text-muted mb-1 text-uppercase small">{{ formatActivityTypeLabel(activity.type) }}</div>
                                            <div class="h3 mb-0">{{ activity.count }}</div>
                                        </div>
                                    </div>
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
    props: {
        props: {
            type: Object,
            default: () => ({}),
        },
        isAdmin: {
            type: Boolean,
            default: false,
        },
    },

    inject: {
        reportFilters: {
            default: () => ({ startDate: '', endDate: '' }),
        },
    },

    data() {
        return {
            currentFilters: {
                startDate: '',
                endDate: '',
            },
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

    computed: {
        canFilterAdvisors() {
            return Boolean(this.props?.isAdmin ?? this.isAdmin);
        },
        activityMetricCards() {
            const metrics = this.reports?.metrics || {};
            const normalized = this.getNormalizedActivitiesByType(metrics.activities_by_type);
            const baseTypes = ['captación', 'demostración', 'publicidad', 'reserva', 'venta', 'alquiler'];

            const cards = baseTypes.map((type) => ({
                type,
                count: Number(normalized[type] || 0),
            }));

            if (!normalized['demostración'] && Number(metrics.demonstrations_count || 0) > 0) {
                cards.find(card => card.type === 'demostración').count = Number(metrics.demonstrations_count || 0);
            }
            if (!normalized['venta'] && Number(metrics.sales_count || 0) > 0) {
                cards.find(card => card.type === 'venta').count = Number(metrics.sales_count || 0);
            }
            if (!normalized['reserva'] && Number(metrics.reservations_count || 0) > 0) {
                cards.find(card => card.type === 'reserva').count = Number(metrics.reservations_count || 0);
            }

            return cards;
        },
    },

    mounted() {
        this.syncFilters(this.reportFilters);
        this.$hub.$on('report-filters-changed', this.syncFilters);
        this.$hub.$on('report-filters-changed', this.loadReports);
        if (this.canFilterAdvisors) {
            this.loadAdvisors();
        }
        this.loadReports();
    },
    beforeDestroy() {
        this.$hub.$off('report-filters-changed', this.syncFilters);
        this.$hub.$off('report-filters-changed', this.loadReports);
    },

    watch: {
        selectedAdvisorId() {
            this.loadReports();
        },
        'reportFilters.startDate': 'loadReports',
        'reportFilters.endDate': 'loadReports',
    },

    methods: {
        syncFilters(filters = {}) {
            this.currentFilters.startDate = filters.startDate || '';
            this.currentFilters.endDate = filters.endDate || '';
        },
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
            
            if (this.canFilterAdvisors && this.selectedAdvisorId) {
                params.user_id = this.selectedAdvisorId;
            }
            if (this.currentFilters.startDate) {
                params.start_date = this.currentFilters.startDate;
            }
            if (this.currentFilters.endDate) {
                params.end_date = this.currentFilters.endDate;
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
            const normalizedActivities = this.getNormalizedActivitiesByType(metrics.activities_by_type);
            const activityKeys = Object.keys(normalizedActivities);
            const activityValues = Object.values(normalizedActivities).map(val => ({ value: val }));

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
        },

        getNormalizedActivitiesByType(raw) {
            if (!raw) {
                return {};
            }

            if (Array.isArray(raw)) {
                return raw.reduce((accumulator, item) => {
                    const type = String(item?.type || '').toLowerCase().trim();
                    if (!type) {
                        return accumulator;
                    }

                    accumulator[type] = Number(item?.count || 0);
                    return accumulator;
                }, {});
            }

            if (typeof raw === 'object') {
                return Object.entries(raw).reduce((accumulator, [type, count]) => {
                    const normalizedType = String(type || '').toLowerCase().trim();
                    if (!normalizedType) {
                        return accumulator;
                    }

                    accumulator[normalizedType] = Number(count || 0);
                    return accumulator;
                }, {});
            }

            return {};
        },

        formatActivityTypeLabel(type) {
            if (!type) {
                return 'Actividad';
            }

            return String(type)
                .replace(/[_-]+/g, ' ')
                .trim()
                .replace(/\s+/g, ' ')
                .replace(/\b\w/g, (char) => char.toUpperCase());
        },

        getActivityIcon(type) {
            const icons = {
                'demostración': 'fas fa-eye',
                'captación': 'fas fa-building',
                'publicidad': 'fas fa-bullhorn',
                'venta': 'fas fa-dollar-sign',
                'alquiler': 'fas fa-key',
                'reserva': 'fas fa-calendar-check',
            };

            return icons[String(type || '').toLowerCase()] || 'fas fa-tasks';
        },

        getActivityMetricGradient(index) {
            const gradients = [
                'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
                'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)',
                'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
                'linear-gradient(135deg, #fa709a 0%, #fee140 100%)',
                'linear-gradient(135deg, #f7971e 0%, #ffd200 100%)',
                'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
            ];

            return gradients[index % gradients.length];
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

.activity-cards-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 14px;
}

.activity-metric-card {
    display: flex;
    align-items: center;
    border: 1px solid #edf0f2;
    border-radius: 12px;
    padding: 14px;
    background: #fff;
}

.activity-metric-icon {
    width: 48px;
    height: 48px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 18px;
    margin-right: 12px;
}

.activity-metric-content {
    min-width: 0;
}

@media (max-width: 768px) {
    .activity-cards-grid {
        grid-template-columns: 1fr;
    }
}
</style>