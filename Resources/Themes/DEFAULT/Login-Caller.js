/* 
HonestRepair Diablo Engine  -  Login Caller Script
https://www.HonestRepair.net
https://github.com/zelon88

Licensed Under GNU GPLv3
https://www.gnu.org/licenses/gpl-3.0.html

Author: Justin Grimes
Date: 3/14/2022
<3 Open-Source

This file is for negotiating login requests & processing the response from the server.

Also provides login related UI functionality.
*/

// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Declare global variables.
var UserInput = false;
var SessionID = false;
var ClientToken = false;
var StayLoggedIn = false;
var ActiveSLI = false;
var SessionActive = false; 
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to test that JQuery is functional.
// / To test JQuery, uncomment the following function & place an <a> element on a page where JQuery is called. 
// / If JQuery is working the <a> element should slowly disappear when clicked.
//$("a").click(function( event ) { 
  //event.preventDefault();
  //$(this).hide("slow"); });
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to take the password typed by the user and create a SHA256 hash of it.
function hashCreds(RawPassword) {
  var passwordBits = sjcl.hash.sha256.hash(RawPassword);  
  var PasswordInput = sjcl.codec.hex.fromBits(passwordBits);
  return (PasswordInput); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to perform the client-side encryption of the users password before they send it to the server.
function secureLogin(RawPassword) {
  var PasswordInput = hashCreds(RawPassword);
  changeValue('PasswordInput', PasswordInput);  
  document.getElementById('RawPassword').required = false;
  clearInput('RawPassword');
  return(PasswordInput); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to detect if "STAYLOGGEDIN" is enabled for the user.
// / Calls StayLoggedInSender() to request new user tokens when enabled.
function StayLoggedInCaller() {
  if (document.getElementById('StayLoggedIn').value == 'ENABLED') { 
    setTimeout(function() { 
      StayLoggedInSender(); 
      StayLoggedInCaller(); }, StayLoggedInInterval); } }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A funciton function to submit the Navigation Bar login form with AJAX & update the UI elements.
// / Also replaces the User input modal with the Password input modal after a Username has been sent.
// / When this function is run the loginModal is displayed.
// / This function hides the loginModal & replaces it with the passwordModal.
// / This function also passes user input to hidden form fields so they can be transferred to passwordFormNav.
$('#loginFormNav').on('submit', function (loginAjax) {
    var UserInput = document.getElementById('UserInput').value;
    loginAjax.preventDefault();
    $.ajax({
      type: 'POST',
      url: '/core.php',
      data: $(this).serialize(),
      success: function(loginReponse) {
        if (!loginReponse.includes('ERROR!!!')) { 
          var responseArray = loginReponse.split(',');
          var UserInput = responseArray[0];
          var ClientTokenInput = responseArray[1];
          toggleVisibility('loginModal');
          toggleVisibility('passwordModal');
          changeValue('ClientTokenInput', ClientTokenInput); 
          changeValue('UserInputPassword', UserInput); } } }); });
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to submit the Navigation Bar login form with AJAX & update the UI elements.
// / When this function is run the passwordModal is being displayed.
// / On HTTP & application success; This function hides passwordModal & replaces it with the successModal for 3 seconds.
// / On HTTP & application success; While successModal is displayed the hidden form fields of the client UI are updated.
// / On HTTP success & application error; This function hides passwordModal & replaces it with the errorModal for 3 seconds.
// / On any HTTP error; This function hides passwordModal & replaces it with the criticalModal for 5 seconds.
$('#passwordFormNav').on('submit', function (passwordAjax) { 
    passwordAjax.preventDefault();
    $.ajax({
      type: 'POST',
      url: '/core.php',
      data: $(this).serialize(),
      success: function(passwordResponse) {
        var passwordCorrect = !passwordResponse.includes('ERROR!!!');
        if (passwordCorrect) { 
          var responseArray = passwordResponse.split(',');
          var UserInput = responseArray[0];
          var SessionID = responseArray[1];
          var ClientToken = responseArray[2];
          var StayLoggedIn = responseArray[3];
          setVisibility('passwordModal', 'none');
          changeContent('successModalHeaderText', 'Login Success');
          toggleVisibility('successModal');
          setTimeout(function() { setVisibility('successModal', 'none'); }, 3000);
          changeValue('UserInputTokens', UserInput);
          changeValue('SessionID', SessionID);
          changeValue('ClientToken', ClientToken);
          changeValue('ActiveSLI', 'ENABLED');
          changeValue('StayLoggedIn', StayLoggedIn);
          toggleVisibility('loginButton');
          toggleVisibility('logoutButton');
          StayLoggedInCaller(); 
          }
        else { 
          toggleVisibility('passwordModal');
          changeContent('errorModalHeaderText', 'Login Failed');
          toggleVisibility('errorModal');
          setTimeout(function() { setVisibility('errorModal', 'none'); }, 3000); } },
      error: function(passwordResponse) {
          toggleVisibility('passwordModal');
          changeContent('criticalModalHeaderText', 'Login Error');
          toggleVisibility('criticalModal');
          setTimeout(function() { setVisibility('criticalModal', 'none'); }, 5000); } }); });
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to keep the user logged in by sending a request for new tokens.
// / This function will only run if "STAYLOGGEDIN" is set to "ENABLED" in a users cache file.
// / This function is activated after successful login.
// / This code is meant to be run on a schedule when a user is using the application.
function StayLoggedInSender() {
  $(function () {
    $.ajax({
      type: 'POST',
      url: '/core.php',
      data: {
        UserInput: document.getElementById('UserInputTokens').value,
        SessionID: document.getElementById('SessionID').value,
        ClientTokenInput: document.getElementById('ClientToken').value,
        ActiveSLI: document.getElementById('ActiveSLI').value,
        StayLoggedIn: document.getElementById('StayLoggedIn').value },
      success: function(sliResponse) { 
        if (sliResponse.length > 0 && !sliResponse.includes('ERROR!!!')) { 
          var SessionActive = true 
          var responseArraySLI = sliResponse.split(',');
          var UserInput = responseArraySLI[0];
          var SessionID = responseArraySLI[1];
          var ClientToken = responseArraySLI[2]; 
          var StayLoggedIn = responseArraySLI[3];
          changeValue('UserInputTokens', UserInput);
          changeValue('SessionID', SessionID);
          changeValue('ClientToken', ClientToken);
          changeValue('ActiveSLI', 'ENABLED');
          changeValue('StayLoggedIn', StayLoggedIn); } } }); }); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to check that a desired username is available.
// / When this function is run the value of the NewUserInput field is validated.
// / On HTTP & application success; This function will display a success message under the NewUserInput field.
// / On HTTP & application success; This function will display the rest of the new user form.
// / On HTTP & application success; This function will disable editing of the NewUserInput field.
// / On HTTP & application success; This function will update the value of the NewUserName field.
// / On any error; This function will display an error message under the NewUserInput field.
function checkAvailability(desiredUsername) {
  $(function () {
    $.ajax({
      type: 'POST',
      url: '/core.php',
      data: {
        UserInput: desiredUsername,
        CheckUserAvailability: 'ENABLED', },
      success: function(checkAvailabilityResponse) { 
        if (checkAvailabilityResponse.includes('FALSE')) { 
          setVisibility('checkResultsFailure', 'block'); }
        else { 
          setVisibility('checkResultsFailure', 'none');
          setVisibility('checkResultsSuccess', 'block');
          setVisibility('createAccountDetails', 'block');
          setVisibility('NewUserInput', 'none');
          setVisibility('checkButton', 'none'); 
          setVisibility('NewUserName', 'inline-block');
          changeValue('NewUserName', desiredUsername);
        } },
      error: function(checkAvailabilityResponse) { 
        toggleVisibility('checkResultsFailure'); } }); }); } 
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to reset the Create Account modal to defaults when the process is abandoned.
// / Re-sets the UI elements so that the process restarts at the beginning.
function cancelAvailability() {
  setVisibility('checkResultsFailure', 'none');
  setVisibility('checkResultsSuccess', 'none');
  setVisibility('createAccountDetails', 'none');
  setVisibility('NewUserInput', 'inline-block');
  setVisibility('checkButton', 'inline-block'); 
  setVisibility('NewUserName', 'none');
  changeValue('NewUserInput', '');
  changeValue('NewUserName', ''); } 
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to log the user out and destroy an existing session.
// / Resets the UI to a state where a different user can log in.
function logout() { 
  toggleVisibility('logoutModal'); 
  toggleVisibility('logoutButton'); 
  toggleVisibility('loginButton'); 
  changeContent('successModalHeaderText', 'Logout Success');
  setVisibility('successModal', 'block'); 
  setTimeout(function() { setVisibility('successModal', 'none'); }, 3000);
  changeValue('UserInputTokens', '');
  changeValue('SessionID', '');
  changeValue('ClientToken', '');
  changeValue('ActiveSLI', 'DISABLED');
  changeValue('StayLoggedIn', StayLoggedIn); }
// / -----------------------------------------------------------------------------------