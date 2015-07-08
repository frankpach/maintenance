<?php

namespace Stevebauman\Maintenance\Models;

use Stevebauman\Inventory\Traits\SupplierTrait;

class Supplier extends BaseModel
{
    use SupplierTrait;

    /**
     * The suppliers table.
     *
     * @var string
     */
    protected $table = 'suppliers';

    /**
     * The belongsToMany items relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function items()
    {
        return $this->belongsToMany(Inventory::class, 'inventory_suppliers', 'supplier_id')->withTimestamps();
    }
}