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
                <input 
                    v-model="property.address" 
                    type="text" 
                    class="form-control"
                    placeholder="Dirección completa"
                >
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Latitud</label>
                    <input 
                        v-model="property.map_lat" 
                        type="text" 
                        class="form-control"
                        placeholder="Ej: 10.4928"
                    >
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Longitud</label>
                    <input 
                        v-model="property.map_lng" 
                        type="text" 
                        class="form-control"
                        placeholder="Ej: -66.8792"
                    >
                </div>
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

        <div v-if="activeTab === 3">
            <h5 class="mb-3">Imagenes</h5>

            <div class="card card-with-shadow border-0 mb-primary">
        <div class="card-header p-primary bg-transparent">
            <h5 class="card-title m-0">Imagenes del inmueble</h5>
        </div>

        <app-overlay-loader v-if="preloader"/>
        <div class="card-body">
            <form ref="form" data-url="/form/multiple-files" class="mb-0" :class="{'loading-opacity': preloader}">
                <div class="form-group row mb-0">
                    <label class="col-sm-2">
                        Zona de imagenes
                    </label>
                    <div class="col-sm-8">
                        <app-input type="dropzone"
                                   v-model="dropzone_files"/>
                        <small class="text-muted font-italic">
                            Solo formato imagen
                        </small>
                    </div>
                </div>
                <div class="mt-5 action-buttons">
                    <button type="submit" class="btn btn-primary mr-2" @click.prevent="submit">
                        Guardar imagenes
                    </button>
                </div>
            </form>
        </div>
    </div>
        </div>

    </div>

    <!-- Botón final -->
    <div class="mt-3 text-end">
        <button class="btn btn-primary" @click="saveProperty">
            Guardar Propiedad
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
            tabs: [
                { label: "Detalles" },
                { label: "Ubicación" },
                { label: "Extras" },
                { label: "Imagenes" },

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

    methods: {
        async saveProperty() {
            try {
                const response = await axios.post("/property/create", this.property);
                this.$toastr.s('Creado exitosamente');

                console.log(response.data);

                this.property = {
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
                };
                this.activeTab = 0;
            } catch (error) {
                console.error(error);
                alert("Hubo un error al guardar la propiedad");
            }
        },
    },
};
</script>
