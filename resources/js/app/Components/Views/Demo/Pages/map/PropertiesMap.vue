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

const PROPERTY_TYPE_COLORS = {
    'terreno':      { color: '#8B4513', label: 'Terreno' },
    'casa':         { color: '#1565C0', label: 'Casa' },
    'apartamento':  { color: '#2E7D32', label: 'Apartamento' },
    'local':        { color: '#E65100', label: 'Local Comercial' },
    'oficina':      { color: '#6A1B9A', label: 'Oficina' },
    'galpon':       { color: '#37474F', label: 'Galpón' },
    'finca':        { color: '#558B2F', label: 'Finca' },
};

const DEFAULT_MARKER_COLOR = '#607D8B';

function getTypeColor(type) {
    if (!type) return DEFAULT_MARKER_COLOR;
    const key = type.toLowerCase().trim();
    return (PROPERTY_TYPE_COLORS[key] || { color: DEFAULT_MARKER_COLOR }).color;
}

function createColoredIcon(color) {
    const svg = `
        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="38" viewBox="0 0 28 38">
            <path d="M14 0C6.268 0 0 6.268 0 14c0 9.917 14 24 14 24S28 23.917 28 14C28 6.268 21.732 0 14 0z"
                  fill="${color}" stroke="#fff" stroke-width="2"/>
            <circle cx="14" cy="14" r="5" fill="#fff" opacity="0.9"/>
        </svg>`;
    return L.divIcon({
        html: svg,
        className: '',
        iconSize: [28, 38],
        iconAnchor: [14, 38],
        popupAnchor: [0, -38],
    });
}

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
            this.addLegend();
            this.loadProperties();
        },

        addLegend() {
            const legend = L.control({ position: 'bottomright' });
            legend.onAdd = () => {
                const div = L.DomUtil.create('div', 'map-legend');
                div.style.cssText = 'background:white;padding:8px 12px;border-radius:8px;box-shadow:0 2px 6px rgba(0,0,0,.3);font-size:12px;line-height:1.8;';
                div.innerHTML = '<strong style="display:block;margin-bottom:4px;">Tipos de propiedad</strong>' +
                    Object.entries(PROPERTY_TYPE_COLORS).map(([, { color, label }]) =>
                        `<div><span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:${color};margin-right:6px;vertical-align:middle;"></span>${label}</div>`
                    ).join('') +
                    `<div><span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:${DEFAULT_MARKER_COLOR};margin-right:6px;vertical-align:middle;"></span>Otro</div>`;
                return div;
            };
            legend.addTo(this.leafletMap);
        },

        refreshMapSize() {
            if (!this.leafletMap) return;
            this.$nextTick(() => {
                window.requestAnimationFrame(() => {
                    this.leafletMap.invalidateSize();
                });
            });
        },

        loadProperties() {
            this.axiosGet(PROPERTY_MAP_DATA)
                .then(response => {
                    this.placeMarkers(response.data);
                })
                .catch(err => {
                    console.error('Error al cargar propiedades del mapa:', err);
                });
        },

        placeMarkers(properties) {
            if (!this.leafletMap) return;

            this.markers.forEach(m => m.remove());
            this.markers = [];

            if (!properties || properties.length === 0) return;

            const bounds = [];

            properties.forEach(property => {
                const lat = parseFloat(property.map_lat);
                const lng = parseFloat(property.map_lng);
                if (isNaN(lat) || isNaN(lng)) return;

                const color = getTypeColor(property.type);
                const icon = createColoredIcon(color);

                const marker = L.marker([lat, lng], {
                    icon,
                    keyboard: false,
                    autoPanOnFocus: false,
                }).addTo(this.leafletMap);

                const price = property.price
                    ? new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', minimumFractionDigits: 2 }).format(parseFloat(property.price))
                    : 'N/D';

                const typeLabel = property.type
                    ? (PROPERTY_TYPE_COLORS[property.type.toLowerCase()] || {}).label || property.type
                    : '-';

                const popupContent = `
                    <div style="min-width:200px;">
                        <strong style="font-size:14px;">${property.title || 'Sin título'}</strong><br>
                        <span style="color:#6c757d;font-size:12px;">${property.address || 'Sin dirección'}</span><br>
                        <hr style="margin:6px 0;">
                        <table style="font-size:12px;width:100%;">
                            <tr><td><b>Tipo:</b></td><td><span style="color:${color};font-weight:600;">● ${typeLabel}</span></td></tr>
                            <tr><td><b>Oferta:</b></td><td>${property.type_sale || '-'}</td></tr>
                            <tr><td><b>Precio:</b></td><td>${price}</td></tr>
                            <tr><td><b>Estado:</b></td><td>${property.status || '-'}</td></tr>
                        </table>
                    </div>
                `;

                marker.bindPopup(popupContent, { autoPan: false });
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

.properties-map .leaflet-tile {
    max-width: none !important;
    max-height: none !important;
}
</style>
