<?php

namespace App\Services;

use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use QrisDinamis\Qris;
use Zxing\QrReader;

class QrisService
{
    /**
     * QRIS for a fixed amount from a period's stored QRIS (uploaded image path/URL or raw payload).
     * Returns a PNG data URI (uploaded image, converted to dynamic), a dynamic payload, or the static source as-is.
     */
    public function forAmount(?string $source, int $amount): ?string
    {
        if ($source === null || $source === '') {
            return null;
        }

        // Uploaded images are stored as an absolute URL (…/storage/qris/x.png); read the file from /public, not the CWD.
        $relativePath = implode('/', array_slice(explode('/', $source), 3));
        $localPath = collect([public_path($relativePath), $relativePath])->first(fn (string $path) => $relativePath !== '' && is_file($path));
        if ($localPath !== null) {
            return $this->pngDataUri(Qris::convert((new QrReader($localPath))->text(), $amount));
        }

        return $this->isQrisPayload($source) ? $this->toDynamic($source, $amount) : $source;
    }

    /**
     * Image to show and download for a fixed amount; `dynamic` means the amount is locked inside the code.
     *
     * @return array{src: string, dynamic: bool}|null
     */
    public function imageFor(?string $source, int $amount): ?array
    {
        $content = $this->forAmount($source, $amount);

        return match (true) {
            $content === null => null,
            $this->isQrisPayload($content) => ['src' => $this->pngDataUri($content), 'dynamic' => true],
            str_starts_with($content, 'data:image/') => ['src' => $content, 'dynamic' => true],
            default => ['src' => $content, 'dynamic' => false],
        };
    }

    /**
     * Renders a QR payload as a PNG data URI (served inline, so it can also be downloaded directly).
     */
    public function pngDataUri(string $payload): string
    {
        $qrCode = new QrCode(
            data: $payload,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::Low,
            size: 300,
            margin: 10,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
        );

        return (new PngWriter)->write($qrCode)->getDataUri();
    }

    /**
     * Check if a string is a raw QRIS EMVCo payload (starts with 000201).
     */
    public function isQrisPayload(?string $payload): bool
    {
        if ($payload === null) {
            return false;
        }

        $trimmed = trim($payload);

        return str_starts_with($trimmed, '000201');
    }

    /**
     * Convert a static QRIS payload into a dynamic QRIS payload with fixed nominal amount.
     * Following EMVCo / ASPI QRIS standard (as in verssache/QRIS-Dinamis).
     */
    public function toDynamic(string $staticQris, int $amount): string
    {
        $payload = trim($staticQris);

        // Strip existing 4-character CRC checksum at the end (after 6304)
        if (preg_match('/(.*6304)[0-9A-Fa-f]{4}$/', $payload, $matches)) {
            $payload = $matches[1];
        } elseif (preg_match('/(.*)6304$/', $payload, $matches)) {
            $payload = $matches[0];
        } else {
            $payload = rtrim($payload, " \t\n\r\0\x0B");
            if (! str_ends_with($payload, '6304')) {
                $payload .= '6304';
            }
        }

        // Remove 6304 from payload temporarily while injecting tags
        $body = substr($payload, 0, -4);

        // Change Point of Initiation Method from Static (010211) to Dynamic (010212)
        $body = str_replace('010211', '010212', $body);

        // Format Tag 54 (Transaction Amount): 54 + 2-digit length + amount string
        $amountStr = (string) $amount;
        $amountLen = sprintf('%02d', strlen($amountStr));
        $tag54 = '54'.$amountLen.$amountStr;

        // If tag 54 is already present, replace it
        if (preg_match('/54(\d{2})(\d+)/', $body, $existing)) {
            $body = preg_replace('/54\d{2}\d+/', $tag54, $body, 1);
        } else {
            // Tag 58 (Country Code, usually 5802ID) is standard anchor after Currency (5303360)
            if (str_contains($body, '5802')) {
                $parts = explode('5802', $body, 2);
                $body = $parts[0].$tag54.'5802'.$parts[1];
            } else {
                // Fallback: append before CRC
                $body .= $tag54;
            }
        }

        // Re-append 6304
        $stringToSign = $body.'6304';

        // Compute and append CRC16-CCITT
        $crc = $this->calculateCrc16($stringToSign);

        return $stringToSign.$crc;
    }

    /**
     * Compute CRC16-CCITT checksum (polynomial 0x1021, initial value 0xFFFF).
     */
    public function calculateCrc16(string $data): string
    {
        $crc = 0xFFFF;
        $length = strlen($data);

        for ($i = 0; $i < $length; $i++) {
            $crc ^= (ord($data[$i]) << 8);

            for ($j = 0; $j < 8; $j++) {
                if (($crc & 0x8000) !== 0) {
                    $crc = (($crc << 1) ^ 0x1021) & 0xFFFF;
                } else {
                    $crc = ($crc << 1) & 0xFFFF;
                }
            }
        }

        return strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
    }
}
