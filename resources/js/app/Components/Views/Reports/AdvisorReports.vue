<template>
    <div class="advisor-reports">
        <div class="card">
            <div class="card-header">
                <h4>{{ $t('reports.advisor_reports') }}</h4>
            </div>
            
            <div class="card-body">
                <!-- Selector de Asesor (solo para admins) -->
                <div v-if="isAdmin" class="form-group mb-4">
                    <label>{{ $t('reports.select_advisor') }}</label>
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

                <!-- Nombre del Asesor -->
                <div v-if="reports" class="advisor-name mb-4">
                    <h5>{{ $t('reports.reports_for') }}: <strong>{{ reports.advisor.name }}</strong></h5>
                </div>

                <!-- Métricas -->
                <div v-if="reports" class="metrics-grid">
                    <!-- Ventas -->
                    <div class="metric-card">
                        <div class="metric-icon sales">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                        <div class="metric-content">
                            <h6>{{ $t('reports.sales') }}</h6>
                            <h3>{{ reports.metrics.sales_count }}</h3>
                        </div>
                    </div>

                    <!-- Reservas -->
                    <div class="metric-card">
                        <div class="metric-icon reservations">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="metric-content">
                            <h6>{{ $t('reports.reservations') }}</h6>
                            <h3>{{ reports.metrics.reservations_count }}</h3>
                        </div>
                    </div>

                    <!-- Captaciones -->
                    <div class="metric-card">
                        <div class="metric-icon properties">
                            <i class="fas fa-home"></i>
                        </div>
                        <div class="metric-content">
                            <h6>{{ $t('reports.properties_captured') }}</h6>
                            <h3>{{ reports.metrics.properties_count }}</h3>
                        </div>
                    </div>
                </div>

                <!-- Actividades por Tipo -->
                <div v-if="reports" class="activities-section mt-4">
                    <h5>{{ $t('reports.activities_by_type') }}</h5>
                    <div class="activities-grid">
                        <div 
                            v-for="(count, type) in reports.metrics.activities_by_type" 
                            :key="type"
                            class="activity-item"
                        >
                            <span class="activity-type">{{ type }}</span>
                            <span class="activity-count badge badge-primary">{{ count }}</span>
                        </div>
                        <div v-if="Object.keys(reports.metrics.activities_by_type).length === 0" class="text-muted">
                            {{ $t('reports.no_activities') }}
                        </div>
                    </div>
                </div>

                <!-- Loading -->
                <div v-if="loading" class="text-center py-5">
                    <div class="spinner-border" role="status">
                        <span class="sr-only">{{ $t('common.loading') }}</span>
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
            reports: null,
            advisors: [],
            selectedAdvisorId: '',
            loading: false,
            isAdmin: false,
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
            // Verificar si el usuario tiene rol de admin
            const user = this.$store.state.user || window.user;
            this.isAdmin = user && user.roles && 
                user.roles.some(role => ['Admin', 'Administrator'].includes(role.name));
        },
        
        async loadAdvisors() {
            try {
                const response = await this.$axios.get('/app/reports/advisors');
                this.advisors = response.data;
            } catch (error) {
                console.error('Error loading advisors:', error);
            }
        },
        
        async loadReports() {
            this.loading = true;
            try {
                const params = {};
                if (this.selectedAdvisorId) {
                    params.user_id = this.selectedAdvisorId;
                }
                
                const response = await this.$axios.get('/app/reports/advisor', { params });
                this.reports = response.data;
            } catch (error) {
                console.error('Error loading reports:', error);
                this.$toastr.error(this.$t('reports.error_loading'));
            } finally {
                this.loading = false;
            }
        },
    }
}
</script>

<style scoped>
.advisor-reports {
    padding: 20px;
}

.advisor-name {
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
    border-left: 4px solid #007bff;
}

.metrics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.metric-card {
    display: flex;
    align-items: center;
    padding: 20px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: transform 0.2s;
}

.metric-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.metric-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    margin-right: 15px;
    color: white;
}

.metric-icon.sales {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.metric-icon.reservations {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.metric-icon.properties {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}

.metric-content h6 {
    margin: 0;
    color: #6c757d;
    font-size: 14px;
    text-transform: uppercase;
}

.metric-content h3 {
    margin: 5px 0 0;
    font-size: 32px;
    font-weight: bold;
    color: #2c3e50;
}

.activities-section {
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
}

.activities-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 15px;
    margin-top: 15px;
}

.activity-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 15px;
    background: white;
    border-radius: 6px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.activity-type {
    font-weight: 500;
    text-transform: capitalize;
}

.activity-count {
    font-size: 16px;
    padding: 5px 12px;
}

@media (max-width: 768px) {
    .metrics-grid {
        grid-template-columns: 1fr;
    }
    
    .activities-grid {
        grid-template-columns: 1fr;
    }
}
</style>
