<?php
/**
 * @user fields for woocommerce
 ** link edit: [site_url]/my-account/edit-account/
 ** require woocommerce install
 */

/**
 * Step 1. Add your field
 */
add_action( 'woocommerce_edit_account_form', 'hf_add_field_edit_account_form' );
function hf_add_field_edit_account_form() {
    
    // 01-Load Measurements to Profile Meta Key
    $args = array(
        'post_type' => 'measurement',
        'posts_per_page' => -1,
        'order'   => 'DESC',
        'orderby' => 'date',
    );
    $the_query = new WP_Query( $args );
    if ( $the_query->have_posts() ) {  
        while ( $the_query->have_posts() ) {
            $the_query->the_post();

            $sizing_id = get_the_ID();
            $sizing = get_post($sizing_id); 
            $measurement_size_min = get_field('measurement_size_min');
            $measurement_size_max = get_field('measurement_size_max');

            $measurement_meta_slug = $sizing->post_name; 
            $measurement_meta_title = get_the_title(); 

            // options for Items: for each Woocommerce field
            $arr_options = array();
            for( $i= $measurement_size_min; $i<= $measurement_size_max; $i++) {
                array_push($arr_options, $i);
            }
            // Item: Add Woocommerce field (parse value from CPT)
            woocommerce_form_field(
                $measurement_meta_slug, // ex:'country_to_visit'
                array(
                    'type'        => 'select',
                    'required'    => true, // remember, this doesn't make the field required, just adds an "*"
                    'label'       => $measurement_meta_title,
                    'options' => $arr_options
                ),
                get_user_meta( get_current_user_id(), $measurement_meta_slug, true ) // get the data
            );
        } // endwhile: measurement loop
    } else { 
        // no posts found 
    } ?>
    <?php wp_reset_postdata();

    // call loop of fields: measurement
	/* woocommerce_form_field(
		'country_to_visit',
		array(
			'type'        => 'select',
			'required'    => true, // remember, this doesn't make the field required, just adds an "*"
            'label'       => 'Country you want to visit the most',
            'options' => array(
                'regular' => __('Regular', 'hf_account_lang_fields'),
                'premium' => __('Premium', 'hf_account_lang_fields'),
                'big' => __('Big', 'hf_account_lang_fields')
            )
		),
		get_user_meta( get_current_user_id(), 'country_to_visit', true ) // get the data
    );
    */
 
}

/**
 * Step 2. Save field value
 */
add_action( 'woocommerce_save_account_details', 'hf_save_account_details' );
function hf_save_account_details( $user_id ) {
	update_user_meta( $user_id, 'country_to_visit', sanitize_text_field( $_POST['country_to_visit'] ) );
}

/**
 * Step 3. Make it required
 */
add_filter('woocommerce_save_account_details_required_fields', 'hf_make_field_required');
function hf_make_field_required( $required_fields ){
 
	$required_fields['country_to_visit'] = 'Country you want to visit the most';
	return $required_fields;
 
}
