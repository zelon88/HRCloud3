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
// / The home page, which is the App Launcher. Each app a user may open will be a tile here.
// / No app is installed in this release, so the page says so.
// / It reads $EnginePageState & the language pack, & no request.
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
$launcherUsername = (isset($EnginePageState) && is_array($EnginePageState) && !empty($EnginePageState['SignedIn'])) ? (string)$EnginePageState['Username'] : '';
// / -----------------------------------------------------------------------------------
?>
    <main class='hrc3-main' id='hrc3Main'>
      <?php if ($launcherUsername !== '') { ?>
      <p class='hrc3-welcome'><?php echo htmlspecialchars($TextWelcome, ENT_QUOTES, 'UTF-8'); ?> <?php echo htmlspecialchars($launcherUsername, ENT_QUOTES, 'UTF-8'); ?></p>
      <?php } ?>
      <section class='hrc3-card' aria-labelledby='hrc3LauncherHeading'>
        <h1 id='hrc3LauncherHeading'><?php echo htmlspecialchars($TextLauncherHeading, ENT_QUOTES, 'UTF-8'); ?></h1>
        <div class='hrc3-launcher' id='hrc3Launcher'>
          <p class='hrc3-empty'><?php echo htmlspecialchars($TextNoApps, ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
      </section>
    </main>
<?php
// / Manually clean up sensitive memory. Helps to keep track of variable assignments.
// / A component is not a function, so its temporary variables are nulled & unset.
$launcherUsername = NULL;
unset($launcherUsername);
?>
