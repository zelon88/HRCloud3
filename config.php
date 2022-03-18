<?php
/* 
HonestRepair Diablo Engine  -  Configuration File
https://www.HonestRepair.net
https://github.com/zelon88

Licensed Under GNU GPLv3
https://www.gnu.org/licenses/gpl-3.0.html

Author: Justin Grimes
Date: 3/18/2022
<3 Open-Source

The Configuration File contains all of the critical settings required for Diablo to run on the server.
*/

// / ----------------------------------------------------------------------------------

// / Application Name
// / Set  $ApplicationName  to a string that represents the name of this application.
$ApplicationName = 'HRCloud3';

// / Allow Anonymous User Registration
// / Set  $AllowUserRegistration  to  TRUE  if you want to allow visitors to create new accounts.
// / Set  $AllowUserRegistration  To  FALSE  if you do not want to allow visitors to create new accounts.
$AllowUserRegistration = TRUE;

// / Set User Availability Request Hit Thresholds
// / Only takes effect if  $AllowUserRegistration  is also set to TRUE.
// / Set  $UAHitThresholds[0]  to number of Username Availability requests allowed in  $UATimeThresholds[0].
// / Default for  $UAHitThresholds[0]  is 6. 
// / Set  $UAHitThreshold[1]  to number of Username Availability requests allowed in  $UATimeThresholds[1].
// / Default for  $UAHitThreshold[1]  is 12. 
// / Set  $UAHitThresholds[2]  to number of Username Availability requests allowed in  $UATimeThresholds[2].
// / Default for  $UAHitThresholds[2]  is 18. 
// / All values must be integers. Higher values mean more requests will be allowed.
$UAHitThresholds = array(6, 12, 18);

// / Set User Availability Request Time Thresholds
// / Only takes effect if  $AllowUserRegistration  is also set to TRUE.
// / Set  $UAHitThresholds[0]  to number of seconds to wait before forgetting recent Username Availability requests.
// / Default for  $UAHitThresholds[0]  is 60. 
// / The application will allow  $UAHitThresholds[0]  Username Availability Requests in  $UATimeThresholds[0]  seconds.
// / Set  $UAHitThresholds[1]  to number of seconds to wait before forgetting somewhat recent Username Availability requests.
// / Default for  $UAHitThresholds[1]  is 900. 
// / The application will allow  $UAHitThresholds[1]  Username Availability Requests in  $UATimeThresholds[1]  seconds.
// / Set  $UAHitThresholds[2]  to number of seconds to wait before forgetting not recent Username Availability requests.
// / Default for  $UAHitThresholds[2]  is 3600. 
// / The application will allow  $UAHitThresholds[2]  Username Availability Requests in  $UATimeThresholds[2]  seconds.
// / All values must be integers. Higher values mean longer cooldown between denied Username Availability Requests.
$UATimeThresholds = array(60, 900, 3600);

// / Maintenance Mode
// / Set  $MaintenanceMode  to  TRUE  if you want to prevent execution of the Diablo Engine.
// / Set  $MaintenanceMode  to  FALSE  if you want to allow execution of the Diablo Engine.
$MaintenanceMode = FALSE;

// / Stay Logged In Interval
// / Logged in users will send session keep-alive requests to the server at this interval for the life of the session.
// / Must be an integer.
// / If this is set too low; the server will be flooded with requests and user bandwith usage will increase.
// / If this is set too high; user sessions will be destroyed too soon.
// / The longer the interval the greater the exposure for session hijacking.
// / Set  $StayLoggedInInterval  to the number of milliseconds in between session keep-alive requests.
// / Minimum reccomended is 20000. 
// / Maximum reccomended is 130000.
$StayLoggedInInterval = 3000;

// / Log verbosity.
// / Set  $Verbose  to  TRUE  if you want to log sucessful operations in addition to failed operations.
// / Set  $Verbose  to  FALSE  to only log errors.
$Verbose = TRUE;

// / Update Sources.
// / Set  $UpdateSources  to a Git repo URL or .zip file URL containing a valid Diablo branch or codebase.
$UpdateSources = array('https://github.com/zelon88/Diablo-Engine/');

