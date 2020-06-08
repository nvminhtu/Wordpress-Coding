<?php
/**
 * paperdolls Theme functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package paperdolls
 * @since 1.0.0
 */

/**
 * Define Constants
 */
define( 'CHILD_THEME_PAPERDOLLS_VERSION', '1.0.0' );

/**
 * Enqueue styles
 */
function child_enqueue_styles() {

	wp_enqueue_style( 'paperdolls-theme-css', get_stylesheet_directory_uri() . '/style.css', array('astra-theme-css'), CHILD_THEME_PAPERDOLLS_VERSION, 'all' );

}

add_action( 'wp_enqueue_scripts', 'child_enqueue_styles', 15 );


// Custom functions
include('inc/custom-functions.php');

// allow decimal usage

// Add min value to the quantity field (default = 1)
add_filter('woocommerce_quantity_input_min', 'min_decimal');
function min_decimal($val) {
    return 0.1;
}

// Add step value to the quantity field (default = 1)
add_filter('woocommerce_quantity_input_step', 'nsk_allow_decimal');
function nsk_allow_decimal($val) {
    return 0.1;
}

// Removes the WooCommerce filter, that is validating the quantity to be an int
remove_filter('woocommerce_stock_amount', 'intval');

// Add a filter, that validates the quantity to be a float
add_filter('woocommerce_stock_amount', 'floatval');



// Register new custom fields per variation.

// regular variable products
function woocommerce_maybe_add_multiple_products_to_cart( $url = false ) {
    // Make sure WC is installed, and add-to-cart qauery arg exists, and contains at least one comma.
    if ( ! class_exists( 'WC_Form_Handler' ) || empty( $_REQUEST['add-to-cart'] ) || false === strpos( $_REQUEST['add-to-cart'], ';' ) ) {
        return;
    }
 
    // Remove WooCommerce's hook, as it's useless (doesn't handle multiple products).
    remove_action( 'wp_loaded', array( 'WC_Form_Handler', 'add_to_cart_action' ), 20 );
 
    $product_ids = explode( ';', $_REQUEST['add-to-cart'] );
    $count       = count( $product_ids );
    $number      = 0;
 
    foreach ( $product_ids as $id_and_quantity ) {
        // Check for quantities defined in curie notation (<product_id>:<product_quantity>)
        // https://dsgnwrks.pro/snippets/woocommerce-allow-adding-multiple-products-to-the-cart-via-the-add-to-cart-query-string/#comment-12236
        $id_and_quantity = explode( ':', $id_and_quantity );
        $product_id = $id_and_quantity[0];
 
        $_REQUEST['quantity'] = ! empty( $id_and_quantity[1] ) ?  $id_and_quantity[1] : 1;
 		
 
        if ( ++$number === $count ) {
            // Ok, final item, let's send it back to woocommerce's add_to_cart_action method for handling.
            $_REQUEST['add-to-cart'] = $product_id;
 
            return WC_Form_Handler::add_to_cart_action( $url );
        }
 
        $product_id        = apply_filters( 'woocommerce_add_to_cart_product_id', absint( $product_id ) );
        $was_added_to_cart = false;
        $adding_to_cart    = wc_get_product( $product_id );
 
        if ( ! $adding_to_cart ) {
            continue;
        }
        $add_to_cart_handler = apply_filters( 'woocommerce_add_to_cart_handler', $adding_to_cart->get_type(), $adding_to_cart );
 
        // Variable product handling
        if ( 'variable' === $add_to_cart_handler ) {
            woo_hack_invoke_private_method( 'WC_Form_Handler', 'add_to_cart_handler_variable', $product_id );
 
        // Grouped Products
        } elseif ( 'grouped' === $add_to_cart_handler ) {
            woo_hack_invoke_private_method( 'WC_Form_Handler', 'add_to_cart_handler_grouped', $product_id );
 
        // Custom Handler
        } elseif ( has_action( 'woocommerce_add_to_cart_handler_' . $add_to_cart_handler ) ){
            do_action( 'woocommerce_add_to_cart_handler_' . $add_to_cart_handler, $url );
 
		// Simple Products
		} else {
		// woo_hack_invoke_private_method( 'WC_Form_Handler', 'add_to_cart_handler_simple', $product_id );
			WC()->cart->add_to_cart($product_id, $_REQUEST['quantity']);
		}
    }
}


// Fire before the WC_Form_Handler::add_to_cart_action callback.
add_action( 'wp_loaded', 'woocommerce_maybe_add_multiple_products_to_cart', 15 );
 
 
/**
 * Invoke class private method
 *
 * @since   0.1.0
 *
 * @param   string $class_name
 * @param   string $methodName
 *
 * @return  mixed
 */
function woo_hack_invoke_private_method( $class_name, $methodName ) {
    if ( version_compare( phpversion(), '5.3', '<' ) ) {
        throw new Exception( 'PHP version does not support ReflectionClass::setAccessible()', __LINE__ );
    }
 
    $args = func_get_args();
    unset( $args[0], $args[1] );
    $reflection = new ReflectionClass( $class_name );
    $method = $reflection->getMethod( $methodName );
    $method->setAccessible( true );
 
    $args = array_merge( array( $class_name ), $args );
    return call_user_func_array( array( $method, 'invoke' ), $args );
}


// Register new fields for the variations

add_action( 'woocommerce_product_after_variable_attributes', 'variation_settings_fields', 10, 3 );
add_action( 'woocommerce_save_product_variation', 'save_variation_settings_fields', 10, 2 );
add_filter( 'woocommerce_available_variation', 'load_variation_settings_fields' );

function variation_settings_fields( $loop, $variation_data, $variation ) {
    woocommerce_wp_text_input(
        array(
            'id'            => "my_text_field{$loop}",
            'name'          => "my_text_field[{$loop}]",
            'value'         => get_post_meta( $variation->ID, 'my_text_field', true ),
            'label'         => __( 'Fabric Usage [example: 3.4]', 'woocommerce' ),
            'desc_tip'      => true,
            'description'   => __( 'Total Fabric usage for this variation.', 'woocommerce' ),
            'wrapper_class' => 'form-row form-row-full',
        )
    );
}

function save_variation_settings_fields( $variation_id, $loop ) {
    $text_field = $_POST['my_text_field'][ $loop ];

    if ( ! empty( $text_field ) ) {
        update_post_meta( $variation_id, 'my_text_field', esc_attr( $text_field ));
    }
}

function load_variation_settings_fields( $variation ) {     
    $variation['my_text_field'] = get_post_meta( $variation[ 'variation_id' ], 'my_text_field', true );

    return $variation;
}


// END Register new custom fields per variation.


/** upper_bust bust waist hips sizing SkirtLength
 * Display my custom field values on the order edit page
 */
add_action( 'woocommerce_admin_order_data_after_billing_address', 'my_custom_checkout_field_display_admin_order_meta', 10, 1 );

