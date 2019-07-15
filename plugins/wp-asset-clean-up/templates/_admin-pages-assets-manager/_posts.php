<?php
/*
 * No direct access to this file
 */
if (! isset($data)) {
	exit;
}
?>
<div style="margin: 25px 0 0;">
    <p>Post Type: 'post' (e.g. blog entries) &#10230; <a target="_blank" href="https://wordpress.org/support/article/writing-posts/"><?php _e('read more', 'wp-asset-clean-up'); ?></a></p>

    <p style="margin-bottom: 0;">&#10230; If "Manage in the Dashboard?" is enabled:</p>
    <p style="margin-top: 0;">Go to "Posts" -&gt; "All Posts" -&gt; [Choose the page you want to manage the assets for] -&gt; Scroll to "Asset CleanUp" meta box where you will see the loaded CSS &amp; JavaScript files</p>

    <hr />

    <p style="margin-bottom: 0;">&#10230; If "Manage in the Front-end?" is enabled and you're logged in:</p>
    <p style="margin-top: 0;">Go to the page where you want to manage the files and scroll to the bottom of the page where you will see the list.</p>
</div>