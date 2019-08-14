<?php
/* 
HonestRepair Diablo Engine  -  GUI Core
https://www.HonestRepair.net
https://github.com/zelon88

Licensed Under GNU GPLv3
https://www.gnu.org/licenses/gpl-3.0.html

Author: Justin Grimes
Date: 3/29/2019
<3 Open-Source

The GUI Core provides resources for the user interfaces & decides which interface to use.
*/

// / -----------------------------------------------------------------------------------
// / The following code sets GUI specific resources.
function getCurrentURL() {
  if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') {
    $httpPrefix = 'https://'; }
  if (!empty($_SERVER['HTTPS']) or $_SERVER['HTTPS'] = 'on') {
    $httpPrefix = 'http://'; }
  $Current_URL = $httpPrefix.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']; 
  return ($CurrentURL); }
function getFiles($pathToFiles) {
  $dirtyFileArr = scandir($Files);
  foreach ($dirtyFileArr as $dirtyFile) {
    $dirtyExt = pathinfo($pathToFiles.'/'.$dirtyFile, PATHINFO_EXTENSION);
    if (in_array($dirtyExt, $DangerousFiles) or $dirtyFile == 'index.html') continue;
    array_push($Files, $dirtyFile); }
  return ($Files); }
function getExtension($pathToFile) {
  return pathinfo($pathToFile, PATHINFO_EXTENSION); } 
function getFilesize($File) {
  $Size = filesize($File);
  if ($Size < 1024) $Size=$Size." Bytes"; 
  elseif (($Size < 1048576) && ($Size > 1023)) $Size = round($Size / 1024, 1)." KB";
  elseif (($Size < 1073741824) && ($Size > 1048575)) $Size = round($Size / 1048576, 1)." MB";
  else ($Size = round($Size/1073741824, 1)." GB");
  return ($Size); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Color scheme handler.
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
  echo ('<link rel="stylesheet" type="text/css" href="'.$CD.'Styles/iframeStyleBLACK.css">'); } 
// / -----------------------------------------------------------------------------------