<?php

namespace App\Console\Commands;

use App\Services\Ledger;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('ledger:anchor')]
#[Description('Record today\'s ledger head hash as a public daily anchor')]
class AnchorLedger extends Command
{
    public function handle(Ledger $ledger): int
    {
        $anchor = $ledger->anchor();

        if ($anchor === null) {
            $this->info('Ledger is empty, nothing to anchor.');

            return self::SUCCESS;
        }

        $this->info("Anchor {$anchor->anchored_on->toDateString()}: entry #{$anchor->seq} {$anchor->hash}");

        return self::SUCCESS;
    }
}
