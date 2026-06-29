<template>
    <div class="content-wrapper">
        <div class="row">
            <div class="col-sm-12 col-md-6">
                <app-breadcrumb :page-title="'Historial de operaciones'" :directory="$t('datatables')" :icon="'grid'"/>
            </div>
            <div class="col-sm-12 col-md-6 breadcrumb-side-button">
                <div class="float-md-right mb-3 mb-sm-3 mb-md-0">
                    <button type="button" class="btn btn-success btn-with-shadow" @click="exportOperations">
                        <i class="fas fa-file-excel mr-1"></i> Exportar Excel
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal Confirmar Venta -->
        <div v-if="showConfirmModal" class="modal-backdrop"
             style="position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,.5);z-index:1050;display:flex;align-items:center;justify-content:center;">
            <div class="card p-4" style="min-width:480px;max-width:600px;z-index:1060;max-height:90vh;overflow-y:auto;">
                <h5 class="mb-1">Confirmar Venta</h5>
                <p class="text-muted mb-3 small">Reserva #{{ confirmData.id }} — <strong>{{ confirmData.property_title }}</strong></p>

                <!-- Desglose de montos -->
                <div class="alert alert-info p-3 mb-3" style="font-size:0.93em;">
                    <div class="mb-1">
                        <i class="fas fa-home mr-1"></i>
                        <strong>Precio total de la propiedad:</strong>
                        <span class="float-right">${{ formatAmt(confirmData.property_price) }}</span>
                    </div>
                    <div class="mb-1">
                        <i class="fas fa-calendar-check mr-1 text-warning"></i>
                        <strong>Monto abonado en reserva:</strong>
                        <span class="float-right text-warning">− ${{ formatAmt(confirmData.reservation_amount) }}</span>
                    </div>
                    <hr class="my-2">
                    <div class="fw-bold">
                        <i class="fas fa-equals mr-1 text-success"></i>
                        <strong>Monto neto de la venta:</strong>
                        <span class="float-right text-success">${{ formatAmt(confirmData.net_amount) }}</span>
                    </div>
                    <p class="text-muted mt-2 mb-0 small">
                        Las comisiones se calculan sobre el <strong>monto neto</strong> de la venta.
                        Las comisiones de la reserva ya están acumuladas automáticamente.
                    </p>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">
                        Monto neto de venta (USD)
                        <small class="text-muted ml-1">— puede ajustarse si es necesario</small>
                    </label>
                    <input
                        v-model="confirmData.amount"
                        type="number"
                        class="form-control"
                        placeholder="Monto neto de venta"
                    >
                </div>

                <div class="mb-3 border rounded p-3 bg-light">
                    <h6 class="mb-2">Comisiones sobre monto neto</h6>
                    <div class="row mb-1 align-items-center">
                        <div class="col-5"><strong>Comisión total</strong></div>
                        <div class="col-4">
                            <div class="input-group input-group-sm">
                                <input type="number" class="form-control" v-model="confirmData.total_commission_percentage" min="0" max="100" step="0.01" readonly>
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                        <div class="col-3 text-dark fw-bold">${{ formatAmt(confirmData.total_commission_amount) }}</div>
                    </div>
                    <div class="row mb-1 align-items-center">
                        <div class="col-5"><strong>Inmobiliaria</strong></div>
                        <div class="col-4">
                            <div class="input-group input-group-sm">
                                <input type="number" class="form-control" v-model="confirmData.company_commission_percentage" min="0" max="100" step="0.01" readonly>
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                        <div class="col-3 text-success fw-bold">${{ formatAmt(confirmData.company_commission_amount) }}</div>
                    </div>
                    <div v-for="sc in confirmData.sellers_commissions" :key="sc.id" class="row mb-1 align-items-center">
                        <div class="col-5">{{ sc.name }}</div>
                        <div class="col-4">
                            <div class="input-group input-group-sm">
                                <input type="number" class="form-control" v-model="sc.percentage" min="0" max="100" step="0.01" readonly>
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                        <div class="col-3 text-primary fw-bold">${{ formatAmt(sc.amount) }}</div>
                    </div>
                    <div v-if="confirmData.sellers_commissions.length === 0" class="small text-muted">
                        Solo la inmobiliaria está involucrada en esta comisión.
                    </div>
                </div>

                <div class="d-flex gap-2 mt-2">
                    <button class="btn btn-success btn-sm" @click="doConfirmSale" :disabled="confirmLoading">
                        <span v-if="confirmLoading" class="spinner-border spinner-border-sm me-1"></span>
                        Confirmar Venta
                    </button>
                    <button class="btn btn-secondary btn-sm ml-2" @click="showConfirmModal = false">Cancelar</button>
                </div>
            </div>
        </div>

        <div class="mb-primary col-12 col-sm-12 col-md-12 col-lg-12 col-xl-12">
            <app-table :id="'default-filter-table'" :options="options" @action="getAction"/>
        </div>
    </div>
</template>

