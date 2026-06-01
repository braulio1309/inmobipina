<template>
    <div class="content-wrapper">
        <div class="row">
            <div class="col-sm-12 col-md-6">
                <app-breadcrumb :page-title="'Listado de Clientes'" :directory="$t('datatables')" :icon="'grid'"/>
            </div>
            <div class="col-sm-12 col-md-6 breadcrumb-side-button">
                <div class="float-md-right mb-3 mb-sm-3 mb-md-0">
                    <button type="button" class="btn btn-primary btn-with-shadow mr-2" @click="showImportModal = true">
                        <i class="fas fa-file-import mr-1"></i> Importar Excel
                    </button>
                    <button type="button" class="btn btn-success btn-with-shadow" @click="exportClients">
                        <i class="fas fa-file-excel mr-1"></i> Exportar Excel
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal importar clientes -->
        <div v-if="showImportModal" class="modal-backdrop" style="position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,.5);z-index:1050;display:flex;align-items:center;justify-content:center;">
            <div class="card p-4" style="min-width:360px;max-width:520px;z-index:1060;">
                <h6 class="mb-3">Importar clientes desde Excel</h6>
                <p class="text-muted small mb-3">
                    El archivo debe tener las columnas en la primera fila:<br>
                    <strong>FECHA, NOMBRE DEL CLIENTE, UBICACION, TIPO DE INMUEBLE, TIPO DE NEG, TELEFONO, MEDIO, AS. ASIGNADO</strong>
                </p>
                <div class="mb-3">
                    <input type="file" class="form-control" ref="importFileInput" accept=".xlsx,.xls,.csv">
                </div>
                <div v-if="importResult" class="mb-3">
                    <div :class="importResult.errors && importResult.errors.length ? 'alert alert-warning' : 'alert alert-success'" class="py-2 px-3 small">
                        {{ importResult.message }}
                        <ul v-if="importResult.errors && importResult.errors.length" class="mt-1 mb-0 pl-3">
                            <li v-for="(err, i) in importResult.errors" :key="i">{{ err }}</li>
                        </ul>
                    </div>
                </div>
                <div class="d-flex">
                    <button class="btn btn-primary btn-sm mr-2" @click="runImport" :disabled="importing">
                        <span v-if="importing"><i class="fas fa-spinner fa-spin mr-1"></i>Importando...</span>
                        <span v-else><i class="fas fa-upload mr-1"></i>Importar</span>
                    </button>
                    <button class="btn btn-secondary btn-sm" @click="closeImportModal">Cerrar</button>
                </div>
            </div>
        </div>

        <!-- Modal cambio de estatus -->
        <div v-if="showStatusModal" class="modal-backdrop" style="position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,.5);z-index:1050;display:flex;align-items:center;justify-content:center;">
            <div class="card p-4" style="min-width:320px;z-index:1060;">
                <h6 class="mb-3">Cambiar estatus del cliente</h6>
                <p class="mb-2 text-muted">{{ selectedClient ? selectedClient.name : '' }}</p>
                <div class="mb-3">
                    <app-input
                        type="select"
                        v-model="newStatus"
                        :list="statusOptions"
                    />
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-primary btn-sm" @click="applyStatusChange">Guardar</button>
                    <button class="btn btn-secondary btn-sm" @click="showStatusModal = false">Cancelar</button>
                </div>
            </div>
        </div>

        <div class="mb-primary col-12 col-sm-12 col-md-12 col-lg-12 col-xl-12">
            <app-table :id="'default-filter-table'" :options="options" @action="getAction"/>
        </div>
    </div>
</template>

