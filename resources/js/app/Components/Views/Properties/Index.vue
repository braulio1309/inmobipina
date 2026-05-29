<template>
    <div class="content-wrapper">
        <div class="row">
            <div class="col-sm-12 col-md-6">
                <app-breadcrumb :page-title="'Listado de Propiedades'" :directory="$t('datatables')" :icon="'grid'"/>
            </div>
            <div class="col-sm-12 col-md-6 breadcrumb-side-button">
                <div class="float-md-right mb-3 mb-sm-3 mb-md-0 d-flex gap-2">
                    <button type="button"
                            class="btn btn-success btn-with-shadow mr-2"
                            @click="exportProperties">
                        <i class="fas fa-file-excel mr-1"></i> Exportar Excel
                    </button>
                    <button type="button"
                            class="btn btn-primary btn-with-shadow"
                            @click="goToCreateProperty">
                        Registrar propiedad
                    </button>
                </div>
            </div>
        </div>
        <div class="mb-primary col-12 col-sm-12 col-md-12 col-lg-12 col-xl-12">
            <app-table :id="'default-filter-table'" :options="options" @action="handleAction"/>
        </div>

        <div v-if="showDetailModal" class="modal-backdrop" style="position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,.5);z-index:1050;display:flex;align-items:center;justify-content:center;">
            <div class="card shadow" style="width:min(760px, 92vw); max-height:85vh; overflow:auto; z-index:1060;">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">Detalle de la propiedad</h5>
                        <small class="text-muted" v-if="selectedPropertySummary">{{ selectedPropertySummary.title }}</small>
                    </div>
                    <button class="btn btn-sm btn-outline-secondary" @click="closeDetailModal">Cerrar</button>
                </div>
                <div class="card-body">
                    <div v-if="detailLoading" class="text-center py-4 text-muted">Cargando detalle...</div>

                    <div v-else-if="selectedPropertySummary">
                        <div class="row mb-4">
                            <div class="col-md-6 mb-3">
                                <div class="border rounded p-3 h-100 bg-light">
                                    <div class="text-muted small">Dirección</div>
                                    <div class="font-weight-bold">{{ selectedPropertySummary.address || 'Sin dirección' }}</div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="border rounded p-3 h-100 bg-light">
                                    <div class="text-muted small">Demostraciones</div>
                                    <div class="h4 mb-0">{{ selectedPropertySummary.demonstrations_count }}</div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="border rounded p-3 h-100 bg-light">
                                    <div class="text-muted small">Publicidades</div>
                                    <div class="h4 mb-0">{{ selectedPropertySummary.advertisements_count }}</div>
                                </div>
                            </div>
                        </div>

                        <h6 class="mb-3">Actividades recientes</h6>
                        <div v-if="selectedPropertySummary.activities && selectedPropertySummary.activities.length">
                            <div
                                v-for="activity in selectedPropertySummary.activities"
                                :key="activity.id"
                                class="border rounded p-3 mb-2"
                            >
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="font-weight-bold">{{ activity.user_name }}</div>
                                        <div class="text-muted small text-capitalize">{{ activity.type || 'Actividad' }}</div>
                                    </div>
                                    <small class="text-muted">{{ formatActivityDate(activity.date) }}</small>
                                </div>
                                <div v-if="activity.description" class="mt-2 text-muted small">{{ activity.description }}</div>
                            </div>
                        </div>
                        <div v-else class="text-muted">Esta propiedad no tiene actividades registradas.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>

    import {TableHelpers} from "../Demo/Tables/mixins/TableHelpers";
    import CoreLibrary from "../../../../../js/core/helpers/CoreLibrary";

    export default {
        name: "Properties",
        mixins: [TableHelpers],
        extends: CoreLibrary,
        data() {
            return {
                showDetailModal: false,
                detailLoading: false,
                selectedPropertySummary: null,
                options: {
                    name: this.$t('default_filter'),
                    url: 'property/listar',
                    showHeader: true,
                    showCount: true,
                    showClearFilter: true,
                    columns: [
                        {
                            title: 'Título',
                            type: 'text',
                            key: 'title',
                            default: "",
                            isVisible: true,
                            modifier:(value, row)=>{
                                return row.title ? row.title : '';
                            }
                        },
                        {
                            title: 'Dirección',
                            type: 'text',
                            key: 'address',
                            default: "",
                            isVisible: true,
                            modifier:(value, row)=>{
                                return row.address;
                            }
                        },
                        {
                            title: 'Asesor responsable',
                            type: 'text',
                            key: 'advisor_name',
                            default: "",
                            isVisible: true,
                            modifier: (value, row) => {
                                const advisor = row.agent || row.creator;

                                if (!advisor) {
                                    return 'Sin asesor';
                                }

                                return [advisor.first_name, advisor.last_name].filter(Boolean).join(' ').trim() || 'Sin asesor';
                            }
                        },
                        {
                            title: 'Tipo',
                            type: 'custom-html',
                            key: 'type',
                            default: "",
                            isVisible: true,
                            modifier:(value, row)=>{
                                let clase = 'primary';
                                if (value == 'Casa')
                                    clase = 'warning'
                                if (value == 'Terreno')
                                    clase = 'success'
                                if (value == 'Galpon')
                                    clase = 'info'
                                if (value == 'Local')
                                    clase = 'dark'
                                return `<span class="badge badge-sm badge-pill badge-${clase}">${row.type}</span>`;
                            }
                        },

                        
                        {
                            title: 'Metros²',
                            type: 'custom-html',
                            key: 'square_meters',
                            default: "",
                            isVisible: true,
                            modifier:(value, row)=>{
                                return row.square_meters+' mt2';
                            }
                        },
                        {
                            title: 'Precio',
                            type: 'custom-html',
                            key: 'price',
                            default: "",
                            isVisible: true,
                            modifier:(value, row)=>{
                                const num = parseFloat(row.price) || 0;
                                return '$' + num.toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                            }
                        },
                        {
                            title: 'Tipo de Venta',
                            type: 'custom-html',
                            key: 'type_sale',
                            default: "",
                            isVisible: true,
                            modifier:(value, row)=>{
                                let clase = 'primary'
                                if (value == 'alquiler')
                                    clase = 'info'
                                if (value == 'venta')
                                    clase = 'success'
                                
                                return `<span class="badge badge-sm badge-pill badge-${clase}">${row.type_sale}</span>`;
                            }
                        },
                        {
                            title: 'Estatus',
                            type: 'custom-html',
                            key: 'status',
                            default: "",
                            isVisible: true,
                            modifier:(value, row)=>{
                                let clase = 'primary'
                                if (value == 'pending')
                                    clase = 'warning'
                                if (value == 'No disponible')
                                    clase = 'danger'
                                if (value == 'Vendido')
                                    clase = 'success'
                                if (value == 'Alquilado')
                                    clase = 'info'
                                
                                return `<span class="badge badge-sm badge-pill badge-${clase}">${row.status}</span>`;
                            }
                        },
                        {
                            title: this.$t('action'),
                            type: 'action',
                            key: 'invoice',
                            isVisible: true
                        },
                        
                    ],
                    filters: [
                        {
                            "title": 'Fecha',
                            "type": "range-picker",
                            "key": "date",
                            "option": ["today", "thisMonth", "last7Days", "lastYear"]
                        },
                        {
                            "title": 'Rango de Precio (USD)',
                            "type": "range-filter",
                            "key": "price",
                            "minRange": 0,
                            "maxRange": 2000000,
                            "minTitle": "Mínimo",
                            "maxTitle": "Máximo",
                            "sign": "$",
                        },
                        {
                            "title": 'Tipo de Inmueble',
                            "type": "checkbox",
                            "key": "type",
                            "option": [
                                {id: 'Apartamento', value: 'Apartamento'},
                                {id: 'Casa', value: 'Casa'},
                                {id: 'Terreno', value: 'Terreno'},
                                {id: 'Galpon', value: 'Galpon'},
                                {id: 'Local', value: 'Local'},
                                {id: 'Townhouse', value: 'Townhouse'},
                                {id: 'Oficina', value: 'Oficina'},
                                {id: 'Finca', value: 'Finca'},
                                {id: 'Consultorios', value: 'Consultorios'},
                            ],
                        },
                        {
                            "title": 'Tipo de Oferta',
                            "type": "checkbox",
                            "key": "type_sale",
                            "option": [
                                {id: 'venta', value: 'Venta'},
                                {id: 'alquiler', value: 'Alquiler'},
                                {id: 'ambos', value: 'Ambos'},
                            ],
                        },
                        {
                            "title": 'Estatus',
                            "type": "checkbox",
                            "key": "status",
                            "option": [
                                {id: 'pending', value: 'Pendiente'},
                                {id: 'Disponible', value: 'Disponible'},
                                {id: 'No disponible', value: 'No disponible'},
                                {id: 'Reservado', value: 'Reservado'},
                                {id: 'Vendido', value: 'Vendido'},
                                {id: 'Alquilado', value: 'Alquilado'},
                            ],
                        },
                       
                    ],
                    paginationType: "pagination",
                    responsive: true,
                    rowLimit: 50,
                    orderBy: 'desc',
                    showAction: true,
                    actions: [
                        { title: 'Detalle', type: 'none' },
                        { title: 'Editar', type: 'none' },
                        { title: 'Compartir', type: 'none', modifier: (row) => !!row.approved_by || row.status === 'Disponible' },
                    ],
                },
            }
        },
        created() {
            //this.options.columns = [...this.tableColumns];
            if (this.$isAdmin()) {
                this.options.actions.push(
                    { title: 'Aprobar', type: 'none', modifier: (row) => row.status === 'pending' },
                    { title: 'Rechazar', type: 'none', modifier: (row) => row.status === 'pending' }
                );
            }
            this.searchAndSelectFilterOptions();
        },
        methods: {
            formatActivityDate(date) {
                if (!date) {
                    return 'Sin fecha';
                }

                try {
                    return new Date(date).toLocaleString('es-VE', { dateStyle: 'medium', timeStyle: 'short' });
                } catch (error) {
                    return date;
                }
            },
            closeDetailModal() {
                this.showDetailModal = false;
                this.selectedPropertySummary = null;
                this.detailLoading = false;
            },
            async openDetailModal(propertyId) {
                this.showDetailModal = true;
                this.detailLoading = true;
                this.selectedPropertySummary = null;

                try {
                    const response = await this.axiosGet(`property/${propertyId}/summary`);
                    this.selectedPropertySummary = response.data;
                } catch (error) {
                    this.$toastr.e(error.response?.data?.message || 'No se pudo cargar el detalle de la propiedad');
                    this.closeDetailModal();
                } finally {
                    this.detailLoading = false;
                }
            },
            goToCreateProperty() {
                window.location.href = '/properties/create';
            },
            exportProperties() {
                const urlParams = new URLSearchParams(window.location.search);
                const exportUrl = '/property/export?' + urlParams.toString();
                window.open(exportUrl, '_blank');
            },
            handleAction(rowData, actionObj) {
                if (actionObj.title === 'Detalle') {
                    this.openDetailModal(rowData.id);
                } else if (actionObj.title === 'Editar') {
                    window.location.href = `/properties/create?id=${rowData.id}`;
                } else if (actionObj.title === 'Compartir') {
                    const shareUrl = `${window.location.origin}/property/share/${rowData.id}`;
                    window.open(shareUrl, '_blank');
                } else if (actionObj.title === 'Aprobar') {
                    this.axiosPatch({
                        url: `property/${rowData.id}/approve`,
                        data: { action: 'approve' }
                    }).then(res => {
                        this.$toastr.s(res.data.message || 'Propiedad aprobada.');
                        this.$hub.$emit('reload-default-filter-table');
                    }).catch(err => {
                        this.$toastr.e(err.response?.data?.message || 'Error al aprobar.');
                    });
                } else if (actionObj.title === 'Rechazar') {
                    this.axiosPatch({
                        url: `property/${rowData.id}/approve`,
                        data: { action: 'reject' }
                    }).then(res => {
                        this.$toastr.s(res.data.message || 'Propiedad rechazada.');
                        this.$hub.$emit('reload-default-filter-table');
                    }).catch(err => {
                        this.$toastr.e(err.response?.data?.message || 'Error al rechazar.');
                    });
                }
            },
            searchAndSelectFilterOptions() {
                this.axiosGet("admin/auth/users")
                .then(response => {
                    console.log(response.data)
                    this.options.filters.push({
                        title: 'Asesores',
                        type: 'drop-down-filter',
                        key: 'asesor',
                        option: response.data.map(asesor => ({
                            id: asesor.id,
                            value: asesor.name
                        }))
                    });
                });
            }
        }
    }
</script>
