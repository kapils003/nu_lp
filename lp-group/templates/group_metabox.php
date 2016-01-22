<table> 
    <tr valign="top">
        <th class="metabox_label_column">
            <label for="Add Users">Add Users</label>
        </th>
        <td>
        <select id="add_users" class="chosen-select" name="m_username[]" multiple="multiple" >
         <?php $blogusers = get_users( array( 'fields' => array( 'display_name', 'ID' ) ) );
            // get post meta - current post ID
            $post_id = $_REQUEST['post'];
            $group_users = get_post_meta( $post_id, 'm_username',true );

            // compare post meta users, all userrs

            foreach ( $blogusers as $user ) {
                $user_id[] = $user->ID;
                $is_selected = " ";
                if( ( $group_users !== false ) && ( array_search($user->ID, $group_users ) !== false ) ) {
                    $is_selected = "selected=''"; 
                }
                $to_print = sprintf("<option %s value='%s'>%s</option>", $is_selected, $user->ID, esc_html( $user->display_name) );
                echo $to_print;
            } ?>
        </select>
        </td>
    </tr>        
</table>
