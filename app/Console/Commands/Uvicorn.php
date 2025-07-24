<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class Uvicorn extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'uvicorn:serve'; // Renamed for clarity and convention

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run multiple FastAPI servers using Uvicorn.';

    /**
     * Stores references to the running FastAPI processes.
     *
     * @var array<Process>
     */
    protected $fastApiProcesses = [];

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info(' __    __                                              __ 
/  \  /  |                                            /  |
$$  \ $$ |  ______    ______   _____  ____    ______  $$ |
$$$  \$$ | /      \  /      \ /     \/    \  /      \ $$ |
$$$$  $$ |/$$$$$$  |/$$$$$$  |$$$$$$ $$$$  | $$$$$$  |$$ |
$$ $$ $$ |$$ |  $$ |$$ |  $$/ $$ | $$ | $$ | /    $$ |$$ |
$$ |$$$$ |$$ \__$$ |$$ |      $$ | $$ | $$ |/$$$$$$$ |$$ |
$$ | $$$ |$$    $$/ $$ |      $$ | $$ | $$ |$$    $$ |$$ |
$$/   $$/  $$$$$$/  $$/       $$/  $$/  $$/  $$$$$$$/ $$/ 
                                                          
                                                          
                                                          ');


        $this->info('Starting FastAPI servers...');

        // Define your FastAPI apps, their directories, filenames, and ports
        // 'file_and_app_instance' should be 'your_python_file_name_without_py:your_fastapi_app_instance_name'
        $fastApiApps = [
            // [
            //     'name' => 'Chat Router API',
            //     'path' => base_path('python/Tutor Agent'), // Directory where your Python file is
            //     'file_and_app_instance' => 'chat_router_feb:chat_router', // e.g., if you have main.py and `app = FastAPI()`
            //     'port' => 8001,
            // ],
            // [
            //     'name' => 'Tutor Agent API',
            //     'path' => base_path('python/Tutor Agent'), // Directory where your Python file is
            //     'file_and_app_instance' => 'tutor_agent:app', // e.g., if you have main.py and `app = FastAPI()`
            //     'port' => 8002
            // ],
            // [
            //     'name' => 'Summarizer API',
            //     'path' => base_path('python\Summarizer'), // Directory where your Python file is
            //     'file_and_app_instance' => 'mainkhan:app', // e.g., if you have main.py and `app = FastAPI()`
            //     'port' => 8003
            // ],
            // [
            //     'name' => 'Khan API',
            //     'path' => base_path('python\Email Writer'), // Directory where your Python file is
            //     'file_and_app_instance' => 'email-writer:app', // e.g., if you have main.py and `app = FastAPI()`
            //     'port' => 8003
            // ],
            //   [
            //     'name' => 'Study Habits API',
            //     'path' => base_path('python\study_habits'), // Directory where your Python file is
            //     'file_and_app_instance' => 'study_habits_agent:app', // e.g., if you have main.py and `app = FastAPI()`
            //     'port' => 8004
            // ],
           
            // [
            //     'name' => 'Sentence Starters API',
            //     'path' => base_path('python\sentence_starters'), // Directory where your Python file is
            //     'file_and_app_instance' => 'sentence_starters_agent:app', // e.g., if you have main.py and `app = FastAPI()`
            //     'port' => 8005
            // ],
            //  [
            //     'name' => 'Proofreader API',
            //     'path' => base_path('python\proofreader'), // Directory where your Python file is
            //     'file_and_app_instance' => 'proofreader:app', // e.g., if you have main.py and `app = FastAPI()`
            //     'port' => 8006
            // ],
            //  [
            //     'name' => 'Real World Agent API',
            //     'path' => base_path('python\Real World'), // Directory where your Python file is
            //     'file_and_app_instance' => 'real_world_agent:app', // e.g., if you have main.py and `app = FastAPI()`
            //     'port' => 8007
            // ],
            //  [
            //     'name' => '5 questions API',
            //     'path' => base_path('python\five_questions'), // Directory where your Python file is
            //     'file_and_app_instance' => 'five_question_agent:app', // e.g., if you have main.py and `app = FastAPI()`
            //     'port' => 8008
            // ],
          
            [
                'name' => 'Mav Leveler with Chat Router API',
                'path' => base_path('python\Text Leveler'), // Directory where your Python file is
                'file_and_app_instance' => 'leveler_agent:app', // e.g., if you have main.py and `app = FastAPI()`
                'port' => 8009
            ],
            [
                'name' => 'Mav Informational API',
                'path' => base_path('python\Informational Text'), // Directory where your Python file is
                'file_and_app_instance' => 'informational_agent:app', // e.g., if you have main.py and `app = FastAPI()`
                'port' => 8010
            ],
            [
                'name' => 'Mav Chat with Docs API',
                'path' => base_path('python\Chat with Docs'), // Directory where your Python file is
                'file_and_app_instance' => 'chatwithdocs_agent:app', // e.g., if you have main.py and `app = FastAPI()`
                'port' => 8011
            ],
            [
                'name' => 'Mav Math Review API',
                'path' => base_path('python\five_questions'), // Directory where your Python file is
                'file_and_app_instance' => 'mathreview:app', // e.g., if you have main.py and `app = FastAPI()`
                'port' => 8012
            ],
            [
                'name' => 'Mav Make it Relevant API',
                'path' => base_path('python\Make it Relevant'), // Directory where your Python file is
                'file_and_app_instance' => 'makeitrelevant:app', // e.g., if you have main.py and `app = FastAPI()`
                'port' => 8013
            ],
            // [
            //     'name' => 'Bea Ass Scaffolder API',
            //     'path' => base_path('python\Assignment Scaffolder'), // Directory where your Python file is
            //     'file_and_app_instance' => 'assignmentscaffolder:app', // e.g., if you have main.py and `app = FastAPI()`
            //     'port' => 8014
            // ],
            // [
            //     'name' => 'Bea Email Responder API',
            //     'path' => base_path('python\Email Responder'), // Directory where your Python file is
            //     'file_and_app_instance' => 'responder:app', // e.g., if you have main.py and `app = FastAPI()`
            //     'port' => 8015
            // ],
            // [
            //     'name' => 'Bea Text Scaffolder API',
            //     'path' => base_path('python\Scaffolder'), // Directory where your Python file is
            //     'file_and_app_instance' => 'scaffolder:app', // e.g., if you have main.py and `app = FastAPI()`
            //     'port' => 8016
            // ],
            // [
            //     'name' => 'Bea Rewriter API',
            //     'path' => base_path('python\Rewriter'), // Directory where your Python file is
            //     'file_and_app_instance' => 'rewriter_agent:app', // e.g., if you have main.py and `app = FastAPI()`
            //     'port' => 8017
            // ],
            // [
            //     'name' => 'Bea explainer API',
            //     'path' => base_path('python'), // Directory where your Python file is
            //     'file_and_app_instance' => 'explanations:app', // e.g., if you have main.py and `app = FastAPI()`
            //     'port' => 8018
            // ],
            
            // [
            //     'name' => 'Translator API',
            //     'path' => base_path('python/translator'), // Directory where your Python file is
            //     'file_and_app_instance' => 'translator_agent:app', // e.g., if you have main.py and `app = FastAPI()`
            //     'port' => 8019
            // ],
            // [
            //     'name' => 'Step by Step AGent',
            //     'path' => base_path('python'), // Directory where your Python file is
            //     'file_and_app_instance' => 'step_tutor_agent:app', // e.g., if you have main.py and `app = FastAPI()`
            //     'port' => 8020
            // ],
            // [
            //     'name' => 'Social Stories AGent',
            //     'path' => base_path('python\Social Stories'), // Directory where your Python file is
            //     'file_and_app_instance' => 'social_stories_agent:app', // e.g., if you have main.py and `app = FastAPI()`
            //     'port' => 8021
            // ],
            

                    
            
            // Add more FastAPI apps as needed
        ];

        foreach ($fastApiApps as $appConfig) {
            $this->info("Attempting to start '{$appConfig['name']}' at '{$appConfig['path']}' on port {$appConfig['port']}...");

            $command = [
                // 'python', // Use 'python' if 'python3' is not available or desired
                // '-m',
                'uvicorn',
                $appConfig['file_and_app_instance'],
                '--host',
                '0.0.0.0', // Listen on all network interfaces
                '--port',
                (string) $appConfig['port'], 
                // '--reload',// Cast to string for process command
            ];

            $process = new Process($command);
            $process->setWorkingDirectory($appConfig['path']); // Crucial: Run from the FastAPI app's directory
            $process->setTimeout(null); // Allow the process to run indefinitely

            try {
                // Start the process in the background
                $process->start();
                $this->fastApiProcesses[] = $process; // Store for later management
                $this->info("Successfully started '{$appConfig['name']}' (PID: {$process->getPid()})");

                // You can optionally read output asynchronously here, or rely on logs
                // For simplicity, we won't show real-time output in the main loop,
                // as it can block if not done carefully with non-blocking reads.
                // Output will be visible in the console if the process fails immediately.

            } catch (ProcessFailedException $exception) {
                $this->error("Failed to start '{$appConfig['name']}': " . $exception->getMessage());
                // Optionally, exit if a critical service fails to start
                return 1;
            } catch (\Exception $e) {
                $this->error("An unexpected error occurred while starting '{$appConfig['name']}': " . $e->getMessage());
                return 1;
            }
        }

        $this->info('All FastAPI services initiated. The command will now keep running.');
        $this->info('Press Ctrl+C to stop all FastAPI servers.');

        // Keep the main Artisan process alive indefinitely
        // This loop checks the status of background processes periodically.
        while (true) {
            foreach ($this->fastApiProcesses as $index => $process) {
                if (!$process->isRunning()) {
                    $this->error("FastAPI process for '{$fastApiApps[$index]['name']}' (PID: {$process->getPid()}) died unexpectedly.");
                    // TODO: Implement sophisticated restart logic here if desired for production
                    // For now, we'll just log and let it be.
                    unset($this->fastApiProcesses[$index]); // Remove dead process
                    $this->fastApiProcesses = array_values($this->fastApiProcesses); // Re-index array
                }
            }

            if (empty($this->fastApiProcesses)) {
                $this->warn('All FastAPI processes have stopped. Exiting command.');
                return 1; // Exit if all services are down
            }

            sleep(5); // Check every 5 seconds
        }
    }

    /**
     * Called when the command is terminated (e.g., by Ctrl+C).
     * Stops all running FastAPI processes gracefully.
     */
    // public function __destruct()
    // {
    //     if (!empty($this->fastApiProcesses)) {
    //         $this->info('Attempting to stop all FastAPI services...');
    //         foreach ($this->fastApiProcesses as $process) {
    //             if ($process->isRunn ing()) {
    //                 try {
    //                     $process->stop(5); // Give the process 5 seconds to gracefully stop
    //                     $this->info("Stopped FastAPI process (PID: {$process->getPid()})");
    //                 } catch (\Exception $e) {
    //                     $this->warn("Could not stop process {$process->getPid()}: " . $e->getMessage());
    //                 }
    //             }
    //         }
    //         $this->info('All FastAPI services have been instructed to stop.');
    //     } else {
    //         $this->info('No FastAPI processes were running to stop.');
    //     }
    // }
}