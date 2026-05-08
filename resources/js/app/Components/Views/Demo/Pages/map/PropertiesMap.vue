<template>
    <div ref="mapContainer" class="properties-map"></div>
</template>

<script>
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png';
import markerIcon from 'leaflet/dist/images/marker-icon.png';
import markerShadow from 'leaflet/dist/images/marker-shadow.png';
import { PROPERTY_MAP_DATA } from '../../../../../Config/ApiUrl';
import { FormMixin } from '../../../../../../core/mixins/form/FormMixin';

delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
    iconRetinaUrl: markerIcon2x,
    iconUrl: markerIcon,
    shadowUrl: markerShadow,
});

const PUERTO_ORDAZ_CENTER = {
    lat: 8.2830,
    lng: -62.7244,
    zoom: 13,
};

export default {
    name: 'PropertiesMap',
    mixins: [FormMixin],

    data() {
        return {
            leafletMap: null,
            leafletTileLayer: null,
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
            this.leafletTileLayer = null;
        }
    },

    methods: {
        initMap() {
            const mapEl = this.$refs.mapContainer;
            if (!mapEl || this.leafletMap) return;

            // Center on Puerto Ordaz, Bolívar, Venezuela (default)
            this.leafletMap = L.map(mapEl, {
                keyboard: false,
            }).setView([PUERTO_ORDAZ_CENTER.lat, PUERTO_ORDAZ_CENTER.lng], PUERTO_ORDAZ_CENTER.zoom);

            this.leafletTileLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Street_Map/MapServer/tile/{z}/{y}/{x}', {
                attribution: 'Tiles &copy; Esri',
                maxZoom: 19,
                detectRetina: true,
                updateWhenIdle: true,
                updateWhenZooming: false,
                keepBuffer: 6,
                crossOrigin: true,
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

                const marker = L.marker([lat, lng], {
                    keyboard: false,
                    autoPanOnFocus: false,
                }).addTo(this.leafletMap);

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

                marker.bindPopup(popupContent, {
                    autoPan: false,
                });
                this.markers.push(marker);
                bounds.push([lat, lng]);
            });

            if (bounds.length === 1) {
                this.leafletMap.setView(bounds[0], 15);
                this.refreshMapSize();
            } else if (bounds.length > 1) {
                this.leafletMap.fitBounds(bounds, { padding: [40, 40], maxZoom: 15 });
                this.refreshMapSize();
            } else {
                this.leafletMap.setView([PUERTO_ORDAZ_CENTER.lat, PUERTO_ORDAZ_CENTER.lng], PUERTO_ORDAZ_CENTER.zoom);
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

.properties-map.leaflet-container {
    background: #e9ecef;
}

.properties-map .leaflet-marker-pane img,
.properties-map .leaflet-marker-icon,
.properties-map .leaflet-marker-shadow {
    width: auto !important;
    height: auto !important;
    max-width: none !important;
    max-height: none !important;
}

.properties-map .leaflet-tile {
    max-width: none !important;
    max-height: none !important;
}
</style>
