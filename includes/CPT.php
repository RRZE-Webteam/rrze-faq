<?php

namespace RRZE\FAQ;

defined( 'ABSPATH' ) || exit;

/**
 * Custom Post Type "faq"
 */
class CPT {
    
    private $lang = '';

    public function __construct() {
        $this->lang = substr( get_locale(), 0, 2 );
        add_action( 'init', [$this, 'registerFaq'], 0 );
        add_action( 'init', [$this, 'registerFaqTaxonomy'], 0 );
        add_action( 'publish_faq', [$this, 'setPostMeta'], 10, 1 );
        add_action( 'create_faq_category', [$this, 'setTermMeta'], 10, 1 );
        add_action( 'create_faq_tag', [$this, 'setTermMeta'], 10, 1 );
        add_filter( 'single_template', [$this, 'filter_single_template'] );
        add_filter( 'archive_template', [$this, 'filter_archive_template'] );
        add_filter( 'taxonomy_template', [$this, 'filter_taxonomy_template'] );
    }

    
    public function registerFaq() {	    
        $labels = array(
                'name'                => _x( 'FAQ', 'FAQ, synonym or glossary entries', 'rrze-faq' ),
                'singular_name'       => _x( 'FAQ', 'Single FAQ, synonym or glossary ', 'rrze-faq' ),
                'menu_name'           => __( 'FAQ', 'rrze-faq' ),
                'add_new'             => __( 'Add FAQ', 'rrze-faq' ),
                'add_new_item'        => __( 'Add new FAQ', 'rrze-faq' ),
                'edit_item'           => __( 'Edit FAQ', 'rrze-faq' ),
                'all_items'           => __( 'All FAQ', 'rrze-faq' ),
                'search_items'        => __( 'Search FAQ', 'rrze-faq' ),
        );
        $rewrite = array(
                'slug'                => 'faq',
                'with_front'          => true,
                'pages'               => true,
                'feeds'               => true,
        );
        $args = array(
                'label'               => __( 'FAQ', 'rrze-faq' ),
                'description'         => __( 'FAQ informations', 'rrze-faq' ),
                'labels'              => $labels,
                'supports'            => array( 'title', 'editor' ),
                'hierarchical'        => false,
                'public'              => true,
                'show_ui'             => true,
                'show_in_menu'        => true,
                'show_in_nav_menus'   => false,
                'show_in_admin_bar'   => true,
                'menu_icon'		  => 'dashicons-editor-help',
                'can_export'          => true,
                'has_archive'         => true,
                'exclude_from_search' => false,
                'publicly_queryable'  => true,
                'query_var'           => 'faq',
                'rewrite'             => $rewrite,
                'show_in_rest'        => true,
                'rest_base'           => 'faq',
                'rest_controller_class' => 'WP_REST_Posts_Controller',
        );
        register_post_type( 'faq', $args );
    }

