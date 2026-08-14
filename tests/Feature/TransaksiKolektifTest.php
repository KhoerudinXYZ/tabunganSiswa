<?php

namespace Tests\Feature;

use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransaksiKolektifTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $guruA;
    private User $guruB;
    private User $orangTua;
    private Kelas $kelasA;
    private Kelas $kelasB;
    private Siswa $siswaA1;
    private Siswa $siswaA2;
    private Siswa $siswaB1;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->guruA = User::factory()->create(['role' => 'guru']);
        $this->guruB = User::factory()->create(['role' => 'guru']);
        $this->orangTua = User::factory()->create(['role' => 'orang_tua']);

        $this->kelasA = Kelas::create([
            'nama_kelas' => 'Kelas 1-A',
            'tahun_ajaran' => '2025/2026',
            'wali_kelas_id' => $this->guruA->id,
        ]);

        $this->kelasB = Kelas::create([
            'nama_kelas' => 'Kelas 1-B',
            'tahun_ajaran' => '2025/2026',
            'wali_kelas_id' => $this->guruB->id,
        ]);

        $this->siswaA1 = Siswa::create([
            'kelas_id' => $this->kelasA->id,
            'nis' => 'A001',
            'nama' => 'Siswa A1',
            'jenis_kelamin' => 'L',
            'saldo' => 10000,
        ]);

        $this->siswaA2 = Siswa::create([
            'kelas_id' => $this->kelasA->id,
            'nis' => 'A002',
            'nama' => 'Siswa A2',
            'jenis_kelamin' => 'P',
            'saldo' => 5000,
        ]);

        $this->siswaB1 = Siswa::create([
            'kelas_id' => $this->kelasB->id,
            'nis' => 'B001',
            'nama' => 'Siswa B1',
            'jenis_kelamin' => 'L',
            'saldo' => 20000,
        ]);
    }

    public function test_non_authorized_user_cannot_access_collective_form(): void
    {
        $response = $this->actingAs($this->orangTua)->get(route('transaksi.kolektif.form'));
        $response->assertForbidden();
    }

    public function test_guru_can_only_access_their_own_class_in_collective_form(): void
    {
        // Guru A accesses with no class selected (should auto-select class A because they only have one)
        $response = $this->actingAs($this->guruA)->get(route('transaksi.kolektif.form'));
        $response->assertOk();
        $response->assertViewHas('selectedKelas');
        $this->assertEquals($this->kelasA->id, $response->viewData('selectedKelas')->id);

        // Guru A tries to access Class B
        $response2 = $this->actingAs($this->guruA)->get(route('transaksi.kolektif.form', ['kelas_id' => $this->kelasB->id]));
        $response2->assertForbidden();
    }

    public function test_admin_can_access_any_class_in_collective_form(): void
    {
        $response = $this->actingAs($this->admin)->get(route('transaksi.kolektif.form', ['kelas_id' => $this->kelasB->id]));
        $response->assertOk();
        $response->assertViewHas('selectedKelas');
        $this->assertEquals($this->kelasB->id, $response->viewData('selectedKelas')->id);
    }

    public function test_can_store_collective_transactions_successfully(): void
    {
        $postData = [
            'kelas_id' => $this->kelasA->id,
            'tipe' => 'setor',
            'tanggal' => '2026-07-19',
            'keterangan_default' => 'Setoran Harian',
            'transaksi' => [
                [
                    'siswa_id' => $this->siswaA1->id,
                    'jumlah' => '5000',
                    'keterangan' => 'Tabungan Pagi',
                ],
                [
                    'siswa_id' => $this->siswaA2->id,
                    'jumlah' => '10000',
                    'keterangan' => null, // should fall back to keterangan_default
                ]
            ]
        ];

        $response = $this->actingAs($this->guruA)->post(route('transaksi.kolektif.store'), $postData);

        $response->assertRedirect(route('transaksi.index'));
        $response->assertSessionHas('success');

        // Check balances are updated
        $this->siswaA1->refresh();
        $this->siswaA2->refresh();
        $this->assertEquals(15000, $this->siswaA1->saldo);
        $this->assertEquals(15000, $this->siswaA2->saldo);

        // Check transactions are stored
        $this->assertDatabaseHas('transaksis', [
            'siswa_id' => $this->siswaA1->id,
            'tipe' => 'setor',
            'jumlah' => 5000,
            'keterangan' => 'Tabungan Pagi',
            'user_id' => $this->guruA->id,
        ]);

        $this->assertDatabaseHas('transaksis', [
            'siswa_id' => $this->siswaA2->id,
            'tipe' => 'setor',
            'jumlah' => 10000,
            'keterangan' => 'Setoran Harian',
            'user_id' => $this->guruA->id,
        ]);
    }

    public function test_skips_zero_and_empty_amounts_and_does_not_create_transactions(): void
    {
        $postData = [
            'kelas_id' => $this->kelasA->id,
            'tipe' => 'setor',
            'tanggal' => '2026-07-19',
            'keterangan_default' => 'Setoran Harian',
            'transaksi' => [
                [
                    'siswa_id' => $this->siswaA1->id,
                    'jumlah' => '0',
                ],
                [
                    'siswa_id' => $this->siswaA2->id,
                    'jumlah' => '',
                ]
            ]
        ];

        $response = $this->actingAs($this->guruA)->post(route('transaksi.kolektif.store'), $postData);

        // Should redirect back to form with info message because successCount is 0
        $response->assertRedirect(route('transaksi.kolektif.form', ['kelas_id' => $this->kelasA->id]));
        $response->assertSessionHas('info', 'Tidak ada transaksi yang disimpan (semua nominal kosong atau 0).');

        // Balances remain same
        $this->siswaA1->refresh();
        $this->siswaA2->refresh();
        $this->assertEquals(10000, $this->siswaA1->saldo);
        $this->assertEquals(5000, $this->siswaA2->saldo);

        // No transactions in DB
        $this->assertEquals(0, Transaksi::count());
    }

    public function test_fails_entire_batch_and_rolls_back_if_any_withdraw_amount_exceeds_saldo(): void
    {
        $postData = [
            'kelas_id' => $this->kelasA->id,
            'tipe' => 'tarik',
            'tanggal' => '2026-07-19',
            'keterangan_default' => 'Penarikan Harian',
            'transaksi' => [
                [
                    'siswa_id' => $this->siswaA1->id,
                    'jumlah' => '2000', // valid: balance 10000 -> 8000
                ],
                [
                    'siswa_id' => $this->siswaA2->id,
                    'jumlah' => '6000', // invalid: balance 5000, wants 6000
                ]
            ]
        ];

        $response = $this->actingAs($this->guruA)->post(route('transaksi.kolektif.store'), $postData);

        // Should return back with error errors for collective error
        $response->assertStatus(302);
        $response->assertSessionHasErrors('error_kolektif');

        // Both balances must remain untouched due to rollback
        $this->siswaA1->refresh();
        $this->siswaA2->refresh();
        $this->assertEquals(10000, $this->siswaA1->saldo);
        $this->assertEquals(5000, $this->siswaA2->saldo);

        // No transactions in DB
        $this->assertEquals(0, Transaksi::count());
    }
}
