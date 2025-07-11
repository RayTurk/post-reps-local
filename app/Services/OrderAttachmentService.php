<?php

declare(strict_types=1);

namespace App\Services;

use App\Http\Traits\HelperTrait;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use App\Models\OrderAttachment;
use App\Models\RepairOrderAttachment;

class OrderAttachmentService
{
    use HelperTrait;

    protected $model;

    public function __construct(OrderAttachment $model)
    {
        $this->model = $model;
    }

    public function create(array $attributes)
    {
        return $this->model->create($attributes);
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

    public function findById(int $id): OrderAttachment
    {
        return $this->model->findOrFail($id);
    }

    public function storeRepairOrderAttachments(array $attributes)
    {
        return RepairOrderAttachment::create($attributes);
    }
}
