<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Services\TelegramNotifier;

class WeeklyJobDigest extends Command
{
    protected $signature   = 'jobs:digest';
    protected $description = 'Send weekly summary of all jobs seen this week';

    public function __construct(private TelegramNotifier $telegram)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $weekAgo = now()->subDays(7);

        $jobs = DB::table('seen_jobs')
            ->where('created_at', '>=', $weekAgo)
            ->orderBy('created_at', 'desc')
            ->get();

        if ($jobs->isEmpty()) {
            $this->info('No jobs seen this week.');
            return Command::SUCCESS;
        }

        // Group by source
        $bySource = $jobs->groupBy('source');

        $message  = "📊 <b>Weekly Job Digest</b>\n";
        $message .= now()->subDays(7)->format('d M') . ' – ' . now()->format('d M Y') . "\n";
        $message .= str_repeat("─", 30) . "\n\n";
        $message .= "📬 <b>Total roles seen: {$jobs->count()}</b>\n\n";

        foreach ($bySource as $source => $sourceJobs) {
            $message .= "📌 <b>{$source}</b> ({$sourceJobs->count()} roles)\n";
            foreach ($sourceJobs->take(3) as $job) {
                $title = htmlspecialchars($job->title);
                $message .= "  · {$title}\n";
            }
            if ($sourceJobs->count() > 3) {
                $more = $sourceJobs->count() - 3;
                $message .= "  · <i>+{$more} more</i>\n";
            }
            $message .= "\n";
        }

        $message .= "─────────────────────────────\n";
        $message .= "💻 <i>Job Alert Bot — Weekly Summary</i>";

        $this->telegram->send($message);
        $this->info('Weekly digest sent.');

        return Command::SUCCESS;
    }
}