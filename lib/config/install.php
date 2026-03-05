<?php
$plugin_id = 'hardtryon';
$paths = [
    wa()->getDataPath('plugins/' . $plugin_id . '/uploads', true, 'shop', true),
    wa()->getDataPath('plugins/' . $plugin_id . '/results', true, 'shop', true),
    wa()->getDataPath('plugins/' . $plugin_id . '/scaled', true, 'shop', true),
    wa()->getDataPath('plugins/' . $plugin_id, false, 'shop', true),
    wa()->getDataPath('plugins/' . $plugin_id . '/rate-limit', false, 'shop', true),
];
foreach ($paths as $path) {
    if (!is_dir($path)) {
        waFiles::create($path);
    }
}
