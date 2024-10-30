<?php 
/* Plugin Name: Cross Post
URI: http://www.csmonitor.com/
Description: A plugin that allows posts to be cross posted to several blogs.
Author: Jeremy Stark
Version: 1.0 
Author URI: http://www.csmonitor.com/
*/

require_once(ABSPATH . 'wp-content/plugins/cross-post/classes/classes.php');
require_once(ABSPATH . 'wp-content/plugins/cross-post/admin/options.php');

$cross_posts = new CrossPosts();

register_activation_hook(__FILE__,'cross_post_activate');
register_deactivation_hook(__FILE__,'cross_post_deactivate');
add_action('wp_insert_post', 'apply_cross_posts', 10, 1);
add_action('dbx_post_sidebar', 'cross_post_sidebar');
add_action('admin_menu', 'add_cross_post_options');

function cross_post_activate()
{ 
    global $wpdb;
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    $table_name = 'wp_crossposts';
   
    $sql = 'create table '.$table_name.' (
            id bigint unsigned not null auto_increment,
            from_blog_id mediumint(10),
            to_blog_id mediumint(10),
            post_id bigint(20) unsigned,
            index (from_blog_id),
            index (to_blog_id),
            index (post_id),
            unique key  id (id)
    );';

    $wpdb->query($sql);
    // dbDelta($sql); // Doesn't work on stage

    $table_name = 'wp_cross_post_blogs';

    $sql = 'create table '.$table_name.' (
            id bigint unsigned not null auto_increment,
            blog_id bigint(20),
            code char(10),
            title varchar(255),
            index (blog_id),
            unique key  id (id)
    );';

    $wpdb->query($sql);
    // dbDelta($sql); // Doesn't work on stage
}

/**
* Update the cross-post metadata key value for the supplied post.
* @param int $post_id The post id to which the metadata should be applied.
*/
function update_cross_post_meta($post_id)
{
    if(! array_key_exists('cross_post', $_POST))
    {
        return $post_id;
    }
    $_POST['metakeyinput'] = 'cross-post';
    $_POST['metavalue'] = '';
    foreach($_POST['cross_post'] as $selection)
    {
        $_POST['metavalue'] .= $selection.' ';
    }
    delete_post_meta($post_id, 'cross-post');
    add_meta($post_id);
    $_POST['metakeyinput'] = '';
    $_POST['metavalue'] = '';
}

/**
* Update the cross posts table in the database to reflect desired cross posting.
* This method is hooked to the wp_insert_post event.
* @param int $post_id The ID of the post that is being cross posted.
* @return The supplied post_id.
*/
function apply_cross_posts($post_id)
{
    global $wpdb;

    update_cross_post_meta($post_id);

    $cross_posts = split(' ', get_post_meta($post_id, 'cross-post', true));
    $cross_posts = array_filter($cross_posts, 'filter_current_blog');

    delete_crossposts_for($wpdb->blogid, $post_id);

    foreach(array_filter($cross_posts) as $to_blog_id)
    {
        insert_crosspost($wpdb->blogid, $to_blog_id, $post_id);
    }

    return $post_id;
}

function filter_current_blog($blog_id)
{
    if($blog_id == $wpdb->blogid)
    {
        return false;
    }
    return true;
}

function delete_crossposts_for($blog_id, $post_id)
{
    global $wpdb;
    $wpdb->query('delete from wp_crossposts where from_blog_id='.$blog_id.
        ' and post_id='.$post_id);
}

function insert_crosspost($from_blog_id, $to_blog_id, $post_id)
{
    global $wpdb;
    $wpdb->query('insert into wp_crossposts (from_blog_id, to_blog_id, '.
        'post_id) values ('.$from_blog_id.', '.$to_blog_id.', '.$post_id.')');
}

function cross_post_deactivate()
{
    /* Nothing but the rain... */
}

function query_cross_posts($query='', $blog_ids='')
{
    if($blog_ids == '')
    {
        $blog_ids = get_cross_post_blog_ids();
    }
    global $cross_posts;
    $cross_posts = new CrossPosts();
    $cross_posts->query_post($query, $blog_ids);
}

function get_cross_posts($query='', $blog_ids='')
{
    if($blog_ids == '')
    {
        $blog_ids = get_cross_post_blog_ids();
    }
    global $cross_posts;
    query_cross_posts($query, $blog_ids);
    return $cross_posts->get_posts();
}

function have_cross_posts()
{
    global $cross_posts;
    return $cross_posts->have_posts();
}

function the_cross_post()
{
    global $cross_posts;
    $GLOBALS['post'] = $cross_posts->the_post();
    setup_postdata($GLOBALS['post']);
}

function the_cross_post_permalink()
{
    global $post;
    switch_to_blog($post->blog_id);
    echo get_permalink($post->ID);
    restore_current_blog();
}

function get_cross_post_permalink()
{
    global $post;
    switch_to_blog($post->blog_id);
    $link = get_permalink($post->ID);
    restore_current_blog();
    return $link;
}

function get_cross_post_meta($post, $key, $flag)
{
    switch_to_blog($post->blog_id);
    $result = get_post_meta($post->ID, $key, $flag);
    restore_current_blog();
    return $result;
}

function the_cross_post_sub_header()
{
    global $post;
    echo get_cross_post_meta($post, 'subhead', true);
}

function get_cross_posts_top_story($blog_ids='')
{
    if($blog_ids == '')
    {
        $blog_ids = get_cross_post_blog_ids();
    }
    global $cross_posts;
    $query='showposts=1&meta_key=top_story&meta_value=yes';
    query_cross_posts($query, $blog_ids);
    return $cross_posts->get_posts();
}

function get_cross_post_blogs($filter_current=False)
{
    global $wpdb;
    $query = 'select blog_id, code, title from wp_cross_post_blogs';
    if($filter_current)
    {
        $query .= ' where blog_id != '.$wpdb->blogid;
    }
    return $wpdb->get_results($query, OBJECT);
}

function get_cross_post_blog_ids()
{
    $blogs = get_cross_post_blogs();
    $ids = '';
    foreach($blogs as $blog)
    {
        $ids .= $blog->blog_id.' ';
    }
    return trim($ids);
}

/**
* Draw the UI cross post selection widget for the admin. This method is hooked
* to the dbx_post_sidebar event.
*/
function cross_post_sidebar()
{
    global $post;
    echo '<fieldset id="cross_posts" class="dbx-box">';
    echo '<h3 class="dbx-handle">Cross Posts</h3>';
    $blogs = get_cross_post_blogs(True);
    $selected = ' '.get_post_meta($post->ID, 'cross-post', true).' ';
    foreach($blogs as $blog)
    {
        $checked = '';
        if(strstr($selected, ' '.$blog->blog_id.' '))
        {
            $checked = 'checked';
        }
        echo '<input type="checkbox" name="cross_post[]" value="'.
            $blog->blog_id.'" '.$checked.' />';
        echo '<label> '.$blog->title.'</label><br />';
    }
    echo '</fieldset>';
}

?>
