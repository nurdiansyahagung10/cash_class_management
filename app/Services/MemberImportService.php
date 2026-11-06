<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use ZipArchive;

class MemberImportService
{
    public function __construct(private MemberVerification $verification) {}

    /**
     * @return array{success: int, skipped: array<string>}
     */
    public function import(UploadedFile $file, User $creator): array
    {
        $rows = $this->parseFile($file);

        if (empty($rows)) {
            return [
                'success' => 0,
                'skipped' => ['File kosong atau format data tidak dapat dibaca.'],
            ];
        }

        $firstRow = $rows[0];
        $isHeader = false;
        foreach ($firstRow as $val) {
            if (preg_match('/(nama|name|nim|absen|student|nomor|pass|sandi)/i', (string) $val)) {
                $isHeader = true;
                break;
            }
        }

        if ($isHeader) {
            $header = array_shift($rows);
            $colMap = $this->resolveColumnMap($header);
        } else {
            $colMap = ['name' => 0, 'student_no' => 1, 'password' => 2];
        }

        $success = 0;
        $skipped = [];
        $seenInFile = [];

        foreach ($rows as $index => $row) {
            $rowNum = $isHeader ? ($index + 2) : ($index + 1);

            $name = trim((string) ($row[$colMap['name']] ?? ''));
            $studentNo = trim((string) ($row[$colMap['student_no']] ?? ''));
            $password = (string) ($row[$colMap['password']] ?? '');

            if ($name === '' && $studentNo === '' && $password === '') {
                continue;
            }

            if ($name === '') {
                $skipped[] = "Baris {$rowNum}: Nama tidak boleh kosong.";
                continue;
            }

            if ($studentNo === '') {
                $skipped[] = "Baris {$rowNum}: NIM / No. absen tidak boleh kosong.";
                continue;
            }

            if (strlen($password) < 8) {
                $skipped[] = "Baris {$rowNum} ({$name}): Password minimal 8 karakter.";
                continue;
            }

            if (isset($seenInFile[$studentNo])) {
                $skipped[] = "Baris {$rowNum} ({$name}): NIM '{$studentNo}' duplikat di dalam file.";
                continue;
            }
            $seenInFile[$studentNo] = true;

            try {
                $this->verification->add($creator, $name, $studentNo, $password);
                $success++;
            } catch (ValidationException $e) {
                $firstError = collect($e->validator->errors()->all())->first() ?? 'Data tidak valid.';
                $skipped[] = "Baris {$rowNum} ({$name}): {$firstError}";
            } catch (\Exception $e) {
                $skipped[] = "Baris {$rowNum} ({$name}): ".$e->getMessage();
            }
        }

        return [
            'success' => $success,
            'skipped' => $skipped,
        ];
    }

    /**
     * @return array<int, array<int, string>>
     */
    public function parseFile(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());

        if (in_array($extension, ['xlsx', 'xlsm'], true)) {
            return $this->parseXlsx($file->getRealPath());
        }

        return $this->parseCsv($file->getRealPath());
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function parseCsv(string $path): array
    {
        $rows = [];
        $handle = fopen($path, 'r');
        if (! $handle) {
            return [];
        }

        $firstLine = fgets($handle);
        if ($firstLine === false) {
            fclose($handle);

            return [];
        }

        $firstLine = preg_replace('/^\xEF\xBB\xBF/', '', $firstLine);
        $delimiter = (substr_count($firstLine, ';') > substr_count($firstLine, ',')) ? ';' : ',';

        $firstRow = str_getcsv($firstLine, $delimiter);
        if (! empty($firstRow)) {
            $rows[] = array_map('trim', $firstRow);
        }

        while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
            $rows[] = array_map('trim', $data);
        }

        fclose($handle);

        return $rows;
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function parseXlsx(string $path): array
    {
        if (! class_exists(ZipArchive::class)) {
            throw new \RuntimeException('Ekstensi PHP ZipArchive tidak tersedia di server.');
        }

        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            throw new \RuntimeException('Gagal membuka file Excel (.xlsx). Pastikan format file valid.');
        }

        $sharedStrings = [];
        $sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedStringsXml !== false) {
            $xml = @simplexml_load_string($sharedStringsXml);
            if ($xml) {
                foreach ($xml->si as $si) {
                    if (isset($si->t)) {
                        $sharedStrings[] = (string) $si->t;
                    } elseif (isset($si->r)) {
                        $text = '';
                        foreach ($si->r as $r) {
                            $text .= (string) $r->t;
                        }
                        $sharedStrings[] = $text;
                    } else {
                        $sharedStrings[] = '';
                    }
                }
            }
        }

        $sheetXmlContent = $zip->getFromName('xl/worksheets/sheet1.xml');
        if ($sheetXmlContent === false) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                if ($stat && preg_match('#^xl/worksheets/sheet\d+\.xml$#i', $stat['name'])) {
                    $sheetXmlContent = $zip->getFromIndex($i);
                    break;
                }
            }
        }

        $zip->close();

        if ($sheetXmlContent === false) {
            return [];
        }

        $xml = @simplexml_load_string($sheetXmlContent);
        if (! $xml || ! isset($xml->sheetData)) {
            return [];
        }

        $rows = [];
        foreach ($xml->sheetData->row as $row) {
            $rowCells = [];
            $maxCol = -1;

            foreach ($row->c as $c) {
                $colRef = (string) $c['r'];
                $colLetters = preg_replace('/\d+/', '', $colRef);
                $colIdx = $this->columnLetterToIndex($colLetters);

                $cellType = (string) $c['t'];
                $val = '';

                if ($cellType === 's') {
                    $sIndex = (int) $c->v;
                    $val = $sharedStrings[$sIndex] ?? '';
                } elseif ($cellType === 'inlineStr' && isset($c->is->t)) {
                    $val = (string) $c->is->t;
                } elseif (isset($c->v)) {
                    $val = (string) $c->v;
                }

                $rowCells[$colIdx] = trim($val);
                if ($colIdx > $maxCol) {
                    $maxCol = $colIdx;
                }
            }

            if ($maxCol >= 0) {
                $paddedRow = [];
                for ($i = 0; $i <= $maxCol; $i++) {
                    $paddedRow[$i] = $rowCells[$i] ?? '';
                }
                $rows[] = $paddedRow;
            }
        }

        return $rows;
    }

    private function columnLetterToIndex(string $letters): int
    {
        $letters = strtoupper(trim($letters));
        $len = strlen($letters);
        $idx = 0;
        for ($i = 0; $i < $len; $i++) {
            $idx = $idx * 26 + (ord($letters[$i]) - ord('A') + 1);
        }

        return max(0, $idx - 1);
    }

    /**
     * @param  array<int, string>  $header
     * @return array{name: int, student_no: int, password: int}
     */
    private function resolveColumnMap(array $header): array
    {
        $map = ['name' => 0, 'student_no' => 1, 'password' => 2];

        foreach ($header as $idx => $col) {
            $colName = strtolower(trim((string) $col));
            if (preg_match('/(nama|name)/i', $colName)) {
                $map['name'] = $idx;
            } elseif (preg_match('/(nim|absen|student|nomor)/i', $colName)) {
                $map['student_no'] = $idx;
            } elseif (preg_match('/(pass|sandi|pwd)/i', $colName)) {
                $map['password'] = $idx;
            }
        }

        return $map;
    }
}
