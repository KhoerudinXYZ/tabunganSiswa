<?php

namespace Tests\Feature;

use App\Models\Kelas;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class GuruManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $guru;
    private User $orangTua;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->guru = User::factory()->create(['role' => 'guru', 'name' => 'Guru Asli']);
        $this->orangTua = User::factory()->create(['role' => 'orang_tua']);
    }

    public function test_only_admin_can_access_guru_management_index(): void
    {
        // Admin can access
        $response = $this->actingAs($this->admin)->get(route('guru.index'));
        $response->assertOk();
        $response->assertSee('Daftar Guru');
        $response->assertSee('Guru Asli');

        // Guru cannot access
        $this->actingAs($this->guru)->get(route('guru.index'))->assertForbidden();

        // Orang tua cannot access
        $this->actingAs($this->orangTua)->get(route('guru.index'))->assertForbidden();
    }

    public function test_admin_can_create_new_guru(): void
    {
        $postData = [
            'name' => 'Guru Baru, S.Pd.',
            'email' => 'gurubaru@tabungan.com',
            'password' => 'password123',
        ];

        $response = $this->actingAs($this->admin)->post(route('guru.store'), $postData);

        $response->assertRedirect(route('guru.index'));
        $this->assertDatabaseHas('users', [
            'name' => 'Guru Baru, S.Pd.',
            'email' => 'gurubaru@tabungan.com',
            'role' => 'guru',
        ]);

        $createdUser = User::where('email', 'gurubaru@tabungan.com')->first();
        $this->assertTrue(Hash::check('password123', $createdUser->password));
    }

    public function test_admin_can_update_guru_without_changing_password(): void
    {
        $guru = User::factory()->create([
            'role' => 'guru',
            'name' => 'Nama Lama',
            'email' => 'email_lama@example.com',
            'password' => Hash::make('password123_original'),
        ]);

        $updateData = [
            'name' => 'Nama Baru',
            'email' => 'email_baru@example.com',
            'password' => '', // blank password
        ];

        $response = $this->actingAs($this->admin)->put(route('guru.update', $guru->id), $updateData);

        $response->assertRedirect(route('guru.index'));
        
        $guru->refresh();
        $this->assertEquals('Nama Baru', $guru->name);
        $this->assertEquals('email_baru@example.com', $guru->email);
        $this->assertTrue(Hash::check('password123_original', $guru->password));
    }

    public function test_admin_can_update_guru_including_password(): void
    {
        $guru = User::factory()->create([
            'role' => 'guru',
            'name' => 'Nama Lama',
            'email' => 'email_lama@example.com',
            'password' => Hash::make('password123_original'),
        ]);

        $updateData = [
            'name' => 'Nama Baru',
            'email' => 'email_baru@example.com',
            'password' => 'password123_updated',
        ];

        $response = $this->actingAs($this->admin)->put(route('guru.update', $guru->id), $updateData);

        $response->assertRedirect(route('guru.index'));
        
        $guru->refresh();
        $this->assertTrue(Hash::check('password123_updated', $guru->password));
    }

    public function test_admin_cannot_delete_guru_who_is_active_homeroom_teacher(): void
    {
        $kelas = Kelas::create([
            'nama_kelas' => 'Kelas 2-A',
            'tahun_ajaran' => '2025/2026',
            'wali_kelas_id' => $this->guru->id,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('guru.destroy', $this->guru->id));

        $response->assertRedirect(route('guru.index'));
        $response->assertSessionHasErrors('delete_error');
        $this->assertDatabaseHas('users', ['id' => $this->guru->id]);
    }

    public function test_admin_can_delete_guru_who_has_no_classes(): void
    {
        $response = $this->actingAs($this->admin)->delete(route('guru.destroy', $this->guru->id));

        $response->assertRedirect(route('guru.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('users', ['id' => $this->guru->id]);
    }
}
