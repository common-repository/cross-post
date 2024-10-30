<?php
require_once('simpletest/unit_tester.php');
require_once('simpletest/reporter.php');
require_once('simpletest/mock_objects.php');
require_once('../../../../wp-config.php');
require_once('../classes/classes.php');

Mock::generate('wpdb','Mockwpdb');

class TestOfCrossPost extends UnitTestCase
{
    function TestOfCrossPost()
	{
        $this->UnitTestCase('CrossPosts class');
    }

    function test_ObjectDefaultInit()
	{
        $obj = new CrossPosts();
        $this->assertNotNull($obj);
    }

    function test_have_posts_true()
    {
        $obj = new CrossPosts(array('post'));
        $this->assertEqual($obj->have_posts(), true);
    }

    function test_have_posts_false()
    {
        $obj = new CrossPosts();
        $this->assertEqual($obj->have_posts(), false);
    }

    function test_post_count()
    {
        $obj = new CrossPosts(array('post1', 'post2', 'post3'));
        $this->assertEqual($obj->post_count(), 3);
    }

    function test_the_post()
    {
        $obj = new CrossPosts(array('post1', 'post2', 'post3'));
        $this->assertEqual($obj->the_post(), 'post1');
        $this->assertEqual($obj->the_post(), 'post2');
        $this->assertEqual($obj->the_post(), 'post3');
    }

    function test_the_post_loop()
    {
        $obj = new CrossPosts(array('post1', 'post2', 'post3'));
        $this->assertEqual($obj->have_posts(), true);
        $this->assertEqual($obj->the_post(), 'post1');
        $this->assertEqual($obj->have_posts(), true);
        $this->assertEqual($obj->the_post(), 'post2');
        $this->assertEqual($obj->have_posts(), true);
        $this->assertEqual($obj->the_post(), 'post3');
        $this->assertEqual($obj->the_post(), false);
        $this->assertEqual($obj->have_posts(), false);
    }

    function test_rewind()
    {
        $obj = new CrossPosts(array('post1', 'post2', 'post3'));
        $this->assertEqual($obj->have_posts(), true);
        $this->assertEqual($obj->the_post(), 'post1');
        $this->assertEqual($obj->have_posts(), true);
        $this->assertEqual($obj->the_post(), 'post2');
        $this->assertEqual($obj->have_posts(), true);
        $this->assertEqual($obj->the_post(), 'post3');
        $this->assertEqual($obj->the_post(), false);
        $this->assertEqual($obj->have_posts(), false);
        $obj->reset();
        $this->assertEqual($obj->have_posts(), true);
        $this->assertEqual($obj->the_post(), 'post1');
        $this->assertEqual($obj->have_posts(), true);
        $this->assertEqual($obj->the_post(), 'post2');
        $this->assertEqual($obj->have_posts(), true);
        $this->assertEqual($obj->the_post(), 'post3');
        $this->assertEqual($obj->the_post(), false);
        $this->assertEqual($obj->have_posts(), false);
    }

    function test_get_query_terms()
    {
        $obj = new CrossPosts();
        $query = 'author_name=jeremy&not_valid=foobar&post_status=publish';
        $terms = $obj->get_query_terms($query, 1);
        $this->assertNotNull($terms);
    }

    function test_get_blog_ids_multiple()
    {
        $obj = new CrossPosts();
        $blog_ids = '1 3 5 4';
        $result = $obj->get_blog_ids($blog_ids);
        $this->assertEqual(count($result), 4);
        $this->assertEqual($result[0], 1);
        $this->assertEqual($result[1], 3);
        $this->assertEqual($result[2], 5);
        $this->assertEqual($result[3], 4);
    }

    function test_get_blog_ids_single()
    {
        $obj = new CrossPosts();
        $blog_ids = '5';
        $result = $obj->get_blog_ids($blog_ids);
        $this->assertEqual(count($result), 1);
        $this->assertEqual($result[0], 5);
    }

    function test_get_blog_ids_default()
    {
        $mock_wpdb = &new Mockwpdb();
        $mock_wpdb->setReturnValue('get_results', array(1, 2, 3, 5));
        $obj = new CrossPosts(array(), $mock_wpdb);
        $result = $obj->get_blog_ids();
        $this->assertEqual(count($result), 4);
        $this->assertEqual($result[0], 1);
        $this->assertEqual($result[1], 2);
        $this->assertEqual($result[2], 3);
        $this->assertEqual($result[3], 5);
    }

