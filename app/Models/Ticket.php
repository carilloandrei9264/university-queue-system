<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'department_id',
    'service_id',
    'student_id',
    'queue_date',
    'number',
    'code',
    'status',
    'called_at',
    'completed_at',
    'cancelled_at',
    'handled_by',
    'service_window_id',
])]
class Ticket extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'queue_date' => 'date:Y-m-d',
            'number' => 'integer',
            'called_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function serviceWindow(): BelongsTo
    {
        return $this->belongsTo(ServiceWindow::class);
    }
}
