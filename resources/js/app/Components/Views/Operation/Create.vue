<template>
<div class="container mt-4">

    <h3 class="mb-3">{{ formTitle }}</h3>

    <div v-if="isLocked" class="alert alert-warning mb-3">
        <strong>Este cierre es de solo lectura.</strong> El inmueble ya está {{ propertyStatus }} y no puede modificarse.
    </div>

    <div class="border rounded p-3">

        <!-- PROPIEDAD -->
        <div class="mb-3">
            <label class="form-label">Propiedad</label>
            <app-input
                type="search-select"
                v-model="operation.property_id"
                :list="propertiesList"
                placeholder="Buscar propiedad..."
                @input="onPropertySelected"
                :disabled="isLocked"
            />
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

        <!-- COMPRADORES -->
        <div class="mb-3">
            <label class="form-label">Compradores</label>
            <app-input 
                type="multi-select"
                v-model="operation.buyers"
                :list="buyersList"
                placeholder="Selecciona compradores"
                :disabled="isLocked"
            />
        </div>

        <!-- VENDEDORES (multi-select con autocomplete) -->
        <div class="mb-3">
            <label class="form-label">Asesores / Vendedores</label>
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
                </div>
                <div class="col-md-3">
                    <div class="input-group input-group-sm">
                        <input
                            type="number"
                            class="form-control"
                            :value="eachPartyPercentage"
                            min="0"
                            max="100"
                            step="0.01"
                            readonly
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
                Inmobiliaria: {{ eachPartyPercentage.toFixed(2) }}% — 
                Total: {{ (totalAdvisorPercentage + eachPartyPercentage).toFixed(2) }}%
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

            selectedPropertyPrice: null,
            showAmount: true,
            suggestedMessage: "",
            isLocked: false,
            propertyStatus: null,

            COMMISSION_RATE: 5,

            operationTypes: [
                { id: "venta", value: "Venta" },
                { id: "reserva", value: "Reserva" },
                { id: "exclusividad", value: "Exclusividad" },
            ],

            sellersCommissions: [],

            operation: {
                property_id: "",
                type: "",
                amount: "",
                property_price: "",
                start_date: "",
                end_date: "",
                buyers: [],
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
        eachPartyPercentage() {
            const numSellers = this.sellersCommissions.length;
            return parseFloat((this.COMMISSION_RATE / (numSellers + 1)).toFixed(4));
        },
        companyCommissionAmount() {
            const amt = parseFloat(this.operation.amount) || 0;
            return amt * this.eachPartyPercentage / 100;
        },
        totalAdvisorPercentage() {
            return this.sellersCommissions.reduce((sum, s) => sum + (parseFloat(s.percentage) || 0), 0);
        },
    },

    methods: {

        async loadData() {
                const response = await this.axiosGet('/operations/form-data');
                
                const properties = JSON.parse(JSON.stringify(response.data.properties));
                const buyers = JSON.parse(JSON.stringify(response.data.clients));
                const users = JSON.parse(JSON.stringify(response.data.users));

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
                    }))
                ];

                this.sellersList = [
                    ...users.map(u => ({
                        id: u.id.toString(),
                        value: u.value,
                    }))
                ];
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
                    });
                }
            }

            this.operation = {
                property_id: operation.property_id || '',
                type: operation.type || '',
                amount: operation.amount || '',
                property_price: operation.property_price || '',
                start_date: operation.start_date || '',
                end_date: operation.end_date || '',
                buyers: operation.buyers || [],
                sellers: operation.sellers || [],
                notes: operation.notes || '',
            };

            this.setSelectedPropertyPrice(this.operation.property_id);
            this.applyTypeState(true);
            this.onSellersChanged(this.operation.sellers || [], operation.sellers_commissions || []);
        },

        setSelectedPropertyPrice(val) {
            const selected = this.propertiesList.find(p => p.id === val);
            if (!selected) return;

            this.selectedPropertyPrice = selected.price;
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

            if (this.operation.type === "venta") {
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
            const equalPct = numSellers > 0
                ? parseFloat((this.COMMISSION_RATE / (numSellers + 1)).toFixed(4))
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
            const numSellers = this.sellersCommissions.length;
            if (numSellers === 0) return;
            const equalPct = parseFloat((this.COMMISSION_RATE / (numSellers + 1)).toFixed(4));
            this.sellersCommissions = this.sellersCommissions.map(s => ({
                ...s,
                percentage: equalPct,
            }));
        },

        onCommissionChanged() {
            // Allow manual override
        },

        sellerCommissionAmount(pct) {
            const amt = parseFloat(this.operation.amount) || 0;
            return amt * (parseFloat(pct) || 0) / 100;
        },

        formatAmount(val) {
            return (parseFloat(val) || 0).toFixed(2);
        },

        async saveOperation() {
            try {
                const payload = {
                    ...this.operation,
                    sellers: this.operation.sellers,
                    sellers_commissions: this.sellersCommissions.map(s => ({
                        id: s.id,
                        percentage: s.percentage,
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
                        type: "",
                        amount: "",
                        property_price: "",
                        start_date: "",
                        end_date: "",
                        buyers: [],
                        sellers: [],
                        notes: "",
                    };
                    this.selectedPropertyPrice = null;
                    this.sellersCommissions = [];
                }

            } catch (error) {
                const msg = error.response?.data?.message || "Error al guardar la operación";
                this.$toastr.e(msg);
                console.error(error);
            }
        }
    },
};
</script>
