<?php

namespace RRZE\FAQ;

defined('ABSPATH') || exit;
use function RRZE\FAQ\Config\getShortcodeSettings;

$settings;

/**
 * Shortcode
 */
class Shortcode {

    /**
     * Settings-Objekt
     * @var object
     */
    private $settings = '';

    public function __construct() {
        $this->settings = getShortcodeSettings();
        // add_action( 'init', [$this, 'fill_gutenberg_options'] );
        add_action( 'init',  [$this, 'gutenberg_init'] );
        add_action( 'init', [$this, 'enqueueScripts'] );
        add_shortcode( 'faq', [ $this, 'shortcodeOutput' ], 10, 2 );
        add_shortcode( 'fau_glossar', [ $this, 'shortcodeOutput' ], 10, 2 ); // alternative shortcode
        add_shortcode( 'glossary', [ $this, 'shortcodeOutput' ], 10, 2 ); // alternative shortcode
        // add_action( 'enqueue_block_assets', [ $this, 'prefill' ] ); 
        // add_filter( 'block_editor_settings', [$this, 'prefill'], 10, 0 ); // feuert bei Beitrag erstellen
    }

    public function prefill(){
        $editor_script = $this->settings['block']['blockname'] . '-blockJS';
        wp_localize_script( $editor_script, $this->settings['block']['blockname'] . 'Config', $this->fill_gutenberg_options() );
    }


    /**
     * Enqueue der Skripte.
     */
    public function enqueueScripts() {
        // wp_register_style( 'theme-css', get_stylesheet_directory_uri() . "/style.css", false, '1.0', 'all' );
        // wp_enqueue_style( 'theme-css' );
        wp_register_script( 'rrze-faq-js', plugins_url( '../assets/js/rrze-faq.js', __FILE__ ) );
        wp_enqueue_script( 'rrze-faq' );
        wp_register_style( 'rrze-faq-css', plugins_url( '../assets/css/plugin.css', plugin_basename( __FILE__ ) ) );
        wp_enqueue_style( 'rrze-faq-css' );
    }

    private function get_letter( &$txt ) {
        return mb_strtoupper( mb_substr( remove_accents( $txt ), 0, 1 ), 'UTF-8');
    }

    private function create_a_z( &$aSearch ){
        $ret = '<div class="fau-glossar"><ul class="letters" aria-hidden="true">';
        foreach ( range( 'A', 'Z' ) as $a ) {
            if ( array_key_exists( $a, $aSearch ) ) {
                $ret .= '<li class="filled"><a href="#letter-'.$a.'">'.$a.'</a></li>';
            }  else {
                $ret .= '<li>'.$a.'</li>';
            }
        }
        return $ret . '</ul></div>';
    }

    private function create_tagcloud( &$tags ) {
        $ret = '';
        // foreach()
    }

    private function get_tax_query( &$aTax ){
        $ret = '';
        $aTmp = array();

        foreach( $aTax as $field => $aVal ){
            $aID = array();
            foreach( $aVal as $val ){
                $term = get_term_by( 'slug', $val, 'faq_' . $field );
                if ( $term ){
                    $aID[] = $term->term_id;
                }else{
                    // category or tag is given but cannot be found. To supress output of all FAQ, term_id = 0 is used
                    $aID[] = 0;
                }
            }
            if ( $aID ){
                $aTmp[] = array(
                    'taxonomy' => 'faq_' . $field,
                    'field' => 'id', // can be slug or id - a CPT-onomy term's ID is the same as its post ID
                    'terms' => $aID,
                    'operator' => 'IN'
                );
            }
        }
        if ( $aTmp ){
            $ret = array( $aTmp );
            if ( count ( $aTmp ) > 1 ){
                $ret['relation'] = 'AND';
            }
        }
        return $ret;
    }

    private function search_array_by_key( &$needle, &$aHaystack ){
        foreach( $aHaystack as $k => $v ){
            if ( $k === $needle ){
                return $v;
            }
        }
        return FALSE;
    }

