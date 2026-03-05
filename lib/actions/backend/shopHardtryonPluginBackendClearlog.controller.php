<?php
class shopHardtryonPluginBackendClearlogController extends waJsonController
{
    public function execute()
    {
        try {
            shopHardtryonPlugin::clearDebugLog();
            $this->response = ['cleared' => true];
        } catch (Exception $e) {
            $this->setError($e->getMessage());
        }
    }
}
