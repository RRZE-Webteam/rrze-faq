<?php

namespace RRZE\FAQ;

defined('ABSPATH') || exit;

define ('ENDPOINT', 'wp-json/wp/v2/faq' );

class API {

    private $aAllCats = array();

    protected function checkDomain( &$url ){
        $content = wp_remote_get( $url . ENDPOINT . '?per_page=1' );
        $status_code = wp_remote_retrieve_response_code( $content );
        return ( $status_code != 200 ? FALSE : TRUE );
    }

    protected function isRegisteredDomain( &$url ){
        return in_array( $url, $this->getDomains() );
    }

    public function getDomains(){
        $domains = array();
        $options = get_option( 'rrze-faq' );
        if ( isset( $options['registeredDomains'] ) ){
            foreach( $options['registeredDomains'] as $shortname => $url ){
                $domains[$shortname] = $url;
            }	
        }
        asort( $domains );
        return $domains;
    }
    
    public function setDomain( &$shortname, &$url ){
        $ret = FALSE;
        $url = trailingslashit( preg_replace( "/^((http|https):\/\/)?/i", "https://", $url ) );
        $domains = $this->getDomains();
        $shortname = strtolower( preg_replace('/[^A-Za-z0-9\-]/', '', str_replace( ' ', '-', $shortname ) ) );
        if ( ( in_array( $url, $domains ) === FALSE ) && ( array_key_exists( $shortname, $domains ) === FALSE ) ) {
            if ( $this->checkDomain( $url ) ){
                $domains[$shortname] = $url;
            }else{
                return FALSE;
            }
        }
        return $domains;
    }

    public function deleteDomain( &$shortname ){
        $this->deleteFAQ( $shortname );
    }

    protected function getTaxonomies( $url, $field, &$filter ){
        $aRet = array();    
        $url .= ENDPOINT . '_' . $field;    
        $slug = ( $filter ? '&slug=' . $filter : '' );
        $page = 1;

        do {
            $request = wp_remote_get( $url . '?page=' . $page . $slug );
            $status_code = wp_remote_retrieve_response_code( $request );
            if ( $status_code == 200 ){
                $entries = json_decode( wp_remote_retrieve_body( $request ), true );
                if ( !empty( $entries ) ){
                    foreach( $entries as $entry ){
                        if ( $entry['source'] == 'website' ){                            
                            if ( $entry['children'] ) {
                                foreach( $entry['children'] as $childname ){
                                    $aRet[$entry['name']][$childname] = array();        
                                }
                            }else{
                                $aRet[$entry['name']] = array();
                            }
                        }
                    }
                    foreach( $aRet as $name => $aChildren ){
                        foreach ( $aChildren as $childname => $val ){
                            if ( isset( $aRet[$childname] ) ){
                                $aRet[$name][$childname] = $aRet[$childname];
                            }
                        }
                    }
                }
            }
            $page++;   
        } while ( ( $status_code == 200 ) && ( !empty( $entries ) ) );
        return $aRet;
    }

    
    public function sortIt( &$arr ){
        uasort( $arr, function($a, $b) {
            return strtolower( $a ) <=> strtolower( $b );
        } );
    }
    
    public function deleteTaxonomies( $source, $field ){
        $args = array(
            'hide_empty' => FALSE,
            'meta_query' => array(
                array(
                   'key'       => 'source',
                   'value'     => $source,
                   'compare'   => '='
                )
            ),
            'taxonomy'  => 'faq_' . $field,
            'fields' => 'ids'
            );
        $terms = get_terms( $args );
        foreach( $terms as $ID  ){
            wp_delete_term( $ID, 'faq_' . $field );
        }
    }


    public function deleteCategories( $source ){
        $this->deleteTaxonomies( $source, 'category');
    }

    public function deleteTags( $source ){
        $this->deleteTaxonomies( $source, 'tag');
    }

    protected function setCategories( &$aCategories, &$shortname ){
        $aTmp = $aCategories;
        foreach ( $aTmp as $name => $aDetails ){
            $term = term_exists( $name, 'faq_category' );
            if ( !$term ) {
                $term = wp_insert_term( $name, 'faq_category' );
                update_term_meta( $term['term_id'], 'source', $shortname );    
            }
            foreach ( $aDetails as $childname => $tmp ) {
                $childterm = term_exists( $childname, 'faq_category' );
                if ( !$childterm ) {
                    $childterm = wp_insert_term( $childname, 'faq_category', array( 'parent' => $term['term_id'] ) );
                    update_term_meta( $childterm['term_id'], 'source', $shortname );    
                }
            }
            if ( $aDetails ){
                $aTmp = $aDetails;
            }
        }
    }

    
    public function sortAllCats( &$cats, &$into ) {
        foreach ($cats as $ID => $aDetails) {
            $into[$ID]['slug'] = $aDetails['slug'];
            $into[$ID]['name'] = $aDetails['name'];            
            if ( $aDetails['parentID'] ) {
                $parentID = $aDetails['parentID'];
                $into[$parentID][$ID]['slug'] = $aDetails['slug'];
                $into[$parentID][$ID]['name'] = $aDetails['name'];
            }
            unset( $cats[$parentID] );
        }    
        $this->sortAllCats( $cats, $into );
    }


