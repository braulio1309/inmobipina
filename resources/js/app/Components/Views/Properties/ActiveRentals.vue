<template>
    <div class="content-wrapper">
        <div class="row">
            <div class="col-sm-12 col-md-6">
                <app-breadcrumb :page-title="'Alquileres Activos'" :directory="$t('datatables')" :icon="'home'"/>
            </div>
        </div>

        <div v-if="showStatusModal" class="modal-backdrop" style="position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,.5);z-index:1050;display:flex;align-items:center;justify-content:center;">
            <div class="card p-4" style="min-width:360px;z-index:1060;">
                <h6 class="mb-3">Estado del pago del alquiler</h6>
                <p class="mb-2 text-muted">{{ selectedRow ? selectedRow.property_title : '' }}</p>
                <div class="mb-3">
                    <div class="d-flex flex-column gap-2">
                        <button class="btn btn-success btn-sm" @click="markPaymentStatus('on_time')">Marcar pago a tiempo</button>
                        <button class="btn btn-warning btn-sm" @click="markPaymentStatus('late')">Marcar atrasado</button>
                        <button class="btn btn-secondary btn-sm" @click="markPaymentStatus('pending')">Marcar pendiente</button>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small">Nota</label>
                    <textarea v-model="paymentNote" class="form-control" rows="3" placeholder="Agrega un detalle del estado del pago"></textarea>
                </div>
                <div class="mb-3" v-if="paymentHistory.length">
                    <h6 class="small mb-2">Historial</h6>
                    <ul class="pl-3 mb-0 small">
                        <li v-for="item in paymentHistory" :key="item.id">
                            {{ paymentStatusLabel(item.status) }} - {{ formatDate(item.created_at) }}
                            <div v-if="item.note" class="text-muted">{{ item.note }}</div>
                        </li>
                    </ul>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-secondary btn-sm" @click="showStatusModal = false">Cerrar</button>
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
            paymentNote: '',
            paymentHistory: [],
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
                        title: 'Estado del pago',
                        type: 'custom-html',
                        key: 'payment_status_label',
                        default: '',
                        isVisible: true,
                        modifier: (value) => {
                            const label = value || 'Sin registro';
                            const className = label === 'Pagó a tiempo' ? 'badge-success' : (label === 'Atrasado' ? 'badge-warning' : (label === 'Hoy toca pagar' ? 'badge-danger' : (label === 'Fecha de pago' ? 'badge-info' : 'badge-secondary')));
                            return `<span class="badge badge-pill ${className}">${label}</span>`;
                        },
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
                this.paymentNote = '';
                this.paymentHistory = [];
                this.showStatusModal = true;
                this.loadPaymentHistory(rowData.id);
            }
        },
        async loadPaymentHistory(operationId) {
            try {
                const response = await axios.get(`/operations/${operationId}/payment-history`);
                this.paymentHistory = Array.isArray(response.data) ? response.data : [];
            } catch (error) {
                console.error(error);
            }
        },
        paymentStatusLabel(status) {
            switch (status) {
                case 'on_time':
                    return 'Pagó a tiempo';
                case 'late':
                    return 'Atrasado';
                case 'pending':
                    return 'Pendiente';
                default:
                    return 'Sin registro';
            }
        },
        async markPaymentStatus(status) {
            if (!this.selectedRow) {
                return;
            }

            try {
                const response = await axios.post(`/operations/${this.selectedRow.id}/payment-status`, {
                    status,
                    note: this.paymentNote,
                });
                this.$toastr.s(response.data.message || 'Estado del pago actualizado');
                this.paymentNote = '';
                this.loadPaymentHistory(this.selectedRow.id);
                this.$hub.$emit('reload-active-rentals-table');
            } catch (error) {
                console.error(error);
                this.$toastr.e(error.response?.data?.message || 'No se pudo actualizar el estado del pago');
            }
        },
    },
};
</script>