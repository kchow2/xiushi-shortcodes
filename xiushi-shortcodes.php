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

function xs_shortcodes_init(){
    wp_register_style('xs_css', plugins_url('style.css',__FILE__ ));
    wp_enqueue_style('xs_css');
}
add_action('init', 'xs_shortcodes_init');


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
    usort($resultData, function ($a, $b){return strcasecmp($a['name'], $b['name']);});

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
            $url = '/category-faq?wpv-category='.$cat->slug;
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

    $res = '<div class="xs-all-faqs">';
    $res .= '<div class="row hr-content-block">';
    
    //$res .= '<h3>' . $title . '</h3>';
    foreach ($data as $category) {
        if($category['count'] > 0 || count($category['children']) > 0){
            if(isset($category['children'])){
                $res .= '<div class="col-sm-6">';
                $res .= '<h3>' . $category['description'] . ' <span style="font-size:0.8em;">' . $category['name'] . '</span></h3>'; //$category['count'])
                //$res .= '<ul>';
                $res .= '<div class="hr-subcategory-list">';
                foreach($category['children'] as $category){
                    //$res .= '<li>';
                    $res .= '<div class="hr-subcategory-list-item"><span class="dashicons dashicons-marker hr-dashicon-category" style="color: #CDDC39;line-height: 22px;margin-right: 2px; font-size: 15px;"></span>';
                    $res .= '<a href="' . $category['url'].'">';
                    $res .= $category['description'] . ' | <span style="font-size: 0.8em">' . $category['name'] . '</span>';
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


//USAGE: [xs-tooltip view="view-name" listingid="xx"]
function xs_tooltip($atts){
    
    $linkHTML = "CLICKME";
    $divId = "xsModal-";
    
    if(!isset($atts['listingid'])){
        $contentHTML = "xs-tooltip: listingid attribute not set!";
    }
    else if(!isset($atts['view'])){
        $contentHTML = "xs-tooltip: view attribute not set!";
    }
    else if(function_exists("render_view")){    
        $divId .= $atts['listingid'];
        //$debugInfo = "<br>DEBUG INFO:<br>view name=".$atts["view"]."<br>listingid=".$atts['listingid']."<br>";
        $contentHTML = render_view(array('title' => $atts["view"], 'listingid'=>$atts['listingid'] ));
    }
    else{
        $contentHTML = "xs_tooltip: wpv-views plugin required for this dialog to work properly!";
    }
    
    $ret = '<a href="#'.$divId.'">'.$linkHTML.'</a><div id="'.$divId.'" class="modalDialog"><div><a href="#close" title="Close" class="close">X</a>'.$contentHTML.'</div></div>';
    return $ret;
}
add_shortcode('xs-tooltip', 'xs_tooltip');

//USAGE: [xs-cpt-link type="<type>" cat="category-name"]
function xs_cpt_link($atts){
    if(!isset($atts['type']) || !isset($atts['cat'])){
        return 'xs_cpt_link: Usage: [xs-cpt-link type="type" cat="category-name"]';
    }
    
    $cat['type'] = strtolower($cat['type']);
    
    $postTypes = array(
        'recommended'=>array('post', 'pointer'),    //for 'recommended', look for post types 'post' and 'pointer'
    );
    $typeToUrl = array(
        'pointer'=>'pointer',
        'post'=>'category-news',
        'user-image'=>'user-image',
        'listing'=>'listing',
        'recommended'=>'recommended-posts',
        'faq'=>'category-faq'
        );
    $typeDesc = array(
        'pointer'=>"&#19987;&#23478;&#25351;&#24341;",
        'post'=>"&#34892;&#19994;&#36164;&#35759;",
        'user-image'=>"&#28789;&#24863;&#21457;&#25496;",
        'listing'=>"&#21830;&#23478;&#30446;&#24405;",
        'recommended'=>"&#20837;&#38376;&#24517;&#35835;",
        'faq'=>"&#24120;&#35265;&#38382;&#39064;"
    );
    /*$typeDesc = array(
        'pointer'=>"\u{4e13}\u{5bb6}\u{6307}\u{5f15}",
        'news'=>"\u{884c}\u{4e1a}\u{8d44}\u{8baf}",
        'image'=>"\u{7075}\u{611f}\u{53d1}\u{6398}",
        'listing'=>"\u{5546}\u{5bb6}\u{76ee}\u{5f55}",
        'recommended'=>"\u{5165}\u{95e8}\u{5fc5}\u{8bfb}",
        'faq'=>"\u{5e38}\u{89c1}\u{95ee}\u{9898}"
    );*/
    
    $postType = isset($postTypes[$atts[type]]) ? $postTypes[$atts[type]] : $atts[type];
    $args = array(
        'post_type'=>$postType,
        'category_name' => $atts['cat'],
    );
    $the_query = new WP_Query($args);
    $categoryPosts = $the_query->found_posts;
    
    //If type == 'recommended', if posts exist for (1) the cat and (2) terms "Featured" and "Recommended" exist for Taxonomy "Recommendations" then proceed, if not, discard.
    if($categoryPosts > 0 && $atts['type'] == 'recommended'){   
        $matchingPosts = 0;
        while ( $the_query->have_posts() ) {           
            $the_query->the_post();
            $terms = get_terms('recommendation');
            $termStrings = array();
            foreach($terms as $term){
                $termStrings[] = $term->slug;
            }
            if(in_array('featured', $termStrings) || in_array('recommended', $termStrings)){
                $matchingPosts++;
                break;
            }
	}
        if($matchingPosts === 0){
            return $typeDesc['recommended'];
        }
    }

    $ret = "";
    if($categoryPosts > 0){ //if the cat has posts, output a link
        $format = '<a href="http://www.xiushi.ca/##TYPE##/?wpv-category=##CAT##">##DESC##</a>';
        $typeUrl = array_key_exists($atts['type'], $typeToUrl) ? $typeToUrl[$atts['type']] : $atts['type'];
        $desc = array_key_exists($atts['type'], $typeDesc) ? $typeDesc[$atts['type']] : $atts['type'];
        
        $replace = array('##TYPE##', '##CAT##', '##DESC##', '##COUNT##');
        $replaceWith = array($typeUrl, $atts['cat'], $desc, $categoryPosts);
        $ret = str_replace($replace, $replaceWith, $format);
    }
    else{   //if the cat has no posts, output the desc without link
        $ret = $typeDesc[$atts['type']];
    }
    
    return $ret;
}
add_shortcode('xs-cpt-link', 'xs_cpt_link');
