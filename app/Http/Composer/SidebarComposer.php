<?php

namespace App\Http\Composer;

use App\Models\Core\Builder\Table\CustomTable;
use App\Models\Core\Auth\User;
use Illuminate\View\View;

class SidebarComposer
{
    public function compose(View $view)
    {
        $table = CustomTable::all();
        /** @var User $authUser */
        $authUser = auth()->user();
        $isAdmin = $authUser->isAdmin();

        $menu = [
            [
                'icon' => 'pie-chart',
                'name' => __t('dashboard'),
                'permission' => true,
                'url' => request()->root() . '/dashboard/hospital',
            ],
            [
                'id' => 'Propiedades',
                'icon' => 'home',
                'name' => 'Propiedades',
                'permission' => true,
                'subMenu' => array_filter([
                    [
                        'name' => 'Registrar captación',
                        'url' => request()->root() . '/properties/create',
                        'permission' => $authUser->can('view_default'),
                    ],
                    [
                        'name' => 'Listado',
                        'url' => request()->root() . '/properties',
                        'permission' => $authUser->can('view_academy'),
                    ],
                    [
                        'name' => 'Alquileres activos',
                        'url' => request()->root() . '/properties/active-rentals',
                        'permission' => $authUser->can('view_academy'),
                    ],
                ]),
            ],
            [
                'id' => 'Clientes',
                'icon' => 'users',
                'name' => 'Clientes',
                'permission' => authorize_any(['view_default', 'view_academy', 'view_ecmommerce', 'view_hospital', 'view_hrm']),
                'subMenu' => array_filter([
                    [
                        'name' => 'Registrar cliente',
                        'url' => request()->root() . '/clients/create',
                        'permission' => authorize_any(['view_default', 'view_academy', 'view_ecmommerce', 'view_hospital', 'view_hrm']),
                    ],
                    [
                        'name' => 'Listar',
                        'url' => request()->root() . '/clients',
                        'permission' => $authUser->can('view_academy'),
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
                        'permission' => $authUser->can('view_academy'),
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
                        'permission' => $authUser->can('view_academy'),
                    ],
                    $isAdmin ? [
                        'name' => 'Registrar',
                        'url' => request()->root() . '/create/operations',
                        'permission' => $authUser->can('view_default'),
                    ] : null,
                ]),
            ],
            [
                'id' => 'Reportes',
                'icon' => 'bar-chart-2',
                'name' => 'Reportes',
                'permission' => true,
                'subMenu' => [
                    [
                            'name' => 'Reportes',
                            'url' => request()->root() . '/report-view',
                        'permission' => true,
                    ],
                ],
            ],
            $isAdmin ? [
                'icon' => 'user-check',
                'name' => 'Asesores',
                'url' => request()->root() . '/users-and-roles',
                'permission' => authorize_any(['view_users', 'view_roles', 'invite_user', 'create_roles']),
            ] : null,
            
        ];

        $menu = array_values(array_filter($menu));

        $view->with(['data' => $menu]);
    }
}
