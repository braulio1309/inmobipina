<template>
    <div>
        <app-overlay-loader v-if="preloader" />
        <div v-else>
            <div class="row">
                <div class="col-12 col-md-6 col-lg-3">
                    <div class="card card-with-shadow border-0">
                        <div class="card-body d-flex justifycontent-center align-items-center">
                            <div class="text-center w-100">
                                <div class="text-muted">Ventas en el período</div>
                                <div class="h1">{{ overview ? overview.new_candidates : 0 }}</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-3">
                    <div class="card card-with-shadow border-0">
                        <div class="card-body d-flex justifycontent-center align-items-center">
                            <div class="text-center w-100">
                                <div class="text-muted">Captaciones en el período</div>
                                <div class="h1">{{ overview ? overview.moved_forward : 0 }}</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-3">
                    <div class="card card-with-shadow border-0">
                        <div class="card-body d-flex justifycontent-center align-items-center">
                            <div class="text-center w-100">
                                <div class="text-muted">Reservas en el período</div>
                                <div class="h1">{{ overview ? overview.hired : 0 }}</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-3">
                    <div class="card card-with-shadow border-0">
                        <div class="card-body d-flex justifycontent-center align-items-center">
                            <div class="text-center w-100">
                                <div class="text-muted">Comisión Inmobiliaria (USD)</div>
                                <div class="h1">${{ overview ? formatNumber(overview.active_jobs) : '0.00' }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row mt-primary">
                <div class="col">
                    <div class="card card-with-shadow border-0">
                        <div class="card-body">
                            <h4>Ganancias de la Inmobiliaria</h4>
                            <app-chart class="mb-primary" type="line-chart" :height="230" :labels="performance.labels"
                                :data-sets="performance.dataSet" />
                        </div>
                    </div>
                </div>
            </div>
            <div class="row mt-primary">
                <div class="col-12 col-md-6">
                    <div class="card card-with-shadow border-0">
                        <div class="card-body">
                            <h4>Asesores con más actividades</h4>
                            <app-chart class="mb-primary" type="bar-chart" :height="230" :labels="topCandidates.labels"
                                :data-sets="topCandidates.dataSet" />
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <div class="card card-with-shadow border-0">
                        <div class="card-body">
                            <h4>Actividades por Tipo</h4>
                            <app-chart class="mb-primary" type="dough-chart" :height="230" :labels="newCandidates.labels"
                                :data-sets="newCandidates.dataSet" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import {
    OVERVIEW_STATS,
    PERFORMANCE_CHART,
    ACTIVITIES_BY_TYPE_REPORT,
    TOP_ADVISORS_ACTIVITIES,
} from '../../../../../Config/ApiUrl';
import { FormMixin } from '../../../../../../core/mixins/form/FormMixin';

export default {
    mixins: [FormMixin],
    inject: {
        reportFilters: {
            default: () => ({ startDate: '', endDate: '', reportUnit: 'count' }),
        },
    },
    data() {
        return {
            preloader: true,
            overview: null,
            performance: {
                labels: [],
                dataSet: []
            },
            topCandidates: {
                labels: [],
                dataSet: []
            },
            newCandidates: {
                labels: [],
                dataSet: []
            }
        }
    },
    watch: {
        'reportFilters.startDate': 'reloadAll',
        'reportFilters.endDate': 'reloadAll',
    },
    methods: {
        buildQuery() {
            return {
                start_date: this.reportFilters.startDate || '',
                end_date: this.reportFilters.endDate || '',
            };
        },
        genBarChartData(data) {
            return [
                {
                    barPercentage: 0.5,
                    barThickness: 15,
                    borderWidth: 1,
                    borderColor: [
                        "#5a86f1", "#5bc5d5", "#eb779e", "#46cc97", "#368cd5",
                        "#f7971e", "#764ba2", "#43e97b", "#fa709a", "#fee140"
                    ],
                    backgroundColor: [
                        "#5a86f1", "#5bc5d5", "#eb779e", "#46cc97", "#368cd5",
                        "#f7971e", "#764ba2", "#43e97b", "#fa709a", "#fee140"
                    ],
                    data: data.map(i => i.total_candidates)
                }
            ];
        },
        genDoughnutChartData(data) {
            const colors = [
                "#5a86f1", "#5bc5d5", "#eb779e", "#46cc97", "#368cd5",
                "#f7971e", "#764ba2", "#43e97b", "#fa709a", "#fee140"
            ];
            return [
                {
                    backgroundColor: colors.slice(0, data.length),
                    borderWidth: 0,
                    data: data.map(i => i.total_candidates)
                }
            ];
        },
        getOverview(query) {
            return this.axiosGet(OVERVIEW_STATS, { params: query })
                .then(res => {
                    this.overview = res.data;
                });
        },
        getPerformanceChart(query) {
            return this.axiosGet(PERFORMANCE_CHART, { params: query })
                .then(res => {
                    this.performance.labels = res.data.labels;
                    this.performance.dataSet = [
                        {
                            label: 'Comisión Inmobiliaria (USD)',
                            data: res.data.values,
                            backgroundColor: '#5a86f1',
                            borderColor: '#5a86f1',
                            fill: false,
                            cubicInterpolationMode: 'monotone',
                            tension: 0.4,
                        }
                    ];
                });
        },
        getActivitiesByType(query) {
            return this.axiosGet(ACTIVITIES_BY_TYPE_REPORT, { params: query })
                .then(res => {
                    const data = res.data;
                    this.newCandidates.labels = data.map(i => i.name);
                    this.newCandidates.dataSet = this.genDoughnutChartData(data);
                });
        },
        getTopAdvisors(query) {
            return this.axiosGet(TOP_ADVISORS_ACTIVITIES, { params: query })
                .then(res => {
                    const data = res.data;
                    this.topCandidates.labels = data.map(i => i.name);
                    this.topCandidates.dataSet = this.genBarChartData(data);
                });
        },
        reloadAll() {
            this.preloader = true;
            const query = this.buildQuery();
            Promise.all([
                this.getOverview(query),
                this.getPerformanceChart(query),
                this.getActivitiesByType(query),
                this.getTopAdvisors(query),
            ]).finally(() => {
                this.preloader = false;
            });
        },
        formatNumber(val) {
            return (parseFloat(val) || 0).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        },
    },
    mounted() {
        this.reloadAll();
    }
}
</script>
