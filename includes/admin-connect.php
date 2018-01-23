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
                        <form action="" method="POST">
                            <div style="display: none;">
                                <input type="hidden" name="bswp_submit" value="connect">
                            </div>
                            <div class="row">
                                <div class="input-field col s6">
                                    <input type="text" id="bswp_email" name="bswp_email" class="validate" required data-lpignore="true">
                                    <label for="bswp_email">Your BirdSend Email</label>
                                </div>
                                <div class="input-field col s6">
                                    <input type="password" id="bswp_password" name="bswp_password" class="validate" required data-lpignore="true">
                                    <label for="bswp_password">Your BirdSend Password</label>
                                </div>
                                <div class="col s12">
                                    <button class="btn waves-effect waves-light" type="submit">Connect Now
                                        <i class="material-icons left">sync</i>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div><!-- ./card -->
            </div><!-- ./col -->
        </div><!-- ./row -->
    </div><!-- ./bswp-container -->
</div>