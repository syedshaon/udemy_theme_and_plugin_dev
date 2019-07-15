<?php 
/*
* Template Name: Full Width page
*/
get_header(); 
while(have_posts()){
    the_post(); 
?>

        <!-- Page Title
============================================= -->
    <section id="page-title">

        <div class="container clearfix">
            <h1><?php echo get_the_title(); ?></h1>
           <?php if(function_exists("the_subtitle")){ ?>
            <span><?php the_subtitle(); ?></span>
           <?php } ?>
        </div>

    </section><!-- #page-title end -->

<?php    }
rewind_posts();
?>

	<!-- Content
    ============================================= -->
	<section id="content">

		<div class="content-wrap">
            <div class="container clearfix">				

            
                <?php if(have_posts()){
                    while(have_posts()){
                        the_post(); ?>

                    <div class="single-post nobottommargin">

                        <!-- Single Post
                        ============================================= -->
                        <div class="entry clearfix">

                            

                            <!-- Entry Image
                            ============================================= -->
                            <?php if(has_post_thumbnail()){?>
									<div class="entry-image">
										<a href="<?php the_permalink(); ?>">
											<?php the_post_thumbnail("full"); ?>
										</a>
									</div>
							<?php } ?>

                            <!-- Entry Content
                            ============================================= -->
                            <div class="entry-content notopmargin">

                                <?php the_content(); ?>
                                <?php wp_link_pages(array(
                                    'before'  => '<p class="text-center">'.__('Pages:'),
                                    'after'   => '</p>',
                                )); ?>
                                <!-- Post Single - Content End -->

                                <!-- Tag Cloud
                                ============================================= -->
                                <div class="tagcloud clearfix bottommargin">
                                    <?php the_tags("", " "); ?>
                                </div><!-- .tagcloud end -->

                                <div class="clear"></div>

                            </div>
                        </div><!-- .entry end -->

                        


                       
					</div>


                <?php    }
                } ?>

                    
			</div>

		</div>

	</section><!-- #content end -->

    
<?php get_footer(); ?>