<?php
/**
 * Function: Custom Order Meta data
 * 01- change order item meta
**/
/* add_action( 'woocommerce_before_order_itemmeta', 'so_32457241_before_order_itemmeta', 10, 3 );
function so_32457241_before_order_itemmeta( $item_id, $item, $_product ){
    echo '<p>bacon</p>';
} */

add_filter( 'get_post_metadata', 'so_32457241_order_thumbnail', 10, 4 );
function so_32457241_order_thumbnail( $value, $post_id, $meta_key, $single ) {
    // We want to pass the actual _thumbnail_id into the filter, so requires recursion
    static $is_recursing = false;
    if ( ! $is_recursing && $meta_key === '_thumbnail_id' ) {
        $is_recursing = true; // prevent this conditional when get_post_thumbnail_id() is called
        $value = get_post_thumbnail_id( $post_id );
        $is_recursing = false;
        $value = apply_filters( 'post_thumbnail_id', $value, $post_id );
        if ( ! $single ) {
            $value = array( $value );
        }
    }
    return $value;
}

add_filter( 'post_thumbnail_id', 'so_custom_order_item_thumbnail', 10, 2 );
function so_custom_order_item_thumbnail( $id, $post_id ){
    if( is_admin() ){
        $screen = get_current_screen();
        if( $screen->base == 'post' && $screen->post_type == 'shop_order' ) {
            global $post;
            // some kind of array in the order meta
            $html_output = '';
        } 
    }
    return $html_output;
}
?>
