<?php

namespace App\Console\Commands;

use App\Services\Ledger;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('ledger:verify')]
#[Description('Recompute the ledger hash-chain and report the first broken entry')]
class VerifyLedger extends Command
{
    public function handle(Ledger $ledger): int
    {
        $brokenSeq = $ledger->firstBrokenSeq();

        if ($brokenSeq === null) {
            $this->info('Ledger intact.');

            return self::SUCCESS;
        }

        $this->error("Ledger broken at entry #{$brokenSeq}.");

        return self::FAILURE;
    }
}
