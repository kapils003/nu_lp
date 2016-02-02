<?php
if(!class_exists('Groups'))
{
    /**
     * A PostTypeTemplate class that provides 3 additional meta fields
     */
    class Groups
    {
        const POST_TYPE = "group";
        const LP_TYPE   = "post";
        private $_meta  = array(
            'm_username',
            
            );
        
        /**
         * The Constructor
         */
        public function __construct()
        {
            // register actions
            add_action('init', array(&$this, 'init'));
            add_action('admin_init', array(&$this, 'admin_init'));
            add_action('admin_enqueue_scripts',array(&$this, 'lp_groups_styles'));
        } // END public function __construct()

        /**
         * hook into WP's init action hook
         */

        public static function lp_groups_styles() {
            wp_register_style('custom_css', plugins_url('lp-group/css/style.css'));
            wp_enqueue_style('custom_css');
            wp_enqueue_script('chosen-js',plugins_url('lp-group/js/chosen.jquery.js'));
            wp_enqueue_script('custom-js',plugins_url('lp-group/js/custom.js'));
        }
        public function init()
        {
            // Initialize Post Type
            $this->create_post_type();
            add_action('save_post', array(&$this, 'save_post'));
            add_action('save_post', array(&$this, 'save_post_lps'));
            add_action( 'show_user_profile', array(&$this, 'show_group_ids') );
            add_action( 'edit_user_profile', array(&$this, 'show_group_ids') );
            add_action( 'personal_options_update',array(&$this,  'save_extra_user_profile_fields' ));
            add_action( 'edit_user_profile_update',array(&$this,  'save_extra_user_profile_fields' ));
        } // END public function init()

        /**
         * Create the post type
         */
        public function create_post_type()
        {
            register_post_type(self::POST_TYPE,
                array(
                    'labels' => array(
                        'name' => __(sprintf('%ss', ucwords(str_replace("_", " ", self::POST_TYPE)))),
                        'singular_name' => __(ucwords(str_replace("_", " ", self::POST_TYPE)))
                        ),
                    'public' => true,
                    'has_archive' => true,
                    'description' => __("This is a sample post type meant only to illustrate a preferred structure of plugin development"),
                    'supports' => array(
                        'title', 'editor', 
                        ),
                    )
                );
        }

        /**
         * Save the metaboxes for this custom post type
         */
        public function save_post($post_id)
        {
            // verify if this is an auto save routine. 
            // If it is our form has not been submitted, so we dont want to do anything
            if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
            {
                return;
            }
            
            if(isset($_POST['post_type']) && $_POST['post_type'] == self::POST_TYPE && current_user_can('edit_post', $post_id))
            {
                foreach($this->_meta as $field_name) {
                    if($field_name == 'm_username') {
                        // if we are updating m_username, we need old value to compare
                        $old_users = get_post_meta($post_id, 'm_username', true);
                        // when old users don't exist, e.g. group is new, no users were added previously
                        if($old_users == '') {
                            $old_users = array();
                        }
                        // if m_username is empy (all users are removed)
                        if(!empty($_POST['m_username'])) {
                            $new_users = $_POST['m_username'];
                        } else {
                            $new_users = array();
                        }

                        // see if there are any users who are in old group but not new
                        $removed_users = array_diff($old_users, $new_users);
                        update_post_meta($post_id, $field_name, $_POST[$field_name]);
                    }
                    if($field_name == 'm_username') {
                        // we have to update the user meta with group ID as well
                        if(isset($_POST['m_username'])){
                            $users_to_update = $_POST['m_username'];
                        } else {
                            $users_to_update = array();
                        }
                        foreach ($users_to_update as $key => $user_id) {
                            // check if user exists
                            $wp_user_info = get_user_by('id', $user_id);
                            if( $wp_user_info !== FALSE ) {
                                // get meta key for groups.
                                $existing_groups = get_user_meta($user_id, 'group_id', true);
                                if($existing_groups == '') {
                                    // meta key does not exist, we save current ID as array
                                    update_user_meta($user_id, 'group_id', array($post_id));
                                } elseif(is_array($existing_groups)) {
                                    // meta key exists, we add this group only if it does not exist previously
                                    if(array_search($post_id, $existing_groups) === FALSE) {
                                        // append group ID to existing groups and save
                                        $existing_groups[] = $post_id;
                                        update_user_meta($user_id, 'group_id', $existing_groups);
                                    }
                                }
                            }
                        }
                        
                        // if there are any users which are removed, we need to update their meta keys
                        if(!empty($removed_users)) {
                            foreach ($removed_users as $user_id) {
                                // check if user exists
                                $wp_user_info = get_user_by('id', $user_id);

                                if($wp_user_info !== FALSE) {
                                    // get user meta key if user exists
                                    $existing_groups = get_user_meta($user_id, 'group_id', true);

                                    // the key has to be an array
                                    if(is_array($existing_groups)) {
                                        // find the index of current post in user meta. We need it to remove from array later
                                        $current_group_index = array_search($post_id, $existing_groups);
                                        // if key is not found, then we get FALSE. Else, we get the index of key
                                        if($current_group_index !== FALSE) {
                                            // remove the group from $existing_groups
                                            //array_splice($existing_groups, $current_group_index,1);
                                            unset($existing_groups[$current_group_index]);
                                            $existing_groups = array_values($existing_groups);
                                            // update the post_meta
                                            update_user_meta($user_id, 'group_id', $existing_groups);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

                /*if(isset($_POST['m_groups'])) {
                    update_post_meta($post_id, 'm_groups',$_POST['m_groups']);
                }*/
                $old_groups = get_post_meta($post_id, 'm_groups', true);
                if(isset($_POST['m_groups'])) {
                    $new_groups = $_POST['m_groups'];
                } else {
                    $new_groups = array();
                }
                // when old groups don't exist, e.g. user is new, no groups were added previously
                if($old_groups == '') {
                    $old_groups = array();
                }
                
                // if group_ids is empty (all users are removed)
                if(!empty($_POST['m_groups'])) {
                    $new_groups = $_POST['m_groups'];
                } else {
                    $new_groups = array();
                }
                if(!empty($new_groups)) {
                    foreach ($new_groups as $group_id) {
                        // get users for group
                        $user_list = get_post_meta($group_id, 'm_groups', true);
                        // if user list is not empty
                        if(is_array($user_list)) {
                            // find user index
                            $user_index = array_search($user_id, $user_list);
                            if($user_index !== FALSE) {
                                // remove user from array
                                unset($user_list[$user_index]);
                                $user_list = array_values($user_list);
                                //array_splice($user_list, $user_index);\
                                //error_log('user list' .print_r($user_list,true));
                                update_post_meta($group_id, 'm_groups', $user_list);
                            }
                        }
                    }
                }
                // see if there are any groups who are in old key but not new
                $removed_groups = array_diff($old_groups, $new_groups);
                // if we have removed groups, we update them and remove user_id
                if(!empty($removed_groups)) {
                    foreach ($removed_groups as $group_id) {
                        // get users for group
                        $user_list = get_post_meta($group_id, 'lp_groups', true);
                        // if user list is not empty
                        if(is_array($user_list)) {
                            // find user index
                            $user_index = array_search($post_id, $user_list);
                            if($user_index !== FALSE) {
                                // remove user from array
                                //array_splice($user_list, $user_index);
                                unset($user_list[$user_index]);
                                $user_list = array_values($user_list);
                                update_post_meta($group_id, 'lp_groups', $user_list);
                            }
                        }
                    }
                }

                // for each group, we add the user if they are not already there
                foreach ($new_groups as $group_id) {
                    // get group meta
                    $user_list = get_post_meta($group_id, 'lp_groups', true);
                    // we are saving as array for group. It it's anything else, incorrect
                    if(is_array($user_list)){
                        // we save only if user is not in list already
                        if(array_search($post_id, $user_list) === FALSE) {
                            // add to existing list
                            $user_list[] = $post_id;
                            // save to DB
                            update_post_meta($group_id, 'lp_groups', $user_list);
                        }
                    }
                }

                $result = update_post_meta($post_id, 'm_groups', $new_groups);
                return $result; 
            }
            else
            {
                return;
            } // if($_POST['post_type'] == self::POST_TYPE && current_user_can('edit_post', $post_id))
        } // END public function save_post($post_id)
        public function save_post_lps($post_id){
            if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
            {
                return;
            }
            
            if(isset($_POST['post_type']) && $_POST['post_type'] == self::LP_TYPE && current_user_can('edit_post', $post_id)){

                //update_post_meta($post_id, 'lp_groups',$_POST['lp_groups']);
                // if we are updating m_username, we need old value to compare
                $old_groups = get_post_meta($post_id, 'lp_groups', true);
                if(isset($_POST['lp_groups'])) {
                    $new_groups = $_POST['lp_groups'];
                } else {
                    $new_groups = array();
                }
                // when old groups don't exist, e.g. user is new, no groups were added previously
                if($old_groups == '') {
                    $old_groups = array();
                }
                
                // if group_ids is empty (all users are removed)
                if(!empty($_POST['lp_groups'])) {
                    $new_groups = $_POST['lp_groups'];
                } else {
                    $new_groups = array();
                }
                if(!empty($new_groups)) {
                    foreach ($new_groups as $group_id) {
                        // get users for group
                        $user_list = get_post_meta($group_id, 'lp_groups', true);
                        // if user list is not empty
                        if(is_array($user_list)) {
                            // find user index
                            $user_index = array_search($user_id, $user_list);
                            if($user_index !== FALSE) {
                                // remove user from array
                                unset($user_list[$user_index]);
                                $user_list = array_values($user_list);
                                //array_splice($user_list, $user_index);\
                                //error_log('user list' .print_r($user_list,true));
                                update_post_meta($group_id, 'lp_groups', $user_list);
                            }
                        }
                    }
                }
                // see if there are any groups who are in old key but not new
                $removed_groups = array_diff($old_groups, $new_groups);
                // if we have removed groups, we update them and remove user_id
                if(!empty($removed_groups)) {
                    foreach ($removed_groups as $group_id) {
                        // get users for group
                        $user_list = get_post_meta($group_id, 'm_groups', true);
                        // if user list is not empty
                        if(is_array($user_list)) {
                            // find user index
                            $user_index = array_search($post_id, $user_list);
                            if($user_index !== FALSE) {
                                // remove user from array
                                //array_splice($user_list, $user_index);
                                unset($user_list[$user_index]);
                                $user_list = array_values($user_list);
                                update_post_meta($group_id, 'm_groups', $user_list);
                            }
                        }
                    }
                }

                // for each group, we add the user if they are not already there
                foreach ($new_groups as $group_id) {
                    // get group meta
                    $user_list = get_post_meta($group_id, 'm_groups', true);
                    // we are saving as array for group. It it's anything else, incorrect
                    if(is_array($user_list)){
                        // we save only if user is not in list already
                        if(array_search($post_id, $user_list) === FALSE) {
                            // add to existing list
                            $user_list[] = $post_id;
                            // save to DB
                            update_post_meta($group_id, 'm_groups', $user_list);
                        }
                    }
                }

                $result = update_post_meta($post_id, 'lp_groups', $new_groups);
                return $result; 
            }
            else{
                return;
            }
        }
        
        /**
         * hook into WP's admin_init action hook
         */
        public function admin_init()
        {           
            // Add metaboxes
            add_action('add_meta_boxes', array(&$this, 'add_meta_boxes'));
            add_action('add_meta_boxes', array(&$this, 'add_lp_meta_boxes'));  
            add_action('add_meta_boxes', array(&$this, 'add_lp_group_meta_boxes')); 
                     
        }    
        
         // END public function admin_init()

        /**
         * hook into WP's add_meta_boxes action hook
         */
        public function add_meta_boxes()
        {
            // Add this metabox to every selected post
            add_meta_box( 
                sprintf('lp_group_%s_section', self::POST_TYPE),
                sprintf('Add Users', ucwords(str_replace("_", " ", self::POST_TYPE))),
                array(&$this, 'add_inner_meta_boxes'),
                self::POST_TYPE
                );                  
        } // END public function add_meta_boxes()
        /**
         * called off of the add meta box
         */     
        public function add_inner_meta_boxes($post)
        {       
            // Render the job order meta box
            include(sprintf("%s/../templates/%s_metabox.php", dirname(__FILE__), self::POST_TYPE));         
        } // END public function add_inner_meta_boxes($post)
        public function add_lp_meta_boxes(){
            add_meta_box(
                sprintf('lp_post_%s_section', self::POST_TYPE),
                sprintf('Add LPs'),
                array(&$this, 'add_inner_lp_meta_boxes'),
                self::POST_TYPE
                );
        }
        public function add_inner_lp_meta_boxes($post)
        {       
            // Render the job order meta box
            include(sprintf("%s/../templates/lp_metabox.php", dirname(__FILE__), self::POST_TYPE));
        } // END public function add_inner_meta_boxes($post)
        public function add_lp_group_meta_boxes(){
            add_meta_box(
                sprintf('lp_group_%s_section', self::LP_TYPE),
                sprintf('Add groups'),
                array(&$this, 'post_meta_boxes'),
                self::LP_TYPE
                );
        }
        public function post_meta_boxes($post)
        {       
            // Render the job order meta box
            include(sprintf("%s/../templates/lp_group_metabox.php", dirname(__FILE__), self::LP_TYPE));
        } // END public function add_inner_meta_boxes($post)
        public function show_group_ids($post_id){ ?>
        <h3>Groups</h3>
        <table class="form-table">
            <tr>
                <th><label>Groups</label></th> 
                <td><select id="add_groups" class="chosen-select reguler-text" name="group_ids[]" multiple="multiple" >
                    <?php 
                    global $profileuser;
                    $user_id = $profileuser->ID;
                    $groups = get_user_meta( $user_id ,'group_id', true );
                    $posts = query_posts(array('post_type'=> 'group'));
                    foreach ( $posts as $post) {
                        $is_selected = " ";
                        if( ( $groups !== false ) && is_array($groups) && ( array_search($post->ID, $groups ) !== false ) ) {
                            $is_selected = "selected=''"; 
                        }
                        $to_print = sprintf("<option %s value='%s'>%s</option>", $is_selected, $post->ID, esc_html( $post->post_title) );
                        echo $to_print;
                    } ?> 
                </select></td></tr></table>
                <?php 
            }

            public function save_extra_user_profile_fields($post_id) {
            
                $user_id = $_POST['user_id'];
                // if we are updating m_username, we need old value to compare
                $old_groups = get_user_meta($user_id, 'group_id', true);
                //error_log('user id' .print_r($old_groups,true));
                if(isset($_POST['group_ids'])) {
                    $new_groups = $_POST['group_ids'];
                } else {
                    $new_groups = array();
                }
                // when old groups don't exist, e.g. user is new, no groups were added previously
                if($old_groups == '') {
                    $old_groups = array();
                }
                
                // if group_ids is empty (all users are removed)
                if(!empty($_POST['group_ids'])) {
                    $new_groups = $_POST['group_ids'];
                } else {
                    $new_groups = array();
                }
                // see if there are any groups who are in old key but not new
                $removed_groups = array_diff($old_groups, $new_groups);
                // if we have removed groups, we update them and remove user_id
                if(!empty($removed_groups)) {
                    foreach ($removed_groups as $group_id) {
                        // get users for group
                        $user_list = get_post_meta($group_id, 'm_username', true);
                        // if user list is not empty
                        if(is_array($user_list)) {
                            // find user index
                            $user_index = array_search($user_id, $user_list);
                            if($user_index !== FALSE) {
                                // remove user from array
                                unset($user_list[$user_index]);
                                $user_list = array_values($user_list);
                                //array_splice($user_list, $user_index);\
                                //error_log('user list' .print_r($user_list,true));
                                update_post_meta($group_id, 'm_username', $user_list);
                            }
                        }
                    }
                }

                // for each group, we add the user if they are not already there
               if(!empty($new_groups)) {
                    foreach ($new_groups as $group_id) {
                        // get users for group
                        $group_ids = get_post_meta( $group_id, 'm_username',true );   
                          if(is_array($group_ids)) {
                            $existing = array_search( $user_id, $group_ids );
                            if( $existing === false ) {
                                $group_ids[] = $user_id;
                            }
                        } else {
                            $group_ids = array($user_id);
                        }
                        $result = update_post_meta($group_id, 'm_username', $group_ids);

                       return $result;
                    }
                }
                $result = update_user_meta($user_id, 'group_id', $new_groups);
                return $result; 
            }        
        // Get all the users for a single group    
            public function get_user_by_role( $post_id, $user_role='') {
                if(get_post_status( $ID ) === false ) {
                    // The post does not exist
                    return false;
                }
                $group_users = get_post_meta( $post_id, 'm_username',true );
                if( ( $user_role != '') && (is_array($group_users)) ) {
                    foreach ($group_users as $users) {
                        $user = new WP_User( $users);
                        if ( !empty( $user->roles ) && is_array( $user->roles ) ) {
                            foreach ( $user->roles as $role ) {
                                if( $role === $user_role ) {
                                    $group_user[] = $users;
                                }
                            } 
                        } 
                    } 
                    return $group_user;
                } else {
                    return $group_users;
                }
            }
        // Return All the groups using two parameters post_id and post type.
            public function get_groups($type='', $ID=0){
                if($type == 'user'){
                    //validate input parameters 
                    if(get_user_by('ID' ,$ID) === false ) {
                        return false;
                    }
                    $groups = get_user_meta( $ID ,'group_id', true );
                    if(is_array($groups)){
                        foreach ($groups as $groups_id) {
                            $posts = query_posts(array('post_type'=> 'group','posts_per_page' => -1 ));
                            $ret = array();
                        }
                        foreach ($posts as $post) {
                            $is_selected = array_search($post->ID, $groups);
                            if( $is_selected !== FALSE ) {
                                $selected = 1;
                            } else {
                                $selected = 0;
                            }
                            $ret = array(
                                "id"    =>  $post->ID,
                                "name"  =>  $post->post_title,
                                "selected" => $selected
                                );
                            $rets[] = $ret; 
                        }
                        if (!empty($rets)){
                                return $rets;
                            }else{
                                return false;
                            }
                    }
                }
                elseif($type == 'program'){
                    if(get_post_status( $ID ) === false ) {
                        // The post does not exist
                        return false;
                    }
                    $lp_groups = get_post_meta( $ID, 'm_groups',true );
                    if(is_array($lp_groups)){
                        $posts = query_posts(array('post_type'=> 'group', 'posts_per_page' => -1 ));
                        foreach ($posts as $post) {
                            if( ( $lp_groups !== false ) && is_array($lp_groups) && ( array_search($post->ID, $lp_groups ) !== false ) ){
                                $selected = 1;
                            } else {
                                $selected = 0;
                            }
                            $ret = array(
                                "id"    =>  $post->ID,
                                "name"  =>  $post->post_title,
                                "selected" => $selected
                                );
                            $rets[] = $ret;
                        }
                        if (!empty($rets)){
                                return $rets;
                            }else{
                                return false;
                            }
                    }

                }
                return false;
            }

            public function get_programs($type='', $ID=0){
                if($type == 'user'){
                    //validate input parameters 
                    if(get_user_by('ID' ,$ID) === false ) {
                        return 'false';
                    }
                    $groups = get_user_meta( $ID ,'user_posts', true );
                    if(is_array($groups)){
                        $posts = query_posts(array('post_type'=> 'post','posts_per_page' => -1 ));
                        foreach ($posts as $post) {
                            if( ( $groups !== false ) && array_search($post->ID, $groups) !== false ){
                            $ret = array(
                                "id"    =>  $post->ID,
                                "name"  =>  $post->post_title
                            );
                            $rets[] = $ret; 
                            }
                        }
                        if (!empty($rets)){
                                return $rets;
                        }else{
                            return false;
                        }
                    }
                }
                elseif($type == 'post'){
                    if(get_post_status( $ID ) === false ) {
                        // The post does not exist
                        return false;
                    }
                    $lp_groups = get_post_meta( $ID, 'm_groups',true );
                    if(is_array($lp_groups)){
                        $posts = query_posts(array('post_type'=> 'post', 'posts_per_page' => -1 ));
                        foreach ($posts as $post) {
                            if( ( $lp_groups !== false ) && ( array_search($post->ID, $lp_groups ) !== false ) ){
                                $ret = array(
                                    "id"    =>  $post->ID,
                                    "name"  =>  $post->post_title
                                    );
                                $rets[] = $ret;
                            }
                        }
                        if (!empty($rets)){
                            return $rets;
                        }else{
                            return false;
                        }
                    }     
                }
                return false;
            }
            //Add user to single group (pass user ID and group ID to that)
            public function add_user_to_single_group($user_id , $post_id){
                //validate input parameters 
                if(get_user_by('ID' ,$user_id) === false ) {
                    return false;
                }
                if(get_post_status( $post_id ) === false ) {
                    // The post does not exist
                    return false;
                }
                $group_ids = get_post_meta( $post_id, 'm_username',true );
                if(is_array($group_ids)) {
                    $existing = array_search( $user_id, $group_ids );
                    if( $existing === false ) {
                        $group_ids[] = $user_id;
                    }
                } else {
                    $group_ids = array($user_id);
                }
                $result = update_post_meta($post_id, 'm_username', $group_ids);

                return $result;
            }
    } // END class Groups
} // END







