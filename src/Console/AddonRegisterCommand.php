<?php

namespace Pyro\CustomInstall\Console;

use Anomaly\Streams\Platform\Addon\AddonManager;
use Anomaly\Streams\Platform\Entry\Command\AutoloadEntryModels;
use Illuminate\Console\Command;

class AddonRegisterCommand extends Command
{
    protected $signature = 'addon:_register';

    protected $description = 'register all addons (internal command)';

    public function handle(AddonManager $manager)
    {
        dispatch_now(new AutoloadEntryModels());
        $manager->register(true);
        dispatch_now(new AutoloadEntryModels());
    }
}
