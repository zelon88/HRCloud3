<?php
// / -----------------------------------------------------------------------------------
// / COPYRIGHT INFORMATION ...
// / HRCloud3, Copyright on 11/26/2026 by Justin Grimes, www.github.com/zelon88
// /
// / LICENSE INFORMATION ...
// / This project is protected by the GNU GPLv3 Open-Source license.
// / https://www.gnu.org/licenses/gpl-3.0.html
// /
// / APPLICATION INFORMATION ...
// / HRCloud3 is a home-hosted cloud storage platform built on the HRProprietary Engine.
// /
// / FILE INFORMATION ...
// / v0.9.9.
// / The bar at the top of every page but the Engine's: the application's name, a button for each page a user may go to, & the account.
// / A button names its page in data-hrc3-go, & HRC3-Functions.js posts to it, carrying the session, the interface, the language & the color.
// / The account is the user's name & Sign out for a user who is signed in, & Sign in for a visitor where accounts are on. HRC3-SignIn.js
// / binds both by their ids, & writes in hrc3SignInBar why a sign in ended. Where accounts are off, the account is not drawn at all.
// / It reads $PageName, $PageData, $EnginePageState & the language pack, & no request.
// / See Documentation/CREATING_INTERFACES.txt.
// /
// / HARDWARE REQUIREMENTS ...
// / This application requires at least a Raspberry Pi Model B+ or greater.
// / This application will run on just about any x86 or x64 computer.
// /
// / DEPENDENCY REQUIREMENTS ...
// / The authoritative list of dependencies is Resources/Engine/depends.php & Resources/Contract/depends.php, the Engine's half & the application's, & nothing else.
// / Run  php cloudCore.php -v  to see what this installation has.
// /
// / <3 Open-Source
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Refuse direct execution. This file is a component & has no standalone context.
if (!isset($CoreLoaded) or $CoreLoaded !== TRUE) die('ERROR!!! HRCloud3-2: This file cannot process your request! Please submit your request to cloudCore.php instead!'.PHP_EOL);
// / Assign temporary variables.
$taskbarState = (isset($EnginePageState) && is_array($EnginePageState)) ? $EnginePageState : array();
$taskbarPages = array('home' => $TextHome, 'settings' => $TextSettings);
$taskbarPage = $taskbarLabel = '';
// / -----------------------------------------------------------------------------------
?>
    <header class='hrc3-taskbar'>
      <span class='hrc3-brand'><?php echo htmlspecialchars((string)($PageData['Application'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
      <nav class='hrc3-nav' aria-label='<?php echo htmlspecialchars($TextMainNavigation, ENT_QUOTES, 'UTF-8'); ?>'>
        <?php foreach ($taskbarPages as $taskbarPage => $taskbarLabel) if (in_array($taskbarPage, (array)($PageData['Pages'] ?? array()), TRUE)) { ?>
        <button type='button' class='hrc3-nav-button' data-hrc3-go='<?php echo $taskbarPage; ?>'<?php echo ($taskbarPage === $PageName) ? " aria-current='page'" : ''; ?>><?php echo htmlspecialchars($taskbarLabel, ENT_QUOTES, 'UTF-8'); ?></button>
        <?php } ?>
      </nav>
      <?php if (!empty($taskbarState['Enabled'])) { ?>
      <div class='hrc3-account' id='hrc3SignInBar' role='status' aria-live='polite'>
        <?php if (!empty($taskbarState['SignedIn'])) { ?>
        <span class='hrc3-account-name' title='<?php echo htmlspecialchars((string)$taskbarState['Username'], ENT_QUOTES, 'UTF-8'); ?>'><?php echo htmlspecialchars($TextSignedInAs, ENT_QUOTES, 'UTF-8'); ?> <strong><?php echo htmlspecialchars((string)$taskbarState['Username'], ENT_QUOTES, 'UTF-8'); ?></strong></span>
        <button type='button' class='hrc3-button hrc3-button-quiet' id='hrc3SignOut'><?php echo htmlspecialchars($TextSignOut, ENT_QUOTES, 'UTF-8'); ?></button>
        <?php } else { ?>
        <button type='button' class='hrc3-button' id='hrc3SignInLink'><?php echo htmlspecialchars($TextSignIn, ENT_QUOTES, 'UTF-8'); ?></button>
        <?php } ?>
      </div>
      <?php } ?>
    </header>
<?php
// / Manually clean up sensitive memory. Helps to keep track of variable assignments.
// / A component is not a function, so its temporary variables are nulled & unset.
$taskbarState = $taskbarPages = $taskbarPage = $taskbarLabel = NULL;
unset($taskbarState, $taskbarPages, $taskbarPage, $taskbarLabel);
?>
