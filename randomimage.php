<?php

/*
Plugin Name: randomimage
Version: 2.1
Plugin URI: http://justinsomnia.org/2005/09/random-image-plugin-for-wordpress/
Description: Display a random image that links back to the post it came from
Author: Justin Watt
Author URI: http://justinsomnia.org/

INSTRUCTIONS

1) Save this file as randomimage.php in /path/to/wordpress/wp-content/plugins/ 
2) Activate "randomimage" from the Wordpress control panel. 
3) Add [?php randomimage(); ?] to your index.php or sidebar.php template file
   in /path/to/wordpress/wp-content/themes/theme-name/ where you want the random image to appear
   (make sure to replace the square brackets [] above with angle brackets <>)

CHANGELOG

2.1
added the ability to selectively filter images by post category
added instructions to the configuration interface

2.0
created administrative interface for managing options

1.4
prevent displaying the same image twice
added inter_image_html option (<br /><br /> by default)

1.3
no longer selects images from password protected pages
added post_type option to determine whether to grab images from posts, pages, or both (this prevents pulling images from draft posts)

1.2
fixed src and alt regexes (which would have stopped at first occurence of a single or double quote, regardless of the first delimiter)
added newlines for prettier printing
added show_alt_caption option to display alt text as caption below image
added image_src_regex option to select images using a regular expression based on the image src attribute

1.1
fixed bug in posts that have multiple images which prevented any picture but the first to be displayed

1.0
inital version

LICENSE

randomimage.php
Copyright (C) 2006 Justin Watt
justincwatt@gmail.com
http://justinsomnia.org/

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

*/


// add configuration page to WordPress
function randomimage_add_page()
{
    add_options_page('Random Image', 'Random Image', 6, __FILE__, 'randomimage_configuration_page');
}
add_action('admin_menu', 'randomimage_add_page');


// helper function to set randomimage defaults (if necessary)
// and return array of options
function get_randomimage_options()
{
    $randomimage_options = get_option('randomimage_options');

    // for forward compatibility, make sure new category_filter option gets set as an array
    if (isset($randomimage_options) && !isset($randomimage_options["category_filter"]))
    {
        $randomimage_options["category_filter"] = array();
        add_option('randomimage_options', $randomimage_options);
    }

    // init default options if options aren't found
    if (!$randomimage_options) 
    {
        $randomimage_options = array("show_post_title"      => true,
                                     "show_alt_caption"     => true,
                                     "show_images_in_posts" => true,
                                     "show_images_in_pages" => false,
                                     "number_of_images"     => 1, 
                                     "image_attributes"     => "", 
                                     "inter_image_html"     => "<br /><br />",
                                     "image_src_regex"      => "",
                                     "category_filter"      => array());
        add_option('randomimage_options', $randomimage_options);
    }

    return $randomimage_options;
}


