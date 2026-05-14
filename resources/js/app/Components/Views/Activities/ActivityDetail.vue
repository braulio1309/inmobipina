<template>
    <app-modal :modal-id="modalId"
               modal-size="default"
               modal-alignment="top"
               @close-modal="$emit('close-modal')">
        <template slot="header">
            <h5 class="modal-title">Ficha de Actividad</h5>
            <button type="button" class="close outline-none" data-dismiss="modal" aria-label="Close"
                    @click.prevent="$emit('close-modal')">
                <span><app-icon name="x"></app-icon></span>
            </button>
        </template>

        <template slot="body">
            <app-overlay-loader v-if="preloader"/>

            <div v-if="!preloader && activity" class="activity-detail">

                <!-- Header badge + fecha -->
                <div class="d-flex align-items-center mb-3">
                    <span :class="['badge', 'badge-pill', 'mr-2', typeBadgeClass]" style="font-size:14px;padding:6px 14px;">
                        {{ activity.type }}
                    </span>
                    <span class="text-muted small">
                        <i class="fas fa-calendar-alt mr-1"></i>
                        {{ activity.date ? formatDate(activity.date) : 'Sin fecha' }}
                    </span>
                </div>

                <!-- Asesor -->
                <div class="detail-row" v-if="activity.user">
                    <i class="fas fa-user text-primary mr-2"></i>
                    <strong>Asesor:</strong>
                    <span class="ml-2">{{ fullName(activity.user) }}</span>
                </div>

                <div class="detail-row" v-if="activity.property">
                    <i class="fas fa-building text-primary mr-2"></i>
                    <strong>Propiedad:</strong>
                    <div class="ml-2 d-inline-block">
                        <div>{{ activity.property.title || `Propiedad #${activity.property.id}` }}</div>
                        <small class="text-muted" v-if="activity.property.address">{{ activity.property.address }}</small>
                    </div>
                </div>

                <!-- Descripción -->
                <div class="detail-row" v-if="activity.description">
                    <i class="fas fa-align-left text-primary mr-2"></i>
                    <strong>Descripción:</strong>
                    <p class="ml-2 mt-1 mb-0 text-muted">{{ activity.description }}</p>
                </div>

                <!-- Resultado -->
                <div class="detail-row" v-if="activity.result">
                    <i class="fas fa-check-circle text-success mr-2"></i>
                    <strong>Resultado:</strong>
                    <span class="ml-2">{{ activity.result }}</span>
                </div>

                <!-- Imagen de soporte -->
                <div class="detail-row" v-if="activity.image_path">
                    <i class="fas fa-image text-primary mr-2"></i>
                    <strong>Imagen de soporte:</strong>
                    <div class="mt-2">
                        <img :src="'/storage/' + activity.image_path"
                             class="img-fluid rounded shadow-sm"
                             style="max-height:250px;cursor:pointer;"
                             @click="openImage"
                             alt="Imagen de soporte"/>
                    </div>
                </div>

                <!-- Mapa de ubicación -->
                <div class="detail-row" v-if="hasLocation">
                    <i class="fas fa-map-marker-alt text-danger mr-2"></i>
                    <strong>Ubicación registrada:</strong>
                    <div class="small text-muted mb-1 mt-1">
                        Lat: {{ parseFloat(activity.latitude).toFixed(5) }},
                        Lng: {{ parseFloat(activity.longitude).toFixed(5) }}
                    </div>
                    <div ref="activityMap" class="activity-map mt-2"></div>
                </div>

                <div v-if="!hasLocation" class="text-muted small mt-2 detail-row">
                    <i class="fas fa-map-marker-alt mr-1"></i> Sin ubicación registrada
                </div>

            </div>
        </template>

        <template slot="footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal"
                    @click.prevent="$emit('close-modal')">
                Cerrar
            </button>
        </template>
    </app-modal>
</template>

<script>
import axios from 'axios';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

export default {
    name: 'ActivityDetailModal',

    props: {
        activityId: {
            type: [Number, String],
            required: true,
        }
    },

    data() {
        return {
            preloader: true,
            activity: null,
            modalId: 'activity-detail-modal',
            leafletMap: null,
        };
    },

    computed: {
        hasLocation() {
            return this.activity && this.activity.latitude && this.activity.longitude;
        },
        typeBadgeClass() {
            const map = {
                'demostración': 'badge-success',
                'reserva': 'badge-warning',
                'captación': 'badge-primary',
                'publicidad': 'badge-info',
                'venta': 'badge-info',
                'alquiler': 'badge-secondary',
            };
            const type = this.activity && this.activity.type ? this.activity.type.toLowerCase() : '';
            return map[type] || 'badge-light';
        }
    },

    mounted() {
        this.loadActivity();
    },

    beforeDestroy() {
        if (this.leafletMap) {
            this.leafletMap.remove();
            this.leafletMap = null;
        }
        // Dispose the Bootstrap modal so it can be re-opened cleanly next time
        try {
            $('#' + this.modalId).modal('dispose');
        } catch (e) {
            // ignore if dispose not available (Bootstrap 3 doesn't have dispose)
            $('#' + this.modalId).off('.bs.modal');
        }
    },

    methods: {
        async loadActivity() {
            try {
                const response = await axios.get(`/activities/${this.activityId}`);
                this.activity = response.data;
                this.preloader = false;
                if (this.hasLocation) {
                    this.$nextTick(() => this.initMap());
                }
            } catch (error) {
                this.preloader = false;
                console.error('Error cargando actividad:', error);
            }
        },

        initMap() {
            const mapEl = this.$refs.activityMap;
            if (!mapEl || this.leafletMap) return;

            const lat = parseFloat(this.activity.latitude);
            const lng = parseFloat(this.activity.longitude);

            this.leafletMap = L.map(mapEl).setView([lat, lng], 15);

            L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Street_Map/MapServer/tile/{z}/{y}/{x}', {
                attribution: 'Tiles &copy; Esri',
                maxZoom: 19,
            }).addTo(this.leafletMap);

            L.marker([lat, lng])
                .addTo(this.leafletMap)
                .bindPopup('Ubicación de la actividad')
                .openPopup();

            this.$nextTick(() => {
                this.leafletMap.invalidateSize();
            });
        },

        formatDate(dateStr) {
            try {
                return new Date(dateStr).toLocaleString('es-VE', { dateStyle: 'medium', timeStyle: 'short' });
            } catch (e) {
                return dateStr;
            }
        },

        fullName(user) {
            return ((user.first_name || '') + ' ' + (user.last_name || '')).trim() || 'N/A';
        },

        openImage() {
            window.open('/storage/' + this.activity.image_path, '_blank');
        },
    }
};
</script>

<style scoped>
.activity-detail {
    font-size: 14px;
}
.detail-row {
    padding: 10px 0;
    border-bottom: 1px solid #f0f0f0;
}
.detail-row:last-child {
    border-bottom: none;
}
.activity-map {
    height: 250px;
    width: 100%;
    border-radius: 8px;
    overflow: hidden;
}
</style>
