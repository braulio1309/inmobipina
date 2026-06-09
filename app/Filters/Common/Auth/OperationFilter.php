<?php


namespace App\Filters\Common\Auth;


use App\Filters\Common\FilterContact;
use Illuminate\Database\Eloquent\Builder;

class OperationFilter extends FilterContact
{
    /**
     * You are allowed to modify this method the way you want;
     * You will get same laravel query builder as you get in controller
     * @return Builder Dont change the return type
     */
    public function filter() : Builder
    {
        /** @var \App\Models\Core\Auth\User|null $authUser */
        $authUser = auth()->user();

        if (!$authUser || !$authUser->isAdmin()) {
            $userId = $authUser?->id;

            if (!$userId) {
                return $this->query;
            }

            $this->query->whereHas('sellers', function ($q) use ($userId) {
                $q->where('users.id', $userId);
            });
        }

        return $this->query;
    }
}
