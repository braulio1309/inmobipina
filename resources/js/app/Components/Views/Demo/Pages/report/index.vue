<template>
    <div class="content-wrapper">
        <app-breadcrumb
            :page-title="$t('reports.title')"
            :directory="$t('reports.title')"
            :icon="'bar-chart-2'"
        />

        <!-- Panel de Filtros Global: persiste en todas las pestañas -->
        <div class="card card-with-shadow border-0 mb-primary">
            <div class="card-body p-primary">
                <div class="row align-items-end">
                    <div class="col-12 col-md-4 mb-2 mb-md-0">
                        <label class="d-block mb-1">{{ $t('reports.start_date') }}</label>
                        <app-input
                            type="date"
                            v-model="filters.startDate"
                            @input="applyFilters"
                        />
                    </div>
                    <div class="col-12 col-md-4 mb-2 mb-md-0">
                        <label class="d-block mb-1">{{ $t('reports.end_date') }}</label>
                        <app-input
                            type="date"
                            v-model="filters.endDate"
                            @input="applyFilters"
                        />
                    </div>
                    <div class="col-12 col-md-4">
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

        <!-- Vistas de Pestañas -->
        <app-tab-group :tabs="tabs" />
    </div>
</template>

<script>
import { FormMixin } from "../../../../../../core/mixins/form/FormMixin.js";

export default {
    name: 'ReportView',
    mixins: [FormMixin],
    props: {
        isAdmin: {
            type: Boolean,
            default: false,
        },
    },

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
            },
            tabs: {
                global: {
                    label: this.$t('reports.title'),
                    items: [
                        {
                            name: this.$t('reports.top_sellers_tab'),
                            title: this.$t('reports.top_sellers_tab'),
                            component: 'basic-report',
                            props: {},
                            headerHide: true,
                        },
                        {
                            name: this.$t('reports.sales_tab'),
                            title: this.$t('reports.sales_tab'),
                            component: 'overview',
                            props: {},
                            headerHide: true,
                        },
                        {
                            name: this.$t('reports.advisor_data_tab'),
                            title: this.$t('reports.advisor_data_tab'),
                            component: 'job-overview',
                            props: {
                                isAdmin: this.isAdmin,
                            },
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
            this.applyFilters();
        },
    },
};
</script>