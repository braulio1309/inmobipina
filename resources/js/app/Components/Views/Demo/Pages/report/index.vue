<template>
    <div class="content-wrapper">
        <app-breadcrumb
            :page-title="$t('report')"
            :directory="$t('reports.title')"
            :icon="'bar-chart-2'"
        />

        <!-- Global Filters Panel: persistent across all report tabs -->
        <div class="card card-with-shadow border-0 mb-primary">
            <div class="card-body p-primary">
                <div class="row align-items-end">
                    <div class="col-12 col-md-3 mb-2 mb-md-0">
                        <label class="d-block mb-1">{{ $t('reports.start_date') }}</label>
                        <app-input
                            type="date"
                            v-model="filters.startDate"
                            @input="applyFilters"
                        />
                    </div>
                    <div class="col-12 col-md-3 mb-2 mb-md-0">
                        <label class="d-block mb-1">{{ $t('reports.end_date') }}</label>
                        <app-input
                            type="date"
                            v-model="filters.endDate"
                            @input="applyFilters"
                        />
                    </div>
                    <div class="col-12 col-md-4 mb-2 mb-md-0">
                        <p class="mb-1">{{ $t('reports.order_report_by') }}</p>
                        <app-input
                            type="radio-buttons"
                            v-model="filters.reportUnit"
                            :list="unitList"
                        />
                    </div>
                    <div class="col-12 col-md-2">
                        <button
                            class="btn btn-secondary btn-block"
                            @click="clearFilters"
                        >
                            {{ $t('clear_filter') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab Views -->
        <app-tab-group :tabs="tabs" />
    </div>
</template>

<script>
export default {
    name: 'ReportView',

    provide() {
        return {
            reportFilters: this.filters,
        };
    },

    data() {
        return {
            filters: {
                startDate: '',
                endDate: '',
                reportUnit: 'count',
            },
            unitList: [
                { id: 'count', value: this.$t('reports.count') },
                { id: 'value', value: this.$t('reports.value') },
            ],
            tabs: {
                global: {
                    label: 'Report',
                    items: [
                        {
                            name: this.$t('basic_report'),
                            title: this.$t('basic_report'),
                            component: 'basic-report',
                            props: {},
                            headerHide: true,
                        },
                        {
                            name: this.$t('overview'),
                            title: this.$t('overview'),
                            component: 'overview',
                            props: {},
                            headerHide: true,
                        },
                        {
                            name: this.$t('job_overview'),
                            title: this.$t('job_overview'),
                            component: 'job-overview',
                            props: {},
                            headerHide: true,
                        },
                    ],
                },
            },
        };
    },

    methods: {
        applyFilters() {
            this.$hub.$emit('report-filters-changed', { ...this.filters });
        },
        clearFilters() {
            this.filters.startDate = '';
            this.filters.endDate = '';
            this.filters.reportUnit = 'count';
            this.applyFilters();
        },
    },
};
</script>