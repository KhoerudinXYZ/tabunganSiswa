<?php

namespace Tests\Feature;

use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WaliKelasAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private User $guruA;
    private User $guruB;
    private Kelas $kelasA;
    private Kelas $kelasB;
    private Siswa $siswaA;
    private Siswa $siswaB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->guruA = User::factory()->create(['role' => 'guru']);
        $this->guruB = User::factory()->create(['role' => 'guru']);

        $this->kelasA = Kelas::create([
            'nama_kelas' => 'Kelas A',
            'tahun_ajaran' => '2025/2026',
            'wali_kelas_id' => $this->guruA->id,
        ]);

        $this->kelasB = Kelas::create([
            'nama_kelas' => 'Kelas B',
            'tahun_ajaran' => '2025/2026',
            'wali_kelas_id' => $this->guruB->id,
        ]);

        $this->siswaA = Siswa::create([
            'kelas_id' => $this->kelasA->id,
            'nis' => 'A001',
            'nama' => 'Siswa Kelas A',
            'jenis_kelamin' => 'L',
            'saldo' => 50000,
        ]);

        $this->siswaB = Siswa::create([
            'kelas_id' => $this->kelasB->id,
            'nis' => 'B001',
            'nama' => 'Siswa Kelas B',
            'jenis_kelamin' => 'P',
            'saldo' => 20000,
        ]);
    }

    public function test_guru_cannot_view_siswa_edit_page_of_another_class(): void
    {
        $this->actingAs($this->guruA)
            ->get(route('siswa.edit', $this->siswaB))
            ->assertForbidden();
    }

    public function test_guru_can_view_siswa_edit_page_of_own_class(): void
    {
        $this->actingAs($this->guruA)
            ->get(route('siswa.edit', $this->siswaA))
            ->assertOk();
    }

    public function test_guru_cannot_update_siswa_of_another_class(): void
    {
        $this->actingAs($this->guruA)
            ->put(route('siswa.update', $this->siswaB), [
                'nis' => $this->siswaB->nis,
                'nama' => 'Diubah Paksa',
                'jenis_kelamin' => 'P',
                'kelas_id' => $this->kelasB->id,
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('siswas', ['id' => $this->siswaB->id, 'nama' => 'Diubah Paksa']);
    }

    public function test_guru_cannot_move_own_siswa_into_another_guru_class(): void
    {
        $this->actingAs($this->guruA)
            ->put(route('siswa.update', $this->siswaA), [
                'nis' => $this->siswaA->nis,
                'nama' => $this->siswaA->nama,
                'jenis_kelamin' => $this->siswaA->jenis_kelamin,
                'kelas_id' => $this->kelasB->id,
            ])
            ->assertForbidden();
    }

    public function test_guru_cannot_delete_siswa_of_another_class(): void
    {
        $this->actingAs($this->guruA)
            ->delete(route('siswa.destroy', $this->siswaB))
            ->assertForbidden();

        $this->assertDatabaseHas('siswas', ['id' => $this->siswaB->id]);
    }

    public function test_guru_cannot_create_siswa_in_another_guru_class(): void
    {
        $this->actingAs($this->guruA)
            ->post(route('siswa.store'), [
                'nis' => 'X999',
                'nama' => 'Siswa Baru',
                'jenis_kelamin' => 'L',
                'kelas_id' => $this->kelasB->id,
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('siswas', ['nis' => 'X999']);
    }

    public function test_siswa_index_only_lists_own_class_for_guru(): void
    {
        $response = $this->actingAs($this->guruA)->get(route('siswa.index'));

        $response->assertOk();
        $response->assertSee($this->siswaA->nama);
        $response->assertDontSee($this->siswaB->nama);
    }

    public function test_guru_cannot_view_transaksi_of_another_class(): void
    {
        $transaksi = Transaksi::create([
            'siswa_id' => $this->siswaB->id,
            'user_id' => $this->guruB->id,
            'tipe' => 'setor',
            'jumlah' => 10000,
            'tanggal' => now()->format('Y-m-d'),
        ]);

        $this->actingAs($this->guruA)
            ->get(route('transaksi.show', $transaksi))
            ->assertForbidden();
    }

    public function test_guru_cannot_create_transaksi_for_siswa_of_another_class(): void
    {
        $this->actingAs($this->guruA)
            ->post(route('transaksi.store'), [
                'siswa_id' => $this->siswaB->id,
                'tipe' => 'setor',
                'jumlah' => 10000,
                'tanggal' => now()->format('Y-m-d'),
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('transaksis', ['siswa_id' => $this->siswaB->id]);
    }

    public function test_guru_can_create_transaksi_for_siswa_of_own_class(): void
    {
        $this->actingAs($this->guruA)
            ->post(route('transaksi.store'), [
                'siswa_id' => $this->siswaA->id,
                'tipe' => 'setor',
                'jumlah' => 10000,
                'tanggal' => now()->format('Y-m-d'),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('transaksis', ['siswa_id' => $this->siswaA->id, 'jumlah' => 10000]);
    }

    public function test_admin_can_access_all_classes(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('siswa.edit', $this->siswaB))->assertOk();

        $response = $this->actingAs($admin)->get(route('siswa.index'));
        $response->assertOk();
        $response->assertSee($this->siswaA->nama);
        $response->assertSee($this->siswaB->nama);
    }
}
