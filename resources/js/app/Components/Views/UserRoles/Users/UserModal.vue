<template>
    <modal :modal-id="userAndRoles.users.userModalId"
                     :title="modalTitle"
                     :preloader="preloader"
                     @submit="submit"
                     @close-modal="closeModal">
        <template slot="body">
            <app-overlay-loader v-if="preloader"/>
            <form class="mb-0"
                  :class="{'loading-opacity': preloader}"
                  ref="form">
                <div class="form-group row align-items-center">
                    <label for="inputs_name" class="col-sm-3 mb-0">
                        {{ $t('first_name') }}
                    </label>
                    <app-input id="inputs_name"
                               class="col-sm-9"
                               type="text"
                               v-model="inputs.first_name"
                               :placeholder="$t('enter_first_name')"
                               :required="true"/>

                </div>
                <div class="form-group row align-items-center">
                    <label for="inputs_last_name" class="col-sm-3 mb-0">
                        {{ $t('last_name') }}
                    </label>
                    <app-input id="inputs_last_name"
                               class="col-sm-9"
                               type="text"
                               v-model="inputs.last_name"
                               :placeholder="$t('enter_last_name')"/>
                </div>
                <div class="form-group row align-items-center">
                    <label for="inputs_email" class="col-sm-3 mb-0">
                        {{ $t('email') }}
                    </label>
                    <app-input id="inputs_email"
                               class="col-sm-9"
                               type="email"
                               v-model="inputs.email"
                               :placeholder="$t('enter_user_email')"
                               :required="true"/>
                </div>
                <div class="form-group row align-items-center">
                    <label for="inputs_password" class="col-sm-3 mb-0">
                        {{ $t('password') }}
                    </label>
                    <app-input id="inputs_password"
                               class="col-sm-9"
                               type="password"
                               v-model="inputs.password"
                               :show-password="true"
                               :placeholder="$t('enter_password')"/>
                </div>
                <div class="form-group row align-items-center mb-0">
                    <label for="inputs_role" class="col-sm-3 mb-0">
                        {{ $t('role') }}
                    </label>
                    <app-input id="inputs_role"
                               class="col-sm-9"
                               type="select"
                               v-model="inputs.role_id"
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
    import {ModalMixin} from "../../../../Mixins/ModalMixin";
    import * as actions from '../../../../Config/ApiUrl';

    import {UserAndRoleMixin} from '../Mixins/UserAndRoleMixin';

    export default {
        name: "UserModal",
        mixins: [FormMixin, ModalMixin, UserAndRoleMixin],
        data() {
            return {
                preloader: false,
                inputs: {},
                roleOptions: [],
                modalTitle: this.$t('edit_user'),
            }
        },
        created() {
            this.inputs = {
                ...this.userAndRoles.rowData,
                email: this.userAndRoles.rowData.email || '',
                password: '',
                role_id: this.userAndRoles.rowData.roles && this.userAndRoles.rowData.roles.length
                    ? this.userAndRoles.rowData.roles[0].id
                    : '',
            };
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
                }).catch(error => {
                    this.$toastr.e(error.response?.data?.message || 'Error al cargar los roles.');
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
                const roleId = this.normalizeRoleId(this.inputs.role_id);

                if (!this.inputs.first_name || !this.inputs.email || !roleId) {
                    this.$toastr.e('Completa nombre, correo y rol antes de guardar.');
                    return;
                }

                const payload = {
                    first_name: this.inputs.first_name,
                    last_name: this.inputs.last_name,
                    email: this.inputs.email,
                    roles: [roleId],
                };

                if (this.inputs.password) {
                    payload.password = this.inputs.password;
                }

                this.preloader = true;

                this.axiosPost({
                    url: `/update-user-name/${this.inputs.id}`,
                    data: payload,
                }).then((response) => {
                    this.afterSuccess(response);
                }).catch((error) => {
                    this.afterError(error.response);
                }).finally(() => {
                    this.preloader = false;
                });
            },
            afterSuccess(response) {
                this.$toastr.s(response.data.message);
                this.reLoadTable();
                this.closeModal();
            },
            afterError(response) {
                const message = response?.data?.message
                    || Object.values(response?.data?.errors || {}).flat()[0]
                    || 'No se pudo actualizar el usuario.';

                this.$toastr.e(message);
            },
        },
    }
</script>