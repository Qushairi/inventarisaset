<?php

namespace App\Http\Controllers\Pegawai;

use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends BasePegawaiController
{
    public function index(Request $request)
    {
        $pegawai = $this->currentPegawai();

        $search = $request->input('search');
        $eventFilter = $request->input('event');

        $perPage = (int) $request->input('per_page', 10);
        if (! in_array($perPage, [10, 25, 50], true)) {
            $perPage = 10;
        }

        $logs = ActivityLog::query()
            ->with('user')
            ->where('user_id', $pegawai->id)
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('ip_address', 'like', "%{$search}%");
                });
            })
            ->when($eventFilter, function ($query, $eventFilter) {
                $query->where('event', $eventFilter);
            })
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        $eventOptions = [
            'login' => 'Login',
            'logout' => 'Logout',
            'loan_created' => 'Peminjaman',
            'return_created' => 'Pengembalian',
            'profile_updated' => 'Foto Profil',
            'password_changed' => 'Ganti Password',
        ];

        return view('pegawai.activity-logs.index', $this->layoutData([
            'logs' => $logs,
            'eventOptions' => $eventOptions,
            'search' => $search,
            'selectedEvent' => $eventFilter,
            'totalLogs' => ActivityLog::query()->where('user_id', $pegawai->id)->count(),
        ]));
    }
}
