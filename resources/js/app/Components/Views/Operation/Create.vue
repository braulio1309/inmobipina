<template>
<div class="container mt-4">

    <h3 class="mb-3">{{ formTitle }}</h3>

    <div v-if="isLocked" class="alert alert-warning mb-3">
        <strong>Este cierre es de solo lectura.</strong> El inmueble ya está {{ propertyStatus }} y no puede modificarse.
    </div>

    <div class="border rounded p-3">

        <!-- PROPIEDAD -->
        <div class="mb-3">
            <div class="d-flex align-items-center justify-content-between mb-1">
                <label class="form-label mb-0">Propiedad</label>
                <div class="form-check form-check-inline mb-0" v-if="!isLocked">
                    <input
                        class="form-check-input"
                        type="checkbox"
                        id="externalPropertyCheck"
                        v-model="isExternalProperty"
                        @change="onExternalPropertyToggle"
                    >
                    <label class="form-check-label small text-muted" for="externalPropertyCheck">
                        Captación de otra inmobiliaria (ingreso manual)
                    </label>
                </div>
            </div>
            <app-input
                v-if="!isExternalProperty"
                type="search-select"
                v-model="operation.property_id"
                :list="propertiesList"
                placeholder="Buscar propiedad..."
                @input="onPropertySelected"
                :disabled="isLocked"
            />
            <input
                v-else
                v-model="operation.external_property_title"
                type="text"
                class="form-control"
                placeholder="Nombre/descripción del inmueble de otra inmobiliaria"
                :disabled="isLocked"
            >
        </div>

        <!-- TIPO (venta, reserva, exclusividad) -->
        <div class="mb-3">
            <label class="form-label">Tipo de Operación</label>
            <app-input
                type="select"
                v-model="operation.type"
                :list="operationTypes"
                placeholder="Selecciona el tipo"
                @change="onTypeChange"
                :disabled="isLocked"
            />
        </div>

        <!-- RESERVA: precio propiedad + monto reserva por separado -->
        <div v-if="showAmount && operation.type === 'reserva'" class="mb-3">
            <div class="alert alert-info py-2 mb-2">
                <i class="fas fa-info-circle mr-1"></i>
                <strong>Operación de Reserva:</strong> Puedes ajustar el precio de la propiedad y el monto de la reserva.
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">
                        💰 Precio de la Propiedad
                        <small class="text-muted font-weight-normal">(modificable)</small>
                    </label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input
                            v-model="operation.property_price"
                            type="number"
                            class="form-control"
                            placeholder="Precio de la propiedad"
                            @input="onPropertyPriceChanged"
                            :readonly="isLocked"
                        >
                    </div>
                    <small class="text-muted">Precio original del inmueble</small>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">
                        📋 Monto de la Reserva
                        <small class="text-muted font-weight-normal">(modificable)</small>
                    </label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input
                            v-model="operation.amount"
                            type="number"
                            class="form-control"
                            placeholder="Monto a cobrar por reserva"
                            @input="recalculateCommissions"
                            :readonly="isLocked"
                        >
                    </div>
                    <small class="text-success" v-if="operation.property_price">
                        Sugerido: ${{ formatAmount(operation.property_price * 0.10) }} (10% del precio)
                    </small>
                </div>
            </div>
        </div>

        <!-- AMOUNT para venta/alquiler -->
        <div v-if="showAmount && operation.type !== 'reserva'" class="mb-3">
            <label class="form-label">Monto</label>
            <input
                v-model="operation.amount"
                type="number"
                class="form-control"
                placeholder="Monto de la operación"
                @input="recalculateCommissions"
                :readonly="isLocked"
            >
            <small class="text-muted" v-if="suggestedMessage">
                {{ suggestedMessage }}
            </small>
        </div>

        <!-- EXCLUSIVIDAD: fechas -->
        <div v-if="operation.type === 'exclusividad'" class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Fecha inicio</label>
                <input
                    type="date"
                    class="form-control"
                    v-model="operation.start_date"
                    :readonly="isLocked"
                >
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label">Fecha fin</label>
                <input
                    type="date"
                    class="form-control"
                    v-model="operation.end_date"
                    :readonly="isLocked"
                >
            </div>
        </div>

        <!-- FECHA DE CIERRE -->
        <div class="mb-3">
            <label class="form-label">Fecha de cierre</label>
            <input
                type="date"
                class="form-control"
                v-model="operation.fecha_cierre"
                :readonly="isLocked"
            >
            <small class="text-muted">Fecha efectiva del cierre. Se usa para reportes y filtros de rango de fecha.</small>
        </div>

        <!-- CLIENTE PROPIETARIO -->
        <div class="mb-3">
            <div class="d-flex align-items-center justify-content-between mb-1">
                <label class="form-label mb-0">Cliente propietario</label>
                <div class="form-check form-check-inline mb-0" v-if="!isLocked">
                    <input
                        class="form-check-input"
                        type="checkbox"
                        id="manualOwnerClientCheck"
                        v-model="useManualOwnerClient"
                        @change="onOwnerClientModeChanged"
                    >
                    <label class="form-check-label small text-muted" for="manualOwnerClientCheck">
                        Escribir manualmente
                    </label>
                </div>
            </div>
            <app-input
                v-if="!useManualOwnerClient"
                type="search-select"
                v-model="operation.owner_client_id"
                :list="buyersList"
                placeholder="Selecciona el cliente propietario"
                :disabled="isLocked"
            />
            <input
                v-else
                v-model="operation.owner_client_name_manual"
                type="text"
                class="form-control"
                placeholder="Escribe el nombre del cliente propietario"
                :disabled="isLocked"
            >
        </div>

        <!-- CLIENTE COMPRADOR -->
        <div class="mb-3">
            <div class="d-flex align-items-center justify-content-between mb-1">
                <label class="form-label mb-0">Cliente comprador</label>
                <div class="form-check form-check-inline mb-0" v-if="!isLocked">
                    <input
                        class="form-check-input"
                        type="checkbox"
                        id="manualBuyerClientCheck"
                        v-model="useManualBuyerClient"
                        @change="onBuyerClientModeChanged"
                    >
                    <label class="form-check-label small text-muted" for="manualBuyerClientCheck">
                        Escribir manualmente
                    </label>
                </div>
            </div>
            <app-input
                v-if="!useManualBuyerClient"
                type="search-select"
                v-model="operation.buyer_client_id"
                :list="buyersList"
                placeholder="Selecciona el cliente comprador"
                :disabled="isLocked"
            />
            <input
                v-else
                v-model="operation.buyer_client_name_manual"
                type="text"
                class="form-control"
                placeholder="Escribe el nombre del cliente comprador"
                :disabled="isLocked"
            >
        </div>

        <!-- VENDEDORES (multi-select con autocomplete) -->
        <div class="mb-3">
            <label class="form-label">Asesores</label>
            <app-input
                type="multi-select"
                v-model="operation.sellers"
                :list="sellersList"
                placeholder="Selecciona asesores..."
                @input="onSellersChanged"
                :disabled="isLocked"
            />
        </div>

        <!-- COMISIONES -->
        <div v-if="operation.sellers.length > 0 && showAmount" class="mb-3 border rounded p-3 bg-light">
            <h6 class="mb-3">Distribución de Comisiones ({{ COMMISSION_RATE }}% del monto)</h6>

            <!-- Comisión Inmobiliaria -->
            <div class="row mb-2 align-items-center">
                <div class="col-md-4">
                    <strong>Inmobiliaria</strong>
                    <small class="text-muted d-block">Editable igual que la comisión de asesores</small>
                </div>
                <div class="col-md-3">
                    <div class="input-group input-group-sm">
                        <input
                            type="number"
                            class="form-control"
                            v-model="operation.company_commission_percentage"
                            min="0"
                            max="100"
                            step="0.01"
                            @input="onCompanyCommissionChanged"
                            :readonly="isLocked"
                        >
                        <span class="input-group-text">%</span>
                    </div>
                </div>
                <div class="col-md-3">
                    <span class="text-success fw-bold">
                        ${{ formatAmount(companyCommissionAmount) }}
                    </span>
                </div>
            </div>

            <!-- Comisión por asesor -->
            <div
                v-for="seller in sellersCommissions"
                :key="seller.id"
                class="row mb-2 align-items-center"
            >
                <div class="col-md-4">
                    <span>{{ seller.name }}</span>
                </div>
                <div class="col-md-3">
                    <div class="input-group input-group-sm">
                        <input
                            type="number"
                            class="form-control"
                            v-model="seller.percentage"
                            min="0"
                            max="100"
                            step="0.01"
                            @input="onCommissionChanged"
                            :readonly="isLocked"
                        >
                        <span class="input-group-text">%</span>
                    </div>
                </div>
                <div class="col-md-3">
                    <span class="text-primary fw-bold">
                        ${{ formatAmount(sellerCommissionAmount(seller.percentage)) }}
                    </span>
                </div>
            </div>

            <small class="text-muted">
                Total asesores: {{ totalAdvisorPercentage.toFixed(2) }}% — 
                Inmobiliaria: {{ companyCommissionPct.toFixed(2) }}% — 
                Total: {{ (totalAdvisorPercentage + companyCommissionPct).toFixed(2) }}%
            </small>
        </div>

        <!-- NOTAS -->
        <div class="mb-3">
            <label class="form-label">Notas</label>
            <textarea
                class="form-control"
                rows="4"
                v-model="operation.notes"
                placeholder="Notas adicionales..."
                :readonly="isLocked"
            ></textarea>
        </div>

    </div>

    <!-- BOTÓN -->
    <div class="mt-3 text-end">
        <a
            v-if="canDownloadCommissionPdf"
            :href="`/operations/${operationId}/commission-receipt`"
            target="_blank"
            class="btn btn-success mr-2"
        >
            Descargar Pago Comisión PDF
        </a>
        <button v-if="!isLocked" class="btn btn-primary" @click="saveOperation">
            {{ submitLabel }}
        </button>
    </div>

