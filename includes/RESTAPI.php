<?php

namespace RRZE\FAQ;

defined('ABSPATH') || exit;

use RRZE\FAQ\Config;


/**
 * REST API for the 'faq' object type
 */
class RESTAPI
{
        private $cpt = [];

    /**
     * Constructor
     */
    public function __construct()
    {
                $this->cpt = Config::getConstants('cpt');

        // Register REST API meta fields
        add_action('rest_api_init', [$this, 'registerPostMetaRestFields']);
        // Register REST API taxonomy fields
        add_action('rest_api_init', [$this, 'registerTaxRestFields']);
        // Register REST API taxonomy children fields
        add_action('rest_api_init', [$this, 'registerTaxChildrenRestField']);
        // Register REST API query filters
        add_action('rest_api_init', [$this, 'addRestQueryFilters']);
    }

    /**
     * Get the meta 'source' of a 'faq' object type
     *
     * @param array $object
     * @return string
     */
    public function getPostSource($object)
    {
        return get_post_meta($object['id'], 'source', true);
    }

    /**
     * Get the meta 'lang' of a 'faq' object type
     *
     * @param array $object
     * @return string
     */
    public function getPostLang($object)
    {
        return get_post_meta($object['id'], 'lang', true);
    }

    /**
     * Get the meta 'remoteID' of a 'faq' object type
     *
     * @param array $object
     * @return string
     */
    public function getPostRemoteID($object)
    {
        return get_post_meta($object['id'], 'remoteID', true);
    }

    /**
     * Get the meta 'remoteChanged' of a 'faq' object type
     *
     * @param array $object
     * @return string
     */
    public function getPostRemoteChanged($object)
    {
        return get_post_meta($object['id'], 'remoteChanged', true);
    }

    /**
     * Registers meta fields of a 'faq' object type
     */
    public function registerPostMetaRestFields()
    {
        register_rest_field($this->cpt['faq'], 'source', array(
            'get_callback' => [$this, 'getPostSource'],
            'schema' => null,
        ));

        register_rest_field($this->cpt['faq'], 'lang', array(
            'get_callback' => [$this, 'getPostLang'],
            'schema' => null,
        ));

        register_rest_field($this->cpt['faq'], 'remoteID', array(
            'get_callback' => [$this, 'getPostRemoteID'],
            'schema' => null,
        ));

        register_rest_field($this->cpt['faq'], 'remoteChanged', array(
            'get_callback' => [$this, 'getPostRemoteChanged'],
            'schema' => null,
        ));
    }

    /**
     * Add filters to the REST API query
     */
    public function addRestQueryFilters()
    {
        // Add filter parameters to the object query
        add_filter('rest_'. $this->cpt['faq'] . '_query', [$this, 'addFilterParam'], 10, 2);
        // Add filter parameters to the categories query
        add_filter('rest_'. $this->cpt['category'] . '_query', [$this, 'addFilterParam'], 10, 2);
        // Add filter parameters to the tags query
        add_filter('rest_'. $this->cpt['tag'] . '_query', [$this, 'addFilterParam'], 10, 2);
    }

    /**
     * Add filter parameters to the query
     *
     * @param array $args
     * @param array $request
     * @return array
     */
    public function addFilterParam($args, $request)
    {
        if (empty($request['filter']) || !is_array($request['filter'])) {
            return $args;
        }
        global $wp;
        $filter = $request['filter'];

        $vars = apply_filters('query_vars', $wp->public_query_vars);
        foreach ($vars as $var) {
            if (isset($filter[$var])) {
                $args[$var] = $filter[$var];
            }
        }
        return $args;
    }

    /**
     * Get the terms names of the $this->cpt['category'] taxonomy
     *
     * @param array $object
     * @return array
     */
    public function getCategories($object)
    {
        $cats = wp_get_post_terms($object['id'], $this->cpt['category'], array('fields' => 'names'));
        return $cats;
    }

    /**
     * Get the children terms names of the $this->cpt['category'] taxonomy
     *
     * @param array $term
     * @return array
     */
    public function getChildrenCategories($term)
    {
        $children = get_terms(
            array(
                'taxonomy' => $this->cpt['category'],
                'parent' => $term['id'],
            )
        );
        $aRet = array();
        foreach ($children as $child) {
            $aRet[] = $child->name;
        }
        return $aRet;
    }

    /**
     * Get the terms names of the $this->cpt['tag'] taxonomy
     *
     * @param array $object
     * @return array
     */
    public function getTags($object)
    {
        return wp_get_post_terms($object['id'], $this->cpt['tag'], array('fields' => 'names'));
    }

    /**
     * Get the term meta 'source' of a $this->cpt['faq'] object type
     *
     * @param array $object
     * @return string
     */
    public function getTermSource($object)
    {
        return get_term_meta($object['id'], 'source', true);
    }

    /**
     * Get the term meta 'lang' of a $this->cpt['faq'] object type
     *
     * @param array $object
     * @return string
     */
    public function getTermLang($object)
    {
        return get_term_meta($object['id'], 'lang', true);
    }

    /**
     * Registers the taxonomies fields for the $this->cpt['faq'] object type
     */
    public function registerTaxRestFields()
    {
        register_rest_field(
            $this->cpt['faq'],
            $this->cpt['category'],
            array(
                'get_callback' => [$this, 'getCategories'],
                'update_callback' => null,
                'schema' => null,
            )
        );

        register_rest_field(
            $this->cpt['faq'],
            $this->cpt['tag'],
            array(
                'get_callback' => [$this, 'getTags'],
                'update_callback' => null,
                'schema' => null,
            )
        );

        $fields = array($this->cpt['category'], $this->cpt['tag']);
        foreach ($fields as $field) {
            // Registers the 'source' meta field
            register_rest_field($field, 'source', array(
                'get_callback' => [$this, 'getTermSource'],
                'schema' => null,
            ));
            // Registers the 'lang' meta field
            register_rest_field($field, 'lang', array(
                'get_callback' => [$this, 'getTermLang'],
                'schema' => null,
            ));
        }
    }

    /**
     * Registers the taxonomy children field for the 'faq_category' taxonomy
     */
    public function registerTaxChildrenRestField()
    {
        register_rest_field(
            $this->cpt['category'],
            'children',
            array(
                'get_callback' => [$this, 'getChildrenCategories'],
                'update_callback' => null,
                'schema' => null,
            )
        );
    }
}
