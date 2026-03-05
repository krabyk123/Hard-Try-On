<?php
class shopHardtryonPluginFrontendGenerateController extends waJsonController
{
    public function execute()
    {
        try {
            $product_id = waRequest::post('product_id', 0, waRequest::TYPE_INT);
            if ($product_id <= 0) {
                throw new waException('Не передан ID товара.');
            }

            $consent = waRequest::post('consent', '', waRequest::TYPE_STRING_TRIM);
            if ($consent !== '1') {
                throw new waException('Нужно согласие на обработку изображения.');
            }

            $settings = shopHardtryonPlugin::getSettingsArray();
            if (empty($settings['enabled'])) {
                throw new waException('Плагин выключен в настройках.');
            }

            $limit = max(0, (int) ifset($settings['max_daily_requests_per_ip'], 30));
            if ($limit > 0) {
                $this->checkRateLimit($limit);
            }

            $file = waRequest::file('user_image');
            if (!$file || !$file->uploaded()) {
                throw new waException('Файл не загружен.');
            }

            $ext = strtolower((string) $file->extension);
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                throw new waException('Допустимы только JPEG, PNG или WebP.');
            }

            $max_mb = max(1, (int) ifset($settings['max_upload_mb'], 8));
            if ((int) $file->size > $max_mb * 1024 * 1024) {
                throw new waException('Файл слишком большой. Максимум ' . $max_mb . ' MB.');
            }

            try {
                $file->waImage();
            } catch (Exception $e) {
                throw new waException('Загруженный файл не является корректным изображением.');
            }

            $user = $this->saveUploadedFile($file);
            $product = new shopProduct($product_id, true);
            if (!$product->getId()) {
                throw new waException('Товар не найден.');
            }

            $product_images = shopHardtryonPluginHelper::getProductImages($product, 2);
            if (!$product_images) {
                shopHardtryonPlugin::debugLog('image_paths_ERROR', [
                    'product_id' => $product_id,
                    'message' => 'No product images found',
                ]);
                throw new waException('У товара не найдены изображения для отправки в AI.');
            }

            $reference_pose_url = trim((string) ifset($settings['reference_pose_url'], ''));
            $prompt = shopHardtryonPluginHelper::buildPrompt($product, $settings);

            $scaled_user_url = shopHardtryonPluginHelper::makeScaledPublicCopyByPath($user['path'], 'user', 1400);
            $scaled_product_urls = shopHardtryonPluginHelper::makeScaledPublicCopiesByUrl($product_images, 'product', 1400);
            $scaled_pose_url = $reference_pose_url ? shopHardtryonPluginHelper::makeScaledPublicCopyByUrl($reference_pose_url, 'pose', 1400) : '';

            $provider = (string) ifset($settings['provider'], 'higgsfield');

            shopHardtryonPlugin::debugLog('image_paths', [
                'product_id' => $product_id,
                'product_title' => (string) $product['name'],
                'user_image_path' => $user['path'],
                'user_image_url' => $user['url'],
                'user_image_scaled_url' => $scaled_user_url,
                'product_images_original' => $product_images,
                'product_images_scaled' => $scaled_product_urls,
                'reference_pose_url' => $reference_pose_url,
                'reference_pose_scaled_url' => $scaled_pose_url,
            ]);

            shopHardtryonPlugin::log('request', [
                'provider' => $provider,
                'product_id' => $product_id,
                'user_image_url' => $scaled_user_url ?: $user['url'],
                'product_images' => $scaled_product_urls ?: $product_images,
                'reference_pose_url' => $scaled_pose_url ?: $reference_pose_url,
            ]);

            switch ($provider) {
                case 'gemini':
                    $image_b64 = shopHardtryonPluginProviderGemini::generate(
                        $settings,
                        $prompt,
                        $scaled_user_url ?: $user['url'],
                        $scaled_product_urls ?: $product_images,
                        $scaled_pose_url ?: $reference_pose_url
                    );
                    break;
                case 'custom':
                    $image_b64 = shopHardtryonPluginProviderCustom::generate(
                        $settings,
                        $prompt,
                        $scaled_user_url ?: $user['url'],
                        $scaled_product_urls ?: $product_images,
                        $scaled_pose_url ?: $reference_pose_url,
                        $product_id
                    );
                    break;
                default:
                    $image_b64 = shopHardtryonPluginProviderHiggsfield::generate(
                        $settings,
                        $prompt,
                        $scaled_user_url ?: $user['url'],
                        $scaled_product_urls ?: $product_images,
                        $scaled_pose_url ?: $reference_pose_url
                    );
            }

            if (!$image_b64) {
                throw new waException('AI-сервис не вернул изображение.');
            }

            $saved = shopHardtryonPluginHelper::saveBase64Image($image_b64, 'result');
            shopHardtryonPlugin::log('success', [
                'product_id' => $product_id,
                'result_url' => $saved['url'],
            ]);

            $this->response = [
                'result_url' => $saved['url'],
            ];
        } catch (Exception $e) {
            shopHardtryonPlugin::log('error', $e->getMessage(), true);
            $this->setError($e->getMessage());
        }
    }

    private function saveUploadedFile(waRequestFile $file)
    {
        $dir = shopHardtryonPlugin::getPublicDataPath('uploads');
        if (!is_dir($dir)) {
            waFiles::create($dir);
        }

        $ext = strtolower((string) $file->extension);
        $filename = 'user-' . date('Ymd-His') . '-' . substr(md5(uniqid('', true)), 0, 8) . '.' . $ext;
        $file->moveTo($dir, $filename);

        return [
            'path' => rtrim($dir, '/\\') . '/' . $filename,
            'url' => rtrim(shopHardtryonPlugin::getPublicDataUrl('uploads'), '/\\') . '/' . $filename,
            'mime' => (string) $file->type,
        ];
    }

    private function checkRateLimit($limit)
    {
        $ip = (string) waRequest::getIp();
        $date = date('Y-m-d');
        $dir = shopHardtryonPlugin::getProtectedDataPath('rate-limit');
        if (!is_dir($dir)) {
            waFiles::create($dir);
        }

        $file = $dir . '/' . md5($ip . '|' . $date) . '.json';
        $count = 0;
        if (is_file($file)) {
            $json = json_decode((string) @file_get_contents($file), true);
            $count = (int) ifset($json['count'], 0);
        }

        if ($count >= $limit) {
            throw new waException('Превышен дневной лимит запросов.');
        }

        @file_put_contents($file, json_encode([
            'count' => $count + 1,
            'ip' => $ip,
            'date' => $date,
        ]));
    }
}
