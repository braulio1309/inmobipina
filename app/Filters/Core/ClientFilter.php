<?php


namespace App\Filters\Core;


use App\Filters\Core\traits\StatusIdFilter;
use App\Filters\FilterBuilder;
use App\Filters\Traits\UserFilterTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ClientFilter extends FilterBuilder
{
    use StatusIdFilter, UserFilterTrait;

    public function nombre($nombre = null)
    {
        $this->whereClause('nombre', "%{$nombre}%", 'LIKE');
    }

    public function raza($raza = null)
    {
        $this->whereClause('raza', "%{$raza}%", 'LIKE');
    }

    public function search($search = null)
    {
        return $this->builder->when($search, function (Builder $builder) use ($search) {
            $builder->where(function ($query) use ($search) {
                $query->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%")
                    ->orWhere('phone', 'LIKE', "%{$search}%");
                    
            });
        });
    }

    public function date($date = null)
    {
        $date = $date ?: request()->input('date');
        if (is_string($date)) {
            $date = json_decode(htmlspecialchars_decode($date), true);
        }
        $this->builder->when($date && is_array($date) && isset($date['start']), function (Builder $builder) use ($date) {
            $builder->whereBetween(DB::raw('DATE(created_at)'), [$date['start'], $date['end']]);
        });
    }

    public function asesor($asesorId = null)
    {
        $asesorId = $asesorId ?: request()->input('asesor');
        $this->builder->when($asesorId, function (Builder $builder) use ($asesorId) {
            $builder->where('assigned_to', $asesorId);
        });
    }

    public function status($status = null)
    {
        $status = $status ?: request()->input('status');
        $this->builder->when($status, function (Builder $builder) use ($status) {
            $builder->where('status', $status);
        });
    }


}