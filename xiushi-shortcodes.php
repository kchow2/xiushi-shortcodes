<?php

/*
  Plugin Name: Xiu Shi Shortcodes
  Plugin URI:  http://www.google.ca
  Description: This plugin implements all the custom shortcode functionality for the 'Xiu Shi' website.
  Version:     0.1
  Author:      Kevin Chow
  Author URI:  http://www.google.ca
 */
defined('ABSPATH') or die('No script kiddies please!');

//print debug info
function xs_debug_info_sc($atts){
    $postTypes = get_post_types();
    $taxonomies = get_taxonomies();
    
    $ret = '';
    
    $ret .= 'POST TYPES:<br>';
    foreach($postTypes as $t){
        $ret .= $t . '<br>';
    }
    $ret .= 'TAXONOMIES:<br>';
    foreach($taxonomies as $t){
        $ret .= $t . '<br>';
    }
    
    return $ret;
}
add_shortcode('xs-debug-info', 'xs_debug_info_sc');


//all-faqs shortcode.
//Usage: [all-faqs]
function xs_all_faqs_sc($atts) {
    $resultData = array();
    $topCategories = get_categories(array('taxonomy'=>'category', 'parent'=>0));
    foreach($topCategories as $parentCat){
        $queryArgs = array(
            'posts_per_page' => -1,
            'post_type' => 'faq',
            'tax_query' => array(
                array(
                'taxonomy' => 'category',
                'field' => 'term_id',
                'terms' => $parentCat->term_id,
                )
            )
        );
        $query = new WP_Query($queryArgs);
        $parentFaqCount = $query->found_posts;
        
        $subCats = xs_all_faqs_get_category_tree($parentCat->term_id);
        $resultData[$parentCat->term_id] = array('id'=>$parentCat->term_id, 'description'=>$parentCat->description, 'name'=>$parentCat->name, 'url'=>get_term_link($parentCat, 'category'), 'count'=>$parentFaqCount, 'children'=>$subCats);
    }
    
    //sort top level categories by description
    usort($resultData, function ($a, $b){return strcasecmp($a['description'], $b['description']);});

    //Generate the output
    $res = xs_all_faqs_format_result('All FAQs', $resultData);
    wp_reset_postdata();
    return $res;
}
add_shortcode('xs-all-faqs', 'xs_all_faqs_sc');

function xs_all_faqs_get_category_tree($catID){
    $results = array();
    $childCategoryIDs = get_term_children($catID, 'category');
    foreach($childCategoryIDs as $childID){
        $cat = get_term($childID, 'category');
        $queryArgs = array(
            'posts_per_page' => -1,
            'post_type' => 'faq',
            'tax_query' => array(
                array(
                'taxonomy' => 'category',
                'field' => 'term_id',
                'terms' => $childID,
                )
            )
        );
        $query = new WP_Query($queryArgs);
        if($query->found_posts > 0){
            //$url = get_term_link($cat, 'category')
            $url = '/category-faq?wpv-category='.$cat->name;
            $results[] = array('id'=>$cat->term_id, 'description'=>$cat->description, 'name'=>$cat->name, 'count'=>$query->found_posts, 'url'=>$url);
        }
    }
    usort($results, function ($a, $b){return strcasecmp($a['description'], $b['description']);});
    return $results;
}

//helper function to format the HTML output from the found results
function xs_all_faqs_format_result($title, array $data) {
    if (empty($data))
        return '';

    $res = '<div class="xs-all-faqs"">';
    $res .= '<div class="row hr-content-block">';
    
    //$res .= '<h3>' . $title . '</h3>';
    foreach ($data as $category) {
        if($category['count'] > 0 || count($category['children']) > 0){
            if(isset($category['children'])){
                $res .= '<div class="col-sm-6">';
                $res .= '<h3>' . $category['name'] . ' (' . $category['count'] . ')</h3>';
                //$res .= '<ul>';
                $res .= '<div class="hr-subcategory-list">';
                foreach($category['children'] as $category){
                    //$res .= '<li>';
                    $res .= '<div class="hr-subcategory-list-item"><span class="dashicons dashicons-marker hr-dashicon-category" style="color: #CDDC39;line-height: 22px;margin-right: 2px; font-size: 15px;"></span>';
                    $res .= '<a href="' . $category['url'].'">';
                    $res .= $category['name'] . ' | <span style="font-size: 0.8em">' . $category['description'] . ' (' . $category['count'] . ')</span>';
                    $res .= '</a>';  
                    $res .= '</div>';
                    //$res .= '</li>';
                }
                // $res .= '</ul>';
                $res .= '</div>';//<div class="hr-subcategory-list">
                $res .= '</div>';//<div class="col-sm-6">
            }
        }
    }
    /*
     * <div class="row hr-content-block">
    <div class="col-sm-6">  (6 columns)
              <h3>parent taxonomy title</h3>
              <div class="hr-subcategory-list-item"><span class="dashicons dashicons-marker hr-dashicon-category"></span>[wpv-taxonomy-link] | <span style="font-size: 0.8em">[wpv-taxonomy-description]</span>
          </div>
              
            </div>
     */
    
    $res .= '</div>';//<div class="row hr-content-block">
    $res .= '</div>';//class="xs-all-faqs"
    return $res;
}

