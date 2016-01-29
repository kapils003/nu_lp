<?php
if(!class_exists('Groups'))
{
    /**
     * A PostTypeTemplate class that provides 3 additional meta fields
     */
    class Groups
    {
        const POST_TYPE = "group";
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
                        'title', 'editor', 'excerpt', 
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
                foreach($this->_meta as $field_name)
                {
                    // Update the post's meta field
                    update_post_meta($post_id, $field_name, $_POST[$field_name]);
                }
                update_post_meta($post_id, 'm_groups',$_POST['m_groups']);
            }
            else
            {
                return;
            } // if($_POST['post_type'] == self::POST_TYPE && current_user_can('edit_post', $post_id))
        } // END public function save_post($post_id)

        /**
         * hook into WP's admin_init action hook
         */
        public function admin_init()
        {           
            // Add metaboxes
            add_action('add_meta_boxes', array(&$this, 'add_meta_boxes'));
            add_action('add_meta_boxes', array(&$this, 'add_lp_meta_boxes'));           
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
            $result = update_user_meta($user_id, 'group_id', $_POST['group_ids']);
            return $result;
        }        
        // Get all the users for a single group    
        public function get_user_by_role( $post_id, $user_role='') {
            $group_users = get_post_meta( $post_id, 'm_username',true );
            if( $user_role != '') {
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
                    return $rets;
                }
            }
            elseif($type == 'group'){
            $lp_groups = get_post_meta( $ID, 'm_groups',true );
            if(is_array($lp_groups)){
                foreach ($lp_groups as $groups_id) {
                    $posts = query_posts(array('post_type'=> 'post', 'posts_per_page' => -1 ));
                    $ret = array();
                }
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
                return $rets;
            }
                 
            }
        }

        public function all_groups_for_lp($id=0) {
            // assuming key is 'm_groups' in LPs
            $lp_groups = get_post_meta( $id, 'm_groups',true );
            if(is_array($lp_groups)){
                foreach ($lp_groups as $groups_id) {
                    $posts = query_posts(array('post_type'=> 'post', 'posts_per_page' => -1 ));
                    $ret = array();
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
                }
            }
                return $rets; 
        }
        //Add user to single group (pass user ID and group ID to that)
        public function Add_user_to_single_group($user_id , $post_id){
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







