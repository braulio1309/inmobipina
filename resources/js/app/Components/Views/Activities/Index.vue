<template>
    <div class="content-wrapper">
        <div class="row">
            <div class="col-sm-12 col-md-6">
                <app-breadcrumb :page-title="'Listado de actividades'"/>
            </div>
            <div class="col-sm-12 col-md-6 breadcrumb-side-button">
                <div class="float-md-right mb-3 mb-sm-3 mb-md-0">
                    <button type="button"
                            class="btn btn-success btn-with-shadow mr-2"
                            @click="exportActivities">
                        <i class="fas fa-file-excel mr-1"></i> Exportar Excel
                    </button>
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

        <activity-detail-modal
            v-if="isDetailModalActive"
            :activity-id="detailActivityId"
            @close-modal="closeDetailModal"
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
import ActivityDetailModal from './ActivityDetail.vue';

export default {
    extends: CoreLibrary,
    name: "GridView",
    mixins: [TableHelpers],
    components: { ActivityDetailModal },
    data() {
        return {
            isAddEditModalActive: false,
            isDetailModalActive: false,
            deleteConfirmationModalActive: false,
            deleteLoader: false,
            selectedUrl: '',
            detailActivityId: null,
            tableId: 'grid-view-table2',
            rowData: {},
            activeFilters: {},
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
                            return value;
                        },
                        isVisible: true
                    },
                     {
                        title: 'Tipo',
                        type: 'custom-html',
                        key: 'type',
                        isVisible: true,
                        modifier: (value) => {
                            let ClassName = 'captación';

                            if (value === 'demostración') ClassName = `success`;
                            else if (value === 'publicidad') ClassName = `info`;
                            else if (value === 'reserva') ClassName = `warning`;

                            return `<span class="badge badge-sm badge-pill badge-${ClassName}">${value}</span>`;
                        }
                    },
                    {
                        title: 'Descripción',
                        type: 'text',
                        key: 'description',
                        isVisible: true,
                        modifier: (value, row) => {
                            return value; 
                        },
                    },
                    {
                        title: 'Fecha',
                        type: 'text',
                        key: 'date',
                        isVisible: true,
                        modifier: (value, row) => {
                            return value;
                        },
                    },
                    {
                        title: 'Resultado',
                        type: 'text',
                        key: 'result',
                        isVisible: true,
                        modifier: (value, row) => {
                            return value;
                        },
                    },
                ],
                actions: [
                    {
                        title: 'Ver detalle',
                        icon: 'eye',
                        type: 'none',
                    },
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
                        "option": ["today", "thisMonth", "last7Days", "lastYear"]
                    },
                    {
                        "title": 'Tipo de actividad',
                        "type": "checkbox",
                        "key": "type",
                        "option": [
                            {id: 'demostración', value: 'Demostración'},
                            {id: 'captación', value: 'Captación'},
                            {id: 'publicidad', value: 'Publicidad'},
                            {id: 'reserva', value: 'Reserva'},
                            {id: 'venta', value: 'Venta'},
                            {id: 'alquiler', value: 'Alquiler'},
                        ],
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
        this.searchAndSelectFilterOptions();
    },
    methods: {
        openAddEditModal() {
            this.isAddEditModalActive = true;
        },

        closeAddEditModal() {
            $("#activity-add-edit-modal").modal('hide');
            this.isAddEditModalActive = false;
            this.searchAndSelectFilterOptions();
            this.reSetData();
        },

        openDetailModal(activityId) {
            this.detailActivityId = activityId;
            this.isDetailModalActive = true;
        },

        closeDetailModal() {
            $("#activity-detail-modal").modal('hide');
            this.isDetailModalActive = false;
            this.detailActivityId = null;
        },

        getListAction(rowData, actionObj, active) {
            this.rowData = rowData;

            if (actionObj.title === 'Delete' || actionObj.title === this.$t('delete')) {
                this.openDeleteModal();
            } else if (actionObj.title === this.$t('edit')) {
                this.selectedUrl = `${actions.DATATABLE_DATA}/${rowData.id}`;
                this.openAddEditModal();
            } else if (actionObj.title === 'Ver detalle') {
                this.openDetailModal(rowData.id);
            }
        },

        openDeleteModal() {
            this.deleteConfirmationModalActive = true;
        },

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

        cancelled() {
            this.deleteConfirmationModalActive = false;
            this.reSetData();
        },

        reSetData() {
            this.rowData = {};
            this.selectedUrl = '';
        },

        searchAndSelectFilterOptions() {
            this.options.filters = this.options.filters.filter(filter => filter.key !== 'asesor');

            this.axiosGet('admin/auth/users').then(response => {
                const users = Array.isArray(response.data) ? response.data : (response.data.data || []);
                this.options.filters.push({
                    title: 'Asesores',
                    type: 'drop-down-filter',
                    key: 'asesor',
                    option: users.map(asesor => ({
                        id: asesor.id,
                        value: asesor.first_name
                            ? (asesor.first_name + ' ' + (asesor.last_name || '')).trim()
                            : (asesor.name || asesor.value || 'Sin nombre'),
                    }))
                });
            });
        },

        exportActivities() {
            // Build query string from current URL params (table uses query params)
            const urlParams = new URLSearchParams(window.location.search);
            // Also capture any active query params from the datatable's last request
            const exportUrl = '/activities/export?' + urlParams.toString();
            window.open(exportUrl, '_blank');
        },
    },
}
</script>

