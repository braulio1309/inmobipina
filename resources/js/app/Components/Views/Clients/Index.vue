<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="'Listado de Clientes'" :directory="$t('datatables')" :icon="'grid'"/>

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
                                return row.name
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
                    filters: [],
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
                this.axiosGet(actions.DATATABLE_SEARCH_SELECT).then(response => {
                    this.options.filters.push({
                        "title": 'Asesores',
                        "type": "drop-down-filter",
                        "key": "Propiedades por asesor",
                        "option": response.data.map(name => ({
                            id: name.name,
                            value: name.name
                        }))
                    });
                });
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
