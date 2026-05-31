<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class JobDeduplicator
{
    public function isNew(string $url): bool
    {
        return !DB::table('seen_jobs')->where('job_url', $url)->exists();
    }

    public function markSeen(string $url, string $title, string $source): void
    {
        try {
            DB::table('seen_jobs')->insertOrIgnore([
                'job_url'    => $url,
                'title'      => $title,
                'source'     => $source,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::warning('Dedup insert failed: ' . $e->getMessage());
        }
    }

    public function filterNew(array $jobs): array
    {
        return array_filter($jobs, fn($job) => $this->isNew($job['link']));
    }
}