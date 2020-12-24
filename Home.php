<?php
/* 
HonestRepair Diablo Engine  -  Home Page
https://www.HonestRepair.net
https://github.com/zelon88

Licensed Under GNU GPLv3
https://www.gnu.org/licenses/gpl-3.0.html

Author: Justin Grimes
Date: 12/23/2020
<3 Open-Source

The Home Page provides an entry point to the UI elements available in the Diablo Engine.
*/

// / -----------------------------------------------------------------------------------
// / The following code specifies the logic flow for the session.

// / Reset the time limit for script execution.
set_time_limit(0);

// / Load the header file and prepare valid HTML syntax for the session.
if (!file_exists('header.html')) die('ERROR!!! 1A, Could not process the Header file (header.html)!'.PHP_EOL); 
else require_once ('header.html');

// / Load the header file and prepare valid HTML syntax for the session.
if (!file_exists('core.php')) die('ERROR!!! 1B, Could not process the Core Diablo Engine file (core.php)!'.PHP_EOL); 
else require_once ('core.php');

// / This code verifies the date & time for file & log operations.
list ($Date, $Time, $Minute, $LastMinute) = verifyDate();

// / This code verifies the integrity of the application.
// / Also generates required directories in case they are missing & creates required log & cache files.
list ($LogFile, $CacheFile, $InstallationIsVerified) = verifyInstallation();
if (!$InstallationIsVerified) dieGracefully(3, 'Could not verify installation!');
else if ($Verbose) logEntry('Verified installation.');

// / This code loads & sanitizes the global cache & prepares the user list.
list ($Users, $CacheIsLoaded) = loadCache();
if (!$CacheIsLoaded) dieGracefully(4, 'Could not load cache file!');
else if ($Verbose) logEntry('Loaded cache file.');

// / This code takes in all required inputs to build a session and ensures they exist & are a valid type.
list ($UserInput, $PasswordInput, $SessionID, $ClientTokenInput, $UserDir, $RequestTokens, $GlobalsAreVerified) = verifyGlobals();
if (!$GlobalsAreVerified) requireLogin(); 
else if ($Verbose) logEntry('Verified global variables.');

// / This code ensures that a same-origin UI element generated the login request.
// / Also protects against packet replay attacks by ensuring that the request was generated recently and by making each request unique. 
list ($ClientToken, $ServerToken, $TokensAreVerified) = generateTokens($ClientTokenInput, $PasswordInput);
if (!$TokensAreValid) dieGracefully(6, 'Invalid tokens!');
else if ($Verbose) logEntry('Generated tokens.');

// / This code validates credentials supplied by the user against the hashed ones stored on the server.
// / Also removes the $Users user list from memory so it can not be leaked.
// / Displays a login screen when authentication fails and kills the application. 
list ($UserID, $UserName, $UserEmail, $PasswordIsCorrect, $UserIsAdmin, $AuthIsComplete) = authenticate($UserInput, $PasswordInput, $ClientToken, $ServerToken);
if (!$PasswordIsCorrect or !$AuthIsComplete) dieGracefully(7, 'Invalid username or password!'); 
else if ($Verbose) logEntry('Authenticated '.$UserName.', '.$UserID.'.');

// / This code builds arrays of good & bad libraries.
// / Libraries are directory for storing specific types of information. 
list ($LibrariesActive, $LibrariesInactive, $LibrariesCustom, $LibrariesDefault, $LibrariesAreLoaded) = loadLibraries();
if (!$LibrariesAreLoaded) dieGracefully(8, 'Could not load libraries!');
else if ($Verbose) logEntry('Loaded libraries.');

// / This code verifies each active library directory exists.
// / Verified libraries receive the $LibrariesActive[3] element & become fully activated. 
// / $LibrariesActive[0] contains the name of the library in all caps. Used as the array key.
// / $LibrariesActive[1] contains a boolean value. TRUE enables the library & FALSE disables the library.
// / $LibrariesActive[2] contains a file path to the user-specific library 
// / $LibrariesActive[3] contains an array containing library contents.
// / Instead of accepting $LibrariesActive as an argument and re-specifying it as a return value, we represent it in global scope in the loadLibraryData function.
list ($LibraryError, $LibraryDataIsLoaded) = loadLibraryData();
if (!$LibraryDataIsLoaded) dieGracefully(9, 'Could not load library data from '.$LibraryError.'!');
else if ($Verbose) logEntry('Loaded library data.');

// / This code generates a user cache file if none exists. Useful for initializing new users logging-in for the first time.
list ($UserCacheExists, $UserCache, $UserDataDir) = generateUserCache();
if (!$UserCacheExists or !$UserDataDir) dieGracefully(10, 'Could not generate a user cache file!');
else if ($Verbose) logEntry('Created user cache.');

// / This code generates a user notifications file if none exists. Useful for initializing new users logging-in for the first time.
list ($NotificationsFileExists, $NotificationsFile) = generateNotificationsFile();
if (!$NotificationsFileExists or !$NotificationsFile) dieGracefully(11, 'Could not generate a user notifications file!');
else if ($Verbose) logEntry('Created user notifications.'); 
// / -----------------------------------------------------------------------------------