<script>

    import * as actions from "../../../../../js/app/Config/ApiUrl";
    import {TableHelpers} from "../Demo/Tables/mixins/TableHelpers";
    import CoreLibrary from "../../../../../js/core/helpers/CoreLibrary";
    import axios from "axios";

    export default {
        name: "Clients",
        mixins: [TableHelpers],
        extends: CoreLibrary,
        data() {
            return {
                showStatusModal: false,
                selectedClient: null,
                newStatus: 'potencial',
                showImportModal: false,
                importing: false,
                importResult: null,
                statusOptions: [
                    { id: "potencial", value: "Potencial" },
                    { id: "no potencial", value: "No potencial" },
                    { id: "atendido", value: "Atendido" },
                    { id: "cerrado", value: "Cerrado" },
                ],
                options: {
                    name: this.$t('default_filter'),
                    url: 'client/listar',
                    showHeader: true,
                    showCount: true,
                    showClearFilter: true,
                    columns: [
                        {
                            title: '#',
                            type: 'text',
                            key: 'id',
                            default: "",
                            isVisible: true,
                            modifier:(value, row)=>{
                                return row.id ? row.id : '';
                            }
                        },
                        {
                            title: 'Nombre',
                            type: 'text',
                            key: 'name',
                            default: "",
                            isVisible: true,
                            modifier: (value, row) => {
                                return row.display_name || value || row.name || 'Sin nombre';
                            }
                        },
                        {
                            title: 'Correo',
                            type: 'text',
                            key: 'email',
                            default: "",
                            isVisible: true,
                            modifier: (value, row) => {
                                return value
                            }
                        },
                        {
                            title: 'Teléfono',
                            type: 'text',
                            key: 'phone',
                            default: "",
                            isVisible: true,
                            modifier: (value, row) => {
                                return value
                            }
                        },
                        {
                            title: 'Fecha',
                            type: 'object',
                            key: 'date',
                            default: "",
                            isVisible: true,
                            modifier: (value) => {
                                if (!value) {
                                    return '—';
                                }

                                return String(value).split('T')[0].split(' ')[0];
                            }
                        },
                        {
                            title: 'Medio',
                            type: 'text',
                            key: 'source',
                            default: "",
                            isVisible: true,
                            modifier: (value, row) => {
                                const map = { telefono: 'Teléfono', instagram: 'Instagram', tu_inmueble: 'Tu Inmueble', pendon: 'Pendón' };
                                return map[value] || value || '—';
                            }
                        },
                        {
                            title: 'Tipo de negociación',
                            type: 'text',
                            key: 'tipo_neg',
                            default: "",
                            isVisible: true,
                            modifier: (value) => {
                                return value || '—';
                            }
                        },
                        {
                            title: 'Asesor',
                            type: 'text',
                            key: 'advisor_name',
                            default: "",
                            isVisible: true,
                            modifier: (value, row) => {
                                return value || '—';
                            }
                        },
                        {
                            title: 'Estatus',
                            type: 'custom-html',
                            key: 'status',
                            default: "",
                            isVisible: true,
                            modifier: (value, row) => {
                                const map = {
                                    potencial: 'primary',
                                    'no potencial': 'secondary',
                                    atendido: 'warning',
                                    cerrado: 'success',
                                };
                                const cls = map[value] || 'secondary';
                                const label = value ? value.charAt(0).toUpperCase() + value.slice(1) : '—';
                                return `<span class="badge badge-sm badge-pill badge-${cls}">${label}</span>`;
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
                            "title": 'Estatus',
                            "type": "drop-down-filter",
                            "key": "status",
                            "option": [
                                { id: "potencial", value: "Potencial" },
                                { id: "no potencial", value: "No potencial" },
                                { id: "atendido", value: "Atendido" },
                                { id: "cerrado", value: "Cerrado" },
                            ]
                        },
                    ],
                    paginationType: "pagination",
                    responsive: true,
                    rowLimit: 50,
                    orderBy: 'desc',
                    showAction: true,
                    actions: [
                        { title: 'Editar', type: 'none' },
                        { title: 'Cambiar estatus', type: 'none' },
                    ],
                },
            }
        },
        created() {
            this.searchAndSelectFilterOptions();
        },
        methods: {
            searchAndSelectFilterOptions() {
                this.options.filters = this.options.filters.filter(f => f.key !== 'asesor');
                this.axiosGet("admin/auth/users")
                .then(response => {
                    const users = Array.isArray(response.data) ? response.data : (response.data.data || []);
                    this.options.filters.push({
                        title: 'Asesores',
                        type: 'drop-down-filter',
                        key: 'asesor',
                        option: users.map(u => ({
                            id: u.id,
                            value: u.first_name
                                ? (u.first_name + ' ' + (u.last_name || '')).trim()
                                : (u.name || u.value || 'Sin nombre'),
                        }))
                    });
                });
            },
            closeImportModal() {
                this.showImportModal = false;
                this.importResult = null;
                if (this.$refs.importFileInput) {
                    this.$refs.importFileInput.value = '';
                }
            },
            async runImport() {
                const fileInput = this.$refs.importFileInput;
                if (!fileInput || !fileInput.files || !fileInput.files[0]) {
                    this.$toastr.e('Selecciona un archivo Excel primero.');
                    return;
                }
                this.importing = true;
                this.importResult = null;
                try {
                    const formData = new FormData();
                    formData.append('file', fileInput.files[0]);
                    const response = await axios.post('/client/import', formData, {
                        headers: { 'Content-Type': 'multipart/form-data' }
                    });
                    this.importResult = response.data;
                    this.$hub.$emit('reload-default-filter-table');
                } catch (error) {
                    const msg = error.response?.data?.message || 'Error al importar el archivo.';
                    this.importResult = { message: msg, errors: [] };
                    this.$toastr.e(msg);
                } finally {
                    this.importing = false;
                }
            },
            exportClients() {
                const urlParams = new URLSearchParams(window.location.search);
                const exportUrl = '/client/export?' + urlParams.toString();
                window.open(exportUrl, '_blank');
            },
            getAction(rowData, actionObj) {
                if (actionObj.title === 'Editar') {
                    window.location.href = `/clients/create?id=${rowData.id}`;
                } else if (actionObj.title === 'Cambiar estatus') {
                    this.selectedClient = rowData;
                    this.newStatus = rowData.status || 'potencial';
                    this.showStatusModal = true;
                }
            },
            async applyStatusChange() {
                try {
                    await axios.patch(`/client/${this.selectedClient.id}/status`, { status: this.newStatus });
                    this.$toastr.s('Estatus actualizado correctamente');
                    this.showStatusModal = false;
                    this.$hub.$emit('reload-default-filter-table');
                } catch (error) {
                    this.$toastr.e('Error al cambiar el estatus');
                    console.error(error);
                }
            }
        }
    }
</script>
