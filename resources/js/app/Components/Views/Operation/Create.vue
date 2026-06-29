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
                            :value="formatInputWithCommas(operation.property_price)"
                            type="text"
                            inputmode="decimal"
                            class="form-control"
                            placeholder="Precio de la propiedad"
                            @input="onPropertyPriceInput"
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
                            :value="formatInputWithCommas(operation.amount)"
                            type="text"
                            inputmode="decimal"
                            class="form-control"
                            placeholder="Monto a cobrar por reserva"
                            @input="onAmountInput"
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
                :value="formatInputWithCommas(operation.amount)"
                type="text"
                inputmode="decimal"
                class="form-control"
                placeholder="Monto de la operación"
                @input="onAmountInput"
                :readonly="isLocked"
            >
            <small class="text-muted" v-if="suggestedMessage">
                {{ suggestedMessage }}
            </small>
        </div>

        <div v-if="operation.type === 'alquiler'" class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label">Meses de adelanto</label>
                <input
                    v-model="operation.meses_adelanto"
                    type="number"
                    min="0"
                    step="1"
                    class="form-control"
                    placeholder="Cantidad de meses"
                    :readonly="isLocked"
                >
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label">Fecha inicio</label>
                <input
                    v-model="operation.start_date"
                    type="date"
                    class="form-control"
                    :readonly="isLocked"
                >
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label">Mes administrativo</label>
                <input
                    v-model="operation.mes_administrativo"
                    type="number"
                    min="0"
                    step="1"
                    class="form-control"
                    placeholder="0 o 1"
                    @input="onAdministrativeMonthsChanged"
                    :readonly="isLocked"
                >
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label">Fecha corte</label>
                <input
                    v-model="operation.fecha_corte"
                    type="date"
                    class="form-control"
                    :readonly="isLocked"
                >
                <small class="text-muted">Fecha final del alquiler para calcular la duración.</small>
            </div>

            <div class="col-md-6 mb-3 d-flex align-items-end">
                <small class="text-muted">
                    La duración del alquiler se toma desde la fecha inicio hasta la fecha corte.
                </small>
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label">Tiempo de pago</label>
                <app-input
                    type="select"
                    v-model="operation.payment_frequency"
                    :list="paymentFrequencyOptions"
                    placeholder="Selecciona la periodicidad"
                    :disabled="isLocked"
                />
            </div>
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
        <div v-if="showAmount" class="mb-3 border rounded p-3 bg-light">
            <h6 class="mb-3">Distribución de Comisiones</h6>

            <div v-if="!isRentalOperation" class="row mb-2 align-items-center">
                <div class="col-md-4">
                    <strong>Comisión total</strong>
                    <small class="text-muted d-block">Porcentaje referencial sobre el monto de la operación</small>
                </div>
                <div class="col-md-3">
                    <div class="input-group input-group-sm">
                        <input
                            type="number"
                            class="form-control"
                            v-model="operation.total_commission_percentage"
                            min="0"
                            max="100"
                            step="0.01"
                            @input="onTotalCommissionChanged"
                            :readonly="isLocked"
                        >
                        <span class="input-group-text">%</span>
                    </div>
                </div>
                <div class="col-md-5 text-muted small">
                    {{ commissionReferenceText }}
                </div>
            </div>

            <div class="row mb-3 align-items-center">
                <div class="col-md-4">
                    <strong>Monto de comisión</strong>
                    <small class="text-muted d-block">{{ isRentalOperation ? 'Monto a repartir entre la inmobiliaria y los asesores.' : 'Monto editable sobre el que se reparte la comisión' }}</small>
                </div>
                <div class="col-md-3">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">$</span>
                        <input
                            type="number"
                            class="form-control"
                            v-model="operation.total_commission_amount"
                            min="0"
                            step="0.01"
                            @input="onTotalCommissionAmountChanged"
                            :readonly="isLocked"
                        >
                    </div>
                </div>
                <div class="col-md-5 text-muted small">
                    Referencia actual: ${{ formatAmount(referenceCommissionAmount) }}.
                </div>
            </div>

            <!-- Comisión Inmobiliaria -->
            <div class="row mb-2 align-items-center">
                <div class="col-md-4">
                    <strong>Inmobiliaria</strong>
                    <small class="text-muted d-block">{{ isRentalOperation ? 'En alquiler se sugiere 50% para la inmobiliaria, pero puedes editarlo.' : 'Editable igual que la comisión de asesores' }}</small>
                </div>
                <div v-if="!isRentalOperation" class="col-md-3">
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
                <div :class="isRentalOperation ? 'col-md-5' : 'col-md-3'">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">$</span>
                        <input
                            type="number"
                            class="form-control"
                            v-model="operation.company_commission_amount"
                            min="0"
                            step="0.01"
                            @input="onCompanyCommissionAmountChanged"
                            :readonly="isLocked"
                        >
                    </div>
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
                <div v-if="!isRentalOperation" class="col-md-3">
                    <div class="input-group input-group-sm">
                        <input
                            type="number"
                            class="form-control"
                            v-model="seller.percentage"
                            min="0"
                            max="100"
                            step="0.01"
                            @input="onSellerPercentageChanged(seller, seller.percentage)"
                            :readonly="isLocked"
                        >
                        <span class="input-group-text">%</span>
                    </div>
                </div>
                <div :class="isRentalOperation ? 'col-md-5' : 'col-md-3'">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">$</span>
                        <input
                            type="number"
                            class="form-control"
                            v-model="seller.amount"
                            min="0"
                            step="0.01"
                            @input="onSellerCommissionAmountChanged(seller)"
                            :readonly="isLocked"
                        >
                    </div>
                </div>
            </div>

            <div v-if="sellersCommissions.length === 0" class="small text-muted mb-2">
                Solo está involucrada la inmobiliaria en esta comisión.
            </div>

            <div v-if="isRentalOperation" class="small text-muted mb-2">
                En alquiler la comisión sugerida es monto por meses administrativos. La distribución inicia 50% para la inmobiliaria y 50% para los asesores, pero puedes modificarla.
            </div>

            <small v-if="!isRentalOperation" class="text-muted">
                Total asesores: {{ totalAdvisorPercentage.toFixed(2) }}% — 
                Inmobiliaria: {{ companyCommissionPct.toFixed(2) }}% — 
                Total asignado: {{ (totalAdvisorPercentage + companyCommissionPct).toFixed(2) }}% —
                Monto asignado: ${{ formatAmount(totalAssignedCommissionAmount) }} —
                Total comisión: {{ totalCommissionPct.toFixed(2) }}%
            </small>
            <small v-else class="text-muted">
                Monto asesores: ${{ formatAmount(totalAdvisorCommissionAmount) }} —
                Monto inmobiliaria: ${{ formatAmount(companyCommissionAmount) }} —
                Monto asignado: ${{ formatAmount(totalAssignedCommissionAmount) }} —
                Total comisión: ${{ formatAmount(totalCommissionAmount) }}
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

    props: {
        isAdmin: {
            type: Boolean,
            default: false,
        },
    },

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
            hasEditedTotalCommissionAmount: false,
            hasEditedCommissionDistribution: false,

            operationTypes: [
                { id: "venta", value: "Venta" },
                { id: "alquiler", value: "Alquiler" },
                { id: "reserva", value: "Reserva" },
                { id: "exclusividad", value: "Exclusividad" },
                { id: "traspaso", value: "Traspaso" },
            ],
            paymentFrequencyOptions: [
                { id: "", value: "Selecciona una opción" },
                { id: "quincenal", value: "Quincenal" },
                { id: "mensual", value: "Mensual" },
                { id: "semestral", value: "Semestral" },
                { id: "anual", value: "Anual" },
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
                meses_adelanto: 0,
                mes_administrativo: 0,
                fecha_corte: "",
                payment_frequency: "",
                total_commission_percentage: 5,
                total_commission_amount: 0,
                company_commission_percentage: 5,
                company_commission_amount: 0,
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
            return this.totalCommissionAmount > 0
                ? (this.companyCommissionAmount / this.totalCommissionAmount) * 100
                : 0;
        },
        isRentalOperation() {
            return this.operation.type === 'alquiler';
        },
        totalCommissionPct() {
            return this.commissionBaseAmount > 0
                ? (this.totalCommissionAmount / this.commissionBaseAmount) * 100
                : 0;
        },
        commissionBaseAmount() {
            if (this.operation.type === 'reserva') {
                const propertyTotal = this.normalizeMoney(this.operation.property_price);
                if (propertyTotal > 0) {
                    return propertyTotal;
                }
            }

            return this.normalizeMoney(this.operation.amount);
        },
        eachPartyPercentage() {
            const numSellers = this.sellersCommissions.length;
            const distributablePercentage = Math.max(100 - this.companyCommissionPct, 0);
            return numSellers > 0 ? parseFloat((distributablePercentage / numSellers).toFixed(4)) : 0;
        },
        totalCommissionAmount() {
            return this.normalizeMoney(this.operation.total_commission_amount);
        },
        referenceCommissionAmount() {
            if (this.isRentalOperation) {
                const administrativeMonths = Math.max(0, parseInt(this.operation.mes_administrativo, 10) || 0);
                return this.commissionBaseAmount * administrativeMonths;
            }

            return this.commissionBaseAmount * 0.05;
        },
        commissionReferenceText() {
            if (this.isRentalOperation) {
                const administrativeMonths = Math.max(0, parseInt(this.operation.mes_administrativo, 10) || 0);
                return `Referencia sugerida: monto de la operacion x meses administrativos (${administrativeMonths}).`;
            }

            if (this.operation.type === 'reserva') {
                return 'Referencia sugerida: 5% del precio total de la propiedad.';
            }

            return 'Referencia sugerida: 5% del monto de la operación.';
        },
        companyCommissionAmount() {
            return this.normalizeMoney(this.operation.company_commission_amount);
        },
        totalAdvisorPercentage() {
            return this.sellersCommissions.reduce((sum, s) => sum + (parseFloat(s.percentage) || 0), 0);
        },
        totalAdvisorCommissionAmount() {
            return this.sellersCommissions.reduce((sum, s) => sum + this.normalizeMoney(s.amount), 0);
        },
        totalAssignedCommissionAmount() {
            return this.companyCommissionAmount + this.totalAdvisorCommissionAmount;
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

            // Admin users can always edit any closure regardless of property status
            this.isLocked = this.isAdmin ? false : (operation.is_locked || false);
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
                meses_adelanto: operation.meses_adelanto ?? 0,
                mes_administrativo: operation.mes_administrativo ?? 0,
                fecha_corte: operation.fecha_corte ?? '',
                payment_frequency: operation.payment_frequency ?? '',
                total_commission_percentage: operation.total_commission_percentage ?? 5,
                total_commission_amount: operation.total_commission_amount ?? 0,
                owner_client_id: operation.owner_client_id || '',
                owner_client_name_manual: '',
                buyer_client_id: operation.buyer_client_id || '',
                buyer_client_name_manual: '',
                company_commission_percentage: operation.company_commission_percentage ?? 2.5,
                company_commission_amount: operation.company_commission_amount ?? 0,
                sellers: operation.sellers || [],
                notes: operation.notes || '',
            };

            this.useManualOwnerClient = !this.operation.owner_client_id && Boolean(operation.owner_client_name);
            this.useManualBuyerClient = !this.operation.buyer_client_id && Boolean(operation.buyer_client_name);
            this.hasEditedTotalCommissionAmount = true;
            this.hasEditedCommissionDistribution = true;

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

        applySuggestedRentalCommissionSplit(force = false) {
            if (!this.isRentalOperation || this.sellersCommissions.length === 0) {
                return;
            }

            if (!force && this.hasEditedCommissionDistribution) {
                this.recalculateCommissionAmountsFromPercentages();
                return;
            }

            const halfAmount = parseFloat((this.totalCommissionAmount / 2).toFixed(2));
            this.operation.company_commission_amount = halfAmount;
            this.syncCompanyPercentageFromAmount();

            const sellerShare = this.sellersCommissions.length > 0
                ? parseFloat((50 / this.sellersCommissions.length).toFixed(4))
                : 0;
            const sellerAmount = this.sellersCommissions.length > 0
                ? parseFloat((halfAmount / this.sellersCommissions.length).toFixed(2))
                : 0;

            this.sellersCommissions = this.sellersCommissions.map((seller) => ({
                ...seller,
                percentage: sellerShare,
                amount: sellerAmount,
            }));
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

            if (!this.normalizeMoney(this.operation.total_commission_amount)) {
                this.operation.total_commission_amount = this.referenceCommissionAmount.toFixed(2);
                this.syncTotalCommissionPercentageFromAmount();
            }

            this.recalculateCommissions();
        },

        onPropertyPriceInput(event) {
            this.operation.property_price = this.parseMoneyInput(event?.target?.value);
            this.onPropertyPriceChanged();
        },

        onPropertyPriceChanged() {
            if (this.operation.type === 'reserva' && this.operation.property_price) {
                // In reservations, commission references are based on total property price.
                this.recalculateCommissions();
            }
        },

        onAmountInput(event) {
            this.operation.amount = this.parseMoneyInput(event?.target?.value);
            this.recalculateCommissions();
        },

        onSellersChanged(selectedIds, existingCommissions = []) {
            const previousSellerCount = this.sellersCommissions.length;

            if (selectedIds.length === 0) {
                this.sellersCommissions = [];
                this.operation.company_commission_amount = this.totalCommissionAmount;
                this.syncCompanyPercentageFromAmount();
                return;
            }

            if (
                previousSellerCount === 0
                && existingCommissions.length === 0
                && this.companyCommissionAmount >= this.totalCommissionAmount
            ) {
                this.operation.company_commission_amount = parseFloat(
                    (this.totalCommissionAmount / (selectedIds.length + 1)).toFixed(2)
                );
                this.syncCompanyPercentageFromAmount();
            }

            const numSellers = selectedIds.length;
            const distributablePercentage = Math.max(100 - this.companyCommissionPct, 0);
            const equalPct = numSellers > 0
                ? parseFloat((distributablePercentage / numSellers).toFixed(4))
                : 0;

            this.sellersCommissions = selectedIds.map(id => {
                const seller = this.sellersList.find(s => s.id === id);
                const existing = existingCommissions.find(c => c.id === id);
                return {
                    id: id,
                    name: seller ? seller.value : id,
                    percentage: existing
                        ? this.percentageFromCommissionAmount(existing.amount)
                        : equalPct,
                    amount: existing ? this.normalizeMoney(existing.amount) : parseFloat((this.sellerCommissionAmount(equalPct)).toFixed(2)),
                };
            });

            this.applySuggestedRentalCommissionSplit(existingCommissions.length === 0);
        },

        recalculateCommissions() {
            if (this.isRentalOperation) {
                if (!this.hasEditedTotalCommissionAmount) {
                    this.operation.total_commission_amount = parseFloat(this.referenceCommissionAmount.toFixed(2));
                }
                this.syncTotalCommissionPercentageFromAmount();
                this.applySuggestedRentalCommissionSplit();
                return;
            }

            this.recalculateCommissionAmountsFromPercentages();
        },

        onCommissionChanged() {
            this.recalculateCommissions();
        },

        onTotalCommissionChanged() {
            this.hasEditedTotalCommissionAmount = true;
            this.operation.total_commission_percentage = this.normalizePercentage(this.operation.total_commission_percentage);
            this.operation.total_commission_amount = parseFloat(this.referenceCommissionAmountFor(this.operation.total_commission_percentage).toFixed(2));

            if (this.isRentalOperation) {
                this.applySuggestedRentalCommissionSplit(true);
                return;
            }

            if (this.sellersCommissions.length === 0) {
                this.operation.company_commission_percentage = 100;
                this.operation.company_commission_amount = this.totalCommissionAmount;
                return;
            }

            if (this.companyCommissionPct > 100) {
                this.operation.company_commission_percentage = 100;
                this.operation.company_commission_amount = this.totalCommissionAmount;
            }

            this.recalculateCommissions();
        },

        onTotalCommissionAmountChanged() {
            this.hasEditedTotalCommissionAmount = true;
            this.operation.total_commission_amount = this.normalizeMoney(this.operation.total_commission_amount);
            this.syncTotalCommissionPercentageFromAmount();

            if (this.isRentalOperation) {
                this.applySuggestedRentalCommissionSplit(true);
                return;
            }

            if (this.sellersCommissions.length === 0) {
                this.operation.company_commission_percentage = 100;
                this.operation.company_commission_amount = this.totalCommissionAmount;
            }
        },

        onCompanyCommissionChanged() {
            this.hasEditedCommissionDistribution = true;
            this.operation.company_commission_percentage = this.normalizePercentage(this.operation.company_commission_percentage);

            if (this.companyCommissionPct > 100) {
                this.operation.company_commission_percentage = 100;
            }

            this.recalculateCommissionAmountsFromPercentages();
        },

        onCompanyCommissionAmountChanged() {
            this.hasEditedCommissionDistribution = true;
            this.operation.company_commission_amount = this.normalizeMoney(this.operation.company_commission_amount);
            if (this.operation.company_commission_amount > this.totalCommissionAmount) {
                this.operation.company_commission_amount = this.totalCommissionAmount;
            }
            this.syncCompanyPercentageFromAmount();
        },

        onSellerPercentageChanged(seller, value) {
            this.hasEditedCommissionDistribution = true;
            seller.percentage = this.normalizePercentage(value);
            seller.amount = parseFloat(this.sellerCommissionAmount(seller.percentage).toFixed(2));
        },

        onSellerCommissionAmountChanged(seller) {
            this.onSellerAmountChanged(seller);
        },

        onSellerAmountChanged(seller) {
            this.hasEditedCommissionDistribution = true;
            seller.amount = this.normalizeMoney(seller.amount);
            seller.percentage = this.percentageFromCommissionAmount(seller.amount);
        },

        onAdministrativeMonthsChanged() {
            if (!this.isRentalOperation) {
                return;
            }

            this.recalculateCommissions();
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

        normalizeMoney(value) {
            const amount = parseFloat(String(value ?? '').replace(/,/g, ''));
            return Number.isFinite(amount) ? amount : 0;
        },

        parseMoneyInput(value) {
            const normalized = String(value ?? '')
                .replace(/\s+/g, '')
                .replace(/\$/g, '')
                .replace(/,/g, '');
            const amount = parseFloat(normalized);
            return Number.isFinite(amount) ? amount : 0;
        },

        formatInputWithCommas(value) {
            const raw = String(value ?? '').trim();
            if (raw === '') {
                return '';
            }

            const number = this.normalizeMoney(value);
            return new Intl.NumberFormat('en-US', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 2,
            }).format(number);
        },

        syncTotalCommissionPercentageFromAmount() {
            this.operation.total_commission_percentage = this.commissionBaseAmount > 0
                ? parseFloat(((this.totalCommissionAmount / this.commissionBaseAmount) * 100).toFixed(4))
                : 0;
        },

        syncCompanyPercentageFromAmount() {
            this.operation.company_commission_percentage = this.percentageFromCommissionAmount(this.operation.company_commission_amount);
        },

        percentageFromCommissionAmount(amount) {
            return this.totalCommissionAmount > 0
                ? parseFloat(((this.normalizeMoney(amount) / this.totalCommissionAmount) * 100).toFixed(4))
                : 0;
        },

        referenceCommissionAmountFor(percentage) {
            return this.commissionBaseAmount * (this.normalizePercentage(percentage) / 100);
        },

        sellerCommissionAmount(pct) {
            return this.totalCommissionAmount * (parseFloat(pct) || 0) / 100;
        },

        recalculateCommissionAmountsFromPercentages() {
            const normalizedCompanyPercentage = Math.min(100, Math.max(0, this.normalizePercentage(this.operation.company_commission_percentage)));
            this.operation.company_commission_percentage = normalizedCompanyPercentage;
            this.operation.company_commission_amount = parseFloat(this.sellerCommissionAmount(normalizedCompanyPercentage).toFixed(2));

            this.sellersCommissions = this.sellersCommissions.map(s => ({
                ...s,
                percentage: this.normalizePercentage(s.percentage),
                amount: parseFloat(this.sellerCommissionAmount(s.percentage).toFixed(2)),
            }));
        },

        formatAmount(val) {
            return (parseFloat(val) || 0).toFixed(2);
        },

        formatPercentage(val) {
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

                if (this.isRentalOperation) {
                    if (!this.operation.sellers.length) {
                        this.$toastr.e('Debes seleccionar al menos un asesor para el alquiler');
                        return false;
                    }

                    if (!this.operation.start_date) {
                        this.$toastr.e('Debes indicar la fecha inicio del alquiler');
                        return false;
                    }

                    if (!this.operation.fecha_corte) {
                        this.$toastr.e('Debes indicar la fecha corte del alquiler');
                        return false;
                    }

                    if (new Date(this.operation.fecha_corte) < new Date(this.operation.start_date)) {
                        this.$toastr.e('La fecha corte no puede ser menor que la fecha inicio');
                        return false;
                    }

                    if ((parseInt(this.operation.meses_adelanto, 10) || 0) < 0) {
                        this.$toastr.e('Los meses de adelanto no pueden ser negativos');
                        return false;
                    }

                    if ((parseInt(this.operation.mes_administrativo, 10) || 0) < 0) {
                        this.$toastr.e('El mes administrativo no puede ser negativo');
                        return false;
                    }

                    if (!this.operation.payment_frequency) {
                        this.$toastr.e('Debes indicar el tiempo de pago del alquiler');
                        return false;
                    }
                }

                if (this.totalAssignedCommissionAmount > this.totalCommissionAmount + 0.0001) {
                    this.$toastr.e('La distribución supera el monto total de comisión');
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
                    total_commission_percentage: this.totalCommissionPct,
                    total_commission_amount: this.totalCommissionAmount,
                    company_commission_percentage: this.companyCommissionPct,
                    company_commission_amount: this.companyCommissionAmount,
                    sellers_commissions: this.sellersCommissions.map(s => ({
                        id: s.id,
                        percentage: this.normalizePercentage(s.percentage),
                        amount: this.normalizeMoney(s.amount),
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
                        meses_adelanto: 0,
                        mes_administrativo: 0,
                        fecha_corte: "",
                        payment_frequency: "",
                        total_commission_percentage: 5,
                        total_commission_amount: 0,
                        company_commission_percentage: 5,
                        company_commission_amount: 0,
                        sellers: [],
                        notes: "",
                    };
                    this.isExternalProperty = false;
                    this.useManualOwnerClient = false;
                    this.useManualBuyerClient = false;
                    this.hasEditedTotalCommissionAmount = false;
                    this.hasEditedCommissionDistribution = false;
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
