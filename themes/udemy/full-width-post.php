<?php
/*
* Template Name: Full Width Post
* Template Post Type: Post
*/


get_header(); ?>

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

                            <!-- Entry Title
                            ============================================= -->
                            <div class="entry-title">
                                <h2><?php echo get_the_title(); ?></h2>
                            </div><!-- .entry-title end -->

                            <!-- Entry Meta
                            ============================================= -->
                            <ul class="entry-meta clearfix">
                                <li><i class="icon-calendar3"></i> <?php echo get_the_date(); ?></li>
                                <li><a href=" <?php echo get_author_posts_url( get_the_author_meta('ID') ); ?> "><i class="icon-user"></i> <?php the_author(); ?></a></li>
                                <li><i class="icon-folder-open"></i> <?php the_category(' ') ?></li>
                                <li><a href="#"><i class="icon-comments"></i><?php comments_number('0') ?> Comments</a></li>
                            </ul><!-- .entry-meta end -->

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

                        <!-- Post Navigation
                        ============================================= -->
                        <div class="post-navigation clearfix">

                            <div class="col_half nobottommargin">
                                <?php previous_post_link(); ?>
                            </div>

                            <div class="col_half col_last tright nobottommargin">
                                <?php next_post_link(); ?>
                            </div>

                        </div><!-- .post-navigation end -->

                        <div class="line"></div>

                        <!-- Post Author Info
                        ============================================= -->
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <h3 class="panel-title">Posted by <span><a href=" <?php echo get_author_posts_url( get_the_author_meta('ID') ); ?> "><?php the_author(); ?></a></span></h3>
                            </div>
                            <div class="panel-body">
                                <div class="author-image">
                                    <?php echo get_avatar( get_the_author_meta('ID') , 90, "", false, array( 'class' => 'img-circle')); ?>
                                </div>
                                <?php echo nl2br(get_the_author_meta('description')); ?>
                            </div>
                        </div><!-- Post Single - Author End -->

                        <div class="line"></div>

                        <h4>Related Posts:</h4>

                       
                        <?php
                         $categories = get_the_category();
                            $rp_query = new WP_Query(array(
                                'posts_per_page' => 2,
                                'post__not_in'   => array( $post -> ID),
                                'cat'            => $categories[0]-> term_id
                            ));

                            
                        ?>

                        <div class="related-posts clearfix">
                            <div class="mpost clearfix">
                                <?php if($rp_query->have_posts()){
                                while($rp_query->have_posts()){
                                        $rp_query->the_post();?>

                                    <?php  if(has_post_thumbnail()){ ?>
                                        <div class="entry-image">
                                            <a href="<?php the_permalink(); ?>"> <?php the_post_thumbnail(); ?></a>
                                        </div>
                                    <?php } ?>

                                        <div class="entry-c">
                                            <div class="entry-title">
                                                <a href="<?php the_permalink(); ?>"> <h4><?php echo get_the_title(); ?></h4></a>
                                            </div>
                                            <ul class="entry-meta clearfix">
                                                <li><i class="icon-calendar3"></i><?php echo get_the_date(); ?></li>
                                                <li><a href="<?php the_permalink(); ?>"> <i class="icon-comments"><?php comments_number('0'); ?></i></a> </li>
                                            </ul>
                                            <div class="entry-content">
                                                <?php the_excerpt(); ?>
                                            </div>
                                        </div>

                                    <?php
                                    }
                                    wp_reset_postdata();
                                }
                                                        
                                ?>
                            </div>
                            
                        </div>

                            

                            <!-- Comments
                        ============================================= -->

                        <?php
                        if(comments_open() || get_comments_number()){
                            comments_template(); 
                        }                        
                        ?>

                       
					</div>


                <?php    }
                } ?>


			</div>
 
		</div>

	</section><!-- #content end -->

    
<?php get_footer(); ?>