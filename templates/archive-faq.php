<?php
/**
 * The template for displaying all FAQ
 *
 *
 * @package WordPress
 * @subpackage FAU
 * @since FAU 1.0
*/

include_once('template-parts/head.php');

if ( have_posts() ) : while ( have_posts() ) : the_post();

include('template-parts/faq_content.php');

endwhile; endif;

if ($schema){
    echo RRZE_SCHEMA_START . $schema . RRZE_SCHEMA_END;
}

include_once('template-parts/foot.php');