    public function sortCats(Array &$cats, Array &$into, $parentID = 0, $prefix = '' ) {
        $prefix .= ( $parentID ? '-' : '' );
        foreach ($cats as $i => $cat) {
            if ( $cat->parent == $parentID ) {
                $into[$cat->term_id] = $cat;                
                unset( $cats[$i] );
            }
            $this->aAllCats[$cat->term_id]['parentID'] = $cat->parent;
            $this->aAllCats[$cat->term_id]['slug'] = $cat->slug;
            // $this->aAllCats[$cat->term_id]['name'] = ltrim( $prefix . ' ' . $cat->name );
            $this->aAllCats[$cat->term_id]['name'] = str_replace( '~', '&nbsp;', str_pad( ltrim( $prefix . ' ' . $cat->name ), 100, '~') );
        }    
        foreach ($into as $topCat) {
            $topCat->children = array();
            $this->sortCats($cats, $topCat->children, $topCat->term_id, $prefix );
        }
        if ( !$cats ){
            foreach ( $this->aAllCats as $ID => $aDetails ){
                if ( $aDetails['parentID'] ){
                    $this->aAllCats[$aDetails['parentID']]['children'][$ID] = $this->aAllCats[$ID];
                }
            }
        } 
    }

    public function cleanCats(){
        foreach ( $this->aAllCats as $ID => $aDetails ){
            if ( $aDetails['parentID'] ){
                unset( $this->aAllCats[$ID] );
            }
        }
    }

    public function getSlugNameCats(&$cats, &$into ){
        foreach ( $cats as $i => $cat ){
            $into[$cat['slug']] = $cat['name'];
            if ( isset( $cat['children'] ) ){
                $this->getSlugNameCats($cat['children'], $into );
            }
            unset( $cats[$i] );
        }
    }

    public function getCategories( $url, $shortname, $categories = '' ){
        $aRet = array();
        $aCategories = $this->getTaxonomies( $url, 'category', $categories );
        $this->setCategories( $aCategories, $shortname );
        $categories = get_terms( array(
            'taxonomy' => 'faq_category',
            'meta_query' => array( array(
                'key' => 'source',
                'value' => $shortname
            ) ),
            'hide_empty' => FALSE
            ) );
        $categoryHierarchy = array();
        $this->sortCats($categories, $categoryHierarchy);
        $this->cleanCats();
        $this->getSlugNameCats( $this->aAllCats, $aRet );
        return $aRet;
    }


    public function deleteFAQ( $source ){
        // deletes all FAQ by source
        $iDel = 0;
        $allFAQ = get_posts( array( 'post_type' => 'faq', 'meta_key' => 'source', 'meta_value' => $source, 'numberposts' => -1 ) );

        foreach ( $allFAQ as $faq ) {
            wp_delete_post( $faq->ID, TRUE );
            $iDel++;
        } 
        return $iDel;
    }

    protected function getFAQ( &$url, &$categories ){
        $faqs = array();
        $aCategoryRelation = array();
        $filter = '&filter[faq_category]=' . $categories;
        $page = 1;

        do {
            $request = wp_remote_get( $url . ENDPOINT . '?page=' . $page . $filter );
            $status_code = wp_remote_retrieve_response_code( $request );
            if ( $status_code == 200 ){
                $entries = json_decode( wp_remote_retrieve_body( $request ), true );
                if ( !empty( $entries ) ){
                    if ( !isset( $entries[0] ) ){
                        $entries = array( $entries );
                    }
                    foreach( $entries as $entry ){
                        if ( $entry['source'] == 'website' ){
                            $content = substr( $entry['content']['rendered'], 0, strpos( $entry['content']['rendered'], '<!-- rrze-faq -->' ));

                            $faqs[$entry['id']] = array(
                                'id' => $entry['id'],
                                'title' => $entry['title']['rendered'],
                                'content' => $content,
                                'lang' => $entry['lang'],
                                'faq_category' => $entry['faq_category'],
                                'remoteID' => $entry['remoteID'],
                                'remoteChanged' => $entry['remoteChanged']
                            );
                            $sTag = '';
                            foreach ( $entry['faq_tag'] as $tag ){
                                $sTag .= $tag . ',';
                            }
                            $faqs[$entry['id']]['faq_tag'] = trim( $sTag, ',' );
                        }
                    }
                }
            }
            $page++;   
        } while ( ( $status_code == 200 ) && ( !empty( $entries ) ) );

        return $faqs;
    }

