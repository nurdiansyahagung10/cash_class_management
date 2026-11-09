<?php

namespace App\Console\Commands;

use App\Services\Ledger;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('ledger:verify')]
#[Description('Recompute the ledger hash-chain, check it against the daily anchors and report the first problem')]
class VerifyLedger extends Command
{
    public function handle(Ledger $ledger): int
    {
        $brokenSeq = $ledger->firstBrokenSeq();
        $mismatchedAnchor = $ledger->firstMismatchedAnchor();

        if ($brokenSeq === null && $mismatchedAnchor === null) {
            $this->info('Ledger intact.');

            return self::SUCCESS;
        }

        if ($brokenSeq !== null) {
            $this->error("Ledger broken at entry #{$brokenSeq}.");
        }

        if ($mismatchedAnchor !== null) {
            $this->error("Anchor {$mismatchedAnchor->anchored_on->toDateString()} (entry #{$mismatchedAnchor->seq}) no longer matches the ledger.");
        }

        return self::FAILURE;
    }
}
