<?php
class shopHardtryonPluginProviderCustom
{
    public static function generate(array $settings, $prompt, $user_image_url, array $product_image_urls, $reference_pose_url = '', $product_id = 0)
    {
        $endpoint = trim((string) ifset($settings['custom_endpoint'], ''));
        if ($endpoint === '') {
            throw new waException('Не заполнен Custom endpoint.');
        }

        list($code, $body) = shopHardtryonPluginHttp::postJson(
            $endpoint,
            [
                'product_id' => (int) $product_id,
                'prompt' => $prompt,
                'user_image_url' => $user_image_url,
                'product_image_urls' => array_values($product_image_urls),
                'reference_pose_url' => $reference_pose_url,
            ],
            [],
            180
        );

        if ($code < 200 || $code >= 300) {
            throw new waException('Custom endpoint error: HTTP ' . $code . ' ' . mb_substr((string) $body, 0, 300));
        }

        $json = json_decode((string) $body, true);
        if (!is_array($json)) {
            throw new waException('Custom endpoint вернул невалидный JSON.');
        }

        if (!empty($json['image_base64'])) {
            return (string) $json['image_base64'];
        }
        if (!empty($json['image_url'])) {
            return base64_encode(shopHardtryonPluginHttp::getBytes((string) $json['image_url'], [], 60));
        }

        throw new waException('Custom endpoint не вернул image_base64 или image_url.');
    }
}
