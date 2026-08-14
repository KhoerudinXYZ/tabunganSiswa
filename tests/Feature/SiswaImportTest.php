<?php

namespace Tests\Feature;

use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class SiswaImportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $guru;
    private User $orangTua;
    private Kelas $kelas;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->guru = User::factory()->create(['role' => 'guru', 'name' => 'Guru Wali']);
        $this->orangTua = User::factory()->create(['role' => 'orang_tua']);

        $this->kelas = Kelas::create([
            'nama_kelas' => 'Kelas 1-A',
            'tahun_ajaran' => '2025/2026',
            'wali_kelas_id' => $this->guru->id,
        ]);
    }

    /**
     * Helper to create an Excel (.xlsx) UploadedFile from header and rows.
     */
    private function makeExcel(array $header, array $rows): UploadedFile
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        foreach ($header as $colIdx => $colName) {
            $sheet->setCellValue([$colIdx + 1, 1], $colName);
        }

        foreach ($rows as $rowIdx => $row) {
            foreach ($row as $colIdx => $val) {
                if ($colIdx === 0) {
                    $sheet->setCellValueExplicit([$colIdx + 1, $rowIdx + 2], $val, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                } else {
                    $sheet->setCellValue([$colIdx + 1, $rowIdx + 2], $val);
                }
            }
        }

        $tmpPath = tempnam(sys_get_temp_dir(), 'excel_') . '.xlsx';
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($tmpPath);

        return new UploadedFile($tmpPath, 'siswa.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }

    // ────────────────────────────────────────────────────
    //  Access Control
    // ────────────────────────────────────────────────────

    public function test_admin_can_access_import_form(): void
    {
        $response = $this->actingAs($this->admin)->get(route('siswa.import.form'));
        $response->assertOk();
        $response->assertSee('Import');
    }

    public function test_guru_can_access_import_form_for_own_class(): void
    {
        $response = $this->actingAs($this->guru)->get(route('siswa.import.form'));
        $response->assertOk();
    }

    public function test_orang_tua_cannot_access_import_form(): void
    {
        $response = $this->actingAs($this->orangTua)->get(route('siswa.import.form'));
        $response->assertForbidden();
    }

    // ────────────────────────────────────────────────────
    //  Template Download
    // ────────────────────────────────────────────────────

    public function test_admin_can_download_excel_template(): void
    {
        $response = $this->actingAs($this->admin)->get(route('siswa.import.template'));
        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->assertHeader('Content-Disposition', 'attachment; filename="template_siswa.xlsx"');
    }

    // ────────────────────────────────────────────────────
    //  Successful Import
    // ────────────────────────────────────────────────────

    public function test_import_excel_with_valid_data_creates_students(): void
    {
        $header = ['nis', 'nama', 'jenis_kelamin', 'alamat', 'no_hp_orang_tua', 'email_orang_tua'];
        $rows = [
            ['20260001', 'Ahmad Subardi', 'L', 'Jl. Merdeka 10', '08123456789', ''],
            ['20260002', 'Siti Rahma', 'P', 'Jl. Mawar 4', '08578901234', ''],
        ];
        $file = $this->makeExcel($header, $rows);

        $response = $this->actingAs($this->admin)->post(route('siswa.import.store'), [
            'kelas_id' => $this->kelas->id,
            'file_excel' => $file,
        ]);

        $response->assertRedirect(route('siswa.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('siswas', ['nis' => '20260001', 'nama' => 'Ahmad Subardi', 'kelas_id' => $this->kelas->id]);
        $this->assertDatabaseHas('siswas', ['nis' => '20260002', 'nama' => 'Siti Rahma', 'kelas_id' => $this->kelas->id]);
        $this->assertEquals(2, Siswa::count());
    }

    // ────────────────────────────────────────────────────
    //  Auto-Create Parent Account
    // ────────────────────────────────────────────────────

    public function test_import_excel_auto_creates_parent_account_when_email_provided(): void
    {
        $header = ['nis', 'nama', 'jenis_kelamin', 'alamat', 'no_hp_orang_tua', 'email_orang_tua'];
        $rows = [
            ['20260010', 'Budi Santoso', 'L', 'Jl. Kenangan 5', '08111222333', 'wali.budi@example.com'],
        ];
        $file = $this->makeExcel($header, $rows);

        $response = $this->actingAs($this->admin)->post(route('siswa.import.store'), [
            'kelas_id' => $this->kelas->id,
            'file_excel' => $file,
        ]);

        $response->assertRedirect(route('siswa.index'));

        // Parent account should have been created
        $this->assertDatabaseHas('users', [
            'email' => 'wali.budi@example.com',
            'role' => 'orang_tua',
        ]);

        // Student should be linked to the parent account
        $siswa = Siswa::where('nis', '20260010')->first();
        $this->assertNotNull($siswa->user_id);

        $parentUser = User::find($siswa->user_id);
        $this->assertEquals('wali.budi@example.com', $parentUser->email);
    }

    public function test_import_excel_reuses_existing_parent_account_for_siblings(): void
    {
        // Pre-create a parent account
        $existingParent = User::factory()->create([
            'role' => 'orang_tua',
            'email' => 'keluarga@example.com',
            'name' => 'Wali Murid Kakak',
        ]);

        $header = ['nis', 'nama', 'jenis_kelamin', 'alamat', 'no_hp_orang_tua', 'email_orang_tua'];
        $rows = [
            ['20260020', 'Adik Pertama', 'P', 'Jl. Keluarga 1', '08999', 'keluarga@example.com'],
        ];
        $file = $this->makeExcel($header, $rows);

        $response = $this->actingAs($this->admin)->post(route('siswa.import.store'), [
            'kelas_id' => $this->kelas->id,
            'file_excel' => $file,
        ]);

        $response->assertRedirect(route('siswa.index'));

        $siswa = Siswa::where('nis', '20260020')->first();
        $this->assertEquals($existingParent->id, $siswa->user_id);

        // Should NOT create a new user
        $this->assertEquals(1, User::where('email', 'keluarga@example.com')->count());
    }

    // ────────────────────────────────────────────────────
    //  Validation & Rollback
    // ────────────────────────────────────────────────────

    public function test_import_excel_rollback_on_duplicate_nis_in_database(): void
    {
        // Pre-create a student with NIS 20260001
        Siswa::create([
            'nis' => '20260001',
            'nama' => 'Existing Student',
            'jenis_kelamin' => 'L',
            'kelas_id' => $this->kelas->id,
            'saldo' => 0,
        ]);

        $header = ['nis', 'nama', 'jenis_kelamin', 'alamat', 'no_hp_orang_tua', 'email_orang_tua'];
        $rows = [
            ['20260099', 'Student A (valid)', 'L', 'Alamat A', '', ''],
            ['20260001', 'Student B (duplicate)', 'P', 'Alamat B', '', ''], // duplicate NIS
        ];
        $file = $this->makeExcel($header, $rows);

        $response = $this->actingAs($this->admin)->post(route('siswa.import.store'), [
            'kelas_id' => $this->kelas->id,
            'file_excel' => $file,
        ]);

        $response->assertRedirect(); // back to form
        $response->assertSessionHasErrors('file_excel');

        // Student A should NOT have been created (entire transaction rolled back)
        $this->assertDatabaseMissing('siswas', ['nis' => '20260099']);

        // Only the original student should remain
        $this->assertEquals(1, Siswa::count());
    }

    public function test_import_excel_rollback_on_duplicate_nis_within_file(): void
    {
        $header = ['nis', 'nama', 'jenis_kelamin', 'alamat', 'no_hp_orang_tua', 'email_orang_tua'];
        $rows = [
            ['20260050', 'Student A', 'L', '', '', ''],
            ['20260050', 'Student B (same NIS)', 'P', '', '', ''], // duplicate within file
        ];
        $file = $this->makeExcel($header, $rows);

        $response = $this->actingAs($this->admin)->post(route('siswa.import.store'), [
            'kelas_id' => $this->kelas->id,
            'file_excel' => $file,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('file_excel');

        // No students should have been created
        $this->assertEquals(0, Siswa::count());
    }

    public function test_import_excel_rejects_invalid_gender(): void
    {
        $header = ['nis', 'nama', 'jenis_kelamin', 'alamat', 'no_hp_orang_tua', 'email_orang_tua'];
        $rows = [
            ['20260060', 'Student Salah', 'X', '', '', ''], // invalid gender
        ];
        $file = $this->makeExcel($header, $rows);

        $response = $this->actingAs($this->admin)->post(route('siswa.import.store'), [
            'kelas_id' => $this->kelas->id,
            'file_excel' => $file,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('file_excel');
        $this->assertEquals(0, Siswa::count());
    }

    public function test_import_excel_rejects_non_orang_tua_email(): void
    {
        $header = ['nis', 'nama', 'jenis_kelamin', 'alamat', 'no_hp_orang_tua', 'email_orang_tua'];
        $rows = [
            // Use the guru's email — this should be rejected
            ['20260070', 'Student Conflict', 'L', '', '', $this->guru->email],
        ];
        $file = $this->makeExcel($header, $rows);

        $response = $this->actingAs($this->admin)->post(route('siswa.import.store'), [
            'kelas_id' => $this->kelas->id,
            'file_excel' => $file,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('file_excel');
        $this->assertEquals(0, Siswa::count());
    }

    public function test_import_excel_rejects_invalid_header_format(): void
    {
        $header = ['nomor_induk', 'nama_lengkap', 'gender']; // wrong headers
        $rows = [
            ['20260080', 'Student X', 'L'],
        ];
        $file = $this->makeExcel($header, $rows);

        $response = $this->actingAs($this->admin)->post(route('siswa.import.store'), [
            'kelas_id' => $this->kelas->id,
            'file_excel' => $file,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('file_excel');
    }

    public function test_import_excel_rejects_empty_file(): void
    {
        $header = ['nis', 'nama', 'jenis_kelamin', 'alamat', 'no_hp_orang_tua', 'email_orang_tua'];
        $file = $this->makeExcel($header, []); // header only, no data rows

        $response = $this->actingAs($this->admin)->post(route('siswa.import.store'), [
            'kelas_id' => $this->kelas->id,
            'file_excel' => $file,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('file_excel');
    }
}
