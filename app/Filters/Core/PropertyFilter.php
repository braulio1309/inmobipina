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

    public function type($type = null)
    {
        $type = $type ?: request()->input('type');
        $this->builder->when($type, function (Builder $builder) use ($type) {
            $types = is_array($type) ? $type : explode(',', $type);
            $builder->whereIn('type', array_filter($types));
        });
    }

    public function status($status = null)
    {
        $status = $status ?: request()->input('status');
        $this->builder->when($status, function (Builder $builder) use ($status) {
            $statuses = is_array($status) ? $status : explode(',', $status);
            $builder->whereIn('status', array_filter($statuses));
        });
    }

    public function typeSale($typeSale = null)
    {
        $typeSale = $typeSale ?: request()->input('type_sale');
        $this->builder->when($typeSale, function (Builder $builder) use ($typeSale) {
            $values = is_array($typeSale) ? $typeSale : explode(',', $typeSale);
            $builder->whereIn('type_sale', array_filter($values));
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