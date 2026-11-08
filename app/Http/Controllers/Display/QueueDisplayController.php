<?php

namespace App\Http\Controllers\Display;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\ServiceWindow;
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
        return view('display.show', [
            'department' => $department,
            'windows' => ServiceWindow::query()
                ->where('department_id', $department->id)
                ->where('is_active', true)
                ->with(['servingTicket' => fn ($query) => $query->where('queue_date', $queueDate)])
                ->orderBy('position')
                ->get(),
            'nextTicket' => Ticket::query()
                ->where('department_id', $department->id)
                ->where('queue_date', $queueDate)
                ->where('status', 'waiting')
                ->orderBy('number')
                ->first(['code']),
        ]);
    }
}
