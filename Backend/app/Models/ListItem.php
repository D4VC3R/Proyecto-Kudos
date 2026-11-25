<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Entrada de una lista: un ítem en una posición, con una nota opcional.
 */
class ListItem extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'list_items';

    protected $fillable = ['list_id', 'item_id', 'position', 'note'];

    protected $casts = [
        'position' => 'integer',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
