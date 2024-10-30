<?php

if(!class_exists("CPQueryTerm"))
{
/**
* A class that stores a single query term. A query term consists of a condition;
* a table on which the condition is applied; an optional join to another table;
* and a scope that specifies what blogs should be searched (all, current,
* foreign).
*/
class CPQueryTerm
    {
        var $condition;
        var $table;
        var $join;
        var $scope;

        /**
        * Construct a CPQueryTerm.
        * @param string $condition An SQL condition: table.field='foo'
        * @param string $table The table the condition applies to
        * @param string $join An SQL join: table1.id==table2.table1_id
        * @param string $scope Specifies to what blogs this term applies. One
        *     of 'all', 'current' or 'foreign'.
        */
        function CPQueryTerm($condition='', $table='', $join='', $scope='all')
        {
            if($condition && $table && $join)
            {
                $this->setTerms($condition, $table, $join, $scope);
            }
        }

        function setTerms($condition, $table, $join, $scope='all')
        {
            $this->condition = $condition;
            $this->table = $table;
            $this->join = $join;
            $this->scope = $scope;
        }

        function __toString()
        {
            return '('.$this->condition.', '.$this->table.', '.$this->join.', '.
                $this->scope.')';
        }
    }
}

if(!class_exists("CPQueryTerms"))
{
/**
* A class that aggrigates CPQueryTerm objects. It has methods for returning
* lists of term data useful when constructing an SQL query.
*/
    class CPQueryTerms
    {
        var $terms;

        function CPQueryTerms($terms=array())
        {
            $this->terms = array();
            $this->addTerms($terms);
        }

        function addTerm($term)
        {
            $this->terms[] = $term;
        }

        function addTerms($terms)
        {
            foreach($terms as $term)
            {
                $this->addTerm($term);
            }
        }

        function getTerms($scopes='all')
        {
            $scopes = split(' ', $scopes);
            $result = array();
            foreach($scopes as $scope)
            {
                foreach($this->terms as $term)
                {
                    if($term->scope == $scope)
                    {
                        $result[] = $term;
                    }
                }
            }
            
            return $result;
        }

        function getJoins($scopes='all')
        {
            $terms = $this->getTerms($scopes);
            $result = array();
            foreach($terms as $term)
            {
                $result[] = $term->join;
            }

            return array_unique(array_filter($result));
        }

        function getTables($scopes='all')
        {
            $terms = $this->getTerms($scopes);
            $result = array();
            foreach($terms as $term)
            {
                $result[] = $term->table;
            }

            return array_unique(array_filter($result));
        }

        function getConditions($scopes='all')
        {
            $terms = $this->getTerms($scopes);
            $result = array();
            foreach($terms as $term)
            {
                $result[] = $term->condition;
            }

            return array_unique(array_filter($result));
        }
    }
}