function my_custom_checkout_field_display_admin_order_meta($order){

    echo '<p><strong>'.__('Standard Size').':</strong> <br/>' . get_post_meta( $order->get_id(), 'sizing', true ) . '</p>';
    echo '<p><strong>'.__('Height').':</strong> <br/>' . get_post_meta( $order->get_id(), 'height', true ) . '</p>';
    echo '<p><strong>'.__('Shoulder to waist').':</strong> <br/>' . get_post_meta( $order->get_id(), 'shoulder_to_waist', true ) . '</p>';
    echo '<p><strong>'.__('Upper bust').':</strong> <br/>' . get_post_meta( $order->get_id(), 'upper_bust', true ) . '</p>';
    echo '<p><strong>'.__('Bust').':</strong> <br/>' . get_post_meta( $order->get_id(), 'bust', true ) . '</p>';
    echo '<p><strong>'.__('Waist').':</strong> <br/>' . get_post_meta( $order->get_id(), 'waist', true ) . '</p>';
    echo '<p><strong>'.__('Hips').':</strong> <br/>' . get_post_meta( $order->get_id(), 'hips', true ) . '</p>';
    echo '<p><strong>'.__('Length').':</strong> <br/>' . get_post_meta( $order->get_id(), 'SkirtLength', true ) . '</p>';
}

/**
 * Display my custom product field values on the order edit page
 */

/**
 * Add custom field to order object
 */
function cfwc_add_custom_data_to_order( $item, $cart_item_key, $values, $order ) {
	foreach( $item as $cart_item_key=>$values ) {

			if( isset( $values['height'] ) ) {
				$item->add_meta_data( __( 'Height', 'cfwc' ), $values['height'], true );
				}
			if( isset( $values['shoulder_to_waist'] ) ) {
				$item->add_meta_data( __( 'Shoulder to waist', 'cfwc' ), $values['shoulder_to_waist'], true );
				}
			if( isset( $values['upper_bust'] ) ) {
				$item->add_meta_data( __( 'Upper bust', 'cfwc' ), $values['upper_bust'], true );
				}
			if( isset( $values['bust'] ) ) {
				$item->add_meta_data( __( 'Bust', 'cfwc' ), $values['bust'], true );
				}
			if( isset( $values['waist'] ) ) {
				$item->add_meta_data( __( 'Waist', 'cfwc' ), $values['waist'], true );
				}
			if( isset( $values['hips'] ) ) {
				$item->add_meta_data( __( 'Hips', 'cfwc' ), $values['hips'], true );
				}
			if( isset( $values['SkirtLength'] ) ) {
				$item->add_meta_data( __( 'Skirt Length', 'cfwc' ), $values['SkirtLength'], true );
				}
			if( isset( $values['sizing'] ) ) {
				$item->add_meta_data( __( 'Size', 'cfwc' ), $values['sizing'], true );
			}
		}
	}
add_action( 'woocommerce_checkout_create_order_line_item', 'cfwc_add_custom_data_to_order', 10, 4 );

// add measurements

function add_measurments( $cart_item_data, $product_id, $variation_id, $quantity ) {
 if( ! empty( $_POST['sizing'] ) ) {
	 $cart_item_data['height'] = $_POST['height'];
	 $cart_item_data['fabric'] = $_POST['fabric'];
	 $cart_item_data['fabricQuantity'] = $_POST['fabricQuantity'];  
	 $cart_item_data['shoulder_to_waist'] = $_POST['shoulder_to_waist'];
	 $cart_item_data['upper_bust'] = $_POST['upper_bust'];
	 $cart_item_data['bust'] = $_POST['bust'];
	 $cart_item_data['waist'] = $_POST['waist'];  
	 $cart_item_data['hips'] = $_POST['hips']; 
	 $cart_item_data['sizing'] = $_POST['sizing'];
	 $cart_item_data['SkirtLength'] = $_POST['SkirtLength'];
	 }
	 return $cart_item_data;
}
add_filter( 'woocommerce_add_cart_item_data', 'add_measurments', 10, 4 );

/**
 * Display the custom field value in the cart and checkout
 * @since 1.0.0
 */
function cfwc_cart_item_name( $name, $cart_item, $cart_item_key ) {
 	

 		$name .= '<table>';

 	if( ($cart_item['sizing']) == "c" ) {

	 	if( isset( $cart_item['height'] ) ) {
		 	$name .= sprintf('<tr><td>Height</td><td>%s</td></tr>', esc_html( $cart_item['height'] )
			);
		} 	
		if( isset( $cart_item['shoulder_to_waist'] ) ) {
		 	$name .= sprintf('<tr><td>shoulder to waist</td><td>%s</td></tr>', esc_html( $cart_item['shoulder_to_waist'] )
			);
		} 	
		if( isset( $cart_item['upper_bust'] ) ) {
		 	$name .= sprintf('<tr><td>Upper bust</td><td>%s</td></tr>', esc_html( $cart_item['upper_bust'] )
			);
		} 	
		if( isset( $cart_item['bust'] ) ) {
		 	$name .= sprintf('<tr><td>Bust</td><td>%s</td></tr>', esc_html( $cart_item['bust'] )
			);
		}
		if( isset( $cart_item['waist'] ) ) {
		 	$name .= sprintf('<tr><td>Waist</td><td>%s</td></tr>', esc_html( $cart_item['waist'] )
			);
		} 	
		if( isset( $cart_item['hips'] ) ) {
		 	$name .= sprintf('<tr><td>Hips</td><td>%s</td></tr>', esc_html( $cart_item['hips'] )
			);
		}
		if( isset( $cart_item['SkirtLength'] ) ) {
		 	$name .= sprintf('<tr><td>Skirt length</td><td>%s</td></tr>', esc_html( $cart_item['SkirtLength'] )
			);
		}
	}
	else if ( ($cart_item['sizing']) != "c" ) {
		if( isset( $cart_item['SkirtLength'] ) ) {
		 	$name .= sprintf('<tr><td>Skirt length</td><td>%s</td></tr>', esc_html( $cart_item['SkirtLength'] )
			);
		}
			$name .= sprintf('<tr><td>Size</td><td>%s</td></tr>', esc_html( $cart_item['sizing'] )
			);
		}

		$name .= '</table>';

	return $name;

}
add_filter( 'woocommerce_cart_item_name', 'cfwc_cart_item_name', 10, 3 );


// Custom redirect url

function my_custom_add_to_cart_redirect( $fabricQuantity ) {

	$fabricQuantity = $_POST['fabricQuantity']; 
	$fabric = $_POST['fabric']; 
	

	$root = get_site_url();
    $url = $root ."/cart?add-to-cart=". $fabric .":".$fabricQuantity .";"; // URL to redirect to (1 is the page ID here)
    return $url;
}
add_filter( 'woocommerce_add_to_cart_redirect', 'my_custom_add_to_cart_redirect' );