// generate configuration page
function randomimage_configuration_page()
{
    $randomimage_options = get_randomimage_options();

    // if form has been submitted, save values
    if ( isset($_POST['submit']) )
    {
        // correct for empty image number
        if ($_POST['number_of_images'] < 1)
        {
            $_POST['number_of_images'] = 1;
        }

        // correct for posts and pages being deselected
        if (!isset($_POST['show_images_in_posts']) && !isset($_POST['show_images_in_pages']))
        {
            $_POST['show_images_in_posts'] = "on";
        }

        if (!is_array($_POST['category_filter']))
        {
            $_POST['category_filter'] = array();
        }

        // create array of new options
        $randomimage_options = array(
            "show_post_title"      => $_POST['show_post_title'],
            "show_alt_caption"     => $_POST['show_alt_caption'],
            "show_images_in_posts" => $_POST['show_images_in_posts'],
            "show_images_in_pages" => $_POST['show_images_in_pages'],
            "number_of_images"     => $_POST['number_of_images'],
            "image_attributes"     => stripslashes($_POST['image_attributes']),
            "inter_image_html"     => stripslashes($_POST['inter_image_html']),
            "image_src_regex"      => stripslashes($_POST['image_src_regex']),
            "category_filter"      => $_POST['category_filter']
        );
        update_option('randomimage_options', $randomimage_options);
    }

?>

<div class="wrap">
<h2>Random Image Settings</h2>

<form method="post" action="">

<p><strong>Instructions:</strong> Use the following options to configure how you want the Random Image plugin to appear. The sample image below will reflect the changes you make. When you're satisfied, add <code>&lt?php randomimage(); ?&gt</code> to your index.php or sidebar.php template file located in /<em>path</em>/<em>to</em>/<em>wordpress</em>/wp-content/themes/<em>theme-name</em>/

<div style="clear: both;padding-top:10px;">
<label style="float:left;width:250px;text-align:right;padding-right:6px;" for="show_post_title">Show post title above image?</label>
<div style="float:left;"><input type="checkbox" id="show_post_title" name="show_post_title" <?php if (isset($randomimage_options["show_post_title"])) print "checked='on'"; ?>/>&nbsp;&nbsp;<label for="show_alt_caption">Show <code>alt</code> text below?</label> <input type="checkbox" id="show_alt_caption" name="show_alt_caption" <?php if (isset($randomimage_options["show_alt_caption"])) print "checked='on'"; ?>/> </div>
</div>

<div style="clear: both;padding-top:10px;">
<label style="float:left;width:250px;text-align:right;padding-right:6px;" for="show_images_in_posts">Include images from WordPress posts?</label>
<div style="float:left;"><input type="checkbox" id="show_images_in_posts" name="show_images_in_posts" <?php if (isset($randomimage_options["show_images_in_posts"])) print "checked='on'"; ?>/>&nbsp;&nbsp;<label for="show_images_in_pages">Pages?</label> <input type="checkbox" id="show_images_in_pages" name="show_images_in_pages" <?php if (isset($randomimage_options["show_images_in_pages"])) print "checked='on'"; ?>/><br /></div>
</div>

<div style="clear: both;padding-top:10px;">
<label style="float:left;width:250px;text-align:right;padding-right:6px;padding-top:7px;" for="number_of_images">How many images to display?</label>
<div style="float:left;"><input type="text" id="number_of_images" name="number_of_images" size="1" maxlength="2" <?php if (isset($randomimage_options["number_of_images"])) print "value='" . $randomimage_options["number_of_images"] . "'"; ?>/>&nbsp;&nbsp;<label for="inter_image_html">HTML between images:</label> <input type="text" id="inter_image_html" name="inter_image_html" size="12" <?php if (isset($randomimage_options["inter_image_html"])) print "value='" . stripslashes(htmlspecialchars($randomimage_options["inter_image_html"], ENT_QUOTES)) . "'"; ?>/>  e.g. <code>&lt;br /&gt;&lt;br /&gt;</code></div>
</div>

<div style="clear: both;padding-top:10px;">
<label style="float:left;width:250px;text-align:right;padding-right:6px;padding-top:7px;" for="image_attributes">Optional attributes for each <code>&lt;img&gt;</code> tag:</label>
<div style="float:left;"><input type="text" id="image_attributes" name="image_attributes"  style="width:200px;" <?php if (isset($randomimage_options["image_attributes"])) print "value='" . stripslashes(htmlspecialchars($randomimage_options["image_attributes"], ENT_QUOTES)) . "'"; ?>/> e.g. <code>style="width:200px;"</code></div>
</div>

<div style="clear: both;padding-top:10px;">
<label style="float:left;width:250px;text-align:right;padding-right:6px;padding-top:7px;">Limit by categories:<br />(leave unchecked for all)</label>
<div style="float:left;">

<div style='overflow:auto;height:6em;width:200px;background-color:#efefef;border:1px solid #b2b2b2;padding:2px 0 0 3px;'>
<?php

    // create WordPress-style category multi-select list
    global $wpdb;
    $categories = $wpdb->get_results("SELECT cat_ID, cat_name
                                      FROM $wpdb->categories
                                      ORDER BY cat_name");
    foreach ($categories as $category) {
        print "<label style='display:block;' for='category-$category->cat_ID'><input type='checkbox' value='$category->cat_ID' name='category_filter[]' id='category-$category->cat_ID'" . (in_array( $category->cat_ID, $randomimage_options["category_filter"] ) ? ' checked="checked"' : "") . " />" .  wp_specialchars($category->cat_name) . "</label>\n";
    }
?>
</div>

</div>
</div>

<div style="clear: both;padding-top:10px;">
<label style="float:left;width:250px;text-align:right;padding-right:6px;padding-top:7px;" for="image_src_regex">Regex to match against the <code>&lt;img&gt;</code> <code>src</code>:</label>
<div style="float:left;"><input type="text" id="image_src_regex" name="image_src_regex" style="width:200px;" <?php if (isset($randomimage_options["image_src_regex"])) print "value='" . stripslashes(htmlspecialchars($randomimage_options["image_src_regex"], ENT_QUOTES)) . "'"; ?>/> e.g. <code>images</code></div>
</div>

<div style="clear: both;padding-top:10px;text-align:center;">
<p class="submit"><input type="submit" name="submit" value="Update Options &raquo;" /></p>
</div>
</form>
</div>


<div class="wrap">
<h2>Sample Random Image</h2>
<?php randomimage(); ?>
</div>




<?php
}



function randomimage($show_post_title  = true, 
                     $number_of_images = 1, 
                     $image_attributes = "", 
                     $show_alt_caption = true, 
                     $image_src_regex  = "",
                     $post_type        = "posts",
                     $inter_image_html = "<br /><br />",
                     $category_filter  = "")
{
    // get access to wordpress' database object
    global $wpdb;

    // if no arguments are specified
    // assume we're going with the configuration options
    if (!func_get_args())
    {
        $randomimage_options = get_randomimage_options();

        $show_post_title  = $randomimage_options['show_post_title'];        
        $number_of_images = $randomimage_options['number_of_images'];        
        $image_attributes = $randomimage_options['image_attributes'];        
        $show_alt_caption = $randomimage_options['show_alt_caption'];        
        $image_src_regex  = $randomimage_options['image_src_regex'];        
        $inter_image_html = $randomimage_options['inter_image_html'];

        if (!is_array($randomimage_options['category_filter']))
        {
            $randomimage_options['category_filter'] = array();
        }

        // convert category filter array into a comma-separated list
        $category_filter  = implode(",", $randomimage_options['category_filter']);

        if ($randomimage_options['show_images_in_posts'] == true && $randomimage_options['show_images_in_pages'] == false)
        {
            $post_type = "posts";
        }
        elseif ($randomimage_options['show_images_in_posts'] == false && $randomimage_options['show_images_in_pages'] == true)
        {
             $post_type = "pages";
        }
        else
        {
             $post_type = "both";
        }
    }

    // select the post_type sql for both post pages (post_status = 'static') 
    // and posts (AND post_status = 'publish')
    // or for just pages or for just posts (the default)
    // by adding this where criteria, we also solve the problem
    // of accidentally including images from draft posts.
    if ($post_type == "both")
    {
        $post_type_sql = "AND (post_status = 'publish' OR post_status = 'static')";
    }
    else if ($post_type == "pages")
    {
        $post_type_sql = "AND post_status = 'static'";
    }
    else
    {
        $post_type_sql = "AND post_status = 'publish'";
    }

    // assuming $category_filter is a comma separated list of category ids,
    // modify query to join with post2cat table to select from only the chosen categories
    if ($category_filter != "")
    {
        $category_filter_join  = "LEFT JOIN $wpdb->post2cat ON $wpdb->posts.ID = $wpdb->post2cat.post_id";
        $category_filter_sql   = "AND $wpdb->post2cat.category_id IN ($category_filter)";   
        $category_filter_group = "GROUP BY $wpdb->posts.ID";
    }
    else
    {   
        $category_filter_join  = "";
        $category_filter_sql   = "";
        $category_filter_group = "";
    }

    // query records that contain img tags, ordered randomly
    // do not select images from password protected posts
    $sql = "SELECT $wpdb->posts.ID, $wpdb->posts.post_title, $wpdb->posts.post_content
            FROM $wpdb->posts
            $category_filter_join
            WHERE post_content LIKE '%<img%'
            AND post_password = ''
            $post_type_sql
            $category_filter_sql
            $category_filter_group
            ORDER BY rand()";
    $resultset = mysql_query($sql) or die($sql);

    // keep track of multiple images to prevent displaying dups
    $image_srcs = array();

    // loop through database results one at a time
    $image_count = 0;
    while ($row = mysql_fetch_array($resultset))
    {
        $post_title     = $row['post_title'];
        $post_permalink = get_permalink($row['ID']);
        $post_content   = $row['post_content'];

        // find all img tags
        preg_match_all("/<img[^>]+>/i", $post_content, $matches);

        // if there are none, try again, 
        // if there are many choose one at random
        // otherwise, pick the first one
        if (count($matches[0]) == 0)
        {
            continue;
        }
        elseif (count($matches[0]) > 1)
        {
            $num = rand(0, count($matches[0])-1);
            $image_element = $matches[0][$num];
        }
        else
        {
            $image_element = $matches[0][0];
        }

        // grab the src attribute and see if it exists, if not try again
        preg_match("/src\s*=\s*(\"|')(.*?)\\1/i", $image_element, $image_src);
        $image_src = $image_src[2];

        if ($image_src == "" || in_array($image_src, $image_srcs))
        {
            continue;
        }

        // if a regex is supplied and it doesn't match, try next post
        if ($image_src_regex != "" && !preg_match("/" . $image_src_regex . "/i", $image_src))
        {
            continue;
        }

        // add img src to array to check for dups
        $image_srcs[] = $image_src;
           
        // grab the alt attribute and see if it exists, if not supply default
        preg_match("/alt\s*=\s*(\"|')(.*?)\\1/i", $image_element, $image_alt);
        $image_alt = $image_alt[2];

        if ($image_alt == "")
        {
            $image_alt = "random image";
        }
    
        if ($show_post_title)
        {
            print "\n<strong>" . $post_title . "</strong><br />\n";
        }

        print "<a href='$post_permalink'><img src='$image_src' alt='$image_alt' $image_attributes /></a>";
        
        if ($show_alt_caption && $image_alt != "random image")
        {
            print "<br />\n<em>$image_alt</em>";
        }

        $image_count++;
        
        if ($image_count == $number_of_images)
        {
            print "\n";
            break;
        }
        else
        {
            // print a linebreak between each successive image
            print "$inter_image_html\n";
        }
    }
}


?>