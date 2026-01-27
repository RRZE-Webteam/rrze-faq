<?php

/*
Plugin Name:     RRZE FAQ
Plugin URI:      https://gitlab.rrze.fau.de/rrze-webteam/rrze-faq
Description:     Plugin, um FAQ zu erstellen und aus dem FAU-Netzwerk zu synchronisieren. Verwendbar als Shortcode, Block oder Widget. 
Version:         5.4.46
Requires at least: 6.1
Requires PHP:      8.2
Author:          RRZE Webteam
Author URI:      https://blogs.fau.de/webworking/
License:         GNU General Public License v2
License URI:     http://www.gnu.org/licenses/gpl-2.0.html
Domain Path:     /languages
Text Domain:     rrze-faq
 */

namespace RRZE\FAQ;

defined('ABSPATH') || exit;

use RRZE\FAQ\Main;

$s = array(
    '/^((http|https):\/\/)?(www.)+/i',
    '/\//',
    '/[^A-Za-z0-9\-]/',
);
$r = array(
    '',
    '-',
    '-',
);

define('FAQLOGFILE', plugin_dir_path(__FILE__) . 'rrze-faq-' . preg_replace($s, $r, get_bloginfo('url')) . '.log');

const RRZE_PHP_VERSION = '8.0';
const RRZE_WP_VERSION = '6.1';
const RRZE_PLUGIN_FILE = __FILE__;

// Automatische Laden von Klassen.
spl_autoload_register(function ($class) {
    $prefix = __NAMESPACE__;
    $base_dir = __DIR__ . '/includes/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

// Registriert die Plugin-Funktion, die bei Aktivierung des Plugins ausgeführt werden soll.
register_activation_hook(__FILE__, __NAMESPACE__ . '\activation');
// Registriert die Plugin-Funktion, die ausgeführt werden soll, wenn das Plugin deaktiviert wird.
register_deactivation_hook(__FILE__, __NAMESPACE__ . '\deactivation');
// Wird aufgerufen, sobald alle aktivierten Plugins geladen wurden.
add_action('plugins_loaded', __NAMESPACE__ . '\loaded');

/**
 * Einbindung der Sprachdateien.
 */
function load_textdomain()
{
    load_plugin_textdomain('rrze-faq', false, sprintf('%s/languages/', dirname(plugin_basename(__FILE__))));
}

/**
 * Überprüft die minimal erforderliche PHP- u. WP-Version.
 */
function system_requirements()
{
    $error = '';
    if (version_compare(PHP_VERSION, RRZE_PHP_VERSION, '<')) {
        /* translators: 1: current PHP version, 2: required PHP version */
        $error = sprintf(__('The server is running PHP version %1$s. The Plugin requires at least PHP version %2$s.', 'rrze-faq'), PHP_VERSION, RRZE_PHP_VERSION);
    } elseif (version_compare($GLOBALS['wp_version'], RRZE_WP_VERSION, '<')) {
        /* translators: 1: current WordPress version, 2: required WordPress version */
        $error = sprintf(__('The server is running WordPress version %1$s. The Plugin requires at least WordPress version %2$s.', 'rrze-faq'), $GLOBALS['wp_version'], RRZE_WP_VERSION);
    }
    return $error;
}

/**
 * Wird durchgeführt, nachdem das Plugin aktiviert wurde.
 */
function activation()
{
    // Sprachdateien werden eingebunden.
    load_textdomain();

    // Überprüft die minimal erforderliche PHP- u. WP-Version.
    // Wenn die Überprüfung fehlschlägt, dann wird das Plugin automatisch deaktiviert.
    if ($error = system_requirements()) {
        deactivate_plugins(plugin_basename(__FILE__), false, true);
        wp_die(esc_html($error));
    }

    // Ab hier können die Funktionen hinzugefügt werden,
    // die bei der Aktivierung des Plugins aufgerufen werden müssen.
    // Bspw. wp_schedule_event, flush_rewrite_rules, etc.
}

/**
 * Wird durchgeführt, nachdem das Plugin deaktiviert wurde.
 */
function deactivation()
{
    // Hier können die Funktionen hinzugefügt werden, die
    // bei der Deaktivierung des Plugins aufgerufen werden müssen.
    // Bspw. delete_option, wp_clear_scheduled_hook, flush_rewrite_rules, etc.

    // delete_option(Options::get_option_name());
    wp_clear_scheduled_hook('rrze_faq_auto_sync');
    flush_rewrite_rules();
}


function rrze_faq_cpt_update()
{
    global $wpdb;

    if (get_option('rrze_faq_cpt_update_done')) {
        return;
    }

    $wpdb->query("
        UPDATE {$wpdb->term_taxonomy} tt
        INNER JOIN {$wpdb->term_relationships} tr ON tr.term_taxonomy_id = tt.term_taxonomy_id
        INNER JOIN {$wpdb->posts} p ON tr.object_id = p.ID
        SET tt.taxonomy = 'rrze_faq_category'
        WHERE p.post_type = 'faq'
        AND tt.taxonomy = 'faq_category'
    ");

    $wpdb->query("
        UPDATE {$wpdb->term_taxonomy} tt
        INNER JOIN {$wpdb->term_relationships} tr ON tr.term_taxonomy_id = tt.term_taxonomy_id
        INNER JOIN {$wpdb->posts} p ON tr.object_id = p.ID
        SET tt.taxonomy = 'rrze_faq_tag'
        WHERE p.post_type = 'faq'
        AND tt.taxonomy = 'faq_tag'
    ");

    $wpdb->update(
        $wpdb->posts,
        ['post_type' => 'rrze_faq'],
        ['post_type' => 'faq']
    );

    wp_cache_flush();
    flush_rewrite_rules();

    update_option('rrze_faq_cpt_update_done', 1);
}

function rrze_faq_init()
{
    register_block_type(__DIR__ . '/build');
    $script_handle = generate_block_asset_handle('create-block/rrze-faq', 'editorScript');
    wp_set_script_translations($script_handle, 'rrze-faq', plugin_dir_path(__FILE__) . 'languages');
}

/**
 * Wird durchgeführt, nachdem das WP-Grundsystem hochgefahren
 * und alle Plugins eingebunden wurden.
 */
function loaded()
{
    // Sprachdateien werden eingebunden.
    load_textdomain();

    // Überprüft die minimal erforderliche PHP- u. WP-Version.
    if ($error = system_requirements()) {
        include_once ABSPATH . 'wp-admin/includes/plugin.php';
        $plugin_data = get_plugin_data(__FILE__);
        $plugin_name = $plugin_data['Name'];
        $tag = is_network_admin() ? 'network_admin_notices' : 'admin_notices';
        add_action($tag, function () use ($plugin_name, $error) {
            printf('<div class="notice notice-error"><p>%1$s: %2$s</p></div>', esc_html($plugin_name), esc_html($error));
        });
    } else {
        // Hauptklasse (Main) wird instanziiert.
        $main = new Main(__FILE__);
        $main->onLoaded();
        add_action('init', __NAMESPACE__ . '\rrze_faq_cpt_update');
    }

    add_action('init', __NAMESPACE__ . '\rrze_faq_init');

}
