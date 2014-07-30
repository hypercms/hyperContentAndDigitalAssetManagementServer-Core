<?xml version="1.0" encoding="utf-8" ?>
<template>
<name>Home</name>
<user>admin</user>
<category>page</category>
<extension>php</extension>
<application>php</application>
<content><![CDATA[[hyperCMS:textc id='NavigationHide' label='Hide in Navigation' value='yes' infotype='meta' onPublish='hidden']
[hyperCMS:textu id='NavigationSortOrder' label='Navigation Sort Order' constraint='inRange0:1000' infotype='meta' onPublish='hidden' height='25']
[hyperCMS:fileinclude file='%abs_comp%/%publication%/configuration.php']
<!DOCTYPE html>
<html>
  <head>
    <title>[hyperCMS:textu id='Title' infotype='meta' height='25' label='Page Title' constraint='R']</title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <meta name="description" content="[hyperCMS:pagedescription infotype='meta' height='50']" />
    <meta name="keywords" content="[hyperCMS:pagekeywords infotype='meta' height='50']" />
    <link rel="shortcut icon" href="%tplmedia%/favicon.ico"> 
    <!-- 57 x 57 Android and iPhone 3 icon -->
    <link rel="apple-touch-icon" media="screen and (resolution: 163dpi)" href="%tplmedia%/mobile_icon57.png" />
     <!-- 114 x 114 iPhone 4 icon -->
    <link rel="apple-touch-icon" media="screen and (resolution: 326dpi)" href="%tplmedia%/mobile_icon114.png" />
    <!-- 57 x 57 Nokia icon -->
    <link rel="shortcut icon" href="%tplmedia%/mobile_icon57.png" />
    <link rel="stylesheet" id="attitude_style-css" hypercms_href="%tplmedia%/style.css" type="text/css" media="all">
    <script type="text/javascript" src="%tplmedia%/jquery-1.11.1.min.js"></script>
    <script type="text/javascript" src="%tplmedia%/jquery-migrate-1.2.1.min.js"></script>
    <script type="text/javascript" src="%tplmedia%/backtotop.js"></script>
    <script type="text/javascript" src="%tplmedia%/tinynav.js"></script>
  </head>
  <body>
    <div class="wrapper">
      <header id="branding">
        [hyperCMS:tplinclude file='Header.inc.tpl']
        <nav id="access" class="clearfix" >
          <div class="container clearfix">
            [hyperCMS:tplinclude file='Navigation.inc.tpl']
          </div>
        </nav><!-- #access -->
        <section class="featured-slider">
          <div class="slider-cycle">
            [hyperCMS:scriptbegin 
            if (substr_count("[hyperCMS:mediafile id='slide_1' onEdit='hidden']", "Null_media.gif" )==0 || "%view%" == "cmsview") {
              if (substr_count("[hyperCMS:mediafile id='slide_1' onEdit='hidden']", "Null_media.gif" )==0)
                $slide = "[hyperCMS:mediafile id='slide_1' onEdit='hidden']";
              else
                $slide = "%tplmedia%/slide_placeholder.png";
            scriptend]
            <div class="slides">
              <figure>
                [hyperCMS:mediafile id='slide_1' mediatype='image' label='Picture for Slide 1' onPublish='hidden' ]
                <a href="[hyperCMS:linkhref id='slide_1_link' label='Link for Slide 1' ]" title="[hyperCMS:mediaalttext id='slide_1' label='Alttext for Slide 1']">
                  <img title="[hyperCMS:mediaalttext id='slide_1' label='Alttext for Slide 1' ]" alt="[hyperCMS:mediaalttext id='slide_1' label='Alttext for Slide 1']" width="1038" height="460" src="[hyperCMS:scriptbegin echo $slide; scriptend]"></img>
                </a>
              </figure>
              <article class="featured-text">
                [hyperCMS:scriptbegin $tmp_title = "[hyperCMS:textu id='slide_1_title' onEdit='hidden']"; if (!empty($tmp_title) || "%view%" == "cmsview") { scriptend]
                <div class="featured-title">
                  [hyperCMS:textu id='slide_1_title' height='25' label='Title for Slide 1']
                </div><!-- .featured-title -->
                [hyperCMS:scriptbegin } scriptend]
                [hyperCMS:scriptbegin $tmp_content = "[hyperCMS:textf id='slide_1_text' onEdit='hidden']"; if (!empty($tmp_content) || "%view%" == "cmsview") { scriptend]
                <div class="featured-content">
                  [hyperCMS:textf id='slide_1_text' label='Text for Slide 1']
                </div><!-- .featured-content -->
                [hyperCMS:scriptbegin } scriptend]
              </article><!-- .featured-text -->
            </div><!-- .slides -->
            [hyperCMS:scriptbegin } scriptend]
            [hyperCMS:scriptbegin 
            if (substr_count("[hyperCMS:mediafile id='slide_2' onEdit='hidden']", "Null_media.gif")==0 || "%view%" == "cmsview") {
              if (substr_count("[hyperCMS:mediafile id='slide_2' onEdit='hidden']", "Null_media.gif")==0)
                $slide = "[hyperCMS:mediafile id='slide_2' onEdit='hidden' ]";
              else
                $slide = "%tplmedia%/slide_placeholder.png";
            scriptend]
            <div class="slides">
              <figure>
                [hyperCMS:mediafile id='slide_2' mediatype='image' label='Picture for Slide 2' onPublish='hidden']
                <a href="[hyperCMS:linkhref id='slide_2_link' label='Link for Slide 2' ]" title="[hyperCMS:mediaalttext id='slide_2' label='Alttext for Slide 2']">
                  <img title="[hyperCMS:mediaalttext id='slide_2' label='Alttext Slide 2' ]" alt="[hyperCMS:mediaalttext id='slide_2' label='Alttext for Slide 2']" width="1038" height="460" src="[hyperCMS:scriptbegin echo $slide; scriptend]"></img>
                </a>
              </figure>
              <article class="featured-text">
                [hyperCMS:scriptbegin $tmp_title = "[hyperCMS:textu id='slide_2_title' onEdit='hidden']"; if (!empty($tmp_title) || "%view%" == "cmsview") { scriptend]
                <div class="featured-title">
                  [hyperCMS:textu id='slide_2_title' height='25' label='Title for Slide 2']
                </div><!-- .featured-title -->
                [hyperCMS:scriptbegin } scriptend]
                [hyperCMS:scriptbegin $tmp_content = "[hyperCMS:textf id='slide_2_text' onEdit='hidden']"; if (!empty($tmp_content) || "%view%" == "cmsview") { scriptend]
                <div class="featured-content">
                  [hyperCMS:textf id='slide_2_text' label='Text for Slide 2']
                </div><!-- .featured-content -->
                [hyperCMS:scriptbegin } scriptend]
              </article><!-- .featured-text -->
            </div><!-- .slides -->
            [hyperCMS:scriptbegin } scriptend]
            [hyperCMS:scriptbegin 
            if (substr_count("[hyperCMS:mediafile id='slide_3' onEdit='hidden']", "Null_media.gif")==0 || "%view%" == "cmsview" ) {
              if (substr_count("[hyperCMS:mediafile id='slide_3' onEdit='hidden']", "Null_media.gif")==0)
                $slide = "[hyperCMS:mediafile id='slide_3' onEdit='hidden' ]";
              else
                $slide = "%tplmedia%/slide_placeholder.png";
            scriptend]
            <div class="slides">
              <figure>
                [hyperCMS:mediafile id='slide_3' mediatype='image' label='Picture for Slide 3' onPublish='hidden']
                <a href="[hyperCMS:linkhref id='slide_3_link' label='Link for Slide 3' ]" title="[hyperCMS:mediaalttext id='slide_3' label='Alttext for Slide 3']">
                  <img title="[hyperCMS:mediaalttext id='slide_3' label='Alttext for Slide 3' ]" alt="[hyperCMS:mediaalttext id='slide_3' label='Alttext for Slide 3']" width="1038" height="460" src="[hyperCMS:scriptbegin echo $slide; scriptend]"></img>
                </a>
              </figure>
              <article class="featured-text">
                [hyperCMS:scriptbegin $tmp_title = "[hyperCMS:textu id='slide_3_title' onEdit='hidden']"; if (!empty($tmp_title) || "%view%" == "cmsview") { scriptend]
                <div class="featured-title">
                  [hyperCMS:textu id='slide_3_title' height='25' label='Title for Slide 3']
                </div><!-- .featured-title -->
                [hyperCMS:scriptbegin } scriptend]
                [hyperCMS:scriptbegin $tmp_content = "[hyperCMS:textf id='slide_3_text' onEdit='hidden']"; if (!empty($tmp_content) || "%view%" == "cmsview" ) { scriptend]
                <div class="featured-content">
                  [hyperCMS:textf id='slide_3_text' label='Text for Slide 3']
                </div><!-- .featured-content -->
                [hyperCMS:scriptbegin } scriptend]
              </article><!-- .featured-text -->
            </div><!-- .slides -->
            [hyperCMS:scriptbegin } scriptend]
            [hyperCMS:scriptbegin 
            if (substr_count("[hyperCMS:mediafile id='slide_4' onEdit='hidden']", "Null_media.gif")==0 || "%view%" == "cmsview") {
              if (substr_count("[hyperCMS:mediafile id='slide_4' onEdit='hidden']", "Null_media.gif")==0)
                $slide = "[hyperCMS:mediafile id='slide_4' onEdit='hidden']";
              else
                $slide = "%tplmedia%/slide_placeholder.png";
            scriptend]
            <div class="slides">
              <figure>
                [hyperCMS:mediafile id='slide_4' mediatype='image' label='Picture for Slide 4' onPublish='hidden']
                <a href="[hyperCMS:linkhref id='slide_4_link' label='Link for Slide 4']" title="[hyperCMS:mediaalttext id='slide_4' label='Alttext Slide 4']">
                  <img title="[hyperCMS:mediaalttext id='slide_4' label='Alttext Slide 4' ]" alt="[hyperCMS:mediaalttext id='slide_4' label='Alttext Slide 4']" width="1038" height="460" src="[hyperCMS:scriptbegin echo $slide; scriptend]"></img>
                </a>
              </figure>
              <article class="featured-text">
                [hyperCMS:scriptbegin $tmp_title = "[hyperCMS:textu id='slide_4_title' onEdit='hidden']"; if (!empty($tmp_title) || "%view%" == "cmsview") { scriptend]
                <div class="featured-title">
                  [hyperCMS:textu id='slide_4_title' height='25' label='Title for Slide 4']
                </div><!-- .featured-title -->
                [hyperCMS:scriptbegin } scriptend]
                [hyperCMS:scriptbegin $tmp_content = "[hyperCMS:textf id='slide_4_text' onEdit='hidden']"; if (!empty($tmp_content) || "%view%" == "cmsview" ) { scriptend]
                <div class="featured-content">
                  [hyperCMS:textf id='slide_4_text' label='Text for Slide 4']
                </div><!-- .featured-content -->
                [hyperCMS:scriptbegin } scriptend]
              </article><!-- .featured-text -->
            </div><!-- .slides -->
            [hyperCMS:scriptbegin } scriptend]
            [hyperCMS:scriptbegin 
            if (substr_count("[hyperCMS:mediafile id='slide_5' onEdit='hidden']", "Null_media.gif" )==0 || "%view%" == "cmsview") {
              if (substr_count("[hyperCMS:mediafile id='slide_5' onEdit='hidden']", "Null_media.gif" )==0)
                $slide = "[hyperCMS:mediafile id='slide_5' onEdit='hidden']";
              else
                $slide = "%tplmedia%/slide_placeholder.png";
            scriptend]
            <div class="slides">
              <figure>
                [hyperCMS:mediafile id='slide_5' mediatype='image' label='Picture for Slide 5' onPublish='hidden' ]
                <a href="[hyperCMS:linkhref id='slide_5_link' label='Link for Slide 5' ]" title="[hyperCMS:mediaalttext id='slide_5' label='Alttext for Slide 5']">
                  <img title="[hyperCMS:mediaalttext id='slide_5' label='Alttext for Slide 5']" alt="[hyperCMS:mediaalttext id='slide_5' label='Alttext for Slide 5']" width="1038" height="460" src="[hyperCMS:scriptbegin echo $slide; scriptend]"></img>
                </a>
              </figure>
              <article class="featured-text">
                [hyperCMS:scriptbegin $tmp_title = "[hyperCMS:textu id='slide_5_title' onEdit='hidden']"; if (!empty($tmp_title) || "%view%" == "cmsview") { scriptend]
                <div class="featured-title">
                  [hyperCMS:textu id='slide_5_title' height='25' label='Title for Slide 5']
                </div><!-- .featured-title -->
                [hyperCMS:scriptbegin } scriptend]
                [hyperCMS:scriptbegin $tmp_content = "[hyperCMS:textf id='slide_5_text' onEdit='hidden']"; if (!empty($tmp_content) || "%view%" == "cmsview" ) { scriptend]
                <div class="featured-content">
                  [hyperCMS:textf id='slide_5_text' label='Text for Slide 5']
                </div><!-- .featured-content -->
                [hyperCMS:scriptbegin } scriptend]
              </article><!-- .featured-text -->
            </div><!-- .slides -->
            [hyperCMS:scriptbegin } scriptend]
            
          </div>
          <nav id="controllers" class="clearfix">
          </nav><!-- #controllers -->
        </section><!-- .featured-slider -->
        [hyperCMS:scriptbegin $tmp = "[hyperCMS:textu id='SloganTitle' onEdit='hidden']"; if (!empty($tmp) || "%view%" == "cmsview" ) { scriptend]
        <section class="slogan-wrap clearfix">
          <div class="container">
            <div class="slogan">
              [hyperCMS:textu id='SloganTitle' label='Slogan Title' height='25']
              <span class="continuation">[hyperCMS:textf id='SloganText' label='Slogan Text']</span>
            </div><!-- .slogan -->
          </div><!-- .container -->
        </section><!-- .slogan-wrap -->
        [hyperCMS:scriptbegin } scriptend]
      </header>
      <div id="main" class="container clearfix">
        <section id="service_widgets" class="widget widget_service">
          <div class="column clearfix">
            [hyperCMS:scriptbegin $tmp = "[hyperCMS:textu id='article_1_title' onEdit='hidden']"; if (!empty($tmp) || "%view%" == "cmsview") { scriptend]
            <div class="one-third fixed-row-height">
              <div class="service-item clearfix">
                <h3 class="service-title">[hyperCMS:textu id='article_1_title' label='Title for Article 1'  height='25']</h3>
              </div><!-- .service-item -->
              <article>
                <p>
                  [hyperCMS:textf id='article_1_text' label='Text for Article 1']
                </p>
              </article>
              [hyperCMS:scriptbegin $tmp = "[hyperCMS:linktext id='article_1_link' onEdit='hidden']"; if (!empty($tmp) || "%view%" == "cmsview") { scriptend]
              <a class="more-link" title="[hyperCMS:linktext id='article_1_link' label='Link-Text for Article 1']" href="[hyperCMS:linkhref id='article_1_link' label='Link for Article 1']">Read more</a>
              [hyperCMS:scriptbegin } scriptend]
            </div>
            [hyperCMS:scriptbegin } scriptend]
            [hyperCMS:scriptbegin $tmp = "[hyperCMS:textu id='article_2_title' onEdit='hidden']"; if (!empty($tmp) || "%view%" == "cmsview") { scriptend]
            <div class="one-third fixed-row-height">
              <div class="service-item clearfix">
                <h3 class="service-title">[hyperCMS:textu id='article_2_title' label='Title for Article 2'  height='25']</h3>
              </div><!-- .service-item -->
              <article>
                <p>
                  [hyperCMS:textf id='article_2_text' label='Text for Article 2']
                </p>
              </article>
              [hyperCMS:scriptbegin $tmp = "[hyperCMS:linktext id='article_2_link' onEdit='hidden']"; if (!empty($tmp) || "%view%" == "cmsview") { scriptend]
              <a class="more-link" title="[hyperCMS:linktext id='article_2_link' label='Link-Text for Article 2']" href="[hyperCMS:linkhref id='article_2_link' label='Link for Article 2']">Read more</a>
              [hyperCMS:scriptbegin } scriptend]
            </div>
            [hyperCMS:scriptbegin } scriptend]
            [hyperCMS:scriptbegin $tmp = "[hyperCMS:textu id='article_3_title' onEdit='hidden']"; if (!empty($tmp) || "%view%" == "cmsview") { scriptend]
            <div class="one-third fixed-row-height">
              <div class="service-item clearfix">
                <h3 class="service-title">[hyperCMS:textu id='article_3_title' label='Title for Article 3' height='25']</h3>
              </div><!-- .service-item -->
              <article>
                <p>
                  [hyperCMS:textf id='article_3_text' label='Text for Article 3']
                </p>
              </article>
              [hyperCMS:scriptbegin $tmp = "[hyperCMS:linktext id='article_3_link' onEdit='hidden']"; if (!empty($tmp) || "%view%" == "cmsview") { scriptend]
              <a class="more-link" title="[hyperCMS:linktext id='article_3_link' label='Link-Text for Article 3']" href="[hyperCMS:linkhref id='article_3_link' label='Link for Article 3']">Read more</a>
              [hyperCMS:scriptbegin } scriptend]
            </div>
            [hyperCMS:scriptbegin } scriptend]
            [hyperCMS:scriptbegin $tmp = "[hyperCMS:textu id='article_4_title' onEdit='hidden']"; if (!empty($tmp) || "%view%" == "cmsview") { scriptend]
            <div class="one-third fixed-row-height">
              <div class="service-item clearfix">
                <h3 class="service-title">[hyperCMS:textu id='article_4_title' label='Title for Article 4'  height='25']</h3>
              </div><!-- .service-item -->
              <article>
                <p>
                  [hyperCMS:textf id='article_4_text' label='Text article 4']
                </p>
              </article>
              [hyperCMS:scriptbegin $tmp = "[hyperCMS:linktext id='article_4_link' onEdit='hidden']"; if (!empty($tmp) || "%view%" == "cmsview") { scriptend]
              <a class="more-link" title="[hyperCMS:linktext id='article_4_link' label='Link-Text for Article 4']" href="[hyperCMS:linkhref id='article_4_link' label='Link for Article 4']">Read more</a>
              [hyperCMS:scriptbegin } scriptend]
            </div>
            [hyperCMS:scriptbegin } scriptend]
            [hyperCMS:scriptbegin $tmp = "[hyperCMS:textu id='article_5_title' onEdit='hidden']"; if (!empty($tmp) || "%view%" == "cmsview") { scriptend]
            <div class="one-third fixed-row-height">
              <div class="service-item clearfix">
                <h3 class="service-title">[hyperCMS:textu id='article_5_title' label='Title for Article 5' height='25']</h3>
              </div><!-- .service-item -->
              <article>
                <p>
                  [hyperCMS:textf id='article_5_text' label='Text article 5']
                </p>
              </article>
              [hyperCMS:scriptbegin $tmp = "[hyperCMS:linktext id='article_5_link' onEdit='hidden']"; if (!empty($tmp) || "%view%" == "cmsview" ) { scriptend]
              <a class="more-link" title="[hyperCMS:linktext id='article_5_link' label='Link-Text for Article 5']" href="[hyperCMS:linkhref id='article_5_link' label='Link for Article 5']">Read more</a>
              [hyperCMS:scriptbegin } scriptend]
            </div>
            [hyperCMS:scriptbegin } scriptend]
            [hyperCMS:scriptbegin $tmp = "[hyperCMS:textu id='article_6_title' onEdit='hidden']"; if (!empty($tmp) || "%view%" == "cmsview") { scriptend]
            <div class="one-third fixed-row-height">
              <div class="service-item clearfix">
                <h3 class="service-title">[hyperCMS:textu id='article_6_title' label='Title for Article 6' height='25']</h3>
              </div><!-- .service-item -->
              <article>
                <p>
                  [hyperCMS:textf id='article_6_text' label='Text article 6']
                </p>
              </article>
              [hyperCMS:scriptbegin $tmp = "[hyperCMS:linktext id='article_6_link' onEdit='hidden']"; if (!empty($tmp) || "%view%" == "cmsview") { scriptend]
              <a class="more-link" title="[hyperCMS:linktext id='article_6_link' label='Link-Text for Article 6']" href="[hyperCMS:linkhref id='article_6_link' label='Link for Article 6']">Read more</a>
              [hyperCMS:scriptbegin } scriptend]
            </div>
            [hyperCMS:scriptbegin } scriptend]
          </div><!-- .column -->
        </section><!-- #service_widgets -->
      </div><!-- #main -->
      [hyperCMS:tplinclude file='Footer.inc.tpl']
    </div><!-- .wrapper -->
    <script type="text/javascript" src="%tplmedia%/jquery.cycle.all.min.js"></script>
    <script type="text/javascript">
      /* &lt;![CDATA[ */
      var slider_value = {"transition_effect":"fade","transition_delay":"4000","transition_duration":"3000"};
      /* ]]&gt; */
    </script>
    <script type="text/javascript" src="%tplmedia%/slider-setting.js"></script>
  </body>
</html>]]></content>
</template>