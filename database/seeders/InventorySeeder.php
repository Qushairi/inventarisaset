<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\AssetReturn;
use App\Models\Category;
use App\Models\Loan;
use App\Models\Location;
use App\Models\User;
use DateTimeInterface;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Collection;

class InventorySeeder extends Seeder
{
    public function run(): void
    {
        $this->seedUsers();
        $categories = $this->seedCategories();
        $locations = $this->seedLocations();
        $employees = $this->employeesByEmail();
        $assets = $this->seedAssets($categories, $locations);
        $loans = $this->seedLoans($assets, $employees);
        $this->seedReturns($assets, $employees, $loans);
    }

    private function seedUsers(): void
    {
        User::query()
            ->whereIn('email', [
                'jidan@gmail.com',
                'pegawai1@bengkalis.go.id',
                'pegawai@bengkalis.go.id',
            ])
            ->whereDoesntHave('loans')
            ->whereDoesntHave('returns')
            ->delete();

        User::query()->updateOrCreate(
            ['email' => 'admin@inventarisaset.test'],
            [
                'name' => 'Admin Inventaris',
                'password' => Hash::make('Admin123!'),
                'role' => 'admin',
                'email_verified_at' => now(),
            ],
        );

        $employeePayloads = [[
            'name' => 'Muhammad Amien',
            'email' => 'amien@bengkalis.go.id',
            'password' => Hash::make('Pegawai123!'),
            'role' => 'pegawai',
            'email_verified_at' => now(),
            'created_at' => now()->setDate(2026, 3, 9)->setTime(14, 21),
            'updated_at' => now(),
        ]];

        User::query()->upsert(
            $employeePayloads,
            ['email'],
            ['name', 'password', 'role', 'email_verified_at', 'created_at', 'updated_at'],
        );
    }

    /**
     * @return Collection<string, Category>
     */
    private function seedCategories(): Collection
    {
        Category::query()->upsert([
            [
                'name' => 'Transportasi',
                'code' => '12399',
                'description' => 'Kendaraan',
                'note' => 'Deskripsi kategori sudah tersedia.',
            ],
            [
                'name' => 'Bangunan',
                'code' => '154',
                'description' => 'Gedung dan Ruang Kerja',
                'note' => 'Deskripsi kategori sudah tersedia.',
            ],
            [
                'name' => 'Mesin',
                'code' => '123',
                'description' => 'Hardware',
                'note' => 'Deskripsi kategori sudah tersedia.',
            ],
        ], ['code'], ['name', 'description', 'note']);

        return Category::query()->get()->keyBy('code');
    }

    /**
     * @return Collection<string, Location>
     */
    private function seedLocations(): Collection
    {
        Location::query()->upsert([
            [
                'name' => 'Ruang Bidang SMP',
                'code' => '23371',
                'address' => 'Jl. Pertanian',
                'address_note' => 'Alamat lokasi tersimpan pada sistem.',
                'description' => 'Ruangan Bidang SMP',
                'note' => 'Catatan lokasi tersedia.',
            ],
            [
                'name' => 'Ruangan Bidang SMP',
                'code' => '234',
                'address' => 'r.234',
                'address_note' => 'Alamat lokasi tersimpan pada sistem.',
                'description' => 'Area Penyimpanan Perangkat',
                'note' => 'Catatan lokasi tersedia.',
            ],
        ], ['code'], ['name', 'address', 'address_note', 'description', 'note']);

        return Location::query()->get()->keyBy('code');
    }

    /**
     * @return Collection<string, User>
     */
    private function employeesByEmail(): Collection
    {
        return User::query()->where('role', 'pegawai')->get()->keyBy('email');
    }

    /**
     * @param  Collection<string, Category>  $categories
     * @param  Collection<string, Location>  $locations
     * @return Collection<string, Asset>
     */
    private function seedAssets(Collection $categories, Collection $locations): Collection
    {
        Asset::query()->upsert([
            [
                'name' => 'Mobil',
                'code' => '123999',
                'note' => 'Bisa dipinjam',
                'image_path' => 'assets/images/samples/motorcycle.jpg',
                'category_id' => $categories['12399']->id,
                'location_id' => $locations['23371']->id,
                'condition' => 'Baik',
                'status' => 'Tersedia',
                'acquisition_price' => 300000000,
                'acquired_at' => '2026-03-13',
            ],
            [
                'name' => 'Komputer',
                'code' => '142',
                'note' => 'Siap digunakan',
                'image_path' => 'assets/images/samples/1.png',
                'category_id' => $categories['123']->id,
                'location_id' => $locations['234']->id,
                'condition' => 'Baik',
                'status' => 'Tersedia',
                'acquisition_price' => 10000000,
                'acquired_at' => '2026-03-07',
            ],
            [
                'name' => 'Komputer',
                'code' => '1233',
                'note' => 'Siap digunakan',
                'image_path' => null,
                'category_id' => $categories['123']->id,
                'location_id' => $locations['234']->id,
                'condition' => 'Baik',
                'status' => 'Tersedia',
                'acquisition_price' => 1000000,
                'acquired_at' => '2026-03-07',
            ],
            [
                'name' => 'Komputer',
                'code' => '1234',
                'note' => 'Perlu pengecekan minor',
                'image_path' => null,
                'category_id' => $categories['123']->id,
                'location_id' => $locations['234']->id,
                'condition' => 'Rusak Ringan',
                'status' => 'Tersedia',
                'acquisition_price' => 1000000,
                'acquired_at' => '2026-03-07',
            ],
        ], ['code'], ['name', 'note', 'image_path', 'category_id', 'location_id', 'condition', 'status', 'acquisition_price', 'acquired_at']);

        return Asset::query()->get()->keyBy('code');
    }

