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

                <div class="col-md-4 mb-3">
                    <label class="form-label">Estacionamientos</label>
                    <input 
                        v-model="property.parking_spots" 
                        type="number" 
                        class="form-control"
                        placeholder="Puestos de estacionamiento"
                        min="0"
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
                <label class="form-label">Tipo de Oferta</label>
                <app-input 
                    class=""
                    type="select"
                    v-model="property.type_sale"
                    :list="listForSelect"
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
                <label class="form-label">Tipo de Inmueble</label>
                <app-input 
                    class=""
                    type="select"
                    v-model="property.type"
                    :list="listOffer"
                    placeholder="Selecciona el tipo de inmueble"
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

        <!-- TAB 5: Documentos -->
        <div v-if="activeTab === 4">
            <h5 class="mb-3">Documentos del Inmueble</h5>

            <div v-if="!savedPropertyId" class="alert alert-info">
                Guarda primero la propiedad (botón inferior) y luego sube los documentos aquí.
            </div>

            <div v-else>
                <div class="mb-3">
                    <label class="form-label" for="property-documents-input">Seleccionar documentos</label>
                    <input
                        id="property-documents-input"
                        type="file"
                        multiple
                        accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif,.webp"
                        class="form-control"
                        @change="onDocumentsSelected"
                    >
                    <small class="text-muted">Formatos permitidos: PDF, DOC, DOCX, XLS, XLSX, imágenes. Máx. 10MB por archivo.</small>
                </div>

                <div v-if="selectedDocuments.length > 0" class="mb-3">
                    <ul class="list-group mb-2">
                        <li class="list-group-item d-flex align-items-center justify-content-between"
                            v-for="(file, idx) in selectedDocuments" :key="idx">
                            <span><i class="fas fa-file mr-2 text-secondary"></i>{{ file.name }}</span>
                            <small class="text-muted">{{ (file.size / 1024).toFixed(1) }} KB</small>
                        </li>
                    </ul>
                    <button class="btn btn-secondary btn-sm" @click.prevent="uploadDocuments">
                        <i class="fas fa-upload mr-1"></i> Subir Documentos
                    </button>
                </div>

                <div v-if="uploadedDocuments.length > 0" class="mt-3">
                    <h6>Documentos guardados:</h6>
                    <ul class="list-group">
                        <li class="list-group-item d-flex align-items-center justify-content-between"
                            v-for="(doc, idx) in uploadedDocuments" :key="idx">
                            <div class="d-flex align-items-center">
                                <i :class="docIcon(doc.mime_type)" class="mr-2 fa-lg text-secondary"></i>
                                <div>
                                    <div>{{ doc.name }}</div>
                                    <small class="text-muted">{{ doc.created_at ? doc.created_at.substring(0, 10) : '' }}</small>
                                </div>
                            </div>
                            <div class="d-flex gap-2">
                                <a
                                    v-if="doc.mime_type === 'application/pdf'"
                                    :href="'/storage/' + doc.path"
                                    target="_blank"
                                    class="btn btn-sm btn-outline-primary mr-1"
                                    title="Previsualizar"
                                >
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a
                                    :href="'/storage/' + doc.path"
                                    target="_blank"
                                    class="btn btn-sm btn-outline-secondary mr-1"
                                    :download="doc.name"
                                    title="Descargar"
                                >
                                    <i class="fas fa-download"></i>
                                </a>
                                <button
                                    class="btn btn-sm btn-outline-danger"
                                    @click.prevent="deleteDocument(doc, idx)"
                                    title="Eliminar"
                                >
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- TAB 6: Exclusividad -->
        <div v-if="activeTab === 5">
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

            <div v-if="hasExclusivityData && isExclusivityDirty" class="alert alert-warning mt-3 mb-0">
                Detectamos cambios en los datos de exclusividad. Debes presionar Guardar o Actualizar Propiedad para volver a habilitar la descarga del contrato.
            </div>

            <div v-else-if="canDownloadExclusivityPdf" class="mt-3">
                <a :href="'/property/' + savedPropertyId + '/exclusivity-pdf'" target="_blank" class="btn btn-success">
                    <i class="fas fa-file-pdf"></i> Descargar Contrato de Exclusividad PDF
                </a>
            </div>
        </div>

    </div>

    <!-- Botón final -->
    <div class="mt-3 d-flex justify-content-end gap-2">
        <button
            v-if="canApproveProperty"
            class="btn btn-success mr-2"
            @click="approveProperty"
            :disabled="saving"
        >
            Aprobar propiedad
        </button>
        <button class="btn btn-primary" @click="saveProperty" :disabled="saving">
            {{ saving ? 'Guardando...' : (savedPropertyId ? 'Actualizar Propiedad' : 'Guardar Propiedad') }}
        </button>
    </div>

