<?php

use App\Services\QrisService;

test('calculates correct CRC16 CCITT according to EMVCo QRIS standard', function () {
    $service = new QrisService;

    // Standard CCITT 0xFFFF, 0x1021 test vector
    expect($service->calculateCrc16('123456789'))->toBe('29B1');
});

test('converts static QRIS string into dynamic QRIS with injected amount', function () {
    $service = new QrisService;

    // Simulated static QRIS without tag 54, ending with 6304XXXX
    $staticQris = '00020101021126580016ID.CO.DANA.WWW011893600010000000000002150000000000000000303UMI51440014ID.CO.QRIS.WWW0215ID10200000000000303UMI5204549953033605802ID5911KAS KELAS 1A6007JAKARTA6105123406304ABCD';

    $dynamicQris = $service->toDynamic($staticQris, 10000);

    // Initiation point should become 010212 (dynamic)
    expect($dynamicQris)->toContain('010212')
        ->and($dynamicQris)->not->toContain('010211');

    // Amount tag 540510000 should be present
    expect($dynamicQris)->toContain('540510000');

    // Should end with 6304 followed by valid 4-character hex CRC
    expect($dynamicQris)->toMatch('/6304[0-9A-F]{4}$/');

    // Verify CRC of the generated dynamic QRIS is self-consistent
    $rawWithoutCrc = substr($dynamicQris, 0, -4);
    $expectedCrc = substr($dynamicQris, -4);
    expect($service->calculateCrc16($rawWithoutCrc))->toBe($expectedCrc);
});

test('replaces existing amount if static QRIS already had a nominal tag', function () {
    $service = new QrisService;

    $staticWithOldAmount = '00020101021126580016ID.CO.DANA.WWW5303360540450005802ID5904DANA6007JAKARTA63041234';

    $updated = $service->toDynamic($staticWithOldAmount, 25000);

    expect($updated)->toContain('540525000')
        ->and($updated)->not->toContain('54045000')
        ->and($updated)->toContain('010212');

    $rawWithoutCrc = substr($updated, 0, -4);
    $expectedCrc = substr($updated, -4);
    expect($service->calculateCrc16($rawWithoutCrc))->toBe($expectedCrc);
});

test('detects whether a string is a valid QRIS payload', function () {
    $service = new QrisService;

    expect($service->isQrisPayload('0002010102112658...'))->toBeTrue()
        ->and($service->isQrisPayload('https://example.com/qris.png'))->toBeFalse()
        ->and($service->isQrisPayload(''))->toBeFalse();
});
