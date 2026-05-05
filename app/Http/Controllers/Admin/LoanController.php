<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\Loan;
use App\Models\User;
use App\Support\PegawaiNotificationService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LoanController extends Controller
{
    public function __construct(
        private readonly PegawaiNotificationService $pegawaiNotificationService,
    ) {
    }

    public function index()
    {
        $loans = Loan::query()
            ->with(['asset', 'user'])
            ->latest('loan_date')
            ->paginate(10)
            ->through(function (Loan $loan) {
                return [
                    'id' => $loan->id,
                    'asset_name' => $loan->asset?->name,
                    'asset_code' => $loan->asset?->code,
                    'employee_name' => $loan->user?->name,
                    'employee_email' => $loan->user?->email,
                    'loan_date' => optional($loan->loan_date)->format('d/m/Y'),
                    'return_plan' => 'Rencana kembali ' . optional($loan->planned_return_date)->format('d/m/Y'),
                    'status' => $loan->status,
                    'status_variant' => $this->statusVariant($loan->status),
                    'status_note' => $loan->status_note,
                ];
            });

        return view('admin.loans.index', [
            'loans' => $loans,
            'loanTotal' => Loan::query()->count(),
        ]);
    }

    public function create()
    {
        return view('admin.loans.create', [
            'assets' => Asset::query()->orderBy('name')->get(),
            'employees' => User::query()->where('role', 'pegawai')->orderBy('name')->get(),
            'statuses' => $this->statusOptions(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateLoan($request);

        $loan = Loan::query()->create($validated);

        $this->pegawaiNotificationService->sendLoanStatusNotification($loan);

        return redirect()
            ->route('admin.loans.index')
            ->with('success', 'Data peminjaman berhasil disimpan.');
    }

    public function edit(Loan $loan)
    {
        return view('admin.loans.edit', [
            'loan' => $loan,
            'assets' => Asset::query()->orderBy('name')->get(),
            'employees' => User::query()->where('role', 'pegawai')->orderBy('name')->get(),
            'statuses' => $this->statusOptions(),
        ]);
    }

    public function update(Request $request, Loan $loan)
    {
        $validated = $this->validateLoan($request, $loan);
        $previousStatus = $loan->status;

        $loan->update($validated);

        if ($previousStatus !== $loan->status) {
            $this->pegawaiNotificationService->sendLoanStatusNotification($loan);
        }

        return redirect()
            ->route('admin.loans.index')
            ->with('success', 'Data peminjaman berhasil diperbarui.');
    }

    public function updateStatus(Request $request, Loan $loan)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['Disetujui', 'Ditolak'])],
        ]);

        if ($loan->status !== 'Menunggu') {
            return redirect()
                ->route('admin.loans.index')
                ->with('error', 'Pengajuan peminjaman ini sudah diproses sebelumnya.');
        }

        $loan->update([
            'status' => $validated['status'],
        ]);

        $this->pegawaiNotificationService->sendLoanStatusNotification($loan);

        return redirect()
            ->route('admin.loans.index')
            ->with('success', 'Pengajuan peminjaman berhasil '.($validated['status'] === 'Disetujui' ? 'diterima.' : 'ditolak.'));
    }

    public function destroy(Loan $loan)
    {
        if ($loan->returnRecord()->exists()) {
            return redirect()
                ->route('admin.loans.index')
                ->with('error', 'Peminjaman tidak bisa dihapus karena sudah memiliki data pengembalian.');
        }

        $loan->delete();

        return redirect()
            ->route('admin.loans.index')
            ->with('success', 'Data peminjaman berhasil dihapus.');
    }

    private function statusVariant(string $status): string
    {
        return match ($status) {
            'Ditolak' => 'danger',
            'Menunggu' => 'warning',
            default => 'success',
        };
    }

    private function statusOptions(): array
    {
        return ['Menunggu', 'Disetujui', 'Selesai', 'Ditolak'];
    }

    private function validateLoan(Request $request, ?Loan $loan = null): array
    {
        $uniqueRule = Rule::unique('loans')->where(fn ($query) => $query
            ->where('asset_id', $request->input('asset_id'))
            ->where('user_id', $request->input('user_id'))
            ->where('loan_date', $request->input('loan_date')));

        if ($loan) {
            $uniqueRule = $uniqueRule->ignore($loan->id);
        }

        return $request->validate([
            'asset_id' => ['required', 'exists:assets,id'],
            'user_id' => ['required', 'exists:users,id'],
            'loan_date' => ['required', 'date', $uniqueRule],
            'planned_return_date' => ['nullable', 'date', 'after_or_equal:loan_date'],
            'status' => ['required', Rule::in($this->statusOptions())],
            'status_note' => ['nullable', 'string', 'max:255'],
        ]);
    }
}
