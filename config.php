<?php
/* 
HonestRepair Diablo Engine  -  Configuration File
https://www.HonestRepair.net
https://github.com/zelon88

Licensed Under GNU GPLv3
https://www.gnu.org/licenses/gpl-3.0.html

Author: Justin Grimes
Date: 4/18/2019
<3 Open-Source

The Configuration File contains all of the critical settings required for Diablo to run on the server.
*/

// / ----------------------------------------------------------------------------------

// / Application Name
// / Set  $ApplicationName  to a string that represents the name of this application.
$ApplicationName = 'HRCloud3';

// / Maintenance Mode
// / Set  $MaintenanceMode  to  TRUE  if you want to prevent execution of the Diablo engine.
$MaintenanceMode = FALSE;

// / Log verbosity.
// / Set  $Verbose  to  TRUE  if you want to log sucessful operations in addition to failed operations.
$Verbose = TRUE;

// / Update Sources.
// / A Git repo URL or .zip file URL containing a valid Diablo branch or codebase.
$UpdateSources = array('https://github.com/zelon88/Diablo-Engine/');

// / User Data Compression.
// / Set $DataCompression to TRUE if you want to compress all user files with the xPress compression algorithm by zelon88.
// / For more information about xPress Compression by zelon88 visit https://www.github.com/zelon88/xPress
// / If enabled you must also specify and enable a DATA library in the $Libraries section of this config file.
// / Expect the DATA library to potentially make up 75% of user space requirements (many terabytes).
$DataCompression = TRUE;

// / User Data Encryption.
// / Set $DataEncryption to TRUE if you want to encrypt all user files with user-held and controlled keys.
// / If enabled you must also specify and enable a DATA library in the $Libraries section of this config file.
// / The keys are stored in the users DATA library and can be used to decrypt the files for automated scans.
// / Do not market this to people under the guise that it keeps employees from viewing uploaded data. 
// / It cannot stop that, but it does make it inconvinient or impossible to do manually depending on local permissions. 
// / The keys are stored in whatever directory is set as the DATA $Library. Anyone with read access to this directory
// / will be able to read the keys. Keep in mind that the $Libraries can also be located on remote network shares,
// / storage devices, or removable devices like USB sticks.
$DataEncryption = TRUE;

// / Security Salts.
// / Set AT LEAST 4 array elements to use for authenticating operations that require additional security. 
// / Add additional array elenents will be used where possible, but the first 4 are required. 
$Salts = array('fgdsfg!sdhafbde3i85_+#$@%<G345234381234120', '2lw12564165fgdasfsdf585&^e4f1e3djtjthfnb erfsdaf', 
 '><<>?#@$@%$f%^$#$#!$$#@!DFASF #$FERG#$F34f3F$42F34f$f4', '5683bnfrbnd7uh78r34hp9rh437r8g34378734ryh37489ryh347r9');

// / Library Definitions.
// / Libraries are treated as objects. They are defined in the following arrays.
// / Arrays are formatted as  $Libraries['LIBRARY_NAME', "ENABLED/DISABLED(bool)", '/path/to/library/directory']
// / Admins can add their own custom libraries by simply copy/pasting an existing entry and being cautious of the ending '('.
// / The "DATA" library is used for xPress compression dictionaries and user-supplied encryption keys.
// / It is reccomended that you limit the permissions on the DATA $Library directory so that nobody but the Apache/Nginx user
// / has read access.
$Libraries = array(
 array('DATA', "TRUE", '/mnt/abcdefgh-1234-1234-1234-32abwewdawdasdsdfsadfsda56511651/CloudTestDATA'),
 array('MOVIES', "TRUE", '/mnt/abcdefgh-1234-1234-1234-32abwewdawdasdsdfsadfsda56511651/CloudTestDATA'),
 array('MUSIC', "TRUE", '/mnt/abcdefgh-1234-1234-8360-32abwewdawdasdsdfsadfsda56511651/CloudTestDATA'),
 array('SHOWS', "TRUE", '/mnt/abcdefgh-1234-1234-8360-32abwewdawdasdsdfsadfsda56511651/CloudTestDATA'),
 array('CHANNELS', "FALSE", '/mnt/abcdefgh-1234-1234-1234-32abwewdawdasdsdfsadfsda56511651/CloudTestDATA'),
 array('DRIVE', "TRUE", ''),
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
 array('1', 'zelon88', 'test@gmail.com', '5e884898da28047151d0e56f8dc6292773603d0d6aabbdd62a11ef721d1542d8', "TRUE", ""), // Default Passwords are all 'password'
 array('2', 'Nikki', 'test@gmail.com', '5e884898da28047151d0e56f8dc6292773603d0d6aabbdd62a11ef721d1542d8', "FALSE", ""), 
 array('3', 'Leo', 'test@gmail.com', '5e884898da28047151d0e56f8dc6292773603d0d6aabbdd62a11ef721d1542d8', "FALSE", ""), 
 array('4', 'Raph', 'test@gmail.com', '5e884898da28047151d0e56f8dc6292773603d0d6aabbdd62a11ef721d1542d8', "FALSE", ""), 
 array('5', 'Mikey', 'test@gmail.com', '5e884898da28047151d0e56f8dc6292773603d0d6aabbdd62a11ef721d1542d8', "FALSE", ""), 
 array('6', 'Donny', 'test@gmail.com', '5e884898da28047151d0e56f8dc6292773603d0d6aabbdd62a11ef721d1542d8', "FALSE", ""),
 array('7', 'test', 'test@gmail.com', '5e884898da28047151d0e56f8dc6292773603d0d6aabbdd62a11ef721d1542d8', "FALSE", "") );

// / Available Cores.
// / The following array specifies which corefiles are permitted to load within the platform. 
// / Cores not specified here will not be allowed to run using the loadCores() function.
$AvailableCores = array('SETTINGS', 'ADMIN', 'CLOUD', 'COMPATIBILITY', 'SANITIZE', 'APP', 'BACKUP', 'SECURITY', 'MEDIA', 'DATA');