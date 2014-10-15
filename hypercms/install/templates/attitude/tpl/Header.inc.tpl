<?xml version="1.0" encoding="utf-8" ?>
<template>
<name>Header</name>
<user>admin</user>
<category>inc</category>
<extension></extension>
<application></application>
<content><![CDATA[<div class="container clearfix">
					<div class="hgroup-wrap clearfix">
						<section class="hgroup-right">
							[hyperCMS:tplinclude file='SocialProfiles.inc.tpl']
							<form class="searchform clearfix" method="post" action="%url_page%/search.php">
								<input class="s field" type="text" name="s" placeholder="Search">
							</form><!-- .searchform -->
						</section><!-- .hggroup-right -->
						<hgroup id="site-logo" class="clearfix">
							<?php if (substr_count ($config['logo']['logo'], "Null_media.gif") == 0) { ?>
							<a rel="home" title="home" href="%url_page%"><img src="<?php echo $config['logo']['logo']; ?>" alt="Logo"></img></a>
							<?php } elseif (!empty ($config['logo']['title'])) { ?>
							<h1 id="site-title"> 
								<a href="%url_page%/" title="<?php echo $config['logo']['title']; ?>" rel="home"><?php echo $config['logo']['title']; ?></a>
							</h1>
							<h2 id="site-description"><?php if (!empty ($config['logo']['slogan'])) echo $config['logo']['slogan']; ?></h2>
							<?php } ?>
						</hgroup><!-- #site-logo -->
					</div><!-- .hgroup-wrap -->
				</div><!-- .container -->]]></content>
</template>