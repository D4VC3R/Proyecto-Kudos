<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Proposal extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    public const STATUS_PENDING = 'pending';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CHANGES_REQUESTED = 'changes_requested';

	protected $attributes = [
		'status' => self::STATUS_PENDING,
	];
    protected $fillable = [
        'name',
        'description',
        'images',
        'extra_data',
        'status',
        'creator_id',
        'category_id',
        'reviewed_by',
        'reviewed_at',
        'admin_notes',
    ];

    protected $casts = [
        'images' => 'array',
        'extra_data' => 'array',
        'reviewed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

	public function scopeAdminApplyFilters($query, array $filters)
	{
		return $query
			->when(!empty($filters['status']), fn($q) => $q->where('status', $filters['status']))
			->when(!empty($filters['creator_id']), fn($q) => $q->where('creator_id', $filters['creator_id']))
			->when(!empty($filters['reviewed_by']), fn($q) => $q->where('reviewed_by', $filters['reviewed_by']))
			->when(!empty($filters['category_id']), fn($q) => $q->where('category_id', $filters['category_id']))
			->when(!empty($filters['search']), function ($q) use ($filters) {
				$q->where(fn($sub) => $sub->where('name', 'ilike', "%{$filters['search']}%")
					->orWhere('description', 'ilike', "%{$filters['search']}%"));
			});
	}

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeAccepted($query)
    {
        return $query->where('status', self::STATUS_ACCEPTED);
    }

    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    public function scopeChangesRequested($query)
    {
        return $query->where('status', self::STATUS_CHANGES_REQUESTED);
    }
}

