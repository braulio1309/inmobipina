<template>
    <modal :modal-id="modalId"
           :title="modalTitle"
           :preloader="preloader"
           @submit="submit"
           @close-modal="closeModal">
        
        <template slot="body">
            <app-overlay-loader v-if="preloader"/>

            <form class="mb-0"
                  :class="{'loading-opacity': preloader}"
                  ref="form"
                  :data-url='selectedUrl ? `activities/${inputs.id}` : `activities/create`'>

                <!-- Tipo de Actividad -->
                <div class="form-group row align-items-center">
                    <label for="inputs_type" class="col-sm-3 mb-0">
                        Tipo de Actividad
                    </label>
                    <app-input id="inputs_type"
                               class="col-sm-9"
                               type="select"
                               v-model="inputs.type"
                               :list="activityTypes"
                               :placeholder="'Seleccione un tipo'"
                               :required="true"/>
                </div>

                <!-- Cliente Involucrado -->
                <div class="form-group row align-items-center">
                    <label for="inputs_client" class="col-sm-3 mb-0">
                        Cliente Involucrado
                    </label>
                    <app-input id="inputs_client"
                               class="col-sm-9"
                               type="text"
                               v-model="inputs.client"
                               placeholder="Nombre del cliente"/>
                </div>

                <!-- Resultado -->
                <div class="form-group row align-items-center">
                    <label for="inputs_result" class="col-sm-3 mb-0">
                        Resultado
                    </label>
                    <app-input id="inputs_result"
                               class="col-sm-9"
                               type="text"
                               v-model="inputs.result"
                               placeholder="¿Cuál fue el resultado?"/>
                </div>

                <!-- Fecha -->
                <div class="form-group row align-items-center">
                    <label for="inputs_date" class="col-sm-3 mb-0">
                        Fecha
                    </label>
                    <app-input id="inputs_date"
                               class="col-sm-9"
                               type="date"
                               v-model="inputs.date"
                               :required="true"/>
                </div>

                <!-- Hora -->
                <div class="form-group row align-items-center">
                    <label for="inputs_time" class="col-sm-3 mb-0">
                        Hora
                    </label>
                    <app-input id="inputs_time"
                               class="col-sm-9"
                               type="time"
                               v-model="inputs.time"
                               :required="true"/>
                </div>
            </form>
        </template>
    </modal>
</template>

<script>
import { FormMixin } from "../../../../../../core/mixins/form/FormMixin.js";
import { ModalMixin } from "../../../../../Mixins/ModalMixin";

export default {
    name: "ActivityModal",
    mixins: [FormMixin, ModalMixin],

    props: {
        tableId: String
    },

    data() {
        return {
            preloader: false,
            inputs: {},

            activityTypes: [
                {id: '', value: "Seleccione un tipo"},
                {id: 'demostración', value: "Demostración"},
                {id: 'captación', value: "Captación"},
                {id: 'publicidad', value: "Publicidad"},
                {id: 'Otro', value: "Otro"},
            ],

            modalId: 'activity-add-edit-modal',
            modalTitle: 'Registrar Actividad',
        };
    },

    created() {
        if (this.selectedUrl) {
            this.modalTitle = "Editar Actividad";
            this.preloader = true;
        }
    },

    methods: {
        submit() {
            this.save(this.inputs);
        },

        afterSuccess(response) {
            this.$toastr.s(response.data.message);
            this.$hub.$emit('reload-' + this.tableId);
        },

        afterSuccessFromGetEditData(response) {
            this.inputs = response.data;
            this.preloader = false;
        },
    },
};
</script>
