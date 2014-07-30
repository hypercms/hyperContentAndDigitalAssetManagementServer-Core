<?xml version="1.0" encoding="utf-8" ?>
<template>
<name>SocialProfiles</name>
<user>admin</user>
<category>inc</category>
<extension></extension>
<application></application>
<content><![CDATA[<!-- Social Profiles -->
<?php if (!empty ($config['socialprofiles'])) { ?>
<div class="social-profiles clearfix">
  <ul>
  <?php 
  foreach ($config['socialprofiles'] as $key => $value)
  { 
    if(!empty($value))
    {
    ?>
    <li class="<?php echo $key; ?>">
      <a target="_blank" title="<?php echo $key; ?>" href="<?php echo $value; ?>"><?php echo $key; ?></a>
    </li>
    <?php 
    }
  }
  ?>
  </ul>
</div><!-- .social-profiles -->
<?php } ?>
<!-- Social Profiles -->]]></content>
</template>