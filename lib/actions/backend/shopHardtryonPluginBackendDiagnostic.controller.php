<?php
class shopHardtryonPluginBackendDiagnosticController extends waJsonController
{
    public function execute()
    {
        try {
            $product_id = waRequest::post('product_id', 0, waRequest::TYPE_INT);
            if ($product_id <= 0) {
                throw new waException('Не передан ID товара.');
            }

            $product = new shopProduct($product_id, true);
            if (!$product->getId()) {
                throw new waException('Товар не найден.');
            }

            $images = shopHardtryonPluginHelper::diagnoseProductImages($product, 10);
            $this->response = [
                'product_id' => $product_id,
                'product_title' => (string) $product['name'],
                'images' => $images,
            ];
        } catch (Exception $e) {
            $this->setError($e->getMessage());
        }
    }
}
