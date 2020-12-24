<?php

/* 
HonestRepair Diablo Engine  -  Header
https://www.HonestRepair.net
https://github.com/zelon88

Licensed Under GNU GPLv3
https://www.gnu.org/licenses/gpl-3.0.html

Author: Justin Grimes
Date: 8/13/2019
<3 Open-Source

The Login file provides UI elements enabling users to gain access to their account.
*/









// / This page needs to collect the username and submit it to the core.php file.
// / Required POSTS 
// /  Username 
// /  RequestTokens

// /  Country?

// / The server will respond with a fresh ClientToken
// / Once the user has obtained a ClientToken they can submit the client token, username, and password to the core.
// / The core will validate the inputs and either allow or deny the login request.
?> 
  <div id="wholePageLogin1" class="modal2">
    <div id="wholePageLogin2" class="center" style="width:75%; height:50%;">
      <form class="modal-content animate" action="core.php" method="POST">
        <div class="imgcontainer">
          <img src="Resources/placeholderAvatar.png" alt="Avatar" class="avatar">
        </div>

        <div class="container" style="background-color:#F1F1F1; text-align:center;">
          <div><h1>Welcome!</h1>
            <h1>Please login or create a new account below.</h1>
          </div>

          <input type="text" placeholder="Enter Username" name="UserInput" required>
          <input type="hidden" id="RequestTokens" name="RequestTokens" required>

          <p><button type="submit">Continue</button></p>

        <p><span class="psw"><a href="#">Forgot Username</a></span></p>
        <p><span class="psw"><a href="#">Create New Account</a></span></p>
        </div>
      </form>
    </div>
  </div>