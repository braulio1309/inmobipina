import Vue from 'vue';

//helper app
import './Components/Helpers/HelperComponent';

/**
 * all component of GUD
 */
Vue.component('personal-information', require('./Components/Views/Auth/PersonalInformation').default);
Vue.component('password-change', require('./Components/Views/Auth/PasswordChange').default);
Vue.component('social-links', require('./Components/Views/Auth/SocialLinks').default);
Vue.component('login', require('./Components/Views/Auth/Login').default);
Vue.component('password-reset', require('./Components/Views/Auth/PasswordReset').default);
Vue.component('reset-password', require('./Components/Views/Auth/ResetPassword').default);
Vue.component('registration', require('./Components/Views/Auth/Registration').default);
Vue.component('user-verified', require('./Components/Views/Auth/UserVerified').default);
Vue.component('my-profile', require('./Components/Views/Auth/Profile').default);

// Settings
Vue.component('app-setting', require('./Components/Views/Settings/Index').default);
Vue.component('general-setting', require('./Components/Views/Settings/GeneralSetting').default);
Vue.component('email-setting', require('./Components/Views/Settings/EmailSetting').default);
Vue.component('sms-setting', require('./Components/Views/Settings/SmsSetting').default);
Vue.component('google-re-captcha-setting', require('./Components/Views/Settings/GoogleRecaptchaSetting').default);
Vue.component('notification-settings', require('./Components/Views/Settings/Notification/Settings/Index').default);
Vue.component('storage-setting', require('./Components/Views/Settings/Storage/Index').default);
Vue.component('database-template', require('./Components/Views/Settings/Notification/Template/DatabaseTemplate').default);
Vue.component('mail-template', require('./Components/Views/Settings/Notification/Template/MailTemplate').default);
Vue.component('payment-method', require('./Components/Views/Settings/PaymentMethod/PaymentMethod').default);
Vue.component('payment-method-create-edit-modal', require('./Components/Views/Settings/PaymentMethod/PaymentMethodCreateEditModal').default);

// Propiedades
Vue.component('properties', require('./Components/Views/Properties/Index').default);
Vue.component('create-properties', require('./Components/Views/Properties/Create').default);


//Operations
Vue.component('operations', require('./Components/Views/Operation/Index').default);
Vue.component('create-operations', require('./Components/Views/Operation/Create').default);

//clients
Vue.component('clients', require('./Components/Views/Clients/Index').default);
Vue.component('create-clients', require('./Components/Views/Clients/Create').default);

//Actividades
Vue.component('activities', require('./Components/Views/Activities/Index').default);

// Reports
Vue.component('advisor-reports', require('./Components/Views/Reports/AdvisorReports').default);

// User and Roles
Vue.component('user-roles', require('./Components/Views/UserRoles/Index').default);
Vue.component('create-user', require('./Components/Views/UserRoles/Users/CreateUser').default);
Vue.component('edit-user', require('./Components/Views/UserRoles/Users/EditUser').default);
Vue.component('group-of-users', require('./Components/Views/UserRoles/Roles/GroupOfUsers').default);

// Layouts
Vue.component('app-top-bar', require('./Components/Views/Layouts/Nabar').default);
Vue.component('app-sidebar', require('./Components/Views/Layouts/Sidebar').default);

// notifications
Vue.component('all-notification', require('./Components/Views/Auth/Notification').default);

Vue.component('re-captcha', require('./Components/Views/Auth/ReCaptcha').default);
Vue.component('payment-view', require('./Components/Views/Demo/Pages/PaymentMethodView').default);

//Custom Table
Vue.component('app-custom-tables', require('./Components/Views/Settings/CustomTable/CustomTable').default)
Vue.component('app-custom-table-modal', require('./Components/Views/Settings/CustomTable/CustomTableModal').default)

// Dynamic table
Vue.component('app-custom-table-dynamic', require('./Components/Views/Demo/CustomTable/DynamicPage').default)

