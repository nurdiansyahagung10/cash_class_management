<?php

use App\Models\PendingMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

uses(RefreshDatabase::class);

function createTestXlsx(array $rows, string $filePath): void
{
    $zip = new ZipArchive;
    $zip->open($filePath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

    $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
        .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
        .'<Default Extension="xml" ContentType="application/xml"/>'
        .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
        .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
        .'<Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/>'
        .'</Types>');

    $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
        .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
        .'</Relationships>');

    $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
        .'<sheets><sheet name="Sheet1" sheetId="1" r:id="rId1" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"/></sheets>'
        .'</workbook>');

    $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
        .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
        .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/>'
        .'</Relationships>');

    $sharedStrings = [];
    $sheetRows = '';
    $rowNum = 1;

    foreach ($rows as $row) {
        $sheetRows .= "<row r=\"{$rowNum}\">";
        $colLetters = ['A', 'B', 'C', 'D', 'E'];
        foreach ($row as $colIdx => $val) {
            $colLetter = $colLetters[$colIdx] ?? 'A';
            $sIdx = count($sharedStrings);
            $sharedStrings[] = (string) $val;
            $sheetRows .= "<c r=\"{$colLetter}{$rowNum}\" t=\"s\"><v>{$sIdx}</v></c>";
        }
        $sheetRows .= '</row>';
        $rowNum++;
    }

    $sstXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="'.count($sharedStrings).'">';
    foreach ($sharedStrings as $str) {
        $sstXml .= '<si><t>'.htmlspecialchars($str, ENT_XML1).'</t></si>';
    }
    $sstXml .= '</sst>';
    $zip->addFromString('xl/sharedStrings.xml', $sstXml);

    $sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>'.$sheetRows.'</sheetData></worksheet>';
    $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);

    $zip->close();
}

test('admin can download member import template', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get(route('members.template'));

    $response->assertOk()
        ->assertHeader('Content-Disposition', 'attachment; filename=template-import-anggota.csv')
        ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

    expect($response->streamedContent())->toContain('Nama,NIM,"Password Awal"');
});

test('only admin can download the template or import members; others go back to the dashboard', function () {
    foreach ([User::factory()->create(), User::factory()->staff()->create()] as $actor) {
        $this->actingAs($actor)->get(route('members.template'))->assertRedirect(route('dashboard'));
        $this->actingAs($actor)->post(route('members.import'))->assertRedirect(route('dashboard'));
    }

    expect(PendingMember::count())->toBe(0);
});

test('admin can import members from csv file', function () {
    $admin = User::factory()->admin()->create();

    $csvContent = "\xEF\xBB\xBFNama,NIM,Password Awal\n"
        ."Ahmad Fauzi,101,password123\n"
        ."Budi Utomo,102,password123\n";

    $file = UploadedFile::fake()->createWithContent('anggota.csv', $csvContent);

    $response = $this->actingAs($admin)->post(route('members.import'), [
        'file' => $file,
    ]);

    $response->assertRedirect(route('members.index'))
        ->assertSessionHas('status', '2 calon anggota berhasil diimpor ke antrean verifikasi.');

    expect(PendingMember::count())->toBe(2);

    $candidate1 = PendingMember::where('student_no', '101')->first();
    expect($candidate1)->not->toBeNull()
        ->and($candidate1->name)->toBe('Ahmad Fauzi')
        ->and($candidate1->created_by)->toBe($admin->id);
});

test('admin can import members from excel xlsx file', function () {
    $admin = User::factory()->admin()->create();

    $tempPath = tempnam(sys_get_temp_dir(), 'test_xlsx_').'.xlsx';

    createTestXlsx([
        ['Nama Lengkap', 'Nomor Induk / NIM', 'Password Awal'],
        ['Citra Dewi', '201', 'password123'],
        ['Doni Pratama', '202', 'password456'],
    ], $tempPath);

    $file = new UploadedFile($tempPath, 'anggota.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

    $response = $this->actingAs($admin)->post(route('members.import'), [
        'file' => $file,
    ]);

    $response->assertRedirect(route('members.index'))
        ->assertSessionHas('status', '2 calon anggota berhasil diimpor ke antrean verifikasi.');

    expect(PendingMember::count())->toBe(2);

    $citra = PendingMember::where('student_no', '201')->first();
    expect($citra)->not->toBeNull()
        ->and($citra->name)->toBe('Citra Dewi');

    if (file_exists($tempPath)) {
        unlink($tempPath);
    }
});

test('import skips rows with existing nim, short password, or duplicate within file', function () {
    $admin = User::factory()->admin()->create();

    // Existing active member
    User::factory()->create(['student_no' => '301', 'name' => 'Existing User']);
    // Existing pending candidate
    PendingMember::factory()->create(['student_no' => '302', 'name' => 'Pending User']);

    $csvContent = "Nama,NIM,Password Awal\n"
        ."Valid Satu,303,password123\n"
        ."Duplikat User,301,password123\n" // Already in User
        ."Duplikat Pending,302,password123\n" // Already in PendingMember
        ."Password Pendek,304,short\n" // Password < 8
        ."Duplikat File A,305,password123\n" // First in file -> succeeds
        ."Duplikat File B,305,password123\n" // Second in file -> duplicate skipped
        .",306,password123\n"; // Missing name

    $file = UploadedFile::fake()->createWithContent('daftar.csv', $csvContent);

    $response = $this->actingAs($admin)->post(route('members.import'), [
        'file' => $file,
    ]);

    $response->assertRedirect(route('members.index'))
        ->assertSessionHas('status')
        ->assertSessionHas('import_errors');

    $errors = session('import_errors');
    expect(count($errors))->toBe(5)
        ->and(PendingMember::where('student_no', '303')->exists())->toBeTrue()
        ->and(PendingMember::where('student_no', '305')->exists())->toBeTrue()
        ->and(PendingMember::where('student_no', '304')->exists())->toBeFalse();
});
