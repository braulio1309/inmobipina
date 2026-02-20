<?php

namespace App\Http\Composer;

use App\Models\Core\Builder\Table\CustomTable;
use Illuminate\View\View;

class SidebarComposer
{
    public function compose(View $view)
    {
        $table = CustomTable::all();
        $isAdmin = auth()->user()->isAdmin();

        $menu = [
            [
                'id' => 'dashboard-samples',
                'icon' => 'pie-chart',
                'name' => __t('dashboard'),
                'permission' => authorize_any(['view_default', 'view_academy', 'view_ecmommerce', 'view_hospital', 'view_hrm']),
                'url' => request()->root() . '/dashboard/hospital',
            ],
            [
                'id' => 'Propiedades',
                'icon' => 'home',
                'name' => 'Propiedades',
                'permission' => authorize_any(['view_default', 'view_academy', 'view_ecmommerce', 'view_hospital', 'view_hrm']),
                'subMenu' => array_filter([
                    $isAdmin ? [
                        'name' => 'Registrar captación',
                        'url' => request()->root() . '/properties/create',
                        'permission' => auth()->user()->can('view_default'),
                    ] : null,
                    [
                        'name' => 'Listado',
                        'url' => request()->root() . '/properties',
                        'permission' => auth()->user()->can('view_academy'),
                    ],
                ]),
            ],
            [
                'id' => 'Clientes',
                'icon' => 'users',
                'name' => 'Clientes',
                'permission' => authorize_any(['view_default', 'view_academy', 'view_ecmommerce', 'view_hospital', 'view_hrm']),
                'subMenu' => array_filter([
                    $isAdmin ? [
                        'name' => 'Registrar cliente',
                        'url' => request()->root() . '/clients/create',
                        'permission' => auth()->user()->can('view_default'),
                    ] : null,
                    [
                        'name' => 'Listar',
                        'url' => request()->root() . '/clients',
                        'permission' => auth()->user()->can('view_academy'),
                    ],
                ]),
            ],
            [
                'id' => 'Actividades',
                'icon' => 'clipboard',
                'name' => 'Actividades',
                'permission' => authorize_any(['view_default', 'view_academy', 'view_ecmommerce', 'view_hospital', 'view_hrm']),
                'subMenu' => [
                    [
                        'name' => 'Historial',
                        'url' => request()->root() . '/activities',
                        'permission' => auth()->user()->can('view_academy'),
                    ],
                ],
            ],
            [
                'id' => 'Cierres',
                'icon' => 'dollar-sign',
                'name' => 'Cierres',
                'permission' => authorize_any(['view_default', 'view_academy', 'view_ecmommerce', 'view_hospital', 'view_hrm']),
                'subMenu' => array_filter([
                    [
                        'name' => 'Historial',
                        'url' => request()->root() . '/operations',
                        'permission' => auth()->user()->can('view_academy'),
                    ],
                    $isAdmin ? [
                        'name' => 'Registrar',
                        'url' => request()->root() . '/create/operations',
                        'permission' => auth()->user()->can('view_default'),
                    ] : null,
                ]),
            ],
            [
                'icon' => 'bar-chart-2',
                'name' => 'Reportes',
                'url' => request()->root() . '/report/advisor',
                'permission' => true,
            ],
            $isAdmin ? [
                'icon' => 'user-check',
                'name' => 'Asesores',
                'url' => request()->root() . '/users-and-roles',
                'permission' => authorize_any(['view_users', 'view_roles', 'invite_user', 'create_roles']),
            ] : null,
            $isAdmin ? [
                'icon' => 'settings',
                'name' => trans('custom.settings'),
                'url' => request()->root() . '/app-setting',
                'permission' => authorize_any(
                    [
                        'view_settings', 'update_settings', 'view_delivery_settings',
                        'update_delivery_settings',
                        'view_notification_settings', 'update_notification_settings',
                        'update_notification_templates', 'view_notification_templates',
                    ]
                ),
            ] : null,
        ];

        $menu = array_values(array_filter($menu));

        $view->with(['data' => $menu]);
    }
}