    function test_format_category_term_in_cat()
    {
        $obj = new CrossPosts();
        $name_value = array('cat', '883');
        $bog_id = 2;
        $result = $obj->format_category_term($name_value, $blog_id);

        $this->assertEqual($result->condition, 'terms.term_id=883');
        $this->assertEqual($result->table, 'wp__terms terms, wp__term_relationships term_relationships, wp__term_taxonomy term_taxonomy');
        $this->assertEqual($result->join, 'terms.term_id=term_taxonomy.term_id and term_taxonomy.term_taxonomy_id=term_relationships.term_taxonomy_id and term_relationships.object_id=posts.ID');
        $this->assertEqual($result->scope, 'current');
    }

    function test_format_category_term_not_in_cat()
    {
        $obj = new CrossPosts();
        $name_value = array('cat', '-883');
        $bog_id = 2;
        $result = $obj->format_category_term($name_value, $blog_id);

        $this->assertEqual($result->condition, 'terms.term_id!=883');
        $this->assertEqual($result->table, 'wp__terms terms, wp__term_relationships term_relationships, wp__term_taxonomy term_taxonomy');
        $this->assertEqual($result->join, 'terms.term_id=term_taxonomy.term_id and term_taxonomy.term_taxonomy_id=term_relationships.term_taxonomy_id and term_relationships.object_id=posts.ID');
        $this->assertEqual($result->scope, 'current');
    }

    function test_format_category_term_cp_in_cat()
    {
        $obj = new CrossPosts();
        $name_value = array('cp_cat', 'humor');
        $bog_id = 2;
        $result = $obj->format_category_term($name_value, $blog_id);

        $this->assertEqual($result->condition, 'terms.name="humor"');
        $this->assertEqual($result->table, 'wp__terms terms, wp__term_relationships term_relationships, wp__term_taxonomy term_taxonomy');
        $this->assertEqual($result->join, 'terms.term_id=term_taxonomy.term_id and term_taxonomy.term_taxonomy_id=term_relationships.term_taxonomy_id and term_relationships.object_id=posts.ID');
        $this->assertEqual($result->scope, 'all');
    }

    function test_format_category_term_cp_not_in_cat()
    {
        $obj = new CrossPosts();
        $name_value = array('cp_cat', '-humor');
        $bog_id = 2;
        $result = $obj->format_category_term($name_value, $blog_id);

        $this->assertEqual($result->condition, 'terms.name!="humor"');
        $this->assertEqual($result->table, 'wp__terms terms, wp__term_relationships term_relationships, wp__term_taxonomy term_taxonomy');
        $this->assertEqual($result->join, 'terms.term_id=term_taxonomy.term_id and term_taxonomy.term_taxonomy_id=term_relationships.term_taxonomy_id and term_relationships.object_id=posts.ID');
        $this->assertEqual($result->scope, 'all');
    }

    function test_get_query_sql()
    {
        $mock_wpdb = &new Mockwpdb();
        $obj = new CrossPosts(array(), $mock_wpdb);
        $blog_ids = array(2, 3, 6, 5);
        $query = 'author_name=starkj&meta_key=top_story&meta_value=yes';
        $result = $obj->get_query_sql($query, $blog_ids, 1);
        $valid_result = 'select * from (select distinct posts.*, "2" as blog_id from wp_2_posts posts , wp_users users, wp_2_postmeta meta, wp_crossposts xp where posts.post_status="publish"  and posts.post_type="post" and users.ID=posts.post_author and meta.post_id=posts.ID and xp.post_id=posts.ID  and users.user_nicename="starkj" and meta.meta_key="top_story" and meta.meta_value="yes" and xp.from_blog_id=2 and xp.to_blog_id=1 union select distinct posts.*, "3" as blog_id from wp_3_posts posts , wp_users users, wp_3_postmeta meta, wp_crossposts xp where posts.post_status="publish"  and posts.post_type="post" and users.ID=posts.post_author and meta.post_id=posts.ID and xp.post_id=posts.ID  and users.user_nicename="starkj" and meta.meta_key="top_story" and meta.meta_value="yes" and xp.from_blog_id=3 and xp.to_blog_id=1 union select distinct posts.*, "6" as blog_id from wp_6_posts posts , wp_users users, wp_6_postmeta meta, wp_crossposts xp where posts.post_status="publish"  and posts.post_type="post" and users.ID=posts.post_author and meta.post_id=posts.ID and xp.post_id=posts.ID  and users.user_nicename="starkj" and meta.meta_key="top_story" and meta.meta_value="yes" and xp.from_blog_id=6 and xp.to_blog_id=1 union select distinct posts.*, "5" as blog_id from wp_5_posts posts , wp_users users, wp_5_postmeta meta, wp_crossposts xp where posts.post_status="publish"  and posts.post_type="post" and users.ID=posts.post_author and meta.post_id=posts.ID and xp.post_id=posts.ID  and users.user_nicename="starkj" and meta.meta_key="top_story" and meta.meta_value="yes" and xp.from_blog_id=5 and xp.to_blog_id=1) as posts  order by posts.post_date desc';
        $this->assertEqual($result, $valid_result);
    }

