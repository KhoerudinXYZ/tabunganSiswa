<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SiswaController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Siswa::class);

        $classes = $this->kelasScope();

        $query = Siswa::with('kelas');
        if (Auth::user()->isAdmin()) {
            // Admin can see all students, including those without a class
        } else {
            // Guru only sees students in their classes
            $query->whereIn('kelas_id', $classes->pluck('id'));
        }

        // Search filter
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%");
            });
        }

        // Class filter
        if ($request->filled('kelas_id')) {
            $query->where('kelas_id', $request->input('kelas_id'));
        }

        $siswas = $query->orderBy('nama', 'asc')->paginate(10)->withQueryString();

        return view('siswa.index', compact('siswas', 'classes'));
    }

    public function create()
    {
        $this->authorize('create', Siswa::class);

        $classes = $this->kelasScope();
        return view('siswa.create', compact('classes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama' => 'required|string|max:100',
            'jenis_kelamin' => 'nullable|in:L,P',
            'kelas_id' => 'nullable|exists:kelas,id',
            'alamat' => 'nullable|string',
        ]);

        if ($request->filled('kelas_id')) {
            $kelas = Kelas::findOrFail($request->input('kelas_id'));
            $this->authorize('create', [Siswa::class, $kelas]);
        } else {
            $this->authorize('create', Siswa::class);
        }

        Siswa::create([
            'nama' => $request->input('nama'),
            'jenis_kelamin' => $request->input('jenis_kelamin'),
            'kelas_id' => $request->input('kelas_id'),
            'alamat' => $request->input('alamat'),
            'saldo' => 0.00,
        ]);

        return redirect()->route('siswa.index')->with('success', 'Data siswa berhasil ditambahkan.');
    }

    public function edit(Siswa $siswa)
    {
        $this->authorize('update', $siswa);

        $classes = $this->kelasScope();
        return view('siswa.edit', compact('siswa', 'classes'));
    }

    public function update(Request $request, Siswa $siswa)
    {
        $this->authorize('update', $siswa);

        $request->validate([
            'nama' => 'required|string|max:100',
            'jenis_kelamin' => 'nullable|in:L,P',
            'kelas_id' => 'nullable|exists:kelas,id',
            'alamat' => 'nullable|string',
        ]);

        if ($request->filled('kelas_id')) {
            $targetKelas = Kelas::findOrFail($request->input('kelas_id'));
            $this->authorize('create', [Siswa::class, $targetKelas]);
        }

        $siswa->update([
            'nama' => $request->input('nama'),
            'jenis_kelamin' => $request->input('jenis_kelamin'),
            'kelas_id' => $request->input('kelas_id'),
            'alamat' => $request->input('alamat'),
        ]);

        return redirect()->route('siswa.index')->with('success', 'Data siswa berhasil diperbarui.');
    }

    public function destroy(Siswa $siswa)
    {
        $this->authorize('delete', $siswa);

        $siswa->delete();

        return redirect()->route('siswa.index')->with('success', 'Data siswa berhasil dihapus.');
    }

    /**
     * Show the student CSV import form.
     */
    public function importForm(Request $request)
    {
        $this->authorize('create', Siswa::class);

        $classes = $this->kelasScope();
        return view('siswa.import', compact('classes'));
    }

    /**
     * Download the Excel import template.
     */
    public function importTemplate()
    {
        $this->authorize('create', Siswa::class);

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template Import Siswa');

        // Header - only 'nama' is required, rest are optional
        $headers = ['nama', 'jenis_kelamin', 'alamat'];
        foreach ($headers as $colIdx => $header) {
            $sheet->setCellValue([$colIdx + 1, 1], $header);
        }

        // Example Rows (jenis_kelamin and alamat can be left empty)
        $examples = [
            ['Ahmad Subardi', 'L', 'Jl. Merdeka No. 10'],
            ['Siti Rahma', '', ''],
        ];

        foreach ($examples as $rowIdx => $row) {
            foreach ($row as $colIdx => $val) {
                $sheet->setCellValue([$colIdx + 1, $rowIdx + 2], $val);
            }
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

        return response()->stream(
            function () use ($writer) {
                $writer->save('php://output');
            },
            200,
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="template_siswa.xlsx"',
                'Cache-Control' => 'max-age=0',
            ]
        );
    }

    /**
     * Store the imported students from Excel.
     */
    public function importStore(Request $request)
    {
        $this->authorize('create', Siswa::class);

        $request->validate([
            'kelas_id' => 'nullable|exists:kelas,id',
            'file_excel' => 'required|file|mimes:xlsx,xls|max:2048',
        ]);

        $kelas = null;
        if ($request->filled('kelas_id')) {
            $kelas = Kelas::findOrFail($request->input('kelas_id'));
            $this->authorize('create', [Siswa::class, $kelas]);
        } else {
            $this->authorize('create', Siswa::class);
        }

        $file = $request->file('file_excel');
        $path = $file->getRealPath();

        try {
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($path);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($path);
            $sheet = $spreadsheet->getActiveSheet();
            $maxRow = $sheet->getHighestRow();
            $maxCol = $sheet->getHighestColumn();
            
            // Map column index to letters
            $maxColIdx = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($maxCol);
            
            if ($maxRow < 1) {
                return back()->withErrors(['file_excel' => 'File Excel kosong atau tidak ada data yang valid.']);
            }

            // Parse header
            $header = [];
            for ($col = 1; $col <= $maxColIdx; $col++) {
                $val = trim((string)$sheet->getCell([$col, 1])->getValue());
                if ($val !== '') {
                    $header[$col] = $val;
                }
            }

            // Only 'nama' column is required; other columns are optional
            $required = ['nama'];
            $headerValues = array_values($header);

            if (empty($header) || array_diff($required, $headerValues)) {
                return back()->withErrors(['file_excel' => 'Format header Excel tidak valid. Kolom wajib: nama. Kolom opsional: jenis_kelamin, alamat']);
            }

            $rows = [];
            for ($row = 2; $row <= $maxRow; $row++) {
                $rowData = [];
                $isEmpty = true;
                
                foreach ($header as $colIdx => $colName) {
                    $cellVal = trim((string)$sheet->getCell([$colIdx, $row])->getValue());
                    $rowData[$colName] = $cellVal;
                    if ($cellVal !== '') {
                        $isEmpty = false;
                    }
                }

                if ($isEmpty) {
                    continue;
                }

                if (empty($rowData['nama'])) {
                    continue;
                }

                $rows[] = $rowData;
            }

        } catch (\Exception $e) {
            return back()->withErrors(['file_excel' => 'Gagal membaca file Excel: ' . $e->getMessage()]);
        }

        if (empty($rows)) {
            return back()->withErrors(['file_excel' => 'File Excel kosong atau tidak ada data yang valid.']);
        }

        $rowNum = 1;

        try {
            DB::transaction(function () use ($rows, $kelas, &$rowNum) {
                foreach ($rows as $row) {
                    $rowNum++;

                    if (empty($row['nama'])) {
                        throw new \Exception("Baris {$rowNum}: Nama tidak boleh kosong.");
                    }
                    if (strlen($row['nama']) > 100) {
                        throw new \Exception("Baris {$rowNum}: Nama tidak boleh lebih dari 100 karakter.");
                    }
                    if (!empty($row['jenis_kelamin']) && !in_array($row['jenis_kelamin'], ['L', 'P'])) {
                        throw new \Exception("Baris {$rowNum}: Jenis Kelamin harus 'L' atau 'P'.");
                    }

                    Siswa::create([
                        'nama' => $row['nama'],
                        'jenis_kelamin' => ($row['jenis_kelamin'] ?? '') ?: null,
                        'kelas_id' => $kelas ? $kelas->id : null,
                        'alamat' => ($row['alamat'] ?? '') ?: null,
                        'saldo' => 0.00,
                    ]);

                }
            });
        } catch (\Exception $e) {
            return back()->withErrors(['file_excel' => $e->getMessage()])->withInput();
        }

        $count = count($rows);
        $kelasName = $kelas ? $kelas->nama_kelas : 'Tanpa Kelas';
        return redirect()->route('siswa.index')->with('success', "Berhasil mengimpor {$count} data siswa ke {$kelasName}.");
    }

    /**
     * Classes visible to the current user: all classes for admin, only the
     * classes they are homeroom teacher of for guru.
     */
    private function kelasScope()
    {
        $user = Auth::user();

        if ($user->isAdmin()) {
            return Kelas::orderBy('nama_kelas')->get();
        }

        return Kelas::where('wali_kelas_id', $user->id)->orderBy('nama_kelas')->get();
    }


}
