<div class="wrap">
    <h1>API Management</h1>
    
    <div class="apm-api-form">
        <h2><?php echo isset($edit_api) ? 'Edit API' : 'Add New API'; ?></h2>
        <form id="apm-api-form">
            <input type="hidden" id="api-id" value="<?php echo isset($edit_api) ? $edit_api->id : ''; ?>">
            
            <table class="form-table">
                <tr>
                    <th><label for="api-name">API Name</label></th>
                    <td>
                        <input type="text" id="api-name" class="regular-text" 
                               value="<?php echo isset($edit_api) ? esc_attr($edit_api->name) : ''; ?>" required>
                    </td>
                </tr>
                <tr>
                    <th><label for="api-url">API Endpoint URL</label></th>
                    <td>
                        <input type="url" id="api-url" class="regular-text" 
                               value="<?php echo isset($edit_api) ? esc_attr($edit_api->url) : ''; ?>" required>
                        <p class="description">The full URL to your products API endpoint</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="api-method">HTTP Method</label></th>
                    <td>
                        <select id="api-method">
                            <option value="GET" <?php echo (isset($edit_api) && $edit_api->method == 'GET') ? 'selected' : ''; ?>>GET</option>
                            <option value="POST" <?php echo (isset($edit_api) && $edit_api->method == 'POST') ? 'selected' : ''; ?>>POST</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="auth-type">Authentication Type</label></th>
                    <td>
                        <select id="auth-type">
                            <option value="none" <?php echo (isset($edit_api) && $edit_api->auth_type == 'none') ? 'selected' : ''; ?>>None</option>
                            <option value="bearer" <?php echo (isset($edit_api) && $edit_api->auth_type == 'bearer') ? 'selected' : ''; ?>>Bearer Token</option>
                            <option value="basic" <?php echo (isset($edit_api) && $edit_api->auth_type == 'basic') ? 'selected' : ''; ?>>Basic Auth</option>
                        </select>
                    </td>
                </tr>
                <tr id="auth-data-row" style="<?php echo (isset($edit_api) && $edit_api->auth_type != 'none') ? '' : 'display: none;'; ?>">
                    <th><label for="auth-data">Authentication Data</label></th>
                    <td>
                        <input type="text" id="auth-data" class="regular-text" 
                               value="<?php echo isset($edit_api) ? esc_attr($edit_api->auth_data) : ''; ?>">
                        <p id="auth-help" class="description">
                            <?php if (isset($edit_api) && $edit_api->auth_type == 'bearer') : ?>
                                Enter your bearer token
                            <?php elseif (isset($edit_api) && $edit_api->auth_type == 'basic') : ?>
                                Enter username:password
                            <?php else : ?>
                                Authentication data
                            <?php endif; ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th><label for="product-path">Products JSON Path</label></th>
                    <td>
                        <input type="text" id="product-path" class="regular-text" 
                               value="<?php echo isset($edit_api) ? esc_attr($edit_api->product_path) : '$.products[*]'; ?>" required>
                        <p class="description">JSONPath to locate products array in response (e.g., $.products[*])</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="field-mapping">Field Mapping</label></th>
                    <td>
                        <textarea id="field-mapping" rows="6" class="large-text code"><?php 
                            echo isset($edit_api) ? esc_textarea($edit_api->mapping) : 
                            '{
    "id": "external_id",
    "title": "name",
    "description": "description",
    "price": "price",
    "image": "image_url",
    "category": "category"
}'; 
                        ?></textarea>
                        <p class="description">Map API response fields to product fields (JSON format)</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="publish-posts">Auto Publish</label></th>
                    <td>
                        <input type="checkbox" id="publish-posts" <?php echo (isset($edit_api) && $edit_api->publish_posts) ? 'checked' : 'checked'; ?>>
                        <label for="publish-posts">Automatically publish products as they are imported</label>
                    </td>
                </tr>
                <tr>
                    <th><label for="auto-categorize">Auto Categorize</label></th>
                    <td>
                        <input type="checkbox" id="auto-categorize" <?php echo (isset($edit_api) && $edit_api->auto_categorize) ? 'checked' : 'checked'; ?>>
                        <label for="auto-categorize">Automatically categorize products using AI</label>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <button type="submit" class="button button-primary">Save API</button>
                <button type="button" id="test-api" class="button">Test API</button>
                <?php if (isset($edit_api)) : ?>
                    <a href="<?php echo admin_url('admin.php?page=apm-apis'); ?>" class="button">Cancel</a>
                <?php endif; ?>
            </p>
        </form>
        
        <div id="test-results" style="display: none; margin-top: 20px;">
            <h3>Test Results</h3>
            <div id="test-output"></div>
        </div>
    </div>
    
    <div class="apm-api-list">
        <h2>Configured APIs</h2>
        <?php if (!empty($apis)) : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>URL</th>
                        <th>Status</th>
                        <th>Last Fetch</th>
                        <th>Products</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($apis as $api) : ?>
                        <tr>
                            <td><?php echo esc_html($api->name); ?></td>
                            <td><?php echo esc_html($api->url); ?></td>
                            <td><span class="status-<?php echo esc_attr($api->status); ?>"><?php echo esc_html(ucfirst($api->status)); ?></span></td>
                            <td><?php echo $api->last_fetch ? esc_html(date('Y-m-d H:i', strtotime($api->last_fetch))) : 'Never'; ?></td>
                            <td><?php echo esc_html($api->product_count); ?></td>
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=apm-apis&action=edit&id=' . $api->id); ?>" class="button button-small edit-api" data-api-id="<?php echo $api->id; ?>">Edit</a>
                                <button class="button button-small fetch-api" data-api-id="<?php echo $api->id; ?>">Fetch Now</button>
                                <button class="button button-small delete-api" data-api-id="<?php echo $api->id; ?>">Delete</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <p>No APIs configured yet. Add your first API using the form above.</p>
        <?php endif; ?>
    </div>
</div>