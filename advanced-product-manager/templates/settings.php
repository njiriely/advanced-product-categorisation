<div class="wrap">
    <h1>Advanced Product Manager Settings</h1>
    
    <form method="post" action="options.php">
        <?php settings_fields('apm_settings_group'); ?>
        <?php do_settings_sections('apm_settings_group'); ?>
        
        <div class="settings-section">
            <h2>Google Vision API Settings</h2>
            
            <table class="form-table">
                <tr>
                    <th><label for="apm_vision_api_key">API Key</label></th>
                    <td>
                        <input type="password" id="apm_vision_api_key" name="apm_vision_api_key" 
                               value="<?php echo esc_attr(get_option('apm_vision_api_key')); ?>" class="regular-text">
                        <p class="description">
                            Your Google Cloud Vision API key. 
                            <a href="https://cloud.google.com/vision/docs/setup" target="_blank">How to get one</a>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th><label for="apm_vision_min_confidence">Minimum Confidence</label></th>
                    <td>
                        <input type="number" id="apm_vision_min_confidence" name="apm_vision_min_confidence" 
                               value="<?php echo esc_attr(get_option('apm_vision_min_confidence', 0.7)); ?>" 
                               min="0.1" max="1.0" step="0.1" class="small-text">
                        <p class="description">
                            Only use labels with this confidence level or higher (0.1-1.0)
                        </p>
                    </td>
                </tr>
                <tr>
                    <th><label for="apm_vision_max_labels">Max Labels per Image</label></th>
                    <td>
                        <input type="number" id="apm_vision_max_labels" name="apm_vision_max_labels" 
                               value="<?php echo esc_attr(get_option('apm_vision_max_labels', 10)); ?>" 
                               min="1" max="20" class="small-text">
                        <p class="description">
                            Maximum number of labels to retrieve per product image
                        </p>
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="settings-section">
            <h2>Processing Settings</h2>
            
            <table class="form-table">
                <tr>
                    <th><label for="apm_auto_categorize">Auto Categorization</label></th>
                    <td>
                        <input type="checkbox" id="apm_auto_categorize" name="apm_auto_categorize" 
                               value="1" <?php checked(1, get_option('apm_auto_categorize', 1)); ?>>
                        <label for="apm_auto_categorize">Automatically categorize new products</label>
                    </td>
                </tr>
                <tr>
                    <th><label for="apm_batch_size">Batch Size</label></th>
                    <td>
                        <input type="number" id="apm_batch_size" name="apm_batch_size" 
                               value="<?php echo esc_attr(get_option('apm_batch_size', 10)); ?>" 
                               min="1" max="100" class="small-text">
                        <p class="description">
                            Number of products to process in each batch during bulk operations
                        </p>
                    </td>
                </tr>
                <tr>
                    <th><label for="apm_debug_mode">Debug Mode</label></th>
                    <td>
                        <input type="checkbox" id="apm_debug_mode" name="apm_debug_mode" 
                               value="1" <?php checked(1, get_option('apm_debug_mode')); ?>>
                        <label for="apm_debug_mode">Enable detailed logging for debugging</label>
                    </td>
                </tr>
            </table>
        </div>
        
        <?php submit_button(); ?>
    </form>
    
    <div class="settings-section">
        <h2>Tools</h2>
        
        <table class="form-table">
            <tr>
                <th>Database Maintenance</th>
                <td>
                    <button id="apm-optimize-tables" class="button">Optimize Database Tables</button>
                    <p class="description">Improve performance by optimizing plugin database tables</p>
                </td>
            </tr>
            <tr>
                <th>Clear Logs</th>
                <td>
                    <button id="apm-clear-logs" class="button">Clear All Logs</button>
                    <p class="description">Remove all log entries from the database</p>
                </td>
            </tr>
        </table>
    </div>
</div>