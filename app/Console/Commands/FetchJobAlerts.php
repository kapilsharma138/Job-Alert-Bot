<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\JobFetcher;
use App\Services\JobScorer;
use App\Services\JobDeduplicator;
use App\Services\TelegramNotifier;

class FetchJobAlerts extends Command
{
    protected $signature   = 'jobs:fetch';
    protected $description = 'Fetch PHP job listings and send top matches to Telegram';

    public function __construct(
        private JobFetcher      $fetcher,
        private JobScorer       $scorer,
        private JobDeduplicator $dedup,
        private TelegramNotifier $telegram,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Fetching jobs...');

        // 1. Fetch from all sources
        $allJobs = $this->fetcher->fetch();
        $this->info("Fetched: " . count($allJobs) . " total jobs");

        if (empty($allJobs)) {
            $this->warn('No jobs fetched — check network or sources.');
            return Command::FAILURE;
        }

        // 2. Filter out already seen jobs
        $newJobs = $this->dedup->filterNew($allJobs);
        $this->info("New (not seen before): " . count($newJobs));

        if (empty($newJobs)) {
            $this->info('No new jobs today.');
            $this->telegram->send("📭 <b>Job Alert</b> — No new PHP roles today.");
            return Command::SUCCESS;
        }

        // 3. Score and get top 5
        $topJobs = $this->scorer->topJobs(array_values($newJobs), 5);

        if (empty($topJobs)) {
            $this->info('No relevant jobs after scoring.');
            return Command::SUCCESS;
        }

        // 4. Format Telegram message
        $message = $this->formatMessage($topJobs);

        // 5. Send to Telegram
        $sent = $this->telegram->send($message);

        if ($sent) {
            // 6. Mark as seen only after successful send
            foreach ($topJobs as $job) {
                $this->dedup->markSeen($job['link'], $job['title'], $job['source']);
            }
            $this->info("Sent " . count($topJobs) . " jobs to Telegram.");
        } else {
            $this->error('Telegram send failed — jobs not marked as seen, will retry tomorrow.');
        }

        return Command::SUCCESS;
    }

    private function formatMessage(array $jobs): string
    {
        $date    = now()->format('D, d M Y');
        $message = "🔍 <b>PHP Job Alert — {$date}</b>\n";
        $message .= str_repeat("─", 30) . "\n\n";

        foreach ($jobs as $i => $job) {
            $num      = $i + 1;
            $title    = htmlspecialchars($job['title']);
            $company  = htmlspecialchars($job['company']);
            $source   = $job['source'];
            $score    = $job['score'];
            $link     = $job['link'];

            $message .= "{$num}. <b>{$title}</b>\n";
            $message .= "   🏢 {$company}  ·  📌 {$source}  ·  ⭐ Score: {$score}\n";
            $message .= "   <a href='{$link}'>Apply →</a>\n\n";
        }

        $message .= "─────────────────────────────\n";
        $message .= "💻 <i>Powered by Job Alert Bot</i>";

        return $message;
    }
}