    function test_get_query_sql_full()
    {
        $mock_wpdb = &new Mockwpdb();
        $obj = new CrossPosts(array(), $mock_wpdb);
        $blog_ids = $obj->get_blog_ids('2 3 6');
        $query = 'author_name=jeremy&post_status="publish"&date=20080623';
        $result = $obj->get_query_sql($query, $blog_ids, 1);
        $valid_result = 'select * from (select distinct posts.*, "2" as blog_id from wp_2_posts posts , wp_users users, wp_crossposts xp where posts.post_status="publish"  and posts.post_type="post" and users.ID=posts.post_author and xp.post_id=posts.ID  and users.user_nicename="jeremy" and xp.from_blog_id=2 and xp.to_blog_id=1 union select distinct posts.*, "3" as blog_id from wp_3_posts posts , wp_users users, wp_crossposts xp where posts.post_status="publish"  and posts.post_type="post" and users.ID=posts.post_author and xp.post_id=posts.ID  and users.user_nicename="jeremy" and xp.from_blog_id=3 and xp.to_blog_id=1 union select distinct posts.*, "6" as blog_id from wp_6_posts posts , wp_users users, wp_crossposts xp where posts.post_status="publish"  and posts.post_type="post" and users.ID=posts.post_author and xp.post_id=posts.ID  and users.user_nicename="jeremy" and xp.from_blog_id=6 and xp.to_blog_id=1) as posts  order by posts.post_date desc';
        $this->assertEqual($result, $valid_result);
    }

    function test_get_query_sql_in_category_883()
    {
        $mock_wpdb = &new Mockwpdb();
        $obj = new CrossPosts(array(), $mock_wpdb);
        $blog_ids = $obj->get_blog_ids('2 3 6');
        $query = 'cat=883';
        $result = $obj->get_query_sql($query, $blog_ids, 3);
        $valid_result = 'select * from (select distinct posts.*, "2" as blog_id from wp_2_posts posts , wp_crossposts xp where posts.post_status="publish"  and posts.post_type="post" and xp.post_id=posts.ID  and xp.from_blog_id=2 and xp.to_blog_id=3 union select distinct posts.*, "3" as blog_id from wp_3_posts posts , wp_3_terms terms, wp_3_term_relationships term_relationships, wp_3_term_taxonomy term_taxonomy where posts.post_status="publish"  and posts.post_type="post" and terms.term_id=term_taxonomy.term_id and term_taxonomy.term_taxonomy_id=term_relationships.term_taxonomy_id and term_relationships.object_id=posts.ID  and terms.term_id=883 union select distinct posts.*, "6" as blog_id from wp_6_posts posts , wp_crossposts xp where posts.post_status="publish"  and posts.post_type="post" and xp.post_id=posts.ID  and xp.from_blog_id=6 and xp.to_blog_id=3) as posts  order by posts.post_date desc';
        $this->assertEqual($result, $valid_result);
    }
    
    function test_get_query_sql_category_tables_and_joins()
    {
        $mock_wpdb = &new Mockwpdb();
        $obj = new CrossPosts(array(), $mock_wpdb);
        $blog_ids = $obj->get_blog_ids('2 3 6');
        $query = 'cat=883';
        $result = $obj->get_query_sql($query, $blog_ids, 3);
        $this->assertPattern('/terms\.term_id=883/', $result);
        $this->assertPattern('/wp_3_terms terms/', $result);
        $this->assertPattern('/wp_3_term_relationships term_relationships/',
            $result);
        $this->assertPattern('/wp_3_term_taxonomy term_taxonomy/', $result);
        $this->assertPattern('/terms.term_id=term_taxonomy.term_id/', $result);
        $this->assertPattern('/term_taxonomy.term_taxonomy_id=term_relationships.term_taxonomy_id/', $result);
        $this->assertPattern('/ term_relationships.object_id=posts.ID/',
            $result);
    }

