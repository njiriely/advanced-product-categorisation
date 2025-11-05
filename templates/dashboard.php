<div class="wrap">
    <h1>Advanced Product Manager Dashboard</h1>
    
    <div class="apm-stats-grid">
        <div class="stat-card">
            <h3><?php echo esc_html($total_products); ?></h3>
            <p>Total Products</p>
            <span class="stat-detail">Across all APIs</span>
        </div>
        
        <div class="stat-card">
            <h3><?php echo esc_html($active_apis); ?></h3>
            <p>Active APIs</p>
            <span class="stat-detail">Currently connected</span>
        </div>
        
        <div class="stat-card">
            <h3><?php echo esc_html($uncategorized); ?></h3>
            <p>Need Categorization</p>
            <span class="stat-detail">Awaiting AI processing</span>
        </div>
        
        <div class="stat-card">
            <h3><?php echo esc_html($published); ?></h3>
            <p>Published Products</p>
            <span class="stat-detail">Live on your site</span>
        </div>
    </div>
    
    <div class="apm-quick-actions">
        <h2>Quick Actions</h2>
        <div class="action-buttons">
            <button id="bulk-fetch-all" class="button button-primary">Fetch All APIs</button>
            <button id="bulk-categorize-all" class="button button-primary">Bulk Categorize All</button>
            <a href="<?php echo admin_url('admin.php?page=apm-apis'); ?>" class="button">Manage APIs</a>
            <a href="<?php echo admin_url('admin.php?page=apm-products'); ?>" class="button">View Products</a>
        </div>
    </div>
    
    <div class="apm-recent-activity">
        <h2>Recent Activity</h2>
        <?php if (!empty($recent_logs)) : ?>
            <?php foreach ($recent_logs as $log) : ?>
                <div class="log-item">
                    <div class="log-content log-<?php echo esc_attr($log->status); ?>">
                        <strong><?php echo esc_html(ucfirst($log->action)); ?>:</strong> 
                        <?php echo esc_html($log->message); ?>
                        <span class="log-action">API #<?php echo esc_html($log->api_id); ?></span>
                    </div>
                    <span class="log-time"><?php echo esc_html(human_time_diff(strtotime($log->created_at), time())); ?> ago</span>
                </div>
            <?php endforeach; ?>
        <?php else : ?>
            <p>No recent activity.</p>
        <?php endif; ?>
    </div>
    
    <div class="apm-system-status">
        <h2>System Status</h2>
        <div class="status-checks">
            <div class="status-item">
                <span class="status-indicator status-ok"></span>
                <span>Database Tables: OK</span>
            </div>
            <div class="status-item">
                <span class="status-indicator <?php echo $vision_status ? 'status-ok' : 'status-warning'; ?>"></span>
                <span>Google Vision API: <?php echo $vision_status ? 'Connected' : 'Not Configured'; ?></span>
            </div>
            <div class="status-item">
                <span class="status-indicator status-ok"></span>
                <span>Cron Jobs: Active</span>
            </div>
            <div class="status-item">
                <span class="status-indicator status-ok"></span>
                <span>Plugin Version: <?php echo esc_html(APM_VERSION); ?></span>
            </div>
        </div>
    </div>
</div>