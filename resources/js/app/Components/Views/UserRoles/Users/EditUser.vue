<template>
  <div class="content-wrapper">
    <app-breadcrumb 
      :page-title="$t('Editar usuario')" 
      :directory="$t('Usuarios')" 
      :icon="'user-edit'"
    />

    <div class="container mt-4">
      <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body">
          <!-- Nav Tabs -->
          <ul class="nav nav-pills mb-4 justify-content-center">
            <li class="nav-item" v-for="tab in tabs" :key="tab.key">
              <button
                class="nav-link"
                :class="{ active: activeTab === tab.key }"
                @click="activeTab = tab.key"
              >
                <i :class="tab.icon" class="me-1"></i> {{ tab.label }}
              </button>
            </li>
          </ul>

          <!-- TAB 1: DATOS PERSONALES -->
          <div v-if="activeTab === 'personal'">
            <h5 class="mb-3 fw-semibold text-primary">Datos personales</h5>
            <form @submit.prevent="activeTab = 'dieta'">
              <div class="row mb-3">
                <div class="col-md-6">
                  <label class="form-label fw-semibold">Nombre completo</label>
                  <input type="text" class="form-control" v-model="form.name" required />
                </div>
                <div class="col-md-3">
                  <label class="form-label fw-semibold">Fecha de nacimiento</label>
                  <input type="date" class="form-control" v-model="form.birth_date" required />
                </div>
                <div class="form-group row align-items-center col-md-3">
                  <label class="col-sm-12 mb-sm-0">Sexo</label>
                  <app-input class="col-sm-12"
                             type="select"
                             v-model="form.gender"
                             :list="generos"
                             :placeholder="'Seleccione un genero'"/>
                </div>
              </div>

              <div class="row mb-3">
                <div class="col-md-6">
                  <label class="form-label fw-semibold">Correo electrónico</label>
                  <input type="email" class="form-control" v-model="form.email" required />
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-semibold">Teléfono</label>
                  <input type="text" class="form-control" v-model="form.phone" />
                </div>
              </div>

              <div class="d-flex justify-content-end mt-4">
                <button type="submit" class="btn btn-primary px-4">
                  Siguiente <i class="bi bi-arrow-right-circle ms-1"></i>
                </button>
              </div>
            </form>
          </div>

          <!-- TAB 2: DATOS DE DIETA -->
          <div v-if="activeTab === 'dieta'">
            <h5 class="mb-3 fw-semibold text-success">Datos de dieta y salud</h5>
            <form @submit.prevent="activeTab = 'dietas'">
              <div class="row mb-3">
                <div class="col-md-4">
                  <label class="form-label fw-semibold">Peso (kg)</label>
                  <input type="number" step="0.1" class="form-control" v-model="form.weight_kg" />
                </div>
                <div class="col-md-4">
                  <label class="form-label fw-semibold">Altura (cm)</label>
                  <input type="number" class="form-control" v-model="form.height_cm" />
                </div>
                <div class="form-group row align-items-center col-md-4">
                  <label class="col-sm-12 mb-sm-0">Nivel de actividad</label>
                  <app-input class="col-sm-12"
                             type="select"
                             v-model="form.activity_level"
                             :list="actividad"
                             :placeholder="'Seleccione uno'"/>
                </div>
              </div>

              <div class="row mb-3">
                <div class="form-group row align-items-center col-md-6">
                  <label class="col-sm-12 mb-sm-0">Objetivo</label>
                  <app-input class="col-sm-12"
                             type="select"
                             v-model="form.goal"
                             :list="objetivos"
                             :placeholder="'Seleccione uno'"/>
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-semibold">Fecha de inicio</label>
                  <input type="date" class="form-control" v-model="form.start_date" />
                </div>
              </div>

              <div class="row mb-3">
                <div class="col-md-6">
                  <label class="form-label fw-semibold">Alergias o intolerancias</label>
                  <textarea class="form-control" v-model="form.allergies" rows="2"></textarea>
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-semibold">Preferencias alimenticias</label>
                  <textarea class="form-control" v-model="form.preferences" rows="2"></textarea>
                </div>
              </div>

              <div class="mb-3">
                <label class="form-label fw-semibold">Observaciones del entrenador</label>
                <textarea class="form-control" v-model="form.observations" rows="3"></textarea>
              </div>

              <div class="d-flex justify-content-between mt-4">
                <button type="button" class="btn btn-secondary px-4" @click="activeTab = 'personal'">
                  <i class="bi bi-arrow-left-circle me-1"></i> Volver
                </button>
                <button type="submit" class="btn btn-success px-4">
                  Siguiente <i class="bi bi-arrow-right-circle ms-1"></i>
                </button>
              </div>
            </form>
          </div>

          <!-- TAB 3: DIETAS CREADAS -->
          <div v-if="activeTab === 'dietas'">
            <h5 class="mb-3 fw-semibold text-info">Dietas creadas</h5>

            <div class="mb-3 d-flex justify-content-between align-items-center">
              <span class="text-muted">Dietas generadas con IA o creadas manualmente</span>
              <button class="btn btn-outline-primary" @click="generateDiet">
                <i class="bi bi-magic"></i> Generar dieta con IA
              </button>
            </div>

            <table class="table table-bordered align-middle">
              <thead class="table-light">
                <tr>
                  <th>#</th>
                  <th>Nombre</th>
                  <th>Fecha</th>
                  <th>Objetivo</th>
                  <th>Acciones</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(diet, index) in diets" :key="index">
                  <td>{{ index + 1 }}</td>
                  <td>{{ diet.title }}</td>
                  <td>{{ diet.date }}</td>
                  <td>{{ diet.goal }}</td>
                  <td>
                    <a :href="diet.pdf_url" target="_blank" class="btn btn-sm btn-outline-primary me-2">
                      <i class="bi bi-eye"></i>
                    </a>
                    <button class="btn btn-sm btn-outline-danger" @click="deleteDiet(index)">
                      <i class="bi bi-trash"></i>
                    </button>
                  </td>
                </tr>
                <tr v-if="diets.length === 0">
                  <td colspan="5" class="text-center text-muted py-4">
                    No hay dietas registradas.
                  </td>
                </tr>
              </tbody>
            </table>

            <div class="d-flex justify-content-between mt-4">
              <button type="button" class="btn btn-secondary px-4" @click="activeTab = 'dieta'">
                <i class="bi bi-arrow-left-circle me-1"></i> Volver
              </button>
              <button type="button" class="btn btn-success px-4" @click="saveUser">
                <i class="bi bi-save me-1"></i> Guardar cambios
              </button>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { FormMixin } from '../../../../../core/mixins/form/FormMixin.js';
