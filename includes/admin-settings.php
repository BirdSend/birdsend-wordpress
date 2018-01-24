<?php

$bswp_token = bswp_token();
$bswp_options = bswp_options();

// Options
$bswp_enabled = bswp_is_enabled();

// Posts and pages
$posts = get_posts();
$pages = get_pages();

// Custom post types
$args = array('public' => true, '_builtin' => false);
$post_types = get_post_types($args, 'object', 'and');

?>
<div class="bswp-wrapper">
    <header>
        <nav class="grey lighten-5">
            <div class="nav-wrapper">
                <a href="https://birdsend.co" target="_blank" class="brand-logo center"><img src="<?php echo BSWP_IMG; ?>logo-sm.png" ></a>
                <!--
                <ul id="nav-mobile" class="right hide-on-med-and-down">
                    <li><a href="sass.html">Sass</a></li>
                    <li><a href="badges.html">Components</a></li>
                    <li><a href="collapsible.html">JavaScript</a></li>
                </ul>
                //-->
            </div>
        </nav>
    </header>
    <div class="bswp-container">
        <div class="row">
            <div class="col s12 l12">
                <div class="card card-wide">
                    <div class="card-content">
                        <?php if ($bswp_token) { ?>
                        <span class="badge green lighten-2 white-text">Connected</span>
                        <?php } else { ?>
                        <span class="badge red lighten-2 white-text">Not Connected</span>
                        <?php } ?>
                        <span class="card-title"><strong>API Settings</strong></span>

                        <?php if ($bswp_token) { ?>
                        <form action="" method="POST">
                            <div style="display: none;">
                                <input type="hidden" name="bswp_submit" value="disconnect">
                            </div>
                            <div style="margin: 15px 0">
                                <button type="submit" id="bswp-disconnect-btn" class="waves-effect waves-light btn red lighten-2">Disconnect My BirdSend</button>
                            </div>
                            <p>Click the button above to reset/remove your BirdSend integration</p>
                        </form>
                        <?php } else { ?>
                        <div style="margin: 15px 0">
                            <button data-url="<?php echo admin_url('admin.php?page=bswp-settings&mode=connect'); ?>" id="bswp-connect-btn" class="waves-effect waves-light btn orange lighten-2">Connect My BirdSend</button>
                        </div>
                        <p>Click the button above to integrate WordPress with your BirdSend account</p>
                        <?php } ?>
                    </div>
                </div><!-- ./card -->
                <?php if ($bswp_token) { ?>
                <div class="card card-wide">
                    <div class="card-content">
                        <span class="card-title"><strong>BirdSend Pixel Settings</strong></span><br>
                        <form method="POST">
                            <div style="display: none;">
                                <input type="hidden" name="bswp_submit" value="save_options">
                            </div>
                            <div class="row">
                                <div class="col s8">Enable</div>
                                <div class="col s4">
                                    <div class="switch right">
                                        <label>
                                            No
                                            <input type="checkbox" id="bswp_settings_switch" name="bswp_options[enabled]" value="1" <?php echo $bswp_enabled ? 'checked="checked"' : '' ?>>
                                            <span class="lever"></span>
                                            Yes
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div id="bswp_settings_box">
                                <div class="row">
                                    <div class="col s12 notice notice-info">
                                        <p>By default, your BirdSend pixel will be inserted to all of your posts/pages/custom posts. Use options below to exclude them.</p>
                                    </div>
                                    <div class="col s12"><p>&nbsp;</p></div>
                                    <div class="input-field col s12">
                                        <select multiple name="bswp_options[excluded_posts][]">
                                            <option value="" disabled selected>Choose your posts</option>
                                            <?php foreach ($posts as $post) { ?>
                                            <?php $selected = isset($bswp_options['excluded_posts']) ? in_array($post->ID, $bswp_options['excluded_posts']) : false; ?>
                                                <option value="<?php echo $post->ID; ?>" <?php echo $selected ? 'selected="selected"' : ''; ?>><?php echo $post->post_title; ?></option>
                                            <?php } ?>
                                        </select>
                                        <label>Exclude Posts</label>
                                    </div>
                                    <div class="input-field col s12">
                                        <select multiple name="bswp_options[excluded_pages][]">
                                            <option value="" disabled selected>Choose your pages</option>
                                            <?php foreach ($pages as $page) { ?>
                                            <?php $selected = isset($bswp_options['excluded_pages']) ? in_array($page->ID, $bswp_options['excluded_pages']) : false; ?>
                                                <option value="<?php echo $page->ID; ?>" <?php echo $selected ? 'selected="selected"' : ''; ?>><?php echo $page->post_title; ?></option>
                                            <?php } ?>
                                        </select>
                                        <label>Exclude Pages</label>
                                    </div>
                                    <?php foreach ($post_types as $key => $type) { ?>
                                    <div class="input-field col s12">
                                        <div style="display: none;"><?php echo maybe_serialize($type); ?></div>
                                        <select multiple name="bswp_options[excluded_custom_<?php echo $key; ?>][]">
                                            <option value="" disabled selected>Choose your <?php echo $type->label; ?></option>
                                            <?php $customs = get_posts(array(
                                                'post_type' => $key,
                                                'post_status' => 'publish',
                                                'numberposts' => -1
                                            )); ?>
                                            <?php foreach ($customs as $custom) { ?>
                                            <?php $selected = isset($bswp_options['excluded_custom_' . $key]) ? in_array($custom->ID, $bswp_options['excluded_custom_' . $key]) : false; ?>
                                                <option value="<?php echo $custom->ID; ?>" <?php echo $selected ? 'selected="selected"' : ''; ?>><?php echo $custom->post_title; ?></option>
                                            <?php } ?>
                                        </select>
                                        <label>Exclude <?php echo $type->label; ?></label>
                                    </div>
                                    <?php } ?>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col s12">
                                    <button class="btn waves-effect waves-light" type="submit">Save
                                        <i class="material-icons left">save</i>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div><!-- ./card -->
                <?php } ?>
            </div><!-- ./col -->
        </div><!-- ./row -->
    </div><!-- ./bswp-container -->
</div>