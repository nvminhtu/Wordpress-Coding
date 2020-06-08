<?php
/**
 * Single Product Thumbnails
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/product-thumbnails.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see         https://docs.woocommerce.com/document/template-structure/
 * @package     WooCommerce/Templates
 * @version     3.5.1
 */

defined( 'ABSPATH' ) || exit;

// Note: `wc_get_gallery_image_html` was added in WC 3.3.2 and did not exist prior. This check protects against theme overrides being used on older versions of WC.
if ( ! function_exists( 'wc_get_gallery_image_html' ) ) {
	return;
}

global $product;

$attachment_ids = $product->get_gallery_image_ids();

// Custom: Variation Woo
if ( $product->has_child() ) {
	$variations = $product->get_children();
	print_r($variation);
	$variation_thumbs = array();
	foreach ( $variations as $variation ) {
		//echo $variation;
		if(has_post_thumbnail($variation)) {
			$varID = get_post_thumbnail_id( $variation );
			array_push($variation_thumbs, $varID);
		}
	}
}

$allpics = $product->get_gallery_image_ids();

foreach ( $allpics as $picID ) {
	// echo $picID;
	//echo apply_filters( 'woocommerce_single_product_image_thumbnail_html', wc_get_gallery_image_html( $picID ), $picID );
}

// Product Gallery: Show all Photos
if ( $attachment_ids && $product->get_image_id() ) {
	echo '<ul>';
	foreach ( $attachment_ids as $attachment_id ) {
		echo '<li>'.wtshow_product_image($attachment_id).'</li>';
		// print_r(wc_get_gallery_image_html( $attachment_id ));
		// if(!in_array($attachment_id, $variation_thumbs)) {
		// 	// echo apply_filters( 'woocommerce_single_product_image_thumbnail_html', wc_get_gallery_image_html( $attachment_id ), $attachment_id ); // phpcs:disable WordPress.XSS.EscapeOutput.OutputNotEscaped
		// }
	}
	echo '</ul>';
}

// Product Gallery: Show Thumbnails Item
function wtshow_product_image($attachment_id) {
	$output = '';
	$img_atts = wp_get_attachment_image_src($attachment_id, 'thumbnail'); // get custom size
	$output .= '<img src="'.$img_atts[0].'">';
	return $output;
}
