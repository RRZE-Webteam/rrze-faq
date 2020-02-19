<?php

namespace RRZE\Glossar\Server;

global $tax;
$tax = [
    [ 
        'name' => 'glossary_category',
        'label' => __('Category', 'rrze-faq'),
        'slug' => 'categories',
        'rest_base' => 'glossary_category',
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
        'name' => 'glossary_tag',
        'label' => __('Tags', 'rrze-faq'),
        'slug' => 'tags',
        'rest_base' => 'glossary_tag',
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


function fau_glossary_taxonomy() {
    global $tax;

    foreach ($tax as $t){
        register_taxonomy(
            $t['name'],  //The name of the taxonomy. Name should be in slug form (must not contain capital letters or spaces).
            'glossary',   		 //post type name
            array(
                'hierarchical'	=> false,
                'label' 		=> $t['label'], //Display name
                'labels'        => $t['labels'],
                'show_admin_column' => true,
                'query_var' 	=> true,
                'rewrite'		=> array(
                       'slug'	    => $t['slug'], // This controls the base slug that will display before each term
                       'with_front'	    => false // Don't display the category base before
                ),
                'show_in_rest'       => true,
                'rest_base'          => $t['rest_base'],
                'rest_controller_class' => 'WP_REST_Terms_Controller'
            )
        );
    }
}

add_action( 'init', 'RRZE\Glossar\Server\fau_glossary_taxonomy');