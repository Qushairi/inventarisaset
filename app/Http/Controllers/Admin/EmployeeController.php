<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $editId = $request->integer('edit');
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $employees = User::query()
            ->where('role', 'pegawai')
            ->when($editId, fn ($query, int $id) => $query->whereKey($id))
            ->when($filters['search'] ?? null, function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', '%'.$search.'%')
                        ->orWhere('nip', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%');
                });
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString()
            ->through(function (User $user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'nip' => $user->nip,
                    'account_id' => '#' . $user->id,
                    'initials' => Str::upper(Str::of($user->name)->explode(' ')->take(2)->map(fn ($part) => Str::substr($part, 0, 1))->join('')),
                    'role' => Str::title($user->role),
                    'email' => $user->email,
                    'email_note' => 'Digunakan sebagai akun login ke sistem.',
                    'registered_at' => optional($user->created_at)->format('d/m/Y'),
                    'registered_time' => 'Pukul ' . optional($user->created_at)->format('H:i') . ' WIB',
                ];
            });

        return view('admin.employees.index', [
            'employees' => $employees,
            'filters' => $filters,
            'hasActiveFilters' => collect($filters)->filter(fn ($value) => filled($value))->isNotEmpty(),
        ]);
    }

    public function create()
    {
        return redirect()->route('admin.employees.index', ['create' => 1]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'nip' => ['required', 'string', 'max:30', 'regex:/^[0-9]+$/', 'unique:users,nip'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        User::query()->create([
            'name' => $validated['name'],
            'nip' => $validated['nip'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'pegawai',
            'email_verified_at' => now(),
        ]);

        return redirect()
            ->route('admin.employees.index')
            ->with('success', 'Pegawai "' . $validated['name'] . '" berhasil disimpan.');
    }

    public function edit(User $employee)
    {
        abort_if($employee->role !== 'pegawai', 404);

        return redirect()->route('admin.employees.index', ['edit' => $employee->id]);
    }

    public function update(Request $request, User $employee)
    {
        abort_if($employee->role !== 'pegawai', 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'nip' => ['required', 'string', 'max:30', 'regex:/^[0-9]+$/', Rule::unique('users', 'nip')->ignore($employee->id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($employee->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $payload = [
            'name' => $validated['name'],
            'nip' => $validated['nip'],
            'email' => $validated['email'],
        ];

        if (!empty($validated['password'])) {
            $payload['password'] = Hash::make($validated['password']);
        }

        $employee->update($payload);

        if ($employee->wasChanged(['name', 'nip'])) {
            $employee->receivedBeritaAcaras()->update([
                'pdf_generated_at' => null,
            ]);
        }

        return redirect()
            ->route('admin.employees.index')
            ->with('success', 'Pegawai "' . $validated['name'] . '" berhasil diperbarui.');
    }

    public function destroy(User $employee)
    {
        abort_if($employee->role !== 'pegawai', 404);

        if ($employee->loans()->exists() || $employee->returns()->exists()) {
            return redirect()
                ->route('admin.employees.index')
                ->with('error', 'Pegawai tidak bisa dihapus karena masih memiliki riwayat transaksi.');
        }

        $name = $employee->name;
        $employee->delete();

        return redirect()
            ->route('admin.employees.index')
            ->with('success', 'Pegawai "' . $name . '" berhasil dihapus.');
    }
}
