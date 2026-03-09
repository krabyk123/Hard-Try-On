<?php
class shopHardtryonPluginHelper
{
    public static function buildPrompt(shopProduct $product, array $settings)
    {
        $template = (string) ifset($settings['prompt_template'], '');
        if ($template === '') {
            $template = "Dress the person from the user photo in the garment from the product photo. Keep face, body, hands and background unchanged. Return one photorealistic image.";
        }

        return strtr($template, [
            '{PRODUCT_TITLE}' => (string) $product['name'],
            '{PRODUCT_URL}' => (string) $product->getProductUrl(true, true, true),
            '{PRODUCT_IMAGES_COUNT}' => (string) count(self::getProductImages($product, 50)),
        ]);
    }

    public static function getProductImages(shopProduct $product, $max = 2)
    {
        $images = $product->getImages(['main' => '970'], true);
        $result = [];

        foreach ($images as $image) {
            if (!empty($image['url_main'])) {
                $result[] = $image['url_main'];
            }
            if (count($result) >= $max) {
                break;
            }
        }

        return array_values(array_unique($result));
    }

    public static function diagnoseProductImages(shopProduct $product, $max = 10)
    {
        $images = self::getProductImages($product, $max);
        $result = [];
        foreach ($images as $url) {
            $path = self::urlToLocalPath($url);
            $result[] = [
                'url' => $url,
                'path' => $path,
                'exists' => $path ? file_exists($path) : false,
            ];
        }
        return $result;
    }

    public static function saveBase64Image($base64, $prefix = 'result')
    {
        $dir = shopHardtryonPlugin::getPublicDataPath('results');
        if (!is_dir($dir)) {
            waFiles::create($dir);
        }

        $base64 = preg_replace('~^data:image/[^;]+;base64,~', '', (string) $base64);
        $bytes = base64_decode($base64, true);
        if ($bytes === false || $bytes === '') {
            throw new waException('Не удалось декодировать base64-изображение.');
        }

        $filename = sprintf('%s-%s-%s.png', $prefix, date('Ymd-His'), substr(md5(uniqid('', true)), 0, 6));
        $path = rtrim($dir, '/\\') . '/' . $filename;

        if (@file_put_contents($path, $bytes) === false) {
            throw new waException('Не удалось сохранить сгенерированное изображение.');
        }

        return [
            'path' => $path,
            'url' => rtrim(shopHardtryonPlugin::getPublicDataUrl('results'), '/\\') . '/' . $filename,
        ];
    }

    public static function makeScaledPublicCopiesByUrl(array $urls, $prefix, $max_width)
    {
        $result = [];
        foreach ($urls as $index => $url) {
            $scaled = self::makeScaledPublicCopyByUrl($url, $prefix . '-' . $index, $max_width);
            $result[] = $scaled ?: $url;
        }
        return $result;
    }

    public static function makeScaledPublicCopyByUrl($url, $prefix, $max_width)
    {
        if (!$url) {
            return '';
        }

        $path = self::urlToLocalPath($url);
        if ($path && is_file($path)) {
            return self::makeScaledPublicCopyByPath($path, $prefix, $max_width);
        }

        return $url;
    }

    public static function makeScaledPublicCopyByPath($path, $prefix, $max_width)
    {
        if (!$path || !is_file($path)) {
            return '';
        }

        try {
            $image = waImage::factory($path);
            $info = @getimagesize($path);
            if (!$info || empty($info[0]) || (int) $info[0] <= $max_width) {
                return self::publicUrlForInternalPath($path);
            }

            $dir = shopHardtryonPlugin::getPublicDataPath('scaled');
            if (!is_dir($dir)) {
                waFiles::create($dir);
            }

            $filename = sprintf('%s-%s.jpg', $prefix, md5($path . '|' . $max_width . '|' . @filemtime($path)));
            $dest = rtrim($dir, '/\\') . '/' . $filename;
            if (!is_file($dest)) {
                $image->resize($max_width, false);
                $image->save($dest, 90);
            }

            return rtrim(shopHardtryonPlugin::getPublicDataUrl('scaled'), '/\\') . '/' . $filename;
        } catch (Exception $e) {
            shopHardtryonPlugin::debugLog('scale_error', [
                'path' => $path,
                'message' => $e->getMessage(),
            ]);
            return self::publicUrlForInternalPath($path);
        }
    }

    public static function publicUrlForInternalPath($path)
    {
        $root = rtrim((string) wa()->getConfig()->getRootPath(), '/\\');
        $path = str_replace('\\', '/', (string) $path);
        $root = str_replace('\\', '/', $root);
        if (strpos($path, $root . '/') !== 0) {
            return '';
        }

        $relative = ltrim(substr($path, strlen($root)), '/');
        return rtrim(wa()->getRootUrl(true), '/') . '/' . $relative;
    }

    public static function urlToLocalPath($url)
    {
        $url = (string) $url;
        if ($url === '') {
            return '';
        }

        if (preg_match('~^https?://~i', $url)) {
            $root_url = rtrim(wa()->getRootUrl(true), '/');
            if (strpos($url, $root_url . '/') !== 0 && $url !== $root_url) {
                return '';
            }

            $relative = ltrim(substr($url, strlen($root_url)), '/');
            return rtrim((string) wa()->getConfig()->getRootPath(), '/\\') . '/' . $relative;
        }

        if (strpos($url, '/') === 0) {
            return rtrim((string) wa()->getConfig()->getRootPath(), '/\\') . $url;
        }

        return '';
    }

    public static function guessMimeByUrl($url)
    {
        $ext = strtolower(pathinfo(parse_url((string) $url, PHP_URL_PATH), PATHINFO_EXTENSION));
        switch ($ext) {
            case 'png':
                return 'image/png';
            case 'webp':
                return 'image/webp';
            default:
                return 'image/jpeg';
        }
    }
}
