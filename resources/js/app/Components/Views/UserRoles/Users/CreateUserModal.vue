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

    export default {
        name: "CreateUserModal",
        mixins: [FormMixin],
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
            submit() {
                if (this.form.role_id) {
                    this.form.roles = [this.form.role_id];
                }
                this.save(this.form);
            },
            afterSuccess(res) {
                this.$toastr.s(res.data.message);
                this.$hub.$emit('reload-users-table');
                this.$emit('close-modal');
            },
        }
    }
</script>