    public function setTags( $terms, $shortname ){
        if ( $terms ){
            $aTerms = explode( ',', $terms );
            foreach( $aTerms as $name ){
                if ( $name ){
                    $term = term_exists( $name, 'faq_tag' );
                    if ( !$term ) {
                        $term = wp_insert_term( $name, 'faq_tag' );
                        update_term_meta( $term['term_id'], 'source', $shortname );    
                    }
                }
            }
        }
    }

    public function getFAQRemoteIDs( $source ){
        $aRet = array();
        $allFAQ = get_posts( array( 'post_type' => 'faq', 'meta_key' => 'source', 'meta_value' => $source, 'fields' => 'ids', 'numberposts' => -1 ) );
        foreach ( $allFAQ as $postID ){
            $remoteID = get_post_meta( $postID, 'remoteID', TRUE );
            $remoteChanged = get_post_meta( $postID, 'remoteChanged', TRUE );
            $aRet[$remoteID] = array(
                'postID' => $postID,
                'remoteChanged' => $remoteChanged
                );
        }
        return $aRet;
    }

    public function setFAQ( $url, $categories, $shortname ){
        $iNew = 0;
        $iUpdated = 0;
        $iDeleted = 0;

        // get all remoteIDs of stored FAQ to this source ( key = remoteID, value = postID )
        $aRemoteIDs = $this->getFAQRemoteIDs( $shortname );

        // $this->deleteTags( $shortname );
        // $this->deleteCategories( $shortname );
        // $this->getCategories( $url, $shortname );

        // get all FAQ
        $aFaq = $this->getFAQ( $url, $categories );
        
        // set FAQ
        foreach ( $aFaq as $faq ){
            $this->setTags( $faq['faq_tag'], $shortname );

            $aCategoryIDs = array();
            foreach ( $faq['faq_category'] as $name ){
                $term = get_term_by( 'name', $name, 'faq_category' );
                $aCategoryIDs[] = $term->term_id;
            }

            if ( isset( $aRemoteIDs[$faq['remoteID']] ) ) {
                if ( $aRemoteIDs[$faq['remoteID']]['remoteChanged'] < $faq['remoteChanged'] ){
                    // update FAQ
                    $post_id = wp_update_post( array(
                        'ID' => $aRemoteIDs[$faq['remoteID']]['postID'],
                        'post_name' => sanitize_title( $faq['title'] ),
                        'post_title' => $faq['title'],
                        'post_content' => $faq['content'],
                        'meta_input' => array(
                            'source' => $shortname,
                            'lang' => $faq['lang'],
                            'remoteID' => $faq['remoteID']
                            ),
                        'tax_input' => array(
                            'faq_category' => $aCategoryIDs,
                            'faq_tag' => $faq['faq_tag']
                            )
                        ) ); 
                        $iUpdated++;
                }
                unset( $aRemoteIDs[$faq['remoteID']] );
            } else {
                // insert FAQ
                $post_id = wp_insert_post( array(
                    'post_type' => 'faq',
                    'post_name' => sanitize_title( $faq['title'] ),
                    'post_title' => $faq['title'],
                    'post_content' => $faq['content'],
                    'comment_status' => 'closed',
                    'ping_status' => 'closed',
                    'post_status' => 'publish',
                    'meta_input' => array(
                        'source' => $shortname,
                        'lang' => $faq['lang'],
                        'remoteID' => $faq['id'],
                        'remoteChanged' => $faq['remoteChanged']
                        ),
                    'tax_input' => array(
                        'faq_category' => $aCategoryIDs,
                        'faq_tag' => $faq['faq_tag']
                        )
                    ) );
                $iNew++;
            }
        }

        // delete all other FAQ to this source
        foreach( $aRemoteIDs as $remoteID => $aDetails ){
            wp_delete_post( $aDetails['postID'], TRUE );
            $iDeleted++;
        }

        return array( 
            'iNew' => $iNew,
            'iUpdated' => $iUpdated,
            'iDeleted' => $iDeleted
        );
    }
}    


