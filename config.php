<?php
/* 
HonestRepair Diablo Engine  -  Configuration File
https://www.HonestRepair.net
https://github.com/zelon88

Licensed Under GNU GPLv3
https://www.gnu.org/licenses/gpl-3.0.html

Author: Justin Grimes
Date: 4/2/2019
<3 Open-Source

The Configuration File contains all of the critical settings required for Diablo to run on the server.
*/

// / ----------------------------------------------------------------------------------

// / Maintenance Mode
// / Set  $MaintenanceMode  to  TRUE  if you want to prevent execution of the Diablo engine.
$MaintenanceMode = FALSE;

// / Log verbosity.
// / Set  $Verbose  to  TRUE  if you want to log sucessful operations in addition to failed operations.
$Verbose = TRUE;

// / Update Sources.
// / A Git repo URL or .zip file URL containing a valid Diablo branch or codebase.
$UpdateSources = array('https://github.com/zelon88/Diablo-Engine/');

// / Security Salts.
// / Set AT LEAST 4 array elements to use for authenticating operations that require additional security. 
// / Add additional array elenents will be used where possible, but the first 4 are required. 
$Salts = array('fgdsfg!sdhafbde3i85_+#$@%<G345234381234120', '2lw12564165fgdasfsdf585&^e4f1e3djtjthfnb erfsdaf', 
 '><<>?#@$@%$f%^$#$#!$$#@!DFASF #$FERG#$F34f3F$42F34f$f4', '5683bnfrbnd7uh78r34hp9rh437r8g34378734ryh37489ryh347r9');

// / Library Definitions.
// / Libraries are treated as objects. They are defined in the following arrays.
// / Arrays are formatted as  $Libraries['LIBRARY_NAME', "ENABLED/DISABLED(bool)", '/path/to/library/directory']
// / Admins can add their own custom libraries by simply copy/pasting an existing entry and being cautious of the ending '('.
$Libraries = array(
 array('DATA', "TRUE", '/media/justin/Media/Media/Data'),
 array('MOVIES', "TRUE", '/media/justin/Media/Media/Movies'),
 array('MUSIC', "TRUE", '/media/justin/Media/Media/Music'),
 array('SHOWS', "TRUE", '/media/justin/Media/Media/Shows'),
 array('CHANNELS', "FALSE", '/media/justin/Media/Media/YouTube Subscriptions'),
 array('DRIVE', "FALSE", ''),
 array('STREAMS', "FALSE", ''),
 array('IMAGES', "FALSE", ''),
 array('DOCUMENTS', "FALSE", ''),
 array('CUSTOM-1', "FALSE", ''),
 array('CUSTOM-2', "FALSE", ''),
 array('CUSTOM-3', "FALSE", '') );

// / Super Admin Users.
// / Users are treated as objects. Users added here have global admin powers that cannot be changed via the GUI.
// / Users added through the GUI after initial setup are contained in the cache.
// / Arrays are formatted as  $Users['USER_ID', 'USER_NAME', 'USER_EMAIL', 'SHA-256_HASHED_PASSWORD', "ADMIN_YES/NO(bool)", "LAST_SESION_ID"]
$Users = array(
 array('1', 'zelon88', 'test@gmail.com', 'testpassword', "TRUE", ""),
 array('2', 'Nikki', 'test@gmail.com', 'password', "FALSE", "") 
 array('3', 'Leo', 'test@gmail.com', 'password', "FALSE", "") 
 array('4', 'Raph', 'test@gmail.com', 'password', "FALSE", "") 
 array('5', 'Mikey', 'test@gmail.com', 'password', "FALSE", "") 
 array('6', 'Donny', 'test@gmail.com', 'these-are-all-fake-passwords', "FALSE", "") );

// / Hash Config User Info.
// / If the passwords for the config.php users listed above are in plain-speech they will need to be hashed and stored in the cache
// / before they are valid. Set the  $HashConfigPasswords  to  TRUE  if the passwords above are plainspeech. Set to  FALSE  if they
// / are already hashed with SHA256.
$HashConfigUserinfo = TRUE;

// / Available Cores.
// / The following array specifies which corefiles are permitted to load within the platform. 
// / Cores not specified here will not be allowed to run using the loadCores() function.
$AvailableCores = array('SETTINGS', 'ADMIN', 'CLOUD', 'COMPATIBILITY', 'SANITIZE', 'APP', 'BACKUP', 'SECURITY', 'MEDIA', 'DATA');