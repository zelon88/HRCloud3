/* 
HonestRepair Diablo Engine  -  Javascript Library
https://www.HonestRepair.net
https://github.com/zelon88

Licensed Under GNU GPLv3
https://www.gnu.org/licenses/gpl-3.0.html

Author: Justin Grimes
Date: 4/102022
<3 Open-Source

The Home Page provides common functionality for many of the UI elements in HRCloud3.

There may be additional specialized functionality contained in separate Javascript files.
*/

// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to focus the cursor on a specified element.
// / Useful for building dynamic forms.
function setFocus(id) { 
  document.getElementById(id).focus(); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to change the value of an element.
// / Useful for building dynamic forms.
function changeValue(id, newValue) {
  document.getElementById(id).value = newValue; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to change the inner HTML content of an element.
// / Useful for building dynmic pages.
function changeContent(id, newContent) { 
  document.getElementById(id).innerHTML = newContent; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to toggle the visibility of an HTML element. 
// / Switches the 'Visibility' property between 'block' & 'none'.
function toggleVisibility(id) {
  var e = document.getElementById(id);
  if (e.style.display == 'block') e.style.display = 'none';
  else e.style.display = 'block'; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to set the visibility of an HTML element to a specific value. 
// / Switches the 'Visibility' property to whatever the second argument is.
function setVisibility(id, newVisibility) {
  var e = document.getElementById(id);
  e.style.display = newVisibility; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Provides pass-through "Back" button functionality.
// / Useful for adding a back button to UI's designed to run in headless environments.
function goBack() {
  window.history.back(); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to clear a text box.
function clearInput(id) {    
  document.getElementById(id).value = ""; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to refresh an iframe from an iframe.
function refreshIframe(iframeDiv) {
  var ifr = document.getElementsByName(iframeDiv)[0];
  ifr.src = ifr.src; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to replace the inner HTML of an element with data from another div element.
// / This DOES NOT change the ID of either div. It just replaces the contents of the div.
function replaceDiv(originalDivID, newDivID) {
  document.getElementById(originalDivID).innerHTML = document.getElementById(newDivID).innerHTML; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A funtion to pause Javascript execution for a specific number of miliseconds.
// / For use in async functions.
// / Must use with await. Example: await sleep(500);
function sleep(ms) { 
  new Promise(ms => setTimeout(ms, 2000)); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to outline an element in red to draw attention to it.
function outlineRed(id) { 
  document.getElementById(id).style.borderColor = 'red'; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to outline an element in red to draw attention to it.
function outlineNone(id) { 
  document.getElementById(id).style.removeProperty('border'); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function for determining if a form element should be outlined in red or not.
function outlineAuto(id) { 
  if (document.getElementById(id).value == '') { 
    outlineRed(id); }
  else { 
    outlineNone(id); } }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to check a checkbox.
function checkCheckbox(id) { 
document.getElementById(id).checked = true; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to uncheck a checkbox.
function uncheckCheckbox(id) { 
document.getElementById(id).checked = false; }
// / -----------------------------------------------------------------------------------