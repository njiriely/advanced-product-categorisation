<div class="wrap">
    <h1>Activity Logs</h1>
    
    <div class="tablenav top">
        <div class="alignleft actions">
            <select name="filter_action">
                <option value="">All Actions</option>
                <option value="fetch" <?php selected($current_action, 'fetch'); ?>>API Fetch</option>
                <option value="categorize" <?php selected($current_action, 'categorize'); ?>>Categorization</option>
                <option value="publish" <?php selected($current_action, 'publish'); ?>>Publishing</option>
                <option value="error" <?php selected($current_action, 'error'); ?>>Errors</option>
            </select>
            
            <select name="filter_api">
                <option value="">All APIs</option>
                <?php foreach ($apis as $api) : ?>
                    <option value="<?php echo $api->id; ?>" <?php selected($current_api, $api->id); ?>>
                        <?php echo esc_html($api->name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <button class="button" id="filter-logs">Filter</button>
        </div>
        
        <div class="tablenav-pages">
            <span class="displaying-num"><?php echo $total_logs; ?> items</span>
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
                <th>Time</th>
                <th>Action</th>
                <th>API</th>
                <th>Status</th>
                <th>Message</th>
                <th>Details</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($logs)) : ?>
                <?php foreach ($logs as $log) : ?>
                    <tr>
                        <td><?php echo esc_html(date('Y-m-d H:i:s', strtotime($log->created_at))); ?></td>
                        <td><?php echo esc_html(ucfirst($log->action)); ?></td>
                        <td>
                            <?php if ($log->api_id) : ?>
                                <a href="<?php echo admin_url('admin.php?page=apm-apis&action=edit&id=' . $log->api_id); ?>">
                                    API #<?php echo $log->api_id; ?>
                                </a>
                            <?php else : ?>
                                System
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($log->status == 'success') : ?>
                                <span class="status-active">Success</span>
                            <?php elseif ($log->status == 'error') : ?>
                                <span style="color: #d63638;">Error</span>
                            <?php else : ?>
                                <?php echo esc_html(ucfirst($log->status)); ?>
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html($log->message); ?></td>
                        <td>
                            <?php if ($log->data) : ?>
                                <a href="#" class="view-log-details" data-log-id="<?php echo $log->id; ?>">View Details</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="6">No logs found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    
    <!-- Modal for log details -->
    <div id="log-details-modal" style="display: none;">
        <div class="log-details-content"></div>
    </div>
</div>