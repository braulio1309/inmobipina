<template>
    <div class="content-wrapper">
        <div class="row">
            <div class="col-sm-12 col-md-6">
                <app-breadcrumb :page-title="'Listado de actividades'"/>
            </div>
            <div class="col-sm-12 col-md-6 breadcrumb-side-button">
                <div class="float-md-right mb-3 mb-sm-3 mb-md-0">
                    <button type="button"
                            class="btn btn-primary btn-with-shadow"
                            data-toggle="modal"
                            @click="openAddEditModal">
                        Registrar actividad
                    </button>
                </div>
            </div>
        </div>

        <app-table
            :id="tableId"
            :options="options"
            :card-view="true"
            @action="getListAction"
        />

        <app-add-modal
            v-if="isAddEditModalActive"
            :table-id="tableId"
            :selected-url="selectedUrl"
            @close-modal="closeAddEditModal"
        />

        <app-delete-modal
            v-if="deleteConfirmationModalActive"
            :preloader="deleteLoader"
            modal-id="demo-delete"
            @confirmed="confirmed"
            @cancelled="cancelled"
        />
    </div>
</template>

<script>
import * as actions from "../../../../../js/app/Config/ApiUrl";
import {TableHelpers} from "../Demo/Tables/mixins/TableHelpers";
import CoreLibrary from "../../../../../js/core/helpers/CoreLibrary";
import AppFunction from "../../../../../js/core/helpers/app/AppFunction";

export default {
    extends: CoreLibrary,
    name: "GridView",
    mixins: [TableHelpers],
    data() {
        return {
            isAddEditModalActive: false,
            deleteConfirmationModalActive: false,
            deleteLoader: false,
            selectedUrl: '',
            tableId: 'grid-view-table2',
            rowData: {},
            options: {
                cardView: true,
                cardViewComponent: 'grid-view',
                name: 'AdvanceTable',
                url: 'activities/listar',
                showHeader: true,
                columns: [
                    {
                        title: this.$t('user'),
                        type: 'media-object',
                        key: 'media-object',
                        mediaTitleKey: 'name',
                        mediaSubtitleKey: 'email',
                        default: AppFunction.getAppUrl('images/avatar-demo.jpg'),
                        modifier: (value, row) => {
                            return value; // imag url
                        },
                        isVisible: true
                    },
                     {
                        title: 'type',
                        type: 'custom-html',
                        key: 'type',
                        isVisible: true,
                        modifier: (value) => {
                            let ClassName = 'captación';

                            if (value === 'demostración') ClassName = `success`;
                            else if (value === 'reserva') ClassName = `warning`;

                            return `<span class="badge badge-sm badge-pill badge-${ClassName}">${value}</span>`;
                        }
                    },
                    {
                        title: 'description',
                        type: 'text',
                        key: 'description',
                        isVisible: true,
                        modifier: (value, row) => {
                            return value; 
                        },
                    },
                    {
                        title: 'date',
                        type: 'text',
                        key: 'date',
                        isVisible: true,
                        modifier: (value, row) => {
                            return value; // imag url
                        },
                    },
                    {
                        title: 'result',
                        type: 'text',
                        key: 'result',
                        isVisible: true,
                        modifier: (value, row) => {
                            return value; // imag url
                        },
                    },
                ],
                actions: [
                    {
                        title: this.$t('edit'),
                        icon: 'edit',
                        type: 'none',
                        component: 'app-add-modal',
                        modalId: 'demo-add-edit-Modal',

                    }, {
                        title: this.$t('delete'),
                        icon: 'trash',
                        type: 'none',
                        component: 'app-confirmation-modal',
                        modalId: 'demo-delete',
                    }
                ],
                filters: [
                    {
                        "title": 'Fecha',
                        "type": "range-picker",
                        "key": "date",
                        "option": ["today", "thisMonth", "last7Days", "nextYear"]
                    },
                    {
                        "title": 'Tipo de actividad',
                        "type": "checkbox",
                        "key": "status",
                        "option": [
                            {id: 'Demostración', value: 'Demostración'},
                            {id: 'Captación', value: 'Captación'},
                            {id: 'Reserva', value: 'Reserva'},
                            {id: 'Publicidad', value: 'Publicidad'},

                        ],
                    },
                    {
                        "title": 'Asesores',
                        "type": "drop-down-filter",
                        "key": "Busca por asesores",
                        "option": [],
                    },
                   
                ],
                showFilter: true,
                showSearch: true,
                showCount: true,
                showClearFilter: true,
                paginationType: "pagination",
                responsive: true,
                rowLimit: 10,
                showAction: true,
                orderBy: 'desc',
                actionType: "default",
            }
        }
    },

    created() {
        //this.options.columns = [...this.options.columns, this.actionObj];
        this.searchAndSelectFilterOptions();
    },
    methods: {
        /**
         * for open add edit modal
         */
        openAddEditModal() {
            this.isAddEditModalActive = true;
        },

        /**
         * for close add edit modal
         */
        closeAddEditModal() {
            $("#demo-add-edit-Modal").modal('hide');
            this.isAddEditModalActive = false;
            this.searchAndSelectFilterOptions();
            this.reSetData();
        },

        /**
         * $emit Form datatable action
         */
        getListAction(rowData, actionObj, active) {

            this.rowData = rowData;

            if (actionObj.title == 'Delete') {

                this.openDeleteModal();
            } else if (actionObj.title == this.$t('edit')) {

                this.selectedUrl = `${actions.DATATABLE_DATA}/${rowData.id}`;
                this.openAddEditModal();
            }
        },

        /**
         * for open confirmation modal
         */
        openDeleteModal() {
            this.deleteConfirmationModalActive = true;
        },

        /**
         * confirmed $emit Form confirmation modal
         */
        confirmed() {
            let url = `${actions.DATATABLE_DATA}/${this.rowData.id}`;
            this.deleteLoader = true;
            this.axiosDelete(url)
                .then(response => {
                    this.deleteLoader = false;
                    $("#demo-delete").modal('hide');
                    this.cancelled();
                    this.$toastr.s(response.data.message);
                    this.searchAndSelectFilterOptions();
                }).catch(({error}) => {

                //trigger after error
            }).finally(() => {
                this.$hub.$emit('reload-' + this.tableId);
            });
        },

        /**
         * cancelled $emit Form confirmation modal
         */
        cancelled() {
            this.deleteConfirmationModalActive = false;
            this.reSetData();
        },

        reSetData() {
            this.rowData = {};
            this.selectedUrl = '';
        },

        searchAndSelectFilterOptions() {
            this.axiosGet(actions.DATATABLE_SEARCH_SELECT).then(response => {

                let name = this.options.filters.find(element => element.title === this.$t('search_and_select'));
                name.option = response.data.map(name => {
                    return {
                        id: name.name,
                        value: name.name
                    }
                });
            });
        }
    },
}
</script>
