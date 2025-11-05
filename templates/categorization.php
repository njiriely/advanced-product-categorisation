<div class="wrap">
    <h1>AI Categorization</h1>
    
    <div class="apm-categorization-tabs">
        <button class="tab-button active" data-tab="mappings">Label Mappings</button>
        <button class="tab-button" data-tab="uncategorized">Uncategorized Labels</button>
        <button class="tab-button" data-tab="bulk">Bulk Processing</button>
    </div>
    
    <div id="tab-mappings" class="tab-content active">
        <div class="mapping-form">
            <h2>Add New Mapping</h2>
            <form id="mapping-form">
                <table class="form-table">
                    <tr>
                        <th><label for="vision-label">Vision AI Label</label></th>
                        <td>
                            <input type="text" id="vision-label" class="regular-text" required>
                            <p class="description">The label returned by Google Vision AI</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="category-id">Category</label></th>
                        <td>
                            <?php
                            wp_dropdown_categories(array(
                                'show_option_all' => 'Select a category',
                                'hide_empty' => 0,
                                'name' => 'category-id',
                                'id' => 'category-id',
                                'orderby' => 'name',
                                'hierarchical' => true
                            ));
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="priority">Priority</label></th>
                        <td>
                            <select id="priority">
                                <option value="1">Low</option>
                                <option value="2" selected>Medium</option>
                                <option value="3">High</option>
                            </select>
                            <p class="description">Higher priority mappings take precedence</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="confidence-boost">Confidence Boost</label></th>
                        <td>
                            <input type="number" id="confidence-boost" min="0.5" max="2.0" step="0.1" value="1.0">
                            <p class="description">Multiply the AI confidence by this value (0.5-2.0)</p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <button type="submit" class="button button-primary">Save Mapping</button>
                </p>
            </form>
        </div>
        
        <div class="mappings-list">
            <h2>Existing Mappings</h2>
            <?php if (!empty($mappings)) : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Vision Label</th>
                            <th>Category</th>
                            <th>Priority</th>
                            <th>Confidence Boost</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($mappings as $mapping) : ?>
                            <tr>
                                <td><?php echo esc_html($mapping->vision_label); ?></td>
                                <td><?php echo esc_html(get_cat_name($mapping->category_id)); ?></td>
                                <td><span class="priority-<?php echo $mapping->priority; ?>">
                                    <?php 
                                    if ($mapping->priority == 3) echo 'High';
                                    elseif ($mapping->priority == 2) echo 'Medium';
                                    else echo 'Low';
                                    ?>
                                </span></td>
                                <td><?php echo esc_html($mapping->confidence_boost); ?>x</td>
                                <td>
                                    <button class="button button-small delete-mapping" data-mapping-id="<?php echo $mapping->id; ?>">Delete</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p>No mappings defined yet. Add your first mapping using the form above.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <div id="tab-uncategorized" class="tab-content">
        <h2>Uncategorized Labels</h2>
        <p>These are labels that Vision AI has detected but haven't been mapped to categories yet.</p>
        
        <?php if (!empty($uncategorized_labels)) : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Vision Label</th>
                        <th>Frequency</th>
                        <th>Average Confidence</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($uncategorized_labels as $label) : ?>
                        <tr>
                            <td><?php echo esc_html($label->vision_label); ?></td>
                            <td><?php echo esc_html($label->frequency); ?></td>
                            <td><?php echo esc_html(round($label->avg_confidence * 100, 2)); ?>%</td>
                            <td>
                                <button class="button button-small quick-map" data-label="<?php echo esc_attr($label->vision_label); ?>">
                                    Quick Map
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <p>No uncategorized labels found. All detected labels have been mapped.</p>
        <?php endif; ?>
    </div>
    
    <div id="tab-bulk" class="tab-content">
        <h2>Bulk Categorization</h2>
        
        <div class="test-section">
            <h3>Test Vision API Connection</h3>
            <p>
                <button id="test-vision-api" class="button">Test Vision API</button>
            </p>
            <div id="vision-test-results" style="display: none; margin-top: 10px;"></div>
        </div>
        
        <div class="settings-section">
            <h3>Bulk Processing</h3>
            <p>Process all uncategorized products using Google Vision AI.</p>
            
            <div id="bulk-progress" style="display: none;">
                <div class="progress-bar">
                    <div class="progress-fill" style="width: 0%;"></div>
                </div>
                <p class="progress-text">0/0 products processed</p>
            </div>
            
            <p>
                <button id="start-bulk-categorize" class="button button-primary">Start Bulk Categorization</button>
            </p>
            
            <p class="description">
                Note: This will consume Google Vision API credits. Estimated cost: $1.50 per 1000 images.
            </p>
        </div>
    </div>
</div>