    function test_get_query_sql_not_in_category_883()
    {
        $mock_wpdb = &new Mockwpdb();
        $obj = new CrossPosts(array(), $mock_wpdb);
        $blog_ids = $obj->get_blog_ids('2 3 6');
        $query = 'cat=-883';
        $result = $obj->get_query_sql($query, $blog_ids, 3);
        $valid_result = 'select * from (select distinct posts.*, "2" as blog_id from wp_2_posts posts , wp_crossposts xp where posts.post_status="publish"  and posts.post_type="post" and xp.post_id=posts.ID  and xp.from_blog_id=2 and xp.to_blog_id=3 union select distinct posts.*, "3" as blog_id from wp_3_posts posts , wp_3_terms terms, wp_3_term_relationships term_relationships, wp_3_term_taxonomy term_taxonomy where posts.post_status="publish"  and posts.post_type="post" and terms.term_id=term_taxonomy.term_id and term_taxonomy.term_taxonomy_id=term_relationships.term_taxonomy_id and term_relationships.object_id=posts.ID  and terms.term_id!=883 union select distinct posts.*, "6" as blog_id from wp_6_posts posts , wp_crossposts xp where posts.post_status="publish"  and posts.post_type="post" and xp.post_id=posts.ID  and xp.from_blog_id=6 and xp.to_blog_id=3) as posts  order by posts.post_date desc';
        $this->assertEqual($result, $valid_result);
    }

    function test_get_query_sql_in_categories_883_and_777()
    {
        $mock_wpdb = &new Mockwpdb();
        $obj = new CrossPosts(array(), $mock_wpdb);
        $blog_ids = $obj->get_blog_ids('2 3 6');
        $query = 'cat=883&cat=777';
        $result = $obj->get_query_sql($query, $blog_ids, 3);
        $valid_result = 'select * from (select distinct posts.*, "2" as blog_id from wp_2_posts posts , wp_crossposts xp where posts.post_status="publish"  and posts.post_type="post" and xp.post_id=posts.ID  and xp.from_blog_id=2 and xp.to_blog_id=3 union select distinct posts.*, "3" as blog_id from wp_3_posts posts , wp_3_terms terms, wp_3_term_relationships term_relationships, wp_3_term_taxonomy term_taxonomy where posts.post_status="publish"  and posts.post_type="post" and terms.term_id=term_taxonomy.term_id and term_taxonomy.term_taxonomy_id=term_relationships.term_taxonomy_id and term_relationships.object_id=posts.ID  and terms.term_id=883 and terms.term_id=777 union select distinct posts.*, "6" as blog_id from wp_6_posts posts , wp_crossposts xp where posts.post_status="publish"  and posts.post_type="post" and xp.post_id=posts.ID  and xp.from_blog_id=6 and xp.to_blog_id=3) as posts  order by posts.post_date desc';
        $this->assertEqual($result, $valid_result);
    }
    
    function test_get_query_sql_categories_in_777_but_not_883()
    {
        $mock_wpdb = &new Mockwpdb();
        $obj = new CrossPosts(array(), $mock_wpdb);
        $blog_ids = $obj->get_blog_ids('2 3 6');
        $query = 'cat=-883&cat=777';
        $result = $obj->get_query_sql($query, $blog_ids, 3);
        $valid_result = 'select * from (select distinct posts.*, "2" as blog_id from wp_2_posts posts , wp_crossposts xp where posts.post_status="publish"  and posts.post_type="post" and xp.post_id=posts.ID  and xp.from_blog_id=2 and xp.to_blog_id=3 union select distinct posts.*, "3" as blog_id from wp_3_posts posts , wp_3_terms terms, wp_3_term_relationships term_relationships, wp_3_term_taxonomy term_taxonomy where posts.post_status="publish"  and posts.post_type="post" and terms.term_id=term_taxonomy.term_id and term_taxonomy.term_taxonomy_id=term_relationships.term_taxonomy_id and term_relationships.object_id=posts.ID  and terms.term_id!=883 and terms.term_id=777 union select distinct posts.*, "6" as blog_id from wp_6_posts posts , wp_crossposts xp where posts.post_status="publish"  and posts.post_type="post" and xp.post_id=posts.ID  and xp.from_blog_id=6 and xp.to_blog_id=3) as posts  order by posts.post_date desc';
        $this->assertEqual($result, $valid_result);
    }

