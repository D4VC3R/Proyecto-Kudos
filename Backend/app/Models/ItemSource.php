<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/*
 * Fuente externa de un ítem del catálogo (TMDB, Wikidata…), F1-01.
 * (provider, external_id) es único; is_primary marca el proveedor que manda.
 */
class ItemSource extends Model
{
    use HasUuids;

    protected $fillable = ['item_id', 'provider', 'external_id', 'url', 'is_primary', 'last_synced_at'];

    protected $casts = [
        'is_primary' => 'boolean',
        'last_synced_at' => 'datetime',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
