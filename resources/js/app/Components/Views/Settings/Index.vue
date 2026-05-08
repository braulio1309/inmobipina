<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="pageTitle" :icon="pageIcon"/>
        <app-tab :tabs="tabs" :icon="pageIcon"/>
    </div>
</template>

<script>
    export default {
        name: "SettingsLayout",
        props: {
            permissions: {}
        },
        data() {
            return {
                pageTitle: 'Settings',
                pageIcon: 'settings',
                tabs: [
                    {
                        "name": this.$t('general'),
                        "title": this.$t('general'),
                        "component": "general-setting",
                        "permission": this.permissions.general,
                    },
                ]
            }
        },
    }
</script>
