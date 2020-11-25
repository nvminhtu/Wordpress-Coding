
In this chapter: We write down a function which user can change posts using select option (choose catgory) 

Frontend: Show categories list (drop down or only listing categories) - It's up to your Frontend code.
```
<div class="filter-wrap">
    <div class="category">
        <div class="field-title">Category</div>
        <?php $get_categories = get_categories(array('hide_empty' => 0)); ?>
            <select class="js-category">
                <option value="all">All</option>
                <?php
					if ( $get_categories ) :
						foreach ( $get_categories as $cat ) :
					?>
                    <option value="<?php echo $cat->term_id; ?>">
                        <?php echo $cat->name; ?>
                    </option>
                    <?php endforeach; 
						endif;
					?>
            </select>
    </div>
 
    <div class="date">
        <div class="field-title">Sort by</div>
        <select class="js-date">
            <option value="new">Newest</option>
            <option value="old">Oldest</option>
        </select>
    </div>
</div>
```

Frontend: Show Results - We declared `filtered-posts` - place to put your all data to show result of AJAX
`get_template_part( 'template-parts/content-post' );`: This is your content of post (format or sthing....)

```
<div class="filtered-posts">
<?php
 
if ( have_posts() ) :
	while ( have_posts() ) : the_post();
		get_template_part( 'template-parts/content-post' );
	endwhile;
endif;
 
?>
</div>
```

Javascript: Ajax Filter Code (write below your Frontend code - result)
> In this place: we check the action `change` of selector ( category/ date). 
category: taxonomy in wordpress
date: attribute in wordpress
> Then We write $.ajax and put all your query to `data`
> the main is success: function(data) => we check if run well => show result with data of PHP code - we wrote above.

```
jQuery(document).ready(function($){
	jQuery( ".js-category, .js-date" ).on( "change", function() {
		var category = $( '.js-category' ).val();
		var date = $( '.js-date' ).val()
 
		data = {
			'action': 'filterposts',
			'category': category,
			'date': date
		};
 
		$.ajax({
			url : ajaxurl,
			data : data,
			type : 'POST',
			beforeSend : function ( xhr ) {
				$('.filtered-posts').html( 'Loading...' );
				$('.js-category').attr( 'disabled', 'disabled' );
				$('.js-date').attr( 'disabled', 'disabled' );
			},
			success : function( data ) {
				if ( data ) {
					$('.filtered-posts').html( data.posts );
 
					$('.js-category').removeAttr('disabled');
					$('.js-date').removeAttr('disabled');
				} else {
					$('.filtered-posts').html( 'No posts found.' );
				}
			}
		});
	});
});
```

Declare your AJAX in functions.php (wordpress)
Write Query PHP code to show data in this AJAX function (get params from AJAX - above code)
```
	function ajax_filterposts_handler() {
		$category = esc_attr( $_POST['category'] );
		$date = esc_attr( $_POST['date'] );
 
		$args = array(
			'post_type' => 'post',
			'post_status' => 'publish',
			'posts_per_page' => -1,
			'orderby' => 'date',
			'order' => 'DESC'
		);
 
		if ( $category != 'all' )
			$args['cat'] = $category;
 
		if ( $date == 'new' ) {
			$args['order'] = 'DESC';
		} else {
			$args['order'] = 'ASC';
		}
 
		$posts = 'No posts found.';
 
		$the_query = new WP_Query( $args );
	 
		if ( $the_query->have_posts() ) :
			ob_start();
 
			while ( $the_query->have_posts() ) : $the_query->the_post();
				get_template_part( 'template-parts/content-post' );
			endwhile;
 
			$posts = ob_get_clean();
		endif;
 
		$return = array(
	    	'posts' => $posts
		);
 
		wp_send_json($return);
	}
	add_action( 'wp_ajax_filterposts', 'ajax_filterposts_handler' );
	add_action( 'wp_ajax_nopriv_filterposts', 'ajax_filterposts_handler' );
```