import * as actions from '../../../../Config/ApiUrl';

export default {
  name: "EditUser",
  mixins: [FormMixin],
  data() {
    return {
      activeTab: "personal",
      userId: null,
      loading: false,
      diets: [],
      id: null,

      tabs: [
        { key: "personal", label: "Datos personales", icon: "bi bi-person-circle" },
        { key: "dieta", label: "Datos de dieta", icon: "bi bi-heart-pulse" },
        { key: "dietas", label: "Dietas creadas", icon: "bi bi-journal-check" },
      ],

      form: {
        name: "",
        birth_date: "",
        gender: "",
        email: "",
        phone: "",
        weight_kg: "",
        height_cm: "",
        activity_level: "Moderado",
        goal: "Mantener peso",
        allergies: "",
        preferences: "",
        observations: "",
        start_date: new Date().toISOString().split("T")[0],
      },

      generos: [
        { id: '', value: 'Elige uno' },
        { id: 'Masculino', value: 'Masculino' },
        { id: 'Femenino', value: 'Femenino' },
        { id: 'Otro', value: 'Otro' },
      ],
      objetivos: [
        { id: '', value: 'Elige uno' },
        { id: 'Perder grasa', value: 'Perder grasa' },
        { id: 'Mantener peso', value: 'Mantener peso' },
        { id: 'Ganar masa', value: 'Ganar masa' },
      ],
      actividad: [
        { id: '', value: 'Elige uno' },
        { id: 'Sedentario', value: 'Sedentario' },
        { id: 'Ligero', value: 'Ligero' },
        { id: 'Moderado', value: 'Moderado' },
        { id: 'Alto', value: 'Alto' },
      ],
    };
  },

 
  created() {
    this.loadUser();
  },


  methods: {
    /**
     * Carga los datos del usuario por ID
     */
    loadUser() {
        const url = window.location.pathname; // ej: "/users/edit/123"
        const partes = url.split("/");        // ["", "users", "edit", "123"]
        this.id = partes[partes.length - 1]; // "123"
        console.log(this.id)
      this.loading = true;
      this.axiosGet(`users/${this.id}`)
        .then(response => {
            console.log(response.data.user)
          if(response.data.status === 'success') {
            const data = response.data.user;
            this.userId = data.id;
            this.form.name = data.first_name;
            this.form.birth_date = data.birth_date;
            this.form.gender = data.gender;
            this.form.email = data.email;
            this.form.phone = data.phone;
            this.form.weight_kg = data.weight_kg;
            this.form.height_cm = data.height_cm;
            this.form.activity_level = data.activity_level;
            this.form.goal = data.goal;
            this.form.allergies = data.allergies;
            this.form.preferences = data.preferences;
            this.form.observations = data.observations;
            this.form.start_date = data.start_date;
            this.diets = data.diets || [];
          }
        })
        .catch(error => {
          console.error(error);
          this.$toastr.e('Error al cargar los datos del usuario');
        })
        .finally(() => { this.loading = false; });
    },

    /**
     * Guarda los cambios del usuario
     */
    saveUser() {
      if(!this.id) return;
      this.preloader = true;

      this.axiosPost({
        url: `/users/edit/${this.id}`,
        data: this.form,
      })
      .then(response => {
        if (response.data.status === 'success') {
          this.$toastr.s('✅ Usuario actualizado correctamente.');
          this.activeTab = 'dietas';
        } else {
          this.$toastr.e('⚠️ No se pudo actualizar el usuario.');
        }
      })
      .catch(error => {
        console.error(error);
        this.$toastr.e(error.response?.data?.message || 'Error de conexión.');
      })
      .finally(() => { this.preloader = false; });
    },

    /**
     * Genera dieta con IA
     */
    generateDiet() {
      if (!this.id) {
        this.$toastr.w('⚠️ Primero debes guardar los datos del usuario.');
        return;
      }

      this.preloader = true;
      this.axiosPost({
        url: '/diets/generate',
        data: {
          user_id: this.id,
          observations: this.form.observations,
        },
      })
      .then(response => {
        const data = response.data;
        if (data.status === 'success') {
          this.diets.push({
            title: data.diet.title,
            goal: data.diet.goal,
            date: new Date().toLocaleDateString(),
            pdf_url: data.pdf_url,
          });
          this.$toastr.s('✅ Dieta generada y PDF guardado correctamente.');
        } else {
          this.$toastr.e('⚠️ No se pudo generar la dieta.');
        }
      })
      .catch(error => {
        console.error(error);
        this.$toastr.e('Error de conexión con el servidor.');
      })
      .finally(() => { this.preloader = false; });
    },

    deleteDiet(index) {
      this.diets.splice(index, 1);
      this.$toastr.s('✅ Dieta eliminada.');
    }
  }
};
</script>

<style scoped>
.nav-pills .nav-link {
  border-radius: 50px;
  margin: 0 5px;
  padding: 8px 18px;
  transition: all 0.3s ease;
}
.nav-pills .nav-link.active {
  background-color: #0d6efd;
  color: #fff;
  font-weight: 600;
  box-shadow: 0 2px 6px rgba(13, 110, 253, 0.3);
}
.card {
  border-radius: 16px;
}
</style>
