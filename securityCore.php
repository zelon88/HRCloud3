<?php
/* 
HonestRepair Diablo Engine  -  Security Core
https://www.HonestRepair.net
https://github.com/zelon88

Licensed Under GNU GPLv3
https://www.gnu.org/licenses/gpl-3.0.html

Author: Justin Grimes
Date: 3/29/2019
<3 Open-Source

The Security Core runs A/V scans & performs cryptographic validation for sensitive operations.
*/

// / ----------------------------------------------------------------------------------
// / Make sure the core is loaded.
if (!isset($ConfigIsLoaded) or $ConfigIsLoaded !== TRUE) die('ERROR!!! securityCore: The requested application is currently unavailable.'.PHP_EOL); 
// / ----------------------------------------------------------------------------------

// / ----------------------------------------------------------------------------------
// / The following code sets the functions for the session.

// / A function to scan a file or folder for viruses with ClamAV.
function clamAVScan($path) { 
  
}

// / A function to scan a file or folder for viruses with PHP-AV.
function phpAVScan($path) { 
  
}

// / A function to update the ClamAV virus definitions.
function clamAVUpdate() { 
  
}

// / A function to update PHP-AV virus definitions.
function clamAVUpdate() { 
  
}
// / ----------------------------------------------------------------------------------