    /**
     * @param  Collection<string, Asset>  $assets
     * @param  Collection<string, User>  $employees
     * @return Collection<string, Loan>
     */
    private function seedLoans(Collection $assets, Collection $employees): Collection
    {
        Loan::query()->upsert([
            [
                'asset_id' => $assets['1234']->id,
                'user_id' => $employees['amien@bengkalis.go.id']->id,
                'loan_date' => '2026-04-22',
                'planned_return_date' => '2026-04-22',
                'status' => 'Selesai',
                'status_note' => 'Diproses oleh Admin Dinas',
            ],
            [
                'asset_id' => $assets['1234']->id,
                'user_id' => $employees['amien@bengkalis.go.id']->id,
                'loan_date' => '2026-04-17',
                'planned_return_date' => '2026-04-17',
                'status' => 'Selesai',
                'status_note' => 'Diproses oleh Admin Dinas',
            ],
            [
                'asset_id' => $assets['1234']->id,
                'user_id' => $employees['amien@bengkalis.go.id']->id,
                'loan_date' => '2026-04-16',
                'planned_return_date' => '2026-04-17',
                'status' => 'Ditolak',
                'status_note' => 'Tidak ada stok tersedia saat pengajuan.',
            ],
            [
                'asset_id' => $assets['123999']->id,
                'user_id' => $employees['amien@bengkalis.go.id']->id,
                'loan_date' => '2026-04-15',
                'planned_return_date' => '2026-04-15',
                'status' => 'Selesai',
                'status_note' => 'Diproses oleh Admin Dinas',
            ],
            [
                'asset_id' => $assets['1233']->id,
                'user_id' => $employees['amien@bengkalis.go.id']->id,
                'loan_date' => '2026-04-10',
                'planned_return_date' => '2026-04-10',
                'status' => 'Menunggu',
                'status_note' => 'Menunggu konfirmasi akhir dari admin.',
            ],
        ], ['asset_id', 'user_id', 'loan_date'], ['planned_return_date', 'status', 'status_note']);

        return Loan::query()
            ->with(['asset', 'user'])
            ->get()
            ->keyBy(fn (Loan $loan) => $this->loanLookupKey($loan));
    }

    /**
     * @param  Collection<string, Asset>  $assets
     * @param  Collection<string, User>  $employees
     * @param  Collection<string, Loan>  $loans
     */
    private function seedReturns(Collection $assets, Collection $employees, Collection $loans): void
    {
        AssetReturn::query()->upsert([
            [
                'loan_id' => $loans['1234|amien@bengkalis.go.id|2026-04-22']->id,
                'asset_id' => $assets['1234']->id,
                'user_id' => $employees['amien@bengkalis.go.id']->id,
                'returned_at' => '2026-04-20',
                'verified_note' => 'Diverifikasi Admin Dinas',
                'condition' => 'Rusak Ringan',
                'status' => 'Terverifikasi',
                'status_note' => 'Rusak ringan',
                'report_number' => 'BA-20260422065603-0010',
                'report_note' => 'Berita acara sudah tersedia.',
            ],
            [
                'loan_id' => $loans['1234|amien@bengkalis.go.id|2026-04-17']->id,
                'asset_id' => $assets['1234']->id,
                'user_id' => $employees['amien@bengkalis.go.id']->id,
                'returned_at' => '2026-04-17',
                'verified_note' => 'Diverifikasi Admin Dinas',
                'condition' => 'Rusak Berat',
                'status' => 'Terverifikasi',
                'status_note' => 'Rusak',
                'report_number' => 'BA-20260417064102-0009',
                'report_note' => 'Berita acara sudah tersedia.',
            ],
            [
                'loan_id' => $loans['123999|amien@bengkalis.go.id|2026-04-15']->id,
                'asset_id' => $assets['123999']->id,
                'user_id' => $employees['amien@bengkalis.go.id']->id,
                'returned_at' => '2026-04-16',
                'verified_note' => 'Diverifikasi Admin Dinas',
                'condition' => 'Rusak Ringan',
                'status' => 'Terverifikasi',
                'status_note' => 'Rusak ringan',
                'report_number' => 'BA-20260416080246-0008',
                'report_note' => 'Berita acara sudah tersedia.',
            ],
            [
                'loan_id' => $loans['1233|amien@bengkalis.go.id|2026-04-10']->id,
                'asset_id' => $assets['1233']->id,
                'user_id' => $employees['amien@bengkalis.go.id']->id,
                'returned_at' => '2026-04-15',
                'verified_note' => 'Diverifikasi Admin Dinas',
                'condition' => 'Baik',
                'status' => 'Terverifikasi',
                'status_note' => 'Mntp',
                'report_number' => 'BA-20260422063454-0007',
                'report_note' => 'Berita acara sudah tersedia.',
            ],
            [
                'loan_id' => null,
                'asset_id' => $assets['1234']->id,
                'user_id' => $employees['amien@bengkalis.go.id']->id,
                'returned_at' => '2026-04-10',
                'verified_note' => 'Diverifikasi Admin Dinas',
                'condition' => 'Rusak Berat',
                'status' => 'Terverifikasi',
                'status_note' => 'Rusak',
                'report_number' => 'BA-20260410094909-0006',
                'report_note' => 'Berita acara sudah tersedia.',
            ],
        ], ['report_number'], ['loan_id', 'asset_id', 'user_id', 'returned_at', 'verified_note', 'condition', 'status', 'status_note', 'report_note']);
    }

    private function loanLookupKey(Loan $loan): string
    {
        $loanDate = $loan->loan_date;
        $loanDateKey = $loanDate instanceof DateTimeInterface
            ? $loanDate->format('Y-m-d')
            : (string) $loanDate;

        return $loan->asset->code.'|'.$loan->user->email.'|'.$loanDateKey;
    }
}
