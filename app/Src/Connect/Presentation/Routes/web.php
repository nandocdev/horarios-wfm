<?php

declare(strict_types=1);

use App\Src\Connect\Infrastructure\WebSockets\TelephonyEventSubscriber;
use Illuminate\Support\Facades\Route;

Route::post('/api/connect/cisco/webhook', function (TelephonyEventSubscriber $subscriber) {
    $payload = request()->all();
    $subscriber->handleCiscoWebhook($payload);
    return response()->json(['status' => 'ok']);
})->name('connect.cisco.webhook');

Route::post('/api/connect/avaya/webhook', function (TelephonyEventSubscriber $subscriber) {
    $payload = request()->all();
    $subscriber->handleAvayaWebhook($payload);
    return response()->json(['status' => 'ok']);
})->name('connect.avaya.webhook');
