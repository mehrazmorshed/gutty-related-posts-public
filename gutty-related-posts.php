<?php
/*
Plugin Name: Gutty Related Posts
Plugin URI: https://guttypress.com/
Description: Gutty Related Posts is the best way to show related and relevant content on your WordPress site using the power of the Block Editor and Full Site Editing.
Version: 1.3
Author: Team GuttyPress
Author URI: https://profiles.wordpress.org/guttypress/
License: GPL2
Text Domain: gutty-related-posts
Domain Path: /languages
*/

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('GUTTY_RELATED_POSTS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('GUTTY_RELATED_POSTS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('GUTTY_RELATED_POSTS_VERSION', '1.3');
define('GUTTY_RELATED_POSTS_STOP_WORDS', GUTTY_RELATED_POSTS_PLUGIN_DIR . 'includes/stop-words');

// Include required files
require_once GUTTY_RELATED_POSTS_PLUGIN_DIR . 'includes/class-grp-stop-words.php';
require_once GUTTY_RELATED_POSTS_PLUGIN_DIR . 'includes/class-grp-keyword-handler.php';
require_once GUTTY_RELATED_POSTS_PLUGIN_DIR . 'includes/class-grp-rest-api.php';
require_once GUTTY_RELATED_POSTS_PLUGIN_DIR . 'admin/class-grp-settings.php';
require_once GUTTY_RELATED_POSTS_PLUGIN_DIR . 'includes/class-grp-block.php';
require_once GUTTY_RELATED_POSTS_PLUGIN_DIR . 'includes/grp-shortcode.php';


// Activation hook to create custom tables
function gutty_related_posts_activate_plugin() {
    $grp_keyword_handler = new GuttyRelatedPosts\KeywordHandler\GuttyRelatedPosts_Keyword_Handler();
    $grp_keyword_handler->create_keyword_table();
}
register_activation_hook(__FILE__, 'gutty_related_posts_activate_plugin');

function gutty_related_posts_create_tables_multisite($blog_id) {
    if (is_plugin_active_for_network(plugin_basename(__FILE__))) {
        switch_to_blog($blog_id);
        gutty_related_posts_activate_plugin();
        restore_current_blog();
    }
}
add_action('wpmu_new_blog', 'gutty_related_posts_create_tables_multisite');

// Load plugin textdomain for translations
function gutty_related_posts_load_textdomain() {
    load_plugin_textdomain('gutty-related-posts', false, dirname(plugin_basename(__FILE__)) . '/languages/');
}
add_action('init', 'gutty_related_posts_load_textdomain');

// Enqueue styles & scripts
function gutty_related_posts_scripts() {
	wp_register_style('gutty-related-posts-shortcode', plugins_url('assets/css/gutty-related-posts.css', __FILE__), [], GUTTY_RELATED_POSTS_VERSION);
}

add_action('wp_enqueue_scripts', 'gutty_related_posts_scripts', 10, 1);

function gutty_related_posts_admin_scripts() {
	wp_enqueue_style('gutty-related-posts-admin', plugins_url('assets/css/gutty-related-posts-admin.css', __FILE__), [], GUTTY_RELATED_POSTS_VERSION);
}

add_action('admin_enqueue_scripts', 'gutty_related_posts_admin_scripts', 10, 1);

// Shortcut settings page
function gutty_related_posts_plugin_action_links($links, $file) {
	static $this_plugin;

	if ( ! $this_plugin) {
		$this_plugin = plugin_basename(__FILE__);
	}

	if ($file == $this_plugin) {
		$settings_link = '<a href="' . esc_url(admin_url('options-general.php?page=guttyrelatedposts-settings')) . '">' . esc_html__('Settings', 'gutty-related-posts') . '</a>';
		$website_link  = '<a href="https://guttypress.com/" target="_blank">' . esc_html__('Docs', 'gutty-related-posts') . '</a>';

        array_unshift($links, $settings_link, $website_link); 
	}

	return $links;
}
add_filter('plugin_action_links', 'gutty_related_posts_plugin_action_links', 10, 2);

// Notice to invite user init the database
function gutty_related_posts_notice_init() {
    global $wpdb;

    $screen_id = get_current_screen();
    if ('settings_page_guttyrelatedposts-settings' === $screen_id->base) {
        return;
    }

    // Check if the "gutty_related_posts_keywords" table has any entries
    $has_entries = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}gutty_related_posts_keywords");

    // Display notice only if the table is empty
    if ($has_entries > 0) {
        return;
    }

    $class = 'notice notice-warning';
    $message = '<strong>' . esc_html__('Gutty Related Posts', 'gutty-related-posts') . '</strong>';
    $message .= '<p>' . esc_html__('Please go to the settings to generate a relationship table for your content, allowing related articles to be displayed.', 'gutty-related-posts') . '</p>';
    $message .= '<a href="' . esc_url(admin_url('options-general.php?page=guttyrelatedposts-settings')) . '" class="button"> ' . esc_html__('Generate related posts', 'gutty-related-posts') . '</a>';

    printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), wp_kses_post($message));
}
add_action('admin_notices', 'gutty_related_posts_notice_init');
