<?php

namespace RRZE\FAQ;

defined('ABSPATH') || exit;

use function RRZE\FAQ\Config\getConstants;


class Tools
{
    /**
     * Workaround for a known Gutenberg issue where shortcodes within Preformatted blocks
     * are incorrectly parsed and executed, even when wrapped in double brackets [[shortcode]].
     *
     * $post is only needed for the workaround.
     *
     * In most cases, $post is defined. However, if do_shortcode() is called manually,
     * via AJAX callbacks, or within a REST API context, $post may not be available.
     * Therefore, we check if $post is a valid WP_Post object before proceeding.
     *
     * This check prevents execution by detecting the double-bracketed shortcode in the post content.
     *
     * @param string $shortcode_tag The shortcode name
     * @return string|false Escaped placeholder if found, or false to continue normal processing
     */
    public static function preventGutenbergDoubleBracketBug(string $shortcode_tag)
    {
        global $post;

        if (!($post instanceof \WP_Post) || !isset($post->post_content)) {
            return '';
        }

        if (strpos($post->post_content, '[[' . $shortcode_tag . ']]') !== false) {
            return esc_html("[[$shortcode_tag]]");
        }

        return false;
    }

    /**
     * Sorts an associative array alphabetically by its values (case-insensitive).
     *
     * @param array &$arr The array to sort.
     * @return void
     */
    public static function sortIt(&$arr)
    {
        uasort($arr, function ($a, $b) {
            return strtolower($a) <=> strtolower($b);
        });
    }

    /**
     * Searches an associative array by a given key and returns the matching value.
     *
     * @param string $needle The key to search for.
     * @param array  $aHaystack The array to search in.
     * @return mixed The value if found, false otherwise.
     */
    public static function searchArrayByKey(&$needle, &$aHaystack)
    {
        foreach ($aHaystack as $k => $v) {
            if ($k === $needle) {
                return $v;
            }
        }
        return false;
    }

    public static function getHeaderID(?int $postID = null): string
    {
        $random = wp_rand(); // In case there are multiple FAQs on the same page
        return 'header-' . ($postID ?? 'noid') . '-' . $random;
    }

    /**
     * Wraps the given content in a DIV with optional layout classes and ARIA label binding.
     *
     * Uses the provided header ID for aria-labelledby. Supports optional Masonry layout,
     * color scheme, and additional CSS classes.
     *
     * @param string &$content           The HTML content to wrap.
     * @param string &$headerID         The header ID used for aria-labelledby.
     * @param bool   &$masonry           Whether to apply Masonry layout classes.
     * @param string &$color             Optional color class (e.g. 'blue', 'phil', ...).
     * @param string &$additional_class  Additional CSS classes to append.
     * @return string The wrapped HTML output.
     */
    public static function renderFAQWrapper(?int $postID = null, string &$content, string &$headerID, bool &$masonry, string &$color, string &$additional_class): string
    {
        $classes = 'rrze-faq';

        if ($masonry) {
            $classes .= ' faq-masonry';
        }

        if (!empty($color)) {
            $classes .= ' ' . trim($color);
        }

        if (!empty($additional_class)) {
            $classes .= ' ' . trim($additional_class);
        }

        $content .= Tools::getSchema($postID, $question, $answer);

        return '<div class="' . esc_attr($classes) . '" aria-labeledby="' . esc_attr($headerID) . '">' . $content . '</div>';
    }


    /**
     * Extracts and returns the uppercase first letter of a given string.
     *
     * @param string $txt The input string.
     * @return string The uppercase initial letter.
     */
    public static function getLetter(&$txt)
    {
        return mb_strtoupper(mb_substr(remove_accents($txt), 0, 1), 'UTF-8');
    }

    /**
     * Generates an A–Z letter navigation HTML block based on available letters.
     *
     * @param array &$aSearch Array of available letters.
     * @return string HTML output of the letter navigation.
     */
    public static function createAZ(&$aSearch)
    {
        if (count($aSearch) == 1) {
            return '';
        }
        $ret = '<ul class="letters">';

        foreach (range('A', 'Z') as $a) {
            if (array_key_exists($a, $aSearch)) {
                $ret .= '<li class="filled"><a href="#letter-' . $a . '">' . $a . '</a></li>';
            } else {
                $ret .= '<li>' . $a . '</li>';
            }
        }
        return $ret . '</ul>';
    }

