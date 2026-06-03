<?php

namespace App\Services;

class JobScorer
{
    // Keywords that increase score
    private array $mustHave = [
        'php'     => 4,
        'laravel' => 4,
        'node.js' => 4,
        'nodejs'  => 4,
    ];

    private array $positive = [
        'senior'       => 3,
        'backend'      => 2,
        'node.js'      => 2,
        'node'         => 2,
        'aws'          => 2,
        'remote'       => 2,
        'api'          => 1,
        'mysql'        => 1,
        'rest'         => 1,
        'microservice' => 1,
        'docker'       => 1,
        'full stack'   => 1,
        'full-stack'   => 1,
    ];

    // Keywords that decrease score
    private array $negative = [
        'junior'    => -4,
        'intern'    => -5,
        'internship'=> -5,
        'java '     => -2,  // space prevents matching javascript
        'python'    => -2,
        '.net'      => -2,
        'ruby'      => -2,
        'android'   => -3,
        'ios'       => -3,
        'react native' => -2,
    ];

    // public function score(array $job): int
    // {
    //     $text  = strtolower($job['title'] . ' ' . $job['desc']);
    //     $score = 0;

    //     // must-have — if neither PHP nor Laravel → skip entirely
    //     $hasMustHave = false;
    //     foreach ($this->mustHave as $keyword => $weight) {
    //         if (str_contains($text, $keyword)) {
    //             $score      += $weight;
    //             $hasMustHave = true;
    //         }
    //     }

    //     if (!$hasMustHave) return -99; // not relevant at all

    //     foreach ($this->positive as $keyword => $weight) {
    //         if (str_contains($text, $keyword)) {
    //             $score += $weight;
    //         }
    //     }

    //     foreach ($this->negative as $keyword => $penalty) {
    //         if (str_contains($text, $keyword)) {
    //             $score += $penalty; // penalty is already negative
    //         }
    //     }

    //     return $score;
    // }
    public function score(array $job): int
    {
        $text  = strtolower($job['title'] . ' ' . $job['desc']);
        $score = 0;

        // CHANGED: job must have PHP/Laravel OR Node.js — not both required
        $hasPhp  = str_contains($text, 'php') || str_contains($text, 'laravel');
        $hasNode = str_contains($text, 'node.js') || str_contains($text, 'nodejs') || str_contains($text, 'node js');

        if (!$hasPhp && !$hasNode) return -99;

        foreach ($this->mustHave as $keyword => $weight) {
            if (str_contains($text, $keyword)) {
                $score += $weight;
            }
        }

        foreach ($this->positive as $keyword => $weight) {
            if (str_contains($text, $keyword)) {
                $score += $weight;
            }
        }

        foreach ($this->negative as $keyword => $penalty) {
            if (str_contains($text, $keyword)) {
                $score += $penalty;
            }
        }

        return $score;
    }

    // public function topJobs(array $jobs, int $limit = 5): array
    // {
    //     // score each job
    //     $scored = array_map(function ($job) {
    //         return array_merge($job, ['score' => $this->score($job)]);
    //     }, $jobs);

    //     // remove irrelevant jobs
    //     $scored = array_filter($scored, fn($j) => $j['score'] > 0);

    //     // sort by score descending
    //     usort($scored, fn($a, $b) => $b['score'] <=> $a['score']);

    //     return array_slice($scored, 0, $limit);
    // }
    public function topJobs(array $jobs, int $limit = 5): array
    {
        $scored = array_map(function ($job) {
            return array_merge($job, ['score' => $this->score($job)]);
        }, $jobs);

        $scored = array_filter($scored, function ($j) {
            return $j['score'] > 0 && $this->meetsSalaryFilter($j); // ← add salary check
        });

        usort($scored, fn($a, $b) => $b['score'] <=> $a['score']);

        return array_slice($scored, 0, $limit);
    }

    private function meetsSalaryFilter(array $job): bool
    {
        $minSalary = (int) config('services.jobs.min_salary_lpa', 0);

        if ($minSalary === 0) return true; // no filter set

        $text = strtolower($job['title'] . ' ' . $job['desc']);

        // look for salary patterns: "20 lpa", "20lpa", "₹20", "20 lac"
        preg_match_all('/(\d+)\s*(?:lpa|lac|lakh|l\.p\.a)/i', $text, $matches);

        if (empty($matches[1])) return true; // no salary mentioned — don't filter out

        $maxFound = max(array_map('intval', $matches[1]));

        return $maxFound >= $minSalary;
    }
}