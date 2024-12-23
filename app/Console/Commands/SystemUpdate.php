<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SystemUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'system:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run all system updates including exchange rates, news, and more';

    /**
     * Execute the console command.
     */
    public function handle()
    {
         // Daftar tugas (task) yang akan dijalankan
        $tasks = [
            [\App\Http\Controllers\API\KursController::class, 'store'],
            [\App\Http\Controllers\API\BeritaController::class, 'store'],
            // Tambahkan tugas lain di sini jika diperlukan
        ];

        foreach ($tasks as $task) {
            try {
                [$controllerClass, $method] = $task;
                $controller = new $controllerClass();
                $controller->$method(request()); // Sesuaikan jika memerlukan parameter khusus
                $this->info("Task {$controllerClass}::{$method} executed successfully.");
            } catch (\Exception $e) {
                $this->error("Error in {$controllerClass}::{$method}: " . $e->getMessage());
            }
        }

        $this->info('System update completed.');
        return Command::SUCCESS;
    }
}