    function test_get_query_sql_cp_in_category()
    {
        $mock_wpdb = &new Mockwpdb();
        $obj = new CrossPosts(array(), $mock_wpdb);
        $blog_ids = $obj->get_blog_ids('2 3 6');
        $query = 'cp_cat=humor';
        $result = $obj->get_query_sql($query, $blog_ids, 3);
        $valid_result = 'select * from (select distinct posts.*, "2" as blog_id from wp_2_posts posts , wp_2_terms terms, wp_2_term_relationships term_relationships, wp_2_term_taxonomy term_taxonomy, wp_crossposts xp where posts.post_status="publish"  and posts.post_type="post" and terms.term_id=term_taxonomy.term_id and term_taxonomy.term_taxonomy_id=term_relationships.term_taxonomy_id and term_relationships.object_id=posts.ID and xp.post_id=posts.ID  and terms.name="humor" and xp.from_blog_id=2 and xp.to_blog_id=3 union select distinct posts.*, "3" as blog_id from wp_3_posts posts , wp_3_terms terms, wp_3_term_relationships term_relationships, wp_3_term_taxonomy term_taxonomy where posts.post_status="publish"  and posts.post_type="post" and terms.term_id=term_taxonomy.term_id and term_taxonomy.term_taxonomy_id=term_relationships.term_taxonomy_id and term_relationships.object_id=posts.ID  and terms.name="humor" union select distinct posts.*, "6" as blog_id from wp_6_posts posts , wp_6_terms terms, wp_6_term_relationships term_relationships, wp_6_term_taxonomy term_taxonomy, wp_crossposts xp where posts.post_status="publish"  and posts.post_type="post" and terms.term_id=term_taxonomy.term_id and term_taxonomy.term_taxonomy_id=term_relationships.term_taxonomy_id and term_relationships.object_id=posts.ID and xp.post_id=posts.ID  and terms.name="humor" and xp.from_blog_id=6 and xp.to_blog_id=3) as posts  order by posts.post_date desc';
        $this->assertEqual($result, $valid_result);
    }

    function test_get_query_sql_cp_not_in_category()
    {
        $mock_wpdb = &new Mockwpdb();
        $obj = new CrossPosts(array(), $mock_wpdb);
        $blog_ids = $obj->get_blog_ids('2 3 6');
        $query = 'cp_cat=-humor';
        $result = $obj->get_query_sql($query, $blog_ids, 3);
        $valid_result = 'select * from (select distinct posts.*, "2" as blog_id from wp_2_posts posts , wp_2_terms terms, wp_2_term_relationships term_relationships, wp_2_term_taxonomy term_taxonomy, wp_crossposts xp where posts.post_status="publish"  and posts.post_type="post" and terms.term_id=term_taxonomy.term_id and term_taxonomy.term_taxonomy_id=term_relationships.term_taxonomy_id and term_relationships.object_id=posts.ID and xp.post_id=posts.ID  and terms.name!="humor" and xp.from_blog_id=2 and xp.to_blog_id=3 union select distinct posts.*, "3" as blog_id from wp_3_posts posts , wp_3_terms terms, wp_3_term_relationships term_relationships, wp_3_term_taxonomy term_taxonomy where posts.post_status="publish"  and posts.post_type="post" and terms.term_id=term_taxonomy.term_id and term_taxonomy.term_taxonomy_id=term_relationships.term_taxonomy_id and term_relationships.object_id=posts.ID  and terms.name!="humor" union select distinct posts.*, "6" as blog_id from wp_6_posts posts , wp_6_terms terms, wp_6_term_relationships term_relationships, wp_6_term_taxonomy term_taxonomy, wp_crossposts xp where posts.post_status="publish"  and posts.post_type="post" and terms.term_id=term_taxonomy.term_id and term_taxonomy.term_taxonomy_id=term_relationships.term_taxonomy_id and term_relationships.object_id=posts.ID and xp.post_id=posts.ID  and terms.name!="humor" and xp.from_blog_id=6 and xp.to_blog_id=3) as posts  order by posts.post_date desc';
        $this->assertEqual($result, $valid_result);
    }

    function test_get_query_sql_full_defaults()
    {
        $mock_wpdb = &new Mockwpdb();
        $mock_wpdb->setReturnValue('get_results', array(2, 3, 6));
        $obj = new CrossPosts(array(), $mock_wpdb);
        $blog_ids = $obj->get_blog_ids();
        $result = $obj->get_query_sql('', $blog_ids, 1);
        $valid_result = 'select * from (select distinct posts.*, "2" as blog_id from wp_2_posts posts , wp_crossposts xp where posts.post_status="publish"  and posts.post_type="post" and xp.post_id=posts.ID  and xp.from_blog_id=2 and xp.to_blog_id=1 union select distinct posts.*, "3" as blog_id from wp_3_posts posts , wp_crossposts xp where posts.post_status="publish"  and posts.post_type="post" and xp.post_id=posts.ID  and xp.from_blog_id=3 and xp.to_blog_id=1 union select distinct posts.*, "6" as blog_id from wp_6_posts posts , wp_crossposts xp where posts.post_status="publish"  and posts.post_type="post" and xp.post_id=posts.ID  and xp.from_blog_id=6 and xp.to_blog_id=1) as posts  order by posts.post_date desc';
        $this->assertEqual($result, $valid_result);
    }

