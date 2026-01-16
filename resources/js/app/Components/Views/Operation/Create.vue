<template>
<div class="container mt-4">

    <h3 class="mb-3">Registrar Operación</h3>

    <div class="border rounded p-3">

        <!-- PROPIEDAD -->
        <div class="mb-3">
            <label class="form-label">Propiedad</label>
            <app-input
                type="select"
                    v-if="propertiesList.length"

                v-model="operation.property_id"
                :list="propertiesList"
                placeholder="Selecciona una propiedad"
                @change="onPropertySelected"
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

        <!-- VENDEDORES -->
        <div class="mb-3">
            <label class="form-label">Vendedores</label>
            <app-input
                type="select"
                v-model="operation.sellers"
                :list="sellersList"
                placeholder="Selecciona vendedores"
            />
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

            operationTypes: [
                { id: "venta", value: "Venta" },
                { id: "reserva", value: "Reserva" },
                { id: "exclusividad", value: "Exclusividad" },
            ],

            operation: {
                property_id: "",
                type: "",
                amount: "",
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
    },

    methods: {

        async loadData() {

                const response = await this.axiosGet('/operations/form-data');
                console.log(response.data.users)
                
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
                console.log(this.buyersList)


                // Propiedades
                this.propertiesList = [
                    { id: "", value: "Elige uno" },
                    ...properties.map(p => ({
                        id: p.id.toString(),
                        value: p.value,
                        price: p.price,
                    }))
                ];

                // Usuarios (vendedores)
                this.sellersList = [
                    { id: "", value: "Elige uno" },
                    ...users.map(u => ({
                        id: u.id.toString(),
                        value: u.value,
                    }))
                ];
        },

        onPropertySelected() {
            const selected = this.propertiesList.find(
                p => p.id === this.operation.property_id
            );

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
        },

        async saveOperation() {
            try {
                const response = await axios.post("/operations/create", this.operation);
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
                };
                this.selectedPropertyPrice = null;

            } catch (error) {
                this.$toastr.e("Error al guardar la operación");
                console.error(error);
            }
        }
    },
};
</script>
