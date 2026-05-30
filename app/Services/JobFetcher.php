<?php

namespace App\Services;

class JobFetcher
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    // $response = Http::get('https://larajobs.com/feed');
    // $xml = simplexml_load_string($response->body());
    // foreach ($xml->channel->item as $item) {
    //     $jobs[] = [
    //         'title'   => (string) $item->title,
    //         'company' => (string) $item->children('dc', true)->creator,
    //         'link'    => (string) $item->link,
    //         'desc'    => (string) $item->description,
    //         'source'  => 'LaraJobs',
    //     ];
    // }
}
