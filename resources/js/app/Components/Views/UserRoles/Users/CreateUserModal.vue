<template>
    <modal :modal-id="modalId"
           :title="$t('create_user')"
           :preloader="preloader"
           @submit="submit"
           @close-modal="$emit('close-modal')">
        <template slot="body">
            <app-overlay-loader v-if="preloader"/>
            <form ref="form"
                  data-url="admin/auth/users"
                  :class="{'loading-opacity': preloader}">
                <div class="form-group row align-items-center">
                    <label class="col-sm-3 mb-0">{{ $t('first_name') }}</label>
                    <app-input class="col-sm-9"
                               type="text"
                               v-model="form.first_name"
                               :placeholder="$t('enter_first_name')"
                               :required="true"/>
                </div>
                <div class="form-group row align-items-center">
                    <label class="col-sm-3 mb-0">{{ $t('last_name') }}</label>
                    <app-input class="col-sm-9"
                               type="text"
                               v-model="form.last_name"
                               :placeholder="$t('enter_last_name')"/>
                </div>
                <div class="form-group row align-items-center">
                    <label class="col-sm-3 mb-0">{{ $t('email') }}</label>
                    <app-input class="col-sm-9"
                               type="email"
                               v-model="form.email"
                               :placeholder="$t('enter_user_email')"
                               :required="true"/>
                </div>
                <div class="form-group row align-items-center">
                    <label class="col-sm-3 mb-0">{{ $t('password') }}</label>
                    <app-input class="col-sm-9"
                               type="password"
                               v-model="form.password"
                               :placeholder="$t('enter_password')"
                               :required="true"/>
                </div>
                <div class="form-group row align-items-center mb-0">
                    <label class="col-sm-3 mb-0">{{ $t('role') }}</label>
                    <app-input class="col-sm-9"
                               type="select"
                               v-model="form.role_id"
                               :list="roleOptions"
                               :placeholder="$t('select_a_role')"
                               :required="true"/>
                </div>
            </form>
        </template>
    </modal>
</template>

<script>
    import {FormMixin} from '../../../../../core/mixins/form/FormMixin.js';
    import * as actions from '../../../../Config/ApiUrl';
    import {UserAndRoleMixin} from '../Mixins/UserAndRoleMixin';

    export default {
        name: "CreateUserModal",
        mixins: [FormMixin, UserAndRoleMixin],
        props: {
            modalId: {
                type: String,
                default: 'create-user-modal'
            }
        },
        data() {
            return {
                preloader: false,
                form: {
                    first_name: '',
                    last_name: '',
                    email: '',
                    password: '',
                    role_id: '',
                    roles: [],
                },
                roleOptions: [],
            }
        },
        created() {
            this.loadRoles();
        },
        methods: {
            loadRoles() {
                this.preloader = true;
                this.axiosGet(actions.ROLES).then(response => {
                    this.roleOptions = response.data.data.map(role => ({
                        id: role.id,
                        value: role.name
                    }));
                }).catch(err => {
                    this.$toastr.e(err.response?.data?.message || 'Error al cargar los roles.');
                }).finally(() => {
                    this.preloader = false;
                });
            },
            normalizeRoleId(role) {
                if (!role) {
                    return '';
                }

                if (typeof role === 'object') {
                    return role.id || role.value || '';
                }

                return role;
            },
            submit() {
                const roleId = this.normalizeRoleId(this.form.role_id);

                if (!this.form.first_name || !this.form.email || !this.form.password || !roleId) {
                    this.$toastr.e('Completa nombre, correo, contraseña y rol antes de guardar.');
                    return;
                }

                const payload = {
                    first_name: this.form.first_name,
                    last_name: this.form.last_name,
                    email: this.form.email,
                    password: this.form.password,
                    roles: [roleId],
                };

                this.preloader = true;

                this.axiosPost({
                    url: 'admin/auth/users',
                    data: payload,
                }).then((res) => {
                    this.afterSuccess(res);
                }).catch((error) => {
                    this.afterError(error.response);
                }).finally(() => {
                    this.preloader = false;
                });
            },
            afterSuccess(res) {
                this.$toastr.s(res.data.message);
                this.reLoadTable();
                this.$emit('close-modal');
            },
            afterError(response) {
                const message = response?.data?.message
                    || Object.values(response?.data?.errors || {}).flat()[0]
                    || 'No se pudo guardar el usuario.';

                this.$toastr.e(message);
            },
        }
    }
</script>
