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
function compressFiles($path, $newPath) { 
  
}

// / A function for decompressing a users files into a folder.
function decompressFiles($path, $newPath) { 
  
}

// / A function for converting files between formats.
// / Also works on files.
function convertFiles($path, $newPath, $extension) { 
  
}

// / A function for copying files between Cloud locations.
// / Also works on folders.
function moveFiles($path, $newPath) { 
  
} 

// / A function for renaming files in a users Cloud.
// / Also works on directories.
function renameFiles($path, $newPath) { 
  
}
// / ----------------------------------------------------------------------------------