    /**
     * Generieren Sie die Shortcode-Ausgabe
     * @param  array   $atts Shortcode-Attribute
     * @param  string  $content Beiliegender Inhalt
     * @return string Gib den Inhalt zurück
     */
    public function shortcodeOutput( $atts ) {
        // merge given attributes with default ones
        $atts_default = array();
        foreach( $this->settings as $k => $v ){
            if ( $k != 'block' ){
                $atts_default[$k] = $v['default'];
            }
        }
        $atts = shortcode_atts( $atts_default, $atts );

        // echo '<pre>';
        // return json_encode($atts);

        extract( $atts );

        // $domain = 'https://www.helpdesk.rrze.fau.de/otrs/nph-genericinterface.pl/Webservice/RRZEPublicFAQConnectorREST/CategoryList';
        // $content = wp_remote_get( $domain );
        // $status_code = wp_remote_retrieve_response_code( $content );


        // echo '<pre>';
        // var_dump($content);
        // var_dump($status_code);
        // echo '</pre>';
        // exit;
        

        $content = '';
        $glossarystyle  = ( isset( $glossarystyle ) ? $glossarystyle : '' );
        $color = ( isset( $color ) ? $color : '' );

        if ( $glossary && ( array_key_exists( $glossary, $this->settings['glossary']['values'] ) == FALSE )){
            return __( 'Attribute glossary is not correct. Please use either glossary="category" or glossary="tag".', 'rrze-faq' );
        }

        if( $domain ) {
            // DOMAIN
            $domains = get_option('registeredDomains');

            if ( !in_array( $domain, array_keys( $domains ) ) ){
                return __( 'Domain is not registered', 'rrze-faq' );
            }

            $items = array();
            $page = 1;
            $domain = $domains[$domain] . 'wp-json/wp/v2/faq';

            $filter = '';
            $single = FALSE;

            if ( $glossary ){
                if ( !$tag && !$category ){
                    return __( 'There are too many entries expected. Please provide at least one tag or category.', 'rrze-faq' );
                }

                // get all used tags or categories
                $url = $domain . '_' . $glossary;
                $tags_or_categories = array();
                $page = 1;
                $accordion = '[collapsibles]';

                $slug = '';
                if ( ( $category && ( $glossary == 'category' ) ) || ( $tag && ( $glossary == 'tag' ) ) ){
                    $slug = '&slug=' . ( $category ? $category : $tag );
                } else {
                    // 2DO: Knackpunkt ist die Reihenfolge: besser zuerst $category bzw $tag abrufen und dann erst glossary 
                    // umgekehrt: time-out Proxy-Error
                    return __( 'The combination of $glossary="category" + $tag and $glossary="tag" + $category is not implemented yet because it leads to time-outs (Proxy-Error).', 'rrze-faq' );
                }

                do {
                    $request = wp_remote_get( $url . '?page=' . $page . $slug );
                    $status_code = wp_remote_retrieve_response_code( $request );
                    if ( $status_code == 200 ){
                        $entries = json_decode( wp_remote_retrieve_body( $request ), true );
                        if ( !empty( $entries ) ){
                            foreach( $entries as $entry ){
                                if ( $entry['count'] > 0 ){
                                    $tags_or_categories[$entry['slug']] = $entry['name'];
                                }
                            }
                        }
                    }
                    $page++;   
                } while ( ( $status_code == 200 ) && ( !empty( $entries ) ) );

                asort( $tags_or_categories );

                $filter_term = ( $category ? 'category' : ( $tag ? 'tag' : $glossary ) );

                // get all FAQ for each category or tag ( = $glossary )
                foreach( $tags_or_categories as $slug => $name ){
                    // $term_slug = ( $category ? $category : ( $tag ? $tag : $slug ) );
                    $aTerm_slugs = array_map( 'trim', explode( ',', ( $category ? $category : ( $tag ? $tag : $slug ) ) ) );
                    foreach( $aTerm_slugs as $term_slug ){
                        $filter = '&filter[faq_' . $filter_term . ']=' . $term_slug;
                        $page = 1;

                        do {
                            $request = wp_remote_get( $domain . '?page=' . $page . $filter );
                            $status_code = wp_remote_retrieve_response_code( $request );
                            if ( $status_code == 200 ){
                                $entries = json_decode( wp_remote_retrieve_body( $request ), true );
                                if ( !empty( $entries ) ){
                                    if ( isset( $entries[0] ) ){
                                        foreach( $entries as $entry ){
                                            // $items[$entry['title']['rendered']] = $entry['content']['rendered'];
                                            $items[$term_slug][$entry['title']['rendered']] = $entry['content']['rendered'];
                                            // $items[$term_slug] = array(
                                            //     'title' => $entry['title']['rendered'],
                                            //     'content' => $entry['content']['rendered']
                                            // );
                                        }
                                    } else {
                                        // $items[$entries['title']['rendered']] = $entries['content']['rendered'];
                                        $items[$term_slug][$entries['title']['rendered']] = $entries['content']['rendered'];
                                        // $items[$term_slug] = array(
                                        //     'title' => $entries['title']['rendered'],
                                        //     'content' => $entries['content']['rendered']
                                        // );
                                    }
                                }
                            }
                            $page++;   
                        } while ( ( $status_code == 200 ) && ( !empty( $entries ) ) );
                    }
                }

                foreach( $tags_or_categories as $slug => $name ){
                    if ( isset( $items[$slug] ) ){
                        $letter = $this->get_letter( $name );
                        $accordion .= '[collapse title="' . $name . '" color="' . $color . '" name="letter-' . $letter . '"]';    

                        ksort( $items[$slug] );                        
                        foreach( $items[$slug] as $faqtitle => $faqcontent ){
                                $accordion .= '[accordion][accordion-item title="' . $faqtitle . '"]' . $faqcontent . '[/accordion-item][/accordion]';

                        }
                        $accordion .= '[/collapse]';
                    }
                }
                $accordion .= '[/collapsibles]';
                $content .= do_shortcode( $accordion );
            } else {
                if ( intval( $id ) > 0 ){
                    // single FAQ
                    $single = TRUE;
                    $domain .= '/' . $id . '/';
                } else {
                    if ( $category ){
                        $filter = '&filter[faq_category]=' . $category;
                    } 
                    if ( $tag ) {
                        $filter .= '&filter[faq_tag]=' . $tag;
                    } 
                }

                do {
                    $request = wp_remote_get( $domain . '?page=' . $page . $filter );
                    $status_code = wp_remote_retrieve_response_code( $request );
                    if ( $status_code == 200 ){
                        $entries = json_decode( wp_remote_retrieve_body( $request ), true );
                        if ( !empty( $entries ) ){
                            if ( isset( $entries[0] ) ){
                                foreach( $entries as $entry ){
                                    $items[$entry['title']['rendered']] = $entry['content']['rendered'];
                                }
                            } else {
                                $items[$entries['title']['rendered']] = $entries['content']['rendered'];
                            }
                        }
                    }
                    $page++;   
                } while ( ( $status_code == 200 ) && ( !empty( $entries ) ) && !$single );

                ksort( $items );
                                
                $aLetters = array();
                $accordion = '[collapsibles]';
                foreach ( $items as $title => $txt ) {
                    $letter = $this->get_letter( $title );
                    $aLetters[$letter] = TRUE; 
                    $accordion .= '[collapse title="' . $title . '" color="' . $color . '" name="letter-' . $letter . '"]' . str_replace( ']]>', ']]&gt;', $txt ) . '[/collapse]';
                }
        
                $accordion .= '[/collapsibles]';
                if ( !$single && $glossary ){
                        $content = ( $glossarystyle == 'tagcloud' ? wp_generate_tag_cloud( $term ) : $this->create_a_z( $aLetters ) );
                }

                $content .= do_shortcode( $accordion );
            }
        } elseif ( intval( $id ) > 0 ) {
            // SINGLE FAQ
            $title = get_the_title( $id );
            $letter = $this->get_letter( $title );
            $content = str_replace( ']]>', ']]&gt;', apply_filters( 'the_content',  get_post_field('post_content',$id) ) );
            if ( !isset( $content ) || ( mb_strlen( $content ) < 1)) {
                $content = get_post_meta( $id, 'description', true );
            }
            if ( $content) {
                $accordion = '[collapsibles][collapse title="' . $title . '" color="' . $color . '" name="letter-' . $letter . '"]' . $content . '[/collapse][/collapsibles]';
                $content = do_shortcode( $accordion );
            }
        } else {
            // attribute category or tag is given or none of them
            $aLetters = array();
            $aCategory = array();
            $aTax = array();
            $tax_query = '';
            $postQuery = array('post_type' => 'faq', 'post_status' => 'publish', 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC', 'suppress_filters' => false);

            $fields = array( 'category', 'tag' );

            foreach( $fields as $field ){
                if ( $$field ){
                    if ( is_array( $$field ) ){
                        $aTax[$field] = $$field;    
                    }else{
                        $aTax[$field] = explode(',', trim( $$field ) );
                    }
                }
            }
            $tax_query = $this->get_tax_query( $aTax );
            if ( $tax_query ){
                $postQuery['tax_query'] = $tax_query;
            }

            // echo '<pre>';
            // var_dump($postQuery);
            // exit;

            $posts = get_posts( $postQuery );

            if ( $glossary ){
                // get all used tags or categories
                $aUsedTerms = array();
                $aPostIDs = array();
                $check_filter = FALSE;
                foreach( $posts as $post ) {
                    // get all tags for each post
                    $aTermIds = array();
                    $aTerms = array();

                    if ( $glossary == 'tag' && $tag ){
                        $check_filter = TRUE;
                        $aTags = array_map( 'trim', explode( ',', $tag ) );                
                        $valid_term_ids = array();
                        foreach ( $aTags as $slug ){
                            $filter_term = get_term_by( 'slug', $slug, 'faq_tag' );
                            if ( $filter_term ){
                                $valid_term_ids[] = $filter_term->term_id;
                            } 
                        }
                    }            

                    $term = wp_get_post_terms( $post->ID, 'faq_' . $glossary );
                    if ( $term ){
                        foreach( $term as $t ){
                            if ( $check_filter && in_array( $t->term_id, $valid_term_ids ) === FALSE ){
                                continue;
                            }
                            $aTermIds[] = $t->term_id;
                            $letter = $this->get_letter( $t->name );
                            // $aTerms = (array)$t;
                            // $aTerms['link'] = '#letter_' . $letter;
                            $aLetters[$letter] = TRUE; 
                            // $aUsedTags[$t->name] = array( 'letter' => $letter, 'ID' => $t->term_id );
                            $aUsedTerms[$t->name] = array( 'letter' => $letter, 'ID' => $t->term_id );
                            $aPostIDs[$t->term_id][] = $post->ID;
                        }
                        $aTerms = (object)$aTerms;
                    }                    
                }
                if ( $aLetters ){
                    // 2DO : links setzen -> #letter-b
                    // https://wordpress.stackexchange.com/questions/225693/how-to-add-css-class-to-cloud-tag-anchors
                    $content = ( $glossarystyle == 'tagcloud' ? wp_generate_tag_cloud( $term ) : $this->create_a_z( $aLetters ) );
                }

                asort( $aUsedTerms );

                $accordion = '[collapsibles]';

                foreach ( $aUsedTerms as $k => $aVal ){
                    $letter = $this->get_letter( $k );
                    $accordion .= '[collapse title="' . $k . '" color="' . $color . '" name="letter-' . $letter . '"]';
                    // find the postIDs to this tag
                    $aIDs = $this->search_array_by_key( $aVal['ID'], $aPostIDs );
                    foreach ( $aIDs as $ID ){
                        $tmp = str_replace( ']]>', ']]&gt;', apply_filters( 'the_content',  get_post_field('post_content', $ID) ) );
                        if ( !isset( $tmp ) || (mb_strlen( $tmp ) < 1)) {
                            $tmp = get_post_meta( $ID, 'description', true );
                        }
                        $accordion .= '[accordion][accordion-item title="' . get_the_title( $ID ) . '"]' . $tmp . '[/accordion-item][/accordion]';
                    }
                    $accordion .= '[/collapse]';
                }
                $accordion .= '[/collapsibles]';
                $content .= do_shortcode( $accordion );
            } else {
                $accordion = '[collapsibles]';
                foreach( $posts as $post ) {
                    $title = get_the_title( $post->ID );
                    $letter = $this->get_letter( $title );
                    $aLetters[$letter] = TRUE; 
                    $tmp = str_replace( ']]>', ']]&gt;', apply_filters( 'the_content',  get_post_field( 'post_content', $post->ID ) ) );
                    if ( !isset( $tmp ) || ( mb_strlen( $tmp ) < 1 ) ) {
                        $tmp = get_post_meta( $post->ID, 'description', true );
                    }
                    $accordion .= '[collapse title="' . $title . '" color="' . $color . '" name="letter-' . $letter . '"]' . $tmp . '[/collapse]';
                }
                $accordion .= '[/collapsibles]';
                $content .= do_shortcode( $accordion );
            }
       } 
    //    $this->enqueueScripts();
       return $content;
    }

    public function sortIt( &$arr ){
        uasort( $arr, function($a, $b) {
            return strtolower( $a ) <=> strtolower( $b );
        } );
    }

    public function fill_gutenberg_options() {

        // echo 'fill';
        // exit;

        // Skip if Gutenberg is not enabled
        if ( ! function_exists( 'register_block_type' ) ) {
            return;
        }

        $domains = get_option( 'registeredDomains' );
        if ( $domains ){
            foreach ( $domains as $name => $url ){
                $this->settings['domain']['values'][$url] = $name;
            }
        }

        $options = get_option( 'rrze-faq' );

        // fill selects "category" and "tag"
        $fields = array( 'category', 'tag' );
        foreach ( $fields as $field ) {
            // set new params for gutenberg / the old ones are used for shortcode in classic editor
            $this->settings[$field]['field_type'] = 'multi_select';
            $this->settings[$field]['default'] = array('');
            $this->settings[$field]['type'] = 'array';
            $this->settings[$field]['items'] = array( 'type' => 'string' );
            $this->settings[$field]['values'][0] = __( '-- all --', 'rrze-faq' );

            // get categories and tags from this website
            $terms = get_terms([
                'taxonomy' => 'faq_' . $field,
                'hide_empty' => FALSE,
                'meta_query' => array(
                    array(
                        'key' => 'source',
                        'value' => 'website',
                    )
                )
            ]);
            foreach ( $terms as $term ){
                $this->settings[$field]['values'][$term->id] = $term->name;
            }

            // get categories and tags from other domains
            if ( $domains ) {
                foreach( $domains as $name => $url ){
                    $page = 1;
                    do {
                        // $request = wp_remote_get( $url . 'wp-json/wp/v2/faq_' . $field . '?_fields=name,id&page=' . $page );
                        $request = wp_remote_get( $url . 'wp-json/wp/v2/faq_' . $field . '?page=' . $page );
                        $status_code = wp_remote_retrieve_response_code( $request );
                        if ( $status_code == 200 ){
                            $body = json_decode( wp_remote_retrieve_body( $request ), true );
                            if ( !empty( $body ) ){
                                foreach( $body as $entry ){
                                    $this->settings[$field]['values'][$entry['id']] = $entry['name'];
                                }
                            }
                        }
                        $page++;   
                    } while ( ( $status_code == 200 ) && ( !empty( $body ) ) );
                }
            }

            $this->sortIt( $this->settings[$field]['values'] );
        }


        // https://www.nickless.test.rrze.fau.de/faq-gutenberg/wp-json/wp/v2/faq?faq_category=951,950&faq_tag=90

        // get FAQ from this website and synced from OTRS
        $faqs = get_posts( array(
            'posts_per_page'  => -1,
            'post_type' => 'faq',
            'order' => 'ASC',
            // 'orderby' => 'title',
            // 'fields' => 'ids',
            // 'meta_query' => array(
            //     array(
            //         'key' => 'source',
            //         'value' => 'website',
            //     )
            // )
        ));

        $this->settings['id']['field_type'] = 'select';
        $this->settings['id']['type'] = 'string';
        $this->settings['id']['values'][0] = __( '-- all --', 'rrze-faq' );
        foreach ( $faqs as $faq){
            $this->settings['id']['values'][$faq->ID] = str_replace( "'", "", str_replace( '"', "", $faq->post_title ) );
        }


        // https://Benjamin:T3st!@www.nickless.test.rrze.fau.de/dev2/wp-json/wp/v2/faq?page=1

        // https://www.nickless.test.rrze.fau.de/dev2/wp-json/wp/v2/faq?faq_category=5 funktioniert
        // https://www.nickless.test.rrze.fau.de/dev2/wp-json/wp/v2/faq?faq_tag=1 funktioniert
        // https://www.nickless.test.rrze.fau.de/dev2/wp-json/wp/v2/faq?faq_tag=8787,123 funktioniert : Werte sind OR
        // Problem: filter nach post_meta_field source
        https://www.nickless.test.rrze.fau.de/dev2/wp-json/wp/v2/faq?post_meta_field[source]=website

        // get FAQ from other domains
        if ( $domains ) {
            foreach( $domains as $name => $url ){
                $page = 1;
                do {
                    // $request = wp_remote_get( $url . 'wp-json/wp/v2/glossary?page=' . $page );
                    $request = wp_remote_get( $url . 'wp-json/wp/v2/faq?page=' . $page );
                    $status_code = wp_remote_retrieve_response_code( $request );
                    // echo '<pre>';
                    // var_dump($request);
                    // exit;
            
                    if ( $status_code == 200 ){
                        $body = json_decode( wp_remote_retrieve_body( $request ), true );
                        if ( !empty( $body ) ){
                            foreach( $body as $entry ){
                                // echo '<pre>';
                                // var_dump($entry);
                                // exit;
                                $this->settings['id']['values'][$entry['id']] = $entry['title']['rendered'];
                            }
                        }
                    }
                    $page++;   
                } while ( ( $status_code == 200 ) && ( !empty( $body ) ) );
            }
        }
        $this->sortIt( $this->settings['id']['values'] );
        return $this->settings;
    }



    public function gutenberg_init() {
        // Skip block registration if Gutenberg is not enabled/merged.
        if ( ! function_exists( 'register_block_type' ) ) {
            return;
        }
        $js = '../assets/js/gutenberg.js';
        $editor_script = $this->settings['block']['blockname'] . '-blockJS';
        wp_register_script(
            $editor_script,
            plugins_url( $js, __FILE__ ),
            array(
                'wp-blocks',
                'wp-i18n',
                'wp-element',
                'wp-components',
                'wp-editor'
            ),
            filemtime( dirname( __FILE__ ) . '/' . $js )
        );
        wp_localize_script( $editor_script, 'blockname', $this->settings['block']['blockname'] );

        $test = 'function() {alert("edit block")}';

        $css = '../assets/css/gutenberg.css';
        $editor_style = 'gutenberg-css';
        wp_register_style( $editor_style, plugins_url( $css, __FILE__ ) );
        register_block_type( $this->settings['block']['blocktype'], array(
            'editor_script' => $editor_script,
            'style' => $editor_style,
            'edit' => $test,
            'render_callback' => [$this, 'shortcodeOutput'],
            'attributes' => $this->settings
            // 'attributes' => $this->fill_gutenberg_options()
            ) 
        );

        wp_localize_script( $editor_script, $this->settings['block']['blockname'] . 'Config', $this->settings );
        // wp_localize_script( $editor_script, $this->settings['block']['blockname'] . 'Config', $this->fill_gutenberg_options() );
    }
}
