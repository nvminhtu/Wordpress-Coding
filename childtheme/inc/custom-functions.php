<?php
/****
 *  More Custom Child Theme Functions
 ****/
define('THEMECHILDPATH', get_theme_file_uri());

// Woo: Custom Product Page
add_action('wp_enqueue_scripts', 'enqueue_single_product_javascript',999); 
function enqueue_single_product_javascript() {
	if (is_product()) {
		wp_enqueue_script('custom-woo', THEMECHILDPATH . '/inc/js/custom-woo.js', '1.0.0', true );
		wp_enqueue_style('custom-woo', THEMECHILDPATH . '/inc/css/custom-woo.css' );
	}
}

// Woo: Custom Variation product
function show_variation_product() {
	echo 'do variation product here';
	global $product;
	$product_id = $product->get_id();

	$product = new WC_Product_Variable( $product_id );
	$variations = $product->get_available_variations();

	$render_html = '<ul>';
	foreach ( $variations as $variation ) {		
		$variation_value = $variation['attributes']['attribute_dress-bottom'];
		$render_html .= "<li class='variation_picture ".$variation_value."'>";
			$render_html .= "<img src=" . $variation['image']['thumb_src'] .">";
		$render_html .= "</li>";
	}
	$render_html .= '</ul>';
	echo $render_html;

	
	// if ( $product->has_child() ) {
	// 	$variations = $product->get_children();
	// 	print_r($variations);
	// 	$variation_thumbs = array();
	// 	foreach ( $variations as $variation ) {
	// 		if(has_post_thumbnail($variation)) {
	// 			$varID = get_post_thumbnail_id( $variation );
	// 			array_push($variation_thumbs, $varID);
	// 		}
	// 	}
	// }

}
add_action( 'woocommerce_single_variation', 'show_variation_product' );






// function show_before_addtocart() {
// 	echo 'do something here';
// }
// add_action( 'woocommerce_before_add_to_cart_button', 'show_before_addtocart' );

// function action_woocommerce_product_thumbnails(  ) { 
// 	wtshow_product_gallery();
// 	echo 'custom test hereassssss';
// };
// add_action( 'woocommerce_product_thumbnails', 'action_woocommerce_product_thumbnails', 10, 0 ); 

// function wtshow_product_gallery() {
// 	global $product;
// 	$attachment_ids = $product->get_gallery_image_ids();
// 	if ( $attachment_ids && $product->get_image_id() ) {
// 		echo '<ul>';
// 		foreach ( $attachment_ids as $attachment_id ) {
// 			echo '<li>'.wtshow_product_image($attachment_id).'</li>';
// 		}
// 		echo '</ul>';
// 	}
// }

// function wtshow_product_image($attachment_id) {
// 	$output = '';
// 	$img_atts = wp_get_attachment_image_src($attachment_id, 'thumbnail'); // get custom size
// 	$output .= '<img src="'.$img_atts[0].'">';
// 	return $output;
// }

// Remove image from product pages
// remove_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_images', 20 );

// function hf_woocommerce_remove_featured_image( $html, $attachment_id ) {
// 	global $post, $product;
// 	$attachment_ids = $product->get_gallery_image_ids();
// 	// if ( ! $attachment_ids ) {
// 	// 	return $html;
// 	// }
// 	$featured_image = get_post_thumbnail_id( $post->ID );
// 	// if ( $attachment_id == $featured_image ) {
// 	// 	$html = '';
// 	// }
// 	return $html;
// }
// // add_filter( 'woocommerce_single_product_image_thumbnail_html', 'hf_woocommerce_remove_featured_image', 10, 2 );

// function filter_woocommerce_single_product_image_thumbnail_html( $sprintf, $post_id ) { 
// 	//return $sprintf; 
// 	echo $sprintf;
// }; 
// add_filter( 'woocommerce_single_product_image_thumbnail_html', 'filter_woocommerce_single_product_image_thumbnail_html', 10, 2 );