<script>

    import {TableHelpers} from "../Demo/Tables/mixins/TableHelpers";
    import CoreLibrary from "../../../../../js/core/helpers/CoreLibrary";
    import axios from "axios";

    export default {
        name: "Operations",
        mixins: [TableHelpers],
        extends: CoreLibrary,
        data() {
            return {
                showConfirmModal: false,
                confirmLoading: false,
                confirmData: {
                    id: null,
                    property_title: '',
                    property_price: 0,
                    reservation_amount: 0,
                    net_amount: 0,
                    amount: '',
                    total_commission_percentage: 5,
                    total_commission_amount: 0,
                    company_commission_percentage: 5,
                    company_commission_amount: 0,
                    sellers_commissions: [],
                },
                options: {
                    name: this.$t('default_filter'),
                    url: 'operations/listar',
                    showHeader: true,
                    showCount: true,
                    showClearFilter: true,
                    columns: [
                        {
                            title: 'ID',
                            type: 'text',
                            key: 'id',
                            default: "",
                            isVisible: true,
                            modifier:(value, row)=>{
                                return row.id ? row.id : '';
                            }
                        },
                        {
                            title: 'Fecha',
                            type: 'text',
                            key: 'closing_date',
                            default: "",
                            isVisible: true,
                            modifier: (value, row) => {
                                return value || row.fecha_cierre || '—';
                            }
                        },
                        {
                            title: 'Vendedor (propietario)',
                            type: 'text',
                            key: 'owner_name',
                            default: "",
                            isVisible: true,
                            modifier: (value, row) => {
                                return value || 'Sin propietario';
                            }

                        },
                        {
                            title: 'Comprador',
                            type: 'text',
                            key: 'buyer_name',
                            default: "",
                            isVisible: true,
                            modifier: (value) => {
                                return value || 'Sin comprador';
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
                                if (value == 'reserva')
                                    clase = 'warning'
                                if (value == 'exclusividad')
                                    clase = 'success'
                                if (value == 'alquiler')
                                    clase = 'info'
                                return `<span class="badge badge-sm badge-pill badge-${clase}">${row.type}</span>`;
                            }
                        },
                        {
                            title: 'Propiedad',
                            type: 'text',
                            key: 'property_title',
                            default: "",
                            isVisible: true,
                            modifier: (value) => {
                                return value || '—';
                            }
                        },
                        {
                            title: 'Medio de captacion',
                            type: 'text',
                            key: 'client_source',
                            default: "",
                            isVisible: true,
                            modifier: (value) => {
                                return value || 'Sin especificar';
                            }
                        },
                        {
                            title: 'Precio total de la propiedad',
                            type: 'custom-html',
                            key: 'property_total_amount',
                            default: "",
                            isVisible: true,
                            modifier:(value, row) => {
                                const total = parseFloat(value || 0);
                                const totalFmt = '$' + total.toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

                                if (row.type === 'reserva') {
                                    const reserve = parseFloat(row.reservation_amount || row.amount || 0);
                                    const reserveFmt = '$' + reserve.toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                                    return `<div><strong>Total:</strong> ${totalFmt}</div><div><strong>Reserva:</strong> ${reserveFmt}</div>`;
                                }

                                return totalFmt;
                            }
                        },
                        
                        {
                            title: 'Comision total $ (%)',
                            type: 'custom-html',
                            key: 'total_commission_amount_resolved',
                            default: "",
                            isVisible: true,
                            modifier: (value, row) => {
                                const amount = parseFloat(value || 0);
                                const pct = parseFloat(row.total_commission_percentage_resolved || 0);
                                const amountFmt = '$' + amount.toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                                return `${amountFmt} (${pct.toFixed(2)}%)`;
                            }
                        },
                        {
                            title: 'Asesores y comisiones',
                            type: 'custom-html',
                            key: 'commission_details',
                            default: "",
                            isVisible: true,
                            modifier: (value) => {
                                if (!Array.isArray(value) || value.length === 0) {
                                    return 'Sin asesores';
                                }

                                const escape = (str) => String(str)
                                    .replace(/&/g, '&amp;')
                                    .replace(/</g, '&lt;')
                                    .replace(/>/g, '&gt;')
                                    .replace(/"/g, '&quot;');

                                return value.map((detail) => {
                                    const amount = parseFloat(detail.amount || 0);
                                    const percentage = parseFloat(detail.percentage || 0);
                                    const amountFmt = '$' + amount.toLocaleString('es-VE', {
                                        minimumFractionDigits: 2,
                                        maximumFractionDigits: 2,
                                    });

                                    return `<div><strong>${escape(detail.name || 'Asesor')}</strong>: ${amountFmt} (${percentage.toFixed(2)}%)</div>`;
                                }).join('');
                            }
                        },
                        {
                            title: 'Comision inmobiliaria $',
                            type: 'custom-html',
                            key: 'company_commission_amount_resolved',
                            default: "",
                            isVisible: true,
                            modifier: (value) => {
                                const amount = parseFloat(value || 0);
                                return '$' + amount.toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
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
                            "title": 'Tipo',
                            "type": "checkbox",
                            "key": "type",
                            "option": [
                                {id: 'venta', value: 'Venta'},
                                {id: 'alquiler', value: 'Alquiler'},
                                {id: 'reserva', value: 'Reserva'},
                                {id: 'exclusividad', value: 'Exclusividad'},
                                {id: 'traspaso', value: 'Traspaso'},
                            ],
                        },
                       
                    ],
                    paginationType: "pagination",
                    responsive: true,
                    rowLimit: 50,
                    orderBy: 'desc',
                    showAction: true,
                    actions: [
                        {
                            title: this.$t('edit'),
                            type: 'none',
                        },
                        {
                            title: 'Descargar Pago Comisión',
                            type: 'none',
                            modifier: (row) => ['reserva', 'venta', 'alquiler', 'traspaso'].includes(row.type),
                        },
                        {
                            title: 'Confirmar Venta',
                            type: 'none',
                            modifier: (row) => row.type === 'reserva',
                        },
                    ],
                },
            }
        },
        computed: {},
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
            formatAmt(val) {
                const num = parseFloat(val) || 0;
                return num.toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            },
            exportOperations() {
                const urlParams = new URLSearchParams(window.location.search);
                const exportUrl = '/operations/export?' + urlParams.toString();
                window.open(exportUrl, '_blank');
            },
            async openConfirmSaleModal(rowData) {
                try {
                    const res = await this.axiosGet(`/operations/${rowData.id}`);
                    const op = res.data;
                    const numSellers = (op.sellers || []).length;
                    const totalPct = parseFloat(op.total_commission_percentage ?? 5) || 0;
                    const companyPct = parseFloat(op.company_commission_percentage ?? totalPct) || 0;
                    const remainingPct = Math.max(0, totalPct - companyPct);
                    const equalPct = numSellers > 0
                        ? parseFloat((remainingPct / numSellers).toFixed(4))
                        : 0;

                    const propertyPrice    = parseFloat(op.property_price  || 0);
                    const reservationAmt   = parseFloat(op.amount          || 0);
                    const netAmount        = Math.max(0, propertyPrice - reservationAmt);

                    // Build seller commission list using names from commission_details
                    const commissionDetails = rowData.commission_details || [];
                    const sellersWithNames = (op.sellers || []).map((sellerId, idx) => {
                        const existing = (op.sellers_commissions || []).find(sc => sc.id === sellerId);
                        const detail   = commissionDetails[idx] || null;
                        return {
                            id: sellerId,
                            name: detail ? detail.name : `Asesor #${sellerId}`,
                            percentage: existing ? parseFloat(existing.percentage) : equalPct,
                            amount: existing ? parseFloat(existing.amount || 0) : 0,
                        };
                    });

                    this.confirmData = {
                        id: rowData.id,
                        property_title:    op.property_title || rowData.property_title || `Propiedad #${op.property_id}`,
                        property_price:    propertyPrice,
                        reservation_amount: reservationAmt,
                        net_amount:         netAmount,
                        amount:             netAmount > 0 ? netAmount : (op.amount || ''),
                        total_commission_percentage: totalPct,
                        total_commission_amount: parseFloat(op.total_commission_amount || 0),
                        company_commission_percentage: companyPct,
                        company_commission_amount: parseFloat(op.company_commission_amount || 0),
                        sellers_commissions: sellersWithNames,
                    };
                    this.showConfirmModal = true;
                } catch (e) {
                    this.$toastr.e('Error al cargar los datos de la operación');
                    console.error(e);
                }
            },
            async doConfirmSale() {
                this.confirmLoading = true;
                try {
                    await axios.post(`/operations/${this.confirmData.id}/confirm-sale`, {
                        amount: this.confirmData.amount,
                    });
                    this.$toastr.s('Venta confirmada correctamente');
                    this.showConfirmModal = false;
                    this.$hub.$emit('reload-default-filter-table');
                } catch (error) {
                    const msg = error.response?.data?.message || 'Error al confirmar la venta';
                    this.$toastr.e(msg);
                    console.error(error);
                } finally {
                    this.confirmLoading = false;
                }
            },
            getAction(rowData, actionObj, active) {

                this.$store.dispatch('setRowData', rowData);

                if (actionObj.title == this.$t('manage_role')) {

                    this.selectedUrl = `${actions.INVITE_USER}/${rowData.id}`;
                    this.operationForUserInvitation();

                } else if(actionObj.title == this.$t('edit')) {
                    window.location.href = `/create/operations?id=${rowData.id}`;

                } else if (actionObj.title === 'Descargar Pago Comisión') {
                    window.open(`/operations/${rowData.id}/commission-receipt`, '_blank');

                } else if (actionObj.title === 'Confirmar Venta') {
                    this.openConfirmSaleModal(rowData);

                } else if (actionObj.title == this.$t('active')) {

                    this.changeUserStatus(1, rowData.id);

                } else if (actionObj.title == this.$t('de_activate')) {

                    this.changeUserStatus(2, rowData.id);
                }
            },
        }
    }
</script>
