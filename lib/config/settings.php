<?php
return [
    'enabled' => [
        'title' => 'Включить блок примерки',
        'description' => 'Если выключено, блок на витрине не показывается.',
        'value' => 1,
        'control_type' => waHtmlControl::CHECKBOX,
    ],
    'placement' => [
        'title' => 'Место вывода в карточке товара',
        'description' => 'Соответствует зонам хука frontend_product.',
        'value' => 'block',
        'control_type' => waHtmlControl::SELECT,
        'options' => [
            ['value' => 'cart', 'title' => 'Возле кнопки «В корзину»'],
            ['value' => 'block', 'title' => 'Основной блок описания'],
            ['value' => 'block_aux', 'title' => 'Дополнительный боковой блок'],
            ['value' => 'menu', 'title' => 'Возле ссылок на доп. страницы'],
        ],
    ],
    'provider' => [
        'title' => 'Провайдер',
        'description' => 'Какой AI-сервис использовать.',
        'value' => 'higgsfield',
        'control_type' => waHtmlControl::SELECT,
        'options' => [
            ['value' => 'higgsfield', 'title' => 'Higgsfield'],
            ['value' => 'gemini', 'title' => 'Gemini'],
            ['value' => 'custom', 'title' => 'Custom endpoint'],
        ],
    ],
    'prompt_template' => [
        'title' => 'Шаблон промпта',
        'description' => 'Плейсхолдеры: {PRODUCT_TITLE}, {PRODUCT_URL}, {PRODUCT_IMAGES_COUNT}.',
        'value' => "You are editing a user's photo.\n\nGOAL: Make the person wear the garment from the provided product photos.\n\nSTRICT RULES:\n- Do NOT change the person's identity: keep face, hair, skin tone, body shape, pose, hands, and background unchanged.\n- Replace ONLY the target clothing item with the product garment.\n- Do not add/remove other items.\n- Keep realistic fabric folds, lighting, and shadows consistent with the original photo.\n- If the product has logos/prints, preserve them accurately.\n\nProduct name: {PRODUCT_TITLE}\nProduct page: {PRODUCT_URL}\nProduct images provided: {PRODUCT_IMAGES_COUNT}\n\nReturn a single photorealistic image.",
        'control_type' => waHtmlControl::TEXTAREA,
    ],
    'reference_pose_url' => [
        'title' => 'URL референса позы',
        'description' => 'Необязательно. Можно указать внешнюю ссылку или путь к публичному файлу вашей установки.',
        'value' => '',
        'control_type' => waHtmlControl::INPUT,
    ],
    'max_upload_mb' => [
        'title' => 'Максимальный размер файла, MB',
        'value' => 8,
        'control_type' => waHtmlControl::INPUT,
    ],
    'max_daily_requests_per_ip' => [
        'title' => 'Лимит запросов в сутки на IP',
        'description' => '0 — без ограничения.',
        'value' => 30,
        'control_type' => waHtmlControl::INPUT,
    ],
    'store_results_hours' => [
        'title' => 'Сколько хранить результаты, часов',
        'value' => 24,
        'control_type' => waHtmlControl::INPUT,
    ],
    'enable_logs' => [
        'title' => 'Писать основной лог',
        'description' => 'tryon.log сохраняется в защищённой директории wa-data.',
        'value' => 1,
        'control_type' => waHtmlControl::CHECKBOX,
    ],
    'higgsfield_key_id' => [
        'title' => 'Higgsfield API key ID',
        'value' => '',
        'control_type' => waHtmlControl::INPUT,
    ],
    'higgsfield_key_secret' => [
        'title' => 'Higgsfield API key secret',
        'value' => '',
        'control_type' => waHtmlControl::PASSWORD,
    ],
    'higgsfield_model_id' => [
        'title' => 'Higgsfield model_id',
        'value' => 'bytedance/seedream/v4/edit',
        'control_type' => waHtmlControl::INPUT,
    ],
    'higgsfield_timeout' => [
        'title' => 'Higgsfield timeout, сек.',
        'value' => 120,
        'control_type' => waHtmlControl::INPUT,
    ],
    'higgsfield_payload_template' => [
        'title' => 'Higgsfield payload template (JSON)',
        'description' => 'Плейсхолдеры: {{prompt}}, {{user_image_url}}, {{reference_pose_url}}, {{product_image_url_1}}, {{product_image_url_2}}, {{product_image_urls_json}}.',
        'value' => "{\n  \"prompt\": \"{{prompt}}\",\n  \"image_url\": \"{{user_image_url}}\",\n  \"reference_image_url\": \"{{product_image_url_1}}\"\n}",
        'control_type' => waHtmlControl::TEXTAREA,
    ],
    'gemini_api_key' => [
        'title' => 'Gemini API key',
        'value' => '',
        'control_type' => waHtmlControl::PASSWORD,
    ],
    'gemini_model' => [
        'title' => 'Gemini model',
        'value' => 'gemini-2.5-flash-image-preview',
        'control_type' => waHtmlControl::INPUT,
    ],
    'gemini_timeout' => [
        'title' => 'Gemini timeout, сек.',
        'value' => 120,
        'control_type' => waHtmlControl::INPUT,
    ],
    'gemini_proxy_enabled' => [
        'title' => 'Использовать SOCKS5-прокси для Gemini',
        'value' => 0,
        'control_type' => waHtmlControl::CHECKBOX,
    ],
    'gemini_proxy_host' => [
        'title' => 'Gemini proxy host',
        'value' => '',
        'control_type' => waHtmlControl::INPUT,
    ],
    'gemini_proxy_port' => [
        'title' => 'Gemini proxy port',
        'value' => '',
        'control_type' => waHtmlControl::INPUT,
    ],
    'gemini_proxy_user' => [
        'title' => 'Gemini proxy user',
        'value' => '',
        'control_type' => waHtmlControl::INPUT,
    ],
    'gemini_proxy_pass' => [
        'title' => 'Gemini proxy password',
        'value' => '',
        'control_type' => waHtmlControl::PASSWORD,
    ],
    'custom_endpoint' => [
        'title' => 'Custom endpoint',
        'description' => 'Ожидается JSON-ответ с image_base64 или image_url.',
        'value' => '',
        'control_type' => waHtmlControl::INPUT,
    ],
];
