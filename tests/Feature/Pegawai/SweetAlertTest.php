<?php

namespace Tests\Feature\Pegawai;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SweetAlertTest extends TestCase
{
    use RefreshDatabase;

    public function test_pegawai_pages_load_sweetalert_and_confirmation_dialogs(): void
    {
        $pegawai = User::factory()->create([
            'role' => 'pegawai',
        ]);

        $pages = [
            [
                'route' => 'pegawai.loans.index',
                'texts' => ['Kirim pengajuan peminjaman?', 'Ya, kirim pengajuan'],
            ],
            [
                'route' => 'pegawai.returns.index',
                'texts' => ['Kirim pengajuan pengembalian?', 'Ya, kirim pengajuan'],
            ],
            [
                'route' => 'pegawai.notifications.index',
                'texts' => [],
            ],
            [
                'route' => 'pegawai.profile.index',
                'texts' => ['Simpan foto profil?', 'Simpan tanda tangan?', 'Ubah password akun?'],
            ],
        ];

        foreach ($pages as $page) {
            $response = $this->actingAs($pegawai)->get(route($page['route']));

            $response->assertOk();
            $response->assertSee('assets/vendors/sweetalert2/sweetalert2.min.css', false);
            $response->assertSee('assets/vendors/sweetalert2/sweetalert2.all.min.js', false);
            $response->assertSee("document.querySelectorAll('form[data-swal-confirm]')", false);
            $response->assertSee('Logout akun?');

            foreach ($page['texts'] as $text) {
                $response->assertSee($text);
            }
        }
    }

    public function test_pegawai_flash_message_is_rendered_for_sweetalert(): void
    {
        $pegawai = User::factory()->create([
            'role' => 'pegawai',
        ]);

        $response = $this->actingAs($pegawai)
            ->withSession(['success' => 'Data pegawai berhasil disimpan.'])
            ->get(route('pegawai.assets.index'));

        $response->assertOk();
        $response->assertSee('Data pegawai berhasil disimpan.');
        $response->assertSee("success: 'Berhasil'", false);
    }
}