    public function registerFaqTaxonomy() {
        $tax = [
            [ 
                'name' => 'faq_category',
                'label' => 'FAQ ' . __('Categories', 'rrze-faq'),
                'slug' => 'faq_category',
                'rest_base' => 'faq_category',
                'hierarchical' => TRUE,
                'labels' => array(
                    'singular_name' => __('Category', 'rrze-faq'),
                    'add_new' => __('Add new category', 'rrze-faq'),
                    'add_new_item' => __('Add new category', 'rrze-faq'),
                    'new_item' => __('New category', 'rrze-faq'),
                    'view_item' => __('Show category', 'rrze-faq'),
                    'view_items' => __('Show categories', 'rrze-faq'),
                    'search_items' => __('Search categories', 'rrze-faq'),
                    'not_found' => __('No category found', 'rrze-faq'),
                    'all_items' => __('All categories', 'rrze-faq'),
                    'separate_items_with_commas' => __('Separate categories with commas', 'rrze-faq'),
                    'choose_from_most_used' => __('Choose from the most used categories', 'rrze-faq'),
                    'edit_item' => __('Edit category', 'rrze-faq'),
                    'update_item' => __('Update category', 'rrze-faq')
                )
            ],
            [ 
                'name' => 'faq_tag',
                'label' => 'FAQ ' . __('Tags', 'rrze-faq'),
                'slug' => 'faq_tag',
                'rest_base' => 'faq_tag',
                'hierarchical' => FALSE,
                'labels' => array(
                    'singular_name' => __('Tag', 'rrze-faq'),
                    'add_new' => __('Add new tag', 'rrze-faq'),
                    'add_new_item' => __('Add new tag', 'rrze-faq'),
                    'new_item' => __('New tag', 'rrze-faq'),
                    'view_item' => __('Show tag', 'rrze-faq'),
                    'view_items' => __('Show tags', 'rrze-faq'),
                    'search_items' => __('Search tags', 'rrze-faq'),
                    'not_found' => __('No tag found', 'rrze-faq'),
                    'all_items' => __('All tags', 'rrze-faq'),
                    'separate_items_with_commas' => __('Separate tags with commas', 'rrze-faq'),
                    'choose_from_most_used' => __('Choose from the most used tags', 'rrze-faq'),
                    'edit_item' => __('Edit tag', 'rrze-faq'),
                    'update_item' => __('Update tag', 'rrze-faq')
                )
            ],
        ];
            
        foreach ($tax as $t){
            $ret = register_taxonomy(
                $t['name'],  //The name of the taxonomy. Name should be in slug form (must not contain capital letters or spaces).
                'faq',   		 //post type name
                array(
                    'hierarchical'	=> $t['hierarchical'],
                    'label' 		=> $t['label'], //Display name
                    'labels'        => $t['labels'],
                    'show_ui'       => TRUE,
                    'show_admin_column' => TRUE,
                    'query_var' 	=> TRUE,
                    'rewrite'		=> array(
                           'slug'	    => $t['slug'], // This controls the base slug that will display before each term
                           'with_front'	    => TRUE // Don't display the category base before
                    ),
                    'show_in_rest'       => TRUE,
                    'rest_base'          => $t['rest_base'],
                    'rest_controller_class' => 'WP_REST_Terms_Controller'
                )
            );
            register_term_meta(
                $t['name'], 
                'source', 
                array(
                    'query_var' 	=> TRUE,
                    'type' => 'string',
                    'single' => TRUE,
                    'show_in_rest' => TRUE,
                    'rest_base'          => 'source',
                    'rest_controller_class' => 'WP_REST_Terms_Controller'
            ));
            register_term_meta(
                $t['name'], 
                'lang', 
                array(
                    'query_var' 	=> TRUE,
                    'type' => 'string',
                    'single' => TRUE,
                    'show_in_rest' => TRUE,
                    'rest_base'          => 'lang',
                    'rest_controller_class' => 'WP_REST_Terms_Controller'
            ));
        }
    }
       
    public function setPostMeta( $postID ){
        add_post_meta( $postID, 'source', 'website', TRUE );
        add_post_meta( $postID, 'lang', $this->lang, TRUE );
        add_post_meta( $postID, 'remoteID', $postID, TRUE );
        $remoteChanged = get_post_timestamp( $postID, 'modified' );
        add_post_meta( $postID, 'remoteChanged', $remoteChanged, TRUE );
    }
    
    public function setTermMeta( $termID ){
        add_term_meta( $termID, 'source', 'website', TRUE );
        add_term_meta( $termID, 'lang', $this->lang, TRUE );
    }


    public function filter_single_template( $template ){
        global $post;
        if( 'faq' === $post->post_type ){
            $template = plugin_dir_path( __DIR__ ) .'templates/single-faq.php';
        }
        return $template;
    }


    
    public function filter_archive_template( $template ){
        if( is_post_type_archive('faq')){
            $template = plugin_dir_path( __DIR__ ) .'templates/archive-faq.php';
        }
        return $template;
    }


    public function filter_taxonomy_template( $template ){
        if( is_tax('faq_category')){
            $template = plugin_dir_path( __DIR__ ) .'templates/faq_category.php';
        }elseif( is_tax('faq_tag')){
            $template = plugin_dir_path( __DIR__ ) .'templates/faq_tag.php';
        }
        return $template;
    }


}