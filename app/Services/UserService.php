<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Yajra\DataTables\DataTables;

class UserService
{
    protected $model;

    public function __construct(User $model)
    {
        $this->model = $model;
    }

    public function create(array $attributes)
    {
        return $this->model->create($attributes);
    }

    public function update($data, $id)
    {
        $user = $this->model->findOrFail($id);

        if (isset($data['first_name']) && isset($data['last_name'])) {
            $user->name = $data['first_name'] . ' ' . $data['last_name'];
        }

        return $user->fill($data)->save();
    }

    public function getAll()
    {
        return $this->model->all();
    }

    public function getAllOrderBy(string $orderBy, string $sortOrder = 'asc'): EloquentCollection
    {
        $getAllOrderBy = $this->model;

        if ($orderBy) {
            $getAllOrderBy = $getAllOrderBy->orderBy($orderBy, $sortOrder);
        }

        $getAllOrderBy = $getAllOrderBy->get();

        return $getAllOrderBy;
    }

    public function findById(int $id): User
    {
        return $this->model->findOrFail($id);
    }

    public function findByIds(array $ids): EloquentCollection
    {
        return $this->model->findOrFail($ids);
    }

    public  function deleteWhereRole(int $role)
    {
        $this->model->where('role',$role)->delete();
        return true;
    }

    public function datatableInstallers()
    {
        $userColumns = ['users.id', 'users.name', 'users.email', 'users.zipcode', 'users.address', 'users.city', 'users.phone', 'users.state', 'users.hire_date', 'users.pay_rate'];
        $query = $this->model->orderBy('users.name')
            ->where('role', User::ROLE_INSTALLER)
            ->select(...$userColumns);

        return DataTables::of($query)->make(true);
    }

    public function getInstallers()
    {
        return $this->model->orderBy('users.name')
            ->where('role', User::ROLE_INSTALLER)
            ->get(['users.id', 'users.name', 'users.first_name', 'users.last_name']);
    }

    public function getActiveInstallers()
    {
        return $this->model->orderBy('users.name')
            ->where('role', User::ROLE_INSTALLER)
            ->where('inactive', false)
            ->get(['users.id', 'users.name', 'users.first_name', 'users.last_name']);
    }

    public function updateBalance(User $user, $credit)
    {
        $currentBalance = $user->balance;
        $newBalance = $currentBalance + $credit;

        $user->balance = $newBalance;
        $user->save();
    }
}
