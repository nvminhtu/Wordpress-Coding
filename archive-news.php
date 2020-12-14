<?php

/**
 * News Archive Page
 */
get_header();
?>
<!-- Our News Listing -->
<section id="me-group-news" class="cd-news-listing grid-box">
  <div class="filter-wrap">
    <div class="date">
      <div class="field-title">Sort by</div>
      <select class="js-date">
        <option value="new">Newest</option>
        <option value="old">Oldest</option>
      </select>
    </div>
  </div>

  <div class="filtered-posts cat-must-go">
    <?php 

      $posts_per_page = 6;
      $args = array(
          'post_type' => 'news',
          'posts_per_page' => $posts_per_page,
          'order'   => 'DESC',
          'orderby' => 'date',
      );
      $the_query = new WP_Query( $args );
      if ( $the_query->have_posts() ) { ?>
        <ul>
        <?php while ( $the_query->have_posts() ) {
            $the_query->the_post(); ?>
              <li><a href="#"><?php the_title(); ?></a></li>
          <?php }
          } else { 
            // no posts found 
          }
        ?></ul>
      <?php wp_reset_postdata();
    ?>
  </div><!-- End Filter posts show -->

  <div class="action-posts container">
    <div class="row">
      <div class="col"><button class="load-more" data-offset="6" data-total-posts="9">View More</button></div>
    </div>
  </div>


  
  <div class="cd-news-list container grid">
    <div class="cd-news-list__heading padding-sm">
      <h2 class="mt-page-heading">News</h2>
      <div class="cd-news-list__sort">
        <p>Sort posts by<span>Newest</span></p>
      </div>
    </div>

    <div class="cd-news-list__content container grid gap-md" style="--gap: 8px;">
      <?php
        if ( have_posts() ) :
          while ( have_posts() ) : the_post();
            ?>
             <div class="cd-news-list__item padding-sm col-4@sm">
              <div class="cd-news-list__item-top">
                <a href="<?php echo get_permalink(); ?>"><img src="<?php echo bloginfo('template_url'); ?>/theme-assets/img/bnr-news.jpg" alt="News 01"></a>
                <span class="cd-news-date">Jul 3, 2020</span>
              </div>
              <div class="cd-news-list__item-info">
                <h3 class="cd-news-title"><a href="news-detail.html">News gears</a></h3>
                <p class="cd-news-description">Lorem ipsum dolor sit amet, consectetur adipiscing elit... </p>
              </div>
            </div>
            <?php
          endwhile;
        endif; 
      ?>

      <div class="cd-news-list__readmore">
        <p><a href="#">Load More</a></p>
      </div>
    </div><!-- End cd-news-list__content -->
  </div>
</section>
<!-- End Our News Listing -->
<?php get_footer(); ?>
<?php 
/* 
  // Custom JS for AJAX here
  // calculation the number of posts: news
  function total_all_posts() {
    $offset = 3;
    $args = array(
        'post_type' => 'news',
        'posts_per_page' => -1,
        'orderby' => 'date',
        'offset' => $offset
      );
      $the_query = new WP_Query( $args );
    $total_posts = $the_query->found_posts;
    return $total_posts;
  } */
?>
<script type="text/javascript">
  jQuery(document).ready(function($) {
    var ajaxurl = 'http://megroup_new.local/wp-admin/admin-ajax.php';

    // Call functions
    load_more_news();
    sort_news();

    function sort_news() {
      jQuery(".js-date").on("change", function(event) {
          event.preventDefault();
          // switch option -> we reset option
          $('.action-posts').show();
          $('.load-more').data('offset',6);
          menews_ajax_search();
      });
    }
    // Filter & Sort News
    function menews_ajax_search() {
      var date = $('.js-date').val();
      data = {
        'action': 'filterposts',
        'date': date
      };
      $.ajax({
        url: ajaxurl,
        data: data,
        type: 'POST',
        beforeSend: function(xhr) {
          $('.filtered-posts').html('Loading...');
          $('.js-date').attr('disabled', 'disabled');
        },
        success: function(data) {
          if (data) {
            $('.filtered-posts').html(data.posts);
            $('.js-date').removeAttr('disabled');
            sort_news();
            load_more_news();
          } else {
            $('.filtered-posts').html('No posts found.');
          }
        }
      });
    }
    
    // Load more News 
    function load_more_news() {
      $('.load-more').click(function(event) {
        var loadmore = $(this);
        var date = $('.js-date').val();
        var offset = loadmore.data('offset');
        var total_posts = loadmore.data('total-posts');; 

        $.ajax({ 
              type : "POST",
              dataType : "html",
              async: false,
              url : '<?php echo admin_url('admin-ajax.php');?>', 
              data : {
                  action: "loadmore_news", 
                  offset: offset,
                  date: date
              },
              beforeSend: function(){
                loadmore.addClass('spinner'); //add preLoad Spinner
              },
              success: function(response) {
                if ( '' === response ) {
                  $('.action-posts').hide();
                } else {
                  // all actions go here (parse remaining posts)
                  $('.cat-must-go').append(response);
                  offset = offset + 3;
                  loadmore.data('offset', offset);

                  if(offset >= total_posts) {
                    $('.action-posts').hide();
                  }
                }
                loadmore.removeClass('spinner'); // remove preLoad Spinner
              },
              error: function( jqXHR, textStatus, errorThrown ){
                  console.log( 'The following error occured: ' + textStatus, errorThrown );
              }
          });
      });
    }
}); 
</script>
