<template>
    <div ref="mapContainer" class="properties-map"></div>
</template>

<script>
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { PROPERTY_MAP_DATA } from '../../../../../Config/ApiUrl';
import { FormMixin } from '../../../../../../core/mixins/form/FormMixin';

delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
    iconRetinaUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
    iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
    shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
});

export default {
    name: 'PropertiesMap',
    mixins: [FormMixin],

    data() {
        return {
            leafletMap: null,
            markers: [],
        };
    },

    mounted() {
        this.$nextTick(() => {
            this.initMap();
        });
    },

    beforeDestroy() {
        if (this.leafletMap) {
            this.leafletMap.remove();
            this.leafletMap = null;
        }
    },

    methods: {
        initMap() {
            const mapEl = this.$refs.mapContainer;
            if (!mapEl || this.leafletMap) return;

            // Center on Puerto Ordaz, Bolívar, Venezuela (default)
            this.leafletMap = L.map(mapEl).setView([8.2830, -62.7244], 12);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                maxZoom: 19,
            }).addTo(this.leafletMap);

            this.refreshMapSize();
            this.loadProperties();
        },

        refreshMapSize() {
            if (!this.leafletMap) {
                return;
            }

            this.$nextTick(() => {
                window.requestAnimationFrame(() => {
                    this.leafletMap.invalidateSize();
                });
            });
        },

        loadProperties() {
            this.axiosGet(PROPERTY_MAP_DATA)
                .then(response => {
                    const properties = response.data;
                    this.placeMarkers(properties);
                })
                .catch(err => {
                    console.error('Error al cargar propiedades del mapa:', err);
                });
        },

        placeMarkers(properties) {
            if (!this.leafletMap) return;

            // Clear existing markers
            this.markers.forEach(m => m.remove());
            this.markers = [];

            if (!properties || properties.length === 0) return;

            const bounds = [];

            properties.forEach(property => {
                const lat = parseFloat(property.map_lat);
                const lng = parseFloat(property.map_lng);
                if (isNaN(lat) || isNaN(lng)) return;

                const marker = L.marker([lat, lng]).addTo(this.leafletMap);

                const price = property.price
                    ? new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', minimumFractionDigits: 2 }).format(parseFloat(property.price))
                    : 'N/D';

                const popupContent = `
                    <div style="min-width:200px;">
                        <strong style="font-size:14px;">${property.title || 'Sin título'}</strong><br>
                        <span style="color:#6c757d;font-size:12px;">${property.address || 'Sin dirección'}</span><br>
                        <hr style="margin:6px 0;">
                        <table style="font-size:12px;width:100%;">
                            <tr><td><b>Tipo:</b></td><td>${property.type || '-'}</td></tr>
                            <tr><td><b>Oferta:</b></td><td>${property.type_sale || '-'}</td></tr>
                            <tr><td><b>Precio:</b></td><td>${price}</td></tr>
                            <tr><td><b>Estado:</b></td><td>${property.status || '-'}</td></tr>
                        </table>
                    </div>
                `;

                marker.bindPopup(popupContent);
                this.markers.push(marker);
                bounds.push([lat, lng]);
            });

            if (bounds.length > 0) {
                this.leafletMap.fitBounds(bounds, { padding: [40, 40], maxZoom: 14 });
                this.refreshMapSize();
            }
        },
    },
};
</script>

<style scoped>
.properties-map {
    min-height: 450px;
    height: 100%;
    width: 100%;
    border-radius: 0 0 8px 8px;
}
</style>