    function test_get_posts()
    {
        $mock_wpdb = &new Mockwpdb();
        $obj = new CrossPosts(array('foo', 'bar', 'baz'), $mock_wpdb);
        $result = $obj->get_posts();
        $this->assertEqual(count($result), 3);
        $this->assertEqual($result[0], 'foo');
        $this->assertEqual($result[1], 'bar');
        $this->assertEqual($result[2], 'baz');
    }
}

class TestOfCPQueryTerm extends UnitTestCase
{
    function TestOfCPQueryTerm()
	{
        $this->UnitTestCase('CPQueryTerm class');
    }

    function test_ObjectDefaultInit()
	{
        $obj = new CPQueryTerm();
        $this->assertNotNull($obj);
    }

    function test_init_with_terms()
    {
        $obj = new CPQueryTerm('author_name=jeremy', 'wp_users users',
            'users.post_id=posts.ID', 'all');
        $this->assertEqual($obj->condition, 'author_name=jeremy');
        $this->assertEqual($obj->table, 'wp_users users');
        $this->assertEqual($obj->join, 'users.post_id=posts.ID');
        $this->assertEqual($obj->scope, 'all');
    }

    function test_set_terms()
    {
        $obj = new CPQueryTerm();
        $obj->setTerms('author_name=jeremy', 'wp_users users', 
            'users.post_id=posts.ID', 'all');
        $this->assertEqual($obj->condition, 'author_name=jeremy');
        $this->assertEqual($obj->table, 'wp_users users');
        $this->assertEqual($obj->join, 'users.post_id=posts.ID');
        $this->assertEqual($obj->scope, 'all');
    }

    function test_to_string()
    {
        $obj = new CPQueryTerm();
        $obj->setTerms('author_name=jeremy', 'wp_users users', 
            'users.post_id=posts.ID', 'all');
        $result = $obj->__toString();

        $this->assertEqual($result, '(author_name=jeremy, wp_users users, users.post_id=posts.ID, all)');
    }
}

class TestOfCPQueryTerms extends UnitTestCase
{
    function TestOfCPQueryTerms()
    {
        $this->UnitTestCase('CPQueryTerms class');
    }

    function test_ObjectDefaultInit()
	{
        $obj = new CPQueryTerms();
        $this->assertNotNull($obj);
    }

    function test_init_with_terms()
    {
        $terms = array();
        $terms[] = new CPQueryTerm('author_name=jeremy', 'wp_users users',
            'users.post_id=posts.ID', 'all');
        $terms[] = new CPQueryTerm('meta_value=yes', 'wp_postmeta meta',
            'meta.post_id=posts.ID', 'all');
        $obj = new CPQueryTerms($terms);
        $this->assertEqual(count($obj->getTerms()), 2);
        $terms = $obj->getTerms();
        $this->assertEqual($terms[0]->condition, 'author_name=jeremy');
        $this->assertEqual($terms[0]->table, 'wp_users users');
        $this->assertEqual($terms[0]->join, 'users.post_id=posts.ID');
        $this->assertEqual($terms[0]->scope, 'all');
        $this->assertEqual($terms[1]->condition, 'meta_value=yes');
        $this->assertEqual($terms[1]->table, 'wp_postmeta meta');
        $this->assertEqual($terms[1]->join, 'meta.post_id=posts.ID');
        $this->assertEqual($terms[1]->scope, 'all');
    }

    function test_add_term()
    {
        $term = new CPQueryTerm('author_name=jeremy', 'wp_users users',
            'users.post_id=posts.ID', 'all');
        $obj = new CPQueryTerms();
        $this->assertEqual(count($obj->getTerms()), 0);
        $obj->addTerm($term);
        $this->assertEqual(count($obj->getTerms()), 1);
    }

    function test_add_terms()
    {
        $terms = array();
        $terms[] = new CPQueryTerm('author_name=jeremy', 'wp_users users',
            'users.post_id=posts.ID', 'all');
        $terms[] = new CPQueryTerm('meta_value=yes', 'wp_postmeta meta',
            'meta.post_id=posts.ID', 'all');
        $obj = new CPQueryTerms();
        $this->assertEqual(count($obj->getTerms()), 0);
        $obj->addTerms($terms);
        $this->assertEqual(count($obj->getTerms()), 2);
    }

