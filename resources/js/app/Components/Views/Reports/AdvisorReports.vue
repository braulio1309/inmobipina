<template>
    <div class="advisor-reports">
        <app-breadcrumb 
            :page-title="$t('reports.advisor_reports')" 
            :directory="$t('reports.title')" 
            :icon="'chart-line'"
        />

        <div class="container-fluid mt-4">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body">
                    <div v-if="isAdmin" class="form-group mb-4">
                        <label class="form-label fw-semibold">{{ $t('reports.select_advisor') }}</label>
                        <select 
                            v-model="selectedAdvisorId" 
                            @change="loadReports"
                            class="form-control"
                        >
                            <option value="">{{ $t('reports.my_reports') }}</option>
                            <option 
                                v-for="advisor in advisors" 
                                :key="advisor.id" 
                                :value="advisor.id"
                            >
                                {{ advisor.name }}
                            </option>
                        </select>
                    </div>

                    <div  class="advisor-name mb-4">
                        <h5>{{ $t('reports.reports_for') }}: <strong>{{ reports.advisor.name }}</strong></h5>
                    </div>

                    <div  class="metrics-grid">
                        <div class="metric-card shadow-sm">
                            <div class="metric-icon demonstrations">
                                <i class="fas fa-eye"></i>
                            </div>
                            <div class="metric-content">
                                <h6>{{ $t('reports.demonstrations') }}</h6>
                                <h3>{{ reports.metrics.demonstrations_count }}</h3>
                            </div>
                        </div>

                        <div class="metric-card shadow-sm">
                            <div class="metric-icon closures">
                                <i class="fas fa-handshake"></i>
                            </div>
                            <div class="metric-content">
                                <h6>{{ $t('reports.closures') }}</h6>
                                <h3>{{ reports.metrics.closures_count }}</h3>
                            </div>
                        </div>

                        <div class="metric-card shadow-sm">
                            <div class="metric-icon sales">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                            <div class="metric-content">
                                <h6>{{ $t('reports.sales') }}</h6>
                                <h3>{{ reports.metrics.sales_count }}</h3>
                            </div>
                        </div>

                        <div class="metric-card shadow-sm">
                            <div class="metric-icon reservations">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                            <div class="metric-content">
                                <h6>{{ $t('reports.reservations') }}</h6>
                                <h3>{{ reports.metrics.reservations_count }}</h3>
                            </div>
                        </div>
                    </div>

                    <div  class="activities-section mt-4 border-0 shadow-sm">
                        <h5 class="fw-semibold mb-3">{{ $t('reports.activities_by_type') }}</h5>
                        <div class="activities-grid">
                            <div 
                                v-for="(count, type) in reports.metrics.activities_by_type" 
                                :key="type"
                                class="activity-item border"
                            >
                                <div class="activity-info">
                                    <i :class="getActivityIcon(type)"></i>
                                    <span class="activity-type text-capitalize">{{ type }}</span>
                                </div>
                                <span class="activity-count badge bg-primary rounded-pill">{{ count }}</span>
                            </div>
                            <div v-if="Object.keys(reports.metrics.activities_by_type).length === 0" class="text-center w-100 py-3 text-muted">
                                {{ $t('reports.no_activities') }}
                            </div>
                        </div>
                    </div>

                    <div v-if="preloader" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">{{ $t('common.loading') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
// Importamos el mixin igual que en tu vista de usuario
import { FormMixin } from '../../../../core/mixins/form/FormMixin.js';

export default {
    name: 'AdvisorReports',
    mixins: [FormMixin],
    
    data() {
        return {
            reports: null,
            advisors: [],
            selectedAdvisorId: '',
            isAdmin: false,
            activityIcons: {
                'demostración': 'fas fa-eye',
                'captación': 'fas fa-building',
                'venta': 'fas fa-dollar-sign',
                'alquiler': 'fas fa-key',
                'reserva': 'fas fa-calendar-check',
            },
        }
    },
    
    mounted() {
        this.checkAdminRole();
        if (this.isAdmin) {
            this.loadAdvisors();
        }
        this.loadReports();
    },
    
    methods: {
        checkAdminRole() {
            const user = this.$store.state.user || window.user;
            this.isAdmin = user && user.roles && 
                user.roles.some(role => ['Admin', 'Administrator'].includes(role.name));
        },
        
        async loadAdvisors() {
            // Usando axiosGet del mixin
            this.axiosGet('/app/reports/advisors')
                .then(response => {
                    this.advisors = response.data;
                })
                .catch(error => {
                    console.error('Error loading advisors:', error);
                });
        },
        
        async loadReports() {
            this.preloader = true; // Variable controlada por FormMixin
            
            const params = {};
            if (this.selectedAdvisorId) {
                params.user_id = this.selectedAdvisorId;
            }

            // Usando axiosGet con parámetros
            this.axiosGet('/app/reports/advisor', { params })
                .then(response => {
                    this.reports = response.data;
                })
                .catch(error => {
                    console.error('Error loading reports:', error);
                    // Uso de toastr.e (error) según el estándar del mixin
                    this.$toastr.e(this.$t('reports.error_loading'));
                })
                .finally(() => {
                    this.preloader = false;
                });
        },
        
        getActivityIcon(type) {
            return this.activityIcons[type.toLowerCase()] || 'fas fa-tasks';
        }
    }
}
</script>

<style scoped>
.advisor-name {
    padding: 15px;
    background: #f0f7ff;
    border-radius: 12px;
    border-left: 5px solid #0d6efd;
}

.metrics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.metric-card {
    display: flex;
    align-items: center;
    padding: 20px;
    background: white;
    border-radius: 16px;
    transition: all 0.3s ease;
}

.metric-card:hover {
    transform: translateY(-5px);
}

.metric-icon {
    width: 55px;
    height: 55px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    margin-right: 15px;
    color: white;
}

/* Colores degradados similares a tu estilo */
.metric-icon.sales { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
.metric-icon.reservations { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
.metric-icon.properties { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
.metric-icon.demonstrations { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); }
.metric-icon.closures { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); }

.metric-content h6 {
    margin: 0;
    color: #6c757d;
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.metric-content h3 {
    margin: 2px 0 0;
    font-size: 28px;
    font-weight: 700;
    color: #2c3e50;
}

.activities-section {
    padding: 25px;
    background: #ffffff;
    border-radius: 16px;
}

.activities-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
    gap: 15px;
}

.activity-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 10px;
}

.activity-info i {
    font-size: 1.1rem;
    color: #0d6efd;
    width: 30px;
}

.activity-type {
    font-weight: 600;
    color: #495057;
}

@media (max-width: 768px) {
    .metrics-grid, .activities-grid {
        grid-template-columns: 1fr;
    }
}
</style>