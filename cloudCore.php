<?php 
/* 
HonestRepair Diablo Engine  -  Cloud Core
https://www.HonestRepair.net
https://github.com/zelon88

Licensed Under GNU GPLv3
https://www.gnu.org/licenses/gpl-3.0.html

Author: Justin Grimes
Date: 3/29/2019
<3 Open-Source

The Cloud Core handles userland data-related operations like file creation, conversion, manipulation & removal.
*/

// / ----------------------------------------------------------------------------------
// / Make sure the core is loaded.
if (!isset($ConfigIsLoaded) or $ConfigIsLoaded !== TRUE) die('ERROR!!! cloudCore: The requested application is currently unavailable.'.PHP_EOL); 
// / ----------------------------------------------------------------------------------

// / ----------------------------------------------------------------------------------
// / The following code sets the functions for the session.
// / All functions accept arrays as inputs. 
// / If using arrays as be sure all your array indicies align properly.

// / A function for determining which folder the user is currently in so relative paths can be constructed.
function defineFolder() { 

}

// / A function for making simple files in the users Cloud.
function makeFile($path, $fileType) { 
  // DON'T FORGET TO ENCODE/SANITIZE THE DATA

}

// / A function for making new folders in the users Cloud.
function makeFolders($path) { 
  
}

// / A function for uploading files to a users Cloud.
function uploadFiles($path, $uploadData) { 
  
}

// / A function for downloading files from a users Cloud.
function downloadFiles($path) { 
  
}

// / A function for compressing a users files into a portable archive.
// / Also works on folders.
function compressFiles($path, $newPath) { 
  
}

// / A function for decompressing a users files into a folder.
function decompressFiles($path, $newPath) { 
  
}

// / A function for converting files between formats.
function convertFiles($path, $newPath, $extension) { 
  
}

// / A function for encrypting files for the user.
// / Also works on folders.
function encryptFiles($path, $newPath, $encryptionType, $key) { 

}

// / A function for decrypting files for the user.
// / Also works on folders.
function decryptFiles($path, $newPath, $encryptionType, $key) { 

}

// / A function for copying files between Cloud locations.
// / Also works on folders.
function moveFiles($path, $newPath) { 
  
} 

// / A function for renaming files in a users Cloud.
// / Also works on directories.
function renameFiles($path, $newPath) { 
  
}

// / A function for cutting files with the users clipboard.
// / Also works on directories.
function clipboardCut($path, $newPath) { 
  
}

// / A function for copying files to the users clipboard.
// / Also works on directories.
function clipboardCopy($path, $newPath) { 
  
}

// / A function for pasting files from the users clipboard.
// / Also works on directories.
function clipboardPaste($path, $newPath) { 
  
}

// / A function for deleting files and folders from the users Cloud.
// / The token must be a string identical to the client token.
function deleteFiles($path, $confirmToken) { 

}
// / ----------------------------------------------------------------------------------