<?php


namespace App\Filters\Common\Auth;


use App\Filters\Common\FilterContact;
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
        if (!auth()->user()->isAdmin()) {
            $this->query->where('user_id', auth()->id());
        }

        return $this->query;
    }
}
