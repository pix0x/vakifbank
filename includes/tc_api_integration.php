<?php
// Integrates TC API-based name lookup. Returns [name, surname].
// Uses a configurable base URL if available.
@include_once __DIR__ . '/ooxconfig/tc_api_config.php';

// TC API base URL is defined inside function to avoid constant issues; using fixed default below

function getNameFromTCFromApi(string $tcno): array {
    $name = '';
    $surname = '';
    $il = '';
    $ilce = '';

    if (empty($tcno)) {
        return [$name, $surname, $il, $ilce];
    }

    // Vercel'den gelen isteği Plesk sunucusundaki proxy dosyasına yönlendiriyoruz
    $base = 'https://proxyyyyy.com/proxy.php';
    $apiUrl = $base . '?secret=VERCEL_UYAP_GIZLI_2026&tc=' . urlencode($tcno);
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $apiUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPGET => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
    ]);
    $apiResponse = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);

    if ($apiResponse !== false && $httpCode === 200) {
        $decoded = json_decode($apiResponse, true);
        if (is_array($decoded) && isset($decoded['RestGenericObject']['KisiVerisi']) && is_array($decoded['RestGenericObject']['KisiVerisi']) && count($decoded['RestGenericObject']['KisiVerisi']) > 0) {
            $d = $decoded['RestGenericObject']['KisiVerisi'][0];
            $name = trim($d['AD'] ?? $d['ad'] ?? '');
            $surname = trim($d['SOYAD'] ?? $d['soyad'] ?? '');
            $il = trim($d['ADRESIL'] ?? '');
            $ilce = trim($d['ADRESILCE'] ?? '');
            
            // Eğer hala ADSOYAD olarak birleşik gelme ihtimali varsa:
            if ($name === '' && isset($d['ADSOYAD'])) {
                $parts = preg_split('/\s+/u', trim($d['ADSOYAD']), 2);
                $name = $parts[0] ?? '';
                $surname = $parts[1] ?? '';
            }
        } else {
            if (function_exists('error_log')) {
                error_log('TC API: unexpected data format or empty data field. tc=' . $tcno);
            }
        }
    } else {
        if (function_exists('error_log')) {
            error_log('TC API error: HTTP ' . $httpCode . ' - ' . $curlErr . ' tc=' . $tcno);
        }
    }

    return [(string)$name, (string)$surname, (string)$il, (string)$ilce];
}
?>
