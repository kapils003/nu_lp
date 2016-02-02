<table> 
    <tr valign="top">
        <th class="metabox_label_column">
            <label for="Add Groups">Add Groups</label>
        </th>
        <td>
        <select id="add_groups" class="chosen-select" name="lp_groups[]" multiple="multiple" >
         <?php $posts = query_posts(array('post_type'=> 'group' ,'posts_per_page' => -1 ));
            // get post meta - current post ID
            $post_id = $_REQUEST['post'];
            $groups = get_post_meta( $post_id ,'lp_groups', true );
            foreach ( $posts as $post) {
                $is_selected = " ";
                if( ( $groups !== false ) && is_array($groups) && ( array_search($post->ID, $groups ) !== false ) ) {
                    $is_selected = "selected=''"; 
                }
                $to_print = sprintf("<option %s value='%s'>%s</option>", $is_selected, $post->ID, esc_html( $post->post_title) );
                echo $to_print;
            } ?>
        </select>
        </td>
    </tr>        
</table>
