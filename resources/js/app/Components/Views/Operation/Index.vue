<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="'Historial de operaciones'" :directory="$t('datatables')" :icon="'grid'"/>
        <div class="mb-primary col-12 col-sm-12 col-md-12 col-lg-12 col-xl-12">
            <app-table :id="'default-filter-table'" :options="options" :action="getAction"/>
        </div>
    </div>
</template>

<script>

    import {TableHelpers} from "../Demo/Tables/mixins/TableHelpers";
    import CoreLibrary from "../../../../../js/core/helpers/CoreLibrary";

    export default {
        name: "Operations",
        mixins: [TableHelpers],
        extends: CoreLibrary,
        data() {
            return {
                options: {
                    name: this.$t('default_filter'),
                    url: 'operations/listar',
                    showHeader: true,
                    showCount: true,
                    showClearFilter: true,
                    columns: [
                        {
                            title: 'id',
                            type: 'text',
                            key: 'id',
                            default: "",
                            isVisible: true,
                            modifier:(value, row)=>{
                                return row.id ? row.id : '';
                            }
                        },
                        {
                            title: 'sellers_names',
                            type: 'text',
                            key: 'sellers_names',
                            default: "",
                            isVisible: true,
                            modifier: (value, row) => {
                                return value
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
                                if (value == 'reserva')
                                    clase = 'warning'
                                if (value == 'exclusividad')
                                    clase = 'success'
                                return `<span class="badge badge-sm badge-pill badge-${clase}">${row.type}</span>`;
                            }
                        },
                        {
                            title: 'amount',
                            type: 'custom-html',
                            key: 'amount',
                            default: "",
                            isVisible: true,
                            modifier:(value, row)=>{
                                return (value)?'$'+row.amount: 'N/A';
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
                            title: 'Descargar formato',
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
            },
            getAction(rowData, actionObj, active) {

                this.$store.dispatch('setRowData', rowData);

                if (actionObj.title == this.$t('manage_role')) {

                    this.selectedUrl = `${actions.INVITE_USER}/${rowData.id}`;
                    this.operationForUserInvitation();

                } else if (actionObj.title == 'Descargar formato') {

                    this.confirmation.url = `${actions.USERS}/${rowData.id}`;
                    this.confirmation.tableId = this.userAndRoles.users.tableId;
                    this.openConfirmationModal();

                } else if(actionObj.title == this.$t('edit')) {
                    //this.selectedUrl = `${actions.UPDATE_USER_NAME}/${rowData.id}`;
                    //this.openUserModal();
                    console.log('epaaa')
                    this.$router.push({ name: 'EditUser', params: { id: rowData.id } });

                } else if (actionObj.title == this.$t('active')) {

                    this.changeUserStatus(1, rowData.id);

                } else if (actionObj.title == this.$t('de_activate')) {

                    this.changeUserStatus(2, rowData.id);
                }
            },
        }
    }
</script>