</div>
</template>


<script>
import {FormMixin} from "../../../../../js/core/mixins/form/FormMixin.js";
import * as actions from "../../../../../js/app/Config/ApiUrl";
import axios from "axios";

export default {
    mixins: [FormMixin],

    data() {
        return {
            operationId: null,
            propertiesList: [],
            buyersList: [],
            sellersList: [],

            isExternalProperty: false,

            selectedPropertyPrice: null,
            showAmount: true,
            suggestedMessage: "",
            isLocked: false,
            propertyStatus: null,
            useManualOwnerClient: false,
            useManualBuyerClient: false,

            COMMISSION_RATE: 5,

            operationTypes: [
                { id: "venta", value: "Venta" },
                { id: "alquiler", value: "Alquiler" },
                { id: "reserva", value: "Reserva" },
                { id: "exclusividad", value: "Exclusividad" },
                { id: "traspaso", value: "Traspaso" },
            ],

            sellersCommissions: [],

            operation: {
                property_id: "",
                external_property_title: "",
                owner_client_id: "",
                owner_client_name_manual: "",
                buyer_client_id: "",
                buyer_client_name_manual: "",
                type: "",
                amount: "",
                property_price: "",
                start_date: "",
                end_date: "",
                fecha_cierre: "",
                company_commission_percentage: 2.5,
                sellers: [],
                notes: "",
            }
        };
    },

    async created() {
        await this.loadData();

        const operationId = new URLSearchParams(window.location.search).get('id');
        if (operationId) {
            this.operationId = operationId;
            await this.loadOperation();
        }
    },

    computed: {
        formTitle() {
            if (this.isLocked) return 'Ver Operación';
            return this.operationId ? 'Editar Operación' : 'Registrar Operación';
        },
        submitLabel() {
            return this.operationId ? 'Actualizar Operación' : 'Guardar Operación';
        },
        companyCommissionPct() {
            const pct = parseFloat(this.operation.company_commission_percentage);
            return Number.isFinite(pct) ? pct : 0;
        },
        eachPartyPercentage() {
            // Kept for backward compatibility; advisors split the remaining percentage.
            const numSellers = this.sellersCommissions.length;
            const distributablePercentage = Math.max(this.COMMISSION_RATE - this.companyCommissionPct, 0);
            return numSellers > 0 ? parseFloat((distributablePercentage / numSellers).toFixed(4)) : 0;
        },
        companyCommissionAmount() {
            const amt = parseFloat(this.operation.amount) || 0;
            return amt * this.companyCommissionPct / 100;
        },
        totalAdvisorPercentage() {
            return this.sellersCommissions.reduce((sum, s) => sum + (parseFloat(s.percentage) || 0), 0);
        },
        canDownloadCommissionPdf() {
            return Boolean(this.operationId) && ['reserva', 'venta', 'alquiler', 'traspaso'].includes(this.operation.type);
        },
    },

    methods: {

        async loadData() {
                try {
                    const response = await this.axiosGet('/operations/form-data');
                    const properties = Array.isArray(response?.data?.properties) ? response.data.properties : [];
                    const buyers = Array.isArray(response?.data?.clients) ? response.data.clients : [];
                    const users = Array.isArray(response?.data?.users) ? response.data.users : [];

                    this.buyersList = [
                        { id: "", value: "Elige uno" },
                        ...buyers.map(c => ({
                            id: c.id.toString(),
                            value: c.value,
                        }))
                    ];

                    this.propertiesList = [
                        { id: "", value: "Elige uno" },
                        ...properties.map(p => ({
                            id: p.id.toString(),
                            value: p.value,
                            price: p.price,
                            suggestedOwnerClientId: p.suggested_owner_client_id ? p.suggested_owner_client_id.toString() : '',
                            suggestedOwnerClientName: p.suggested_owner_client_name || '',
                        }))
                    ];

                    this.sellersList = users.map(u => ({
                        id: u.id.toString(),
                        value: u.value,
                    }));
                } catch (error) {
                    this.buyersList = [{ id: "", value: "Elige uno" }];
                    this.propertiesList = [{ id: "", value: "Elige uno" }];
                    this.sellersList = [];
                    this.$toastr.e('No se pudieron cargar los datos del formulario de cierres');
                    console.error('Error cargando datos del formulario de cierres', error);
                }
        },

        async loadOperation() {
            const response = await this.axiosGet(`/operations/${this.operationId}`);
            const operation = response.data;

            this.isLocked = operation.is_locked || false;
            this.propertyStatus = operation.property_status || null;

            // If the property is locked, add it to propertiesList so it displays correctly
            if (operation.property_id) {
                const exists = this.propertiesList.find(p => p.id === operation.property_id);
                if (!exists) {
                    this.propertiesList.push({
                        id: operation.property_id,
                        value: operation.property_title || `Propiedad #${operation.property_id}`,
                        price: operation.amount,
                        suggestedOwnerClientId: operation.owner_client_id || '',
                        suggestedOwnerClientName: operation.owner_client_name || '',
                    });
                }
            }

            this.operation = {
                property_id: operation.property_id || '',
                external_property_title: operation.external_property_title || '',
                type: operation.type || '',
                amount: operation.amount || '',
                property_price: operation.property_price || '',
                start_date: operation.start_date || '',
                end_date: operation.end_date || '',
                fecha_cierre: operation.fecha_cierre || '',
                owner_client_id: operation.owner_client_id || '',
                owner_client_name_manual: '',
                buyer_client_id: operation.buyer_client_id || '',
                buyer_client_name_manual: '',
                company_commission_percentage: operation.company_commission_percentage ?? 2.5,
                sellers: operation.sellers || [],
                notes: operation.notes || '',
            };

            this.useManualOwnerClient = !this.operation.owner_client_id && Boolean(operation.owner_client_name);
            this.useManualBuyerClient = !this.operation.buyer_client_id && Boolean(operation.buyer_client_name);

            if (this.useManualOwnerClient) {
                this.operation.owner_client_name_manual = operation.owner_client_name || '';
            }

            if (this.useManualBuyerClient) {
                this.operation.buyer_client_name_manual = operation.buyer_client_name || '';
            }

            this.isExternalProperty = !operation.property_id && !!operation.external_property_title;

            this.setSelectedPropertyPrice(this.operation.property_id);
            this.applyTypeState(true);
            this.onSellersChanged(this.operation.sellers || [], operation.sellers_commissions || []);
        },

        setSelectedPropertyPrice(val) {
            const selected = this.propertiesList.find(p => p.id === val);
            if (!selected) return;

            this.selectedPropertyPrice = selected.price;

            this.applySuggestedOwnerClient(selected);
        },

        applySuggestedOwnerClient(selectedProperty) {
            if (this.useManualOwnerClient || !selectedProperty || !selectedProperty.suggestedOwnerClientId) {
                return;
            }

            const currentOwner = this.operation.owner_client_id ? String(this.operation.owner_client_id) : '';
            const suggestedOwner = String(selectedProperty.suggestedOwnerClientId);

            if (currentOwner === '' || currentOwner !== suggestedOwner) {
                this.operation.owner_client_id = suggestedOwner;
            }
        },

        onPropertySelected(val) {
            this.setSelectedPropertyPrice(val);
            this.applyTypeState();
        },

        onTypeChange() {
            this.applyTypeState();
        },

        applyTypeState(preserveAmount = false) {
            if (this.operation.type === "exclusividad") {
                this.showAmount = false;
                this.suggestedMessage = "";
                if (!preserveAmount) {
                    this.operation.amount = "";
                    this.operation.property_price = "";
                }
                return;
            }

            this.showAmount = true;
            this.suggestedMessage = this.selectedPropertyPrice ? "Precio sugerido" : "";

            if (!this.selectedPropertyPrice || preserveAmount) {
                return;
            }

            if (this.operation.type === "venta" || this.operation.type === "traspaso" || this.operation.type === "alquiler") {
                this.operation.amount = this.selectedPropertyPrice;
                this.operation.property_price = "";
            } else if (this.operation.type === "reserva") {
                this.operation.property_price = this.selectedPropertyPrice;
                this.operation.amount = (this.selectedPropertyPrice * 0.10).toFixed(2);
            }

            this.recalculateCommissions();
        },

        onPropertyPriceChanged() {
            if (this.operation.type === 'reserva' && this.operation.property_price) {
                // Recalculate commissions based on amount (not property_price)
                this.recalculateCommissions();
            }
        },

        onSellersChanged(selectedIds, existingCommissions = []) {
            const numSellers = selectedIds.length;
            const distributablePercentage = Math.max(this.COMMISSION_RATE - this.companyCommissionPct, 0);
            const equalPct = numSellers > 0
                ? parseFloat((distributablePercentage / numSellers).toFixed(4))
                : 0;

            this.sellersCommissions = selectedIds.map(id => {
                const seller = this.sellersList.find(s => s.id === id);
                const existing = existingCommissions.find(c => c.id === id);
                return {
                    id: id,
                    name: seller ? seller.value : id,
                    percentage: existing ? parseFloat(existing.percentage) : equalPct,
                };
            });
        },

        recalculateCommissions() {
            this.sellersCommissions = this.sellersCommissions.map(s => ({
                ...s,
                percentage: this.normalizePercentage(s.percentage),
            }));
        },

        onCommissionChanged() {
            this.recalculateCommissions();
        },

        onCompanyCommissionChanged() {
            this.operation.company_commission_percentage = this.normalizePercentage(this.operation.company_commission_percentage);
        },

        onOwnerClientModeChanged() {
            if (this.useManualOwnerClient) {
                this.operation.owner_client_id = '';
                return;
            }

            this.operation.owner_client_name_manual = '';
        },

        onBuyerClientModeChanged() {
            if (this.useManualBuyerClient) {
                this.operation.buyer_client_id = '';
                return;
            }

            this.operation.buyer_client_name_manual = '';
        },

        normalizePercentage(value) {
            const pct = parseFloat(value);
            return Number.isFinite(pct) ? pct : 0;
        },

        sellerCommissionAmount(pct) {
            const amt = parseFloat(this.operation.amount) || 0;
            return amt * (parseFloat(pct) || 0) / 100;
        },

        formatAmount(val) {
            return (parseFloat(val) || 0).toFixed(2);
        },

        validateOperationBeforeSave() {
            if (!this.isExternalProperty && !this.operation.property_id) {
                this.$toastr.e('Debes seleccionar una propiedad');
                return false;
            }

            if (this.isExternalProperty && !this.operation.external_property_title) {
                this.$toastr.e('Debes ingresar el nombre del inmueble');
                return false;
            }

            if (!this.operation.type) {
                this.$toastr.e('Debes seleccionar el tipo de operación');
                return false;
            }

            if (['venta', 'reserva', 'traspaso', 'alquiler'].includes(this.operation.type)) {
                if (!this.operation.owner_client_id && !String(this.operation.owner_client_name_manual || '').trim()) {
                    this.$toastr.e('Debes seleccionar el cliente propietario para generar el pago');
                    return false;
                }

                if (!this.operation.buyer_client_id && !String(this.operation.buyer_client_name_manual || '').trim()) {
                    this.$toastr.e('Debes seleccionar el cliente comprador para generar el pago');
                    return false;
                }

                if (!this.operation.sellers.length) {
                    this.$toastr.e('Debes seleccionar al menos un asesor');
                    return false;
                }
            }

            return true;
        },

        async saveOperation() {
            if (!this.validateOperationBeforeSave()) {
                return;
            }

            try {
                const payload = {
                    ...this.operation,
                    is_external_property: this.isExternalProperty,
                    sellers: this.operation.sellers,
                    company_commission_percentage: this.companyCommissionPct,
                    sellers_commissions: this.sellersCommissions.map(s => ({
                        id: s.id,
                        percentage: this.normalizePercentage(s.percentage),
                    })),
                };

                let response;
                if (this.operationId) {
                    response = await axios.post(`/edit/operations/${this.operationId}`, payload);
                    this.$toastr.s("Operación actualizada correctamente");
                } else {
                    response = await axios.post("/operations/create", payload);
                    this.$toastr.s("Operación creada correctamente");
                }

                if (response.data.pdf_url) {
                    window.open(response.data.pdf_url, '_blank');
                }

                if (!this.operationId) {
                    this.operation = {
                        property_id: "",
                        external_property_title: "",
                        owner_client_id: "",
                        owner_client_name_manual: "",
                        buyer_client_id: "",
                        buyer_client_name_manual: "",
                        type: "",
                        amount: "",
                        property_price: "",
                        start_date: "",
                        end_date: "",
                        fecha_cierre: "",
                        company_commission_percentage: 2.5,
                        sellers: [],
                        notes: "",
                    };
                    this.isExternalProperty = false;
                    this.useManualOwnerClient = false;
                    this.useManualBuyerClient = false;
                    this.selectedPropertyPrice = null;
                    this.sellersCommissions = [];
                }

            } catch (error) {
                const msg = error.response?.data?.message || "Error al guardar la operación";
                this.$toastr.e(msg);
                console.error(error);
            }
        },

        onExternalPropertyToggle() {
            if (this.isExternalProperty) {
                this.operation.property_id = "";
                this.selectedPropertyPrice = null;
            } else {
                this.operation.external_property_title = "";
            }
        },
    },
};
</script>
