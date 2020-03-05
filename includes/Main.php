<?php

namespace RRZE\FAQ;

defined('ABSPATH') || exit;

use RRZE\FAQ\Settings;
use RRZE\FAQ\Shortcode;
use function RRZE\FAQ\Config\deleteLogfile;


/**
 * Hauptklasse (Main)
 */
class Main {
    /**
     * Der vollständige Pfad- und Dateiname der Plugin-Datei.
     * @var string
     */
    protected $pluginFile;

    /**
     * Variablen Werte zuweisen.
     * @param string $pluginFile Pfad- und Dateiname der Plugin-Datei
     */
    public function __construct($pluginFile) {
        $this->pluginFile = $pluginFile;
    }

    /**
     * Es wird ausgeführt, sobald die Klasse instanziiert wird.
     */
    public function onLoaded() {
        add_action( 'wp_enqueue_scripts', [$this, 'enqueueScripts'] );
        // actions: update, sync, delete logfile
        add_action( 'update_option_rrze-faq', [$this, 'doIt'] ); 
        // Auto-Sync
        add_action( 'rrze_faq_auto_update', [$this, 'cronSync'] );
        // Editable FAQ (if synced: non-editable / if self-written: editable)
        add_action( 'add_meta_boxes', [$this, 'add_content_box'] );
        add_action( 'edit_form_after_title', [$this, 'toggle_editor'] );
        add_filter( 'use_block_editor_for_post', [$this, 'gutenberg_post_meta'], 10, 2 );

        // Settings-Klasse wird instanziiert.
        $settings = new Settings($this->pluginFile);
        $settings->onLoaded();

        include_once( __DIR__ . '/posttype/rrze-faq-posttype.php' );
        include_once( __DIR__ . '/posttype/rrze-faq-taxonomy.php' );
        include_once( __DIR__ . '/posttype/rrze-faq-manage-posts.php' );
        // include_once( __DIR__ . '/posttype/rrze-faq-metabox.php');
        include_once( __DIR__ . '/posttype/rrze-faq-admin.php' );
        include_once( __DIR__ . '/posttype/rrze-faq-helper.php' );
        include_once( __DIR__ . '/REST-API/rrze-faq-rest-filter.php' );
        include_once( __DIR__ . '/REST-API/rrze-faq-posttype-rest.php' );
        include_once( __DIR__ . '/faq/rrze-faq-list-table-helper.php' );
        include_once( __DIR__ . '/faq/rrze-faq-list-table.php' );
        include_once( __DIR__ . '/domain/rrze-faq-domain-get.php' );
        include_once( __DIR__ . '/domain/rrze-faq-domain-list.php' );
        new DOMAIN_FAQ();
        include_once( __DIR__ . '/domain/rrze-faq-domain-add.php' );
        new AddFaqDomain();

        // Shortcode wird eingebunden.
        include 'Shortcode.php';
        $shortcode = new Shortcode();
    }

    /**
     * Enqueue der globale Skripte.
     */
    public function enqueueScripts() {
        wp_register_style('rrze-faq', plugins_url('assets/css/plugin.css', plugin_basename($this->pluginFile)));
    }


    /**
     * Trigger editable of CPT faq: 
     * synced FAQ should not be editable
     * self-written FAQ have to be editable
     */
    public function gutenberg_post_meta( $can_edit, $post)  {
        $ret = TRUE;
        if ( get_post_meta( $post->ID, 'source', TRUE ) ) {
            $ret = FALSE;
        }
        return $ret;
    }
    public function toggle_editor( $post ) {
        if ( $post->post_type == 'faq' ) {
            $source = get_post_meta( $post->ID, "source", true );
            if ( $source ){
                remove_post_type_support( 'faq', 'editor' );
                remove_post_type_support( 'faq', 'title' );
            } else {
                remove_meta_box( 'read_only_content_box', 'faq', 'normal' );
            }
        }
    }
    public function add_content_box() {
        add_meta_box(
            'read_only_content_box', // id, used as the html id att
            '&nbsp;', // meta box title
            [$this, 'read_only_cb'], // callback function, spits out the content
            'faq', // post type or page. This adds to posts only
            'normal', // context, where on the screen
            'high' // priority, where should this go in the context
        );
    }
    public function read_only_cb( $post ) {
        echo '<h1>' . $post->post_title . '</h1><br>' . apply_filters( 'the_content', $post->post_content );
    }


    /**
     * Click on buttons "update", "sync" or "delete logfile"
     */
    public function doIt() {
        if ( isset( $_GET['sync'] ) ) {
            $this->jobCron();
            $sync = new Sync();
            $sync->doSync( 'manual' );
        } elseif ( isset( $_GET['del'] ) ) {
            deleteLogfile();
        }
    }

    public function cronSync() {
        // Wochentags, tagsüber 8-18 Uhr alle 3 Stunden, danach und am Wochenende: Alle 6 Stunden
        $sync = [
                'workdays' => [ 2, 8, 11, 14, 17, 20 ],
                'weekend' => [ 6, 12, 18, 0 ] 
        ];

        date_default_timezone_set('Europe/Berlin');
        $today = getdate();
        $weekday = $today["wday"]; // 0=sunday
        $hour = $today["hours"]; // 0 - 23

        if ( $weekday > 0 && $weekday < 6 ){
            if ( in_array( $hour, $sync["workdays"] ) ) {
                $sync = new Sync();
                $sync->doSync( 'automatic' );
            }
        } else {
            if ( in_array( $hour, $sync["weekend"] ) ) {
                $sync = new Sync();
                $sync->doSync( 'automatic' );
            }
        }
    }

    public function jobCron() {
        $options = get_option('rrze-faq' );
        if (isset($options['sync_sync_check'])
            && $options['sync_sync_check'] != 'on') {
            if (wp_next_scheduled( 'rrze_faq_auto_update' )) {
                wp_clear_scheduled_hook( 'rrze_faq_auto_update' );
            }
            return;
        }

        //Use wp_next_scheduled to check if the event is already scheduled*/
        if( !wp_next_scheduled( 'rrze_faq_auto_update' )) {
            //Schedule the event for right now, then to repeat daily using the hook 'cris_create_cron'
            date_default_timezone_set('Europe/Berlin');
            wp_schedule_event( strtotime('today 13:00'), 'hourly', 'rrze_faq_auto_update' );
            $timestamp = wp_next_scheduled( 'rrze_faq_auto_update' );
            if ($timestamp) {
                $message = __('Settings saved', 'rrze-faq' )
                    . '<br />'
                    . __('Next automatically synchronization:', 'rrze-faq' ) . ' '
                    . get_date_from_gmt( date( 'Y-m-d H:i:s', $timestamp ), 'd.m.Y - H:i' );
                add_settings_error('AutoSyncComplete', 'autosynccomplete', $message , 'updated' );
                settings_errors();
            }
        }
    }

}
