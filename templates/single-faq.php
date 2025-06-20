<?php
/**
 * The template for displaying a single FAQ
 *
 *
 * @package WordPress
 * @subpackage FAU
 * @since FAU 1.0
*/

get_header();

?>

<main id="main" class="site-main rrze-faq">

<?php
include_once('template-parts/faq_content.php');
?>
</main>

<?php
get_footer();
