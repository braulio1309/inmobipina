<template>
    <div class="card card-with-shadow border-0 h-100 employee-preview-card">
        <div class="card-body position-relative d-flex flex-column justify-content-between">
            <div v-if="showAction" class="dropdown options-dropdown position-absolute">
                <button type="button"
                        class="btn-option btn d-flex align-items-center justify-content-center"
                        data-toggle="dropdown">
                    <app-icon name="more-horizontal"/>
                </button>
                <div class="dropdown-menu dropdown-menu-right py-2 mt-1">
                    <a class="dropdown-item px-4 py-2"
                       href="#"
                       v-for="(action,index) in actions"
                       :key="index"
                       @click.prevent="callAction(action)">
                        {{ action.title }}
                    </a>
                </div>
            </div>
            <div class="d-flex flex-column align-items-center">
                <app-avatar :title="profile.name"
                            avatar-class="avatars-w-60 mb-2 mx-auto d-inline-block"
                            :img="getImageUrl(profile)"/>
                <div class="my-3">
                    <h5 class="mb-1 mt-3" style="font-size: 1.15rem">{{ profile.user.first_name }}</h5>
                </div>
                <div v-if="profile.type" class="my-3">
                    <span :class="'badge badge-sm badge-pill badge-'+statusClass(profile.type)">{{ profile.type }}</span>
                </div>
                <p class="mb-2 text-muted font-size-90">{{ profile.result }}</p>
                
                <p v-if="profile.date" class="mb-2">{{ profile.date }}</p>
                
            </div>
        </div>
    </div>
</template>

<script>
import AppFunction from "../../../../../../core/helpers/app/AppFunction";

export default {
    name: "ProfileCard",
    props: {
        profile: {
            type: Object,
            required: true
        },
        actions: {
            type: Array,
            required: true
        },
        showAction: {
            type: Boolean,
            default: true
        },
        id: {
            required: true
        }
    },
    methods: {
        callAction(action) {
            this.$emit('action-' + this.id, this.profile, action, true)
        },
        getImageUrl(profile) {
            if (profile.profile_picture) {
                return AppFunction.getAppUrl(profile.profile_picture);
            }
            return AppFunction.getAppUrl('images/avatar-demo.jpg');
        },
        statusClass(status) {
            let ClassName = 'primary';
            if (status === 'demostración') ClassName = `success`;
            else if (status === 'captación') ClassName = `warning`;
            return ClassName;
        }
    },
}
</script>
