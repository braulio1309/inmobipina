<template>
<div class="container mt-4">

    <h3 class="mb-3">Registrar Propiedad</h3>

    <!-- Tabs -->
    <ul class="nav nav-tabs mb-3">
        <li class="nav-item" v-for="(tab, i) in tabs" :key="i">
            <button 
                class="nav-link" 
                :class="{active: activeTab === i}"
                @click="activeTab = i"
            >
                {{ tab.label }}
            </button>
        </li>
    </ul>

    <div class="tab-content border rounded p-3">

        <!-- TAB 1: Detalles -->
        <div v-if="activeTab === 0">
            <h5 class="mb-3">Detalles de la Propiedad</h5>

            <div class="mb-3">
                <label class="form-label">Título</label>
                <input 
                    v-model="property.title" 
                    type="text" 
                    class="form-control"
                    placeholder="Ingresa el título del anuncio"
                >
            </div>

            <div class="mb-3">
                <label class="form-label">Descripción</label>
                <textarea 
                    v-model="property.description" 
                    class="form-control" 
                    rows="4"
                    placeholder="Describe la propiedad"
                ></textarea>
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Baños</label>
                    <input 
                        v-model="property.bathrooms" 
                        type="number" 
                        class="form-control"
                        placeholder="Cantidad"
                    >
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Dormitorios</label>
                    <input 
                        v-model="property.bedrooms" 
                        type="number" 
                        class="form-control"
                        placeholder="Cantidad"
                    >
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Metros Cuadrados</label>
                    <input 
                        v-model="property.square_meters" 
                        type="number" 
                        class="form-control"
                        placeholder="Ej: 120"
                    >
                </div>
            </div>
        </div>

        <!-- TAB 2: Ubicación -->
        <div v-if="activeTab === 1">
            <h5 class="mb-3">Información de Ubicación</h5>

            <div class="mb-3">
                <label class="form-label">Dirección</label>
                <div class="position-relative">
                    <input 
                        v-model="property.address" 
                        type="text" 
                        class="form-control"
                        placeholder="Escribe el nombre de la calle o dirección"
                        @input="onAddressInput"
                        @keydown.down.prevent="moveAddressSuggestion(1)"
                        @keydown.up.prevent="moveAddressSuggestion(-1)"
                        @keydown.enter.prevent="selectAddressSuggestion(addressSuggestionIndex)"
                        autocomplete="off"
                    >
                    <ul v-if="addressSuggestions.length > 0" class="list-group position-absolute w-100" style="z-index:1000; top:100%;">
                        <li 
                            v-for="(s, i) in addressSuggestions"
                            :key="i"
                            class="list-group-item list-group-item-action py-2"
                            :class="{ active: i === addressSuggestionIndex }"
                            style="cursor:pointer"
                            @click="selectAddressSuggestion(i)"
                        >{{ s.display_name }}</li>
                    </ul>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">
                    <i class="fas fa-map-marker-alt mr-1 text-danger"></i>
                    Ubicación en el Mapa
                </label>
                <p class="text-muted small mb-2">Haz clic en el mapa para marcar la ubicación, o busca por nombre de calle arriba.</p>
                <div id="property-map" style="height: 350px; border-radius: 10px; border: 1px solid #dee2e6;"></div>
            </div>
        </div>

        <!-- TAB 3: Extras -->
        <div v-if="activeTab === 2">
            <h5 class="mb-3">Información Extra</h5>

            <div class="mb-3">
                <label class="form-label">Tipo de Propiedad</label>
                <app-input 
                    class=""
                    type="select"
                    v-model="property.type_sale"
                    :list="listForSelect"
                    placeholder="Selecciona el tipo de propiedad"
                />
            </div>

            <div class="mb-3">
                <label class="form-label">Precio (USD)</label>
                <input 
                    v-model="property.price" 
                    type="number" 
                    class="form-control"
                    placeholder="Ej: 45000"
                >
            </div>

            <div class="mb-3">
                <label class="form-label">¿Es exclusivo?</label>
                <div class="">
                    <app-input 
                        type="checkbox"
                        :list="exclusivity"
                        v-model="property.exclusivity"
                    />
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Tipo de Oferta</label>
                <app-input 
                    class=""
                    type="select"
                    v-model="property.type"
                    :list="listOffer"
                    placeholder="Selecciona el tipo de oferta"
                />
            </div>
        </div>

        <!-- TAB 4: Fotos -->
        <div v-if="activeTab === 3">
            <h5 class="mb-3">Imágenes del Inmueble</h5>

            <div v-if="!savedPropertyId" class="alert alert-info">
                Guarda primero la propiedad (botón inferior) y luego sube las imágenes aquí.
            </div>

            <div v-else>
                <div class="mb-3">
                    <label class="form-label" for="property-images-input">Seleccionar imágenes</label>
                    <input
                        id="property-images-input"
                        type="file"
                        multiple
                        accept="image/*"
                        class="form-control"
                        @change="onFilesSelected"
                    >
                    <small class="text-muted">Formatos permitidos: JPG, PNG, GIF, WEBP. Máx. 5MB por imagen.</small>
                </div>

                <div v-if="selectedFiles.length > 0" class="mb-3">
                    <div class="row">
                        <div class="col-md-3 mb-2" v-for="(preview, idx) in filePreviews" :key="idx">
                            <img :src="preview" class="img-thumbnail" style="height:120px;object-fit:cover;width:100%;">
                        </div>
                    </div>
                </div>

                <div v-if="uploadedImages.length > 0" class="mb-3">
                    <h6>Imágenes guardadas:</h6>
                    <div class="row">
                        <div class="col-md-3 mb-2" v-for="(img, idx) in uploadedImages" :key="idx">
                            <img :src="'/storage/' + img.path" class="img-thumbnail" style="height:120px;object-fit:cover;width:100%;">
                        </div>
                    </div>
                </div>

                <button class="btn btn-secondary" @click.prevent="uploadImages" :disabled="selectedFiles.length === 0">
                    Subir Imágenes
                </button>
            </div>
        </div>

        <!-- TAB 5: Exclusividad -->
        <div v-if="activeTab === 4">
            <h5 class="mb-3">Datos del Contrato de Exclusividad</h5>
            <p class="text-muted mb-3">
                Complete estos datos para generar el Contrato de Exclusividad de Bien Inmueble.
            </p>

            <h6 class="mb-2 text-primary">Datos del Propietario</h6>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Nombre del Propietario</label>
                    <input v-model="exclusivityData.propietario_nombre" type="text" class="form-control"
                        placeholder="Nombre completo">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Cédula de Identidad (C.I.)</label>
                    <input v-model="exclusivityData.propietario_ci" type="text" class="form-control"
                        placeholder="Ej: 12.345.678">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">R.I.F.</label>
                    <input v-model="exclusivityData.propietario_rif" type="text" class="form-control"
                        placeholder="Ej: V-123456789-0">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Correo Electrónico del Propietario</label>
                    <input v-model="exclusivityData.propietario_email" type="email" class="form-control"
                        placeholder="correo@ejemplo.com">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Teléfono del Propietario</label>
                    <input v-model="exclusivityData.propietario_telefono" type="text" class="form-control"
                        placeholder="Ej: 0414-123456">
                </div>
            </div>

            <h6 class="mb-2 text-primary mt-3">Descripción del Inmueble (para el contrato)</h6>
            <div class="row">
                <div class="col-md-12 mb-3">
                    <label class="form-label">Constituido por (descripción)</label>
                    <input v-model="exclusivityData.inmueble_descripcion" type="text" class="form-control"
                        placeholder="Ej: una casa de tres (3) habitaciones, dos (2) baños...">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Parroquia</label>
                    <input v-model="exclusivityData.parroquia" type="text" class="form-control"
                        placeholder="Ej: Cachamay">
                </div>
            </div>

            <h6 class="mb-2 text-primary mt-3">Datos del Registro (Protocolización)</h6>
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label">Número</label>
                    <input v-model="exclusivityData.registro_numero" type="text" class="form-control"
                        placeholder="Ej: 15">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Folio</label>
                    <input v-model="exclusivityData.registro_folio" type="text" class="form-control"
                        placeholder="Ej: 25">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Tomo</label>
                    <input v-model="exclusivityData.registro_tomo" type="text" class="form-control"
                        placeholder="Ej: 32 A-PRO">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Protocolo</label>
                    <input v-model="exclusivityData.registro_protocolo" type="text" class="form-control"
                        placeholder="Ej: Transcripción">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Año</label>
                    <input v-model="exclusivityData.registro_anio" type="text" class="form-control"
                        placeholder="Ej: 2020">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Fecha de Registro</label>
                    <input v-model="exclusivityData.registro_fecha" type="date" class="form-control">
                </div>
            </div>

            <h6 class="mb-2 text-primary mt-3">Datos del Contrato</h6>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Precio de Venta (USD)</label>
                    <input v-model="exclusivityData.precio_venta" type="number" step="0.01" class="form-control"
                        placeholder="Ej: 45000">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Fecha de Firma del Contrato</label>
                    <input v-model="exclusivityData.fecha_firma" type="date" class="form-control">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Fecha de Inicio</label>
                    <input v-model="exclusivityData.start_date" type="date" class="form-control">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Fecha de Fin (90 días)</label>
                    <input v-model="exclusivityData.end_date" type="date" class="form-control">
                </div>
            </div>

            <div v-if="savedPropertyId" class="mt-3">
                <a :href="'/property/' + savedPropertyId + '/exclusivity-pdf'" target="_blank" class="btn btn-success">
                    <i class="fas fa-file-pdf"></i> Descargar Contrato de Exclusividad PDF
                </a>
            </div>
        </div>

    </div>

    <!-- Botón final -->
    <div class="mt-3 text-end">
        <button class="btn btn-primary" @click="saveProperty" :disabled="saving">
            {{ saving ? 'Guardando...' : (savedPropertyId ? 'Actualizar Propiedad' : 'Guardar Propiedad') }}
        </button>
    </div>

