<?php
/* 
HonestRepair Diablo Engine  -  GUI Core
https://www.HonestRepair.net
https://github.com/zelon88

Licensed Under GNU GPLv3
https://www.gnu.org/licenses/gpl-3.0.html

Author: Justin Grimes
Date: 3/12/2021
<3 Open-Source

The GUI Core provides resources for the user interfaces & decides which interface to use.
*/


// / ----------------------------------------------------------------------------------
// / Prepare the execution environment.

// / Reset PHP's time limit for execution.
set_time_limit(0);

// / Make sure there is a session started.
if (session_status() == PHP_SESSION_NONE) session_start();

// / Determine the root path where the application is installed and where it is running from.
$RootPath = '';
if (!file_exists('core.php')) { 
  // / If we can't use a relative path, check the server document root directory instead.
  $RootPath = $_SERVER['DOCUMENT_ROOT'];
  if (!file_exists($RootPath.DIRECTORY_SEPARATOR.'core.php')) die('<a class="errorMessage">ERROR!!! 16, Could not process the Core Diablo Engine file (core.php)!</a>'.PHP_EOL); }
// / ----------------------------------------------------------------------------------

// / ----------------------------------------------------------------------------------
// / Perform sanity checks to verify the environment is suitable for running.

// / Determine if the core.php file has been called already and stop execution if it has not.
if (!isset($GlobalsAreVerified)) die('<a class="errorMessage">ERROR!!! 17, Out of context execution of this file is forbidden!</a>'.PHP_EOL); 

// / Stop the application if $MaintenanceMode is enabled.
if ($MaintenanceMode) die('The requested application is currently unavailable due to maintenance.'.PHP_EOL);  
// / ----------------------------------------------------------------------------------

// / ----------------------------------------------------------------------------------
// / The following code sets the functions for the session.

// / A function to determine the current URL.
function getCurrentURL() {
  if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') {
    $httpPrefix = 'https://'; }
  if (!empty($_SERVER['HTTPS']) or $_SERVER['HTTPS'] = 'on') {
    $httpPrefix = 'http://'; }
  $Current_URL = $httpPrefix.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']; 
  return ($CurrentURL); }

// / A function to get a list of files from a folder as an array.
// / Set $pathToFiles to an absolute path to a valid directory where www-data has read access.
// / Set $includeIndexFiles to TRUE to have index.html files appear in the array of results.
// / Set $includeIndexFiles to FALSE to have index.html files removed from the array of results.
// / Set $includeDangerousFiles to TRUE to have dangerous files appear in the array of results.
// / Set $includeDangerousFiles to FALSE to have dangerous files removed from the array of results.
// / The $DangerousFiles array defined in config.php contains the global list of dangerous file extensions.
// / Set $includeDirectories to TRUE to have directories appear in the array of results.
// / Set $includeDirectories to FALSE to have directories removed from the array of results.
function getFiles($pathToFiles, $includeIndexFiles, $includeDangerousFiles, $includeDirectories) {
  global $DangerousFiles;
  $dirtyFileArr = $OperationSuccessful = FALSE;
  $dirtyFileArr = array();
  $dirtyFileArr = scandir($Files);
  foreach ($dirtyFileArr as $dirtyFile) {
    if ($dirtyFile === '.' or $dirtyFile === '..') continue;
    $dirtyExt = pathinfo($pathToFiles.DIRECTORY_SEPARATOR.$dirtyFile, PATHINFO_EXTENSION);
    if (!$includeDangerousFiles && in_array($dirtyExt, $DangerousFiles)) continue;
    if (!$includeIndexFiles && $dirtyFile == 'index.html') continue;
    if (!$includeDirectories && is_dir($pathToFiles)) continue;
    array_push($Files, $dirtyFile); }
  // / Check that the operation returned some files.
  if (count($Files) > 0) $OperationSuccessful = TRUE;
  $dirtyFileArr = $dirtyFile = $dirtyExt = $pathToFiles = $includeDangerousFiles = $includeIndexFiles = NULL;
  unset($dirtyFileArr, $dirtyFile, $dirtyExt, $pathToFiles, $includeDangerousFiles, $includeIndexFiles);
  return ($Files); }

// / A function to return the file extension of an input file.
// / Set $pathToFile to an absolute path to a valid directory where www-data has read access.
function getExtension($pathToFile) {
  $output = pathinfo($pathToFile, PATHINFO_EXTENSION);
  $pathToFile = NULL;
  unset($pathToFile);
  return $output; } 

// / A function to return a human readable file size that scales from Bytes->KB->MB->GB automatically.
// / Set $File to an absolute path to a valid directory where www-data has read access.
function getFilesize($file) {
  $Size = filesize($file);
  $file = NULL;
  unset($file);
  if ($Size < 1024) $Size = $Size." Bytes"; 
  elseif (($Size < 1048576) && ($Size > 1023)) $Size = round($Size / 1024, 1)." KB";
  elseif (($Size < 1073741824) && ($Size > 1048575)) $Size = round($Size / 1048576, 1)." MB";
  else ($Size = round($Size / 1073741824, 1)." GB");
  return ($Size); }