// / User Data Compression.
// / Set  $DataCompression  to  TRUE  if you want to compress user files with the xPress compression algorithm by zelon88.
// / For more information about xPress Compression by zelon88 visit https://www.github.com/zelon88/xPress
// / If enabled you must also specify & enable a DATA library in the $Libraries section of this config file.
// / Expect the DATA library to potentially make up 75% of user space requirements (many terabytes).
// / Set  $DataCompression  to  FALSE  to disable all internal data compression operations.
// / Disabling this will not disable GZIP HTTP compression.
$DataCompression = TRUE;

// / User Data Encryption.
// / Set  $DataEncryption  to  TRUE  if you want to encrypt user files with user-held & controlled keys.
// / If enabled you must also specify & enable a DATA library in the $Libraries section of this config file.
// / Encryption keys are stored in the DATA $Library. 
// / Anyone with read access to the DATA library will be able to read these keys. 
// / Set  $DataEncryption  to  FALSE  to disable all internal data encryption operations.
// / Disabling this will not disable authentication encryption or HTTPS encryption.
$DataEncryption = TRUE;

// / Data Backups
// / Set  $DataBackups  to  TRUE  if you want to enable backup functionality throughout the application.
// / If enabled you must also specify & enable a BACKUPS library in the $Libraries section of this config file.
// / Set  $DataBackups  to  FALSE  if you so not want any backup operations to be performed.
// / Disabling backups will prevent both automated & manual backups from taking place.
$DataBackups = TRUE;

// / Backup Username Availability Check Cache
// / The Username Availability Check Cache is a cache of IPs that have recently checked for available usernames,
// / These requests are tracked to avoid malicious actors using bots or crawlers to enumerate the user database.
// / Cache contents are rotated automatically but could prove useful when investigating security incidents.
// / This setting has the potential to consume considerable storage space if the server remains under constant attack.
// / Set  $BackupUsernameCheckCache  to  TRUE  if you want the server to preserve Username Availability Check Cache files.
// / Set  $BackupUsernameCheckCache  to  FALSE  if you want the server to discard Username Availability Check Cache files.
// / Will only work if the  BACKUPS  library is defined and enabled.
$BackupUsernameCheckCache = TRUE;

// / Dangerous File Definitions
// / Set  $DangerousFiles  to an array with each element containing a string of a valid file extension.
// / During sensitive filesystem scans & file operations files with extensions that match this list will be skipped.
// / Do not include the . character in the file extension.
$DangerousFiles = array('js', 'php', 'sh', 'exe', 'dll', 'ps1', 'vbs', 'hta', 'py', 'pl', 'flv', 'jar');

// / Security Salts.
// / Set an array containing four (4) string elements to be used for obscuring authentication-related operations.
// / You should save a copy of these salts in a safe place. 
// / You may require these salts to recover data in the event of a catastrophe.
$Salts = array('fgdsfg!sdhafbde3i85_+#$@%<G345234381234120', '2lw12564165fgdasfsdf585&^e4f1e3djtjthfnb erfsdaf', 
 '><<>?#@$@%$f%^$#$#!$$#@!DFASF #$FERG#$F34f3F$42F34f$f4', '5683bnfrbnd7uh78r34hp9rh437r8g34378734ryh37489ryh347r9');

// / Terms Of Service File
// / The Terms Of Service File contains the Terms Of Service for your organization.
// / The contents of this file will be displayed to users upon request.
$TermsOfServiceFile = 'Documentation/Terms_Of_Service.txt';

// / Privacy Policy File
// / The Privacy Policy File contains the Privacy Policy for your organization.
// / The contents of this file will be displayed to users upon request.
// / This path should be relative to the installation directory.
$PrivacyPolicyFile = 'Documentation/Privacy_Policy.txt';

// / Default Theme
// / Set  $DefaultTheme  to the default Theme to use.
// / Users can set their own theme, but this one will be used as default.
// / GUIs are stored in /Resources/Themes. Each Theme has its own subdirectory in this folder.
// / Only administrators can install new Themes, but users can select their own from the ones that are already installed.
$DefaultTheme = 'DEFAULT';

// / Default Timezone
// / Set  $DefaultTimezone  to the default timezone to use.
// / Users can set their own timezone, but this one will be used as default.
// / Uses PHP timezone IDs.
// / See https://www.php.net/manual/en/timezones.php for more information.
$DefaultTimezone = 'America/New_York';

