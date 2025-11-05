<?php
/**
 * Plugin Name: Advanced Product Manager with AI Categorization
 * Description: Complete product management system with REST API fetching, automatic categorization using Google Vision
 * Version: 2.1.0
 * Author: CGL Marketing Ltd
 * License: GPL v2 or later
 * Text Domain: advanced-product-manager
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define constants
define('APM_VERSION', '2.1.0');
define('APM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('APM_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('APM_TEXT_DOMAIN', 'advanced-product-manager');

// Main plugin class
class AdvancedProductManager {

    private $table_apis;
    private $table_products;
    private $table_logs;
    private $table_mappings;
    private $table_uncategorized;
    private $api_manager;
    private $vision_processor;
    private $product_creator;

    public function __construct() {
        global $wpdb;
        $this->table_apis = $wpdb->prefix . 'apm_rest_apis';
        $this->table_products = $wpdb->prefix . 'apm_products';
        $this->table_logs = $wpdb->prefix . 'apm_logs';
        $this->table_mappings = $wpdb->prefix . 'apm_vision_mappings';
        $this->table_uncategorized = $wpdb->prefix . 'apm_uncategorized_labels';

        add_action('plugins_loaded', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        register_uninstall_hook(__FILE__, array('AdvancedProductManager', 'uninstall'));
        
        // Include other classes
        $this->includes();
    }

    private function includes() {
        require_once APM_PLUGIN_PATH . 'includes/class-api-manager.php';
        require_once APM_PLUGIN_PATH . 'includes/class-vision-processor.php';
        require_once APM_PLUGIN_PATH . 'includes/class-product-creator.php';
        
        // Initialize classes
        $this->api_manager = new APM_Api_Manager($this);
        $this->vision_processor = new APM_Vision_Processor($this);
        $this->product_creator = new APM_Product_Creator($this);
    }

    public function init() {
        // Load text domain for translations
        load_plugin_textdomain(APM_TEXT_DOMAIN, false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        if (is_admin()) {
            add_action('admin_menu', array($this, 'add_admin_menu'));
            add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));

            // Register AJAX handlers
            $this->register_ajax_handlers();
            
            // Register settings
            add_action('admin_init', array($this, 'register_settings'));
            
            // Add plugin action links
            add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_plugin_action_links'));
        }

        // Cron hooks
        add_action('apm_daily_fetch', array($this, 'daily_fetch_all_apis'));
        add_action('apm_hourly_processing', array($this, 'hourly_processing'));
        add_action('apm_process_product_vision', array($this, 'process_product_with_vision'));

        // Schedule events if not already scheduled
        if (!wp_next_scheduled('apm_daily_fetch')) {
            wp_schedule_event(time(), 'daily', 'apm_daily_fetch');
        }
        
        if (!wp_next_scheduled('apm_hourly_processing')) {
            wp_schedule_event(time(), 'hourly', 'apm_hourly_processing');
        }
        
        // Product hooks for automatic categorization
        add_action('wp_insert_post', array($this, 'handle_new_product'), 10, 3);
        
        // REST API endpoints
        add_action('rest_api_init', array($this, 'register_rest_routes'));
    }

    private function register_ajax_handlers() {
        $ajax_actions = array(
            'apm_save_api',
            'apm_delete_api',
            'apm_test_api',
            'apm_fetch_products',
            'apm_get_api',
            'apm_test_vision',
            'apm_categorize_product',
            'apm_bulk_categorize',
            'apm_save_mapping',
            'apm_delete_mapping',
            'apm_get_suggestions',
            'apm_bulk_fetch_all',
            'apm_bulk_categorize_all',
            'apm_import_sample_data',
            'apm_clear_logs',
            'apm_optimize_tables'
        );

        foreach ($ajax_actions as $action) {
            add_action('wp_ajax_'. $action, array($this, str_replace('apm_', 'ajax_', $action)));
        }
    }
    
    public function register_settings() {
        register_setting('apm_settings_group', 'apm_vision_api_key');
        register_setting('apm_settings_group', 'apm_vision_min_confidence');
        register_setting('apm_settings_group', 'apm_vision_max_labels');
        register_setting('apm_settings_group', 'apm_auto_categorize');
        register_setting('apm_settings_group', 'apm_batch_size');
        register_setting('apm_settings_group', 'apm_debug_mode');
        register_setting('apm_settings_group', 'apm_enable_woocommerce');
        register_setting('apm_settings_group', 'apm_default_category');
    }
    
    public function add_plugin_action_links($links) {
        $settings_link = '<a href="' . admin_url('admin.php?page=apm-settings') . '">' . __('Settings', APM_TEXT_DOMAIN) . '</a>';
        $docs_link = '<a href="https://example.com/docs" target="_blank">' . __('Documentation', APM_TEXT_DOMAIN) . '</a>';
        array_unshift($links, $settings_link, $docs_link);
        return $links;
    }

    public function activate() {
        $this->create_tables();
        
        // Set default options if not already set
        add_option('apm_version', APM_VERSION);
        add_option('apm_vision_min_confidence', 0.7);
        add_option('apm_vision_max_labels', 10);
        add_option('apm_auto_categorize', 1);
        add_option('apm_batch_size', 10);
        add_option('apm_enable_woocommerce', class_exists('WooCommerce') ? 1 : 0);
        add_option('apm_default_category', 1);
        
        // Add a sample API if no APIs exist
        $this->maybe_add_sample_data();
        
        // Trigger a fetch after activation
        wp_schedule_single_event(time() + 60, 'apm_daily_fetch');
    }

    public function deactivate() {
        wp_clear_scheduled_hook('apm_daily_fetch');
        wp_clear_scheduled_hook('apm_hourly_processing');
    }
    
    public static function uninstall() {
        global $wpdb;
        
        // Remove options
        delete_option('apm_version');
        delete_option('apm_vision_api_key');
        delete_option('apm_vision_min_confidence');
        delete_option('apm_vision_max_labels');
        delete_option('apm_auto_categorize');
        delete_option('apm_batch_size');
        delete_option('apm_debug_mode');
        delete_option('apm_enable_woocommerce');
        delete_option('apm_default_category');
        
        // Remove database tables if setting is enabled
        if (get_option('apm_remove_data_on_uninstall')) {
            $tables = array(
                $wpdb->prefix . 'apm_rest_apis',
                $wpdb->prefix . 'apm_products',
                $wpdb->prefix . 'apm_logs',
                $wpdb->prefix . 'apm_vision_mappings',
                $wpdb->prefix . 'apm_uncategorized_labels'
            );
            
            foreach ($tables as $table) {
                $wpdb->query("DROP TABLE IF EXISTS $table");
            }
        }
    }

    private function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // APIs table
        $sql_apis = "CREATE TABLE IF NOT EXISTS {$this->table_apis} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL DEFAULT '',
            url varchar(500) NOT NULL DEFAULT '',
            method varchar(10) NOT NULL DEFAULT 'GET',
            headers text,
            auth_type varchar(20) NOT NULL DEFAULT 'none',
            auth_data text,
            product_path varchar(255) NOT NULL DEFAULT '',
            mapping text,
            status varchar(20) NOT NULL DEFAULT 'active',
            publish_posts tinyint(1) NOT NULL DEFAULT 1,
            auto_categorize tinyint(1) NOT NULL DEFAULT 1,
            post_type varchar(20) NOT NULL DEFAULT 'auto',
            pagination_type varchar(20) NOT NULL DEFAULT 'none',
            pagination_param varchar(50) DEFAULT 'page',
            page_size int DEFAULT 50,
            max_pages int NOT NULL DEFAULT 100,
            last_fetch datetime DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        // Products table
        $sql_products = "CREATE TABLE IF NOT EXISTS {$this->table_products} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            api_id mediumint(9) NOT NULL DEFAULT 0,
            external_id varchar(255) NOT NULL DEFAULT '',
            name varchar(500) NOT NULL DEFAULT '',
            description text,
            price decimal(10,2) NOT NULL DEFAULT 0.00,
            currency varchar(10) NOT NULL DEFAULT 'USD',
            image_url varchar(500) NOT NULL DEFAULT '',
            category varchar(255) NOT NULL DEFAULT '',
            stock_status varchar(50) NOT NULL DEFAULT '',
            raw_data longtext,
            wp_post_id bigint(20) DEFAULT NULL,
            published tinyint(1) NOT NULL DEFAULT 0,
            vision_processed tinyint(1) NOT NULL DEFAULT 0,
            vision_labels text,
            vision_confidence decimal(3,2) DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY api_id (api_id),
            KEY wp_post_id (wp_post_id),
            KEY vision_processed (vision_processed),
            KEY external_id (external_id(100))
        ) $charset_collate;";

        // Logs table
        $sql_logs = "CREATE TABLE IF NOT EXISTS {$this->table_logs} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            api_id mediumint(9) NOT NULL DEFAULT 0,
            product_id mediumint(9) NOT NULL DEFAULT 0,
            action varchar(50) NOT NULL DEFAULT '',
            status varchar(20) NOT NULL DEFAULT '',
            message text,
            data text,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY api_id (api_id),
            KEY action (action),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset_collate;";

        // Vision mappings table
        $sql_mappings = "CREATE TABLE IF NOT EXISTS {$this->table_mappings} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            vision_label varchar(255) NOT NULL,
            category_id bigint(20) NOT NULL,
            confidence_boost decimal(3,2) NOT NULL DEFAULT 1.00,
            priority int NOT NULL DEFAULT 1,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_mapping (vision_label, category_id),
            KEY vision_label (vision_label(100)),
            KEY category_id (category_id)
        ) $charset_collate;";

        // Uncategorized labels table
        $sql_uncategorized = "CREATE TABLE IF NOT EXISTS {$this->table_uncategorized} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            product_id mediumint(9) NOT NULL,
            vision_label varchar(255) NOT NULL,
            confidence decimal(5,4) NOT NULL,
            frequency int NOT NULL DEFAULT 1,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY product_id (product_id),
            KEY vision_label (vision_label(100)),
            KEY frequency (frequency)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_apis);
        dbDelta($sql_products);
        dbDelta($sql_logs);
        dbDelta($sql_mappings);
        dbDelta($sql_uncategorized);
    }
    
    private function maybe_add_sample_data() {
        global $wpdb;
        
        // Check if any APIs exist
        $api_count = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_apis}");
        
        if ($api_count == 0) {
            // Add a sample API
            $sample_api = array(
                'name' => 'Sample Products API',
                'url' => 'https://dummyjson.com/products',
                'method' => 'GET',
                'product_path' => '$.products[*]',
                'mapping' => json_encode(array(
                    'id' => 'id',
                    'title' => 'title',
                    'description' => 'description',
                    'price' => 'price',
                    'image' => 'thumbnail',
                    'category' => 'category'
                )),
                'pagination_type' => 'skip_limit',
                'pagination_param' => 'skip',
                'page_size' => 30,
                'max_pages' => 3
            );
            
            $wpdb->insert($this->table_apis, $sample_api);
            $this->log_action($wpdb->insert_id, 0, 'sample_data', 'success', 'Sample API added');
        }
    }

    public function enqueue_scripts($hook) {
        if (strpos($hook, 'apm-') === false) {
            return;
        }

        wp_enqueue_script('jquery');
        wp_enqueue_script('apm-admin', APM_PLUGIN_URL . 'assets/admin.js', array('jquery'), APM_VERSION, true);
        wp_enqueue_style('apm-admin', APM_PLUGIN_URL . 'assets/admin.css', array(), APM_VERSION);
        
        // Add select2 for better dropdowns
        wp_enqueue_script('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', array('jquery'), '4.1.0', true);
        wp_enqueue_style('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', array(), '4.1.0');

        wp_localize_script('apm-admin', 'apmAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('apm_nonce'),
            'loading_text' => __('Processing...', APM_TEXT_DOMAIN),
            'confirm_delete' => __('Are you sure you want to delete this? This action cannot be undone.', APM_TEXT_DOMAIN),
            'confirm_bulk' => __('This operation may take a long time. Do you want to continue?', APM_TEXT_DOMAIN)
        ));
        
        // Localize category data for mapping
        $categories = get_categories(array('hide_empty' => false));
        $category_options = array();
        
        foreach ($categories as $category) {
            $category_options[] = array(
                'id' => $category->term_id,
                'text' => $category->name
            );
        }
        
        wp_localize_script('apm-admin', 'apmCategories', $category_options);
    }

    public function add_admin_menu() {
        $icon_url = 'dashicons-products';
        
        add_menu_page(
            __('Product Manager', APM_TEXT_DOMAIN),
            __('Product Manager', APM_TEXT_DOMAIN),
            'manage_options',
            'apm-dashboard',
            array($this, 'dashboard_page'),
            $icon_url,
            30
        );
        
        add_submenu_page('apm-dashboard', __('Dashboard', APM_TEXT_DOMAIN), __('Dashboard', APM_TEXT_DOMAIN), 'manage_options', 'apm-dashboard', array($this, 'dashboard_page'));
        add_submenu_page('apm-dashboard', __('API Management', APM_TEXT_DOMAIN), __('API Management', APM_TEXT_DOMAIN), 'manage_options', 'apm-apis', array($this, 'apis_page'));
        add_submenu_page('apm-dashboard', __('Products', APM_TEXT_DOMAIN), __('Products', APM_TEXT_DOMAIN), 'manage_options', 'apm-products', array($this, 'products_page'));
        add_submenu_page('apm-dashboard', __('AI Categorization', APM_TEXT_DOMAIN), __('AI Categorization', APM_TEXT_DOMAIN), 'manage_options', 'apm-categorization', array($this, 'categorization_page'));
        add_submenu_page('apm-dashboard', __('Settings', APM_TEXT_DOMAIN), __('Settings', APM_TEXT_DOMAIN), 'manage_options', 'apm-settings', array($this, 'settings_page'));
        add_submenu_page('apm-dashboard', __('Logs', APM_TEXT_DOMAIN), __('Logs', APM_TEXT_DOMAIN), 'manage_options', 'apm-logs', array($this, 'logs_page'));
        
        // Add a hidden page for tools
        add_submenu_page(null, __('Tools', APM_TEXT_DOMAIN), __('Tools', APM_TEXT_DOMAIN), 'manage_options', 'apm-tools', array($this, 'tools_page'));
    }

    // Admin page methods
    public function dashboard_page() {
        global $wpdb;
        
        // Get statistics for the dashboard
        $stats = array(
            'total_products' => $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_products}"),
            'active_apis' => $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_apis} WHERE status = 'active'"),
            'uncategorized' => $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_products} WHERE vision_processed = 0"),
            'published' => $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_products} WHERE published = 1"),
            'failed_imports' => $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_logs} WHERE action = 'fetch' AND status = 'error' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")
        );
        
        // Get recent logs
        $recent_logs = $wpdb->get_results(
            "SELECT l.*, a.name as api_name 
             FROM {$this->table_logs} l 
             LEFT JOIN {$this->table_apis} a ON l.api_id = a.id 
             ORDER BY l.created_at DESC 
             LIMIT 10"
        );
        
        // Check Google Vision API status
        $vision_status = !empty(get_option('apm_vision_api_key'));
        
        // Get API status
        $api_status = $wpdb->get_results(
            "SELECT id, name, status, last_fetch 
             FROM {$this->table_apis} 
             ORDER BY created_at DESC"
        );
        
        include_once APM_PLUGIN_PATH . 'templates/dashboard.php';
    }

    public function apis_page() {
        global $wpdb;
        
        // Handle API actions
        if (isset($_GET['action']) && isset($_GET['id'])) {
            $action = sanitize_text_field($_GET['action']);
            $api_id = intval($_GET['id']);
            
            switch ($action) {
                case 'edit':
                    $edit_api = $wpdb->get_row($wpdb->prepare(
                        "SELECT * FROM {$this->table_apis} WHERE id = %d", $api_id
                    ));
                    break;
                    
                case 'delete':
                    if (isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'apm_delete_api')) {
                        $this->ajax_delete_api($api_id);
                        wp_redirect(admin_url('admin.php?page=apm-apis&message=deleted'));
                        exit;
                    }
                    break;
                    
                case 'toggle':
                    if (isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'apm_toggle_api')) {
                        $current_status = $wpdb->get_var($wpdb->prepare(
                            "SELECT status FROM {$this->table_apis} WHERE id = %d", $api_id
                        ));
                        
                        $new_status = $current_status == 'active' ? 'inactive' : 'active';
                        
                        $wpdb->update($this->table_apis, 
                            array('status' => $new_status),
                            array('id' => $api_id)
                        );
                        
                        $this->log_action($api_id, 0, 'toggle_api', 'success', "API status changed to {$new_status}");
                        wp_redirect(admin_url('admin.php?page=apm-apis&message=status_changed'));
                        exit;
                    }
                    break;
            }
        }
        
        // Get all APIs with product counts
        $apis = $wpdb->get_results("
            SELECT a.*, COUNT(p.id) as product_count 
            FROM {$this->table_apis} a 
            LEFT JOIN {$this->table_products} p ON a.id = p.api_id 
            GROUP BY a.id 
            ORDER BY a.created_at DESC
        ");
        
        include_once APM_PLUGIN_PATH . 'templates/api-management.php';
    }

    public function products_page() {
        global $wpdb;
        
        // Handle filters
        $current_api = isset($_GET['filter_api']) ? intval($_GET['filter_api']) : 0;
        $current_status = isset($_GET['filter_status']) ? sanitize_text_field($_GET['filter_status']) : '';
        $search_term = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
        
        // Build query
        $where = array('1=1');
        $query_params = array();
        
        if ($current_api > 0) {
            $where[] = "p.api_id = %d";
            $query_params[] = $current_api;
        }
        
        if ($current_status == 'published') {
            $where[] = "p.published = 1";
        } elseif ($current_status == 'draft') {
            $where[] = "p.published = 0";
        } elseif ($current_status == 'uncategorized') {
            $where[] = "p.vision_processed = 0";
        }
        
        if (!empty($search_term)) {
            $where[] = "(p.name LIKE %s OR p.description LIKE %s OR p.external_id LIKE %s)";
            $search_like = '%' . $wpdb->esc_like($search_term) . '%';
            $query_params[] = $search_like;
            $query_params[] = $search_like;
            $query_params[] = $search_like;
        }
        
        $where_clause = implode(' AND ', $where);
        
        // Pagination
        $per_page = 20;
        $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $offset = ($paged - 1) * $per_page;
        
        // Get products
        $products_query = "
            SELECT p.*, a.name as api_name 
            FROM {$this->table_products} p 
            LEFT JOIN {$this->table_apis} a ON p.api_id = a.id 
            WHERE {$where_clause} 
            ORDER BY p.created_at DESC 
            LIMIT {$offset}, {$per_page}
        ";
        
        if (!empty($query_params)) {
            $products_query = $wpdb->prepare($products_query, $query_params);
        }
        
        $products = $wpdb->get_results($products_query);
        
        // Total products for pagination
        $count_query = "
            SELECT COUNT(*) 
            FROM {$this->table_products} p 
            LEFT JOIN {$this->table_apis} a ON p.api_id = a.id 
            WHERE {$where_clause}
        ";
        
        if (!empty($query_params)) {
            $count_query = $wpdb->prepare($count_query, $query_params);
        }
        
        $total_products = $wpdb->get_var($count_query);
        $total_pages = ceil($total_products / $per_page);
        
        // Get APIs for filter dropdown
        $apis = $wpdb->get_results("SELECT * FROM {$this->table_apis} ORDER BY name");
        
        include_once APM_PLUGIN_PATH . 'templates/products.php';
    }

    public function categorization_page() {
        global $wpdb;
        
        // Get mappings with category names
        $mappings = $wpdb->get_results("
            SELECT m.*, c.name as category_name 
            FROM {$this->table_mappings} m 
            LEFT JOIN {$wpdb->terms} t ON m.category_id = t.term_id 
            LEFT JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id 
            LEFT JOIN {$wpdb->terms} c ON tt.parent = c.term_id 
            WHERE tt.taxonomy = 'category'
            ORDER BY m.priority DESC, m.vision_label
        ");
        
        // Get uncategorized labels with frequency
        $uncategorized_labels = $wpdb->get_results("
            SELECT vision_label, COUNT(*) as frequency, AVG(confidence) as avg_confidence 
            FROM {$this->table_uncategorized} 
            GROUP BY vision_label 
            HAVING COUNT(*) > 1 
            ORDER BY frequency DESC
            LIMIT 100
        ");
        
        // Get stats for the categorization page
        $stats = array(
            'total_uncategorized' => $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_products} WHERE vision_processed = 0"),
            'total_mappings' => $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_mappings}"),
            'total_uncategorized_labels' => $wpdb->get_var("SELECT COUNT(DISTINCT vision_label) FROM {$this->table_uncategorized}")
        );
        
        include_once APM_PLUGIN_PATH . 'templates/categorization.php';
    }

    public function settings_page() {
        // Check if WooCommerce is active
        $woocommerce_active = class_exists('WooCommerce');
        
        // Get all categories for default category dropdown
        $categories = get_categories(array('hide_empty' => false));
        
        include_once APM_PLUGIN_PATH . 'templates/settings.php';
    }
    
    public function tools_page() {
        global $wpdb;
        
        // Get system info
        $system_info = array(
            'php_version' => phpversion(),
            'wp_version' => get_bloginfo('version'),
            'plugin_version' => APM_VERSION,
            'db_version' => get_option('apm_version'),
            'table_sizes' => array()
        );
        
        // Get table sizes
        $tables = array($this->table_apis, $this->table_products, $this->table_logs, $this->table_mappings, $this->table_uncategorized);
        
        foreach ($tables as $table) {
            $size = $wpdb->get_var("SELECT ROUND((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024, 2) FROM information_schema.TABLES WHERE TABLE_NAME = '{$table}' AND TABLE_SCHEMA = DATABASE()");
            $system_info['table_sizes'][$table] = $size ? $size . ' MB' : 'N/A';
        }
        
        include_once APM_PLUGIN_PATH . 'templates/tools.php';
    }

    public function logs_page() {
        global $wpdb;
        
        // Handle filters
        $current_action = isset($_GET['filter_action']) ? sanitize_text_field($_GET['filter_action']) : '';
        $current_api = isset($_GET['filter_api']) ? intval($_GET['filter_api']) : 0;
        $current_status = isset($_GET['filter_status']) ? sanitize_text_field($_GET['filter_status']) : '';
        $date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '';
        $date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : '';
        
        // Build query
        $where = array('1=1');
        $query_params = array();
        
        if (!empty($current_action)) {
            $where[] = "l.action = %s";
            $query_params[] = $current_action;
        }
        
        if ($current_api > 0) {
            $where[] = "l.api_id = %d";
            $query_params[] = $current_api;
        }
        
        if (!empty($current_status)) {
            $where[] = "l.status = %s";
            $query_params[] = $current_status;
        }
        
        if (!empty($date_from)) {
            $where[] = "DATE(l.created_at) >= %s";
            $query_params[] = $date_from;
        }
        
        if (!empty($date_to)) {
            $where[] = "DATE(l.created_at) <= %s";
            $query_params[] = $date_to;
        }
        
        $where_clause = implode(' AND ', $where);
        
        // Pagination
        $per_page = 50;
        $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $offset = ($paged - 1) * $per_page;
        
        // Get logs
        $logs_query = "
            SELECT l.*, a.name as api_name, p.name as product_name 
            FROM {$this->table_logs} l 
            LEFT JOIN {$this->table_apis} a ON l.api_id = a.id 
            LEFT JOIN {$this->table_products} p ON l.product_id = p.id 
            WHERE {$where_clause} 
            ORDER BY l.created_at DESC 
            LIMIT {$offset}, {$per_page}
        ";
        
        if (!empty($query_params)) {
            $logs_query = $wpdb->prepare($logs_query, $query_params);
        }
        
        $logs = $wpdb->get_results($logs_query);
        
        // Total logs for pagination
        $count_query = "
            SELECT COUNT(*) 
            FROM {$this->table_logs} l 
            LEFT JOIN {$this->table_apis} a ON l.api_id = a.id 
            LEFT JOIN {$this->table_products} p ON l.product_id = p.id 
            WHERE {$where_clause}
        ";
        
        if (!empty($query_params)) {
            $count_query = $wpdb->prepare($count_query, $query_params);
        }
        
        $total_logs = $wpdb->get_var($count_query);
        $total_pages = ceil($total_logs / $per_page);
        
        // Get APIs for filter dropdown
        $apis = $wpdb->get_results("SELECT * FROM {$this->table_apis} ORDER BY name");
        
        // Get unique actions for filter
        $actions = $wpdb->get_col("SELECT DISTINCT action FROM {$this->table_logs} ORDER BY action");
        
        include_once APM_PLUGIN_PATH . 'templates/logs.php';
    }

    public function log_action($api_id, $product_id, $action, $status, $message, $data = '') {
        global $wpdb;

        $wpdb->insert($this->table_logs, array(
            'api_id' => $api_id,
            'product_id' => $product_id,
            'action' => $action,
            'status' => $status,
            'message' => $message,
            'data' => is_array($data) ? json_encode($data) : $data
        ));
        
        if (get_option('apm_debug_mode')) {
            error_log("[APM] {$action}: {$message} - " . json_encode($data));
        }
        
        return $wpdb->insert_id;
    }

    // AJAX handlers
    public function ajax_save_api() {
        check_ajax_referer('apm_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Unauthorized', APM_TEXT_DOMAIN));
        }
        
        global $wpdb;
        
        $data = array(
            'name' => sanitize_text_field($_POST['name']),
            'url' => esc_url_raw($_POST['url']),
            'method' => sanitize_text_field($_POST['method']),
            'headers' => sanitize_textarea_field($_POST['headers']),
            'auth_type' => sanitize_text_field($_POST['auth_type']),
            'auth_data' => sanitize_text_field($_POST['auth_data']),
            'product_path' => sanitize_text_field($_POST['product_path']),
            'mapping' => wp_json_encode(json_decode(stripslashes($_POST['mapping']))),
            'publish_posts' => isset($_POST['publish_posts']) ? 1 : 0,
            'auto_categorize' => isset($_POST['auto_categorize']) ? 1 : 0,
            'pagination_type' => sanitize_text_field($_POST['pagination_type']),
            'pagination_param' => sanitize_text_field($_POST['pagination_param']),
            'page_size' => intval($_POST['page_size']),
            'max_pages' => intval($_POST['max_pages'])
        );
        
        try {
            if (!empty($_POST['id'])) {
                $api_id = intval($_POST['id']);
                $wpdb->update($this->table_apis, $data, array('id' => $api_id));
                $message = __('API updated successfully', APM_TEXT_DOMAIN);
            } else {
                $wpdb->insert($this->table_apis, $data);
                $api_id = $wpdb->insert_id;
                $message = __('API created successfully', APM_TEXT_DOMAIN);
            }
            
            $this->log_action($api_id, 0, 'save_api', 'success', $message, $data);
            wp_send_json_success($message);
            
        } catch (Exception $e) {
            $this->log_action(0, 0, 'save_api', 'error', $e->getMessage(), $data);
            wp_send_json_error($e->getMessage());
        }
    }

    public function ajax_test_api() {
        check_ajax_referer('apm_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Unauthorized', APM_TEXT_DOMAIN));
        }
        
        $url = esc_url_raw($_POST['url']);
        
        if (empty($url)) {
            wp_send_json_error(__('URL is required', APM_TEXT_DOMAIN));
        }
        
        // Make API request
        $response = wp_remote_request($url, array(
            'method' => sanitize_text_field($_POST['method']),
            'headers' => $this->parse_headers(sanitize_textarea_field($_POST['headers'])),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            wp_send_json_error($response->get_error_message());
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        // Try to parse JSON
        $json_data = json_decode($body, true);
        $is_json = (json_last_error() === JSON_ERROR_NONE);
        
        if ($is_json) {
            $formatted_body = json_encode($json_data, JSON_PRETTY_PRINT);
        } else {
            $formatted_body = $body;
        }
        
        wp_send_json_success(array(
            'status' => $status_code,
            'body' => $formatted_body,
            'is_json' => $is_json
        ));
    }

    private function parse_headers($headers_text) {
        $headers = array();
        $lines = explode("\n", $headers_text);
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            $parts = explode(':', $line, 2);
            if (count($parts) === 2) {
                $headers[trim($parts[0])] = trim($parts[1]);
            }
        }
        
        return $headers;
    }
    
    public function ajax_delete_api() {
        check_ajax_referer('apm_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Unauthorized', APM_TEXT_DOMAIN));
        }
        
        $api_id = isset($_POST['api_id']) ? intval($_POST['api_id']) : 0;
        
        if (!$api_id) {
            wp_send_json_error(__('API ID required', APM_TEXT_DOMAIN));
        }
        
        global $wpdb;
        
        // Delete API and its products
        $wpdb->delete($this->table_apis, array('id' => $api_id));
        $wpdb->delete($this->table_products, array('api_id' => $api_id));
        
        $this->log_action($api_id, 0, 'delete_api', 'success', __('API and associated products deleted', APM_TEXT_DOMAIN));
        
        wp_send_json_success(__('API deleted successfully', APM_TEXT_DOMAIN));
    }
    
    public function ajax_import_sample_data() {
        check_ajax_referer('apm_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Unauthorized', APM_TEXT_DOMAIN));
        }
        
        $this->maybe_add_sample_data();
        
        // Fetch products from the sample API
        $apis = $this->api_manager->get_apis();
        if (!empty($apis)) {
            $this->api_manager->fetch_products_from_api($apis[0]->id, true);
        }
        
        wp_send_json_success(__('Sample data imported successfully', APM_TEXT_DOMAIN));
    }
    
    public function ajax_clear_logs() {
        check_ajax_referer('apm_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Unauthorized', APM_TEXT_DOMAIN));
        }
        
        global $wpdb;
        
        $days = isset($_POST['days']) ? intval($_POST['days']) : 30;
        
        if ($days > 0) {
            $wpdb->query($wpdb->prepare(
                "DELETE FROM {$this->table_logs} WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
                $days
            ));
        } else {
            $wpdb->query("TRUNCATE TABLE {$this->table_logs}");
        }
        
        $this->log_action(0, 0, 'clear_logs', 'success', __('Logs cleared', APM_TEXT_DOMAIN));
        
        wp_send_json_success(__('Logs cleared successfully', APM_TEXT_DOMAIN));
    }
    
    public function ajax_optimize_tables() {
        check_ajax_referer('apm_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Unauthorized', APM_TEXT_DOMAIN));
        }
        
        global $wpdb;
        
        $tables = array(
            $this->table_apis,
            $this->table_products,
            $this->table_logs,
            $this->table_mappings,
            $this->table_uncategorized
        );
        
        foreach ($tables as $table) {
            $wpdb->query("OPTIMIZE TABLE {$table}");
        }
        
        $this->log_action(0, 0, 'optimize_tables', 'success', __('Database tables optimized', APM_TEXT_DOMAIN));
        
        wp_send_json_success(__('Tables optimized successfully', APM_TEXT_DOMAIN));
    }
    
    // Placeholder methods for other functionality
    public function daily_fetch_all_apis() {
        $this->api_manager->fetch_all_apis();
    }
    
    public function hourly_processing() {
        // Process uncategorized products in batches
        $this->vision_processor->process_uncategorized_batch();
        
        // Clean up old logs
        if (get_option('apm_log_retention_days', 30) > 0) {
            global $wpdb;
            $wpdb->query($wpdb->prepare(
                "DELETE FROM {$this->table_logs} WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
                get_option('apm_log_retention_days', 30)
            ));
        }
    }
    
    public function process_product_with_vision($product_id) {
        $this->vision_processor->process_product($product_id);
    }
    
    public function handle_new_product($post_id, $post, $update) {
        if ($post->post_type === 'product' || $post->post_type === 'post') {
            $this->product_creator->handle_product_update($post_id, $post, $update);
        }
    }
    
    public function register_rest_routes() {
        register_rest_route('apm/v1', '/products', array(
            'methods' => 'GET',
            'callback' => array($this, 'rest_get_products'),
            'permission_callback' => '__return_true'
        ));
        
        register_rest_route('apm/v1', '/apis', array(
            'methods' => 'GET',
            'callback' => array($this, 'rest_get_apis'),
            'permission_callback' => array($this, 'rest_permission_check')
        ));
    }
    
    public function rest_get_products($request) {
        global $wpdb;
        
        $params = $request->get_params();
        $limit = isset($params['limit']) ? intval($params['limit']) : 10;
        $offset = isset($params['offset']) ? intval($params['offset']) : 0;
        
        $products = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->table_products} ORDER BY created_at DESC LIMIT %d, %d",
            $offset, $limit
        ));
        
        return rest_ensure_response($products);
    }
    
    public function rest_get_apis($request) {
        global $wpdb;
        
        $apis = $wpdb->get_results("SELECT id, name, url, status, last_fetch FROM {$this->table_apis}");
        
        return rest_ensure_response($apis);
    }
    
    public function rest_permission_check($request) {
        return current_user_can('manage_options');
    }
    
    // Getters for external classes
    public function get_table($table_name) {
        switch ($table_name) {
            case 'apis': return $this->table_apis;
            case 'products': return $this->table_products;
            case 'logs': return $this->table_logs;
            case 'mappings': return $this->table_mappings;
            case 'uncategorized': return $this->table_uncategorized;
            default: return null;
        }
    }
    
    public function get_api_manager() {
        return $this->api_manager;
    }
    
    public function get_vision_processor() {
        return $this->vision_processor;
    }
    
    public function get_product_creator() {
        return $this->product_creator;
    }
    
    // Additional AJAX handlers (placeholders)
    public function ajax_get_api() {
        check_ajax_referer('apm_nonce', 'nonce');
        // Implementation for getting API
    }
    
    public function ajax_test_vision() {
        check_ajax_referer('apm_nonce', 'nonce');
        // Implementation for testing vision API
    }
    
    public function ajax_categorize_product() {
        check_ajax_referer('apm_nonce', 'nonce');
        // Implementation for categorizing a product
    }
    
    public function ajax_bulk_categorize() {
        check_ajax_referer('apm_nonce', 'nonce');
        // Implementation for bulk categorization
    }
    
    public function ajax_save_mapping() {
        check_ajax_referer('apm_nonce', 'nonce');
        // Implementation for saving mapping
    }
    
    public function ajax_delete_mapping() {
        check_ajax_referer('apm_nonce', 'nonce');
        // Implementation for deleting mapping
    }
    
    public function ajax_get_suggestions() {
        check_ajax_referer('apm_nonce', 'nonce');
        // Implementation for getting suggestions
    }
    
    public function ajax_bulk_fetch_all() {
        check_ajax_referer('apm_nonce', 'nonce');
        // Implementation for bulk fetching all
    }
    
    public function ajax_bulk_categorize_all() {
        check_ajax_referer('apm_nonce', 'nonce');
        // Implementation for bulk categorizing all
    }
}

// Initialize plugin
new AdvancedProductManager();