<?php
$tempfile_patterns = array (
  '/^.*\.recycle$/',    // object in recycle bin
  '/^__MACOSX$/',    // OS/X folder
  '/^\._(.*)$/',     // OS/X resource forks
  '/^.DS_Store$/',   // OS/X custom folder settings
  '/^\.(.*)-Spotlight$/', // OS/X Spotlight files
  '/^desktop.ini$/', // Windows custom folder settings
  '/^Thumbs.db$/',   // Windows thumbnail cache
  '/^.(.*).swp$/',   // ViM temporary files
  '/^\.dat(.*)$/',   // Smultron seems to create these
  '/^~lock.(.*)#$/', // Windows 7 lockfiles
);        
?>
