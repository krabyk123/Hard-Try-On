(function () {
    function query(node, selector) {
        return node.querySelector(selector);
    }

    function getCookie(name) {
        var matches = document.cookie.match(new RegExp('(?:^|; )' + name.replace(/([.$?*|{}()\[\]\\/+^])/g, '\\$1') + '=([^;]*)'));
        return matches ? decodeURIComponent(matches[1]) : '';
    }

    function setStatus(root, message, isError) {
        var status = query(root, '.hard-tryon__status');
        if (!status) return;
        status.textContent = message || '';
        status.classList.toggle('is-error', !!isError);
    }

    function setLoading(root, value) {
        root.classList.toggle('is-loading', !!value);
        var button = query(root, '.hard-tryon__btn');
        if (button) {
            button.disabled = !!value || !button.dataset.ready;
        }
    }

    function readFile(file) {
        return new Promise(function (resolve, reject) {
            var reader = new FileReader();
            reader.onload = function () { resolve(reader.result); };
            reader.onerror = function () { reject(reader.error); };
            reader.readAsDataURL(file);
        });
    }

    function initWidget(root) {
        var input = query(root, '.hard-tryon__file');
        var preview = query(root, '.hard-tryon__preview');
        var previewWrap = query(root, '.hard-tryon__previewWrap');
        var button = query(root, '.hard-tryon__btn');
        var consent = query(root, '.hard-tryon__consentChk');
        var result = query(root, '.hard-tryon__result');
        var placeholder = query(root, '.hard-tryon__resultPlaceholder');
        var download = query(root, '.hard-tryon__download');
        var endpoint = root.getAttribute('data-endpoint') || '';
        var productId = root.getAttribute('data-product-id') || '';
        var maxUploadMb = parseInt(root.getAttribute('data-max-upload-mb') || '8', 10);
        var selectedFile = null;

        function refreshButtonState() {
            var ready = !!selectedFile && consent && consent.checked;
            button.disabled = !ready;
            button.dataset.ready = ready ? '1' : '';
        }

        if (consent) {
            consent.addEventListener('change', refreshButtonState);
        }

        if (input) {
            input.addEventListener('change', async function () {
                var file = input.files && input.files[0] ? input.files[0] : null;
                if (!file) {
                    return;
                }

                if (file.size > maxUploadMb * 1024 * 1024) {
                    setStatus(root, 'Файл слишком большой. Максимум ' + maxUploadMb + ' MB.', true);
                    input.value = '';
                    selectedFile = null;
                    refreshButtonState();
                    return;
                }

                selectedFile = file;
                setStatus(root, '');
                refreshButtonState();

                try {
                    preview.src = await readFile(file);
                    previewWrap.style.display = 'block';
                } catch (error) {
                    setStatus(root, 'Не удалось прочитать файл.', true);
                }
            });
        }

        if (button) {
            button.addEventListener('click', async function () {
                if (!selectedFile) {
                    return;
                }
                if (consent && !consent.checked) {
                    setStatus(root, 'Нужно согласие на обработку изображения.', true);
                    return;
                }

                setLoading(root, true);
                setStatus(root, 'Загружаем и генерируем...');

                var formData = new FormData();
                formData.append('product_id', productId);
                formData.append('user_image', selectedFile);
                formData.append('consent', '1');
                formData.append('_csrf', getCookie('_csrf'));

                try {
                    var response = await fetch(endpoint, {
                        method: 'POST',
                        body: formData,
                        credentials: 'same-origin',
                        headers: {'X-Requested-With': 'XMLHttpRequest'}
                    });
                    var json = await response.json();
                    if (!json || json.status !== 'ok') {
                        var message = 'Ошибка. Попробуйте ещё раз.';
                        if (json && json.errors && json.errors[0]) {
                            message = Array.isArray(json.errors[0]) ? json.errors[0][0] : json.errors[0];
                        }
                        setStatus(root, message, true);
                        return;
                    }

                    var resultUrl = json.data && json.data.result_url ? json.data.result_url : '';
                    if (!resultUrl) {
                        setStatus(root, 'Ответ без изображения.', true);
                        return;
                    }

                    result.src = resultUrl;
                    result.style.display = 'block';
                    if (placeholder) {
                        placeholder.style.display = 'none';
                    }
                    if (download) {
                        download.href = resultUrl;
                        download.style.display = 'inline-flex';
                    }
                    setStatus(root, 'Готово!');
                } catch (error) {
                    setStatus(root, 'Ошибка. Попробуйте ещё раз. ' + (error && error.message ? error.message : ''), true);
                } finally {
                    setLoading(root, false);
                }
            });
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.hard-tryon').forEach(initWidget);
    });
})();
