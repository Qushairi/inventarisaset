<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetReturn;
use App\Models\Loan;
use App\Models\User;
use App\Support\PegawaiNotificationService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReturnController extends Controller
{
    public function __construct(
        private readonly PegawaiNotificationService $pegawaiNotificationService,
    ) {
    }

    public function index()
    {
        $returns = AssetReturn::query()
            ->with(['asset', 'user', 'loan'])
            ->latest('returned_at')
            ->paginate(10)
            ->through(function (AssetReturn $return) {
                return [
                    'id' => $return->id,
                    'asset_name' => $return->asset?->name,
                    'asset_code' => $return->asset?->code,
                    'returned_at' => optional($return->returned_at)->format('d/m/Y'),
                    'verified_note' => $return->verified_note,
                    'condition' => $return->condition,
                    'condition_variant' => $this->conditionVariant($return->condition),
                    'status' => $return->status,
                    'status_variant' => $return->status === 'Terverifikasi' ? 'success' : 'info',
                    'status_note' => $return->status_note,
                    'report_number' => $return->report_number,
                    'report_note' => $return->report_note,
                ];
            });

        return view('admin.returns.index', [
            'returns' => $returns,
            'returnTotal' => AssetReturn::query()->count(),
        ]);
    }

    public function create()
    {
        return view('admin.returns.create', [
            'assets' => Asset::query()->orderBy('name')->get(),
            'employees' => User::query()->where('role', 'pegawai')->orderBy('name')->get(),
            'loans' => Loan::query()->with(['asset', 'user'])->latest('loan_date')->get(),
            'conditions' => $this->conditionOptions(),
            'statuses' => $this->statusOptions(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateReturn($request);

        $returnRecord = AssetReturn::query()->create($validated);

        $this->pegawaiNotificationService->sendReturnVerifiedNotification($returnRecord);

        return redirect()
            ->route('admin.returns.index')
            ->with('success', 'Data pengembalian berhasil disimpan.');
    }

    public function edit(AssetReturn $return)
    {
        return view('admin.returns.edit', [
            'returnRecord' => $return,
            'assets' => Asset::query()->orderBy('name')->get(),
            'employees' => User::query()->where('role', 'pegawai')->orderBy('name')->get(),
            'loans' => Loan::query()->with(['asset', 'user'])->latest('loan_date')->get(),
            'conditions' => $this->conditionOptions(),
            'statuses' => $this->statusOptions(),
        ]);
    }

    public function update(Request $request, AssetReturn $return)
    {
        $validated = $this->validateReturn($request, $return);
        $previousStatus = $return->status;

        $return->update($validated);

        if ($previousStatus !== $return->status || $return->status === 'Terverifikasi') {
            $this->pegawaiNotificationService->sendReturnVerifiedNotification($return);
        }

        return redirect()
            ->route('admin.returns.index')
            ->with('success', 'Data pengembalian berhasil diperbarui.');
    }

    public function destroy(AssetReturn $return)
    {
        $return->delete();

        return redirect()
            ->route('admin.returns.index')
            ->with('success', 'Data pengembalian berhasil dihapus.');
    }

    private function conditionVariant(string $condition): string
    {
        return match ($condition) {
            'Rusak Ringan' => 'warning',
            'Rusak Berat' => 'danger',
            default => 'success',
        };
    }

    private function conditionOptions(): array
    {
        return ['Baik', 'Rusak Ringan', 'Rusak Berat'];
    }

    private function statusOptions(): array
    {
        return ['Terverifikasi', 'Menunggu Verifikasi'];
    }

    private function validateReturn(Request $request, ?AssetReturn $return = null): array
    {
        return $request->validate([
            'loan_id' => ['nullable', 'exists:loans,id'],
            'asset_id' => ['required', 'exists:assets,id'],
            'user_id' => ['required', 'exists:users,id'],
            'returned_at' => ['required', 'date'],
            'verified_note' => ['nullable', 'string', 'max:255'],
            'condition' => ['required', Rule::in($this->conditionOptions())],
            'status' => ['required', Rule::in($this->statusOptions())],
            'status_note' => ['nullable', 'string', 'max:255'],
            'report_number' => ['required', 'string', 'max:100', Rule::unique('asset_returns', 'report_number')->ignore($return?->id)],
            'report_note' => ['nullable', 'string', 'max:255'],
        ]);
    }
}
