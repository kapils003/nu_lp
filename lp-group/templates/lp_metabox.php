<table> 
    <tr valign="top">
        <th class="metabox_label_column">
            <label for="Add LPs">Add LPs</label>
        </th>
        <td>
        <select id="add_lps" class="chosen-select" name="m_groups[]" multiple="multiple" >
         <?php $posts = query_posts(array('post_type'=> 'post' ,'posts_per_page' => -1 ));
            // get post meta - current post ID
            $post_id = $_REQUEST['post'];
            $lps = get_post_meta( $post_id, 'm_groups',true );

            // compare post meta users, all users

            foreach ( $posts as $post) {
                $is_selected = " ";
                if( ( $lps !== false ) && is_array($lps) && ( array_search($post->ID, $lps ) !== false ) ) {
                    $is_selected = "selected=''"; 
                }
                $to_print = sprintf("<option %s value='%s'>%s</option>", $is_selected, $post->ID, esc_html( $post->post_title) );
                echo $to_print;
            } ?>
        </select>
        </td>
    </tr>        
</table>
