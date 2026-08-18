<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetReturn;
use App\Models\Loan;
use App\Models\User;
use App\Support\AssetStateService;
use App\Support\AssetReturnLetterService;
use App\Support\PegawaiNotificationService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReturnController extends Controller
{
    public function __construct(
        private readonly AssetStateService $assetStateService,
        private readonly AssetReturnLetterService $assetReturnLetterService,
        private readonly PegawaiNotificationService $pegawaiNotificationService,
    ) {
    }

    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'condition' => ['nullable', Rule::in($this->conditionOptions())],
        ]);

        $returns = AssetReturn::query()
            ->with(['asset', 'user', 'loan.asset', 'loan.user'])
            ->when($filters['search'] ?? null, function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('verified_note', 'like', '%'.$search.'%')
                        ->orWhere('status_note', 'like', '%'.$search.'%')
                        ->orWhere('report_number', 'like', '%'.$search.'%')
                        ->orWhere('report_note', 'like', '%'.$search.'%')
                        ->orWhereHas('asset', function ($query) use ($search) {
                            $query->where('name', 'like', '%'.$search.'%')
                                ->orWhere('code', 'like', '%'.$search.'%');
                        })
                        ->orWhereHas('user', function ($query) use ($search) {
                            $query->where('name', 'like', '%'.$search.'%')
                                ->orWhere('email', 'like', '%'.$search.'%');
                        });
                });
            })
            ->when($filters['condition'] ?? null, fn ($query, string $condition) => $query->where('condition', $condition))
            ->latest('returned_at')
            ->paginate(10)
            ->withQueryString()
            ->through(function (AssetReturn $return) {
                $loan = $return->loan;
                $loanDate = $loan?->loan_date;
                $returnedAt = $return->returned_at;

                return [
                    'id' => $return->id,
                    'asset_name' => $return->asset?->name ?? $loan?->asset?->name,
                    'asset_code' => $return->asset?->code ?? $loan?->asset?->code,
                    'employee_name' => $return->user?->name ?? $loan?->user?->name,
                    'employee_email' => $return->user?->email ?? $loan?->user?->email,
                    'loan_date' => optional($loanDate)->format('d/m/Y'),
                    'planned_return_date' => optional($loan?->planned_return_date)->format('d/m/Y'),
                    'loan_quantity' => max(1, (int) ($loan?->quantity ?? 1)),
                    'loan_duration' => $loanDate && $returnedAt
                        ? $loanDate->diffInDays($returnedAt).' hari'
                        : '-',
                    'returned_at' => optional($returnedAt)->format('d/m/Y'),
                    'verified_note' => $return->verified_note,
                    'condition' => $return->condition,
                    'condition_variant' => $this->conditionVariant($return->condition),
                    'status' => $return->status,
                    'status_variant' => match ($return->status) {
                        'Terverifikasi', 'Disetujui' => 'success',
                        'Ditolak' => 'danger',
                        default => 'warning',
                    },
                    'status_note' => $return->status_note,
                    'report_number' => $return->report_number,
                    'report_note' => $return->report_note,
                ];
            });

        return view('admin.returns.index', [
            'returns' => $returns,
            'returnTotal' => $returns->total(),
            'conditions' => $this->conditionOptions(),
            'createAssets' => Asset::query()->orderBy('name')->get(),
            'createEmployees' => User::query()->where('role', 'pegawai')->orderBy('name')->get(),
            'createLoans' => Loan::query()
                ->with(['asset', 'user'])
                ->where('status', 'Disetujui')
                ->whereDoesntHave('returnRecord')
                ->latest('loan_date')
                ->get(),
            'filters' => $filters,
            'hasActiveFilters' => collect($filters)->filter(fn ($value) => filled($value))->isNotEmpty(),
        ]);
    }

    public function create()
    {
        return view('admin.returns.create', [
            'assets' => Asset::query()->orderBy('name')->get(),
            'employees' => User::query()->where('role', 'pegawai')->orderBy('name')->get(),
            'loans' => Loan::query()->with(['asset', 'user'])->latest('loan_date')->get(),
            'conditions' => $this->conditionOptions(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateReturn($request);

        $returnRecord = AssetReturn::query()->create($validated);
        $this->assetStateService->applyReturnStock($returnRecord);
        $this->assetStateService->syncLoanById($returnRecord->loan_id);
        $returnRecord->refresh();
        $this->assetStateService->syncAssetIds([$returnRecord->asset_id, $returnRecord->stock_asset_id], true);

        $this->pegawaiNotificationService->sendReturnVerifiedNotification($returnRecord);

        return redirect()
            ->route('admin.returns.index')
            ->with('success', 'Data pengembalian berhasil disimpan.');
    }

    public function updateStatus(Request $request, AssetReturn $return)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['Terverifikasi', 'Ditolak'])],
        ]);

        $status = $validated['status'];

        if ($status === 'Terverifikasi') {
            $return->update([
                'status' => 'Terverifikasi',
                'verified_note' => 'Pengembalian aset telah diverifikasi dan disetujui admin.',
            ]);

            $this->assetStateService->applyReturnStock($return);
            $this->assetStateService->syncLoanById($return->loan_id);
            $return->refresh();
            $this->assetStateService->syncAssetIds([$return->asset_id, $return->stock_asset_id], true);

            $this->pegawaiNotificationService->sendReturnVerifiedNotification($return);

            $message = 'Pengembalian aset berhasil diverifikasi dan disetujui.';
        } else {
            $return->update([
                'status' => 'Ditolak',
                'verified_note' => 'Pengembalian aset ditolak admin.',
            ]);

            $this->assetStateService->syncLoanById($return->loan_id);

            $message = 'Pengembalian aset telah ditolak.';
        }

        return redirect()
            ->route('admin.returns.index')
            ->with('success', $message);
    }

    public function edit(AssetReturn $return)
    {
        return view('admin.returns.edit', [
            'returnRecord' => $return,
            'assets' => Asset::query()->orderBy('name')->get(),
            'employees' => User::query()->where('role', 'pegawai')->orderBy('name')->get(),
            'loans' => Loan::query()->with(['asset', 'user'])->latest('loan_date')->get(),
            'conditions' => $this->conditionOptions(),
        ]);
    }

    public function update(Request $request, AssetReturn $return)
    {
        $previousAssetId = $return->asset_id;
        $previousLoanId = $return->loan_id;
        $validated = $this->validateReturn($request, $return);
        $previousStatus = $return->status;

        $this->assetStateService->reverseReturnStock($return);
        $return->update($validated);
        $this->assetStateService->applyReturnStock($return);
        $return->refresh();
        $this->assetStateService->syncLoanById($previousLoanId);
        $this->assetStateService->syncLoanById($return->loan_id);
        $this->assetStateService->syncAssetIds([$previousAssetId, $return->asset_id, $return->stock_asset_id], true);

        if ($previousStatus !== $return->status || $return->status === 'Terverifikasi') {
            $this->pegawaiNotificationService->sendReturnVerifiedNotification($return);
        }

        return redirect()
            ->route('admin.returns.index')
            ->with('success', 'Data pengembalian berhasil diperbarui.');
    }

    public function destroy(AssetReturn $return)
    {
        $assetId = $return->asset_id;
        $loanId = $return->loan_id;
        $stockAssetId = $return->stock_asset_id;
        $this->assetStateService->reverseReturnStock($return);
        $return->delete();
        $this->assetStateService->syncLoanById($loanId);
        $this->assetStateService->syncAssetIds([$assetId, $stockAssetId], true);

        return redirect()
            ->route('admin.returns.index')
            ->with('success', 'Data pengembalian berhasil dihapus.');
    }

    public function showLetter(AssetReturn $return)
    {
        $return->loadMissing(['asset.category', 'user', 'loan.asset.category', 'loan.user', 'loan.approvedBy']);

        return view('admin.returns.letter', array_merge(
            $this->assetReturnLetterService->previewData($return, $this->currentAdmin()),
            [
                'backUrl' => route('admin.returns.index'),
                'downloadUrl' => route('admin.returns.letter.download', $return),
                'editUrl' => route('admin.returns.edit', $return),
            ],
        ));
    }

    public function downloadLetter(AssetReturn $return)
    {
        $return->loadMissing(['asset.category', 'user', 'loan.asset.category', 'loan.user', 'loan.approvedBy']);

        return response($this->assetReturnLetterService->pdfBinary($return, $this->currentAdmin()))
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="'.$this->assetReturnLetterService->pdfFilename($return).'"');
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

    private function validateReturn(Request $request, ?AssetReturn $return = null): array
    {
        $validated = $request->validate([
            'loan_id' => ['nullable', 'exists:loans,id'],
            'asset_id' => ['required', 'exists:assets,id'],
            'user_id' => ['required', 'exists:users,id'],
            'returned_at' => ['required', 'date'],
            'verified_note' => ['nullable', 'string', 'max:255'],
            'condition' => ['required', Rule::in($this->conditionOptions())],
            'status_note' => ['nullable', 'string', 'max:255'],
            'report_number' => ['required', 'string', 'max:100', Rule::unique('asset_returns', 'report_number')->ignore($return?->id)],
            'report_note' => ['nullable', 'string', 'max:255'],
        ]);

        $validated['status'] = 'Terverifikasi';

        return $validated;
    }

    private function currentAdmin(): ?User
    {
        $user = auth()->user();

        return $user instanceof User && $user->role === 'admin'
            ? $user
            : null;
    }
}
