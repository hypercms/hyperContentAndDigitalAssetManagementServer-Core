<?php
$tempfile_patterns = array (
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
