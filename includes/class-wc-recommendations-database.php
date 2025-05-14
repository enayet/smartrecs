<?php
/**
 * Database operations for the plugin.
 *
 * @since      1.0.0
 * @package    WC_Recommendations
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Database operations for the plugin.
 *
 * Handles the creation and management of database tables.
 *
 * @since      1.0.0
 * @package    WC_Recommendations
 */
class WC_Recommendations_Database {

    /**
     * Database version
     */
    const DB_VERSION = '1.0';
    
    /**
     * Table names
     */
    public static function get_table_names() {
        global $wpdb;
        
        return array(
            'interactions' => $wpdb->prefix . 'wc_recommendation_interactions',
            'purchases'    => $wpdb->prefix . 'wc_recommendation_purchases',
            'tracking'     => $wpdb->prefix . 'wc_recommendation_tracking'
        );
    }

    /**
     * Create the necessary database tables.
     *
     * @since    1.0.0
     */
    public static function create_tables() {
        global $wpdb;
        
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        
        $charset_collate = $wpdb->get_charset_collate();
        $tables = self::get_table_names();
        
        // Interactions table (views, add to cart, etc)
        $sql = "CREATE TABLE " . $tables['interactions'] . " (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            interaction_type varchar(50) NOT NULL,
            product_id bigint(20) NOT NULL,
            user_id bigint(20) NOT NULL DEFAULT 0,
            session_id varchar(100) NOT NULL DEFAULT '',
            quantity int(11) NOT NULL DEFAULT 1,
            created_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY product_id (product_id),
            KEY user_id (user_id),
            KEY session_id (session_id),
            KEY interaction_type (interaction_type)
        ) $charset_collate;";
        dbDelta($sql);
        