if(!class_exists("CrossPosts"))
{

class CrossPosts
{
    var $posts;
    var $db;
    var $sql;
    var $limit;
    var $offset;

    function CrossPosts($posts=array(), $db='')
    {
        global $wpdb;
        if($db != '')
        {
            $this->db = $db;
        }
        else
        {
            $this->db = $wpdb;
        }
        $this->posts = $posts;
        reset($this->posts);
        $this->limit = 0;
        $this->offset = 0;
    }

    function have_posts()
    {
        return current($this->posts);
    }

    function the_post()
    {
        $tmp = each($this->posts);
        return $tmp[1];
    }

    function post_count()
    {
        return count($this->posts);
    }

    function reset()
    {
        reset($this->posts);
    }

    /**
    * Execute a query. Return posts as a side effect via global $cross_posts.
    * @param string $query The query_posts() format query to execute.
    * @param string $blog_ids A space seperated list of blog ids to query
    */
    function query_post($query, $blog_ids)
    {
        $blog_ids = $this->get_blog_ids($blog_ids);
        $sql = $this->get_query_sql($query, $blog_ids, $this->db->blogid);
        $GLOBALS['cross_posts'] = new CrossPosts(
            $this->db->get_results($sql, OBJECT));
    }

    /**
    * Generate valid SQL from member CPQueryTerm objects. Select statements for
    * each blog are unioned into the final result set which is ordered by date.
    * @param string $query The query in standard WordPress query_posts format.
    * @param string $blog_ids A space seperated list of blog_ids to query
    * @param string $current_blog_id The ID of the current/active blog
    * @return A massive SQL statement querying all specified blogs.
    */
    function get_query_sql($query, $blog_ids, $current_blog_id)
    {
        $sql = 'select * from (';
        foreach($blog_ids as $blog_id)
        {
            $scope = 'all foreign';
            if($blog_id == $current_blog_id)
            {
                $scope = 'current all';
            }
            $terms = $this->get_query_terms($query, $blog_id);
            $term = new CPQueryTerm('xp.from_blog_id='.$blog_id,
                'wp_crossposts xp', 'xp.post_id=posts.ID', 'foreign');
            $terms->addTerm($term);
            $term = new CPQueryTerm('xp.to_blog_id='.$current_blog_id,
                'wp_crossposts xp', 'xp.post_id=posts.ID', 'foreign');
            $terms->addTerm($term);

            $sql .= 'select distinct posts.*, "'.$blog_id.'" as blog_id '.
                'from wp_'.$blog_id.'_posts posts ';
            foreach($terms->getTables($scope) as $table)
            {
                $sql .= ', '.$table;
            }
            $sql .= ' where posts.post_status="publish" ';
            $sql .= ' and posts.post_type="post" ';
            foreach($terms->getJoins($scope) as $join)
            {
                $sql .= 'and '.$join.' ';
            }
            foreach($terms->getConditions($scope) as $condition)
            {
                $sql .= ' and '.$condition;
            }

            $sql .= ' union ';
        }

        $sql = substr($sql, 0, -7).') as posts ';

        $sql .= ' order by posts.post_date desc';
        if($this->limit)
        {
            $sql .= ' limit '.$this->limit;
        }
        if($this->offset)
        {
            $sql .= ' offset '.$this->offset;
        }
        return $sql;
    }

    /**
    * Convert a supplied query string into CPQueryTerm objects contained in a
    * CPQueryTerms object. Valid query terms are: data, author_name, meta_key,
    * meta_value, show_posts, offset, cat, cp_cat. All terms behave in the
    * standard WP query_posts manner except cp_cat. cp_cat will query a
    * category name across all blogs. cat always defaults to current blog only.
    * @param string $query The query string.
    * @param string $blog_id The ID of the blog the query applies to.
    * @return A CPQueryTerms object representing the supplied query string.
    */
    function get_query_terms($query, $blog_id)
    {
        $term_to_sql = array(
            'date' => array('posts.post_date="%s"', '', 'all'),
            'author_name' => array('users.user_nicename="%s"', 
                'wp_users users', 'users.ID=posts.post_author', 'all'),
            'meta_key' => array('meta.meta_key="%s"',
                'wp_%s_postmeta meta', 'meta.post_id=posts.ID', 'all'),
            'meta_value' => array('meta.meta_value="%s"',
                'wp_%s_postmeta meta', 'meta.post_id=posts.ID', 'all')
        );
        $result = new CPQueryTerms();
        $conditions = split('&', $query);
        foreach($conditions as $condition)
        {
            $name_value = split('=', $condition);
            if(array_key_exists($name_value[0], $term_to_sql))
            {
                $term = new CPQueryTerm(
                    sprintf($term_to_sql[$name_value[0]][0], $name_value[1]),
                    sprintf($term_to_sql[$name_value[0]][1], $blog_id),
                    $term_to_sql[$name_value[0]][2],
                    $term_to_sql[$name_value[0]][3]);
                $result->addTerm($term);
            }
            elseif($name_value[0] == 'showposts')
            {
                $this->limit = $name_value[1];
            }
            elseif($name_value[0] == 'offset')
            {
                $this->offset = $name_value[1];
            }
            elseif($name_value[0] == 'cat' || $name_value[0] == 'cp_cat')
            {
                $term = $this->format_category_term($name_value, $blog_id);
                $result->addTerm($term);
            }
        }
        return $result;
    }

    function format_category_term($name_value, $blog_id)
    {
        $term = new CPQueryTerm();
        if(substr($name_value[1], 0, 1) == '-')
        {
            if(substr($name_value[0], 0, 3) == 'cp_')
            {
                $term->condition = 'terms.name!="'.
                    substr($name_value[1], 1).'"';
            }
            else
            {
                $term->condition = 'terms.term_id!='.
                    substr($name_value[1], 1);
            }
        }
        else
        {
            if(substr($name_value[0], 0, 3) == 'cp_')
            {
                $term->condition = 'terms.name="'.$name_value[1].'"';
            }
            else
            {
                $term->condition = 'terms.term_id='.$name_value[1];
            }
        }
        $term->table = sprintf('wp_%s_terms terms', $blog_id).', '.
            sprintf('wp_%s_term_relationships term_relationships',
            $blog_id).', '.
            sprintf('wp_%s_term_taxonomy term_taxonomy',
            $blog_id);
        $term->join = 'terms.term_id=term_taxonomy.term_id and '.
            'term_taxonomy.term_taxonomy_id='.
            'term_relationships.term_taxonomy_id and '.
            'term_relationships.object_id=posts.ID';
        if(substr($name_value[0], 0, 3) == 'cp_')
        {
            $term->scope = 'all';
        }
        else
        {
            $term->scope = 'current';
        }

        return $term;
    }

    function get_blog_ids($blog_ids='')
    {
        $blog_ids = split(' ', $blog_ids);
        if(! array_filter($blog_ids))
        {
            $blog_ids = array();
            $all_blog_ids = $this->db->get_results('select distinct blog_id '.
                'from wp_blogs');
            foreach($all_blog_ids as $blog_id)
            {
                $blog_ids[] = $blog_id;
            }
        }
        return $blog_ids;
    }

    function get_posts()
    {
        return $this->posts;
    }
}

}
?>
