<?php
/* 
HonestRepair Diablo Engine  -  App Core
https://www.HonestRepair.net
https://github.com/zelon88

Licensed Under GNU GPLv3
https://www.gnu.org/licenses/gpl-3.0.html

Author: Justin Grimes
Date: 3/29/2019
<3 Open-Source

The App Core manages Diablo Engine apps like installing/uninstalling/updating & launching apps.
*/

// / ----------------------------------------------------------------------------------
// / The following code sets the functions for the session.
// / All functions accept arrays as inputs. 
// / If using arrays as be sure all your array indicies align properly.

// / A function for installing new apps.
function installApp($appPackagePath) { 

}

// / A function for uninstalling apps.
function uninstallApp($app) {
  
}

// / A function for downloading an app update.
function downloadAppUpdate($app) {
  
}

// / A function for installing an app update.
function installAppUpdate($app) {
  
}

// / A function for cleaning up after an app update.
function cleanAppUpdate($app) {
  
}

// / A function for checking compatibility after an app update.
function compatAppCheck($app) {
  
}

// / A function for updating a specific app.
function automaticAppUpdate($app) {
  downloadMainUpdate(); 
  installMainUpdate();
  cleanMainUpdate();
  compatMainCheck();
}
// / ----------------------------------------------------------------------------------