// / Default Color Sheme
// / Set  $DefaultColorScheme  to the default color scheme to use.
// / Users can set their own color scheme, but this one will be used as default.
$DefaultColorScheme = 'BLUE';

// / Default Font
// / Set  $DefaultFont  to the default font to use.
// / Users can set their own fonts, but this one will be used as default.
// / Uses client system fonts. 
// / If a client does not have the specified font installed the default local system font is used instead. 
$DefaultFont = 'ARIAL';

// / Default Display Name
// / Set  $DefaultDisplayName  to the default display name to use.
// / Users can set their own display name, but this one will be used as default.
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
$LibrariesDefault = array('DATA', 'MOVIES', 'MUSIC', 'SHOWS', 'CHANNELS', 'DRIVE', 'BACKUPS', 'STREAMS', 'IMAGES', 'DOCUMENTS'); 

// / Set $Libraries to an array of arrays.
// / Arrays are formatted as  $Libraries['LIBRARY_NAME', ENABLED/DISABLED(bool), '/path/to/library/directory/']
$Libraries = array(
 array('DATA', TRUE, '/home/justin/Documents/Projects/DATA/DATA/'),
 array('MOVIES', TRUE, '/home/justin/Documents/Projects/DATA/MOVIES/'),
 array('MUSIC', TRUE, '/home/justin/Documents/Projects/DATA/MUSIC/'),
 array('SHOWS', TRUE, '/home/justin/Documents/Projects/DATA/SHOWS/'),
 array('CHANNELS', FALSE, '/home/justin/Documents/Projects/DATA/CHANNELS/'),
 array('DRIVE', TRUE, '/home/justin/Documents/Projects/DATA/DRIVE/'),
 array('BACKUPS', TRUE, '/home/justin/Documents/Projects/DATA/BACKUPS/'),
 array('STREAMS', FALSE, ''),
 array('IMAGES', FALSE, ''),
 array('DOCUMENTS', FALSE, ''),
 array('CUSTOM-1', FALSE, ''),
 array('CUSTOM-2', FALSE, ''),
 array('CUSTOM-3', FALSE, '') );

// / Super Admin Users.
// / Users are treated as objects. Users added here have global admin powers that cannot be changed via the GUI.
// / Users added through the GUI after initial setup are contained in the cache.
// / Arrays are formatted as  $Users['USER_ID', 'USER_NAME', 'USER_EMAIL', 'SHA-256_HASHED_PASSWORD', 'ADMIN_YES/NO(bool)', 'LAST_SESION_ID']
$Users = array(
 array('1', 'zelon88', 'test@gmail.com', '5e884898da28047151d0e56f8dc6292773603d0d6aabbdd62a11ef721d1542d8', 'TRUE', ""), // Default Passwords are all 'password'
 array('2', 'Nikki', 'test@gmail.com', '5e884898da28047151d0e56f8dc6292773603d0d6aabbdd62a11ef721d1542d8', 'FALSE', ''), 
 array('3', 'Leo', 'test@gmail.com', '5e884898da28047151d0e56f8dc6292773603d0d6aabbdd62a11ef721d1542d8', 'FALSE', ''), 
 array('4', 'Raph', 'test@gmail.com', '5e884898da28047151d0e56f8dc6292773603d0d6aabbdd62a11ef721d1542d8', 'FALSE', ''), 
 array('5', 'Mikey', 'test@gmail.com', '5e884898da28047151d0e56f8dc6292773603d0d6aabbdd62a11ef721d1542d8', 'FALSE', ''), 
 array('6', 'Donny', 'test@gmail.com', '5e884898da28047151d0e56f8dc6292773603d0d6aabbdd62a11ef721d1542d8', 'FALSE', ''),
 array('7', 'test', 'test@gmail.com', '5e884898da28047151d0e56f8dc6292773603d0d6aabbdd62a11ef721d1542d8', 'FALSE', '') );

// / Available Cores.
// / The following array specifies which corefiles are permitted to load within the platform. 
// / Cores not specified here will not be allowed to run using the loadCores() function.
$AvailableCores = array('SETTINGS', 'ADMIN', 'CLOUD', 'COMPATIBILITY', 'SANITIZE', 'APP', 'BACKUP', 'SECURITY', 'MEDIA', 'DATA');