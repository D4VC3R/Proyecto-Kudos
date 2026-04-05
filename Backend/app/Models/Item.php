<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Item extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'name',
        'description',
        'images',
        'extra_data',
        'status',
        'vote_avg',
        'vote_count',
        'creator_id',
        'category_id',
    ];

    protected $casts = [
        'images' => 'array',
        'extra_data' => 'array',
        'vote_avg' => 'float',
        'vote_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }


    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(ItemComment::class);
    }

    public function kudosTransactions(): MorphMany
    {
        return $this->morphMany(KudosTransaction::class, 'reference');
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeInactive($query)
    {
        return $query->where('status', self::STATUS_INACTIVE);
    }
}
