<?php

namespace RRZE\FAQ;

defined('ABSPATH') || exit;

use function RRZE\FAQ\Config\logIt;
use function RRZE\FAQ\Config\deleteLogfile;
use RRZE\FAQ\API;
use RRZE\FAQ\CPT;
use RRZE\FAQ\Layout;
use RRZE\FAQ\RESTAPI;
use RRZE\FAQ\Settings;
use RRZE\FAQ\Shortcode;
use RRZE\FAQ\Widget;


/**
 * Hauptklasse (Main)
 */
class Main {
    /**
     * Der vollständige Pfad- und Dateiname der Plugin-Datei.
     * @var string
     */
    protected $pluginFile;

    protected $settings;

    /**
     * Variablen Werte zuweisen.
     * @param string $pluginFile Pfad- und Dateiname der Plugin-Datei
     */
    public function __construct($pluginFile) {
        $this->pluginFile = $pluginFile;

        Shortcode::instance()->loaded();    
    }

    /**
     * Es wird ausgeführt, sobald die Klasse instanziiert wird.
     */
    public function onLoaded() {
        add_action( 'wp_enqueue_scripts', [$this, 'enqueueScripts'] );
        add_action( 'admin_enqueue_scripts', [$this, 'enqueueScripts'] );

        // Actions: sync, add domain, delete domain, delete logfile
        add_action( 'update_option_rrze-faq', [$this, 'checkSync'] );
        add_filter( 'pre_update_option_rrze-faq',  [$this, 'switchTask'], 10, 1 );

        $cpt = new CPT(); 

        $this->settings = new Settings($this->pluginFile);
        $this->settings->onLoaded();

        $restAPI = new RESTAPI();
        $layout = new Layout();
        $shortcode = new Shortcode();

        // Widget
        add_action( 'widgets_init', [$this, 'loadWidget'] );    

        // Auto-Sync
        add_action( 'rrze_faq_auto_sync', [$this, 'runFAQCronjob'] );
    }


    public function loadWidget() {
        $myWidget = new FAQWidget();
        register_widget($myWidget);
    }

    /**
     * Enqueue der globale Skripte.
     */
    public function enqueueScripts() {
        wp_register_style('rrze-faq-style', plugins_url('assets/css/rrze-faq.css', plugin_basename($this->pluginFile)));
        wp_enqueue_style('rrze-faq-style');
    }


    /**
     * Click on buttons "sync", "add domain", "delete domain" or "delete logfile"
     */
    public function switchTask( $options ) {
        $api = new API();
        $domains = $api->getDomains();

        // get stored options because they are generated and not defined in config.php
        $storedOptions = get_option( 'rrze-faq' );

        if (is_array($storedOptions) && is_array($options)){
            $options = array_merge($storedOptions, $options);
        }

        $tab = ( isset($_GET['doms'] ) ? 'doms' : ( isset( $_GET['sync'] ) ? 'sync' : ( isset( $_GET['del'] ) ? 'del' : '' ) ) );

        switch ( $tab ){
            case 'doms':
                if ( $options['doms_new_name'] && $options['doms_new_url'] ){
                    // add new domain
                    $aRet = $api->setDomain( $options['doms_new_name'], $options['doms_new_url'], $domains );

                    if ( $aRet['status'] ){
                        // url is correct, RRZE-FAQ at given url is in use and shortname is new
                        $domains[$aRet['ret']['cleanShortname']] = $aRet['ret']['cleanUrl'];
                    }else{
                        add_settings_error( 'doms_new_url', 'doms_new_error', $aRet['ret'], 'error' );        
                    }
                } else {
                    // delete domain(s)
                    foreach ( $_POST as $key => $url ){
                        if ( substr( $key, 0, 11 ) === "del_domain_" ){
                            if (($shortname = array_search($url, $domains)) !== false) {
                                unset($domains[$shortname]);
                                $api->deleteFAQ( $shortname );
                            }
                            unset($options['faqsync_categories_' . $shortname]);
                            unset($options['faqsync_donotsync_' . $shortname]);
                        }
                    }
                }    
            break;
            case 'sync':
                $options['timestamp'] = time();
            break;
            case 'del':
                deleteLogfile();
            break;
        }

        if ( !$domains ){
            // unset this option because $api->getDomains() checks isset(..) because of asort(..)
            unset( $options['registeredDomains'] );
        } else {
            $options['registeredDomains'] = $domains;
        }

        // we don't need these temporary fields to be stored in database table options
        // domains are stored as shortname and url in registeredDomains
        // categories and donotsync are stored in faqsync_categories_<SHORTNAME> and faqsync_donotsync_<SHORTNAME>
        unset($options['doms_new_name']);
        unset($options['doms_new_url']);
        unset($options['faqsync_shortname']);
        unset($options['faqsync_url']);
        unset($options['faqsync_categories']);
        unset($options['faqsync_donotsync']);
        unset($options['faqsync_hr']);

        return $options;
    }


    public function checkSync() {
        if ( isset( $_GET['sync'] ) ){
            $sync = new Sync();
            $sync->doSync( 'manual' );

            $this->setFAQCronjob();
        }
    }

    public function runFAQCronjob() {
        // sync hourly
        $sync = new Sync();
        $sync->doSync( 'automatic' );
    }

    public function setFAQCronjob() {
        date_default_timezone_set( 'Europe/Berlin' );

        $options = get_option( 'rrze-faq' );

        if ( $options['faqsync_autosync'] != 'on' ) {
            wp_clear_scheduled_hook( 'rrze_faq_auto_sync' );
            return;
        }

        $nextcron = 0;
        switch( $options['faqsync_frequency'] ){
            case 'daily' : $nextcron = 86400;
                break;
            case 'twicedaily' : $nextcron = 43200;
                break;
        }

        $nextcron += time();
        wp_clear_scheduled_hook( 'rrze_faq_auto_sync' );
        wp_schedule_event( $nextcron, $options['faqsync_frequency'], 'rrze_faq_auto_sync' );

        $timestamp = wp_next_scheduled( 'rrze_faq_auto_sync' );
        $message = __( 'Next automatically synchronization:', 'rrze-faq' ) . ' ' . date( 'd.m.Y H:i:s', $timestamp );
        add_settings_error( 'AutoSyncComplete', 'autosynccomplete', $message , 'updated' );
        settings_errors();
    }
}
