jQuery(document).ready(function($) {
    'use strict';

    // Tab switching functionality
    $('.tab-button').on('click', function() {
        const tab = $(this).data('tab');
        $('.tab-button').removeClass('active');
        $('.tab-content').removeClass('active');
        $(this).addClass('active');
        $('#tab-' + tab).addClass('active');
        
        // Save active tab to localStorage
        localStorage.setItem('apm_active_tab', tab);
    });

    // Restore active tab from localStorage
    const savedTab = localStorage.getItem('apm_active_tab');
    if (savedTab) {
        $(`.tab-button[data-tab="${savedTab}"]`).click();
    }

    // Auth type handler for API form
    $('#auth-type').on('change', function() {
        const type = $(this).val();
        const $row = $('#auth-data-row');
        const $help = $('#auth-help');

        if (type == 'none') {
            $row.hide();
        } else {
            $row.show();
            if (type == 'bearer') {
                $help.text(apmAjax.bearer_help || 'Enter your bearer token');
                $('#auth-data').attr('placeholder', 'your-bearer-token');
            } else if (type == 'basic') {
                $help.text(apmAjax.basic_help || 'Enter username:password');
                $('#auth-data').attr('placeholder', 'username:password');
            } else if (type == 'api_key') {
                $help.text(apmAjax.api_key_help || 'Enter your API key');
                $('#auth-data').attr('placeholder', 'your-api-key');
            }
        }
    }).trigger('change');

    // Pagination type handler
    $('#pagination-type').on('change', function() {
        const type = $(this).val();
        const $configRow = $('#pagination-config-row');
        const $paramHelp = $('#pagination-param-help');

        if (type == 'none') {
            $configRow.hide();
        } else {
            $configRow.show();
            
            switch (type) {
                case 'page_parameter':
                    $paramHelp.text(apmAjax.page_param_help || 'Parameter name for page number (e.g., "page")');
                    $('#pagination-param').attr('placeholder', 'page');
                    break;
                case 'offset_parameter':
                    $paramHelp.text(apmAjax.offset_param_help || 'Parameter name for offset (e.g., "offset")');
                    $('#pagination-param').attr('placeholder', 'offset');
                    break;
                case 'cursor_based':
                    $paramHelp.text(apmAjax.cursor_param_help || 'Parameter name for cursor (e.g., "cursor")');
                    $('#pagination-param').attr('placeholder', 'cursor');
                    break;
                case 'link_header':
                    $paramHelp.text(apmAjax.link_header_help || 'Uses Link headers for pagination');
                    break;
            }
        }
    }).trigger('change');

    // Form submissions
    $('#apm-api-form').on('submit', function(e) {
        e.preventDefault();
        saveApiForm();
    });

    $('#mapping-form').on('submit', function(e) {
        e.preventDefault();
        saveMappingForm();
    });

    $('#settings-form').on('submit', function(e) {
        e.preventDefault();
        saveSettingsForm();
    });

    // Button handlers
    $('#test-api').on('click', testApi);
    $('#test-vision-api').on('click', testVisionApi);
    $('#start-bulk-categorize').on('click', startBulkCategorization);
    $('#bulk-fetch-all').on('click', bulkFetchAll);
    $('#bulk-categorize-all').on('click', bulkCategorizeAll);
    $('#import-sample-data').on('click', importSampleData);
    $('#clear-logs').on('click', clearLogs);
    $('#optimize-tables').on('click', optimizeTables);

    // Dynamic button handlers
    $(document).on('click', '.edit-api', editApi);
    $(document).on('click', '.delete-api', deleteApi);
    $(document).on('click', '.fetch-api', fetchApi);
    $(document).on('click', '.toggle-api', toggleApi);
    $(document).on('click', '.delete-mapping', deleteMapping);
    $(document).on('click', '.quick-map', quickMap);
    $(document).on('click', '.view-log-details', viewLogDetails);
    $(document).on('click', '.apm-categorize-product', categorizeProduct);
    $(document).on('click', '.apm-publish-product', publishProduct);
    $(document).on('click', '.apm-modal-close', closeModal);

    // Initialize Select2 for category dropdowns
    $('.apm-category-select').select2({
        width: '100%',
        placeholder: apmAjax.select_category || 'Select a category',
        allowClear: true
    });

    // Initialize tooltips
    $('.apm-tooltip').each(function() {
        $(this).attr('title', $(this).data('tooltip'));
    });

    // Functions
    function saveApiForm() {
        const $form = $('#apm-api-form');
        const $submitBtn = $form.find('button[type="submit"]');
        
        const formData = {
            action: 'apm_save_api',
            nonce: apmAjax.nonce,
            id: $('#api-id').val(),
            name: $('#api-name').val(),
            url: $('#api-url').val(),
            method: $('#api-method').val(),
            headers: $('#api-headers').val(),
            auth_type: $('#auth-type').val(),
            auth_data: $('#auth-data').val(),
            product_path: $('#product-path').val(),
            mapping: $('#field-mapping').val(),
            publish_posts: $('#publish-posts').is(':checked') ? 1 : 0,
            auto_categorize: $('#auto-categorize').is(':checked') ? 1 : 0,
            pagination_type: $('#pagination-type').val(),
            pagination_param: $('#pagination-param').val(),
            page_size: $('#page-size').val(),
            max_pages: $('#max-pages').val()
        };

        $submitBtn.prop('disabled', true).html('<span class="apm-loading"></span> ' + apmAjax.saving_text);

        $.post(apmAjax.ajaxurl, formData, function(response) {
            if (response.success) {
                showNotice(response.data || apmAjax.api_saved, 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                showNotice(apmAjax.error_text + ': ' + response.data, 'error');
                $submitBtn.prop('disabled', false).text(apmAjax.save_text);
            }
        }).fail(function() {
            showNotice(apmAjax.request_failed, 'error');
            $submitBtn.prop('disabled', false).text(apmAjax.save_text);
        });
    }

    function testApi() {
        const url = $('#api-url').val();
        if (!url) {
            showNotice(apmAjax.enter_url_first, 'error');
            return;
        }

        const $btn = $(this);
        const $output = $('#test-output');
        const $results = $('#test-results');
        
        $btn.html('<span class="apm-loading"></span> ' + apmAjax.testing_text).prop('disabled', true);
        $output.html('');
        $results.hide();

        const requestData = {
            action: 'apm_test_api',
            nonce: apmAjax.nonce,
            url: url,
            method: $('#api-method').val(),
            headers: $('#api-headers').val(),
            auth_type: $('#auth-type').val(),
            auth_data: $('#auth-data').val()
        };

        $.post(apmAjax.ajaxurl, requestData, function(response) {
            $btn.text(apmAjax.test_api_text).prop('disabled', false);

            if (response.success) {
                let output = '<div class="apm-notice success">';
                output += '<strong>✓ ' + apmAjax.success_text + '</strong><br>';
                output += apmAjax.status_text + ': ' + response.data.status + '<br>';
                
                if (response.data.is_json) {
                    output += '<pre class="json-viewer">' + syntaxHighlight(response.data.body) + '</pre>';
                } else {
                    output += '<pre>' + escapeHtml(response.data.body) + '</pre>';
                }
                
                output += '</div>';
                $output.html(output);
            } else {
                $output.html('<div class="apm-notice error"><strong>✗ ' + apmAjax.failed_text + '</strong><br>' + response.data + '</div>');
            }
            
            $results.show();
        }).fail(function() {
            $btn.text(apmAjax.test_api_text).prop('disabled', false);
            $output.html('<div class="apm-notice error"><strong>✗ ' + apmAjax.request_failed + '</strong></div>');
            $results.show();
        });
    }

    function syntaxHighlight(json) {
        if (typeof json !== 'string') {
            json = JSON.stringify(json, null, 2);
        }
        
        json = json.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        
        return json.replace(/("(\\u[a-zA-Z0-9]{4}|\\[^u]|[^\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?)/g, function (match) {
            let cls = 'number';
            if (/^"/.test(match)) {
                if (/:$/.test(match)) {
                    cls = 'key';
                } else {
                    cls = 'string';
                }
            } else if (/true|false/.test(match)) {
                cls = 'boolean';
            } else if (/null/.test(match)) {
                cls = 'null';
            }
            return '<span class="' + cls + '">' + match + '</span>';
        });
    }

    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    function showNotice(message, type) {
        const noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
        const notice = $('<div class="notice ' + noticeClass + ' is-dismissible"><p>' + message + '</p></div>');
        
        // Remove any existing notices
        $('.notice').remove();
        
        $('.wrap h1').first().after(notice);
        
        // Auto-dismiss after 5 seconds
        setTimeout(() => notice.fadeOut(), 5000);
    }

    function startBulkCategorization() {
        if (!confirm(apmAjax.confirm_bulk)) return;

        const $progress = $('#bulk-progress');
        const $fill = $('.progress-fill');
        const $text = $('.progress-text');

        $progress.show();
        processBatch(0, $fill, $text);
    }

    function processBatch(offset, $fill, $text) {
        $.post(apmAjax.ajaxurl, {
            action: 'apm_bulk_categorize',
            nonce: apmAjax.nonce,
            offset: offset,
            batch_size: $('#batch-size').val() || 10
        }, function(response) {
            if (response.success) {
                const data = response.data;
                const percentage = (data.processed / data.total) * 100;
                $fill.css('width', percentage + '%');
                $text.text(data.processed + '/' + data.total + ' ' + apmAjax.products_processed);

                if (!data.completed) {
                    setTimeout(() => processBatch(offset + data.batch_size, $fill, $text), 2000);
                } else {
                    showNotice(apmAjax.bulk_completed, 'success');
                    setTimeout(() => location.reload(), 2000);
                }
            } else {
                showNotice(apmAjax.error_text + ': ' + response.data, 'error');
            }
        });
    }

    function importSampleData() {
        if (!confirm(apmAjax.confirm_import)) return;

        const $btn = $(this);
        $btn.html('<span class="apm-loading"></span> ' + apmAjax.importing_text).prop('disabled', true);

        $.post(apmAjax.ajaxurl, {
            action: 'apm_import_sample_data',
            nonce: apmAjax.nonce
        }, function(response) {
            if (response.success) {
                showNotice(response.data, 'success');
                setTimeout(() => location.reload(), 2000);
            } else {
                showNotice(apmAjax.error_text + ': ' + response.data, 'error');
                $btn.text(apmAjax.import_sample_text).prop('disabled', false);
            }
        });
    }

    function clearLogs() {
        const days = $('#log-retention').val();
        const message = days > 0 ? 
            apmAjax.confirm_clear_logs_days.replace('%d', days) : 
            apmAjax.confirm_clear_logs_all;
        
        if (!confirm(message)) return;

        const $btn = $(this);
        $btn.html('<span class="apm-loading"></span> ' + apmAjax.clearing_text).prop('disabled', true);

        $.post(apmAjax.ajaxurl, {
            action: 'apm_clear_logs',
            nonce: apmAjax.nonce,
            days: days
        }, function(response) {
            if (response.success) {
                showNotice(response.data, 'success');
                setTimeout(() => location.reload(), 2000);
            } else {
                showNotice(apmAjax.error_text + ': ' + response.data, 'error');
                $btn.text(apmAjax.clear_logs_text).prop('disabled', false);
            }
        });
    }

    function optimizeTables() {
        if (!confirm(apmAjax.confirm_optimize)) return;

        const $btn = $(this);
        $btn.html('<span class="apm-loading"></span> ' + apmAjax.optimizing_text).prop('disabled', true);

        $.post(apmAjax.ajaxurl, {
            action: 'apm_optimize_tables',
            nonce: apmAjax.nonce
        }, function(response) {
            if (response.success) {
                showNotice(response.data, 'success');
                $btn.text(apmAjax.optimize_tables_text).prop('disabled', false);
            } else {
                showNotice(apmAjax.error_text + ': ' + response.data, 'error');
                $btn.text(apmAjax.optimize_tables_text).prop('disabled', false);
            }
        });
    }

    function editApi() {
        const id = $(this).data('api-id');
        window.location.href = apmAjax.admin_url + 'admin.php?page=apm-apis&action=edit&id=' + id;
    }

    function deleteApi() {
        if (!confirm(apmAjax.confirm_delete)) return;
        
        const id = $(this).data('api-id');
        const $row = $(this).closest('tr');
        
        $.post(apmAjax.ajaxurl, {
            action: 'apm_delete_api',
            nonce: apmAjax.nonce,
            api_id: id
        }, function(response) {
            if (response.success) {
                showNotice(response.data, 'success');
                $row.fadeOut();
            } else {
                showNotice(apmAjax.error_text + ': ' + response.data, 'error');
            }
        });
    }

    function fetchApi() {
        if (!confirm(apmAjax.confirm_fetch)) return;
        
        const id = $(this).data('api-id');
        const $btn = $(this);
        
        $btn.html('<span class="apm-loading"></span> ' + apmAjax.fetching_text).prop('disabled', true);

        $.post(apmAjax.ajaxurl, {
            action: 'apm_fetch_products',
            nonce: apmAjax.nonce,
            api_id: id
        }, function(response) {
            if (response.success) {
                showNotice(response.data.message, 'success');
            } else {
                showNotice(apmAjax.error_text + ': ' + response.data, 'error');
            }
            $btn.text(apmAjax.fetch_now_text).prop('disabled', false);
        });
    }

    function toggleApi() {
        const id = $(this).data('api-id');
        window.location.href = apmAjax.admin_url + 'admin.php?page=apm-apis&action=toggle&id=' + id + '&_wpnonce=' + apmAjax.nonce;
    }

    function viewLogDetails(e) {
        e.preventDefault();
        
        const logId = $(this).data('log-id');
        const $modal = $('#log-details-modal');
        const $content = $modal.find('.log-details-content');
        
        $content.html('<div class="apm-loading" style="margin: 20px auto;"></div>');
        $modal.show();

        $.post(apmAjax.ajaxurl, {
            action: 'apm_get_log_details',
            nonce: apmAjax.nonce,
            log_id: logId
        }, function(response) {
            if (response.success) {
                $content.html('<pre class="json-viewer">' + syntaxHighlight(response.data) + '</pre>');
            } else {
                $content.html('<p>' + apmAjax.error_text + ': ' + response.data + '</p>');
            }
        });
    }

    function closeModal() {
        $('.apm-modal').hide();
    }

    // Close modal when clicking outside
    $(document).on('click', function(e) {
        if ($(e.target).hasClass('apm-modal')) {
            closeModal();
        }
    });

    // Initialize on page load
    $('#auth-type').trigger('change');
    $('#pagination-type').trigger('change');
    $('#publish-posts').trigger('change');

    // Auto-save settings
    let settingsTimeout;
    $('.apm-auto-save').on('change', function() {
        clearTimeout(settingsTimeout);
        settingsTimeout = setTimeout(saveSettingsForm, 1000);
    });

    function saveSettingsForm() {
        const formData = $('#settings-form').serialize() + '&action=apm_save_settings&nonce=' + apmAjax.nonce;
        
        $.post(apmAjax.ajaxurl, formData, function(response) {
            if (response.success) {
                showNotice(apmAjax.settings_saved, 'success');
            } else {
                showNotice(apmAjax.error_text + ': ' + response.data, 'error');
            }
        });
    }

    // Bulk actions
    $('#doaction, #doaction2').on('click', function(e) {
        const action = $(this).closest('.tablenav').find('.bulkactions select').val();
        const checked = $('input[name="product[]"]:checked');
        
        if (checked.length === 0) {
            e.preventDefault();
            showNotice(apmAjax.select_items, 'error');
            return;
        }
        
        if (action === 'categorize') {
            e.preventDefault();
            bulkCategorizeProducts(checked);
        } else if (action === 'publish') {
            e.preventDefault();
            bulkPublishProducts(checked);
        }
    });

    function bulkCategorizeProducts(products) {
        if (!confirm(apmAjax.confirm_bulk_categorize.replace('%d', products.length))) return;

        const productIds = products.map(function() {
            return $(this).val();
        }).get();

        $.post(apmAjax.ajaxurl, {
            action: 'apm_bulk_categorize_products',
            nonce: apmAjax.nonce,
            product_ids: productIds
        }, function(response) {
            if (response.success) {
                showNotice(response.data.message, 'success');
                setTimeout(() => location.reload(), 2000);
            } else {
                showNotice(apmAjax.error_text + ': ' + response.data, 'error');
            }
        });
    }

    function bulkPublishProducts(products) {
        if (!confirm(apmAjax.confirm_bulk_publish.replace('%d', products.length))) return;

        const productIds = products.map(function() {
            return $(this).val();
        }).get();

        $.post(apmAjax.ajaxurl, {
            action: 'apm_bulk_publish_products',
            nonce: apmAjax.nonce,
            product_ids: productIds
        }, function(response) {
            if (response.success) {
                showNotice(response.data.message, 'success');
                setTimeout(() => location.reload(), 2000);
            } else {
                showNotice(apmAjax.error_text + ': ' + response.data, 'error');
            }
        });
    }

    // Quick search
    let searchTimeout;
    $('#apm-search-input').on('keyup', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
            $('#apm-search-form').submit();
        }, 500);
    });

    // Export functionality
    $('#export-products').on('click', function() {
        const format = $('#export-format').val();
        window.location.href = apmAjax.admin_url + 'admin.php?page=apm-products&export=1&format=' + format;
    });

    // Real-time stats update
    function updateStats() {
        $.post(apmAjax.ajaxurl, {
            action: 'apm_get_stats',
            nonce: apmAjax.nonce
        }, function(response) {
            if (response.success) {
                $('#total-products').text(response.data.total_products);
                $('#active-apis').text(response.data.active_apis);
                $('#uncategorized').text(response.data.uncategorized);
                $('#published').text(response.data.published);
            }
        });
    }

    // Update stats every 30 seconds
    setInterval(updateStats, 30000);
});