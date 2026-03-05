<?php
class shopHardtryonPluginSettingsAction extends waViewAction
{
    public function execute()
    {
        /** @var shopHardtryonPlugin $plugin */
        $plugin = wa('shop')->getPlugin('hardtryon');
        $settings = array_merge(require $plugin->path . '/lib/config/settings.php', []);
        $values = $plugin->getSettings();

        $normalized = [];
        foreach ($settings as $key => $field) {
            $normalized[$key] = array_merge($field, [
                'current' => ifset($values[$key], ifset($field['value'], '')),
            ]);
        }

        $this->view->assign([
            'plugin_id' => 'hardtryon',
            'settings_def' => $normalized,
            'tryon_log_tail' => shopHardtryonPlugin::getLogTail('tryon.log'),
            'debug_log_tail' => shopHardtryonPlugin::getLogTail('debug.log'),
            'debug_log_exists' => is_file(shopHardtryonPlugin::getProtectedDataPath() . '/debug.log'),
            'diagnostic_url' => '?plugin=hardtryon&module=backend&action=diagnostic',
            'clear_log_url' => '?plugin=hardtryon&module=backend&action=clearlog',
        ]);
    }
}