// Display measuremnt Fields


 // Get the variation ID currently selected then get my custom meta 

add_action( 'woocommerce_before_add_to_cart_quantity', 'func_option_valgt' );

function func_option_valgt() {
    global $product;

    if ( $product->is_type('variable') ) {
        $variations_data =[]; // Initializing

        // Loop through variations data
        foreach($product->get_available_variations() as $variation ) {
            // Set for each variation ID the corresponding price in the data array (to be used in jQuery)
            $variations_data[$variation['variation_id']] = $variation['my_text_field'];
        }
        ?>
        <script>
        jQuery(function($) {
            var jsonData = <?php echo json_encode($variations_data); ?>,
                inputVID = 'input.variation_id';

            $('input').change( function(){
                if( '' != $(inputVID).val() ) {
                    var vid      = $(inputVID).val(), // VARIATION ID
                        vprice   = ''; // Initilizing

                    // Loop through variation IDs / Prices pairs
                    $.each( jsonData, function( index, price ) {
                        if( index == $(inputVID).val() ) {
                            vprice = price; // The right variation price
                        }
                    });
                    // internal note visible on outside
                    // document.getElementById("internal").innerHTML = 'Fabric usage (m2): ' +vprice;

                    // Filling the hidden form with fabric data
                    document.getElementById("fabricQuantity").value = +vprice;
					document.getElementById("fabricQuantity").defaultValue = +vprice;
                    // alert('variation Id: '+vid+' | Fabric Meters Test: '+vprice);
                }
            });
        });
        </script>
        <?php
    }
}




