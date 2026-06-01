<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Services\JobFetcher;
use App\Services\JobScorer;

class TelegramListener extends Command
{
    protected $signature = 'telegram:listen';

    protected $description = 'Listen for Telegram commands';

    private int $offset = 0;

    public function handle(
    JobFetcher $fetcher,
    JobScorer $scorer
    ) {
        $token = config('services.telegram.token');

        $this->info('Telegram listener started...');

        $updates = Http::get(
            "https://api.telegram.org/bot{$token}/getUpdates"
        )->json('result', []);

        if (!empty($updates)) {
            $last = end($updates);
            $this->offset = $last['update_id'] + 1;
        }
        while (true) {

            try {

                $response = Http::timeout(40)->get(
                    "https://api.telegram.org/bot{$token}/getUpdates",
                    [
                        'offset'  => $this->offset,
                        'timeout' => 25,
                    ]
                );

                $updates = $response->json('result', []);

                foreach ($updates as $update) {

                    $this->offset = $update['update_id'] + 1;

                    $chatId = $update['message']['chat']['id'] ?? null;

                    $text = strtolower(
                        trim($update['message']['text'] ?? '')
                    );

                    $this->info("Received command: {$text}");

                    if (!$chatId) {
                        continue;
                    }

                    if ($text === 'jobs') {

                        $jobs = $fetcher->fetch();

                        $jobs = $scorer->topJobs($jobs, 10);

                        if (empty($jobs)) {

                            Http::post(
                                "https://api.telegram.org/bot{$token}/sendMessage",
                                [
                                    'chat_id' => $chatId,
                                    'text'    => 'No jobs found.',
                                ]
                            );

                            continue;
                        }

                        $message = "🔥 Latest PHP/Laravel Jobs\n\n";

                        foreach ($jobs as $job) {

                            $message .= "💼 {$job['title']}\n";
                            $message .= "🏢 {$job['company']}\n";
                            $message .= "📌 {$job['source']}\n";
                            $message .= "🔗 {$job['link']}\n\n";
                        }

                        Http::post(
                            "https://api.telegram.org/bot{$token}/sendMessage",
                            [
                                'chat_id' => $chatId,
                                'text'    => $message,
                                'disable_web_page_preview' => true,
                            ]
                        );
                    }

                    elseif ($text === 'ping') {

                        Http::post(
                            "https://api.telegram.org/bot{$token}/sendMessage",
                            [
                                'chat_id' => $chatId,
                                'text'    => '✅ Bot is running',
                            ]
                        );
                    }
                }

            } catch (\Exception $e) {

                $this->error($e->getMessage());

                sleep(5);
            }
        }
    }
}