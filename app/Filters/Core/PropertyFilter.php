<?php


namespace App\Filters\Core;


use App\Filters\Core\traits\StatusIdFilter;
use App\Filters\FilterBuilder;
use App\Filters\Traits\UserFilterTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class PropertyFilter extends FilterBuilder
{
    use StatusIdFilter, UserFilterTrait;

    public function title($title = null)
    {
        $this->whereClause('title', "%{$title}%", 'LIKE');
    }

    public function address($address = null)
    {
        $this->whereClause('address', "%{$address}%", 'LIKE');
    }

    public function search($search = null)
    {
        return $this->builder->when($search, function (Builder $builder) use ($search) {
            $builder->where(function ($query) use ($search) {
                $query->where('title', 'LIKE', "%{$search}%")
                    ->orWhere('address', 'LIKE', "%{$search}%")
                    ->orWhere('price', 'LIKE', "%{$search}%")
                    ->orWhere('type', 'LIKE', "%{$search}%")
                    ->orWhere('type_sale', 'LIKE', "%{$search}%")
                    ->orWhere('status', 'LIKE', "%{$search}%");
                    
            });
            
        });
    }


}