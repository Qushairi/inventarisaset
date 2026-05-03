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
    public function index()
    {
        $employees = User::query()
            ->where('role', 'pegawai')
            ->orderBy('name')
            ->paginate(10)
            ->through(function (User $user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
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
        ]);
    }

    public function create()
    {
        return view('admin.employees.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        User::query()->create([
            'name' => $validated['name'],
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

        return view('admin.employees.edit', [
            'employee' => $employee,
        ]);
    }

    public function update(Request $request, User $employee)
    {
        abort_if($employee->role !== 'pegawai', 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($employee->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $payload = [
            'name' => $validated['name'],
            'email' => $validated['email'],
        ];

        if (!empty($validated['password'])) {
            $payload['password'] = Hash::make($validated['password']);
        }

        $employee->update($payload);

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
