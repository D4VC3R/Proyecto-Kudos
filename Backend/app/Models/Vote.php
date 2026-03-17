<?php

namespace App\Models;

use Database\Factories\VoteFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Vote extends Model
{
    use HasFactory, HasUuids;

    public const TYPE_VOTE = 'vote';
    public const TYPE_SKIP = 'skip';

    protected $fillable = [
        'user_id',
        'item_id',
        'type',
        'score',
    ];

    protected $casts = [
        'score' => 'integer',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function kudosTransactions(): MorphMany
    {
        return $this->morphMany(KudosTransaction::class, 'reference');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
