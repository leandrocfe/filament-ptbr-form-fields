<?php

namespace Leandrocfe\FilamentPtbrFormFields\Commands;

use Illuminate\Console\Command;

class FilamentPtbrFormFieldsCommand extends Command
{
    public $signature = 'filament-ptbr-form-fields';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
