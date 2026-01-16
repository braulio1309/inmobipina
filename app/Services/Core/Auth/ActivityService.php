<?php


namespace App\Services\Core\Auth;

use App\Exceptions\GeneralException;
use App\Helpers\Core\Traits\FileHandler;
use App\Helpers\Core\Traits\HasWhen;
use App\Helpers\Core\Traits\Helpers;
use App\Models\Core\Auth\Role;
use App\Models\Core\Status;
use App\Services\Core\BaseService;

use App\Models\Activity;

class ActivityService extends BaseService
{
    use FileHandler, Helpers, HasWhen;

    protected $role;

    public function __construct(Activity $Activity, Role $role)
    {
        $this->model = $Activity;
        $this->role = $role;
    }

    public function create($attributes = [])
    {
        $status = Status::findByNameAndType('status_active', 'Activity')->id;
        $attributes = array_merge(['status_id' => $status], $attributes);

        parent::save($this->getFillAble(array_merge(request()->only(
            'first_name',
            'last_name',
            'email',
            'password',
        ), $attributes)));

        return $this;
    }


    public function update()
    {
        $this->model->fill($this->getFillAble(request()->only('first_name', 'last_name', 'status_id')));

        throw_if(
            $this->model->isDirty('status_id') && ($this->model->isAppAdmin() || $this->model->id == auth()->id()),
            new GeneralException(trans('default.action_not_allowed'))
        );

        $this->when($this->model->isDirty(), function (ActivityService $service) {
            $service->notify('Activity_updated');
        });

        $this->model->save();

        $this->when(request()->get('roles'), function (ActivityService $service) {
            $service->assignRole(request()->get('roles'));
        });

        return $this->model;
    }

    public function assignRole($roles)
    {
        $this->model->assignRole($roles);

        return $this;
    }

    public function delete(Activity $Activity)
    {
        if ($Activity->isAppAdmin() && !$Activity->isInvited())
            throw new GeneralException(trans('default.action_not_allowed'));

        if ($Activity->id == auth()->id())
            throw new GeneralException(trans('default.cant_delete_own_account'));

        return $Activity->forceDelete();
    }


   
}