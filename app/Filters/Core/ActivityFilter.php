<?php


namespace App\Filters\Core;


use App\Filters\Core\traits\StatusIdFilter;
use App\Filters\FilterBuilder;
use App\Filters\Traits\UserFilterTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ActivityFilter extends FilterBuilder
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
                $query->where('type', 'LIKE', "%{$search}%")
                    ->orWhere('result', 'LIKE', "%{$search}%")
                    ->orWhereHas('user', function ($q) use ($search) {
                  $q->where('first_name', 'LIKE', "%{$search}%")
                    ->orWhere('last_name', 'LIKE', "%{$search}%");
              });
                    
            });
        });
    }


}