</div>
</template>


<script>
import axios from "axios";
import L from "leaflet";
import "leaflet/dist/leaflet.css";
import markerIcon2x from "leaflet/dist/images/marker-icon-2x.png";
import markerIcon from "leaflet/dist/images/marker-icon.png";
import markerShadow from "leaflet/dist/images/marker-shadow.png";
import {FormMixin} from "../../../../../js/core/mixins/form/FormMixin.js";


delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
    iconRetinaUrl: markerIcon2x,
    iconUrl: markerIcon,
    shadowUrl: markerShadow,
});

const PUERTO_ORDAZ_CENTER = {
    lat: 8.2830,
    lng: -62.7244,
    zoom: 14,
};

const PUERTO_ORDAZ_BOUNDS = {
    xmin: -63.1,
    ymin: 8.0,
    xmax: -62.3,
    ymax: 8.6,
};


export default {
    mixins: [FormMixin],

    props: {
        isAdmin: {
            type: Boolean,
            default: false,
        }
    },

    data() {
        return {
            activeTab: 0,
            saving: false,
            savedPropertyId: null,
            selectedFiles: [],
            filePreviews: [],
            uploadedImages: [],
            selectedDocuments: [],
            uploadedDocuments: [],
            leafletMap: null,
            leafletTileLayer: null,
            leafletMarker: null,
            addressSuggestions: [],
            addressSuggestionIndex: -1,
            addressSearchTimeout: null,
            tabs: [
                { label: "Detalles" },
                { label: "Ubicación" },
                { label: "Extras" },
                { label: "Fotos" },
                { label: "Documentos" },
                { label: "Exclusividad" },
            ],

            property: {
                title: "",
                description: "",
                bathrooms: "",
                bedrooms: "",
                square_meters: "",
                parking_spots: "",
                address: "",
                map_lat: "",
                map_lng: "",
                type: "",
                price: "",
                status: "",
                approved_by: null,
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
            savedExclusivityData: null,

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
                    {
                        id: 'Townhouse',
                        value: 'Townhouse'
                    },
                    {
                        id: 'Oficina',
                        value: 'Oficina'
                    },
                    {
                        id: 'Finca',
                        value: 'Finca'
                    },
                    {
                        id: 'Consultorios',
                        value: 'Consultorios'
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
                    this.refreshMapSize();
                });
            }
        }
    },

    computed: {
        hasExclusivityData() {
            return Object.values(this.exclusivityData)
                .some(value => value !== "" && value !== null);
        },

        isExclusivityDirty() {
            if (!this.hasExclusivityData) {
                return false;
            }

            return JSON.stringify(this.normalizeExclusivityData(this.exclusivityData)) !== JSON.stringify(this.savedExclusivitySnapshot);
        },

        savedExclusivitySnapshot() {
            return this.savedExclusivityData
                ? this.normalizeExclusivityData(this.savedExclusivityData)
                : this.normalizeExclusivityData({});
        },

        canDownloadExclusivityPdf() {
            return Boolean(this.savedPropertyId) && this.hasExclusivityData && !this.isExclusivityDirty;
        },

        canApproveProperty() {
            return this.isAdmin && Boolean(this.savedPropertyId) && this.property.status === 'pending';
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
                const latestExclusivity = p.exclusivities && p.exclusivities.length ? p.exclusivities[0] : null;

                this.property.title = p.title || '';
                this.property.description = p.description || '';
                this.property.bathrooms = p.bathrooms || '';
                this.property.bedrooms = p.bedrooms || '';
                this.property.square_meters = p.square_meters || '';
                this.property.parking_spots = p.parking_spots || '';
                this.property.address = p.address || '';
                this.property.map_lat = p.map_lat || '';
                this.property.map_lng = p.map_lng || '';
                this.property.type = p.type || '';
                this.property.price = p.price || '';
                this.property.status = p.status || '';
                this.property.approved_by = p.approved_by || null;
                this.property.exclusivity = p.exclusivity || false;
                this.property.type_sale = p.type_sale || '';

                if (latestExclusivity) {
                    this.exclusivityData = {
                        ...this.exclusivityData,
                        propietario_nombre: latestExclusivity.propietario_nombre || '',
                        propietario_ci: latestExclusivity.propietario_ci || '',
                        propietario_rif: latestExclusivity.propietario_rif || '',
                        propietario_email: latestExclusivity.propietario_email || '',
                        propietario_telefono: latestExclusivity.propietario_telefono || '',
                        inmueble_descripcion: latestExclusivity.inmueble_descripcion || '',
                        parroquia: latestExclusivity.parroquia || 'Cachamay',
                        registro_numero: latestExclusivity.registro_numero || '',
                        registro_folio: latestExclusivity.registro_folio || '',
                        registro_tomo: latestExclusivity.registro_tomo || '',
                        registro_protocolo: latestExclusivity.registro_protocolo || '',
                        registro_anio: latestExclusivity.registro_anio || '',
                        registro_fecha: latestExclusivity.registro_fecha || '',
                        precio_venta: latestExclusivity.precio_venta || '',
                        fecha_firma: latestExclusivity.fecha_firma || '',
                        start_date: latestExclusivity.start_date || '',
                        end_date: latestExclusivity.end_date || '',
                    };

                    this.savedExclusivityData = { ...this.exclusivityData };
                } else {
                    this.savedExclusivityData = this.normalizeExclusivityData({});
                }

                if (p.images && p.images.length) {
                    this.uploadedImages = p.images;
                }

                if (p.documents && p.documents.length) {
                    this.uploadedDocuments = p.documents;
                }

                this.$nextTick(() => {
                    this.syncMapMarker();
                    if (this.activeTab === 1) {
                        this.initMap();
                        this.refreshMapSize();
                    }
                });
            } catch (e) {
                console.error('Error al cargar la propiedad:', e);
            }
        },
        initMap() {
            const mapEl = document.getElementById('property-map');
            if (!mapEl) return;

            if (this.leafletMap) {
                this.refreshMapSize();
                this.syncMapMarker();
                return;
            }
            // Default center: Puerto Ordaz, Bolívar, Venezuela
            const hasCoordinates = this.hasValidCoordinates(this.property.map_lat, this.property.map_lng);
            const defaultLat = hasCoordinates ? parseFloat(this.property.map_lat) : PUERTO_ORDAZ_CENTER.lat;
            const defaultLng = hasCoordinates ? parseFloat(this.property.map_lng) : PUERTO_ORDAZ_CENTER.lng;
            const defaultZoom = hasCoordinates ? 15 : PUERTO_ORDAZ_CENTER.zoom;

            this.leafletMap = L.map(mapEl).setView([defaultLat, defaultLng], defaultZoom);

            this.leafletTileLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Street_Map/MapServer/tile/{z}/{y}/{x}', {
                attribution: 'Tiles &copy; Esri',
                maxZoom: 19,
                detectRetina: true,
                updateWhenIdle: true,
                updateWhenZooming: false,
                keepBuffer: 6,
                crossOrigin: true,
            }).addTo(this.leafletMap);

            this.syncMapMarker();

            // Click to set marker
            this.leafletMap.on('click', (e) => {
                const { lat, lng } = e.latlng;
                this.property.map_lat = lat.toFixed(6);
                this.property.map_lng = lng.toFixed(6);
                this.syncMapMarker();
            });

            this.refreshMapSize();
        },

        hasValidCoordinates(lat, lng) {
            return Number.isFinite(parseFloat(lat)) && Number.isFinite(parseFloat(lng));
        },

        syncMapMarker() {
            if (!this.leafletMap || !this.hasValidCoordinates(this.property.map_lat, this.property.map_lng)) {
                return;
            }

            const lat = parseFloat(this.property.map_lat);
            const lng = parseFloat(this.property.map_lng);

            if (this.leafletMarker) {
                this.leafletMarker.setLatLng([lat, lng]);
            } else {
                this.leafletMarker = L.marker([lat, lng]).addTo(this.leafletMap);
            }

            this.leafletMap.setView([lat, lng], this.leafletMap.getZoom() < 15 ? 15 : this.leafletMap.getZoom());
        },

        refreshMapSize() {
            if (!this.leafletMap) {
                return;
            }

            this.$nextTick(() => {
                window.requestAnimationFrame(() => {
                    this.leafletMap.invalidateSize(false);
                });
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
                let suggestions = await this.searchAddressWithArcGis(query);

                if (!suggestions.length) {
                    suggestions = await this.searchAddressWithNominatim(query);
                }

                this.addressSuggestions = suggestions;
            } catch (e) {
                console.error('Error al buscar dirección:', e);
                this.addressSuggestions = [];
            }
        },

        async searchAddressWithArcGis(query) {
            const params = new URLSearchParams({
                f: 'json',
                text: query,
                maxSuggestions: '8',
                countryCode: 'VEN',
                location: `${PUERTO_ORDAZ_CENTER.lng},${PUERTO_ORDAZ_CENTER.lat}`,
                searchExtent: `${PUERTO_ORDAZ_BOUNDS.xmin},${PUERTO_ORDAZ_BOUNDS.ymin},${PUERTO_ORDAZ_BOUNDS.xmax},${PUERTO_ORDAZ_BOUNDS.ymax}`,
            });

            const response = await fetch(
                `https://geocode.arcgis.com/arcgis/rest/services/World/GeocodeServer/suggest?${params.toString()}`,
                {
                    headers: {
                        'Accept': 'application/json'
                    }
                }
            );

            if (!response.ok) {
                throw new Error(`ArcGIS suggest failed with status ${response.status}`);
            }

            const data = await response.json();

            return (data.suggestions || [])
                .filter((suggestion) => suggestion && suggestion.text)
                .map((suggestion) => ({
                    display_name: suggestion.text,
                    magicKey: suggestion.magicKey,
                    source: 'arcgis',
                }));
        },

        async searchAddressWithNominatim(query) {
            const response = await fetch(
                `https://nominatim.openstreetmap.org/search?format=jsonv2&q=${encodeURIComponent(query)}&countrycodes=ve&limit=5&viewbox=${PUERTO_ORDAZ_BOUNDS.xmin},${PUERTO_ORDAZ_BOUNDS.ymax},${PUERTO_ORDAZ_BOUNDS.xmax},${PUERTO_ORDAZ_BOUNDS.ymin}&bounded=1`,
                {
                    headers: {
                        'Accept': 'application/json',
                        'Accept-Language': 'es'
                    }
                }
            );

            if (!response.ok) {
                throw new Error(`Nominatim lookup failed with status ${response.status}`);
            }

            const data = await response.json();

            return data.map((candidate) => ({
                display_name: candidate.display_name,
                lat: String(candidate.lat),
                lon: String(candidate.lon),
                source: 'nominatim',
            }));
        },

        async resolveArcGisSuggestion(suggestion) {
            const params = new URLSearchParams({
                f: 'json',
                magicKey: suggestion.magicKey,
                SingleLine: suggestion.display_name,
                maxLocations: '1',
                outSR: '4326',
                location: `${PUERTO_ORDAZ_CENTER.lng},${PUERTO_ORDAZ_CENTER.lat}`,
                searchExtent: `${PUERTO_ORDAZ_BOUNDS.xmin},${PUERTO_ORDAZ_BOUNDS.ymin},${PUERTO_ORDAZ_BOUNDS.xmax},${PUERTO_ORDAZ_BOUNDS.ymax}`,
            });

            const response = await fetch(
                `https://geocode.arcgis.com/arcgis/rest/services/World/GeocodeServer/findAddressCandidates?${params.toString()}`,
                {
                    headers: {
                        'Accept': 'application/json'
                    }
                }
            );

            if (!response.ok) {
                throw new Error(`ArcGIS candidate lookup failed with status ${response.status}`);
            }

            const data = await response.json();
            const candidate = data.candidates && data.candidates[0];

            if (!candidate || !candidate.location) {
                return null;
            }

            return {
                display_name: candidate.address || suggestion.display_name,
                lat: String(candidate.location.y),
                lon: String(candidate.location.x),
            };
        },

        moveAddressSuggestion(direction) {
            const len = this.addressSuggestions.length;
            if (len === 0) return;
            this.addressSuggestionIndex = (this.addressSuggestionIndex + direction + len) % len;
        },

        async selectAddressSuggestion(index) {
            const rawSuggestion = this.addressSuggestions[index >= 0 ? index : 0];
            if (!rawSuggestion) return;

            let suggestion = rawSuggestion;

            if (rawSuggestion.source === 'arcgis' && rawSuggestion.magicKey) {
                try {
                    const resolvedSuggestion = await this.resolveArcGisSuggestion(rawSuggestion);

                    if (resolvedSuggestion) {
                        suggestion = resolvedSuggestion;
                    }
                } catch (e) {
                    console.error('Error al resolver sugerencia:', e);
                }
            }

            if (!suggestion.lat || !suggestion.lon) {
                return;
            }

            this.property.address = suggestion.display_name;
            this.property.map_lat = parseFloat(suggestion.lat).toFixed(6);
            this.property.map_lng = parseFloat(suggestion.lon).toFixed(6);
            this.addressSuggestions = [];
            this.addressSuggestionIndex = -1;

            if (this.leafletMap) {
                const lat = parseFloat(suggestion.lat);
                const lng = parseFloat(suggestion.lon);
                this.leafletMap.setView([lat, lng], 16);
                this.syncMapMarker();
                if (this.leafletMarker) {
                    this.leafletMarker.setLatLng([lat, lng]);
                } else {
                    this.leafletMarker = L.marker([lat, lng]).addTo(this.leafletMap);
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

        onDocumentsSelected(event) {
            this.selectedDocuments = Array.from(event.target.files);
        },

        async uploadDocuments() {
            if (!this.savedPropertyId || this.selectedDocuments.length === 0) return;
            const formData = new FormData();
            this.selectedDocuments.forEach(file => formData.append('documents[]', file));
            try {
                const res = await axios.post(`/property/${this.savedPropertyId}/documents`, formData, {
                    headers: { 'Content-Type': 'multipart/form-data' }
                });
                this.uploadedDocuments = [...this.uploadedDocuments, ...res.data.data];
                this.selectedDocuments = [];
                document.getElementById('property-documents-input').value = '';
                this.$toastr.s('Documentos subidos correctamente');
            } catch (error) {
                console.error(error);
                this.$toastr.e('Error al subir los documentos');
            }
        },

        async deleteDocument(doc, idx) {
            if (!confirm(`¿Eliminar el documento "${doc.name}"?`)) return;
            try {
                await axios.delete(`/property/${this.savedPropertyId}/documents/${doc.id}`);
                this.uploadedDocuments.splice(idx, 1);
                this.$toastr.s('Documento eliminado');
            } catch (error) {
                console.error(error);
                this.$toastr.e('Error al eliminar el documento');
            }
        },

        docIcon(mimeType) {
            if (!mimeType) return 'fas fa-file';
            if (mimeType === 'application/pdf') return 'fas fa-file-pdf text-danger';
            if (mimeType.startsWith('image/')) return 'fas fa-file-image text-info';
            if (mimeType.includes('word') || mimeType.includes('document')) return 'fas fa-file-word text-primary';
            if (mimeType.includes('sheet') || mimeType.includes('excel')) return 'fas fa-file-excel text-success';
            return 'fas fa-file';
        },

        normalizeExclusivityData(source = {}) {
            return {
                propietario_nombre: source.propietario_nombre || '',
                propietario_ci: source.propietario_ci || '',
                propietario_rif: source.propietario_rif || '',
                propietario_email: source.propietario_email || '',
                propietario_telefono: source.propietario_telefono || '',
                inmueble_descripcion: source.inmueble_descripcion || '',
                parroquia: source.parroquia || 'Cachamay',
                registro_numero: source.registro_numero || '',
                registro_folio: source.registro_folio || '',
                registro_tomo: source.registro_tomo || '',
                registro_protocolo: source.registro_protocolo || '',
                registro_anio: source.registro_anio || '',
                registro_fecha: source.registro_fecha || '',
                precio_venta: source.precio_venta || '',
                fecha_firma: source.fecha_firma || '',
                start_date: source.start_date || '',
                end_date: source.end_date || '',
            };
        },

        async saveProperty() {
            this.saving = true;
            try {
                const normalizedExclusivityData = this.normalizeExclusivityData(this.exclusivityData);

                const payload = {
                    ...this.property,
                    exclusivity_data: this.hasExclusivityData ? normalizedExclusivityData : undefined,
                };

                let response;
                if (this.savedPropertyId) {
                    response = await axios.post(`/edit/property/${this.savedPropertyId}`, payload);
                    this.$toastr.s('Propiedad actualizada exitosamente');
                } else {
                    response = await axios.post("/property/create", payload);
                    this.savedPropertyId = response.data.data.id;
                    this.$toastr.s('Propiedad guardada exitosamente');
                }

                this.savedExclusivityData = this.hasExclusivityData
                    ? { ...normalizedExclusivityData }
                    : this.normalizeExclusivityData({});

                console.log(response.data);
            } catch (error) {
                console.error(error);
                this.$toastr.e("Hubo un error al guardar la propiedad");
            } finally {
                this.saving = false;
            }
        },

        async approveProperty() {
            if (!this.savedPropertyId) {
                return;
            }

            this.saving = true;

            try {
                const response = await axios.patch(`/property/${this.savedPropertyId}/approve`, {
                    action: 'approve'
                });

                this.property.status = response.data?.property?.status || 'Disponible';
                this.property.approved_by = response.data?.property?.approved_by || true;
                this.$toastr.s(response.data?.message || 'Propiedad aprobada correctamente');
            } catch (error) {
                console.error(error);
                this.$toastr.e(error.response?.data?.message || 'No se pudo aprobar la propiedad');
            } finally {
                this.saving = false;
            }
        },
    },

    beforeDestroy() {
        if (this.leafletMap) {
            this.leafletMap.remove();
            this.leafletMap = null;
            this.leafletTileLayer = null;
            this.leafletMarker = null;
        }

        clearTimeout(this.addressSearchTimeout);
    }
};
</script>

<style>
#property-map.leaflet-container {
    background: #e9ecef;
}

#property-map .leaflet-marker-pane img,
#property-map .leaflet-marker-icon,
#property-map .leaflet-marker-shadow {
    width: auto !important;
    height: auto !important;
    max-width: none !important;
    max-height: none !important;
}

#property-map .leaflet-tile {
    max-width: none !important;
    max-height: none !important;
}

#property-map .leaflet-control-container .leaflet-top,
#property-map .leaflet-control-container .leaflet-bottom {
    z-index: 800;
}
</style>
