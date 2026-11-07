<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['department_id', 'queue_date', 'last_number'])]
class DailyCounter extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'queue_date' => 'date:Y-m-d',
            'last_number' => 'integer',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}
