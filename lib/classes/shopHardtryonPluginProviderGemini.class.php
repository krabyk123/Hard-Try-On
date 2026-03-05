<?php
class shopHardtryonPluginProviderGemini
{
    public static function generate(array $settings, $prompt, $user_image_url, array $product_image_urls, $reference_pose_url = '')
    {
        $key = trim((string) ifset($settings['gemini_api_key'], ''));
        $model = trim((string) ifset($settings['gemini_model'], 'gemini-2.5-flash-image-preview'));
        $timeout = max(15, (int) ifset($settings['gemini_timeout'], 120));
        if ($key === '') {
            throw new waException('Не заполнен Gemini API key.');
        }

        $proxy = [
            'enabled' => !empty($settings['gemini_proxy_enabled']),
            'host' => (string) ifset($settings['gemini_proxy_host'], ''),
            'port' => (string) ifset($settings['gemini_proxy_port'], ''),
            'user' => (string) ifset($settings['gemini_proxy_user'], ''),
            'pass' => (string) ifset($settings['gemini_proxy_pass'], ''),
        ];

        $parts = [];
        $parts[] = ['text' => $prompt];

        $garment_count = 0;
        foreach ($product_image_urls as $url) {
            $garment_count++;
            $parts[] = ['text' =>
                '=== GARMENT IMAGE ' . $garment_count . ' (SOURCE OF CLOTHING) ===' . "\n" .
                'This image shows the product garment. Ignore the person in the image entirely if present. ' .
                'Extract ONLY the garment type, color, texture, cut, pattern, and design details.'
            ];
            $parts[] = [
                'inline_data' => [
                    'mime_type' => shopHardtryonPluginHelper::guessMimeByUrl($url),
                    'data' => base64_encode(shopHardtryonPluginHttp::getBytes($url, [], 60, $proxy)),
                ],
            ];
        }

        if ($reference_pose_url) {
            $parts[] = ['text' =>
                '=== POSE REFERENCE (BODY POSITION ONLY) ===' . "\n" .
                'Use ONLY body position and pose from this image. Do NOT copy any clothing, colors, or fabric from it.'
            ];
            $parts[] = [
                'inline_data' => [
                    'mime_type' => shopHardtryonPluginHelper::guessMimeByUrl($reference_pose_url),
                    'data' => base64_encode(shopHardtryonPluginHttp::getBytes($reference_pose_url, [], 60, $proxy)),
                ],
            ];
        }

        $parts[] = ['text' =>
            '=== PERSON PHOTO (TARGET) ===' . "\n" .
            'This is the real person who must wear the garment. Keep face, hair, skin tone, body shape, pose, hands, and background unchanged. Replace only the target clothing item.'
        ];
        $parts[] = [
            'inline_data' => [
                'mime_type' => shopHardtryonPluginHelper::guessMimeByUrl($user_image_url),
                'data' => base64_encode(shopHardtryonPluginHttp::getBytes($user_image_url, [], 60, $proxy)),
            ],
        ];

        $parts[] = ['text' =>
            '=== YOUR TASK ===' . "\n" .
            'Dress the person from PERSON PHOTO in the garment from GARMENT IMAGE(s). Preserve the person and background. Return one photorealistic image only.'
        ];

        $payload = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => $parts,
                ],
            ],
            'generationConfig' => [
                'responseModalities' => ['Image'],
            ],
        ];

        list($code, $body) = shopHardtryonPluginHttp::postJson(
            'https://generativelanguage.googleapis.com/v1beta/models/' . rawurlencode($model) . ':generateContent',
            $payload,
            ['x-goog-api-key' => $key],
            $timeout,
            $proxy
        );

        if ($code < 200 || $code >= 300) {
            throw new waException('Gemini API error: HTTP ' . $code . ' ' . mb_substr((string) $body, 0, 300));
        }

        $json = json_decode((string) $body, true);
        if (!is_array($json)) {
            throw new waException('Gemini вернул невалидный JSON.');
        }

        foreach ((array) ifset($json['candidates'][0]['content']['parts'], []) as $part) {
            if (!empty($part['inlineData']['data'])) {
                return $part['inlineData']['data'];
            }
            if (!empty($part['inline_data']['data'])) {
                return $part['inline_data']['data'];
            }
        }

        throw new waException('Gemini не вернул изображение в ответе.');
    }
}
