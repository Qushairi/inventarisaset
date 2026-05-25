<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LocationController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $locations = Location::query()
            ->when($filters['search'] ?? null, function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', '%'.$search.'%')
                        ->orWhere('code', 'like', '%'.$search.'%')
                        ->orWhere('address', 'like', '%'.$search.'%')
                        ->orWhere('address_note', 'like', '%'.$search.'%')
                        ->orWhere('description', 'like', '%'.$search.'%')
                        ->orWhere('note', 'like', '%'.$search.'%');
                });
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Location $location) => [
                'name' => $location->name,
                'code' => $location->code,
                'address' => $location->address,
                'address_note' => $location->address_note ?? 'Alamat lokasi tersimpan pada sistem.',
                'description' => $location->description,
                'note' => $location->note ?? 'Catatan lokasi tersedia.',
            ]);

        return view('admin.locations.index', [
            'locations' => $locations,
            'filters' => $filters,
            'hasActiveFilters' => collect($filters)->filter(fn ($value) => filled($value))->isNotEmpty(),
        ]);
    }

    public function create()
    {
        return view('admin.locations.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:50', 'unique:locations,code'],
            'address' => ['required', 'string', 'max:255'],
            'address_note' => ['nullable', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        Location::query()->create($validated);

        return redirect()
            ->route('admin.locations.index')
            ->with('success', 'Lokasi "' . $validated['name'] . '" berhasil disimpan.');
    }

    public function edit(Location $location)
    {
        return view('admin.locations.edit', [
            'location' => $location,
        ]);
    }

    public function update(Request $request, Location $location)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:50', Rule::unique('locations', 'code')->ignore($location->id)],
            'address' => ['required', 'string', 'max:255'],
            'address_note' => ['nullable', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $location->update($validated);

        return redirect()
            ->route('admin.locations.index')
            ->with('success', 'Lokasi "' . $validated['name'] . '" berhasil diperbarui.');
    }

    public function destroy(Location $location)
    {
        if ($location->assets()->exists()) {
            return redirect()
                ->route('admin.locations.index')
                ->with('error', 'Lokasi tidak bisa dihapus karena masih dipakai oleh data aset.');
        }

        $name = $location->name;
        $location->delete();

        return redirect()
            ->route('admin.locations.index')
            ->with('success', 'Lokasi "' . $name . '" berhasil dihapus.');
    }
}