        // Purchases table
        $sql = "CREATE TABLE " . $tables['purchases'] . " (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            order_id bigint(20) NOT NULL,
            product_id bigint(20) NOT NULL,
            user_id bigint(20) NOT NULL DEFAULT 0,
            quantity int(11) NOT NULL DEFAULT 1,
            price decimal(10,2) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY order_id (order_id),
            KEY product_id (product_id),
            KEY user_id (user_id)
        ) $charset_collate;";
        dbDelta($sql);
        
        // Impression and click tracking
        $sql = "CREATE TABLE " . $tables['tracking'] . " (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            event_type varchar(50) NOT NULL,
            recommendation_type varchar(50) NOT NULL,
            product_id bigint(20) NOT NULL,
            recommended_product_id bigint(20) NOT NULL,
            user_id bigint(20) NOT NULL DEFAULT 0,
            session_id varchar(100) NOT NULL DEFAULT '',
            placement varchar(50) NOT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY event_type (event_type),
            KEY recommendation_type (recommendation_type),
            KEY product_id (product_id),
            KEY recommended_product_id (recommended_product_id)
        ) $charset_collate;";
        dbDelta($sql);
        

        

        

        
        // Update version
        update_option('wc_recommendations_db_version', self::DB_VERSION);
    }
    
    /**
     * Clean up old data based on retention settings
     *
     * @since    1.0.0
     */
    public static function cleanup_old_data() {
        global $wpdb;
        
        $settings = get_option('wc_recommendations_settings', array());
        $retention_days = isset($settings['data_retention_days']) ? intval($settings['data_retention_days']) : 90;
        
        if ($retention_days <= 0) {
            return; // No cleanup if retention is set to keep forever
        }
        
        $tables = self::get_table_names();
        $date_threshold = date('Y-m-d H:i:s', strtotime('-' . $retention_days . ' days'));
        
        // Clean up old interaction data
        $wpdb->query($wpdb->prepare(
            "DELETE FROM " . $tables['interactions'] . " WHERE created_at < %s",
            $date_threshold
        ));
        
        // Clean up old tracking data
        $wpdb->query($wpdb->prepare(
            "DELETE FROM " . $tables['tracking'] . " WHERE created_at < %s",
            $date_threshold
        ));
        
        // Clean up old A/B test impressions
        $wpdb->query($wpdb->prepare(
            "DELETE FROM " . $tables['ab_impressions'] . " WHERE created_at < %s",
            $date_threshold
        ));
    }
    
    /**
     * Get interaction data
     *
     * @since    1.0.0
     * @param    string    $type       The interaction type to retrieve
     * @param    array     $args       Additional query arguments
     * @return   array                 The interaction data
     */
    public static function get_interactions($type = null, $args = array()) {
        global $wpdb;
        
        $tables = self::get_table_names();
        $table = $tables['interactions'];
        
        $query = "SELECT * FROM $table WHERE 1=1";
        $query_args = array();
        
        // Filter by interaction type
        if (!empty($type)) {
            $query .= " AND interaction_type = %s";
            $query_args[] = $type;
        }
        
        // Filter by product ID
        if (!empty($args['product_id'])) {
            $query .= " AND product_id = %d";
            $query_args[] = $args['product_id'];
        }
        
        // Filter by user ID
        if (!empty($args['user_id'])) {
            $query .= " AND user_id = %d";
            $query_args[] = $args['user_id'];
        }
        
        // Filter by session ID
        if (!empty($args['session_id'])) {
            $query .= " AND session_id = %s";
            $query_args[] = $args['session_id'];
        }
        
        // Filter by date range
        if (!empty($args['date_from'])) {
            $query .= " AND created_at >= %s";
            $query_args[] = $args['date_from'];
        }
        
        if (!empty($args['date_to'])) {
            $query .= " AND created_at <= %s";
            $query_args[] = $args['date_to'];
        }
        
        // Order
        $query .= " ORDER BY created_at DESC";
        
        // Limit
        if (!empty($args['limit'])) {
            $query .= " LIMIT %d";
            $query_args[] = $args['limit'];
        }
        
        // Prepare and execute query
        if (!empty($query_args)) {
            $query = $wpdb->prepare($query, $query_args);
        }
        
        return $wpdb->get_results($query);
    }
    
    /**
     * Insert interaction data
     *
     * @since    1.0.0
     * @param    string    $type       The interaction type
     * @param    int       $product_id The product ID
     * @param    int       $user_id    The user ID (0 for anonymous)
     * @param    string    $session_id The session ID
     * @param    int       $quantity   The quantity (default 1)
     * @return   int                   The inserted row ID
     */
    public static function insert_interaction($type, $product_id, $user_id = 0, $session_id = '', $quantity = 1) {
        global $wpdb;
        
        $tables = self::get_table_names();
        
        $wpdb->insert(
            $tables['interactions'],
            array(
                'interaction_type' => $type,
                'product_id'       => $product_id,
                'user_id'          => $user_id,
                'session_id'       => $session_id,
                'quantity'         => $quantity,
                'created_at'       => current_time('mysql', true)
            )
        );
        
        return $wpdb->insert_id;
    }
    
    /**
     * Insert purchase data
     *
     * @since    1.0.0
     * @param    int       $order_id   The order ID
     * @param    int       $product_id The product ID
     * @param    int       $user_id    The user ID
     * @param    int       $quantity   The quantity
     * @param    float     $price      The price
     * @return   int                   The inserted row ID
     */
    public static function insert_purchase($order_id, $product_id, $user_id, $quantity, $price) {
        global $wpdb;
        
        $tables = self::get_table_names();
        
        $wpdb->insert(
            $tables['purchases'],
            array(
                'order_id'    => $order_id,
                'product_id'  => $product_id,
                'user_id'     => $user_id,
                'quantity'    => $quantity,
                'price'       => $price,
                'created_at'  => current_time('mysql', true)
            )
        );
        
        return $wpdb->insert_id;
    }
    
    /**
     * Insert tracking data
     *
     * @since    1.0.0
     * @param    string    $event_type           The event type (impression, click)
     * @param    string    $recommendation_type  The recommendation type
     * @param    int       $product_id           The main product ID
     * @param    int       $recommended_id       The recommended product ID
     * @param    int       $user_id              The user ID
     * @param    string    $session_id           The session ID
     * @param    string    $placement            The placement location
     * @return   int                             The inserted row ID
     */
    public static function insert_tracking($event_type, $recommendation_type, $product_id, $recommended_id, $user_id = 0, $session_id = '', $placement = '') {
        global $wpdb;
        
        $tables = self::get_table_names();
        
        $wpdb->insert(
            $tables['tracking'],
            array(
                'event_type'             => $event_type,
                'recommendation_type'    => $recommendation_type,
                'product_id'             => $product_id,
                'recommended_product_id' => $recommended_id,
                'user_id'                => $user_id,
                'session_id'             => $session_id,
                'placement'              => $placement,
                'created_at'             => current_time('mysql', true)
            )
        );
        
        return $wpdb->insert_id;
    }
}