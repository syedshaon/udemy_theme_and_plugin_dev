<?php get_header(); ?>

	<!-- Content
    ============================================= -->
	<section id="content">

		<div class="content-wrap">	

			 <?php

				if( !is_single() && is_home() && function_exists('wpp_get_mostpopular' )  && get_theme_mod( 'ju_show_header_popular_posts_widget' ) ){
					wpp_get_mostpopular(array(
						'wpp_start'         =>  '
							<div class="section header-stick bottommargin-lg clearfix" style="padding: 20px 0;">
								<div>
									<div class="container clearfix">
										<span class="label label-danger bnews-title">' . get_theme_mod( 'ju_popular_posts_widget_title' ) . ':</span>
										<div class="fslider bnews-slider nobottommargin" data-speed="800" data-pause="6000" data-arrows="false" data-pagi="false">
											<div class="flexslider">
												<div class="slider-wrap">',
						'wpp_end'           =>  '</div>
												</div>
											</div>
										</div>
									</div>
								</div>',
						'post_html'         =>  '<div class="slide"><a href="{url}"><strong>{text_title}</strong></a></div>'
					));
				}

			?>	

			<div class="container clearfix">

				<!-- Post Content
                ============================================= -->
				<div class="postcontent nobottommargin clearfix">

					<!-- Posts
                    ============================================= -->
					<div id="posts">

						<?php if(have_posts()){
							while(have_posts()){
								the_post(); 
								
							 get_template_part('partials/post/content-excerpt'); 	
								
								?>

														
							
							<?php }

						} ?>

						

					</div><!-- #posts end -->

					<!-- Pagination
                    ============================================= -->
					<ul class="pager nomargin">
						<li class="previous"> <?php previous_posts_link('&larr; Older'); ?> </li>
						<li class="next"><?php next_posts_link('Newer &rarr;') ?></li>
					</ul><!-- .pager end -->

				</div><!-- .postcontent end -->
                    <?php get_sidebar(); ?>

			</div>

		</div>

	</section><!-- #content end -->

    
<?php get_footer(); ?>