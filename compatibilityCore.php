<?php
/* 
HonestRepair Diablo Engine  -  Compatibility Core
https://www.HonestRepair.net
https://github.com/zelon88

Licensed Under GNU GPLv3
https://www.gnu.org/licenses/gpl-3.0.html

Author: Justin Grimes
Date: 3/12/2021
<3 Open-Source

The Compatibility Core handles engine maintanence, updates & compatibility-related modifications.
*/

global $ConfigIsLoaded, $EngineVersion;

// / ----------------------------------------------------------------------------------
// / Make sure the core is loaded.
if (!isset($ConfigIsLoaded) or $ConfigIsLoaded !== TRUE) die('ERROR!!! compatibilityCore: The requested application is currently unavailable.'.PHP_EOL); 
// / ----------------------------------------------------------------------------------

// / ----------------------------------------------------------------------------------
// / Specify the engine version.
$EngineVersion = 'v0.7.5';
// / ----------------------------------------------------------------------------------

// / ----------------------------------------------------------------------------------
// / The following code sets the functions for the session.

// / A function for downloading an engine update.
function downloadMainUpdate() {
  
}

// / A function for installing an engine update.
function installMainUpdate() {
  
}

// / A function for cleaning up after an engine update.
function cleanMainUpdate() {
  
}

// / A function for checking compatibility after an engine update.
function compatMainCheck() {
  
}

// / A function for automatically updating the engine.
function automaticMainUpdate() { 
  downloadMainUpdate(); 
  installMainUpdate();
  cleanMainUpdate();
  compatMainCheck();
}
// / ----------------------------------------------------------------------------------