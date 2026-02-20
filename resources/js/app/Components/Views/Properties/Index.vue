<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="'Listado de Propiedades'" :directory="$t('datatables')" :icon="'grid'"/>
        <div class="mb-primary col-12 col-sm-12 col-md-12 col-lg-12 col-xl-12">
            <app-table :id="'default-filter-table'" :options="options"/>
        </div>
    </div>
</template>

<script>

    import {TableHelpers} from "../Demo/Tables/mixins/TableHelpers";
    import CoreLibrary from "../../../../../js/core/helpers/CoreLibrary";

    export default {
        name: "Properties",
        mixins: [TableHelpers],
        extends: CoreLibrary,
        data() {
            return {
                options: {
                    name: this.$t('default_filter'),
                    url: 'property/listar',
                    showHeader: true,
                    showCount: true,
                    showClearFilter: true,
                    columns: [
                        {
                            title: 'title',
                            type: 'text',
                            key: 'title',
                            default: "",
                            isVisible: true,
                            modifier:(value, row)=>{
                                return row.title ? row.title : '';
                            }
                        },
                        {
                            title: 'address',
                            type: 'text',
                            key: 'address',
                            default: "",
                            isVisible: true,
                            modifier:(value, row)=>{
                                return row.address;
                            }
                        },
                                                {
                            title: 'type',
                            type: 'custom-html',
                            key: 'type',
                            default: "",
                            isVisible: true,
                            modifier:(value, row)=>{
                                let clase = 'primary';
                                if (value == 'Casa')
                                    clase = 'warning'
                                if (value == 'Terreno')
                                    clase = 'success'
                                if (value == 'Galpon')
                                    clase = 'info'
                                if (value == 'Local')
                                    clase = 'dark'
                                return `<span class="badge badge-sm badge-pill badge-${clase}">${row.type}</span>`;
                            }
                        },

                        
                        {
                            title: 'square_meters',
                            type: 'custom-html',
                            key: 'square_meters',
                            default: "",
                            isVisible: true,
                            modifier:(value, row)=>{
                                return row.square_meters+' mt2';
                            }
                        },
                        {
                            title: 'price',
                            type: 'custom-html',
                            key: 'price',
                            default: "",
                            isVisible: true,
                            modifier:(value, row)=>{
                                return '$'+row.price;
                            }
                        },
                        {
                            title: 'type_sale',
                            type: 'custom-html',
                            key: 'type_sale',
                            default: "",
                            isVisible: true,
                            modifier:(value, row)=>{
                                let clase = 'primary'
                                if (value == 'alquiler')
                                    clase = 'info'
                                if (value == 'venta')
                                    clase = 'success'
                                
                                return `<span class="badge badge-sm badge-pill badge-${clase}">${row.type_sale}</span>`;
                            }
                        },
                        {
                            title: 'status',
                            type: 'custom-html',
                            key: 'status',
                            default: "",
                            isVisible: true,
                            modifier:(value, row)=>{
                                let clase = 'primary'
                                if (value == 'pending')
                                    clase = 'warning'
                                if (value == 'No disponible')
                                    clase = 'danger'
                                if (value == 'Vendido')
                                    clase = 'success'
                                
                                return `<span class="badge badge-sm badge-pill badge-${clase}">${row.status}</span>`;
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
                                {id: 'Apartamento', value: 'Apartamento'},
                                {id: 'Casa', value: 'Casa'},
                                {id: 'Terreno', value: 'Terreno'},
                                {id: 'Galpon', value: 'Galpon'},
                                {id: 'Local', value: 'Local'},
                            ],
                        },
                        {
                            "title": 'Tipo de venta',
                            "type": "checkbox",
                            "key": "type_sale",
                            "option": [
                                {id: 'venta', value: 'Venta'},
                                {id: 'alquiler', value: 'Alquiler'},
                                {id: 'ambos', value: 'Ambos'},
                            ],
                        },
                        {
                            "title": 'Estatus',
                            "type": "checkbox",
                            "key": "status",
                            "option": [
                                {id: 'pending', value: 'Pendiente'},
                                {id: 'Disponible', value: 'Disponible'},
                                {id: 'No disponible', value: 'No disponible'},
                                {id: 'Reservado', value: 'Reservado'},
                                {id: 'Vendido', value: 'Vendido'},
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
                            title: 'Editar',
                            type: 'none',
                        },
                        {
                            title: 'Compartir',
                            type: 'none',
                        },
                        {
                            title: 'Contrato',
                            type: 'none',
                        },
                    ],
                },
            }
        },
        created() {
            //this.options.columns = [...this.tableColumns];
            this.searchAndSelectFilterOptions();
        },
        methods: {
            getActionUser(rowData, actionObj, active) {

                this.$store.dispatch('setRowData', rowData);

                if (actionObj.title == this.$t('manage_role')) {

                    this.selectedUrl = `${actions.INVITE_USER}/${rowData.id}`;
                    this.operationForUserInvitation();

                } else if (actionObj.title == this.$t('delete')) {

                    this.confirmation.url = `${actions.USERS}/${rowData.id}`;
                    this.confirmation.tableId = this.userAndRoles.users.tableId;
                    this.openConfirmationModal();

                } else if(actionObj.title == this.$t('edit')) {
                    // Navigate to property edit page
                    this.$router.push({ name: 'EditUser', params: { id: rowData.id } });

                } else if (actionObj.title == 'Compartir') {
                    // Open the public share link in a new window
                    const shareUrl = `${window.location.origin}/property/share/${rowData.id}`;
                    window.open(shareUrl, '_blank');

                } else if (actionObj.title == 'Contrato') {
                    // Download exclusivity contract PDF
                    const pdfUrl = `${window.location.origin}/property/${rowData.id}/exclusivity-pdf`;
                    window.open(pdfUrl, '_blank');

                } else if (actionObj.title == this.$t('active')) {

                    this.changeUserStatus(1, rowData.id);

                } else if (actionObj.title == this.$t('de_activate')) {

                    this.changeUserStatus(2, rowData.id);
                }
            },
            searchAndSelectFilterOptions() {
                this.axiosGet("admin/auth/users")
                .then(response => {
                    console.log(response.data)
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
            }
        }
    }
</script>
