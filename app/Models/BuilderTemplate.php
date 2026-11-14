<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * A reusable piece for the visual builder: a whole page ({document}), a section
 * ({section}) or a single block ({block}).
 */
#[Fillable(['name', 'description', 'type', 'category', 'payload', 'created_by_id'])]
class BuilderTemplate extends Model
{
    use HasUlids;

    public const TYPES = ['page', 'section', 'block'];

    protected function casts(): array
    {
        return ['payload' => 'array'];
    }
}
