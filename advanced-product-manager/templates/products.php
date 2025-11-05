<div class="wrap">
    <h1>Products</h1>
    
    <div class="tablenav top">
        <div class="alignleft actions">
            <select name="filter_api">
                <option value="">All APIs</option>
                <?php foreach ($apis as $api) : ?>
                    <option value="<?php echo $api->id; ?>" <?php selected($current_api, $api->id); ?>>
                        <?php echo esc_html($api->name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <select name="filter_status">
                <option value="">All Statuses</option>
                <option value="published" <?php selected($current_status, 'published'); ?>>Published</option>
                <option value="draft" <?php selected($current_status, 'draft'); ?>>Draft</option>
                <option value="uncategorized" <?php selected($current_status, 'uncategorized'); ?>>Uncategorized</option>
            </select>
            
            <button class="button" id="filter-action">Filter</button>
        </div>
        
        <div class="tablenav-pages">
            <span class="displaying-num"><?php echo $total_products; ?> items</span>
            <?php if ($total_pages > 1) : ?>
                <span class="pagination-links">
                    <?php echo paginate_links(array(
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'prev_text' => '&laquo;',
                        'next_text' => '&raquo;',
                        'total' => $total_pages,
                        'current' => $paged
                    )); ?>
                </span>
            <?php endif; ?>
        </div>
    </div>
    
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>Image</th>
                <th>Name</th>
                <th>API Source</th>
                <th>Price</th>
                <th>Category</th>
                <th>Status</th>
                <th>AI Processed</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($products)) : ?>
                <?php foreach ($products as $product) : ?>
                    <tr>
                        <td>
                            <?php if ($product->image_url) : ?>
                                <img src="<?php echo esc_url($product->image_url); ?>" style="max-width: 60px; height: auto;">
                            <?php else : ?>
                                <div style="width: 60px; height: 60px; background: #f0f0f1; display: flex; align-items: center; justify-content: center;">
                                    <span class="dashicons dashicons-format-image"></span>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong><?php echo esc_html($product->name); ?></strong>
                            <div class="row-actions">
                                <?php if ($product->wp_post_id) : ?>
                                    <span class="view">
                                        <a href="<?php echo get_permalink($product->wp_post_id); ?>" target="_blank">View</a> |
                                    </span>
                                    <span class="edit">
                                        <a href="<?php echo get_edit_post_link($product->wp_post_id); ?>" target="_blank">Edit</a>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td><?php echo esc_html($product->api_name); ?></td>
                        <td><?php echo esc_html($product->currency . ' ' . number_format($product->price, 2)); ?></td>
                        <td><?php echo esc_html($product->category); ?></td>
                        <td>
                            <?php if ($product->published) : ?>
                                <span class="status-active">Published</span>
                            <?php else : ?>
                                <span class="status-inactive">Draft</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($product->vision_processed) : ?>
                                <span class="status-active">Yes</span>
                            <?php else : ?>
                                <span class="status-inactive">No</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button class="button button-small apm-categorize-product" data-product-id="<?php echo $product->id; ?>">
                                Categorize
                            </button>
                            <?php if (!$product->published && $product->wp_post_id) : ?>
                                <button class="button button-small apm-publish-product" data-product-id="<?php echo $product->id; ?>">
                                    Publish
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="8">No products found. <a href="<?php echo admin_url('admin.php?page=apm-apis'); ?>">Add an API</a> to import products.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>