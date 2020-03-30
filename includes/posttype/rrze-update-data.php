<?php

namespace RRZE\FAQ\Update;


function rrze_update_old_data(){
    $old_faqs = get_posts( array( 'post_type'=> 'glossary', 'fields' => 'ids', 'posts_per_page' => -1) );
    foreach ( $old_faqs as $faq_id ){
        // 1. add source
        add_post_meta( $faq_id, 'source', 'website', TRUE );
        $old_categories = wp_get_post_terms( $faq_id, 'glossary_category', array( 'fields' => 'ids' ) );
        // 2. update post_type
        wp_update_post( array( 'ID' => $faq_id, 'post_type' => 'faq' ) );
        foreach ( $old_categories as $cat_id ){
            // 3. add source to faq_category
            add_term_meta( $cat_id, 'source', 'website', TRUE );
            // 4. update glossary_category to faq_category
            wp_update_term( $cat_id, 'glossary_category', array( 'taxonomy' => 'faq_category'));
        }
    }
}

add_action( 'init', 'RRZE\FAQ\Update\rrze_update_old_data');
