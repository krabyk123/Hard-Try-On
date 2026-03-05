<?php
class shopHardtryonPlugin extends shopPlugin
{
    const PLUGIN_ID = 'hardtryon';

    public function frontendHead()
    {
        $settings = $this->getSettings();
        if (empty($settings['enabled'])) {
            return;
        }

        $this->addCss('css/tryon.css');
        $this->addJs('js/tryon.js');
    }

    public function frontendProduct($product)
    {
        $settings = $this->getSettings();
        if (empty($settings['enabled'])) {
            return [];
        }

        $product = $this->normalizeProduct($product);
        if (!$product || !$product->getId()) {
            return [];
        }

        $placement = $this->sanitizePlacement(ifset($settings['placement'], 'block'));
        $endpoint = wa()->getRouteUrl('shop/frontend/generate', ['plugin' => $this->id], false);
        $max_upload_mb = max(1, (int) ifset($settings['max_upload_mb'], 8));

        $view = wa()->getView();
        $view->assign([
            'product_id' => (int) $product->getId(),
            'endpoint' => $endpoint,
            'max_upload_mb' => $max_upload_mb,
        ]);

        $html = $view->fetch($this->path . '/templates/frontendWidget.html');

        return [
            $placement => $html,
        ];
    }

    public function routing($route = [])
    {
        return parent::routing($route);
    }

    public static function getPluginInstance()
    {
        return wa('shop')->getPlugin(self::PLUGIN_ID);
    }

    public static function getSettingsArray()
    {
        return self::getPluginInstance()->getSettings();
    }

    public static function getPublicDataPath($path = null)
    {
        return wa()->getDataPath('plugins/' . self::PLUGIN_ID . ($path ? '/' . ltrim($path, '/\\') : ''), true, 'shop', true);
    }

    public static function getPublicDataUrl($path = null, $absolute = true)
    {
        return wa()->getDataUrl('plugins/' . self::PLUGIN_ID . ($path ? '/' . ltrim($path, '/\\') : ''), true, 'shop', $absolute);
    }

    public static function getProtectedDataPath($path = null)
    {
        return wa()->getDataPath('plugins/' . self::PLUGIN_ID . ($path ? '/' . ltrim($path, '/\\') : ''), false, 'shop', true);
    }

    public static function cleanupOldFiles()
    {
        $settings = self::getSettingsArray();
        $ttl = max(1, (int) ifset($settings['store_results_hours'], 24)) * 3600;
        $now = time();

        foreach (['uploads', 'results', 'scaled'] as $folder) {
            $dir = self::getPublicDataPath($folder);
            if (!is_dir($dir)) {
                continue;
            }

            foreach (glob($dir . '/*') ?: [] as $file) {
                if (is_file($file) && ($now - (int) @filemtime($file)) > $ttl) {
                    @unlink($file);
                }
            }
        }

        $rate_dir = self::getProtectedDataPath('rate-limit');
        if (is_dir($rate_dir)) {
            foreach (glob($rate_dir . '/*.json') ?: [] as $file) {
                if (is_file($file) && ($now - (int) @filemtime($file)) > 2 * 86400) {
                    @unlink($file);
                }
            }
        }
    }

    public static function log($tag, $message, $force = false)
    {
        $settings = self::getSettingsArray();
        if (!$force && empty($settings['enable_logs'])) {
            return;
        }

        $dir = self::getProtectedDataPath();
        if (!is_dir($dir)) {
            waFiles::create($dir);
        }

        $line = sprintf(
            "[%s] [%s] %s\n",
            date('c'),
            $tag,
            is_scalar($message) ? $message : json_encode($message, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
        @file_put_contents($dir . '/tryon.log', $line, FILE_APPEND);
    }

    public static function debugLog($tag, $message)
    {
        $dir = self::getProtectedDataPath();
        if (!is_dir($dir)) {
            waFiles::create($dir);
        }

        $file = $dir . '/debug.log';
        if (file_exists($file) && filesize($file) > 204800) {
            $content = (string) @file_get_contents($file);
            @file_put_contents($file, substr($content, -102400));
        }

        $line = sprintf(
            "[%s] [%s]\n%s\n%s\n",
            date('c'),
            $tag,
            is_scalar($message) ? (string) $message : json_encode($message, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT),
            str_repeat('-', 60)
        );
        @file_put_contents($file, $line, FILE_APPEND);
    }

    public static function getLogTail($filename, $max_bytes = 30000)
    {
        $path = self::getProtectedDataPath() . '/' . ltrim($filename, '/\\');
        if (!is_file($path)) {
            return '';
        }

        $content = (string) @file_get_contents($path);
        if ($content === '') {
            return '';
        }

        return substr($content, -1 * abs((int) $max_bytes));
    }

    public static function clearDebugLog()
    {
        $path = self::getProtectedDataPath() . '/debug.log';
        if (is_file($path)) {
            @unlink($path);
        }
    }

    private function normalizeProduct($product)
    {
        if ($product instanceof shopProduct) {
            return $product;
        }
        if (is_array($product) && !empty($product['id'])) {
            return new shopProduct((int) $product['id'], true);
        }
        if (is_numeric($product)) {
            return new shopProduct((int) $product, true);
        }

        return null;
    }

    private function sanitizePlacement($placement)
    {
        $allowed = ['menu', 'cart', 'block', 'block_aux'];
        return in_array($placement, $allowed, true) ? $placement : 'block';
    }
}
