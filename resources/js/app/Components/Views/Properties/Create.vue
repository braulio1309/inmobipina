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

        <!-- TAB 3: Detalles -->
        <div v-if="activeTab === 2">
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

            <div v-if="isAdmin" class="mb-3">
                <label class="form-label">Asesor del inmueble</label>
                <app-input
                    type="search-select"
                    v-model="property.agent_id"
                    :list="agentsList"
                    placeholder="Buscar asesor..."
                />
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

        <!-- TAB 4: Ubicación -->
        <div v-if="activeTab === 3">
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
                        @keydown.enter.prevent="onAddressEnter"
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

        <!-- TAB 5: Precio (Extras) -->
        <div v-if="activeTab === 4">
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

        <!-- TAB 6: Fotos -->
        <div v-if="activeTab === 5">
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
                        <div class="col-6 col-md-2 mb-2" v-for="(preview, idx) in filePreviews" :key="idx">
                            <img :src="preview" class="img-thumbnail" style="height:80px;object-fit:cover;width:100%;">
                        </div>
                    </div>
                </div>

                <div v-if="uploadedImages.length > 0" class="mb-3">
                    <h6>Imágenes guardadas:</h6>
                    <div class="row">
                        <div class="col-6 col-md-2 mb-2" v-for="(img, idx) in uploadedImages" :key="idx">
                            <img :src="'/storage/' + img.path" class="img-thumbnail" style="height:80px;object-fit:cover;width:100%;">
                        </div>
                    </div>
                </div>

                <button class="btn btn-secondary" @click.prevent="uploadImages" :disabled="selectedFiles.length === 0">
                    Subir Imágenes
                </button>
            </div>
        </div>

        <!-- TAB 2: Documentos -->
        <div v-if="activeTab === 1">
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

        <!-- TAB 1: Captación -->
        <div v-if="activeTab === 0">
            <h5 class="mb-3">Formulario de Captación</h5>
            <p class="text-muted mb-3">
                Este formulario es opcional. Completamos automáticamente la información base, pero puedes ajustarla manualmente cuando lo necesites.
            </p>

            <div class="card border-0 bg-light mb-4">
                <div class="card-body">
                    <h6 class="mb-3 text-primary">Datos autocompletados</h6>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label text-muted small mb-1">Asesor responsable</label>
                            <input v-model="captationData.asesor_responsable" type="text" class="form-control">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label text-muted small mb-1">Tipo de inmueble</label>
                            <input v-model="captationData.tipo_inmueble" type="text" class="form-control">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label text-muted small mb-1">Tipo de negociación</label>
                            <input v-model="captationData.tipo_negociacion" type="text" class="form-control">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label text-muted small mb-1">Precio cliente</label>
                            <input v-model="captationData.precio_cliente" type="number" step="0.01" class="form-control">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label text-muted small mb-1">Ubicación</label>
                            <input v-model="captationData.ubicacion" type="text" class="form-control">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label text-muted small mb-1">M2 construcción</label>
                            <input v-model="captationData.m2_construccion" type="number" step="0.01" class="form-control">
                        </div>
                        <div class="col-md-4 mb-0">
                            <label class="form-label text-muted small mb-1">Precio por m2</label>
                            <input v-model="captationData.precio_m2" type="number" step="0.01" class="form-control">
                        </div>
                        <div class="col-md-2 mb-0">
                            <label class="form-label text-muted small mb-1">Habitaciones</label>
                            <input v-model="captationData.cantidad_habitaciones" type="number" class="form-control">
                        </div>
                        <div class="col-md-2 mb-0">
                            <label class="form-label text-muted small mb-1">Baños</label>
                            <input v-model="captationData.cantidad_banos" type="number" class="form-control">
                        </div>
                        <div class="col-md-4 mb-0">
                            <label class="form-label text-muted small mb-1">Estacionamiento</label>
                            <input v-model="captationData.capacidad_estacionamiento" type="text" class="form-control">
                        </div>
                    </div>
                </div>
            </div>

            <hr class="my-4">
            <h6 class="mb-3 text-primary">Control interno</h6>
            <div class="row g-3 mb-3">
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Nro. de Publicación</label>
                    <input v-model="captationData.codigo_publicacion" type="text" class="form-control"
                        placeholder="Ej: INM-0012">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Fotos descargadas</label>
                    <select v-model="captationData.fotos_descargadas" class="form-control">
                        <option :value="null">Selecciona</option>
                        <option :value="true">Si</option>
                        <option :value="false">No</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Enviado para flyer</label>
                    <select v-model="captationData.enviado_para_flyer" class="form-control">
                        <option :value="null">Selecciona</option>
                        <option :value="true">Si</option>
                        <option :value="false">No</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Recepción de documentos en correo</label>
                    <select v-model="captationData.recepcion_documentos_correo" class="form-control">
                        <option :value="null">Selecciona</option>
                        <option :value="true">Si</option>
                        <option :value="false">No</option>
                    </select>
                </div>
            </div>
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Fecha de captación</label>
                    <input v-model="captationData.fecha_captacion" type="date" class="form-control">
                </div>
            </div>

            <h6 class="mb-2 text-primary mt-3">Cliente y autorización</h6>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Nombre y apellido</label>
                    <input v-model="captationData.cliente_nombre_apellido" type="text" class="form-control"
                        placeholder="Nombre completo">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Nro. de contacto</label>
                    <input v-model="captationData.cliente_nro_contacto" type="text" class="form-control"
                        placeholder="Ej: 0414-1234567">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Correo electrónico</label>
                    <input v-model="captationData.cliente_correo_electronico" type="email" class="form-control"
                        placeholder="correo@ejemplo.com">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Cédula de identidad</label>
                    <input v-model="captationData.autorizacion_cedula" type="text" class="form-control"
                        placeholder="Ej: V-12345678">
                </div>
                <div class="col-md-12 mb-3">
                    <label class="form-label d-block">Rol del cliente</label>
                    <div class="form-check form-check-inline">
                        <input v-model="captationData.cliente_es_propietario" class="form-check-input" type="checkbox" id="cliente-propietario">
                        <label class="form-check-label" for="cliente-propietario">Propietario</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input v-model="captationData.cliente_es_apoderado" class="form-check-input" type="checkbox" id="cliente-apoderado">
                        <label class="form-check-label" for="cliente-apoderado">Apoderado</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input v-model="captationData.cliente_es_encargado" class="form-check-input" type="checkbox" id="cliente-encargado">
                        <label class="form-check-label" for="cliente-encargado">Encargado</label>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Tipo de cliente (asignado/referido)</label>
                    <input v-model="captationData.tipo_cliente" type="text" class="form-control"
                        placeholder="Ej: Referido">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Nacionalidad</label>
                    <input v-model="captationData.autorizacion_nacionalidad" type="text" class="form-control"
                        placeholder="Ej: Venezolano(a)">
                </div>
                <div class="col-md-12">
                    <div class="alert alert-secondary mb-0 py-2">
                        La autorización se arma automáticamente con el nombre del cliente, su rol, la dirección, la descripción, el precio y la comisión ya cargados en la propiedad.
                    </div>
                </div>
            </div>

            <h6 class="mb-2 text-primary mt-3">Detalles del inmueble</h6>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Punto de referencia</label>
                    <input v-model="captationData.punto_referencia" type="text" class="form-control"
                        placeholder="Referencia cercana">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">m2 terreno</label>
                    <input v-model="captationData.m2_terreno" type="number" step="0.01" class="form-control">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Nivel/Piso</label>
                    <input v-model="captationData.nivel_piso" type="text" class="form-control">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Cocina</label>
                    <input v-model="captationData.cocina" type="text" class="form-control">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Teléfono</label>
                    <input v-model="captationData.telefono" type="text" class="form-control">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Tipo de internet</label>
                    <input v-model="captationData.tipo_internet" type="text" class="form-control">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Closet</label>
                    <input v-model="captationData.closet" type="text" class="form-control">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Estudio</label>
                    <input v-model="captationData.estudio" type="text" class="form-control">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Acabados</label>
                    <input v-model="captationData.acabados" type="text" class="form-control">
                </div>
                <div class="col-md-2 mb-3">
                    <label class="form-label">Listo p/habitar</label>
                    <select v-model="captationData.listo_para_habitar" class="form-control">
                        <option :value="null">-</option>
                        <option :value="true">Si</option>
                        <option :value="false">No</option>
                    </select>
                </div>
                <div class="col-md-2 mb-3">
                    <label class="form-label">P/remodelar</label>
                    <select v-model="captationData.para_remodelar" class="form-control">
                        <option :value="null">-</option>
                        <option :value="true">Si</option>
                        <option :value="false">No</option>
                    </select>
                </div>
            </div>

            <h6 class="mb-2 text-primary mt-3">Documentación y observaciones</h6>
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label">Fecha de verificación</label>
                    <input v-model="captationData.fecha_verificacion" type="date" class="form-control">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Estado</label>
                    <input v-model="captationData.documentacion_estado" type="text" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Datos de registro</label>
                    <input v-model="captationData.datos_registro" type="text" class="form-control">
                </div>
                <div class="col-md-2 mb-3">
                    <label class="form-label">Hipoteca</label>
                    <select v-model="captationData.hipoteca" class="form-control">
                        <option :value="null">-</option>
                        <option :value="true">Si</option>
                        <option :value="false">No</option>
                    </select>
                </div>
                <div class="col-md-5 mb-3">
                    <label class="form-label">Banco</label>
                    <input v-model="captationData.banco" type="text" class="form-control">
                </div>
                <div class="col-md-5 mb-3">
                    <label class="form-label">Estado del trámite</label>
                    <input v-model="captationData.estado_tramite" type="text" class="form-control">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Ventajas y beneficios</label>
                <textarea v-model="captationData.ventajas_beneficios" rows="4" class="form-control"></textarea>
            </div>

            <div class="mb-3">
                <label class="form-label">Observaciones y/o recomendaciones</label>
                <textarea v-model="captationData.observaciones_recomendaciones" rows="4" class="form-control"></textarea>
            </div>

            <h6 class="mb-2 text-primary mt-3">Comercialización</h6>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Precio de inmobiliaria</label>
                    <input v-model="captationData.precio_inmobiliaria" type="number" step="0.01" class="form-control"
                        placeholder="0.00">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">% comisión</label>
                    <input v-model="captationData.porcentaje_comision" type="number" step="0.01" class="form-control"
                        placeholder="Ej: 5">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label text-muted">Autorización generada</label>
                    <input :value="`${captationData.autorizacion_nombre || 'Cliente'} • ${captationData.autorizacion_caracter || 'sin rol'} • ${captationData.autorizacion_precio || 'sin precio'}`" type="text" class="form-control" readonly>
                </div>
            </div>

            <h6 class="mb-2 text-primary mt-3">Medios autorizados</h6>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Instagram / Facebook</label>
                    <select v-model="captationData.medio_instagram_facebook" class="form-control">
                        <option :value="null">Selecciona</option>
                        <option :value="true">Si</option>
                        <option :value="false">No</option>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Pendón / Sticker</label>
                    <select v-model="captationData.medio_pendon_sticker" class="form-control">
                        <option :value="null">Selecciona</option>
                        <option :value="true">Si</option>
                        <option :value="false">No</option>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Video publicitario</label>
                    <select v-model="captationData.medio_video_publicitario" class="form-control">
                        <option :value="null">Selecciona</option>
                        <option :value="true">Si</option>
                        <option :value="false">No</option>
                    </select>
                </div>
            </div>

            <div v-if="hasCaptationData && isCaptationDirty" class="alert alert-warning mt-3 mb-0">
                Detectamos cambios en la ficha de captación. Debes presionar Guardar o Actualizar Propiedad para volver a habilitar la descarga del PDF.
            </div>

            <div v-else-if="canDownloadCaptationPdf" class="mt-3">
                <a :href="'/property/' + savedPropertyId + '/captation-pdf'" target="_blank" class="btn btn-success">
                    <i class="fas fa-file-pdf"></i> Descargar Formulario de Captación PDF
                </a>
            </div>
        </div>

        <!-- TAB 7: Exclusividad -->
        <div v-if="activeTab === 6">
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
    xmin: -62.85,
    ymin: 8.20,
    xmax: -62.55,
    ymax: 8.40,
};


