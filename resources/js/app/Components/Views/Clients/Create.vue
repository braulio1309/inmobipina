<template>
  <div class="container mt-4">
    <div class="card shadow">
      <div class="card-header bg-primary text-white">
        <h5 class="mb-0">{{ clientId ? 'Editar cliente' : 'Registrar nuevo cliente' }}</h5>
      </div>

      <div class="card-body">
        <form @submit.prevent="saveClient">

          <!-- Nombre -->
          <div class="mb-3">
            <label class="form-label">Nombre</label>
            <input type="text" v-model="client.name" class="form-control" required>
          </div>

          <!-- Email -->
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" v-model="client.email" class="form-control">
          </div>

          <!-- Teléfono -->
          <div class="mb-3">
            <label class="form-label">Teléfono</label>
            <input type="text" v-model="client.phone" class="form-control" required>
          </div>

          <!-- Asesor asignado -->
          <div class="mb-3">
            <label class="form-label">Asesor asignado</label>
            <app-input
              type="search-select"
              v-model="client.assigned_to"
              :list="agentsList"
              placeholder="Buscar asesor..."
            />
          </div>

          <!-- Medio de captación -->
          <div class="mb-3">
            <label class="form-label">Medio de captación</label>
            <app-input
              type="select"
              v-model="client.source"
              :list="sourceOptions"
              placeholder="¿Por qué medio llegó?"
            />
          </div>

          <!-- Estatus -->
          <div class="mb-3">
            <label class="form-label">Estatus</label>
            <app-input
              type="select"
              v-model="client.status"
              :list="statusOptions"
            />
          </div>

          <!-- Notas -->
          <div class="mb-3">
            <label class="form-label">Notas</label>
            <textarea
              class="form-control"
              v-model="client.notes"
              rows="4"
              placeholder="Notas adicionales..."
            ></textarea>
          </div>

          <!-- Botón -->
          <div class="mt-3 text-end">
            <button class="btn btn-success" type="submit">
              <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
              {{ clientId ? 'Actualizar Cliente' : 'Guardar Cliente' }}
            </button>
          </div>

        </form>
      </div>
    </div>
  </div>
</template>

<script>
import { FormMixin } from "../../../../../js/core/mixins/form/FormMixin.js";
import axios from "axios";

export default {
  name: "ClientCreateCard",
  mixins: [FormMixin],
  data() {
    return {
      loading: false,
      clientId: null,
      client: {
        name: "",
        email: "",
        phone: "",
        notes: "",
        assigned_to: "",
        source: "",
        status: "potencial",
      },
      sourceOptions: [
        { id: "", value: "Elige uno" },
        { id: "telefono", value: "Por teléfono" },
        { id: "instagram", value: "Instagram" },
        { id: "tu_inmueble", value: "Tu Inmueble" },
        { id: "pendon", value: "Pendón" },
      ],
      statusOptions: [
        { id: "potencial", value: "Potencial" },
        { id: "no potencial", value: "No potencial" },
        { id: "atendido", value: "Atendido" },
        { id: "cerrado", value: "Cerrado" },
      ],
      agentsList: [{ id: "", value: "Elige uno" }],
    };
  },
  async created() {
    await this.loadAgents();
    const clientId = new URLSearchParams(window.location.search).get('id');
    if (clientId) {
      this.clientId = clientId;
      await this.loadClient(clientId);
    }
  },
  methods: {
    async loadAgents() {
      try {
        const res = await axios.get("/admin/auth/users");
        const users = Array.isArray(res.data) ? res.data : (res.data.data || []);
        this.agentsList = [
          { id: "", value: "Elige uno" },
          ...users.map(a => ({
            id: a.id.toString(),
            value: (a.first_name || "") + " " + (a.last_name ?? ""),
          }))
        ];
      } catch (error) {
        console.error("Error cargando asesores", error);
      }
    },

    async loadClient(id) {
      try {
        const res = await axios.get(`/client/${id}`);
        const c = res.data;
        this.client = {
          name: c.name || "",
          email: c.email || "",
          phone: c.phone || "",
          notes: c.notes || "",
          assigned_to: c.assigned_to ? c.assigned_to.toString() : "",
          source: c.source || "",
          status: c.status || "potencial",
        };
      } catch (error) {
        console.error("Error cargando cliente", error);
      }
    },

    async saveClient() {
      this.loading = true;
      try {
        if (this.clientId) {
          await axios.post(`/edit/client/${this.clientId}`, this.client);
          this.$toastr.s("Cliente actualizado correctamente");
        } else {
          await axios.post("/client/create", this.client);
          this.$toastr.s("Cliente registrado correctamente");
          this.client = {
            name: "",
            email: "",
            phone: "",
            notes: "",
            assigned_to: "",
            source: "",
            status: "potencial",
          };
        }
      } catch (error) {
        console.error(error);
        this.$toastr.e("Error al guardar cliente");
      }
      this.loading = false;
    }
  }
};
</script>
