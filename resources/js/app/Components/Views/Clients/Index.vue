<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="'Listado de Clientes'" :directory="$t('datatables')" :icon="'grid'"/>
        <div class="mb-primary col-12 col-sm-12 col-md-12 col-lg-12 col-xl-12">
            <app-table :id="'default-filter-table'" :options="options"/>
        </div>
    </div>
</template>

<script>

    import * as actions from "../../../../../js/app/Config/ApiUrl";
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
                    url: 'client/listar',
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
                            title: 'name',
                            type: 'text',
                            key: 'name',
                            default: "",
                            isVisible: true,
                            modifier: (value, row) => {
                                return row.name
                            }

                        },
                        {
                            title: 'email',
                            type: 'text',
                            key: 'email',
                            default: "",
                            isVisible: true,
                            modifier: (value, row) => {
                                return value
                            }

                        },
                        {
                            title: 'phone',
                            type: 'text',
                            key: 'phone',
                            default: "",
                            isVisible: true,
                            modifier: (value, row) => {
                                return value
                            }

                        },
                    ],
                    filters: [
                        
                       
                    ],
                    paginationType: "pagination",
                    responsive: true,
                    rowLimit: 50,
                    orderBy: 'desc',
                    showAction: false,
                    actions: [],
                },
            }
        },
        created() {
            //this.options.columns = [...this.tableColumns];
            this.searchAndSelectFilterOptions();
        },
        methods: {
            searchAndSelectFilterOptions() {
                this.axiosGet(actions.DATATABLE_SEARCH_SELECT).then(response => {
                    this.options.filters.push({
                        "title": 'Asesores',
                        "type": "drop-down-filter",
                        "key": "Propieades por asesor",
                        "option": [] = response.data.map(name => {
                            return {
                                id: name.name,
                                value: name.name
                            }
                        })
                    })
                });
            }
        }
    }
</script>
