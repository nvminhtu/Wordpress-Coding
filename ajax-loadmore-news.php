<?php
/** 
 * ========= AJAX LOAD MORE CUSTOM FUNCTIONS =========
*/

/** Ajax Filter News **/
function ajax_filterposts_handler() {
	// ajax parameters
	$date = esc_attr( $_POST['date'] );
	$offset = isset($_POST['offset']) ? (int)$_POST['offset'] : 0;
	$posts_per_query = 6;
	
	// args: query
	$args = array(
		'post_type' => 'news',
		'post_status' => 'publish',
		'posts_per_page' => $posts_per_query,
		'orderby' => 'date',
		'offset' => $offset,
		'order' => 'DESC'
	);

	// args: parse parameter to real value
	if ( $date == 'new' ) {
		$args['order'] = 'DESC';
	} else {
		$args['order'] = 'ASC';
	}

	$posts = 'No posts found.';
	$the_query = new WP_Query( $args );
 
	if ( $the_query->have_posts() ) :
		ob_start(); ?>
		<ul>
		<?php while ( $the_query->have_posts() ) {
			$the_query->the_post(); ?>
				<li><a href="#"><?php the_title(); ?></a></li>
			<?php 
		} ?>
		</ul>

		<?php $posts = ob_get_clean();
	endif;

	$return = array(
		'posts' => $posts
	);

	wp_send_json($return);
}
add_action( 'wp_ajax_filterposts', 'ajax_filterposts_handler' );
add_action( 'wp_ajax_nopriv_filterposts', 'ajax_filterposts_handler' );

/** Ajax Load More News **/
add_action('wp_ajax_loadmore_news', 'loadmore_news_data');
add_action('wp_ajax_nopriv_loadmore_news', 'loadmore_news_data');

function loadmore_news_data() {
	// ajax parameters
	$date = esc_attr( $_POST['date'] );
	$offset = isset($_POST['offset']) ? (int)$_POST['offset'] : 0;

    $posts_per_query = 3;
    $args = array(
				'post_type' => 'news',
				'post_status' => 'publish',
				'posts_per_page' => $posts_per_query,
				'orderby' => 'date',
				'order'   => 'DESC',
				'offset' => $offset
			);
	
	// parse parameter to real value
	if ( $date == 'new' ) {
		$args['order'] = 'DESC';
	} else {
		$args['order'] = 'ASC';
	}

	$the_query = new WP_Query( $args );
	
	global $wp_query; 
    $wp_query->in_the_loop = true;
	$total_posts = $the_query->found_posts;
	
    if($total_posts!=0) {
		if ( $the_query->have_posts() ) { ?>
			<ul>
				<?php while ( $the_query->have_posts() ) {
					$the_query->the_post(); ?>
						<li><a href="#"><?php the_title(); ?></a></li>
					<?php 
				} ?>
			</ul>
		<?php } else { 
				// no posts found 
			}
		wp_reset_postdata();
    }
    die();
}
?>