export default {
    mixins: [FormMixin],

    props: {
        isAdmin: {
            type: Boolean,
            default: false,
        },
        currentUserName: {
            type: String,
            default: "",
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
            agentsList: [
                { id: "", value: "Elige uno" },
            ],
            tabs: [
                { label: "Captación" },
                { label: "Documentos" },
                { label: "Detalles" },
                { label: "Ubicación" },
                { label: "Precio (Extras)" },
                { label: "Fotos" },
                { label: "Exclusividad" },
            ],

            property: {
                agent_id: "",
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
            captationData: {
                fotos_descargadas: null,
                enviado_para_flyer: null,
                codigo_publicacion: "",
                recepcion_documentos_correo: null,
                fecha_captacion: "",
                asesor_responsable: "",
                tipo_inmueble: "",
                precio_inmobiliaria: "",
                precio_cliente: "",
                porcentaje_comision: "",
                tipo_negociacion: "",
                cliente_nombre_apellido: "",
                cliente_nro_contacto: "",
                cliente_es_propietario: false,
                cliente_es_apoderado: false,
                cliente_es_encargado: false,
                cliente_correo_electronico: "",
                tipo_cliente: "",
                ubicacion: "",
                punto_referencia: "",
                m2_terreno: "",
                m2_construccion: "",
                precio_m2: "",
                cantidad_habitaciones: "",
                cantidad_banos: "",
                nivel_piso: "",
                cocina: "",
                telefono: "",
                tipo_internet: "",
                capacidad_estacionamiento: "",
                closet: "",
                estudio: "",
                acabados: "",
                listo_para_habitar: null,
                para_remodelar: null,
                fecha_verificacion: "",
                documentacion_estado: "",
                datos_registro: "",
                hipoteca: null,
                banco: "",
                estado_tramite: "",
                ventajas_beneficios: "",
                observaciones_recomendaciones: "",
                autorizacion_nombre: "",
                autorizacion_nacionalidad: "Venezolano(a)",
                autorizacion_cedula: "",
                autorizacion_caracter: "",
                autoriza_venta: false,
                autoriza_alquiler: false,
                autorizacion_inmueble_constituido: "",
                autorizacion_ubicado_en: "",
                autorizacion_precio: "",
                autorizacion_comision: "",
                medio_instagram_facebook: null,
                medio_pendon_sticker: null,
                medio_video_publicitario: null,
            },
            savedExclusivityData: null,
            savedCaptationData: null,

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
            if (newTab === 3) {
                this.$nextTick(() => {
                    this.initMap();
                    this.refreshMapSize();
                });
            }
        },
        'property.type': 'syncDerivedCaptationData',
        'property.type_sale': 'syncDerivedCaptationData',
        'property.price': 'syncDerivedCaptationData',
        'property.square_meters': 'syncDerivedCaptationData',
        'property.address': 'syncDerivedCaptationData',
        'property.bedrooms': 'syncDerivedCaptationData',
        'property.bathrooms': 'syncDerivedCaptationData',
        'property.parking_spots': 'syncDerivedCaptationData',
        'property.agent_id': 'syncDerivedCaptationData',
        'property.description': 'syncDerivedCaptationData',
        'captationData.cliente_nombre_apellido': 'syncDerivedCaptationData',
        'captationData.porcentaje_comision': 'syncDerivedCaptationData',
        'captationData.cliente_es_propietario': 'syncDerivedCaptationData',
        'captationData.cliente_es_apoderado': 'syncDerivedCaptationData',
        'captationData.cliente_es_encargado': 'syncDerivedCaptationData'
    },

    computed: {
        hasCaptationData() {
            return Object.values(this.captationData)
                .some(value => this.hasMeaningfulCaptationValue(value));
        },

        isCaptationDirty() {
            if (!this.hasCaptationData) {
                return false;
            }

            return JSON.stringify(this.normalizeCaptationData(this.captationData)) !== JSON.stringify(this.savedCaptationSnapshot);
        },

        savedCaptationSnapshot() {
            return this.savedCaptationData
                ? this.normalizeCaptationData(this.savedCaptationData)
                : this.normalizeCaptationData({});
        },

        canDownloadCaptationPdf() {
            return Boolean(this.savedPropertyId) && this.hasCaptationData && !this.isCaptationDirty;
        },

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
        if (this.isAdmin) {
            this.loadAgents();
        }

        this.prefillCaptationData();

        const urlParams = new URLSearchParams(window.location.search);
        const propertyId = urlParams.get('id');
        if (propertyId) {
            this.savedPropertyId = propertyId;
            this.loadPropertyData(propertyId);
        }
    },

    methods: {
        async loadAgents() {
            try {
                const response = await this.axiosGet('admin/auth/users');
                const users = Array.isArray(response.data) ? response.data : (response.data.data || []);

                this.agentsList = [
                    { id: "", value: "Elige uno" },
                    ...users.map(user => ({
                        id: user.id ? user.id.toString() : '',
                        value: user.first_name
                            ? `${user.first_name} ${user.last_name || ''}`.trim()
                            : (user.name || user.value || `Usuario #${user.id}`),
                    }))
                ];
            } catch (error) {
                console.error('Error cargando asesores para propiedades:', error);
            }
        },

        async loadPropertyData(id) {
            try {
                const res = await axios.get(`/property/${id}`);
                const p = res.data;
                const existingCaptation = p.captation || null;
                const latestExclusivity = p.exclusivities && p.exclusivities.length ? p.exclusivities[0] : null;

                this.property.agent_id = p.agent_id ? p.agent_id.toString() : '';
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

                if (existingCaptation) {
                    this.captationData = this.normalizeCaptationData(existingCaptation);
                    this.savedCaptationData = this.normalizeCaptationData(existingCaptation);
                } else {
                    this.prefillCaptationData(p);
                    this.savedCaptationData = this.normalizeCaptationData({});
                }

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

                this.prefillCaptationData(p);

                if (p.images && p.images.length) {
                    this.uploadedImages = p.images;
                }

                if (p.documents && p.documents.length) {
                    this.uploadedDocuments = p.documents;
                }

                this.$nextTick(() => {
                    this.syncMapMarker();
                    if (this.activeTab === 3) {
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

        onAddressEnter() {
            if (this.addressSuggestions.length === 0) {
                return;
            }
            if (this.addressSuggestionIndex >= 0) {
                this.selectAddressSuggestion(this.addressSuggestionIndex);
            } else {
                // Highlight first item so the user can confirm with Enter again
                this.addressSuggestionIndex = 0;
            }
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

        hasMeaningfulCaptationValue(value) {
            if (value === true) {
                return true;
            }

            if (typeof value === 'number') {
                return !Number.isNaN(value);
            }

            if (typeof value === 'string') {
                return value.trim() !== '';
            }

            return false;
        },

        normalizeCaptationData(source = {}) {
            return {
                fotos_descargadas: source.fotos_descargadas ?? null,
                enviado_para_flyer: source.enviado_para_flyer ?? null,
                codigo_publicacion: source.codigo_publicacion || '',
                recepcion_documentos_correo: source.recepcion_documentos_correo ?? null,
                fecha_captacion: source.fecha_captacion || '',
                asesor_responsable: source.asesor_responsable || '',
                tipo_inmueble: source.tipo_inmueble || '',
                precio_inmobiliaria: source.precio_inmobiliaria ?? '',
                precio_cliente: source.precio_cliente ?? '',
                porcentaje_comision: source.porcentaje_comision ?? '',
                tipo_negociacion: source.tipo_negociacion || '',
                cliente_nombre_apellido: source.cliente_nombre_apellido || '',
                cliente_nro_contacto: source.cliente_nro_contacto || '',
                cliente_es_propietario: Boolean(source.cliente_es_propietario),
                cliente_es_apoderado: Boolean(source.cliente_es_apoderado),
                cliente_es_encargado: Boolean(source.cliente_es_encargado),
                cliente_correo_electronico: source.cliente_correo_electronico || '',
                tipo_cliente: source.tipo_cliente || '',
                ubicacion: source.ubicacion || '',
                punto_referencia: source.punto_referencia || '',
                m2_terreno: source.m2_terreno ?? '',
                m2_construccion: source.m2_construccion ?? '',
                precio_m2: source.precio_m2 ?? '',
                cantidad_habitaciones: source.cantidad_habitaciones || '',
                cantidad_banos: source.cantidad_banos || '',
                nivel_piso: source.nivel_piso || '',
                cocina: source.cocina || '',
                telefono: source.telefono || '',
                tipo_internet: source.tipo_internet || '',
                capacidad_estacionamiento: source.capacidad_estacionamiento || '',
                closet: source.closet || '',
                estudio: source.estudio || '',
                acabados: source.acabados || '',
                listo_para_habitar: source.listo_para_habitar ?? null,
                para_remodelar: source.para_remodelar ?? null,
                fecha_verificacion: source.fecha_verificacion || '',
                documentacion_estado: source.documentacion_estado || '',
                datos_registro: source.datos_registro || '',
                hipoteca: source.hipoteca ?? null,
                banco: source.banco || '',
                estado_tramite: source.estado_tramite || '',
                ventajas_beneficios: source.ventajas_beneficios || '',
                observaciones_recomendaciones: source.observaciones_recomendaciones || '',
                autorizacion_nombre: source.autorizacion_nombre || '',
                autorizacion_nacionalidad: source.autorizacion_nacionalidad || 'Venezolano(a)',
                autorizacion_cedula: source.autorizacion_cedula || '',
                autorizacion_caracter: source.autorizacion_caracter || '',
                autoriza_venta: Boolean(source.autoriza_venta),
                autoriza_alquiler: Boolean(source.autoriza_alquiler),
                autorizacion_inmueble_constituido: source.autorizacion_inmueble_constituido || '',
                autorizacion_ubicado_en: source.autorizacion_ubicado_en || '',
                autorizacion_precio: source.autorizacion_precio || '',
                autorizacion_comision: source.autorizacion_comision || '',
                medio_instagram_facebook: source.medio_instagram_facebook ?? null,
                medio_pendon_sticker: source.medio_pendon_sticker ?? null,
                medio_video_publicitario: source.medio_video_publicitario ?? null,
            };
        },

        buildAdvisorName(user = null) {
            if (this.isAdmin && this.property.agent_id) {
                const selectedAgent = this.agentsList.find(agent => agent.id === String(this.property.agent_id));

                if (selectedAgent && selectedAgent.value) {
                    return selectedAgent.value;
                }
            }

            if (user) {
                const name = [user.first_name, user.last_name].filter(Boolean).join(' ').trim();
                if (name) {
                    return name;
                }
            }

            return this.currentUserName || '';
        },

        calculatePricePerSquareMeter(price, squareMeters) {
            const totalPrice = parseFloat(price);
            const totalArea = parseFloat(squareMeters);

            if (!Number.isFinite(totalPrice) || !Number.isFinite(totalArea) || totalArea <= 0) {
                return '';
            }

            return (totalPrice / totalArea).toFixed(2);
        },

        preferEditableCaptationValue(currentValue, fallbackValue) {
            if (typeof currentValue === 'string') {
                return currentValue.trim() !== '' ? currentValue : fallbackValue;
            }

            if (typeof currentValue === 'number') {
                return Number.isNaN(currentValue) ? fallbackValue : currentValue;
            }

            return currentValue !== null && currentValue !== undefined && currentValue !== ''
                ? currentValue
                : fallbackValue;
        },

        deriveAuthorizationCharacter() {
            const roles = [];

            if (this.captationData.cliente_es_propietario) {
                roles.push('propietario');
            }

            if (this.captationData.cliente_es_apoderado) {
                roles.push('apoderado');
            }

            if (this.captationData.cliente_es_encargado) {
                roles.push('encargado');
            }

            return roles.join(' / ');
        },

        syncDerivedCaptationData(propertyData = null) {
            const source = propertyData || this.property;
            const creator = propertyData && propertyData.creator ? propertyData.creator : null;
            const advisorName = this.buildAdvisorName(creator);
            const today = new Date().toISOString().slice(0, 10);
            const propertyType = source.type || this.property.type || '';
            const propertyPrice = source.price || this.property.price || '';
            const propertyArea = source.square_meters || this.property.square_meters || '';
            const propertyAddress = source.address || this.property.address || '';
            const propertyDescription = source.description || this.property.description || '';
            const propertyTypeSale = source.type_sale || this.property.type_sale || '';
            const pricePerSquareMeter = this.calculatePricePerSquareMeter(propertyPrice, propertyArea);
            const authorizationCharacter = this.deriveAuthorizationCharacter();
            const normalizedTypeSale = String(propertyTypeSale).toLowerCase();

            this.captationData = {
                ...this.captationData,
                fecha_captacion: this.captationData.fecha_captacion || today,
                asesor_responsable: this.preferEditableCaptationValue(this.captationData.asesor_responsable, advisorName || ''),
                tipo_inmueble: this.preferEditableCaptationValue(this.captationData.tipo_inmueble, propertyType),
                precio_inmobiliaria: this.preferEditableCaptationValue(this.captationData.precio_inmobiliaria, propertyPrice),
                precio_cliente: this.preferEditableCaptationValue(this.captationData.precio_cliente, propertyPrice),
                porcentaje_comision: this.preferEditableCaptationValue(this.captationData.porcentaje_comision, 5),
                tipo_negociacion: this.preferEditableCaptationValue(this.captationData.tipo_negociacion, propertyTypeSale),
                ubicacion: this.preferEditableCaptationValue(this.captationData.ubicacion, propertyAddress),
                m2_construccion: this.preferEditableCaptationValue(this.captationData.m2_construccion, propertyArea),
                precio_m2: this.preferEditableCaptationValue(this.captationData.precio_m2, pricePerSquareMeter),
                cantidad_habitaciones: this.preferEditableCaptationValue(this.captationData.cantidad_habitaciones, source.bedrooms || this.property.bedrooms || ''),
                cantidad_banos: this.preferEditableCaptationValue(this.captationData.cantidad_banos, source.bathrooms || this.property.bathrooms || ''),
                capacidad_estacionamiento: this.preferEditableCaptationValue(this.captationData.capacidad_estacionamiento, source.parking_spots || this.property.parking_spots || ''),
                autorizacion_nombre: this.captationData.cliente_nombre_apellido || '',
                autorizacion_caracter: authorizationCharacter,
                autoriza_venta: ['venta', 'ambos'].includes(normalizedTypeSale),
                autoriza_alquiler: ['alquiler', 'ambos'].includes(normalizedTypeSale),
                autorizacion_inmueble_constituido: propertyDescription,
                autorizacion_ubicado_en: propertyAddress,
                autorizacion_precio: this.captationData.precio_cliente ? `${this.captationData.precio_cliente} USD` : (propertyPrice ? `${propertyPrice} USD` : ''),
                autorizacion_comision: this.captationData.porcentaje_comision ? `${this.captationData.porcentaje_comision}%` : '',
                autorizacion_nacionalidad: this.captationData.autorizacion_nacionalidad || 'Venezolano(a)',
            };
        },

        prefillCaptationData(propertyData = null) {
            this.syncDerivedCaptationData(propertyData);
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
                this.prefillCaptationData();

                const normalizedCaptationData = this.normalizeCaptationData(this.captationData);
                const normalizedExclusivityData = this.normalizeExclusivityData(this.exclusivityData);

                const payload = {
                    ...this.property,
                    captation_data: this.hasCaptationData ? normalizedCaptationData : undefined,
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

                this.savedCaptationData = this.hasCaptationData
                    ? { ...normalizedCaptationData }
                    : this.normalizeCaptationData({});

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