</div>
</template>


<script>
import axios from "axios";
    import {FormMixin} from "../../../../../js/core/mixins/form/FormMixin.js";


export default {
    mixins: [FormMixin],

    data() {
        return {
            activeTab: 0,
            saving: false,
            savedPropertyId: null,
            selectedFiles: [],
            filePreviews: [],
            uploadedImages: [],
            leafletMap: null,
            leafletMarker: null,
            addressSuggestions: [],
            addressSuggestionIndex: -1,
            addressSearchTimeout: null,
            tabs: [
                { label: "Detalles" },
                { label: "Ubicación" },
                { label: "Extras" },
                { label: "Fotos" },
                { label: "Exclusividad" },
            ],

            property: {
                title: "",
                description: "",
                bathrooms: "",
                bedrooms: "",
                square_meters: "",
                address: "",
                map_lat: "",
                map_lng: "",
                type: "",
                price: "",
                exclusivity: false,
                type_sale: "",
            },

            exclusivityData: {
                propietario_nombre: "",
                propietario_ci: "",
                propietario_rif: "",
                propietario_email: "",
                propietario_telefono: "",
                inmueble_descripcion: "",
                parroquia: "Cachamay",
                registro_numero: "",
                registro_folio: "",
                registro_tomo: "",
                registro_protocolo: "",
                registro_anio: "",
                registro_fecha: "",
                precio_venta: "",
                fecha_firma: "",
                start_date: "",
                end_date: "",
            },

              listForSelect: [
                    {
                        id: '',
                        value: 'Elige uno'
                    },
                    {
                        id: 'venta',
                        value: 'Venta'
                    },
                    {
                        id: 'alquiler',
                        value: 'Alquiler'
                    },
                    {
                        id: 'ambos',
                        value: 'Ambos'
                    },
                   
                ],
                listOffer: [
                    {
                        id: '',
                        value: 'Elige uno'
                    },
                    {
                        id: 'Casa',
                        value: 'Casa'
                    },
                    {
                        id: 'Apartamento',
                        value: 'Apartamento'
                    },
                    {
                        id: 'Galpon',
                        value: 'Galpon'
                    },
                    {
                        id: 'Local',
                        value: 'Local'
                    },
                    {
                        id: 'Terreno',
                        value: 'Terreno'
                    },
                   
                ],
                exclusivity: [
                    {
                        id: 'Exclusividad',
                        value: 'Exclusividad'
                    },
                    
                   
                ],
        };
    },

    watch: {
        activeTab(newTab) {
            if (newTab === 1) {
                this.$nextTick(() => {
                    this.initMap();
                });
            }
        }
    },

    mounted() {
        const urlParams = new URLSearchParams(window.location.search);
        const propertyId = urlParams.get('id');
        if (propertyId) {
            this.savedPropertyId = propertyId;
            this.loadPropertyData(propertyId);
        }
    },

    methods: {
        async loadPropertyData(id) {
            try {
                const res = await axios.get(`/property/${id}`);
                const p = res.data;
                this.property.title = p.title || '';
                this.property.description = p.description || '';
                this.property.bathrooms = p.bathrooms || '';
                this.property.bedrooms = p.bedrooms || '';
                this.property.square_meters = p.square_meters || '';
                this.property.address = p.address || '';
                this.property.map_lat = p.map_lat || '';
                this.property.map_lng = p.map_lng || '';
                this.property.type = p.type || '';
                this.property.price = p.price || '';
                this.property.exclusivity = p.exclusivity || false;
                this.property.type_sale = p.type_sale || '';
                if (p.images && p.images.length) {
                    this.uploadedImages = p.images;
                }
            } catch (e) {
                console.error('Error al cargar la propiedad:', e);
            }
        },
        loadLeaflet() {
            return new Promise((resolve) => {
                if (window.L) {
                    resolve(window.L);
                    return;
                }
                // Load Leaflet CSS
                if (!document.getElementById('leaflet-css')) {
                    const link = document.createElement('link');
                    link.id = 'leaflet-css';
                    link.rel = 'stylesheet';
                    link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
                    document.head.appendChild(link);
                }
                // Load Leaflet JS
                const script = document.createElement('script');
                script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
                script.onload = () => resolve(window.L);
                document.head.appendChild(script);
            });
        },

        async initMap() {
            const mapEl = document.getElementById('property-map');
            if (!mapEl || this.leafletMap) return;

            const L = await this.loadLeaflet();

            // Default center: Puerto Ordaz, Bolívar, Venezuela
            const defaultLat = this.property.map_lat ? parseFloat(this.property.map_lat) : 8.2830;
            const defaultLng = this.property.map_lng ? parseFloat(this.property.map_lng) : -62.7244;
            const defaultZoom = this.property.map_lat ? 15 : 14;

            this.leafletMap = L.map('property-map').setView([defaultLat, defaultLng], defaultZoom);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                maxZoom: 19,
            }).addTo(this.leafletMap);

            // Place existing marker if coordinates are saved
            if (this.property.map_lat && this.property.map_lng) {
                this.leafletMarker = L.marker([defaultLat, defaultLng]).addTo(this.leafletMap);
            }

            // Click to set marker
            this.leafletMap.on('click', (e) => {
                const { lat, lng } = e.latlng;
                this.property.map_lat = lat.toFixed(6);
                this.property.map_lng = lng.toFixed(6);

                if (this.leafletMarker) {
                    this.leafletMarker.setLatLng([lat, lng]);
                } else {
                    this.leafletMarker = L.marker([lat, lng]).addTo(this.leafletMap);
                }
            });
        },

        onAddressInput() {
            clearTimeout(this.addressSearchTimeout);
            this.addressSuggestionIndex = -1;
            if (!this.property.address || this.property.address.length < 3) {
                this.addressSuggestions = [];
                return;
            }
            this.addressSearchTimeout = setTimeout(() => {
                this.searchAddress(this.property.address);
            }, 400);
        },

        async searchAddress(query) {
            try {
                const response = await fetch(
                    `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&countrycodes=ve&limit=5&viewbox=-63.1,-62.3,8.0,8.6&bounded=1`,
                    { headers: { 'Accept-Language': 'es' } }
                );
                const data = await response.json();
                this.addressSuggestions = data;
            } catch (e) {
                this.addressSuggestions = [];
            }
        },

        moveAddressSuggestion(direction) {
            const len = this.addressSuggestions.length;
            if (len === 0) return;
            this.addressSuggestionIndex = (this.addressSuggestionIndex + direction + len) % len;
        },

        selectAddressSuggestion(index) {
            const suggestion = this.addressSuggestions[index >= 0 ? index : 0];
            if (!suggestion) return;
            this.property.address = suggestion.display_name;
            this.property.map_lat = parseFloat(suggestion.lat).toFixed(6);
            this.property.map_lng = parseFloat(suggestion.lon).toFixed(6);
            this.addressSuggestions = [];
            this.addressSuggestionIndex = -1;

            if (this.leafletMap) {
                const lat = parseFloat(suggestion.lat);
                const lng = parseFloat(suggestion.lon);
                this.leafletMap.setView([lat, lng], 16);
                if (this.leafletMarker) {
                    this.leafletMarker.setLatLng([lat, lng]);
                } else {
                    const L = window.L;
                    if (L) this.leafletMarker = L.marker([lat, lng]).addTo(this.leafletMap);
                }
            }
        },

        onFilesSelected(event) {
            this.selectedFiles = Array.from(event.target.files);
            this.filePreviews = [];
            this.selectedFiles.forEach(file => {
                const reader = new FileReader();
                reader.onload = (e) => this.filePreviews.push(e.target.result);
                reader.readAsDataURL(file);
            });
        },

        async uploadImages() {
            if (!this.savedPropertyId || this.selectedFiles.length === 0) return;
            const formData = new FormData();
            this.selectedFiles.forEach(file => formData.append('images[]', file));
            try {
                const res = await axios.post(`/property/${this.savedPropertyId}/images`, formData, {
                    headers: { 'Content-Type': 'multipart/form-data' }
                });
                this.uploadedImages = [...this.uploadedImages, ...res.data.data];
                this.selectedFiles = [];
                this.filePreviews = [];
                this.$toastr.s('Imágenes subidas correctamente');
            } catch (error) {
                console.error(error);
                this.$toastr.e('Error al subir las imágenes');
            }
        },

        async saveProperty() {
            this.saving = true;
            try {
                const hasExclusivityData = Object.values(this.exclusivityData)
                    .some(v => v !== "" && v !== null);

                const payload = {
                    ...this.property,
                    exclusivity_data: hasExclusivityData ? this.exclusivityData : undefined,
                };

                let response;
                if (this.savedPropertyId) {
                    response = await axios.post(`/edit/property/${this.savedPropertyId}`, payload);
                    this.$toastr.s('Propiedad actualizada exitosamente');
                } else {
                    response = await axios.post("/property/create", payload);
                    this.savedPropertyId = response.data.data.id;
                    this.$toastr.s('Propiedad guardada exitosamente');

                    // Si tiene datos de exclusividad, descargar el PDF automáticamente
                    if (hasExclusivityData && this.savedPropertyId) {
                        window.location.href = '/property/' + this.savedPropertyId + '/exclusivity-pdf';
                    }
                }

                console.log(response.data);
            } catch (error) {
                console.error(error);
                this.$toastr.e("Hubo un error al guardar la propiedad");
            } finally {
                this.saving = false;
            }
        },
    },
};
</script>
