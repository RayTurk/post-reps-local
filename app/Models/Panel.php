<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Http\Traits\HelperTrait;

class Panel extends Model
{
    use HasFactory;
    use HelperTrait;

    protected $fillable =  [
        "panel_name",
        "quantity",
        "price",
        "free_storage",
        "cost_per_unit",
        "frequency",
        "width",
        "height",
        "office_id",
        "agent_id",
        "status",
        "image_path",
        "item_id_number",
        "item_id_code",
        "listing_order",
        "id_number"
    ];

    const STATUS_ACTIVE = 1;
    const STATUS_INACTIVE = 0;

    public function agents()
    {
        return $this->hasMany(PanelAgent::class ,'panel_id','id');
    }

    public function office()
    {
        return $this->belongsTo(Office::class);
    }

    public function generateItemCodeNumber(): array
    {
        $monthChar = $this->getMonthCharFromAlphabet((int) now()->month);

        $lastItemNumber = self::max('item_id_number') ?? 0;
        $itemNumber = ++$lastItemNumber;
        $year = sprintf('%03d', now()->format('y'));
        $counter = sprintf('%05d', $itemNumber);

        $itemCode = "S{$year}{$monthChar}{$counter}";

        $data['item_id_number'] = $itemNumber;
        $data['item_id_code'] = $itemCode;

        return $data;
    }

}
