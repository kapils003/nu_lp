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

/*                    // we will be updating usermeta only if your current meta field is m_username
                    if( $field_name == 'm_username' ) {
                        $user_ids = $_POST[$field_name];
                        foreach( $user_ids as $user_id ) {
                            $current_group_ids = get_user_meta($user_id,'group_id',true);

                            // if user does not have group_id, we save current $post_id as array
                            if( $current_group_ids == false ) {
                                update_user_meta( $user_id, 'group_id', array( $post_id ) );
                                continue;
                            }

                            // if user has the group_id meta field, we need to check if group ID is already there
                            if (array_search($post_id, $current_group_ids ) === false){
                                // current group_id is not there, we add it to current_group_ids array and save
                                $current_group_ids[] = $post_id;
                                update_user_meta($user_id, 'group_id', $current_group_ids);
                            } else {
                                // group_id already there, we continue to next user
                                continue;
                            }
                        }
                    }*/
                }
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
            // Render the job order metabox
            include(sprintf("%s/../templates/%s_metabox.php", dirname(__FILE__), self::POST_TYPE));         
        } // END public function add_inner_meta_boxes($post)

/*        public function show_group_ids() { ?>
 
                    <h3>Groups</h3>
            <table class="form-table">
                <tr>
                    <th><label>Groups</label></th>
                    <td><input type="text" name= 'group_id' value="<?php 
                    global $profileuser;
                    $user_id = $profileuser->ID;
                    $groups = get_user_meta( $user_id ,'group_id', true );                    
                    $ids = implode(',', $groups);
                    foreach ($groups as $groups_ids) {
                    $posts = query_posts(array('p' => $groups_ids,'post_type'=> 'group'));
                    print_r($posts->post_title);
                    foreach ($posts as $post) {
                          echo $post->post_title;
                      }  
                      echo ',';
                  }
                    ?>" class="regular-text groups" readonly/></td>
                </tr>
            </table>
            <?php
        }*/  
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
                  foreach ($groups as $groups_ids) {
                    $posts = query_posts(array('p' => $groups_ids,'post_type'=> 'group'));
                    foreach ($posts as $post) {      
                        $is_selected = " ";
                        if( ( $groups_ids !== false ) && ( array_search($user_id, $groups_ids ) !== false ) ) {
                            $is_selected = "selected=''"; 
                        }
                        $to_print = sprintf("<option %s value = ' %s '>%s</option>", $is_selected, $user_id, esc_html($post->post_title) );
                        echo $to_print;
                    }  
                } 
            }
            public function save_extra_user_profile_fields($post_id) {
                global $profileuser;
                $user_id = $profileuser->ID;
                $current_group_ids = get_user_meta($user_id,'group_id',true);
            // if user does not have group_id, we save current $post_id as array
                if( $current_group_ids == false ) {
                    update_user_meta( $user_id, 'group_id', array( $post_id ) );
                    continue;
                }
            // if user has the group_id meta field, we need to check if group ID is already there
                if (array_search($post_id, $current_group_ids ) === false){
                // current group_id is not there, we add it to current_group_ids array and save
                    $current_group_ids[] = $post_id;
                    update_user_meta($user_id, 'group_id',$current_group_ids);
                } else {
                // group_id already there, we continue to next user
                    continue;
                }

            }
        // Get all the users for a single group    
          /* public function get_users_for_a_group($post_id){
           $blogusers = get_users( array( 'fields' => array( 'display_name', 'ID' ) ) );
            // get post meta - current post ID
            $post_id = $_REQUEST['post'];
            $group_users = get_post_meta( $post_id, 'm_username',true );

            // compare post meta users, all userrs
            foreach ( $blogusers as $user ) {
                $is_selected = " ";
                if( ( $group_users !== false ) && ( array_search($user->ID, $group_users ) !== false ) ) {
                    $is_selected = "selected=''"; 
                }
                $to_print = sprintf("<option %s value='%s'>%s</option>", $is_selected, $user->ID, esc_html( $user->display_name) );
                echo $to_print;
            } ?> */
            public function is_user_in_role( $user_id, $roles  ) {
                $blogusers = get_users( array( 'fields' => array( 'display_name', 'ID' ) ) );
                $post_id = 5;
                $group_users = get_post_meta( $post_id, 'm_username',true );
                $user = new WP_User( $user_id );
                if ( !empty( $user->roles ) && is_array( $user->roles ) ) {
                    foreach ( $user->roles as $role )
                        print_r($role);
                }
                if($roles == $role){
                    foreach ( $blogusers as $user ) {
                        $is_selected = " ";
                        if( ( $group_users !== false ) && ( array_search($user_id, $group_users ) !== false ) ) {
                            $is_selected = "selected=''"; 
                        }
                        $to_print = sprintf("<option %s value='%s'>%s</option>", $is_selected, $user->ID, esc_html( $user->display_name) );
                        echo $to_print; }
                     }elseif($role == false){
                        foreach ( $blogusers as $user ) {
                            $is_selected = " ";
                            if( ( $group_users !== false ) && ( array_search($user_id, $group_users ) !== false ) ) {
                                $is_selected = "selected=''"; 
                            }
                            $to_print = sprintf("<option %s value='%s'>%s</option>", $is_selected, $$user_id, esc_html( $user->display_name) );
                            echo $to_print;
                        }
                    }
            }
            public function list_of_learning_programs($id, $post_type){

            }
    } // END class Groups
} // END if(!class_exists('Groups'))


$group = new Groups();

$group->is_user_in_role(2,'subscriber');