// / A function to set the color scheme for the session.
function setColorScheme() { 
  if ($ColorScheme == '0' or $ColorScheme == '' or !isset($ColorScheme)) {
    $ColorScheme = '1'; }
  if ($ColorScheme == '1') {
    echo ('<link rel="stylesheet" type="text/css" href="'.$CD.'Styles/iframeStyle.css">'); }
  if ($ColorScheme == '2') {
    echo ('<link rel="stylesheet" type="text/css" href="'.$CD.'Styles/iframeStyleRED.css">'); }
  if ($ColorScheme == '3') {
    echo ('<link rel="stylesheet" type="text/css" href="'.$CD.'Styles/iframeStyleGREEN.css">'); }
  if ($ColorScheme == '4') {
    echo ('<link rel="stylesheet" type="text/css" href="'.$CD.'Styles/iframeStyleGREY.css">'); }
  if ($ColorScheme == '5') {
    echo ('<link rel="stylesheet" type="text/css" href="'.$CD.'Styles/iframeStyleBLACK.css">'); } }

function outputPageElement($htmlFile) {
  global $DefaultTheme;
  
  return ($HtmlElement);
}

// / A function to initialize installed widgets.
// / Widgets are small macros for adding standalone functionality to the GUI. 
function initializeWidgets() { 
  global $WidgetsArrayCache;
  $wac = $wid = $widget = $widgets = $WidgetsAreLoaded = $key = $keyCheck = $keyWac = $widgetArrayCountRaw = $widgetArrayCountRaw = $widgetArrayCheck = FALSE;
  $widgetsArray = $newWidgetsArray = array();
  // / Scan the Widgets directory and build an array of installed Widgets.
  $widgetsArray = getFiles('Widgets', FALSE, TRUE);
  // / Iterate through all installed widgets and
  foreach ($widgetsArray as $widget) if (!is_dir($widget) && !($widget === '.' or $widget === '..' or $widget === 'index.html')) array_push($WidgetsArrayCache, $widget); 
  $WidgetArrayCountRaw = count($WidgetsArrayCache);
  $widgetArrayCountRaw = count($WidgetsArrayCache);
  // / Validate the Widget cache is accurate.
  // / The cahce is not for improving performance, but rather to store the order that the user has arranged their Widgets.
  // / Detects when Widgets are added or removed and updates the cache accordingly.
  foreach ($widgetsArray as $keyCheck => $widgetArrayCheck) {
    // / Detect if a Widget needs to be removed from the Widget cache. 
    if (!in_array($widgetArrayCheck, $widgetsArray)) { 
      // / Remove the Widget from the cache array.
      $WidgetsArrayCache[$keyCheck] = NULL;
      unset ($WidgetsArrayCache[$keyCheck]); } }
  // / Consolidate the WidgetsArrayCache but keep the order of array elements intact.
  foreach ($WidgetsArrayCache as $keyWac => $wac) {
    // / Add the current array index to a new array, but in the same order as the original array.
    // / This way new array keys will be generated in-order, but the order of the array will be preserved.
    // / This is required to keep the number of array keys down.
    // / Otherwise our array keys would become orphaned and the our index would grow arbitrarily.
    $newWidgetsArray = array_push($newWidgetsArray, $wac); 
    // / Erase the current array index from the original WidgetsArrayCache array.
    $WidgetsArrayCache[$keyWac] = NULL;
    unset ($WidgetsArrayCache[$keyWac]); }
  // / Redefine the WidgetsArrayCache with data from the newWidgetsArray.
  $WidgetsArrayCache = $newWidgetsArray;
  // / Clean up unneeded memory.
  $wac = $wid = $widget = $widgets = $WidgetsAreLoaded = $key = $keyCheck = $keyWac = $widgetArrayCountRaw = $widgetArrayCountRaw = $widgetArrayCheck = $widgetsArray = $newWidgetsArray = NULL;
  unset($wac, $wid, $widget, $widgets, $WidgetsAreLoaded, $key, $keyCheck, $keyWac, $widgetArrayCountRaw, $widgetArrayCountRaw, $widgetArrayCheck, $widgetsArray, $newWidgetsArray);
  return (array($WidgetsArrayCache, $WidgetsAreLoaded)); } 

function updateWidgetOrder() { 
  

  return ($WidgetOrder);
}

function outputWidgets($StyleToUse) { 
  
  return ($WidgetsElement);
}

function initializeApplications() { 
  $app = $application = $applications = $ApplicationsAreLoaded = $key = FALSE;
  $ApplicationsArray = $applicationArray = array();
  $applicationsArray = scandir('Widgets');
  foreach ($applicationsArray as $application) if (is_dir($application) && !($application === '.' or $application === '..')) array_push($ApplicationsArray, $application); 
  foreach ($ApplicationsArray as $key => $application) { }
  $applicationsArray = $applications = NULL;
  unset($applicationsArray, $applications);
  return (array($ApplicationArray, $applicationsAreLoaded)); }

function updateApplicationOrder() { 
  
  return ($ApplicationOrder);
}

function outputApplications($StyleToUse) { 
  
  return ($ApplicationsElement);
}

function outputLogs($StyleToUse, $LogSource) { 
  
  return ($LogsElement);
}
// / -----------------------------------------------------------------------------------
