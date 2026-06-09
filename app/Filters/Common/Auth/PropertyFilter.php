<?php


namespace App\Filters\Common\Auth;


use App\Filters\Common\FilterContact;
use Illuminate\Database\Eloquent\Builder;

class PropertyFilter extends FilterContact
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

            $this->query->where(function ($q) use ($userId) {
                $q->where('status', 'Disponible')
                  ->orWhere(function ($q2) use ($userId) {
                      $q2->where('status', 'pending')
                         ->where('created_by', $userId);
                  });
            });
        }

        return $this->query;
    }
}
