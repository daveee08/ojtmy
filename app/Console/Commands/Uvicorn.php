<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;


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
    protected $description = 'Run the FastAPI Chat Server using Uvicorn';

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
        $this->info('Starting FastAPI server...');

        $process = new Process([
            'uvicorn',
            'chat_router_feb:chat_router', // Adjust this if your FastAPI app is named differently
            '--host', '0.0.0.0',
            '--port', '8000',
            '--reload'
        ]);

        $process->setWorkingDirectory(base_path('python/Tutor Agent/')); // adjust if different
        $process->setTimeout(null); // Run indefinitely

        $process->start();

        foreach ($process as $type => $data) {
            if ($process::OUT === $type) {
                $this->line($data);  // Normal output (print, logging, stdout)
            } else { // STDERR
                $this->error($data); // Errors and tracebacks
            }
        }

        return 0;
    }
}

