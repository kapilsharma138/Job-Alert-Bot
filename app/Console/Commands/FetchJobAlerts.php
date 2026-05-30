<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class FetchJobAlerts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fetch-job-alerts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        //
    }

    // $message = "🔍 PHP Job Alert — " . now()->format('d M Y') . "\n\n";
    // foreach ($topJobs as $i => $job) {
    //     $message .= ($i+1) . ". {$job['title']}\n";
    //     $message .= "   {$job['company']} · Score: {$job['score']}\n";
    //     $message .= "   Apply →\n\n";
    // }
    // $this->telegram->send($message);
}
