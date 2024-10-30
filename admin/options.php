<?php

function add_cross_post_options()
{
    if(function_exists('add_options_page'))
    {
        add_options_page('Cross Post', 'Cross Post Options', 7,
            'cross_post_options', 'cross_post_options_page');
    }
}

function cross_post_options_page()
{
    if(isset($_POST['action']) && $_POST['action'] == 'update')
    {
        update_cross_post_blogs_table($_POST['blog_list']);
    }
    ?>
    <div class="wrap">
        <form method="post"
              action="options-general.php?page=cross_post_options">
            <?php wp_nonce_field('cross-post-update-options'); ?>
            <div class="submit">
                <input type="submit" name="Submit"
                       value="<?php _e('Update Options &raquo;'); ?>" />
            </div>
            <h2><?php _e('Configuration options for Cross Post'); ?></h2>
            <p>Enter blogs that allow cross posts here:</p>
            <?php
            $blogs = get_cross_post_blogs();
            $text = '';
            foreach($blogs as $blog)
            {
                $text .= $blog->blog_id.' '.$blog->code.' '.$blog->title."\n";
            }
            ?>
            <textarea name="blog_list" cols="60"
                      rows="20"><?php echo $text; ?></textarea>
            <input type="hidden" name="action" value="update" />
            <input type="hidden" name="page_options" value="bucket" />
            <div class="submit">
                <input type="submit" name="Submit"
                       value="<?php _e('Update Options &raquo;'); ?>" />
            </div>
        </form>
    </div>
    <?php
}

function update_cross_post_blogs_table($data)
{
    global $wpdb;
    $wpdb->query($query);
    $rows = split("\r\n", $data);
    $wpdb->query('delete from wp_cross_post_blogs');
    foreach($rows as $row)
    {
        $fields = split(' ', $row, 3);
        if(sizeof($fields) != 3)
        {
            continue;
        }
        $query = 'insert into wp_cross_post_blogs (blog_id, code, title) '.
            'values ('.$fields[0].', "'.$fields[1].'", "'.$fields[2].'")';
        $wpdb->query($query);
    }
}

function cross_post_blog_exists($id)
{
    global $wpdb;
    return $wpdb->get_var('select count(id) from wp_cross_post_blogs where '.
        'blog_id='.$id);
}

?>
