<?php
class shopHardtryonPluginHttp
{
    public static function postJson($url, array $payload, array $headers = [], $timeout = 120, array $proxy = [])
    {
        $ch = curl_init($url);
        $curl_headers = ['Content-Type: application/json'];
        foreach ($headers as $name => $value) {
            $curl_headers[] = $name . ': ' . $value;
        }

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => (int) $timeout,
            CURLOPT_HTTPHEADER => $curl_headers,
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);

        self::applyProxy($ch, $proxy);

        $body = curl_exec($ch);
        if ($body === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new waException('cURL POST error: ' . $error);
        }

        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [$code, $body];
    }

    public static function get($url, array $headers = [], $timeout = 60, array $proxy = [])
    {
        $ch = curl_init($url);
        $curl_headers = [];
        foreach ($headers as $name => $value) {
            $curl_headers[] = $name . ': ' . $value;
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => (int) $timeout,
            CURLOPT_HTTPHEADER => $curl_headers,
        ]);

        self::applyProxy($ch, $proxy);

        $body = curl_exec($ch);
        if ($body === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new waException('cURL GET error: ' . $error);
        }

        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [$code, $body];
    }

    public static function getBytes($url, array $headers = [], $timeout = 60, array $proxy = [])
    {
        list($code, $body) = self::get($url, $headers, $timeout, $proxy);
        if ($code < 200 || $code >= 300) {
            throw new waException('HTTP GET error: ' . $code . ' ' . mb_substr((string) $body, 0, 300));
        }
        return (string) $body;
    }

    private static function applyProxy($ch, array $proxy)
    {
        if (empty($proxy['enabled'])) {
            return;
        }

        $host = trim((string) ifset($proxy['host'], ''));
        $port = trim((string) ifset($proxy['port'], ''));
        if ($host === '' || $port === '') {
            return;
        }

        curl_setopt($ch, CURLOPT_PROXY, $host . ':' . $port);
        curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);

        $user = trim((string) ifset($proxy['user'], ''));
        $pass = (string) ifset($proxy['pass'], '');
        if ($user !== '') {
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $user . ':' . $pass);
        }
    }
}
