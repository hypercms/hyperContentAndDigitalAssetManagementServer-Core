<?xml version="1.0" encoding="utf-8" ?>
<template>
<name>Configuration</name>
<user>admin</user>
<category>comp</category>
<extension>php</extension>
<application>php</application>
<content><![CDATA[[hyperCMS:objectview name='formedit']

[hyperCMS:textu id='title' height='30' label='Your Name' default='Your Name' onPublish='hidden']
[hyperCMS:textu id='slogan' height='30' label='Your Slogan' default='Your Slogan'  onPublish='hidden']
[hyperCMS:mediafile id='logo' mediatype='image' label='Your Logo' onPublish='hidden']

[hyperCMS:textu id='email' height='30' label='Your E-Mail' onPublish='hidden' constraint='isEmail']

[hyperCMS:textu id='companyname' height='30' label='Footer Name' default='Your Name' onPublish='hidden']
[hyperCMS:linkhref id='companylink' height='30' label='Footer Link' onPublish='hidden']

[hyperCMS:textu id='facebook' label='Facebook' height='30'  onPublish='hidden']
[hyperCMS:textu id='twitter' label='Twitter' height='30' onPublish='hidden']
[hyperCMS:textu id='googleplus' label='Google+' height='30' onPublish='hidden']
[hyperCMS:textu id='pinterest' label='Pinterest' height='30' onPublish='hidden']
[hyperCMS:textu id='linked' label='Linked' height='30' onPublish='hidden']
[hyperCMS:textu id='tumblr' label='Tumblr' height='30' onPublish='hidden']
[hyperCMS:textu id='vimeo' label='Vimeo' height='30' onPublish='hidden']
[hyperCMS:textu id='myspace' label='MySpace' height='30' onPublish='hidden']
[hyperCMS:textu id='flickr' label='Flickr' height='30' onPublish='hidden']
[hyperCMS:textu id='youtube' label='YouTube' height='30' onPublish='hidden']
[hyperCMS:textu id='rss' label='RSS' height='30' onPublish='hidden']

<?php 
$config = array();

// company
$config['company']['companyname'] =  "[hyperCMS:textu id='companyname' onEdit='hidden']";
$config['company']['companylink'] =  "[hyperCMS:linkhref id='companylink' onEdit='hidden']";

// logo
$config['logo']['title'] = "[hyperCMS:textu id='title' onEdit='hidden']";
$config['logo']['slogan'] = "[hyperCMS:textu id='slogan' onEdit='hidden']";
$config['logo']['logo'] = "[hyperCMS:mediafile id='logo' onEdit='hidden']";

// email
$config['contact']['email'] = "[hyperCMS:textu id='email' onEdit='hidden']";

// social profiles
$config['socialprofiles']['facebook'] = "[hyperCMS:textu id='facebook' onEdit='hidden']";
$config['socialprofiles']['twitter'] = "[hyperCMS:textu id='twitter' onEdit='hidden']";
$config['socialprofiles']['google-plus'] = "[hyperCMS:textu id='googleplus' onEdit='hidden']";
$config['socialprofiles']['pinterest'] = "[hyperCMS:textu id='pinterest' onEdit='hidden']";
$config['socialprofiles']['linked'] = "[hyperCMS:textu id='linked' onEdit='hidden']";
$config['socialprofiles']['tumblr'] = "[hyperCMS:textu id='tumblr' onEdit='hidden']";
$config['socialprofiles']['vimeo'] = "[hyperCMS:textu id='vimeo' onEdit='hidden']";
$config['socialprofiles']['my-space'] = "[hyperCMS:textu id='myspace' onEdit='hidden']";
$config['socialprofiles']['flickr'] = "[hyperCMS:textu id='flickr' onEdit='hidden']";
$config['socialprofiles']['you-tube'] = "[hyperCMS:textu id='youtube' onEdit='hidden']";
$config['socialprofiles']['rss'] = "[hyperCMS:textu id='rss' onEdit='hidden']";

?>]]></content>
</template>