<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class Uvicorn extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'Uvicorn';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rex T. Pinatil';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        return "0";
    }
}