function display_measurement_fields() { 

	global $post;
	$terms = wp_get_post_terms( $post->ID, 'product_cat' );
	foreach ( $terms as $term ) $categories[] = $term->slug;

	add_filter( 'woocommerce_loop_add_to_cart_link', 'replace_default_button' );

	if ( in_array( 'polkadots', $categories ) ) {

		function replace_default_button(){
			    return '<button>Text a Dealer</button>';
		}
		$root = get_site_url();
		echo "<a href='". $root ."/product-category/designs/?fabric=blue-fabric'>Make a dress</a>";
	}

	if ( in_array( 'designs', $categories ) ) {
?> 
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">




<div class="fullwidth fabricSelections">
	
<!-- // Fabric selection  -->

<h2>Fabric selection</h2>
<input type="hidden" name="fabricQuantity" id="fabricQuantity" placeholder="" value="">
<?php echo func_option_valgt() ?>
<!-- <i id="internal"></i> -->


<?php 

	// Getting the Fabrics
function show_fabrics($category,$product_number) {

	$args = array(
		'post_type' => 'product',
		'orderby'   => 'title',
		'tax_query' => array(
			array(
				'taxonomy'  => 'product_cat',
				'field'     => 'id',
				'terms'     => $category
			),
		),
		'posts_per_page' => $product_number
	);
	$featured_query = new WP_Query( $args );


	$html .= "<div><h3>Fabrics</h3><div class='row'><div class='col-md-3'><ul class='filterList'>";

	$argst = array(
		  'orderby' => 'name',
		  'parent' => 18,
		  'taxonomy' => 'product_cat',
		  'hide_empty' => 1
	);
	$categoriest = get_categories( $argst );

	foreach ( $categoriest as $category ) {
	    $html .= '<li><span class="btn filtersProducts" id="' . $category->name . '"><i class="fa fa-circle-o"></i> ' . $category->name . '</span></li>';
	    
	}
	// function to get the catagory and add it to the class for filter usage
	function getProductCategories($product) {
		$product = $product;
		$product_id = $product->get_id();
		$terms = get_the_terms ( $product_id, 'product_cat' );
		foreach ( $terms as $term ) {
		     $cat_name = $term->name .' ';
		}
		return $cat_name;
	}

			
	$html .= "</ul></div><div class='col-md-9'><div class='row'>";

	while ($featured_query->have_posts()) :
		$featured_query->the_post();
		$product = get_product( $featured_query->post->ID ); // By doing this, we will be able to fetch all information related to single WooCommerce Product
		$productImage = $product->get_image();
		$productImageID = $product->get_image_id();
		$productImageURL = wp_get_attachment_url(  $productImageID, $size = 'thumbnail' );
		$productName = $product->get_name();

		// $productCategory = $product->get_categories();
		$productID = $product->get_id();
		$productQuantity = $product->get_stock_quantity();
		$productStatus = $product->get_stock_status();

		if ($productQuantity >= "3.2") {
			$html .= "<input class='fabrics filter ". $productCategory ."' hidden='' name='fabric' id='fabric_". $productID ."' type='radio' value='". $productID ."' required>";
			$html .= "<div class='col-md-4  filter ". getProductCategories($product) ." ". $productStatus ." ". $productQuantity ."'><label for='fabric_". $productID ."'><img class='' src='". $productImageURL ."' /><h5>". $productName ."</h5></label></div>";
			}
	endwhile;

	$html .= "</div></div></div></div>";

	return $html;
	wp_reset_query();
}

echo show_fabrics('18','9');
?>
	





<!-- // measurements  -->




</div>
<div class="fullwidth measurementsSelections">
<div class="row">
	<div class="col-md-8" style="padding: 40px">

		<h2>Measurements / Sizing</h2>

			<div class="labelM topButtons">
				<input id="none" type="radio" name="sizingType" value="none" checked="checked">
				<label class="standardSizeSelection" for="none">
				Standard Size</label>
			</div>
			<div class="labelM topButtons">
				<input id="c" type="radio" name="sizingType" value="c" >
				<label class="standardSizeSelection" for="c">
				Custom Size +25$</label>
			</div>
				<select id="metricsSystem">
					<option value="inches" selected="selected">Inches</option>
					<option value="centimeters">Centimeters</option>
				</select>


	<table class="labelM" id="allSizes">
<!-- 		<tr>
			<td>
				Measurements
			</td>
			<td colspan="5">
				Standard size
			</td>
			<td colspan="3">
				Plus size (+20$)
			</td>
		</tr>
		<tr> -->
			<td class="topLineSize">
				
			</td>
			<td class="topLineSize">
				<input id="xs" type="radio" name="sizing" value="xs">
				<label for="xs">
				XS</label>
			</td>
			<td class="topLineSize">
				<input id="s" type="radio" name="sizing" value="s" >
				<label for="s">
				S</label>
			</td>
			<td class="topLineSize">
				<input id="m" type="radio" name="sizing" value="m" required checked="checked">
				<label for="m">
				M</label>
			</td>
			<td class="topLineSize">
				<input id="l" type="radio" name="sizing" value="l" >
				<label for="l">
				L</label>
			</td>
			<td class="topLineSize">
				<input id="xl" type="radio" name="sizing" value="xl" >
				<label for="xl">
				XL</label>
			</td>
			<td class="topLineSize">
				<input id="2xl" type="radio" name="sizing" value="2xl" >
				<label for="2xl">
				2XL</label>
			</td>
			<td class="topLineSize">
				<input id="3xl" type="radio" name="sizing" value="3xl" >
				<label for="3xl">
				3XL</label>
			</td>
			<td class="topLineSize">
				<input id="4xl" type="radio" name="sizing" value="4xl" >
				<label for="4xl">
				4XL</label>
			</td>
		</tr>
		<tr>
			<td class="shoulderLabel help">
				Shoulder to waist <span class="rightAligned">?</span>
			</td>
			<td class="xs">
				<p class="inches">15"</p><p class="centimeters">38,1 cm</p>
			</td>
			<td class="s">
				<p class="inches">15,5"</p><p class="centimeters">39,1 cm</p>
			</td>
			<td class="m boldSelect">
				<p class="inches">16"</p><p class="centimeters">40,6 cm</p>
			</td>
			<td class="l">
				<p class="inches">16,5"</p><p class="centimeters">41,9 cm</p>
			</td>
			<td class="xl"> 
				<p class="inches">17"</p><p class="centimeters">43,2 cm</p>
			</td>
			<td class="2xl">
				<p class="inches">17,5"</p><p class="centimeters">44,5 cm</p>
			</td>
			<td class="3xl">
				<p class="inches">18"</p><p class="centimeters">45,7 cm</p>
			</td>
			<td class="4xl">
				<p class="inches">18,5"</p><p class="centimeters">47,0 cm</p>
			</td>
		</tr>
		<tr>
			<td class="upperbustLabel help">
				Upper Bust <span class="rightAligned">?</span>
			</td>		
			<td class="xs">
				<p class="inches">31"</p><p class="centimeters">78,7 cm</p>
			</td>
			<td class="s">
				<p class="inches">33"</p><p class="centimeters">83,8 cm</p>
			</td>
			<td class="m boldSelect">
				<p class="inches">33,5"</p><p class="centimeters">85,1 cm</p>
			</td>
			<td class="l">
				<p class="inches">36"</p><p class="centimeters">91,4 cm</p>
			</td>
			<td class="xl">
				<p class="inches">38"</p><p class="centimeters">96,5 cm</p>
			</td>
			<td class="2xl">
				<p class="inches">42"</p><p class="centimeters">106,7 cm</p>
			</td>
			<td class="3xl">
				<p class="inches">46"</p><p class="centimeters">116,8 cm</p>
			</td>
			<td class="4xl">
				<p class="inches">50"</p><p class="centimeters">127,0 cm</p>
			</td>
		</tr>
		<tr>
			<td class="bustLabel help">
				Bust <span class="rightAligned">?</span>
			</td>		
			<td class="xs">
				<p class="inches">32"</p><p class="centimeters">81,3 cm</p>
			</td>
			<td class="s">
				<p class="inches">34"</p><p class="centimeters">86,4 cm</p>
			</td>
			<td class="m boldSelect">
				<p class="inches">36"</p><p class="centimeters">91,4 cm</p>
			</td>
			<td class="l">
				<p class="inches">38"</p><p class="centimeters">96,5 cm</p>
			</td>
			<td class="xl">
				<p class="inches">40"</p><p class="centimeters">101,6 cm</p>
			</td>
			<td class="2xl">
				<p class="inches">45"</p><p class="centimeters">114,3 cm</p>
			</td>
			<td class="3xl">
				<p class="inches">49"</p><p class="centimeters">124,5 cm</p>
			</td>
			<td class="4xl">
				<p class="inches">53"</p><p class="centimeters">134,6 cm</p>
			</td>
		</tr>
		<tr>
			<td class="waistLabel help">
				Waist <span class="rightAligned">?</span>
			</td>
			<td class="xs">
				<p class="inches">24"</p><p class="centimeters">61,0 cm</p>
			</td>
			<td class="s">
				<p class="inches">26"</p><p class="centimeters">66,0 cm</p>
			</td>
			<td class="m boldSelect">
				<p class="inches">28"</p><p class="centimeters">71,1 cm</p>
			</td>
			<td class="l">
				<p class="inches">30"</p><p class="centimeters">76,2 cm</p>
			</td>
			<td class="xl">
				<p class="inches">32"</p><p class="centimeters">81,3 cm</p>
			</td>
			<td class="2xl">
				<p class="inches">36"</p><p class="centimeters">91,4 cm</p>
			</td>
			<td class="3xl">
				<p class="inches">40"</p><p class="centimeters">101,6 cm</p>
			</td>
			<td class="4xl">
				<p class="inches">40"</p><p class="centimeters">11,8 cm</p>
			</td>
		</tr>
		<tr>
			<td class="hipLabel help">
				Hips <span class="rightAligned">?</span>
			</td>
			<td class="xs">
				<p class="inches">35"</p><p class="centimeters">88,9 cm</p>
			</td>
			<td class="s">
				<p class="inches">37"</p><p class="centimeters">94,0 cm</p>
			</td>
			<td class="m boldSelect">
				<p class="inches">39"</p><p class="centimeters">99,1 cm</p>
			</td>
			<td class="l">
				<p class="inches">41"</p><p class="centimeters">104,1 cm</p>
			</td>
			<td class="xl">
				<p class="inches">43"</p><p class="centimeters">109,2 cm</p>
			</td>
			<td class="2xl">
				<p class="inches">48"</p><p class="centimeters">121,9 cm</p>
			</td>
			<td class="3xl">
				<p class="inches">52"</p><p class="centimeters">132,1 cm</p>
			</td>
			<td class="4xl">
				<p class="inches">56"</p><p class="centimeters">142,2 cm</p>
			</td>
		</tr>
	</table>




	<table id="customSize">
		<tr>
			<td>
				Height
			</td>
			<td>
				Shoulder to waist
			</td>
			<td>
				Upper Bust
			</td>
			<td>
				Bust
			</td>
			<td>
				Waist
			</td>
			<td>
				Hips
			</td>
		</tr>
		<tr>
			<td>
				<select name="height" id="height">
					<option class="centimeters" value="155">155 cm</option>
					<option class="inches" value="155" selected="selected">5’1</option>
					<option class="inches" value="157">5’2</option>
					<option class="centimeters" value="157">157 cm</option>
					<option class="inches" value="160">5’3</option>
					<option class="centimeters" value="160">160 cm</option>
					<option class="inches" value="162">5’4</option>
					<option class="centimeters" value="162">162 cm</option>
					<option class="inches" value="165">5’5</option>
					<option class="centimeters" value="165">165 cm</option>
					<option class="inches" value="168">5’6</option>
					<option class="centimeters" value="168">168 cm</option>
					<option class="inches" value="170">5’7</option>
					<option class="centimeters" value="170">170 cm</option>
					<option class="inches" value="173">5’8</option>
					<option class="centimeters" value="173">173 cm</option>
					<option class="inches" value="175">5’9</option>
					<option class="centimeters" value="175">175 cm</option>
					<option class="inches" value="177">5’10</option>
					<option class="centimeters" value="177">177 cm</option>
					<option class="inches" value="180">5’11</option>
					<option class="centimeters" value="180">180 cm</option>
				</select>
			</td>
			<td>
				<select name="shoulder_to_waist" id="shoulder_to_waist">
					<option value="33" >13"</option>
					<option value="34" >13.5"</option>
					<option value="35" selected="selected">14"</option>
					<option value="37" >14.5"</option>
					<option value="38" >15"</option>
					<option value="40" class="offAtStart" >15.5"</option>
					<option value="41" class="offAtStart"  >16"</option>
					<option value="42" class="offAtStart" >16.5"</option>
					<option value="43" class="offAtStart" >17"</option>
					<option value="44" class="offAtStart" >17.5"</option>
					<option value="46" class="offAtStart" >18"</option>
					<option value="47" class="offAtStart" >18.5"</option>
					<option value="48" class="offAtStart" >19"</option>
					<option value="50" class="offAtStart" >19.5"</option>
					<option value="51" class="offAtStart" >20"</option>
				</select>
			</td>
			<td>
				<select name="upper_bust" id="upper_bust">
					<option class="centimeters" value="155">155 cm</option>
					<option class="inches" value="155" selected="selected">15"</option>
					<option class="inches" value="157">15,5"</option>
					<option class="centimeters" value="157">157 cm</option>
					<option class="inches" value="160">16"</option>
					<option class="centimeters" value="160">160 cm</option>
					<option class="inches" value="162">16,5"</option>
					<option class="centimeters" value="162">162 cm</option>
					<option class="inches" value="165">17"</option>
					<option class="centimeters" value="165">165 cm</option>
					<option class="inches" value="168">17,5"</option>
					<option class="centimeters" value="168">168 cm</option>
					<option class="inches" value="170">18"</option>
					<option class="centimeters" value="170">170 cm</option>
					<option class="inches" value="173">18,5"</option>
					<option class="centimeters" value="173">173 cm</option>
					<option class="inches" value="175">19"</option>
				</select>
			</td>
			<td>
				<select name="bust" id="bust">
					<option class="centimeters" value="155">155 cm</option>
					<option class="inches" value="155" selected="selected">32"</option>
					<option class="inches" value="157">34"</option>
					<option class="centimeters" value="157">157 cm</option>
					<option class="inches" value="160">36"</option>
					<option class="centimeters" value="160">160 cm</option>
					<option class="inches" value="162">38"</option>
					<option class="centimeters" value="162">162 cm</option>
					<option class="inches" value="165">40"</option>
					<option class="centimeters" value="165">165 cm</option>
					<option class="inches" value="168">45"</option>
					<option class="centimeters" value="168">168 cm</option>
					<option class="inches" value="170">49"</option>
					<option class="centimeters" value="170">170 cm</option>
					<option class="inches" value="173">53"</option>
				</select>
			</td>
			<td>
				<select  name="waist" id="waist">
					<option class="centimeters" value="155">155 cm</option>
					<option class="inches" value="155" selected="selected">24"</option>
					<option class="inches" value="157">26"</option>
					<option class="centimeters" value="157">157 cm</option>
					<option class="inches" value="160">28"</option>
					<option class="centimeters" value="160">160 cm</option>
					<option class="inches" value="162">30"</option>
					<option class="centimeters" value="162">162 cm</option>
					<option class="inches" value="165">32"</option>
					<option class="centimeters" value="165">165 cm</option>
					<option class="inches" value="168">34"</option>
					<option class="centimeters" value="168">168 cm</option>
					<option class="inches" value="170">36"</option>
					<option class="centimeters" value="170">170 cm</option>
					<option class="inches" value="173">38"</option>
					<option class="centimeters" value="173">173 cm</option>
					<option class="inches" value="175">40"</option>
					<option class="centimeters" value="175">175 cm</option>
					<option class="inches" value="177">42"</option>
					<option class="centimeters" value="177">177 cm</option>
					<option class="inches" value="180">44"</option>
					<option class="centimeters" value="180">180 cm</option>
				</select>
			</td>
			<td>
				<select name="hips" id="hips">
					<option class="centimeters" value="155">155 cm</option>
					<option class="inches" value="155" selected="selected">35"</option>
					<option class="inches" value="157">37"</option>
					<option class="centimeters" value="157">157 cm</option>
					<option class="inches" value="160">39"</option>
					<option class="centimeters" value="160">160 cm</option>
					<option class="inches" value="162">41"</option>
					<option class="centimeters" value="162">162 cm</option>
					<option class="inches" value="165">43"</option>
					<option class="centimeters" value="165">165 cm</option>
					<option class="inches" value="168">48"</option>
					<option class="centimeters" value="168">168 cm</option>
					<option class="inches" value="170">52"</option>
					<option class="centimeters" value="170">170 cm</option>
					<option class="inches" value="173">56"</option>
				</select>
			</td>
		</tr>
	</table>
	</div>

	<div class="col-md-4" style="padding:40px;">
		<h2>Skirt length</h2>
		<ul class="noStyleUl">
			<li class="inches">
				<span class="btn lengthSkirt">
					<input type="radio" name="SkirtLength" id="skirtLength1" value="20" required checked="checked">
					<label for="skirtLength1"><i class="fa fa-circle-o"></i>Above Knee (Reg) 20”</label>
				</span>
			</li>
			<li class="centimeters">
				<span class="btn lengthSkirt">
					<input type="radio" name="SkirtLength" id="skirtLength2" value="20">
					<label for="skirtLength2"><i class="fa fa-circle-o"></i>Above Knee (Reg): 50,8 cm</label>
				</span>
			</li>
			<li class="centimeters">
				<span class="btn lengthSkirt">
					<input type="radio" name="SkirtLength" id="skirtLength3" value="22">
					<label for="skirtLength3"><i class="fa fa-circle-o"></i>Above Knee (Tall): 55,9 cm</label>
				</span>
			</li>
			<li class="inches">
				<span class="btn lengthSkirt">
					<input type="radio" name="SkirtLength" id="skirtLength4" value="22">
					<label for="skirtLength4"><i class="fa fa-circle-o"></i>Above Knee (Tall) 22”</label>
				</span>
			</li>
			<li class="inches">
				<span class="btn lengthSkirt">
					<input type="radio" name="SkirtLength" id="skirtLength5" value="24">
					<label for="skirtLength5"><i class="fa fa-circle-o"></i>Knee L (Reg) 24”</label>
				</span>
			</li>
			<li class="inches">
				<span class="btn lengthSkirt">
					<input type="radio" name="SkirtLength" id="skirtLength6" value="26">
					<label for="skirtLength6"><i class="fa fa-circle-o"></i>Knee L (Tall) 26”</label>
				</span>
			</li>
			<li class="inches">
				<span class="btn lengthSkirt">
					<input type="radio" name="SkirtLength" id="skirtLength7" value="28">
					<label for="skirtLength7"><i class="fa fa-circle-o"></i>Midi/Tea L (Reg) 28”</label>
				</span>
			</li>
			<li class="inches">
				<span class="btn lengthSkirt">
					<input type="radio" name="SkirtLength" id="skirtLength8" value="30">
					<label for="skirtLength8"><i class="fa fa-circle-o"></i>Midi/Tea L (Tall) 30”</label>
				</span>
			</li>
			<li class="centimeters">
				<span class="btn lengthSkirt">
					<input type="radio" name="SkirtLength" id="skirtLength10" value="24">
					<label for="skirtLength10"><i class="fa fa-circle-o"></i>Knee L (Reg): 61,0 cm</label>
				</span>
			</li>
			<li class="centimeters">
				<span class="btn lengthSkirt">
					<input type="radio" name="SkirtLength" id="skirtLength11" value="26">
					<label for="skirtLength11"><i class="fa fa-circle-o"></i>Knee L (Tall): 66,0 cm</label>
				</span>
			</li>
			<li class="centimeters">
				<span class="btn lengthSkirt">
					<input type="radio" name="SkirtLength" id="skirtLength12" value="28">
					<label for="skirtLength12"><i class="fa fa-circle-o"></i>Midi/Tea L (Reg): 71,1 cm</label>
				</span>
			</li>
			<li class="centimeters">
				<span class="btn lengthSkirt">
					<input type="radio" name="SkirtLength" id="skirtLength13" value="30">
					<label for="skirtLength13"><i class="fa fa-circle-o"></i>Midi/Tea L (Tall): 76,2 cm</label>
				</span>
			</li>
		</ul>
	</div>
</div>


<script type="text/javascript">

	// display or hide custom sizes
	jQuery("#dress-bottom").on('change', function() {
	    console.log("data changed");

	});

	jQuery("input:radio[name='sizing']").on('change', function() {
		if (jQuery(this).val() == 'xs'){ 
			jQuery(".s,.m,.l,.xl,.2xl,.3xl,.4xl").removeClass('boldSelect');
			jQuery(".xs").addClass('boldSelect');
		}
		else if (jQuery(this).val() == 's'){ 
			jQuery(".xs,.m,.l,.xl,.2xl,.3xl,.4xl").removeClass('boldSelect');
			jQuery(".s").addClass('boldSelect');
		}
		else if (jQuery(this).val() == 'm'){ 
			jQuery(".xs,.s,.l,.xl,.2xl,.3xl,.4xl").removeClass('boldSelect');
			jQuery(".m").addClass('boldSelect');
		}
		else if (jQuery(this).val() == 'l'){ 
			jQuery(".xs,.s,.m,.xl,.2xl,.3xl,.4xl").removeClass('boldSelect');
			jQuery(".l").addClass('boldSelect');
		}
		else if (jQuery(this).val() == 'xl'){ 
			jQuery(".xs,.s,.l,.m,.2xl,.3xl,.4xl").removeClass('boldSelect');
			jQuery(".xl").addClass('boldSelect');
		}
		else if (jQuery(this).val() == '2xl'){ 
			jQuery(".xs,.s,.l,.xl,.m,.3xl,.4xl").removeClass('boldSelect');
			jQuery(".2xl").addClass('boldSelect');
		}
		else if (jQuery(this).val() == '3xl'){ 
			jQuery(".xs,.s,.l,.xl,.2xl,.m,.4xl").removeClass('boldSelect');
			jQuery(".3xl").addClass('boldSelect');
		}
		else if (jQuery(this).val() == '4xl'){ 
			jQuery(".xs,.s,.l,.xl,.2xl,.3xl,.m").removeClass('boldSelect');
			jQuery(".4xl").addClass('boldSelect');
		}
	});

	// display or hide fabrics like a filter, add status to the classes
	jQuery(".filtersProducts").on('click', function() {
	    	var id = (this.id);
	    	var myClass = (this.className);
	    	jQuery(".filtersProducts").find('i').removeClass('on');
	    	jQuery(this).find('i').addClass('on');
	    	jQuery(".filtersProducts").trigger('classChangeButton');
	    	
	    	console.log(id,"Filter changed");
	    	jQuery(".filter").removeClass('on');
	    	jQuery("."+id).addClass('on');
	    	jQuery(".filter").addClass('off').trigger('classChange');
	    	jQuery("."+id).removeClass('off');
	});

	// display or hide fabrics like a filter 
	jQuery('.filter').on('classChange', function() {

			if ( jQuery( this ).hasClass( "on" ) ) {
	    		jQuery( this ).delay('300').show('300');
			} 
			else {
	    		jQuery( this ).hide('300');
	    		jQuery(this).prop('checked', false);
			}
	});

	// Custom filter button for fabric categories
	jQuery('.filtersProducts').on('classChangeButton', function() {

			if ( jQuery( this ).find('i').hasClass( "on" ) ) {
				jQuery( this ).find('i').removeClass('fa-circle-o');
	    		jQuery( this).find('i').addClass('fa-circle');
			} 
			else {
				jQuery( this ).find('i').removeClass('fa-circle');
	    		jQuery( this).find('i').addClass('fa-circle-o');
			}
	});


	// display or hide lengthSkirt like a filter, add status to the classes
	jQuery(".lengthSkirt").on('click', function() {
	    	var id = (this.id);
	    	var myClass = (this.className);
	    	jQuery(".lengthSkirt").find('i').removeClass('on');
	    	jQuery(this).find('i').addClass('on');
	    	jQuery(".lengthSkirt").trigger('classChangeButtonlengthSkirt');
	    	
	    	console.log(id,"Filter changed");
	    	jQuery(".filter").removeClass('on');
	    	jQuery("."+id).addClass('on');
	    	jQuery(".filter").addClass('off').trigger('classChangelengthSkirt');
	    	jQuery("."+id).removeClass('off');
	});

	// display or hide fabrics like a filter lengthSkirt
	jQuery('.filter').on('classChangelengthSkirt', function() {

			if ( jQuery( this ).hasClass( "on" ) ) {
	    		jQuery( this ).delay('300').show('300');
			} 
			else {
	    		jQuery( this ).hide('300');
	    		jQuery(this).prop('checked', false);
			}
	});

	// Custom filter button for categories lengthSkirt
	jQuery('.lengthSkirt').on('classChangeButtonlengthSkirt', function() {

			if ( jQuery( this ).find('i').hasClass( "on" ) ) {
				jQuery( this ).find('i').removeClass('fa-circle-o');
	    		jQuery( this).find('i').addClass('fa-circle');
			} 
			else {
				jQuery( this ).find('i').removeClass('fa-circle');
	    		jQuery( this).find('i').addClass('fa-circle-o');
			}
	});

	// display or hide custom sizes
	jQuery("input:radio[name='sizing']").change(
    	function(){
			if (jQuery(this).is(':checked') && jQuery(this).val() == 'c') { 
		        jQuery('#customSize').show();
		        jQuery('#allSizes').hide();
		        console.log('Display custom size');
			}
			else {
		        jQuery('#allSizes').show();
		        jQuery('#customSize').hide();
		        console.log('Hide custom size');
			}
    });
    jQuery("input:radio[name='sizingType']").on('change', function() {
		if (jQuery(this).val() == 'none'){ 
	    	console.log("Standard selection size");
	    		jQuery('#allSizes').show();
		        jQuery('#customSize').hide();
		    } else {
	    	console.log("Custom selection size");
		        jQuery('#customSize').show();
		        jQuery('#allSizes').hide();

		    }

	});


	// change the metric system accordingly
	jQuery("#metricsSystem").on('change', function() {
		if (jQuery(this).val() == 'inches'){ 
		    jQuery('.inches').show();
		    jQuery('.centimeters').hide();
    		jQuery('#height').val('157').trigger('change');
    		jQuery('#skirtLength').val('20').trigger('change');
	    	console.log("Inches Selected");
		} else {
		    jQuery('.centimeters').show();
		    jQuery('.inches').hide();
    		jQuery('#height').val('155').trigger('change');
    		jQuery('#skirtLength').val('22').trigger('change');
	    	console.log("Centimeters selected");
		}
	});
	
    

	// change the heights accordingly
	jQuery("#height").on('change', function() {
		if (jQuery(this).val() == '155'){ 
	        jQuery('#shoulder_to_waist option[value=40],#shoulder_to_waist option[value=41],#shoulder_to_waist option[value=42],#shoulder_to_waist option[value=43],#shoulder_to_waist option[value=44],#shoulder_to_waist option[value=46],#shoulder_to_waist option[value=47],#shoulder_to_waist option[value=48],#shoulder_to_waist option[value=50],#shoulder_to_waist option[value=51]').hide();

	        jQuery('#shoulder_to_waist option[value=37],#shoulder_to_waist option[value=38],#shoulder_to_waist option[value=34],#shoulder_to_waist option[value=33],#shoulder_to_waist option[value=35]').show();

	        jQuery('#shoulder_to_waist option[value=35]').attr('selected','selected');



	        console.log('set 35');
	    }
	    if (jQuery(this).val() == '157'){

	        jQuery('#shoulder_to_waist option[value=33],#shoulder_to_waist option[value=41],#shoulder_to_waist option[value=42],#shoulder_to_waist option[value=43],#shoulder_to_waist option[value=44],#shoulder_to_waist option[value=46],#shoulder_to_waist option[value=47],#shoulder_to_waist option[value=48],#shoulder_to_waist option[value=50],#shoulder_to_waist option[value=51]').hide();

	        jQuery('#shoulder_to_waist option[value=34],#shoulder_to_waist option[value=35],#shoulder_to_waist option[value=38],#shoulder_to_waist option[value=40],#shoulder_to_waist option[value=37]').show();

	        jQuery('#shoulder_to_waist option[value=37]').attr('selected','selected');




	        console.log('set 37');
	    }
	    if (jQuery(this).val() == '160'){
	        jQuery('#shoulder_to_waist option[value=33],#shoulder_to_waist option[value=34],#shoulder_to_waist option[value=42],#shoulder_to_waist option[value=43],#shoulder_to_waist option[value=44],#shoulder_to_waist option[value=46],#shoulder_to_waist option[value=47],#shoulder_to_waist option[value=48],#shoulder_to_waist option[value=50],#shoulder_to_waist option[value=51]').hide();

	        jQuery('#shoulder_to_waist option[value=35],#shoulder_to_waist option[value=37],#shoulder_to_waist option[value=38],#shoulder_to_waist option[value=40],#shoulder_to_waist option[value=41]').show();

	        jQuery('#shoulder_to_waist option[value=38]').attr('selected','selected');

	        console.log('set 38');
	    }
	    if (jQuery(this).val() == '162'){
	        jQuery('#shoulder_to_waist option[value=33]').hide();
	        jQuery('#shoulder_to_waist option[value=34]').hide();
	        jQuery('#shoulder_to_waist option[value=35]').hide();
	        jQuery('#shoulder_to_waist option[value=37]').show();
	        jQuery('#shoulder_to_waist option[value=38]').show();
	        jQuery('#shoulder_to_waist option[value=40]').show();
	        jQuery('#shoulder_to_waist option[value=40]').attr('selected','selected');
	        jQuery('#shoulder_to_waist option[value=41]').show();
	        jQuery('#shoulder_to_waist option[value=42]').show();
	        jQuery('#shoulder_to_waist option[value=43]').hide();
	        jQuery('#shoulder_to_waist option[value=44]').hide();
	        jQuery('#shoulder_to_waist option[value=46]').hide();
	        jQuery('#shoulder_to_waist option[value=47]').hide();
	        jQuery('#shoulder_to_waist option[value=48]').hide();
	        jQuery('#shoulder_to_waist option[value=50]').hide();
	        jQuery('#shoulder_to_waist option[value=51]').hide();
	        console.log('set 40');
	    }
	    if (jQuery(this).val() == '165'){
	        jQuery('#shoulder_to_waist option[value=33]').hide();
	        jQuery('#shoulder_to_waist option[value=34]').hide();
	        jQuery('#shoulder_to_waist option[value=35]').hide();
	        jQuery('#shoulder_to_waist option[value=37]').hide();
	        jQuery('#shoulder_to_waist option[value=38]').show();
	        jQuery('#shoulder_to_waist option[value=40]').show();
	        jQuery('#shoulder_to_waist option[value=41]').show();
	        jQuery('#shoulder_to_waist option[value=41]').attr('selected','selected');
	        jQuery('#shoulder_to_waist option[value=42]').show();
	        jQuery('#shoulder_to_waist option[value=43]').show();
	        jQuery('#shoulder_to_waist option[value=44]').hide();
	        jQuery('#shoulder_to_waist option[value=46]').hide();
	        jQuery('#shoulder_to_waist option[value=47]').hide();
	        jQuery('#shoulder_to_waist option[value=48]').hide();
	        jQuery('#shoulder_to_waist option[value=50]').hide();
	        jQuery('#shoulder_to_waist option[value=51]').hide();
	        console.log('set 41');
	    }
	    if (jQuery(this).val() == '168'){
	        jQuery('#shoulder_to_waist option[value=33]').hide();
	        jQuery('#shoulder_to_waist option[value=34]').hide();
	        jQuery('#shoulder_to_waist option[value=35]').hide();
	        jQuery('#shoulder_to_waist option[value=37]').hide();
	        jQuery('#shoulder_to_waist option[value=38]').hide();
	        jQuery('#shoulder_to_waist option[value=40]').show();
	        jQuery('#shoulder_to_waist option[value=41]').show();
	        jQuery('#shoulder_to_waist option[value=42]').show();
	        jQuery('#shoulder_to_waist option[value=42]').attr('selected','selected');
	        jQuery('#shoulder_to_waist option[value=43]').show();
	        jQuery('#shoulder_to_waist option[value=44]').show();
	        jQuery('#shoulder_to_waist option[value=46]').hide();
	        jQuery('#shoulder_to_waist option[value=47]').hide();
	        jQuery('#shoulder_to_waist option[value=48]').hide();
	        jQuery('#shoulder_to_waist option[value=50]').hide();
	        jQuery('#shoulder_to_waist option[value=51]').hide();
	        console.log('set 42');
	    }
	    if (jQuery(this).val() == '170'){
	        jQuery('#shoulder_to_waist option[value=33]').hide();
	        jQuery('#shoulder_to_waist option[value=34]').hide();
	        jQuery('#shoulder_to_waist option[value=35]').hide();
	        jQuery('#shoulder_to_waist option[value=37]').hide();
	        jQuery('#shoulder_to_waist option[value=38]').hide();
	        jQuery('#shoulder_to_waist option[value=40]').hide();
	        jQuery('#shoulder_to_waist option[value=41]').show();
	        jQuery('#shoulder_to_waist option[value=42]').show();
	        jQuery('#shoulder_to_waist option[value=43]').show();
	        jQuery('#shoulder_to_waist option[value=43]').attr('selected','selected');
	        jQuery('#shoulder_to_waist option[value=44]').show();
	        jQuery('#shoulder_to_waist option[value=46]').show();
	        jQuery('#shoulder_to_waist option[value=47]').hide();
	        jQuery('#shoulder_to_waist option[value=48]').hide();
	        jQuery('#shoulder_to_waist option[value=50]').hide();
	        jQuery('#shoulder_to_waist option[value=51]').hide();
	        console.log('set 43');
	    }
	    if (jQuery(this).val() == '173'){
	        jQuery('#shoulder_to_waist option[value=33]').hide();
	        jQuery('#shoulder_to_waist option[value=34]').hide();
	        jQuery('#shoulder_to_waist option[value=35]').hide();
	        jQuery('#shoulder_to_waist option[value=37]').hide();
	        jQuery('#shoulder_to_waist option[value=38]').hide();
	        jQuery('#shoulder_to_waist option[value=40]').hide();
	        jQuery('#shoulder_to_waist option[value=41]').hide();
	        jQuery('#shoulder_to_waist option[value=42]').show();
	        jQuery('#shoulder_to_waist option[value=43]').show();
	        jQuery('#shoulder_to_waist option[value=44]').show();
	        jQuery('#shoulder_to_waist option[value=44]').attr('selected','selected');
	        jQuery('#shoulder_to_waist option[value=46]').show();
	        jQuery('#shoulder_to_waist option[value=47]').show();
	        jQuery('#shoulder_to_waist option[value=48]').hide();
	        jQuery('#shoulder_to_waist option[value=50]').hide();
	        jQuery('#shoulder_to_waist option[value=51]').hide();
	        console.log('set 44');
	    }
	    if (jQuery(this).val() == '175'){
	        jQuery('#shoulder_to_waist option[value=33]').hide();
	        jQuery('#shoulder_to_waist option[value=34]').hide();
	        jQuery('#shoulder_to_waist option[value=35]').hide();
	        jQuery('#shoulder_to_waist option[value=37]').hide();
	        jQuery('#shoulder_to_waist option[value=38]').hide();
	        jQuery('#shoulder_to_waist option[value=40]').hide();
	        jQuery('#shoulder_to_waist option[value=41]').hide();
	        jQuery('#shoulder_to_waist option[value=42]').hide();
	        jQuery('#shoulder_to_waist option[value=43]').show();
	        jQuery('#shoulder_to_waist option[value=44]').show();
	        jQuery('#shoulder_to_waist option[value=46]').show();
	        jQuery('#shoulder_to_waist option[value=46]').attr('selected','selected');
	        jQuery('#shoulder_to_waist option[value=47]').show();
	        jQuery('#shoulder_to_waist option[value=48]').show();
	        jQuery('#shoulder_to_waist option[value=50]').hide();
	        jQuery('#shoulder_to_waist option[value=51]').hide();
	        console.log('set 46');
	    }
	    if (jQuery(this).val() == '177'){
	        jQuery('#shoulder_to_waist option[value=33]').hide();
	        jQuery('#shoulder_to_waist option[value=34]').hide();
	        jQuery('#shoulder_to_waist option[value=35]').hide();
	        jQuery('#shoulder_to_waist option[value=37]').hide();
	        jQuery('#shoulder_to_waist option[value=38]').hide();
	        jQuery('#shoulder_to_waist option[value=40]').hide();
	        jQuery('#shoulder_to_waist option[value=41]').hide();
	        jQuery('#shoulder_to_waist option[value=42]').hide();
	        jQuery('#shoulder_to_waist option[value=43]').hide();
	        jQuery('#shoulder_to_waist option[value=44]').show();
	        jQuery('#shoulder_to_waist option[value=46]').show();
	        jQuery('#shoulder_to_waist option[value=47]').show();
	        jQuery('#shoulder_to_waist option[value=47]').attr('selected','selected');
	        jQuery('#shoulder_to_waist option[value=48]').show();
	        jQuery('#shoulder_to_waist option[value=50]').show();
	        jQuery('#shoulder_to_waist option[value=51]').hide();
	        console.log('set 47');
	    }
	    if (jQuery(this).val() == '180'){
	        jQuery('#shoulder_to_waist option[value=33]').hide();
	        jQuery('#shoulder_to_waist option[value=34]').hide();
	        jQuery('#shoulder_to_waist option[value=35]').hide();
	        jQuery('#shoulder_to_waist option[value=37]').hide();
	        jQuery('#shoulder_to_waist option[value=38]').hide();
	        jQuery('#shoulder_to_waist option[value=40]').hide();
	        jQuery('#shoulder_to_waist option[value=41]').hide();
	        jQuery('#shoulder_to_waist option[value=42]').hide();
	        jQuery('#shoulder_to_waist option[value=43]').hide();
	        jQuery('#shoulder_to_waist option[value=44]').hide();
	        jQuery('#shoulder_to_waist option[value=46]').show();
	        jQuery('#shoulder_to_waist option[value=47]').show();
	        jQuery('#shoulder_to_waist option[value=48]').show();
	        jQuery('#shoulder_to_waist option[value=48]').attr('selected','selected');
	        jQuery('#shoulder_to_waist option[value=50]').show();
	        jQuery('#shoulder_to_waist option[value=51]').show();
	        console.log('set 48');
	    }
	});
	</script> 
</div>
	<?php

	}
?>
<div class="fullwidth addButton">
	<?php
}

add_action( 'woocommerce_before_add_to_cart_button', 'display_measurement_fields' );
