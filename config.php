<?php
/* 
HonestRepair Diablo Engine  -  Configuration File
https://www.HonestRepair.net
https://github.com/zelon88

Licensed Under GNU GPLv3
https://www.gnu.org/licenses/gpl-3.0.html

Author: Justin Grimes
Date: 3/12/2021
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
// / Set  $Verbose  to  FALSE  to only log errors.
$Verbose = TRUE;

// / Update Sources.
// / Set  $UpdateSources  to a Git repo URL or .zip file URL containing a valid Diablo branch or codebase.
$UpdateSources = array('https://github.com/zelon88/Diablo-Engine/');

// / User Data Compression.
// / Set  $DataCompression  to  TRUE  if you want to compress all user files with the xPress compression algorithm by zelon88.
// / For more information about xPress Compression by zelon88 visit https://www.github.com/zelon88/xPress
// / If enabled you must also specify and enable a DATA library in the $Libraries section of this config file.
// / Expect the DATA library to potentially make up 75% of user space requirements (many terabytes).
// / Set  $DataCompression  to  FALSE  to disable all internal data compression operations.
// / Disabling this will not disable GZIP HTTP compression.
$DataCompression = TRUE;

// / User Data Encryption.
// / Set  $DataEncryption  to  TRUE  if you want to encrypt all user files with user-held and controlled keys.
// / If enabled you must also specify and enable a DATA library in the $Libraries section of this config file.
// / The keys are stored in the users DATA library and can be used to decrypt the files for automated scans.
// / Do not market this to people under the guise that it keeps employees from viewing uploaded data. 
// / It cannot stop that, but it does make it inconvinient or impossible to do manually depending on local permissions. 
// / The keys are stored in whatever directory is set as the DATA $Library. Anyone with read access to this directory
// / will be able to read the keys. Keep in mind that the $Libraries can also be located on remote network shares,
// / storage devices, or removable devices like USB sticks.
// / Set  $DataEncryption  to  FALSE  to disable all internal data encryption operations.
// / Disabling this will not disable authentication encryption or HTTPS encryption.
$DataEncryption = TRUE;

// / Dangerous File Definitions
// / Set  $DangerousFiles  to an array with each element containing a string of a valid file extension.
// / During sensitive filesystem scans & file operations files with extensions that match this list will be skipped.
// / Do not include the . character in the file extension.
$DangerousFiles = array('js', 'php', 'sh', 'exe', 'dll', 'ps1', 'vbs', 'hta', 'py', 'pl', 'flv', 'jar');

// / Security Salts.
// / Set AT LEAST 4 array elements to use for authenticating operations that require additional security. 
// / Add additional array elenents will be used where possible, but the first 4 are required. 
$Salts = array('fgdsfg!sdhafbde3i85_+#$@%<G345234381234120', '2lw12564165fgdasfsdf585&^e4f1e3djtjthfnb erfsdaf', 
 '><<>?#@$@%$f%^$#$#!$$#@!DFASF #$FERG#$F34f3F$42F34f$f4', '5683bnfrbnd7uh78r34hp9rh437r8g34378734ryh37489ryh347r9');

// / Default Theme
// / Set  $DefaultTheme  to the default Theme to use.
// / Users can set their own, but this one will be used as default.
// / GUIs are stored in /Resources/Themes. Each Theme has its own subdirectory in this folder.
// / Only administrators can install new Themes, but users can select their own from the ones that are already installed.
$DefaultTheme = 'DEFAULT';

// / Default Timezone
// / Set  $DefaultTimezone  to the default timezone to use.
// / Users can set their own, but this one will be used as default.
// / Uses PHP timezone IDs.
// / See https://www.php.net/manual/en/timezones.php for more information.
$DefaultTimezone = 'America/New_York';

// / Default Color Sheme
// / Set  $DefaultColorScheme  to the default color scheme to use.
// / Users can set their own, but this one will be used as default.
$DefaultColorScheme = 'BLUE';

// / Default Font
// / Set  $DefaultFont  to the default font to use.
// / Users can set their own, but this one will be used as default.
// / Uses client system fonts. 
// / If a client does not have the specified font installed the default local system font is used instead. 
$DefaultFont = 'ARIAL';

// / Default Display Name
// / Set  $DefaultDisplayName  to the default display name to use.
// / Users can set their own, but this one will be used as default.
// / This is what interative GUI elements like HRAI will refer to the user as by default.
$DefaultDisplayName = 'Commander';

// / Default Tips
// / Determines whether tips are displayed to the user by default.
// / Set  $DefaultTips  to  ENABLED  to display random tips to the user by default.
// / Set  $DefaultTips  to  DISABLED  to disable tips on all pages by default.
$DefaultTips = 'ENABLED';

// / Default HRAI
// / Determines whether HRAI is displayed to the user by default.
// / Set  $DefaultHRAI  to  ENABLED  to display HRAI to the user by default.
// / Set  $DefaultHRAI  to  DISABLED  to disable HRAI on all pages by default.
$DefaultHRAI = 'ENABLED';

// / Default HRAI Audio
// / Determines whether HRAI is displayed to the user by default.
// / Set  $DefaultHRAIAudio  to  ENABLED  to allow HRAI to use the client's audio device by default.
// / Set  $DefaultHRAIAudio  to  DISABLED  to prevent HRAI from using the client's audio device on all pages by default.
$DefaultHRAIAudio = 'ENABLED';

// / Default Landing Page
// / Sets which GUI element the user will be redirected to upon basic authentication, by default.
// / Set  $DefaultLandingPage  to  DEFAULT  to allow the selected theme to decide, by default.
// / See the documentation included with your theme for more information and acceptable values.
$DefaultLandingPage = 'DEFAULT';

// / Default Stay Logged In
// / Determines whether the user session will be continued automatically by default.
// / Set  $DefaultStayLoggedIn  to  ENABLED  to continue beyond 2 minutes without requiring reauthentication, by default.
// / Set  $DefaultStayLoggedIn  to  DISABLED  to require that users reauthenticate approximately every 2 minutes by default.
$DefaultStayLoggedIn = 'ENABLED';

// / Library Definitions.
// / Libraries are treated as objects. They are defined in the following arrays.
// / Do not forget to add the trailing slash. Trailing slash is required.
// / Admins can add their own custom libraries by simply copy/pasting an existing entry and being cautious of the ending '('.
// / The "DATA" library is used for storing private account related user data & metadata
// / Set $LibrariesDefault to an array containing all of the default libraries included with this version of HRCloud3.
$LibrariesDefault = array('DATA', 'MOVIES', 'MUSIC', 'SHOWS', 'CHANNELS', 'DRIVE', 'STREAMS', 'IMAGES', 'DOCUMENTS'); 
// / Set $Libraries to an array of arrays.
// / Arrays are formatted as  $Libraries['LIBRARY_NAME', ENABLED/DISABLED(bool), '/path/to/library/directory/']
$Libraries = array(
 array('DATA', TRUE, '/home/justin/Documents/Projects/DATA/DATA/'),
 array('MOVIES', TRUE, '/home/justin/Documents/Projects/DATA/MOVIES/'),
 array('MUSIC', TRUE, '/home/justin/Documents/Projects/DATA/MUSIC/'),
 array('SHOWS', TRUE, '/home/justin/Documents/Projects/DATA/SHOWS/'),
 array('CHANNELS', FALSE, '/home/justin/Documents/Projects/DATA/CHANNELS/'),
 array('DRIVE', TRUE, '/home/justin/Documents/Projects/DATA/DRIVE/'),
 array('STREAMS', FALSE, ''),
 array('IMAGES', FALSE, ''),
 array('DOCUMENTS', FALSE, ''),
 array('CUSTOM-1', FALSE, ''),
 array('CUSTOM-2', FALSE, ''),
 array('CUSTOM-3', FALSE, '') );

// / Super Admin Users.
// / Users are treated as objects. Users added here have global admin powers that cannot be changed via the GUI.
// / Users added through the GUI after initial setup are contained in the cache.
// / Arrays are formatted as  $Users['USER_ID', 'USER_NAME', 'USER_EMAIL', 'SHA-256_HASHED_PASSWORD', "ADMIN_YES/NO(bool)", "LAST_SESION_ID"]
$Users = array(
 array('1', 'zelon88', 'test@gmail.com', '5e884898da28047151d0e56f8dc6292773603d0d6aabbdd62a11ef721d1542d8', 'TRUE', ""), // Default Passwords are all 'password'
 array('2', 'Nikki', 'test@gmail.com', '5e884898da28047151d0e56f8dc6292773603d0d6aabbdd62a11ef721d1542d8', 'FALSE', ""), 
 array('3', 'Leo', 'test@gmail.com', '5e884898da28047151d0e56f8dc6292773603d0d6aabbdd62a11ef721d1542d8', 'FALSE', ""), 
 array('4', 'Raph', 'test@gmail.com', '5e884898da28047151d0e56f8dc6292773603d0d6aabbdd62a11ef721d1542d8', 'FALSE', ""), 
 array('5', 'Mikey', 'test@gmail.com', '5e884898da28047151d0e56f8dc6292773603d0d6aabbdd62a11ef721d1542d8', 'FALSE', ""), 
 array('6', 'Donny', 'test@gmail.com', '5e884898da28047151d0e56f8dc6292773603d0d6aabbdd62a11ef721d1542d8', 'FALSE', ""),
 array('7', 'test', 'test@gmail.com', '5e884898da28047151d0e56f8dc6292773603d0d6aabbdd62a11ef721d1542d8', 'FALSE', "") );

// / Available Cores.
// / The following array specifies which corefiles are permitted to load within the platform. 
// / Cores not specified here will not be allowed to run using the loadCores() function.
$AvailableCores = array('SETTINGS', 'ADMIN', 'CLOUD', 'COMPATIBILITY', 'SANITIZE', 'APP', 'BACKUP', 'SECURITY', 'MEDIA', 'DATA');