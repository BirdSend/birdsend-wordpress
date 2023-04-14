<header>
	<nav class="grey lighten-5">
		<div class="nav-wrapper">
			<a href="https://birdsend.co" target="_blank" class="brand-logo center"><img src="<?php echo BSWP_IMG; ?>logo.svg" /></a>
			
			<ul id="nav-mobile" class="left hide-on-med-and-down">
				<li class="<?php echo ! $_GET[ 'action' ] ? 'active' : ''; ?>"><a class="grey-text text-darken-3" href="<?php echo admin_url( 'admin.php?page=bswp-settings' ); ?>"><i class="material-icons left">home</i> Home</a></li>
				<li class="<?php echo $_GET[ 'action' ] == 'forms' ? 'active' : ''; ?>"><a class="grey-text text-darken-3" href="<?php echo admin_url( 'admin.php?page=bswp-settings&action=forms' ); ?>"><i class="material-icons left">library_books</i> Forms</a></li>
			</ul>
		</div>
	</nav>
</header>