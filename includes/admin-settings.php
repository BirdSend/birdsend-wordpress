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
                        <?php if ($token = bswp_token()) { ?>
                        <span class="badge green lighten-2 white-text">Connected</span>
                        <?php } else { ?>
                        <span class="badge red lighten-2 white-text">Not Connected</span>
                        <?php } ?>
                        <span class="card-title"><strong>API Settings</strong></span>

                        <?php if ($token) { ?>
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
            </div><!-- ./col -->
        </div><!-- ./row -->
    </div><!-- ./bswp-container -->
</div>