    /**
     * Generates a tab-style navigation based on terms.
     *
     * @param array $aTerms List of terms.
     * @param array $aPostIDs Mapping of term IDs to post IDs.
     * @return string HTML output of the tab navigation.
     */
    public static function createTabs(&$aTerms, $aPostIDs)
    {
        if (count($aTerms) == 1) {
            return '';
        }
        $ret = '';
        foreach ($aTerms as $name => $aDetails) {
            $ret .= '<a href="#ID-' . $aDetails['ID'] . '">' . $name . '</a> | ';
        }
        return rtrim($ret, ' | ');
    }

    /**
     * Generates a tag cloud with font size scaled by post count.
     *
     * @param array $aTerms List of terms.
     * @param array $aPostIDs Mapping of term IDs to post IDs.
     * @return string HTML output of the tag cloud.
     */
    public static function createTagcloud(&$aTerms, $aPostIDs)
    {
        if (count($aTerms) == 1) {
            return '';
        }
        $ret = '';
        $smallest = 12;
        $largest = 22;
        $aCounts = array();
        foreach ($aTerms as $name => $aDetails) {
            $aCounts[$aDetails['ID']] = count($aPostIDs[$aDetails['ID']]);
        }
        $iMax = max($aCounts);
        $aSizes = array();
        foreach ($aCounts as $ID => $cnt) {
            $aSizes[$ID] = round(($cnt / $iMax) * $largest, 0);
            $aSizes[$ID] = ($aSizes[$ID] < $smallest ? $smallest : $aSizes[$ID]);
        }
        foreach ($aTerms as $name => $aDetails) {
            $ret .= '<a href="#ID-' . $aDetails['ID'] . '" style="font-size:' . $aSizes[$aDetails['ID']] . 'px">' . $name .
                '</a> | ';
        }
        return rtrim($ret, ' | ');
    }

    /**
     * Builds a tax_query array for use in WP_Query based on taxonomy source-value pairs.
     *
     * @param array &$aTax The structured taxonomy input.
     * @return array A WP-compatible tax_query array.
     */
    public static function getTaxQuery(&$aTax)
    {
        $ret = array();

        foreach ($aTax as $taxfield => $aEntries) {
            $term_queries = array();
            $sources = array();

            foreach ($aEntries as $entry) {
                $source = !empty($entry['source']) ? $entry['source'] : '';
                $term_queries[$source][] = $entry['value'];
            }

            foreach ($term_queries as $source => $aTerms) {

                $query = array(
                    'taxonomy' => $taxfield,
                    'field' => 'slug',
                    'terms' => $aTerms,
                );

                if (count($aTerms) > 1) {
                    $query['operator'] = 'IN';
                }

                if (!empty($source)) {
                    $query['meta_key'] = 'source'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
                    $query['meta_value'] = $source; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
                }

                $ret[$taxfield][] = $query;
            }
            if (count($ret[$taxfield]) > 1) {
                $ret[$taxfield]['relation'] = 'OR';
            }
        }

        if (count($ret) > 1) {
            $ret['relation'] = 'AND';
        }

        return $ret;
    }

    /**
     * Creates schema.org markup for a FAQ item if source is 'website'.
     *
     * @param int    &$postID   The post ID of the FAQ item.
     * @param string &$question The FAQ question.
     * @param string &$answer   The FAQ answer.
     * @return string JSON-LD schema markup string.
     */
    public static function getSchema(int &$postID, string &$question, string &$answer): string
    {
        $schema = '';
        $source = get_post_meta($postID, "source", true);
        $answer = wp_strip_all_tags($answer, true);
        $constants = getConstants();

        if ($source === 'website') {
            $schema = $constants['schema']['RRZE_SCHEMA_QUESTION_START'] . $question . $constants['schema']['RRZE_SCHEMA_QUESTION_END'];
            $schema .= $constants['schema']['RRZE_SCHEMA_ANSWER_START'] . $answer . $constants['schema']['RRZE_SCHEMA_ANSWER_END'];
        }
        return $schema;
    }

