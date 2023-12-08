<?php

namespace RRZE\FAQ;

defined('ABSPATH') || exit;

/**
 * Class Shortcode
 * @package RRZE\FAQ
 */
class Gutenberg
{
    /**
     * Renders the faq block for the frontend
     *
     * @param [type] $attributes
     * @return void
     */
    public static function rrze_faq_render_block($attributes)
    {
        $result = Shortcode::instance()->shortcodeFaq($attributes);
        return $result;
    }
}
