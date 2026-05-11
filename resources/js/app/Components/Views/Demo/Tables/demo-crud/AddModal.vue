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
                  enctype="multipart/form-data"
                :data-url='selectedUrl ? `edit/activities/${inputs.id}` : `activities/create`'>

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

                <!-- Descripción -->
                <div class="form-group row align-items-center">
                    <label for="inputs_description" class="col-sm-3 mb-0">
                        Descripción
                    </label>
                    <app-input id="inputs_description"
                               class="col-sm-9"
                               type="textarea"
                               v-model="inputs.description"
                               placeholder="Describe la actividad realizada"/>
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

                <!-- Imagen de soporte -->
                <div class="form-group row align-items-center">
                    <label class="col-sm-3 mb-0">
                        Imagen de soporte
                    </label>
                    <div class="col-sm-9">
                        <input type="file"
                               class="form-control-file"
                               accept="image/*"
                               @change="onImageChange"/>
                        <div v-if="imagePreview" class="mt-2">
                            <img :src="imagePreview" style="max-width:200px;max-height:120px;border-radius:6px;" alt="Preview"/>
                        </div>
                        <div v-else-if="inputs.image_path" class="mt-2">
                            <img :src="'/storage/' + inputs.image_path" style="max-width:200px;max-height:120px;border-radius:6px;" alt="Imagen actual"/>
                            <small class="text-muted d-block">Imagen actual</small>
                        </div>
                    </div>
                </div>

                <!-- Ubicación -->
                <div class="form-group row align-items-center">
                    <label class="col-sm-3 mb-0">
                        Ubicación
                    </label>
                    <div class="col-sm-9">
                        <div v-if="locationLoading" class="text-muted small">
                            <i class="fas fa-spinner fa-spin mr-1"></i> Obteniendo ubicación...
                        </div>
                        <div v-else-if="inputs.latitude && inputs.longitude" class="text-success small">
                            <i class="fas fa-map-marker-alt mr-1"></i>
                            Lat: {{ parseFloat(inputs.latitude).toFixed(5) }}, Lng: {{ parseFloat(inputs.longitude).toFixed(5) }}
                        </div>
                        <div v-else class="text-muted small">
                            <i class="fas fa-map-marker-alt mr-1"></i> Ubicación no disponible
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-secondary mt-1" @click="captureLocation">
                            <i class="fas fa-location-arrow mr-1"></i> Capturar ubicación actual
                        </button>
                        <input type="hidden" name="latitude" :value="inputs.latitude">
                        <input type="hidden" name="longitude" :value="inputs.longitude">
                    </div>
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
            inputs: {
                type: '',
                description: '',
                result: '',
                date: '',
                latitude: null,
                longitude: null,
                image_path: null,
            },
            imageFile: null,
            imagePreview: null,
            locationLoading: false,

            activityTypes: [
                {id: '', value: "Seleccione un tipo"},
                {id: 'demostración', value: "Demostración"},
                {id: 'captación', value: "Captación"},
                {id: 'reserva', value: "Reserva"},
                {id: 'venta', value: "Venta"},
                {id: 'alquiler', value: "Alquiler"},
            ],

            modalId: 'activity-add-edit-modal',
            modalTitle: 'Registrar Actividad',
        };
    },

    created() {
        if (this.selectedUrl) {
            this.modalTitle = "Editar Actividad";
            this.preloader = true;
        } else {
            // Auto-capture location when opening for new activity.
            // A non-silent call so the user sees a warning if it fails.
            this.captureLocation(false);
        }
    },

    methods: {
        onImageChange(event) {
            const file = event.target.files[0];
            if (!file) return;
            this.imageFile = file;
            const reader = new FileReader();
            reader.onload = (e) => { this.imagePreview = e.target.result; };
            reader.readAsDataURL(file);
        },

        captureLocation(silent = false) {
            if (!navigator.geolocation) {
                if (!silent) this.$toastr.w('La geolocalización no está disponible en este dispositivo.');
                return;
            }
            this.locationLoading = true;
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    this.inputs.latitude = position.coords.latitude;
                    this.inputs.longitude = position.coords.longitude;
                    this.locationLoading = false;
                    if (!silent) this.$toastr.s('Ubicación capturada correctamente.');
                },
                (error) => {
                    this.locationLoading = false;
                    if (!silent) this.$toastr.w('No se pudo obtener la ubicación: ' + error.message);
                },
                { enableHighAccuracy: true, timeout: 10000 }
            );
        },

        submit() {
            const formData = new FormData();
            formData.append('type', this.inputs.type || '');
            formData.append('description', this.inputs.description || '');
            formData.append('result', this.inputs.result || '');
            formData.append('date', this.inputs.date || '');
            if (this.inputs.latitude) formData.append('latitude', this.inputs.latitude);
            if (this.inputs.longitude) formData.append('longitude', this.inputs.longitude);
            if (this.imageFile) formData.append('image', this.imageFile);

            const isEdit = !!this.selectedUrl;
            const url = isEdit ? `/edit/activities/${this.inputs.id}` : '/activities/create';

            this.preloader = true;
            const token = document.head.querySelector('meta[name="csrf-token"]');
            const headers = { 'X-CSRF-TOKEN': token ? token.content : '' };

            import('axios').then(({ default: axios }) => {
                axios.post(url, formData, { headers })
                    .then(response => { this.afterSuccess(response); })
                    .catch(() => {
                        this.preloader = false;
                        this.$toastr.e('Error al guardar la actividad.');
                    });
            });
        },

        afterSuccess(response) {
            this.preloader = false;
            this.$toastr.s(response.data.message || 'Actividad guardada correctamente.');
            this.$hub.$emit('reload-' + this.tableId);
            this.$emit('close-modal');
        },

        afterSuccessFromGetEditData(response) {
            this.inputs = {
                ...response.data,
                latitude: response.data.latitude || null,
                longitude: response.data.longitude || null,
                image_path: response.data.image_path || null,
            };
            this.preloader = false;
        },
    },
};
</script>
