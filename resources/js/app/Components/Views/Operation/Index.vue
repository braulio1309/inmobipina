<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="'Historial de operaciones'" :directory="$t('datatables')" :icon="'grid'"/>

        <!-- Modal Confirmar Venta -->
        <div v-if="showConfirmModal" class="modal-backdrop" style="position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,.5);z-index:1050;display:flex;align-items:center;justify-content:center;">
            <div class="card p-4" style="min-width:400px;z-index:1060;">
                <h5 class="mb-3">Confirmar Venta</h5>
                <p class="text-muted mb-3">Reserva #{{ confirmData.id }} — Propiedad: {{ confirmData.property_title }}</p>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Monto final de venta (USD)</label>
                    <input
                        v-model="confirmData.amount"
                        type="number"
                        class="form-control"
                        placeholder="Precio final de venta"
                        @input="recalcConfirmCommissions"
                    >
                </div>

                <div v-if="confirmData.sellers_commissions.length > 0" class="mb-3 border rounded p-3 bg-light">
                    <h6 class="mb-2">Comisiones ({{ COMMISSION_RATE }}% del monto)</h6>
                    <div class="row mb-1 align-items-center">
                        <div class="col-5"><strong>Inmobiliaria</strong></div>
                        <div class="col-4">
                            <div class="input-group input-group-sm">
                                <input type="number" class="form-control" :value="confirmCompanyPct" readonly>
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                        <div class="col-3 text-success fw-bold">${{ confirmCompanyAmt }}</div>
                    </div>
                    <div v-for="sc in confirmData.sellers_commissions" :key="sc.id" class="row mb-1 align-items-center">
                        <div class="col-5">{{ sc.name }}</div>
                        <div class="col-4">
                            <div class="input-group input-group-sm">
                                <input type="number" class="form-control" v-model="sc.percentage" min="0" max="100" step="0.01" @input="recalcConfirmCommissions">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                        <div class="col-3 text-primary fw-bold">${{ formatAmt(parseFloat(confirmData.amount || 0) * parseFloat(sc.percentage || 0) / 100) }}</div>
                    </div>
                </div>

                <div class="d-flex gap-2 mt-2">
                    <button class="btn btn-success btn-sm" @click="doConfirmSale" :disabled="confirmLoading">
                        <span v-if="confirmLoading" class="spinner-border spinner-border-sm me-1"></span>
                        Confirmar Venta
                    </button>
                    <button class="btn btn-secondary btn-sm" @click="showConfirmModal = false">Cancelar</button>
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
                COMMISSION_RATE: 5,
                showConfirmModal: false,
                confirmLoading: false,
                confirmData: {
                    id: null,
                    property_title: '',
                    amount: '',
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
                            title: 'Propiedad',
                            type: 'text',
                            key: 'property_title',
                            default: "",
                            isVisible: true,
                            modifier: (value, row) => {
                                return value || '—';
                            }
                        },
                        {
                            title: 'Vendedores',
                            type: 'text',
                            key: 'sellers_names',
                            default: "",
                            isVisible: true,
                            modifier: (value, row) => {
                                return value
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
                                return `<span class="badge badge-sm badge-pill badge-${clase}">${row.type}</span>`;
                            }
                        },
                        {
                            title: 'Monto',
                            type: 'custom-html',
                            key: 'amount',
                            default: "",
                            isVisible: true,
                            modifier:(value, row)=>{
                                return (value)?'$'+row.amount: 'N/A';
                            }
                        },
                        {
                            title: 'Comisiones',
                            type: 'custom-html',
                            key: 'commission_details',
                            default: "",
                            isVisible: true,
                            modifier:(value, row) => {
                                if (!value || !value.length) return 'N/A';
                                const escape = (str) => String(str)
                                    .replace(/&/g, '&amp;')
                                    .replace(/</g, '&lt;')
                                    .replace(/>/g, '&gt;')
                                    .replace(/"/g, '&quot;');
                                const lines = value.map(d =>
                                    `<div><strong>${escape(d.name)}</strong>: $${escape(parseFloat(d.amount).toFixed(2))} (${escape(parseFloat(d.percentage).toFixed(2))}%)</div>`
                                );
                                return lines.join('');
                            }
                        },
                        {
                            title: 'Contrato',
                            type: 'custom-html',
                            key: 'contract_url',
                            default: "",
                            isVisible: true,
                            modifier:(value, row) => {
                                if (row.type !== 'exclusividad') return 'N/A';
                                if (value) {
                                    return `<span class="badge badge-sm badge-pill badge-success">Disponible</span>`;
                                }
                                return `<span class="badge badge-sm badge-pill badge-secondary">Sin contrato</span>`;
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
                                {id: 'reserva', value: 'Reserva'},
                                {id: 'exclusividad', value: 'Exclusividad'},
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
                            title: 'Confirmar Venta',
                            type: 'none',
                            modifier: (row) => row.type === 'reserva',
                        },
                    ],
                },
            }
        },
        computed: {
            confirmCompanyPct() {
                const n = this.confirmData.sellers_commissions.length;
                return parseFloat((this.COMMISSION_RATE / (n + 1)).toFixed(4));
            },
            confirmCompanyAmt() {
                const amt = parseFloat(this.confirmData.amount) || 0;
                return this.formatAmt(amt * this.confirmCompanyPct / 100);
            },
        },
        created() {
            this.searchAndSelectFilterOptions();
        },
        methods: {
            searchAndSelectFilterOptions() {
                this.axiosGet("admin/auth/users")
                .then(response => {
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
            },
            formatAmt(val) {
                return (parseFloat(val) || 0).toFixed(2);
            },
            recalcConfirmCommissions() {
                // Keep percentages as-is; amounts recalculated in template
            },
            async openConfirmSaleModal(rowData) {
                try {
                    const res = await this.axiosGet(`/operations/${rowData.id}`);
                    const op = res.data;
                    const numSellers = (op.sellers || []).length;
                    const equalPct = numSellers > 0
                        ? parseFloat((this.COMMISSION_RATE / (numSellers + 1)).toFixed(4))
                        : 0;

                    // Try to get seller names from commission_details of the row
                    const commissionDetails = rowData.commission_details || [];

                    const sellersWithNames = (op.sellers || []).map((sellerId, idx) => {
                        const existing = (op.sellers_commissions || []).find(sc => sc.id === sellerId);
                        const detail = commissionDetails[idx] || null;
                        return {
                            id: sellerId,
                            name: detail ? detail.name : `Asesor #${sellerId}`,
                            percentage: existing ? parseFloat(existing.percentage) : equalPct,
                        };
                    });

                    this.confirmData = {
                        id: rowData.id,
                        property_title: op.property_title || rowData.property_title || `Propiedad #${op.property_id}`,
                        amount: op.amount || '',
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
                        sellers_commissions: this.confirmData.sellers_commissions,
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
