<template>
  <div class="container mt-4">
    <div class="card shadow">
      <div class="card-header bg-primary text-white">
        <h5 class="mb-0">Registrar nuevo cliente</h5>
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
            <input type="email" v-model="client.email" class="form-control" required>
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
              type="select"
              v-model="client.assigned_to"
              :list="agentsList"
              placeholder="Selecciona un asesor"
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
              Guardar Cliente
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
      client: {
        name: "",
        email: "",
        phone: "",
        notes: "",
      },
      clientTypes: [
        { id: "", value: "Elige uno" },
        { id: "regular", value: "Regular" },
        { id: "vip", value: "VIP" },
        { id: "empresa", value: "Empresa" },
      ],
      agentsList: [{ id: "", value: "Elige uno" }],
    };
  },
  async created() {
    await this.loadAgents();
  },
  methods: {
    async loadAgents() {
      try {
        const res = await axios.get("/admin/auth/users");
        this.agentsList = [
          { id: "", value: "Elige uno" },
          ...res.data.map(a => ({
            id: a.id.toString(),
            value: a.first_name + " " + (a.last_name ?? ""),
          }))
        ];
      } catch (error) {
        console.error("Error cargando asesores", error);
      }
    },

    async saveClient() {
      this.loading = true;
      try {
        await axios.post("/client/create", this.client);
        this.$toastr.s("Cliente registrado correctamente");

        // Reset formulario
        this.client = {
          name: "",
          email: "",
          phone: "",
          notes: "",
        };
      } catch (error) {
        console.error(error);
        this.$toastr.e("Error al guardar cliente");
      }
      this.loading = false;
    }
  }
};
</script>
