<?php

namespace RRZE\Glossar\Server;

/*
 *  $content = wp_remote_get("https://wordpress.dev/wp-json/wp/v2/glossary?filter[glossary_category]=studium-a-z&per_page=200", $args );
 */

Class FaqListTableHelper {

    public static function getGlossaryForWPListTable() {
        
        $args = array(
            'sslverify'   => false,
        );
        
        $registeredDomains = get_option('registerDomain');
        
        if($registeredDomains) { 
        
            foreach($registeredDomains as $k => $v) {

                $content = wp_remote_get("https://{$v}/wp-json/wp/v2/glossary?per_page=2000", $args );

                $status_code = wp_remote_retrieve_response_code( $content );

                if ( 200 === $status_code ) {

                    $response[] = $content['body'];
                    
                    $category = wp_remote_get("https://{$v}/wp-json/wp/v2/glossary_category?per_page=100", $args);

                    $categories[] = $category['body'];
                    
                    $clean1 = array_filter($categories);

                    $o = array();
                    
                    foreach($categories as $a => $q) {
                        $cat = json_decode($clean1[$a], true);
                    }
                    
                    echo '<pre>';
                    print_r($cat[0]['slug']);
                    print_r(count($cat));
                    echo '</pre>';
                    
                    for($z = 0;  $z < sizeof($cat); $z++) {
                        $o[$z]['id'] = $cat[$z]['id'];
                        $o[$z]['slug'] = $cat[$z]['slug'];
                    }
                    
                    $flag = 1;
                }
            }

            if($flag == 1) {
                $clean = array_filter($response);

                foreach($clean as $c => $v) {
                    $list[$c] = json_decode($clean[$c], true);
                }

                $i = 1;
                foreach($list as $k => $v) {
                    foreach($v as $b => $c) {
                        $item[$i]['id']         = $c['id'];
                        $item[$i]['title']      = $c['title']['rendered'];
                        $item[$i]['content']    = $c['content']['rendered'];
                        $output = '';
                        $items = $c['glossary_category'];
                        $count_items = (count($items));
                        foreach($items as $t => $e) {
                          foreach($o as $w => $p) {
                            if ($e === $o[$w]['id']) {
                                echo $o[$w]['slug'];
                              $output .= $o[$w]['slug'] . ($count_items > 1 ? ',' : '');
                            }
                          }
                        }
                        //$out = ($count_items > 1) ? substr($output, 0, -1) : '';
                        $item[$i]['category'] = $output;
                        $url = parse_url($c['guid']['rendered']);
                        $item[$i]['domain']     = $url['host'];
                        $i++;
                    }
                }
                return $item;
            } else {
                return;
            }
        } else {
            return;
        } 
    }
}