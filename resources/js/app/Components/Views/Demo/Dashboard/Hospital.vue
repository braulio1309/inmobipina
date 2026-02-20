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
            <app-breadcrumb :page-title="$t('hospital')" :directory="$t('dashboard')" :icon="'pie-chart'"/>

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
                <div class="col-12">
                    <div class="card card-with-shadow border-0">
                        <div class="card-header d-flex align-items-center justify-content-between p-primary primary-card-color">
                            <h5 class="card-title d-inline-block mb-0">{{ 'Mapa de Propiedades' }}</h5>
                        </div>
                        <div class="card-body p-0">
                            <Map :token="googleMapsToken" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- TABLAS CORREGIDAS -->
            <div class="row" v-if="!mainPreloader">
                <div class="col-12 col-sm-12 col-md-6 mb-4 mb-md-0">
                    <div class="card card-with-shadow border-0">
                        <div class="card-header d-flex align-items-center justify-content-between p-primary primary-card-color">
                            <h5 class="card-title d-inline-block mb-0">{{ 'Últimas Ventas' }}</h5>
                        </div>
                        <div class="card-body p-0">
                            <app-table :id="'latest-sales-table'" :options="latestSalesList"/>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-12 col-md-6">
                    <div class="card card-with-shadow border-0">
                        <div class="card-header d-flex align-items-center justify-content-between p-primary primary-card-color">
                            <h5 class="card-title d-inline-block mb-0">{{ 'Top Vendedores' }}</h5>
                        </div>
                        <div class="card-body p-0">
                            <app-table :id="'top-sellers-table'" :options="topSellersList"/>
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
    import Map from '../Pages/map/Map.vue';
    

    export default {
        name: "Hospital",
        mixins: [FormMixin, TableHelpers],
        components: {
            Map
        },

        data() {
            return {
                /* Loader section and active/inactive section */
                mainPreloader: true,

                isActiveHospitalActivity: false,
                isActivePatientStatistics: false,
                isActivedDefaultInfo: false,
                /* end */

                value: 'this_week',
                hospital: {},
                realEstateData: {
                    defaultData: [],
                    activitiesByType: { labels: [], dataSet: [], chartElement: [] },
                    latestSales: { data: [] },
                    topSellers: { data: [] },
                    salesOverTime: { labels: [], chartData: [], totalSales: 0, totalCaptaciones: 0 }
                },

                // Date filters
                filters: {
                    startDate: null,
                    endDate: null
                },

                // Google Maps Token
                googleMapsToken: process.env.MIX_GOOGLE_MAPS_API_KEY || null,

                /*Latest Sales list*/
                latestSalesList: {
                    name: 'Latest Sales List',
                    url: null,
                    datatableWrapper: false,
                    showHeader: true,
                    tableShadow: false,
                    managePagination: false,
                    columns: [
                        { 
                            title: 'Propiedad', 
                            type: 'text', 
                            key: 'property', 
                            isVisible: true 
                        },
                        { 
                            title: 'Comprador', 
                            type: 'text', 
                            key: 'buyer', 
                            isVisible: true 
                        },
                        { 
                            title: 'Monto', 
                            type: 'text', 
                            key: 'amount', 
                            isVisible: true,
                            modifier: (value) => {
                                return value ? '$' + numberFormatter(value) : '$0';
                            }
                        },
                        { 
                            title: 'Fecha', 
                            type: 'text', 
                            key: 'date', 
                            isVisible: true 
                        }
                    ],
                    showFilter: false,
                    paginationType: "pagination",
                    responsive: true,
                    rowLimit: 10,
                    orderBy: 'desc',
                    showAction: false,
                    actions: [],
                    data: []
                },

                /*Top Sellers list*/
                topSellersList: {
                    name: 'Top Sellers List',
                    url: null,
                    datatableWrapper: false,
                    showHeader: true,
                    tableShadow: false,
                    managePagination: false,
                    columns: [
                        {
                            title: 'Vendedor',
                            type: 'text',
                            key: 'name',
                            isVisible: true
                        },
                        { 
                            title: 'Email', 
                            type: 'text', 
                            key: 'email', 
                            isVisible: true 
                        },
                        { 
                            title: 'Ventas', 
                            type: 'text', 
                            key: 'sales_count', 
                            isVisible: true 
                        },
                        { 
                            title: 'Total', 
                            type: 'text', 
                            key: 'total_revenue', 
                            isVisible: true,
                            modifier: (value) => {
                                return value ? '$' + numberFormatter(value) : '$0';
                            }
                        }
                    ],
                    showFilter: false,
                    paginationType: "pagination",
                    responsive: true,
                    rowLimit: 10,
                    orderBy: 'desc',
                    showAction: false,
                    actions: [],
                    data: []
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

                this.axiosGet(url, reqData).then(response => {
                    console.log('✅ Respuesta completa de la API:', response.data);
                    
                    // Asignar datos generales
                    this.realEstateData = response.data;
                    
                    // CORRECCIÓN: Actualizar tabla de Últimas Ventas
                    if (response.data.latestSales && response.data.latestSales.data) {
                        console.log('📊 Últimas Ventas recibidas:', response.data.latestSales.data);
                        this.latestSalesList.data = response.data.latestSales.data;
                    } else {
                        console.warn('⚠️ No hay datos de ventas');
                        this.latestSalesList.data = [];
                    }
                    
                    // CORRECCIÓN: Actualizar tabla de Top Vendedores
                    if (response.data.topSellers && response.data.topSellers.data) {
                        console.log('🏆 Top Vendedores recibidos:', response.data.topSellers.data);
                        this.topSellersList.data = response.data.topSellers.data;
                    } else {
                        console.warn('⚠️ No hay datos de vendedores');
                        this.topSellersList.data = [];
                    }

                    // Forzar actualización reactiva con $set
                    this.$set(this.latestSalesList, 'data', this.latestSalesList.data);
                    this.$set(this.topSellersList, 'data', this.topSellersList.data);

                    console.log('✅ Tablas actualizadas:', {
                        ventas: this.latestSalesList.data.length,
                        vendedores: this.topSellersList.data.length
                    });

                    // Activar banderas de visualización
                    this.isActivedDefaultInfo = true;
                    this.isActiveHospitalActivity = true;
                    this.isActivePatientStatistics = true;

                }).catch((error) => {
                    console.error('❌ Error cargando datos:', error);
                    if (error.response) {
                        console.error('Detalles del error:', error.response.data);
                    }
                }).finally(() => {
                    this.mainPreloader = false;
                    this.countCreatedResponse = 3;
                });
            },
            numberFormat(value) {
                return numberFormatter(value);
            }
        }
    }
</script>