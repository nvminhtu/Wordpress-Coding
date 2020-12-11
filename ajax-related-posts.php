<?php 
add_action('wp_ajax_relatedloadmore', 'related_load_more_posts');
add_action('wp_ajax_nopriv_relatedloadmore', 'related_load_more_posts');
function related_load_more_posts() {
	global $post;
	$offset = isset($_POST['offset']) ? (int)$_POST['offset'] : 0;
	$cat = isset($_POST['cat']) ? $_POST['cat'] : 'vietnam';
    $posts_per_query = 3;
    $args = array(
        'post_type' => 'post',
        'posts_per_page' => $posts_per_query,
		'orderby' => 'date',
		'order'   => 'DESC',
        'category__in' => wp_get_post_categories( $post->ID ),
        'post__not_in' => array( $post->ID ),
        'offset' => $offset
    );

    $the_query = new WP_Query( $args );
	global $wp_query; 
    $wp_query->in_the_loop = true; 
    $total_posts = $the_query->found_posts;
    echo '<div class="row">';
    if($total_posts!=0) {
    	if ( $the_query->have_posts() ) {
		    while ( $the_query->have_posts() ) {
		        $the_query->the_post();
		    	 echo '<div class="col-12 col-md-4 post-item">
		        		<div class="card">
			        		<a href="'.get_the_permalink().'"><figure>
								<img src="'.get_stylesheet_directory_uri().'/assets/images/thum-post-display.png" alt="'.get_the_title().'">
							</figure</a>
							<div class="card-body">
			        			<h4><a href="'.get_the_permalink().'">'.get_the_title() . '</a></h4>
				        		<p>'.mb_substr(strip_tags($post->post_content),0,100) . '...</p>
				        		<p class="meta meta-date">'.get_the_date().'</p>
		        			</div>
		        		</div>
		        	</div>';
		    }
		}
		wp_reset_postdata();
    }
    echo '</div>';
    die();
}
function total_related_posts() {
	global $post;
	$args = array(
        'post_type' => 'post',
        'posts_per_page' => -1,
        'orderby' => 'date',
        'category__in' => wp_get_post_categories( $post->ID ), 
        'post__not_in' => array( $post->ID ),
        'offset' => $offset
    );

    $the_query = new WP_Query( $args );
	$total_posts = $the_query->found_posts;
	return $total_posts;
}

add_action( 'wp_footer', 'ajax_loadmore_posts_related' );
function ajax_loadmore_posts_related(){
	if(is_single()) {
  ?>
  	<script type="text/javascript">
		jQuery(document).ready(function($) {
			var offset = 3,
				total_posts = <?php echo total_related_posts(); ?>; 
			console.log(total_posts);
		    $('.load-more').click(function(event) {
		    	$.ajax({ 
		            type : "post",
		            dataType : "html",
		            async: false,
		            url : '<?php echo admin_url('admin-ajax.php');?>', 
		            data : {
		                action: "relatedloadmore", 
		                offset: offset
		            },
		            beforeSend: function(){
		                $('.load-more').addClass('spinner'); //add preLoad Spinner
		            },
		            success: function(response) {
		            	if ( '' === response ) {
		                    $('.action-posts').hide();
		                } else {
			                $('.cat-must-go').append(response);
			                offset = offset + 3;
			                if(offset >= total_posts) {
			                	$('.action-posts').hide();
			                }
		                }
		                // remove preLoad Spinner
		                $('.load-more').removeClass('spinner');
		            },
		            error: function( jqXHR, textStatus, errorThrown ){
		                console.log( 'The following error occured: ' + textStatus, errorThrown );
		            }
		       });
		    });
		});
	</script>
  <?php } // end check is_single
}
?>
