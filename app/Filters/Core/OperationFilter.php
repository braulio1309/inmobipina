<?php


namespace App\Filters\Core;


use App\Filters\Core\traits\StatusIdFilter;
use App\Filters\FilterBuilder;
use App\Filters\Traits\UserFilterTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class OperationFilter extends FilterBuilder
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

    public function type($type = null)
    {
        $type = $type ?: request()->input('type');
        $this->builder->when($type, function (Builder $builder) use ($type) {
            $types = is_array($type) ? $type : explode(',', $type);
            $builder->whereIn('type', array_filter($types));
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

    public function search($search = null)
    {
        return $this->builder->when($search, function (Builder $builder) use ($search) {
            $builder->where(function ($query) use ($search) {
                $query->where('type', 'LIKE', "%{$search}%")
                    ->orWhere('amount', 'LIKE', "%{$search}%")
                    ->orWhere('notes', 'LIKE', "%{$search}%")
                    ->orWhere('start_date', 'LIKE', "%{$search}%")
                    ->orWhereHas('sellers', function ($q) use ($search) {
                        $q->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"]);
                    });
                    
            });
        });
    }


}