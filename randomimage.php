<?php

/*
Plugin Name: randomimage
Version: 1.3
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
Copyright (C) 2005 Justin Watt
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


function randomimage($show_post_title  = true, 
                     $number_of_images = 1, 
                     $image_attributes = "", 
                     $show_alt_caption = true, 
                     $image_src_regex  = "",
                     $post_type        = "posts")
{
    // get access to wordpress' database object
    global $wpdb;

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

    // query records that contain img tags, ordered randomly
    // do not select images from password protected posts
    $sql = "SELECT * 
            FROM $wpdb->posts 
            WHERE post_content LIKE '%<img%'
            AND post_password = ''
            $post_type_sql
            ORDER BY rand()";
    $resultset = mysql_query($sql) or die($sql);

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

        if ($image_src == "")
        {
            continue;
        }

        // if a regex is supplied and it doesn't match, try next post
        if ($image_src_regex != "" && !preg_match("/" . $image_src_regex . "/i", $image_src))
        {
            continue;
        }
           
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
            print "<br />\n";
        }
    }
}


?>