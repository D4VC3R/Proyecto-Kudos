<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CategoryFieldDefinition extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'category_id',
        'key',
        'label',
        'type',
        'required',
        'options',
        'rules',
        'sort_order',
        'is_filterable',
        'is_active',
    ];

    protected $casts = [
        'required' => 'boolean',
        'options' => 'array',
        'rules' => 'array',
        'sort_order' => 'integer',
        'is_filterable' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}

