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

if (!$GlobalsAreVerified) if (!$RequestTokens) requireLogin(); 