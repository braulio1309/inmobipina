<template>
    <div class="content-wrapper">
        <div class="row">
            <div class="col-sm-12 col-md-6">
                <app-breadcrumb :page-title="'Alquileres Activos'" :directory="$t('datatables')" :icon="'home'"/>
            </div>
        </div>

        <div class="mb-primary col-12 col-sm-12 col-md-12 col-lg-12 col-xl-12">
            <app-table :id="'active-rentals-table'" :options="options"/>
        </div>
    </div>
</template>

<script>
import {TableHelpers} from "../Demo/Tables/mixins/TableHelpers";
import CoreLibrary from "../../../../../js/core/helpers/CoreLibrary";

export default {
    name: "ActiveRentalsProperties",
    mixins: [TableHelpers],
    extends: CoreLibrary,
    data() {
        return {
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
                ],
                filters: [],
                paginationType: 'pagination',
                responsive: true,
                rowLimit: 50,
                orderBy: 'desc',
                showAction: false,
                actions: [],
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
    },
};
</script>