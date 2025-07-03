<?php

declare(strict_types=1);

namespace App\Services;

use App\Http\Traits\HelperTrait;
use App\Models\{Accessory, AccessoryAgent, AccessoryOffice};
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\DataTables;

class AccessoryService
{
    protected $model;

    use HelperTrait;

    public function __construct(Accessory $model)
    {
        $this->model = $model;
    }

    public function create(array $attributes)
    {
        //Item id
        $monthChar = $this->getMonthCharFromAlphabet((int) now()->month);

        $lastItemNumber = $this->model->max('item_id_number') ?? 0;
        $itemNumber = ++$lastItemNumber;
        $year = sprintf('%03d', now()->format('y'));
        $counter = sprintf('%05d', $itemNumber);

        $itemCode = "A{$year}{$monthChar}{$counter}";

        $attributes['item_id_number'] = $itemNumber;
        $attributes['item_id_code'] = $itemCode;
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

    public function findById(int $id): Accessory
    {
        return $this->model->findOrFail($id);
    }

    public function getOrderByListingOrderAndName()
    {
        $data = $this->model
            ->with('accessory_offices')
            ->with('accessory_agents')
            ->where('status', 1)
            ->orderBy('listing_order', 'asc')
            ->orderBy('accessory_name', 'asc')
            ->whereNotNull("listing_order")
            ->with('office_access')
            ->get();
        $nulls = $this->model->whereNull('listing_order')
            ->with('office_access')
            ->where('status', 1)
            ->get();
        foreach ($nulls as $n) {
            $data->push($n);
        }
        return $data;
    }

    public function datatable()
    {
        $accessoryCols = [
            'accessories.*',
        ];
        $query = $this->model->select(...$accessoryCols)
            ->orderBy('listing_order', 'asc')
            ->orderBy('accessory_name', 'asc');

        return Datatables::of($query)->make(true);
    }

    public function lockAgentPrice(int $accessoryId, int $agentId, int $access, float $price)
    {
        AccessoryAgent::updateOrCreate(
            ['accessory_id' => $accessoryId, 'agent_id' => $agentId],
            ['access' => $access, 'price' => $price, 'locked' => true]
        );
    }

    public function unlockAgentPrice(int $accessoryId, int $agentId, int $access, float $price)
    {
        AccessoryAgent::where(['accessory_id' => $accessoryId, 'agent_id' => $agentId])
            ->update(['access' => $access, 'price' => $price, 'locked' => false]);
    }

    public function findAccessoryOffice(int $accessoryId, int $officeId)
    {
        return AccessoryOffice::where('accessory_id', $accessoryId)
            ->where('office_id', $officeId)
            ->first();
    }
}
