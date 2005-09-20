<?php

/*
Plugin Name: randomimage
Version: 1.0
Plugin URI: http://justinsomnia.org/2005/09/random-image-plugin-for-wordpress/
Description: Display a random image that links back to the post it came from
Author: Justin Watt
Author URI: http://justinsomnia.org/

Save this file as randomimage.php in /path/to/wordpress/wp-content/plugins/ 
Activate from the Wordpress control panel. 
Add [?php randomimage(); ?] (replacing the square brackets [] with angle brackets <>)
to you index.php or sidebar.php template file where you want the random image to appear.

CHANGELOG

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


function randomimage($show_post_title = true, $number_of_images = 1, $image_attributes = "")
{
    // get access to wordpress' database object
    global $wpdb;

    // query records that contain img tags, ordered randomly
    $sql = "SELECT * 
            FROM $wpdb->posts 
            WHERE post_content LIKE '%<img%'
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
        preg_match("/src\s*=\s*(\"|\')([^\"']+)/i", $image_element, $image_src);
        $image_src = $image_src[2];

        //if (substr($image, 0, 7) != "http://" && !file_exists( dirname(__FILE__) . $image_src))
        //{
        //    continue;
        //}

        // grab the alt attribute and see if it exists, if not supply default
        preg_match("/alt\s*=\s*(\"|\')([^\"']+)/i", $image_element, $image_alt);
        $image_alt = $image_alt[2];

        if ($image_alt == "")
        {
            $image_alt = "random image";
        }
    
        if ($show_post_title)
        {
            print "<strong>" . $post_title . "</strong><br/>";
        }
        print "<a href='$post_permalink'><img src='$image_src' alt='$image_alt' $image_attributes/></a>";
        $image_count++;
        
        if ($image_count == $number_of_images)
        {
            break;
        }
    }
}


?>