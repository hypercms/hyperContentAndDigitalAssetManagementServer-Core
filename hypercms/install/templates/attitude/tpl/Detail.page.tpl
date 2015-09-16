<?xml version="1.0" encoding="utf-8" ?>
<template>
<name>Detail</name>
<user>admin</user>
<category>page</category>
<extension>xhtml</extension>
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
    <meta name="description" content="[hyperCMS:textu id='Description' infotype='meta' height='50']" />
    <meta name="keywords" content="[hyperCMS:textu id='Keywords' infotype='meta' height='50']" />
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
  <body class="no-sidebar-template">
    <div class="wrapper">
      <header id="branding">
        [hyperCMS:tplinclude file='Header.inc.tpl']
        <nav id="access" class="clearfix" >
          <div class="container clearfix">
            [hyperCMS:tplinclude file='Navigation.inc.tpl']
          </div>
        </nav><!-- #access -->
        <div class="page-title-wrap">
          <div class="container clearfix">
            [hyperCMS:tplinclude file='Breadcrumb.inc.tpl']			   
             <h3 class="page-title">[hyperCMS:textu id='Title' height='25' label='Page Title' constraint='R']</h3><!-- .page-title -->
          </div>
        </div>
      </header>
      <div id="main" class="container clearfix">
        <div id="container">
          <div id="content">
            <article>
              <header class="entry-header">
                <h2 class="entry-title">
                  [hyperCMS:textu id='EntryTitle' label='Entry Title' height='25']
                </h2><!-- .entry-title -->
              </header>
              [hyperCMS:scriptbegin 
              if (substr_count("[hyperCMS:mediafile id='FeaturedImage' onEdit='hidden']", "Null_media.gif" )==0 || "%view%" != "publish" ) {
              scriptend]
              <figure class="post-featured-image">
                <img src="[hyperCMS:mediafile id='FeaturedImage' mediatype='image' label='Featured Image']" alt="[hyperCMS:mediaalttext id='FeaturedImage']" title="[hyperCMS:mediaalttext id='FeaturedImage']"></img>
              </figure>  
              [hyperCMS:scriptbegin } scriptend]              
              <div class="entry-content clearfix">
                [hyperCMS:textf id='EntryContent' label='Content']
              </div>   
            </article>
          </div>
        </div>
      </div><!-- #main -->
      [hyperCMS:tplinclude file='Footer.inc.tpl']
    </div><!-- .wrapper -->
  </body>
</html>]]></content>
</template>