<?xml version="1.0" encoding="utf-8" ?>
<template>
<name>Footer</name>
<user>admin</user>
<category>inc</category>
<extension></extension>
<application></application>
<content><![CDATA[<!-- Footer -->
<footer id="colophon" class="clearfix">
  <div id="site-generator">
    <div class="container">
      [hyperCMS:tplinclude file='SocialProfiles.inc.tpl']
      <div class="copyright">
        Copyright  © <?php echo date("Y"); ?>
        <?php if(!empty($config['company']['companylink']) ) { ?>
        <a title="<?php echo $config['company']['companyname']; ?>" href="<?php echo $config['company']['companylink']; ?>">
        <?php } ?>
        <?php if(!empty($config['company']['companyname']) ) echo "<span>".$config['company']['companyname']."</span>"; ?>
        <?php if(!empty($config['company']['companylink']) ) { ?>
        </a>
        <?php } ?>
      </div>
      <div style="clear:both;"></div>
    </div>
  </div>
  <div class="back-to-top">
    <a href="#branding">Back to Top</a>
  </div>
</footer>
<!-- Footer -->]]></content>
</template>