    function test_uniqueness_add_terms()
    {
        $terms = array();
        $terms[] = new CPQueryTerm('author_name=jeremy', 'wp_users users',
            'users.post_id=posts.ID', 'all');
        $terms[] = new CPQueryTerm('meta_value=yes', 'wp_postmeta meta',
            'meta.post_id=posts.ID', 'all');
        $obj = new CPQueryTerms();
        $this->assertEqual(count($obj->getTerms()), 0);
        $obj->addTerms($terms);
        $this->assertEqual(count($obj->getTerms()), 2);
        $terms = array();
        $terms[] = new CPQueryTerm('author_name=joe', 'wp_users users',
            'users.post_id=posts.ID', 'all');
        $terms[] = new CPQueryTerm('meta_value=yes', 'wp_postmeta meta',
            'meta.post_id=posts.ID', 'all');
        $obj->addTerms($terms);
        $this->assertEqual(count($obj->getTerms()), 4);
    }

    function test_get_conditions_default()
    {
        $terms = array();
        $terms[] = new CPQueryTerm('author_name=jeremy', 'wp_users users',
            'users.post_id=posts.ID', 'all');
        $terms[] = new CPQueryTerm('meta_value=yes', 'wp_postmeta meta',
            'meta.post_id=posts.ID', 'all');
        $obj = new CPQueryTerms($terms);
        $conditions = $obj->getConditions();
        $this->assertEqual(count($conditions), 2);
        $this->assertEqual($conditions[0], 'author_name=jeremy');
        $this->assertEqual($conditions[1], 'meta_value=yes');
    }

    function test_get_conditions_current()
    {
        $terms = array();
        $terms[] = new CPQueryTerm('author_name=jeremy', 'wp_users users',
            'users.post_id=posts.ID', 'all');
        $terms[] = new CPQueryTerm('meta_value=yes', 'wp_postmeta meta',
            'meta.post_id=posts.ID', 'all');
        $terms[] = new CPQueryTerm('cat=883', 'wp_terms categories',
            'categories.post_id=posts.ID', 'current');
        $obj = new CPQueryTerms($terms);
        $conditions = $obj->getConditions('current');
        $this->assertEqual(count($conditions), 1);
        $this->assertEqual($conditions[0], 'cat=883');
    }

    function test_get_conditions_current_all()
    {
        $terms = array();
        $terms[] = new CPQueryTerm('author_name=jeremy', 'wp_users users',
            'users.post_id=posts.ID', 'all');
        $terms[] = new CPQueryTerm('meta_value=yes', 'wp_postmeta meta',
            'meta.post_id=posts.ID', 'all');
        $terms[] = new CPQueryTerm('cat=883', 'wp_terms categories',
            'categories.post_id=posts.ID', 'current');
        $terms[] = new CPQueryTerm('xp.from_blog_id=3', 'wp_crosspost xp',
            'xp.post_id=posts.ID', 'foreign');
        $obj = new CPQueryTerms($terms);
        $conditions = $obj->getTables('current all');
        $this->assertEqual(count($conditions), 3);
    }

    function test_get_conditions_foreign()
    {
        $terms = array();
        $terms[] = new CPQueryTerm('author_name=jeremy', 'wp_users users',
            'users.post_id=posts.ID', 'all');
        $terms[] = new CPQueryTerm('meta_value=yes', 'wp_postmeta meta',
            'meta.post_id=posts.ID', 'foreign');
        $terms[] = new CPQueryTerm('cat=883', 'wp_terms categories',
            'categories.post_id=posts.ID', 'current');
        $obj = new CPQueryTerms($terms);
        $conditions = $obj->getConditions('foreign');
        $this->assertEqual(count($conditions), 1);
        $this->assertEqual($conditions[0], 'meta_value=yes');
    }

    function test_get_tables_default()
    {
        $terms = array();
        $terms[] = new CPQueryTerm('author_name=jeremy', 'wp_users users',
            'users.post_id=posts.ID', 'all');
        $terms[] = new CPQueryTerm('meta_value=yes', 'wp_postmeta meta',
            'meta.post_id=posts.ID', 'all');
        $obj = new CPQueryTerms($terms);
        $tables = $obj->getTables();
        $this->assertEqual(count($tables), 2);
        $this->assertEqual($tables[0], 'wp_users users');
        $this->assertEqual($tables[1], 'wp_postmeta meta');
    }

    function test_get_tables_current()
    {
        $terms = array();
        $terms[] = new CPQueryTerm('author_name=jeremy', 'wp_users users',
            'users.post_id=posts.ID', 'all');
        $terms[] = new CPQueryTerm('meta_value=yes', 'wp_postmeta meta',
            'meta.post_id=posts.ID', 'all');
        $terms[] = new CPQueryTerm('cat=883', 'wp_terms categories',
            'categories.post_id=posts.ID', 'current');
        $obj = new CPQueryTerms($terms);
        $tables = $obj->getTables('current');
        $this->assertEqual(count($tables), 1);
        $this->assertEqual($tables[0], 'wp_terms categories');
    }

