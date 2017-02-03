<?php
/**
 * UMW Directory API - Full Bio Employee Template
 */
?>
<header>
	<h2 class="employee-name"><?php echo $employee->{'first-name'} ?> <?php echo $employee->{'last-name'} ?></h2>
	<?php echo $employee->photo ?>
	<section class="employee-meta">
		<h3 class="employee-title"><?php echo $employee->{'job-title'} ?></h3>
<?php
	if ( ! empty( $employee->departments ) ) {
?>
		<ul class="department-list">
<?php
		foreach ( $employee->departments as $dept ) {
?>
			<li class="department-name"><?php echo $dept->name ?></li>
<?php
		}
?>
		</ul>
<?php
	}
?>
	</section>
</header>
<div class="employee-contact">
 <span class="dashicons dashicons-building"></span> <?php echo $employee->building->name ?> <?php echo $employee->room ?>
 <span class="dashicons dashicons-email-alt"></span> <a href="mailto:<?php echo $employee->email ?>"><?php echo $employee->email ?></a>
<?php
	if ( ! empty( $employee->phone ) ) {
?>
  <span class="dashicons dashicons-phone"></span> <?php echo UMW_Directory_API_Templates\Base_Template::do_tel_link( array( 'title' => '%1$s %2$s' ), $employee->phone ) ?>
<?php
	}
	$links = array();
	if ( ! empty( $employee->website ) ) {
		$links['website'] = sprintf( '<a href="%1$s" class="genericon genericon-link"><span class="hidden">%2$s %3$s\'s website</span></a>', $employee->website, $employee->{'first-name'}, $employee->{'last-name'} );
	}
	$social = array(
		'facebook'  => 'facebook', 
		'twitter'   => 'twitter', 
		'instagram' => 'instagram', 
		'linkedin'  => 'linkedin', 
		'vimeo'     => 'vimeo', 
		'youtube'   => 'youtube', 
		'tumblr'    => 'tumblr', 
		'google-plus' => 'googleplus', 
	);
	foreach ( $social as $net => $icon ) {
		if ( ! empty( $employee->$net ) ) {
			$links[$net] = sprintf( '<a href="%1$s" class="genericon genericon-%4$s"><span class="hidden">%2$s %3$s on %5$s</span></a>', $employee->$net, $employee->{'first-name'}, $employee->{'last-name'}, $icon, ucfirst( $net ) );
		}
	}
	if ( ! empty( $employee->academia ) ) {
		$links['academia'] = sprintf( '<a id="academia-button" href="[types field='academia' output='raw'][/types]">Follow me on Academia.edu</a><script src="//a.academia-assets.com/javascripts/social.js"></script>' );
	}
?>
</div>
<div class="employee-academic">
	<h3>Academic Degrees</h3>
	<div class="academic-degrees">
		<?php echo $employee->degrees ?>
	</div>
</div>
<?php 
	if ( ! empty( $employee->biography ) ) { 
?>
<div class="employee-biography">
	<?php echo $employee->biography ?>
</div>
<?php 
	}
	
	$news_items = UMW_Directory_API_Templates\Base_Template::get_feeds( array( sprintf( 'http://eagleeye.umw.edu/tag/%s/feed/', $employee->username ), sprintf( 'http://www.umw.edu/news/tag/%s/feed/', $employee->username ) ), 5 );
	if ( ! empty( $news_items ) ) {
		// Output the news entries
?>
<div class="employee-in-the-news">
	<h3><?php echo $employee->{'first-name'} ?> <?php echo $employee->{'last-name'} ?> in the News</h3>
<?php
		foreach ( $news_items as $news ) {
?>
	<div class="employee-news-entry">
	</div>
<?php
		}
?>
  	<a id="userMoreNews" href="http://eagleeye.umw.edu/tag/[types field="username"][/types]/">
  		Read more about [types field="first-name"][/types] [types field="last-name"][/types]
  	</a>.
</div>
<?php
	}
?>