    /**
     * Parses a string of source-prefixed taxonomies into a structured array.
     *
     * Input format: "rrze:cat1, fau:cat2, general"
     *
     * @param string $input The raw input string.
     * @return array Parsed array of [ 'source' => string, 'value' => string ] pairs.
     */
    public static function getTaxBySource($input)
    {
        $result = [];

        if (empty($input)) {
            return $result;
        }

        // Teilen des Eingabestrings in einzelne Kategorien
        $categories = explode(', ', $input);

        foreach ($categories as $category) {
            // Teilen der Kategorie in Quelle und Wert
            list($source, $value) = array_pad(explode(':', $category, 2), 2, '');

            // Überprüfen, ob $value leer ist
            if ($value === '') {
                $value = $source; // Wenn $value leer ist, setze $value auf $source
                $source = ''; // Setze $source auf leer
            }

            // Erstellen des Ergebnisarrays für jede Kategorie
            $result[] = array(
                'source' => preg_replace('/[\s,]+$/', '', $source),
                'value' => preg_replace('/[\s,]+$/', '', $value)
            );
        }

        return $result;
    }

    /**
     * Returns a comma-separated list of term links for a given taxonomy.
     *
     * Retrieves all terms assigned to the specified post and taxonomy,
     * and outputs them as linked names pointing to the respective term archive pages.
     *
     * @param int    $postID     The ID of the post.
     * @param string $mytaxonomy The taxonomy name (e.g., 'faq_category', 'faq_tag').
     * @return string HTML string of linked term names, separated by commas.
     */

    public static function getTermLinks(&$postID, $mytaxonomy)
    {
        $ret = '';
        $terms = wp_get_post_terms($postID, $mytaxonomy);

        foreach ($terms as $term) {
            $ret .= '<a href="' . get_term_link($term->slug, $mytaxonomy) . '">' . $term->name . '</a>, ';
        }
        return substr($ret, 0, -2);
    }

    /**
     * Returns information about a context page based on the first valid top-level FAQ category.
     *
     * Among all assigned 'faq_category' terms, only top-level ones (not children of other assigned terms)
     * are considered. The method returns the first valid 'linked_page' from those terms as structured data.
     *
     * @param int &$postID The ID of the current FAQ post (passed by reference, but not modified).
     * @return array|null Array with keys 'id', 'url', and 'title' of the linked page, or null if none found.
     */
    public static function getLinkedPage(int &$postID): ?array
    {
        $assigned_terms = get_the_terms($postID, 'faq_category');
        if (!$assigned_terms || is_wp_error($assigned_terms)) {
            return null;
        }

        // Collect all parent IDs among the assigned terms
        $parent_term_ids = array_filter(wp_list_pluck($assigned_terms, 'parent'));

        // Keep only terms that are not children of another assigned term
        $top_level_terms = array_filter($assigned_terms, function ($term) use ($parent_term_ids) {
            return !in_array($term->term_id, $parent_term_ids, true);
        });

        foreach ($top_level_terms as $term) {
            $linked_page_id = get_term_meta($term->term_id, 'linked_page', true);
            if (!$linked_page_id || get_post_status($linked_page_id) !== 'publish') {
                continue;
            }

            return [
                'url' => get_permalink($linked_page_id),
                'title' => get_the_title($linked_page_id),
            ];
        }

        return null;
    }

    public static function hasSync(): bool
    {
        $query = new WP_Query([
            'post_type' => 'faq',
            'post_status' => 'publish',
            'posts_per_page' => 1,
            'meta_query' => [
                [
                    'key' => 'source',
                    'value' => 'website',
                    'compare' => '!=',
                ],
            ],
            'fields' => 'ids',
            'no_found_rows' => true,
        ]);

        return $query->have_posts();
    }


}