    function test_get_tables_current_all()
    {
        $terms = array();
        $terms[] = new CPQueryTerm('author_name=jeremy', 'wp_users users',
            'users.post_id=posts.ID', 'all');
        $terms[] = new CPQueryTerm('meta_value=yes', 'wp_postmeta meta',
            'meta.post_id=posts.ID', 'all');
        $terms[] = new CPQueryTerm('cat=883', 'wp_terms categories',
            'categories.post_id=posts.ID', 'current');
        $terms[] = new CPQueryTerm('xp.from_blog_id=3', 'wp_crosspost xp',
            'xp.post_id=posts.ID', 'foreign');
        $obj = new CPQueryTerms($terms);
        $tables = $obj->getTables('current all');
        $this->assertEqual(count($tables), 3);
    }

    function test_get_tables_foreign()
    {
        $terms = array();
        $terms[] = new CPQueryTerm('author_name=jeremy', 'wp_users users',
            'users.post_id=posts.ID', 'all');
        $terms[] = new CPQueryTerm('meta_value=yes', 'wp_postmeta meta',
            'meta.post_id=posts.ID', 'foreign');
        $terms[] = new CPQueryTerm('cat=883', 'wp_terms categories',
            'categories.post_id=posts.ID', 'current');
        $obj = new CPQueryTerms($terms);
        $tables = $obj->getTables('foreign');
        $this->assertEqual(count($tables), 1);
        $this->assertEqual($tables[0], 'wp_postmeta meta');
    }

    function test_get_joins_default()
    {
        $terms = array();
        $terms[] = new CPQueryTerm('author_name=jeremy', 'wp_users users',
            'users.post_id=posts.ID', 'all');
        $terms[] = new CPQueryTerm('meta_value=yes', 'wp_postmeta meta',
            'meta.post_id=posts.ID', 'all');
        $obj = new CPQueryTerms($terms);
        $joins = $obj->getJoins();
        $this->assertEqual(count($joins), 2);
        $this->assertEqual($joins[0], 'users.post_id=posts.ID');
        $this->assertEqual($joins[1], 'meta.post_id=posts.ID');
    }

    function test_get_joins_current()
    {
        $terms = array();
        $terms[] = new CPQueryTerm('author_name=jeremy', 'wp_users users',
            'users.post_id=posts.ID', 'all');
        $terms[] = new CPQueryTerm('meta_value=yes', 'wp_postmeta meta',
            'meta.post_id=posts.ID', 'all');
        $terms[] = new CPQueryTerm('cat=883', 'wp_terms categories',
            'categories.post_id=posts.ID', 'current');
        $obj = new CPQueryTerms($terms);
        $joins = $obj->getJoins('current');
        $this->assertEqual(count($joins), 1);
        $this->assertEqual($joins[0], 'categories.post_id=posts.ID');
    }

    function test_get_joins_current_all()
    {
        $terms = array();
        $terms[] = new CPQueryTerm('author_name=jeremy', 'wp_users users',
            'users.post_id=posts.ID', 'all');
        $terms[] = new CPQueryTerm('meta_value=yes', 'wp_postmeta meta',
            'meta.post_id=posts.ID', 'all');
        $terms[] = new CPQueryTerm('cat=883', 'wp_terms categories',
            'categories.post_id=posts.ID', 'current');
        $terms[] = new CPQueryTerm('xp.from_blog_id=3', 'wp_crosspost xp',
            'xp.post_id=posts.ID', 'foreign');
        $obj = new CPQueryTerms($terms);
        $joins = $obj->getJoins('current all');
        $this->assertEqual(count($joins), 3);
    }

    function test_get_joins_foreign()
    {
        $terms = array();
        $terms[] = new CPQueryTerm('author_name=jeremy', 'wp_users users',
            'users.post_id=posts.ID', 'all');
        $terms[] = new CPQueryTerm('meta_value=yes', 'wp_postmeta meta',
            'meta.post_id=posts.ID', 'foreign');
        $terms[] = new CPQueryTerm('cat=883', 'wp_terms categories',
            'categories.post_id=posts.ID', 'current');
        $obj = new CPQueryTerms($terms);
        $joins = $obj->getJoins('foreign');
        $this->assertEqual(count($joins), 1);
        $this->assertEqual($joins[0], 'meta.post_id=posts.ID');
    }
}

$test = &new TestOfCrossPost();
$test->run(new HtmlReporter());

$test = &new TestOfCPQueryTerm();
$test->run(new HtmlReporter());

$test = &new TestOfCPQueryTerms();
$test->run(new HtmlReporter());
?>
