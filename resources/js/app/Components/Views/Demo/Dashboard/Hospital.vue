<template>
    <div class="position-relative">
        <div v-if="countCreatedResponse<3" class="root-preloader position-absolute overlay-loader-wrapper">
            <div class="spinner-bounce d-flex align-items-center dashboard-preloader justify-content-center">
                <span class="bounce1 mr-1"/>
                <span class="bounce2 mr-1"/>
                <span class="bounce3 mr-1"/>
                <span class="bounce4"/>
            </div>
        </div>

        <div class="content-wrapper">
            <app-breadcrumb :page-title="'Dashboard Inmobiliario'" :directory="$t('dashboard')" :icon="'pie-chart'"/>

            <!-- Date Filter Section -->
            <div class="card card-with-shadow border-0 mb-primary" v-if="!mainPreloader">
                <div class="card-body">
                    <div class="row align-items-end">
                        <div class="col-md-4">
                            <label class="mb-2">Fecha Inicial</label>
                            <app-input type="date"
                                       v-model="filters.startDate"
                                       :placeholder="'Seleccionar fecha inicial'"/>
                        </div>
                        <div class="col-md-4">
                            <label class="mb-2">Fecha Final</label>
                            <app-input type="date"
                                       v-model="filters.endDate"
                                       :placeholder="'Seleccionar fecha final'"/>
                        </div>
                        <div class="col-md-4">
                            <button type="button" class="btn btn-primary" @click="applyFilters">
                                Filtrar
                            </button>
                            <button type="button" class="btn btn-secondary ml-2" @click="resetFilters">
                                Limpiar
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <app-overlay-loader v-if="!isActivedDefaultInfo && !mainPreloader"/>
            <div class="row" v-if="isActivedDefaultInfo" >
                <div class="col-12 col-sm-6 col-xl-3" v-for="(item, index) in realEstateData.defaultData" :key="index">
                    <app-widget class="mb-primary"
                                :type="'app-widget-with-icon'"
                                :label="item.label"
                                :number="numberFormat(item.number)"
                                :icon="item.icon"/>
                </div>
            </div>

            <div class="row">
                <div class="col-12 col-md-9 mb-primary">
                    <app-overlay-loader v-if="!isActiveHospitalActivity && !mainPreloader"/>
                    <div class="card card-with-shadow border-0 h-100" v-if="isActiveHospitalActivity">
                        <div class="card-header bg-transparent p-primary d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">{{ 'Ventas y Captaciones en el Tiempo' }}</h5>
                        </div>
                        <div class="card-body">
                            <app-chart class="mb-primary"
                                       type="line-chart"
                                       :height="270"
                                       :labels="realEstateData.salesOverTime.labels"
                                       :data-sets="realEstateData.salesOverTime.chartData"/>
                            <div class="chart-data-list d-flex flex-wrap justify-content-center">
                                <div class="data-group-item" style="color: rgb(240, 84, 84);">
                                    <span class="square" style="background-color: rgb(240, 84, 84);"/>
                                    {{ 'Ventas' }}
                                    <span class="value">{{ realEstateData.salesOverTime.totalSales }}</span>
                                </div>
                                <div class="data-group-item" style="color: rgb(14, 73, 181);">
                                    <span class="square" style="background-color: rgb(14, 73, 181);"/>
                                    {{ 'Captaciones' }}
                                    <span class="value">{{ realEstateData.salesOverTime.totalCaptaciones }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-3 mb-primary">
                    <app-overlay-loader v-if="!isActivePatientStatistics && !mainPreloader"/>
                    <div class="card card-with-shadow border-0 h-100" v-if="isActivePatientStatistics">
                        <div class="card-header bg-transparent p-primary d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">{{ 'Actividades por Tipo' }}</h5>
                        </div>
                        <div class="card-body">
                            <app-chart class="mb-primary"
                                       type="pie-chart"
                                       :height="230"
                                       :labels="realEstateData.activitiesByType.labels"
                                       :data-sets="realEstateData.activitiesByType.dataSet"/>
                            <div class="chart-data-list">
                                <div class="row">
                                    <div class="col-12"
                                         v-for="item in realEstateData.activitiesByType.chartElement"
                                         :key="item.key">
                                        <div class="data-group-item" :style="item.color">
                                            <span class="square" :style="item.background_color"/>
                                            {{item.key}}
                                            <span class="value">{{item.value}}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Map Section -->
            <div class="row mb-primary" v-if="!mainPreloader">
                <div class="col-12 col-md-7">
                    <div class="card card-with-shadow border-0 h-100">
                        <div class="card-header d-flex align-items-center justify-content-between p-primary primary-card-color">
                            <h5 class="card-title d-inline-block mb-0">{{ 'Mapa de Propiedades' }}</h5>
                        </div>
                        <div class="card-body p-0">
                            <PropertiesMap />
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-5 mt-3 mt-md-0">
                    <app-overlay-loader v-if="!isActiveClientSources"/>
                    <div class="card card-with-shadow border-0 h-100" v-if="isActiveClientSources">
                        <div class="card-header bg-transparent p-primary d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">Clientes por Medio de Captación</h5>
                            <small class="text-muted">Total: {{ clientSources.total }}</small>
                        </div>
                        <div class="card-body">
                            <div v-if="clientSources.labels && clientSources.labels.length > 0">
                                <app-chart class="mb-primary"
                                           type="dough-chart"
                                           :height="200"
                                           :labels="clientSources.labels"
                                           :data-sets="[{
                                               borderWidth: 2,
                                               backgroundColor: clientSources.colors,
                                               data: clientSources.data
                                           }]"/>
                                <div class="chart-data-list">
                                    <div class="row">
                                        <div class="col-12"
                                             v-for="(label, idx) in clientSources.labels"
                                             :key="idx">
                                            <div class="data-group-item"
                                                 :style="'color:' + clientSources.colors[idx % clientSources.colors.length]">
                                                <span class="square"
                                                      :style="'background-color:' + clientSources.colors[idx % clientSources.colors.length]"/>
                                                {{ label }}
                                                <span class="value">{{ clientSources.data[idx] }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-center text-muted p-primary">
                                Sin datos de fuente de clientes
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TABLAS -->
            <div class="row" v-if="!mainPreloader">
                <div class="col-12 col-sm-12 col-md-6 mb-4 mb-md-0">
                    <div class="card card-with-shadow border-0">
                        <div class="card-header d-flex align-items-center justify-content-between p-primary primary-card-color">
                            <h5 class="card-title d-inline-block mb-0">Últimas Negociaciones</h5>
                        </div>
                        <div class="card-body p-0">
                            <div v-if="realEstateData.latestNegotiations && realEstateData.latestNegotiations.data && realEstateData.latestNegotiations.data.length > 0">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>Propiedad</th>
                                            <th>Tipo</th>
                                            <th>Cliente</th>
                                            <th>Monto</th>
                                            <th>Fecha</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(negotiation, idx) in realEstateData.latestNegotiations.data" :key="idx">
                                            <td>{{ negotiation.property }}</td>
                                            <td>{{ negotiation.type }}</td>
                                            <td>{{ negotiation.client }}</td>
                                            <td>{{ negotiation.amount ? '$' + numberFormat(negotiation.amount) : '$0' }}</td>
                                            <td>{{ negotiation.date }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div v-else class="p-primary text-center text-muted">
                                No hay negociaciones registradas
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-12 col-md-6">
                    <div class="card card-with-shadow border-0">
                        <div class="card-header d-flex align-items-center justify-content-between p-primary primary-card-color">
                            <h5 class="card-title d-inline-block mb-0">Top Vendedores</h5>
                        </div>
                        <div class="card-body p-0">
                            <div v-if="realEstateData.topSellers && realEstateData.topSellers.data && realEstateData.topSellers.data.length > 0">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>Vendedor</th>
                                            <th>Email</th>
                                            <th>Cierres</th>
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(seller, idx) in realEstateData.topSellers.data" :key="idx">
                                            <td>{{ seller.name }}</td>
                                            <td>{{ seller.email }}</td>
                                            <td>{{ seller.closures_count }}</td>
                                            <td>{{ seller.total_revenue ? '$' + numberFormat(seller.total_revenue) : '$0' }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div v-else class="p-primary text-center text-muted">
                                No hay vendedores registrados
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    import {FormMixin} from '../../../../../core/mixins/form/FormMixin';
    import {TableHelpers} from "../../Demo/Tables/mixins/TableHelpers";
    import * as actions from '../../../../Config/ApiUrl';
    import {numberFormatter} from "../../../../Helpers/Helpers";
    import PropertiesMap from '../Pages/map/PropertiesMap.vue';
    

    export default {
        name: "Hospital",
        mixins: [FormMixin, TableHelpers],
        components: {
            PropertiesMap
        },

        data() {
            return {
                /* Loader section and active/inactive section */
                mainPreloader: true,

                isActiveHospitalActivity: false,
                isActivePatientStatistics: false,
                isActivedDefaultInfo: false,
                isActiveClientSources: false,
                /* end */

                value: 'this_week',
                hospital: {},
                realEstateData: {
                    defaultData: [],
                    activitiesByType: { labels: [], dataSet: [], chartElement: [] },
                    latestNegotiations: { data: [] },
                    topSellers: { data: [] },
                    salesOverTime: { labels: [], chartData: [], totalSales: 0, totalCaptaciones: 0 }
                },
                clientSources: {
                    labels: [],
                    data: [],
                    colors: [],
                    total: 0,
                },

                // Date filters
                filters: {
                    startDate: null,
                    endDate: null
                },
            }
        },
        created() {
            this.initializeDates();
            this.loadRealEstateData();
        },
        methods: {
            initializeDates() {
                const endDate = new Date();
                const startDate = new Date();
                startDate.setDate(startDate.getDate() - 30);

                this.filters.endDate = this.formatDateForInput(endDate);
                this.filters.startDate = this.formatDateForInput(startDate);
            },
            formatDateForInput(date) {
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                return `${year}-${month}-${day}`;
            },
            applyFilters() {
                this.mainPreloader = true;
                this.loadRealEstateData();
            },
            resetFilters() {
                this.initializeDates();
                this.mainPreloader = true;
                this.loadRealEstateData();
            },
            loadRealEstateData() {
                let url = actions.REAL_ESTATE_DASHBOARD;
                
                let reqData = {
                    params: {
                        start_date: this.filters.startDate,
                        end_date: this.filters.endDate
                    }
                };

                this.isActivedDefaultInfo = false;
                this.isActiveHospitalActivity = false;
                this.isActivePatientStatistics = false;
                this.isActiveClientSources = false;

                this.axiosGet(url, reqData).then(response => {
                    this.realEstateData = response.data;

                    // Activar banderas de visualización
                    this.isActivedDefaultInfo = true;
                    this.isActiveHospitalActivity = true;
                    this.isActivePatientStatistics = true;

                }).catch((error) => {
                    console.error('Error cargando datos del dashboard:', error);
                }).finally(() => {
                    this.mainPreloader = false;
                    this.countCreatedResponse = 3;
                });

                this.axiosGet('clients-by-source', reqData).then(response => {
                    this.clientSources = response.data;
                    this.isActiveClientSources = true;
                }).catch((error) => {
                    console.error('Error cargando fuentes de clientes:', error);
                });
            },
            numberFormat(value) {
                return numberFormatter(value);
            }
        }
    }
</script>