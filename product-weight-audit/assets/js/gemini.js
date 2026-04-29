(function ($) {
    'use strict';

    if (!window.pwaGemini) return;

    var currentProductId = null;
    var bulkQueue        = [];
    var bulkIndex        = 0;
    var bulkTotal        = 0;
    var bulkRunning      = false;

    var RETRY_WAIT    = 65;
    var BULK_DELAY    = 5000;
    var retryTimer    = null;

    // ---- Single product: open modal ----

    $(document).on('click', '.pwa-btn-ai', function () {
        currentProductId = $(this).data('id');
        openModal(
            $(this).data('name'),
            $(this).data('img'),
            $(this).data('sku')
        );
        doAnalyzeSingle(currentProductId);
    });

    function doAnalyzeSingle(id) {
        callAnalyze(id, function (ok, data, canRetry) {
            if (ok) {
                showResult(data);
            } else if (canRetry) {
                showRetryCountdown(id);
            } else {
                showError(data);
            }
        });
    }

    function showRetryCountdown(id) {
        var remaining = RETRY_WAIT;
        showError('Límite de solicitudes alcanzado. Reintentando en ' + remaining + 's…');
        $('#pwa-gemini-loading').hide();
        retryTimer = setInterval(function () {
            remaining--;
            if (remaining <= 0) {
                clearInterval(retryTimer);
                showLoadingState();
                doAnalyzeSingle(id);
            } else {
                $('#pwa-error-msg').text('Límite de solicitudes alcanzado. Reintentando en ' + remaining + 's…');
            }
        }, 1000);
    }

    function openModal(name, img, sku) {
        $('#pwa-modal-name').text(name);
        $('#pwa-modal-sku').text(sku && sku !== '—' ? 'SKU: ' + sku : '');
        var $img = $('#pwa-modal-img');
        img ? $img.attr('src', img).show() : $img.hide();
        showLoadingState();
        $('#pwa-gemini-modal').fadeIn(150);
    }

    function showLoadingState() {
        $('#pwa-gemini-loading').show();
        $('#pwa-gemini-result').hide();
        $('#pwa-gemini-error').hide();
        $('#pwa-btn-save').hide();
    }

    function showResult(data) {
        $('#pwa-gemini-loading').hide();
        $('#pwa-gemini-error').hide();
        $('#pwa-gemini-result').show();
        $('#pwa-btn-save').show();

        var confMap = {
            high:   { label: 'Confianza alta',   cls: 'pwa-conf--high'   },
            medium: { label: 'Confianza media',  cls: 'pwa-conf--medium' },
            low:    { label: 'Confianza baja',   cls: 'pwa-conf--low'    },
        };
        var c = confMap[data.confidence] || { label: data.confidence, cls: '' };
        $('#pwa-confidence-badge').attr('class', 'pwa-confidence ' + c.cls).text(c.label);
        $('#pwa-reasoning').text(data.reasoning);

        $('#pwa-field-weight').val(data.weight);
        $('#pwa-field-length').val(data.length);
        $('#pwa-field-width').val(data.width);
        $('#pwa-field-height').val(data.height);
    }

    function showError(msg) {
        $('#pwa-gemini-loading').hide();
        $('#pwa-gemini-result').hide();
        $('#pwa-gemini-error').show();
        $('#pwa-error-msg').text(msg || 'Error desconocido.');
        $('#pwa-btn-save').hide();
    }

    function closeModal() {
        if (retryTimer) { clearInterval(retryTimer); retryTimer = null; }
        $('#pwa-gemini-modal').fadeOut(150);
        currentProductId = null;
    }

    $(document).on('click', '#pwa-modal-close, #pwa-btn-cancel', closeModal);
    $(document).on('click', '#pwa-modal-overlay', closeModal);

    // ---- Save single ----

    $('#pwa-btn-save').on('click', function () {
        if (!currentProductId) return;
        var $btn = $(this).prop('disabled', true).text('Guardando...');
        var fields = getFields();
        callSave(currentProductId, fields, function (ok) {
            if (ok) {
                updateRow(currentProductId, fields);
                closeModal();
            } else {
                showError('No se pudo guardar. Intente de nuevo.');
            }
            $btn.prop('disabled', false).text('Guardar datos');
        });
    });

    function getFields() {
        return {
            weight: $('#pwa-field-weight').val(),
            length: $('#pwa-field-length').val(),
            width:  $('#pwa-field-width').val(),
            height: $('#pwa-field-height').val(),
        };
    }

    // ---- AJAX helpers ----

    function callAnalyze(id, cb) {
        $.post(pwaGemini.ajaxUrl, {
            action:     'pwa_gemini_analyze',
            nonce:      pwaGemini.nonce,
            product_id: id,
        }, function (res) {
            if (res.success) {
                cb(true, res.data, false);
            } else {
                var msg   = (res.data && res.data.message) ? res.data.message : (res.data || 'Error desconocido.');
                var retry = !!(res.data && res.data.retry);
                cb(false, msg, retry);
            }
        }).fail(function () {
            cb(false, 'Error de conexión.', false);
        });
    }

    function callSave(id, data, cb) {
        $.post(pwaGemini.ajaxUrl, {
            action:     'pwa_gemini_save',
            nonce:      pwaGemini.nonce,
            product_id: id,
            weight:     data.weight,
            length:     data.length,
            width:      data.width,
            height:     data.height,
        }, function (res) {
            cb(res.success);
        }).fail(function () {
            cb(false);
        });
    }

    function updateRow(id, data) {
        var $row = $('[data-product-id="' + id + '"]');
        if (!$row.length) return;

        $row.removeClass('pwa-row--empty pwa-row--partial').addClass('pwa-row--complete');
        $row.find('td:first').css('border-left-color', '#46b450');
        $row.find('.pwa-badge')
            .removeClass('pwa-badge--empty pwa-badge--partial')
            .addClass('pwa-badge--complete')
            .text('Completo');

        var $tds = $row.find('td');
        $($tds[3]).removeClass('pwa-missing').addClass('pwa-has').text(data.weight);
        $($tds[4]).removeClass('pwa-missing').addClass('pwa-has').text(data.height);
        $($tds[5]).removeClass('pwa-missing').addClass('pwa-has').text(data.width);
        $($tds[6]).removeClass('pwa-missing').addClass('pwa-has').text(data.length);

        $row.find('.pwa-btn-ai').remove();
    }

    // ---- Bulk analyze ----

    function fetchAndStartBulk(filter, label, $btn, originalText) {
        $.post(pwaGemini.ajaxUrl, {
            action: 'pwa_gemini_get_incomplete',
            nonce:  pwaGemini.nonce,
            filter: filter,
        }, function (res) {
            $btn.prop('disabled', false).text(originalText);
            if (!res.success || !res.data.ids.length) {
                alert('No hay productos en "' + label + '" para analizar.');
                return;
            }
            if (confirm('Se analizarán ' + res.data.ids.length + ' productos (' + label + ') con Gemini AI.\n\nEste proceso puede tardar varios minutos. ¿Continuar?')) {
                startBulk(res.data.ids, label);
            }
        }).fail(function () {
            $btn.prop('disabled', false).text(originalText);
            alert('Error de conexión.');
        });
    }

    $('#pwa-btn-bulk').on('click', function () {
        var $btn = $(this).prop('disabled', true).text('Cargando lista...');
        fetchAndStartBulk('incomplete', 'Incompletos', $btn, 'Analizar incompletos con IA');
    });

    $(document).on('click', '.pwa-card-analyze-btn', function () {
        var $btn   = $(this).prop('disabled', true).text('Cargando...');
        var filter = $(this).data('filter');
        var label  = $(this).data('label');
        fetchAndStartBulk(filter, label, $btn, 'Generar todos');
    });

    function startBulk(ids, label) {
        bulkQueue   = ids;
        bulkTotal   = ids.length;
        bulkIndex   = 0;
        bulkRunning = true;

        $('#pwa-bulk-log').empty();
        $('#pwa-bulk-fill').css('width', '0%');
        $('#pwa-bulk-stop').text('Detener').removeClass('button-primary').addClass('button-secondary');
        $('strong', '#pwa-bulk-panel__title').text('Análisis masivo con Gemini AI');
        $('#pwa-bulk-category').text(label ? '— ' + label : '');
        updateBulkCounter();
        $('#pwa-bulk-panel').slideDown(200);
        processNext();
    }

    function processNext() {
        if (!bulkRunning || bulkIndex >= bulkTotal) {
            if (bulkIndex >= bulkTotal) {
                $('#pwa-bulk-status').text('Completado');
                $('#pwa-bulk-stop').text('Cerrar').removeClass('button-secondary').addClass('button-primary');
            }
            return;
        }

        var id = bulkQueue[bulkIndex];
        appendLog(id, 'analyzing', 'Analizando...');

        callAnalyze(id, function (ok, data, canRetry) {
            if (!bulkRunning) return;
            if (ok) {
                callSave(id, data, function (saved) {
                    if (!bulkRunning) return;
                    if (saved) {
                        var detail = data.weight + ' ' + pwaGemini.unitWeight + ',  '
                            + data.length + '×' + data.width + '×' + data.height + ' ' + pwaGemini.unitDim;
                        appendLog(id, 'saved', detail);
                        updateRow(id, data);
                    } else {
                        appendLog(id, 'error', 'Error al guardar');
                    }
                    nextBulkStep();
                });
            } else if (canRetry) {
                bulkWaitAndRetry(id);
            } else {
                appendLog(id, 'error', data);
                nextBulkStep();
            }
        });
    }

    function bulkWaitAndRetry(id) {
        var remaining = RETRY_WAIT;
        appendLog(id, 'analyzing', 'Límite de API. Reintentando en ' + remaining + 's…');
        var timer = setInterval(function () {
            if (!bulkRunning) { clearInterval(timer); return; }
            remaining--;
            if (remaining <= 0) {
                clearInterval(timer);
                appendLog(id, 'analyzing', 'Reintentando…');
                processNext();
            } else {
                appendLog(id, 'analyzing', 'Límite de API. Reintentando en ' + remaining + 's…');
                $('#pwa-bulk-status').text('Esperando: ' + remaining + 's…');
            }
        }, 1000);
    }

    function nextBulkStep() {
        bulkIndex++;
        updateBulkCounter();
        setTimeout(processNext, BULK_DELAY);
    }

    function updateBulkCounter() {
        var pct = bulkTotal > 0 ? Math.round((bulkIndex / bulkTotal) * 100) : 0;
        $('#pwa-bulk-fill').css('width', pct + '%');
        $('#pwa-bulk-counter').text(bulkIndex + ' / ' + bulkTotal);
        if (bulkIndex < bulkTotal) {
            $('#pwa-bulk-status').text('Procesando...');
        }
    }

    function appendLog(id, status, detail) {
        var name = $('[data-product-id="' + id + '"]').find('.col-name strong').text() || ('ID ' + id);
        var icons = { analyzing: '&#8987;', saved: '&#10003;', error: '&#10007;' };
        var icon  = icons[status] || '';
        var html  = icon + ' <strong>' + $('<span>').text(name).html() + '</strong>'
            + (detail ? ' &mdash; ' + $('<span>').text(detail).html() : '');

        var $entry = $('[data-log-id="' + id + '"]');
        if ($entry.length) {
            $entry.attr('class', 'pwa-log-entry pwa-log--' + status).html(html);
        } else {
            $('#pwa-bulk-log').append(
                $('<div>').attr({ 'class': 'pwa-log-entry pwa-log--' + status, 'data-log-id': id }).html(html)
            );
        }
        var $log = $('#pwa-bulk-log');
        $log.scrollTop($log[0].scrollHeight);
    }

    $('#pwa-bulk-stop').on('click', function () {
        if (bulkRunning) {
            if (!confirm('¿Detener el análisis en curso?')) return;
            bulkRunning = false;
            $(this).text('Cerrar').removeClass('button-secondary').addClass('button-primary');
            $('#pwa-bulk-status').text('Detenido');
        } else {
            $('#pwa-bulk-panel').slideUp(200);
        }
    });

})(jQuery);
