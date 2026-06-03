<?php


namespace App\Filters\Common\Auth;


use App\Filters\Common\FilterContact;
use App\Models\Core\Auth\User;
use Illuminate\Database\Eloquent\Builder;

class ClientFilter extends FilterContact
{
    /**
     * You are allowed to modify this method the way you want;
     * You will get same laravel query builder as you get in controller
     * @return Builder Dont change the return type
     */
    public function filter() : Builder
    {
        /** @var User $authUser */
        $authUser = auth()->user();

        if (!$authUser->isAdmin()) {
            $this->query->where(function (Builder $builder) {
                $builder->where('user_id', auth()->id())
                    ->orWhere('assigned_to', auth()->id());
            });
        }

        return $this->query;
    }
}
