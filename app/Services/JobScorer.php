<?php

namespace App\Services;

class JobScorer
{
    // Keywords that increase score
    private array $mustHave = [
        'php'     => 4,
        'laravel' => 4,
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

    public function score(array $job): int
    {
        $text  = strtolower($job['title'] . ' ' . $job['desc']);
        $score = 0;

        // must-have — if neither PHP nor Laravel → skip entirely
        $hasMustHave = false;
        foreach ($this->mustHave as $keyword => $weight) {
            if (str_contains($text, $keyword)) {
                $score      += $weight;
                $hasMustHave = true;
            }
        }

        if (!$hasMustHave) return -99; // not relevant at all

        foreach ($this->positive as $keyword => $weight) {
            if (str_contains($text, $keyword)) {
                $score += $weight;
            }
        }

        foreach ($this->negative as $keyword => $penalty) {
            if (str_contains($text, $keyword)) {
                $score += $penalty; // penalty is already negative
            }
        }

        return $score;
    }

    public function topJobs(array $jobs, int $limit = 5): array
    {
        // score each job
        $scored = array_map(function ($job) {
            return array_merge($job, ['score' => $this->score($job)]);
        }, $jobs);

        // remove irrelevant jobs
        $scored = array_filter($scored, fn($j) => $j['score'] > 0);

        // sort by score descending
        usort($scored, fn($a, $b) => $b['score'] <=> $a['score']);

        return array_slice($scored, 0, $limit);
    }
}