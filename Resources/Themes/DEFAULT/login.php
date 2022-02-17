<?php

/* 
HonestRepair Diablo Engine  -  Header
https://www.HonestRepair.net
https://github.com/zelon88

Licensed Under GNU GPLv3
https://www.gnu.org/licenses/gpl-3.0.html

Author: Justin Grimes
Date: 2/16/2022
<3 Open-Source

The Login file provides UI elements enabling users to gain access to their account.
*/

?> 
  <div id="wholePageLogin1" class="modal2">
    <div id="wholePageLogin1" class="center" style="width:75%; height:50%;">
      <form class="modal-content animate" action="core.php" method="POST">
        <div class="imgcontainer">
          <img src="Resources/placeholderAvatar.png" alt="Avatar" class="avatar">
        </div>

        <div class="container" style="background-color:#F1F1F1; text-align:center;">
          <div><h1>Welcome!</h1>
            <h1>Please login or create a new account below.</h1>
          </div>

          <input type="text" placeholder="Enter Username" id="userInput" name="UserInput" required>
          <input type="hidden" id="requestTokens" id="RequestTokens" name="RequestTokens" required>

          <p><button type="submit" id="submitButton">Continue</button></p>

        <p><span class="psw"><a href="#">Forgot Username</a></span></p>
        <p><span class="psw"><a href="#">Create New Account</a></span></p>

        <button type="button" onclick="document.getElementById('wholePageLogin1').style.display='none'" class="cancelbtn">Cancel</button>
        
        </div>
      </form>
    </div>
  </div>