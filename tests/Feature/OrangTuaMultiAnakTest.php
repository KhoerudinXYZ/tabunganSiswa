<?php

namespace Tests\Feature;

use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class OrangTuaMultiAnakTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Kelas $kelasA;
    private Kelas $kelasB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);

        $guru = User::factory()->create(['role' => 'guru']);
        $this->kelasA = Kelas::create(['nama_kelas' => 'Kelas A', 'tahun_ajaran' => '2025/2026', 'wali_kelas_id' => $guru->id]);
        $this->kelasB = Kelas::create(['nama_kelas' => 'Kelas B', 'tahun_ajaran' => '2025/2026', 'wali_kelas_id' => $guru->id]);
    }

    public function test_parent_can_see_and_switch_between_multiple_children(): void
    {
        $parent = User::factory()->create(['role' => 'orang_tua']);
        $anak1 = Siswa::create(['user_id' => $parent->id, 'kelas_id' => $this->kelasA->id, 'nis' => 'A001', 'nama' => 'Kakak', 'jenis_kelamin' => 'L', 'saldo' => 10000]);
        $anak2 = Siswa::create(['user_id' => $parent->id, 'kelas_id' => $this->kelasB->id, 'nis' => 'A002', 'nama' => 'Ladik', 'jenis_kelamin' => 'P', 'saldo' => 5000]);

        $response = $this->actingAs($parent)->get(route('siswa.dashboard'));
        $response->assertOk();
        $response->assertSee('Kakak');
        $response->assertSee('Ladik');
        $response->assertSee('Selamat Datang, Wali dari Kakak'); // defaults to first child

        $response2 = $this->actingAs($parent)->get(route('siswa.dashboard', ['siswa_id' => $anak2->id]));
        $response2->assertOk();
        $response2->assertSee('Selamat Datang, Wali dari Ladik');
    }

    public function test_parent_cannot_view_another_familys_child_via_siswa_id(): void
    {
        $parentA = User::factory()->create(['role' => 'orang_tua']);
        Siswa::create(['user_id' => $parentA->id, 'kelas_id' => $this->kelasA->id, 'nis' => 'A001', 'nama' => 'Anak A', 'jenis_kelamin' => 'L', 'saldo' => 10000]);

        $parentB = User::factory()->create(['role' => 'orang_tua']);
        $anakB = Siswa::create(['user_id' => $parentB->id, 'kelas_id' => $this->kelasA->id, 'nis' => 'B001', 'nama' => 'Anak B', 'jenis_kelamin' => 'P', 'saldo' => 20000]);

        // Parent A tries to peek at Parent B's child by guessing the siswa_id.
        $response = $this->actingAs($parentA)->get(route('siswa.dashboard', ['siswa_id' => $anakB->id]));

        $response->assertOk();
        $response->assertSee('Anak A'); // silently falls back to their own child
        $response->assertDontSee('Anak B');
        $response->assertDontSee('20.000');
    }

    public function test_second_child_with_same_parent_email_links_to_existing_account(): void
    {
        $this->actingAs($this->admin)->post(route('siswa.store'), [
            'nis' => 'K001', 'nama' => 'Kakak', 'jenis_kelamin' => 'L', 'kelas_id' => $this->kelasA->id,
            'buat_akun' => '1', 'email_orang_tua' => 'keluarga@example.com',
        ])->assertRedirect(route('siswa.index'));

        $this->actingAs($this->admin)->post(route('siswa.store'), [
            'nis' => 'K002', 'nama' => 'Adik', 'jenis_kelamin' => 'P', 'kelas_id' => $this->kelasB->id,
            'buat_akun' => '1', 'email_orang_tua' => 'keluarga@example.com',
        ])->assertRedirect(route('siswa.index'));

        // Still only one orang_tua account — the second child was linked, not duplicated.
        $this->assertEquals(1, User::where('email', 'keluarga@example.com')->count());

        $parent = User::where('email', 'keluarga@example.com')->first();
        $this->assertEquals(2, $parent->siswas()->count());
    }

    public function test_cannot_link_student_to_email_belonging_to_non_parent_account(): void
    {
        $guru = User::factory()->create(['role' => 'guru', 'email' => 'guru.pinjam@example.com']);

        $response = $this->actingAs($this->admin)->post(route('siswa.store'), [
            'nis' => 'X001', 'nama' => 'Siswa X', 'jenis_kelamin' => 'L', 'kelas_id' => $this->kelasA->id,
            'buat_akun' => '1', 'email_orang_tua' => 'guru.pinjam@example.com',
        ]);

        $response->assertSessionHasErrors('email_orang_tua');
        $this->assertDatabaseMissing('siswas', ['nis' => 'X001']);
    }

    public function test_deleting_one_sibling_does_not_delete_shared_parent_account(): void
    {
        $parent = User::factory()->create(['role' => 'orang_tua']);
        $anak1 = Siswa::create(['user_id' => $parent->id, 'kelas_id' => $this->kelasA->id, 'nis' => 'S001', 'nama' => 'Kakak', 'jenis_kelamin' => 'L', 'saldo' => 0]);
        Siswa::create(['user_id' => $parent->id, 'kelas_id' => $this->kelasA->id, 'nis' => 'S002', 'nama' => 'Adik', 'jenis_kelamin' => 'P', 'saldo' => 0]);

        $this->actingAs($this->admin)->delete(route('siswa.destroy', $anak1))->assertRedirect();

        $this->assertDatabaseHas('users', ['id' => $parent->id]);
        $this->assertDatabaseMissing('siswas', ['id' => $anak1->id]);
    }
}
