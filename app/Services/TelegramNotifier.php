<?php

namespace App\Services;

class TelegramNotifier
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    // The whole service is just this:
    // Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
    //     'chat_id'    => $chatId,
    //     'text'       => $message,
    //     'parse_mode' => 'HTML',
    // ]);
}
