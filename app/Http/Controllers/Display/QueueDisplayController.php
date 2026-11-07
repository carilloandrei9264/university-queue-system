<?php

namespace App\Http\Controllers\Display;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Ticket;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class QueueDisplayController extends Controller
{
    public function index(): View
    {
        return view('display.departments', [
            'departments' => Department::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function show(Department $department): View
    {
        abort_unless($department->is_active, 404);

        $queueDate = Carbon::now(config('app.timezone'))->toDateString();
        $baseQuery = Ticket::query()
            ->where('department_id', $department->id)
            ->where('queue_date', $queueDate);

        return view('display.show', [
            'department' => $department,
            'servingTicket' => (clone $baseQuery)
                ->where('status', 'serving')
                ->orderByDesc('called_at')
                ->first(['code', 'called_at']),
            'nextTicket' => (clone $baseQuery)
                ->where('status', 'waiting')
                ->orderBy('number')
                ->first(['code']),
        ]);
    }
}
