<template>
<div class="container mt-4">

    <h3 class="mb-3">Registrar Operación</h3>

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
            />
        </div>

        <!-- AMOUNT -->
        <div v-if="showAmount" class="mb-3">
            <label class="form-label">Monto</label>
            <input
                v-model="operation.amount"
                type="number"
                class="form-control"
                placeholder="Monto de la operación"
                @input="recalculateCommissions"
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
                >
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label">Fecha fin</label>
                <input
                    type="date"
                    class="form-control"
                    v-model="operation.end_date"
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
            />
        </div>

        <!-- COMISIONES -->
        <div v-if="operation.sellers.length > 0 && showAmount" class="mb-3 border rounded p-3 bg-light">
            <h6 class="mb-3">Distribución de Comisiones (5% del monto)</h6>

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
                            v-model="operation.company_commission_percentage"
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
                Inmobiliaria: {{ operation.company_commission_percentage }}%
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
            ></textarea>
        </div>

    </div>

    <!-- BOTÓN -->
    <div class="mt-3 text-end">
        <button class="btn btn-primary" @click="saveOperation">
            Guardar Operación
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
            propertiesList: [],
            buyersList: [],
            sellersList: [],

            selectedPropertyPrice: null,
            showAmount: true,
            suggestedMessage: "",

            COMMISSION_RATE: 5, // Total commission percentage (split equally among advisors)

            operationTypes: [
                { id: "venta", value: "Venta" },
                { id: "reserva", value: "Reserva" },
                { id: "exclusividad", value: "Exclusividad" },
            ],

            // Per-seller commission tracking [{id, name, percentage}]
            sellersCommissions: [],

            operation: {
                property_id: "",
                type: "",
                amount: "",
                start_date: "",
                end_date: "",
                buyers: [],
                sellers: [],
                notes: "",
                company_commission_percentage: 5,
            }
        };
    },

    async created() {
        await this.loadData();
    },

    computed: {
        companyCommissionAmount() {
            const amt = parseFloat(this.operation.amount) || 0;
            const pct = parseFloat(this.operation.company_commission_percentage) || 0;
            return amt * pct / 100;
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

                // Propiedades
                this.propertiesList = [
                    { id: "", value: "Elige uno" },
                    ...properties.map(p => ({
                        id: p.id.toString(),
                        value: p.value,
                        price: p.price,
                    }))
                ];

                // Usuarios (vendedores) — incluye Asesor Externo
                this.sellersList = [
                    ...users.map(u => ({
                        id: u.id.toString(),
                        value: u.value,
                    }))
                ];
        },

        onPropertySelected(val) {
            const selected = this.propertiesList.find(p => p.id === val);
            if (!selected) return;
            this.selectedPropertyPrice = selected.price;
            this.updateAmountByType();
        },

        onTypeChange() {
            this.updateAmountByType();
        },

        updateAmountByType() {
            if (!this.selectedPropertyPrice) return;

            if (this.operation.type === "venta") {
                this.operation.amount = this.selectedPropertyPrice;
                this.showAmount = true;
                this.suggestedMessage = "Precio sugerido";
            }

            if (this.operation.type === "reserva") {
                this.operation.amount = (this.selectedPropertyPrice * 0.10).toFixed(2);
                this.showAmount = true;
                this.suggestedMessage = "Precio sugerido";
            }

            if (this.operation.type === "exclusividad") {
                this.operation.amount = "";
                this.showAmount = false;
                this.suggestedMessage = "";
            }

            this.recalculateCommissions();
        },

        onSellersChanged(selectedIds) {
            // Rebuild sellersCommissions array preserving existing percentages
            const numSellers = selectedIds.length;
            const equalPct = numSellers > 0 ? parseFloat((this.COMMISSION_RATE / numSellers).toFixed(4)) : 0;

            this.sellersCommissions = selectedIds.map(id => {
                const existing = this.sellersCommissions.find(s => s.id === id);
                const seller = this.sellersList.find(s => s.id === id);
                return {
                    id: id,
                    name: seller ? seller.value : id,
                    percentage: existing ? existing.percentage : equalPct,
                };
            });
        },

        recalculateCommissions() {
            // Re-distribute equally when amount changes
            const numSellers = this.sellersCommissions.length;
            if (numSellers === 0) return;
            const equalPct = parseFloat((this.COMMISSION_RATE / numSellers).toFixed(4));
            this.sellersCommissions = this.sellersCommissions.map(s => ({
                ...s,
                percentage: equalPct,
            }));
        },

        onCommissionChanged() {
            // Allow manual override — no auto recalculation
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

                await axios.post("/operations/create", payload);
                this.$toastr.s("Operación creada correctamente");

                // Reiniciar formulario
                this.operation = {
                    property_id: "",
                    type: "",
                    amount: "",
                    start_date: "",
                    end_date: "",
                    buyers: [],
                    sellers: [],
                    notes: "",
                    company_commission_percentage: this.COMMISSION_RATE,
                };
                this.selectedPropertyPrice = null;
                this.sellersCommissions = [];

            } catch (error) {
                this.$toastr.e("Error al guardar la operación");
                console.error(error);
            }
        }
    },
};
</script>
