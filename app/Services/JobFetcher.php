<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class JobFetcher
{
    public function fetch(): array
    {
        $jobs = [];

        $jobs = array_merge($jobs, $this->fetchLaraJobs());
        $jobs = array_merge($jobs, $this->fetchRemotive());
        $jobs = array_merge($jobs, $this->fetchRemoteOk());

        return $jobs;
    }

    private function fetchLaraJobs(): array
    {
        try {

            $response = Http::timeout(15)
                ->get('https://larajobs.com/feed');

            if (!$response->successful()) {
                return [];
            }

            $xml = simplexml_load_string($response->body());

            $jobs = [];

            foreach ($xml->channel->item as $item) {

                $jobs[] = [
                    'title'   => (string)$item->title,
                    'company' => (string)$item->children('dc', true)->creator ?: 'Unknown',
                    'link'    => (string)$item->link,
                    'desc'    => strip_tags((string)$item->description),
                    'source'  => 'LaraJobs',
                ];
            }

            return $jobs;

        } catch (\Exception $e) {

            Log::warning('LaraJobs failed: '.$e->getMessage());

            return [];
        }
    }

    private function fetchRemotive(): array
    {
        try {

            $response = Http::timeout(15)
                ->get('https://remotive.com/api/remote-jobs', [
                    'category' => 'software-dev'
                ]);

            if (!$response->successful()) {
                return [];
            }

            $jobs = [];

            foreach ($response->json('jobs', []) as $job) {

                $text = strtolower(
                    ($job['title'] ?? '') .
                    ' ' .
                    strip_tags($job['description'] ?? '')
                );

                if (
                    !str_contains($text, 'php') &&
                    !str_contains($text, 'laravel') &&
                    !str_contains($text, 'backend')
                ) {
                    continue;
                }

                $jobs[] = [
                    'title'   => $job['title'],
                    'company' => $job['company_name'],
                    'link'    => $job['url'],
                    'desc'    => strip_tags($job['description']),
                    'source'  => 'Remotive',
                ];
            }

            return $jobs;

        } catch (\Exception $e) {

            Log::warning('Remotive failed: '.$e->getMessage());

            return [];
        }
    }

    private function fetchRemoteOk(): array
    {
        try {

            $response = Http::timeout(15)
                ->get('https://remoteok.com/api');

            if (!$response->successful()) {
                return [];
            }

            $jobs = [];

            foreach ($response->json() as $job) {

                if (!isset($job['position'])) {
                    continue;
                }

                $text = strtolower(
                    ($job['position'] ?? '') .
                    ' ' .
                    implode(' ', $job['tags'] ?? [])
                );

                if (
                    !str_contains($text, 'php') &&
                    !str_contains($text, 'laravel')
                ) {
                    continue;
                }

                $jobs[] = [
                    'title'   => $job['position'],
                    'company' => $job['company'] ?? 'Unknown',
                    'link'    => $job['url'] ?? '',
                    'desc'    => implode(', ', $job['tags'] ?? []),
                    'source'  => 'RemoteOK',
                ];
            }

            return $jobs;

        } catch (\Exception $e) {

            Log::warning('RemoteOK failed: '.$e->getMessage());

            return [];
        }
    }
}