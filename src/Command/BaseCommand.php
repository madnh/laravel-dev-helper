<?php

namespace MaDnh\LaravelDevHelper\Command;

use Illuminate\Console\Command;
use MaDnh\LaravelCommandUtil\CommandUtil;

class BaseCommand extends Command
{

    use CommandUtil;

    public function handle()
    {
        //
    }
}
