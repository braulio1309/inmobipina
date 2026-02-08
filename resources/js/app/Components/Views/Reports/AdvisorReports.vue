<template>
    <div class="content-wrapper">
        <div class="row">
            <div class="col-12">
                <div class="card border-0 shadow">
                    <div class="card-header d-flex align-items-center justify-content-between p-primary primary">
                        <h5 class="card-title d-inline-block mb-0 text-white">
                            {{ $t('advisor_reports') }}
                        </h5>
                    </div>

                    <div class="card-body">
                        <!-- Admin: Advisor Selector -->
                        <div v-if="isAdmin" class="row mb-4">
                            <div class="col-md-6">
                                <label class="mb-2">{{ $t('select_advisor') }}</label>
                                <select 
                                    v-model="selectedAdvisorId" 
                                    @change="fetchAdvisorMetrics"
                                    class="form-control"
                                >
                                    <option value="">{{ $t('select_an_advisor') }}</option>
                                    <option 
                                        v-for="advisor in advisors" 
                                        :key="advisor.id" 
                                        :value="advisor.id"
                                    >
                                        {{ advisor.name }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <!-- Advisor Name Display -->
                        <div v-if="metrics && advisor" class="row mb-4">
                            <div class="col-12">
                                <div class="alert alert-info d-flex align-items-center">
                                    <i class="fas fa-user-tie mr-2"></i>
                                    <h5 class="mb-0">
                                        {{ $t('advisor') }}: <strong>{{ advisor.name }}</strong>
                                    </h5>
                                </div>
                            </div>
                        </div>

                        <!-- Loading State -->
                        <div v-if="loading" class="text-center py-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="sr-only">{{ $t('loading') }}...</span>
                            </div>
                        </div>

                        <!-- Metrics Cards -->
                        <div v-if="!loading && metrics" class="row">
                            <!-- Sales Card -->
                            <div class="col-md-4 mb-4">
                                <div class="card border-0 shadow-sm h-100">
                                    <div class="card-body text-center">
                                        <div class="icon-circle bg-success text-white mb-3 mx-auto" 
                                             style="width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-dollar-sign fa-2x"></i>
                                        </div>
                                        <h3 class="mb-2">{{ metrics.sales_count }}</h3>
                                        <p class="text-muted mb-0">{{ $t('sales') }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Reservations Card -->
                            <div class="col-md-4 mb-4">
                                <div class="card border-0 shadow-sm h-100">
                                    <div class="card-body text-center">
                                        <div class="icon-circle bg-warning text-white mb-3 mx-auto" 
                                             style="width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-calendar-check fa-2x"></i>
                                        </div>
                                        <h3 class="mb-2">{{ metrics.reservations_count }}</h3>
                                        <p class="text-muted mb-0">{{ $t('reservations') }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Properties Card -->
                            <div class="col-md-4 mb-4">
                                <div class="card border-0 shadow-sm h-100">
                                    <div class="card-body text-center">
                                        <div class="icon-circle bg-primary text-white mb-3 mx-auto" 
                                             style="width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-home fa-2x"></i>
                                        </div>
                                        <h3 class="mb-2">{{ metrics.properties_count }}</h3>
                                        <p class="text-muted mb-0">{{ $t('properties_captured') }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Activities by Type -->
                        <div v-if="!loading && metrics" class="row mt-4">
                            <div class="col-12">
                                <div class="card border-0 shadow-sm">
                                    <div class="card-header bg-light">
                                        <h5 class="mb-0">{{ $t('activities_by_type') }}</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>{{ $t('activity_type') }}</th>
                                                        <th class="text-right">{{ $t('count') }}</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr v-for="(count, type) in metrics.activities_by_type" :key="type">
                                                        <td>
                                                            <i :class="getActivityIcon(type)" class="mr-2"></i>
                                                            {{ $t(type) || type }}
                                                        </td>
                                                        <td class="text-right">
                                                            <span class="badge badge-primary badge-pill">{{ count }}</span>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                                <tfoot>
                                                    <tr class="font-weight-bold">
                                                        <td>{{ $t('total') }}</td>
                                                        <td class="text-right">
                                                            <span class="badge badge-success badge-pill">{{ totalActivities }}</span>
                                                        </td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- No Data Message -->
                        <div v-if="!loading && !metrics && !isAdmin" class="alert alert-info">
                            {{ $t('no_data_available') }}
                        </div>

                        <div v-if="!loading && !metrics && isAdmin && !selectedAdvisorId" class="alert alert-info">
                            {{ $t('please_select_an_advisor') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'AdvisorReports',
    data() {
        return {
            loading: false,
            isAdmin: false,
            advisors: [],
            selectedAdvisorId: null,
            advisor: null,
            metrics: null
        };
    },
    computed: {
        totalActivities() {
            if (!this.metrics || !this.metrics.activities_by_type) {
                return 0;
            }
            return Object.values(this.metrics.activities_by_type).reduce((sum, count) => sum + count, 0);
        }
    },
    mounted() {
        this.checkUserRole();
        if (this.isAdmin) {
            this.fetchAdvisors();
        } else {
            // Regular advisors see their own metrics automatically
            this.fetchAdvisorMetrics();
        }
    },
    methods: {
        checkUserRole() {
            // Check if current user is admin
            // We check both window.user (server-side rendered data) and 
            // this.$store?.state?.user (client-side Vuex state) to handle
            // different initialization scenarios in the application
            const user = window.user || this.$store?.state?.user;
            if (user) {
                this.isAdmin = user.is_admin || user.roles?.some(role => role.is_admin);
            }
        },
        
        async fetchAdvisors() {
            try {
                this.loading = true;
                const response = await axios.get('/api/reports/advisors');
                this.advisors = response.data.advisors;
            } catch (error) {
                console.error('Error fetching advisors:', error);
                this.$toaster.error(this.$t('error_fetching_advisors'));
            } finally {
                this.loading = false;
            }
        },

        async fetchAdvisorMetrics() {
            if (this.isAdmin && !this.selectedAdvisorId) {
                this.metrics = null;
                this.advisor = null;
                return;
            }

            try {
                this.loading = true;
                const params = this.selectedAdvisorId ? { user_id: this.selectedAdvisorId } : {};
                const response = await axios.get('/api/reports/advisor-metrics', { params });
                
                this.advisor = response.data.advisor;
                this.metrics = response.data.metrics;
            } catch (error) {
                console.error('Error fetching metrics:', error);
                this.$toaster.error(this.$t('error_fetching_metrics'));
                this.metrics = null;
                this.advisor = null;
            } finally {
                this.loading = false;
            }
        },

        getActivityIcon(type) {
            const icons = {
                'demostración': 'fas fa-eye',
                'captación': 'fas fa-hand-holding',
                'venta': 'fas fa-handshake',
                'alquiler': 'fas fa-key',
                'reserva': 'fas fa-bookmark'
            };
            return icons[type] || 'fas fa-tasks';
        }
    }
};
</script>

<style scoped>
.icon-circle {
    transition: transform 0.3s ease;
}

.card:hover .icon-circle {
    transform: scale(1.1);
}

.table tbody tr {
    transition: background-color 0.2s ease;
}

.table tbody tr:hover {
    background-color: #f8f9fa;
}

.badge-pill {
    font-size: 1rem;
    padding: 0.5rem 0.75rem;
}
</style>
