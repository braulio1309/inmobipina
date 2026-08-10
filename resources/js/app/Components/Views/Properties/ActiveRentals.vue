<template>
    <div class="content-wrapper">
        <div class="row">
            <div class="col-sm-12 col-md-6">
                <app-breadcrumb :page-title="'Alquileres Activos'" :directory="$t('datatables')" :icon="'home'"/>
            </div>
        </div>

        <div v-if="showStatusModal" class="modal-backdrop" style="position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,.5);z-index:1050;display:flex;align-items:center;justify-content:center;">
            <div class="card p-4" style="min-width:320px;z-index:1060;">
                <h6 class="mb-3">Cambiar estatus</h6>
                <p class="mb-2 text-muted">{{ selectedRow ? selectedRow.property_title : '' }}</p>
                <div class="mb-3">
                    <select v-model="newStatus" class="form-control">
                        <option value="Disponible">Disponible</option>
                        <option value="Alquilado">Alquilado</option>
                        <option value="No disponible">No disponible</option>
                    </select>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-primary btn-sm" @click="applyStatusChange">Guardar</button>
                    <button class="btn btn-secondary btn-sm" @click="showStatusModal = false">Cancelar</button>
                </div>
            </div>
        </div>

        <div class="mb-primary col-12 col-sm-12 col-md-12 col-lg-12 col-xl-12">
            <app-table :id="'active-rentals-table'" :options="options" @action="getAction"/>
        </div>
    </div>
</template>

<script>
import axios from "axios";
import {TableHelpers} from "../Demo/Tables/mixins/TableHelpers";
import CoreLibrary from "../../../../../js/core/helpers/CoreLibrary";

export default {
    name: "ActiveRentalsProperties",
    mixins: [TableHelpers],
    extends: CoreLibrary,
    data() {
        return {
            showStatusModal: false,
            selectedRow: null,
            newStatus: 'Disponible',
            options: {
                name: this.$t('default_filter'),
                url: 'operations/active-rentals',
                showHeader: true,
                showCount: true,
                showClearFilter: true,
                columns: [
                    {
                        title: 'Título',
                        type: 'text',
                        key: 'property_title',
                        default: '',
                        isVisible: true,
                    },
                    {
                        title: 'Ubicación',
                        type: 'text',
                        key: 'property_address',
                        default: '',
                        isVisible: true,
                    },
                    {
                        title: 'Precio de la operación',
                        type: 'custom-html',
                        key: 'operation_amount',
                        default: '',
                        isVisible: true,
                        modifier: (value) => {
                            const amount = parseFloat(value) || 0;
                            return '$' + amount.toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                        }
                    },
                    {
                        title: 'Propietario',
                        type: 'text',
                        key: 'owner_name',
                        default: '',
                        isVisible: true,
                    },
                    {
                        title: 'Comprador',
                        type: 'text',
                        key: 'buyer_name',
                        default: '',
                        isVisible: true,
                    },
                    {
                        title: 'Asesor encargado',
                        type: 'text',
                        key: 'operation_advisor_name',
                        default: '',
                        isVisible: true,
                    },
                    {
                        title: 'Forma de pago',
                        type: 'text',
                        key: 'payment_frequency',
                        default: '',
                        isVisible: true,
                    },
                    {
                        title: 'Siguiente fecha corte',
                        type: 'text',
                        key: 'next_cutoff_date',
                        default: '',
                        isVisible: true,
                        modifier: (value) => this.formatDate(value),
                    },
                    {
                        title: 'Fecha final',
                        type: 'text',
                        key: 'final_date',
                        default: '',
                        isVisible: true,
                        modifier: (value) => this.formatDate(value),
                    },
                    {
                        title: 'Acciones',
                        type: 'action',
                        key: 'id',
                        default: '',
                        isVisible: true,
                    },
                ],
                filters: [],
                paginationType: 'pagination',
                responsive: true,
                rowLimit: 50,
                orderBy: 'desc',
                showAction: true,
                showActionColumn: true,
                actions: [
                    { title: 'Cambiar estatus', type: 'none' },
                ],
            },
        };
    },
    methods: {
        formatDate(value) {
            if (!value) {
                return 'Sin fecha';
            }

            try {
                return new Date(value).toLocaleDateString('es-VE', {
                    year: 'numeric',
                    month: '2-digit',
                    day: '2-digit',
                });
            } catch (error) {
                return value;
            }
        },
        getAction(rowData, actionObj) {
            if (actionObj.title === 'Cambiar estatus') {
                this.selectedRow = rowData;
                this.newStatus = rowData.property_status || 'Disponible';
                this.showStatusModal = true;
            }
        },
        async applyStatusChange() {
            if (!this.selectedRow) {
                return;
            }

            try {
                await axios.patch(`/property/${this.selectedRow.property_id || this.selectedRow.id}/status`, { status: this.newStatus });
                this.$toastr.s('Estatus actualizado correctamente');
                this.showStatusModal = false;
                this.$hub.$emit('reload-active-rentals-table');
            } catch (error) {
                console.error(error);
                this.$toastr.e(error.response?.data?.message || 'No se pudo actualizar el estatus');
            }
        },
    },
};
</script>