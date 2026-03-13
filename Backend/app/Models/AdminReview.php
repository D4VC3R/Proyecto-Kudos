<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @deprecated Reemplazado por Proposal. Mantener temporalmente para compatibilidad hasta retirar referencias.
 */
class AdminReview extends Model
{
    use HasFactory, HasUuids;

    protected $guarded = [];
}
