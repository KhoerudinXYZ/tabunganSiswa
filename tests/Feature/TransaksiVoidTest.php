<?php

namespace Tests\Feature;

use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransaksiVoidTest extends TestCase
{
    use RefreshDatabase;

    private User $guruA;
    private User $guruB;
    private Siswa $siswaA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->guruA = User::factory()->create(['role' => 'guru']);
        $this->guruB = User::factory()->create(['role' => 'guru']);

        $kelasA = Kelas::create([
            'nama_kelas' => 'Kelas A',
            'tahun_ajaran' => '2025/2026',
            'wali_kelas_id' => $this->guruA->id,
        ]);

        Kelas::create([
            'nama_kelas' => 'Kelas B',
            'tahun_ajaran' => '2025/2026',
            'wali_kelas_id' => $this->guruB->id,
        ]);

        $this->siswaA = Siswa::create([
            'kelas_id' => $kelasA->id,
            'nis' => 'A001',
            'nama' => 'Siswa Kelas A',
            'jenis_kelamin' => 'L',
            'saldo' => 50000,
        ]);
    }

    public function test_voiding_a_deposit_creates_reversal_and_restores_balance(): void
    {
        $transaksi = Transaksi::create([
            'siswa_id' => $this->siswaA->id,
            'user_id' => $this->guruA->id,
            'tipe' => 'setor',
            'jumlah' => 20000,
            'tanggal' => now()->format('Y-m-d'),
        ]);
        $this->siswaA->increment('saldo', 20000); // simulate the store() side-effect: saldo now 70000

        $response = $this->actingAs($this->guruA)->post(route('transaksi.void', $transaksi), [
            'alasan' => 'Salah input nominal',
        ]);

        $response->assertRedirect();
        $this->siswaA->refresh();
        $this->assertEquals(50000, $this->siswaA->saldo);

        $this->assertDatabaseHas('transaksis', [
            'reversal_of_id' => $transaksi->id,
            'is_reversal' => true,
            'tipe' => 'tarik',
            'jumlah' => 20000,
        ]);
    }

    public function test_cannot_void_the_same_transaction_twice(): void
    {
        $transaksi = Transaksi::create([
            'siswa_id' => $this->siswaA->id,
            'user_id' => $this->guruA->id,
            'tipe' => 'setor',
            'jumlah' => 20000,
            'tanggal' => now()->format('Y-m-d'),
        ]);
        $this->siswaA->increment('saldo', 20000);

        $this->actingAs($this->guruA)->post(route('transaksi.void', $transaksi), ['alasan' => 'Koreksi pertama'])
            ->assertRedirect();

        $this->actingAs($this->guruA)->post(route('transaksi.void', $transaksi), ['alasan' => 'Koreksi kedua'])
            ->assertForbidden();
    }

    public function test_cannot_void_a_reversal_entry(): void
    {
        $transaksi = Transaksi::create([
            'siswa_id' => $this->siswaA->id,
            'user_id' => $this->guruA->id,
            'tipe' => 'setor',
            'jumlah' => 20000,
            'tanggal' => now()->format('Y-m-d'),
        ]);
        $this->siswaA->increment('saldo', 20000);

        $this->actingAs($this->guruA)->post(route('transaksi.void', $transaksi), ['alasan' => 'Koreksi'])
            ->assertRedirect();

        $reversal = Transaksi::where('reversal_of_id', $transaksi->id)->firstOrFail();

        $this->actingAs($this->guruA)->post(route('transaksi.void', $reversal), ['alasan' => 'Coba batalkan koreksi'])
            ->assertForbidden();
    }

    public function test_guru_cannot_void_transaction_of_another_class(): void
    {
        $transaksi = Transaksi::create([
            'siswa_id' => $this->siswaA->id,
            'user_id' => $this->guruA->id,
            'tipe' => 'setor',
            'jumlah' => 20000,
            'tanggal' => now()->format('Y-m-d'),
        ]);

        $this->actingAs($this->guruB)->post(route('transaksi.void', $transaksi), ['alasan' => 'Coba batalkan punya kelas lain'])
            ->assertForbidden();
    }

    public function test_voiding_a_deposit_is_blocked_if_balance_already_spent(): void
    {
        // Siswa deposits 20000 (balance 50000 -> 70000), then withdraws 60000 (balance -> 10000).
        $setor = Transaksi::create([
            'siswa_id' => $this->siswaA->id,
            'user_id' => $this->guruA->id,
            'tipe' => 'setor',
            'jumlah' => 20000,
            'tanggal' => now()->format('Y-m-d'),
        ]);
        $this->siswaA->increment('saldo', 20000);

        Transaksi::create([
            'siswa_id' => $this->siswaA->id,
            'user_id' => $this->guruA->id,
            'tipe' => 'tarik',
            'jumlah' => 60000,
            'tanggal' => now()->format('Y-m-d'),
        ]);
        $this->siswaA->decrement('saldo', 60000); // saldo now 10000

        // Voiding the 20000 deposit would require pulling 20000 back out, but only 10000 remains.
        $response = $this->actingAs($this->guruA)->post(route('transaksi.void', $setor), [
            'alasan' => 'Salah input nominal',
        ]);

        $response->assertSessionHasErrors('alasan');
        $this->siswaA->refresh();
        $this->assertEquals(10000, $this->siswaA->saldo);
        $this->assertDatabaseMissing('transaksis', ['reversal_of_id' => $setor->id]);
    }

    public function test_withdrawal_exceeding_balance_is_rejected(): void
    {
        $response = $this->actingAs($this->guruA)->post(route('transaksi.store'), [
            'siswa_id' => $this->siswaA->id,
            'tipe' => 'tarik',
            'jumlah' => 999999,
            'tanggal' => now()->format('Y-m-d'),
        ]);

        $response->assertSessionHasErrors('jumlah');
        $this->siswaA->refresh();
        $this->assertEquals(50000, $this->siswaA->saldo);
    }
}
