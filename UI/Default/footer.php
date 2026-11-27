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
// / The end of every page. It starts the script library, & the sign in library where accounts are on, & closes what header.php opened.
// / The footer is the one component every page shares, & each library must be running before the page sends anything.
// / The page tells the sign in library only whether it is signed in, how often to trade its key & where to send it. The key is in the tab,
// / & never in a page.
// / It reads $PageName, $InterfaceAssets, $InterfaceToUse, $LanguageToUse, $ColorToUse, $PageData, $EnginePageState & the language pack, & no request.
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
$footerState = (isset($EnginePageState) && is_array($EnginePageState)) ? $EnginePageState : array();
$footerChoices = array('interface' => (string)$InterfaceToUse, 'language' => (string)$LanguageToUse, 'color' => (string)$ColorToUse);
$footerHome = 'cloudCore.php?interface='.rawurlencode((string)$InterfaceToUse).'&language='.rawurlencode((string)$LanguageToUse).'&color='.rawurlencode((string)$ColorToUse);
// / The fields that draw this page again. A sign in page draws the page its request asked for, which is where signing in leads.
$footerHere = array(array('page', ($PageName === 'engine' ? (string)($PageData['Resume'] ?? 'home') : (string)$PageName)), array('interface', (string)$InterfaceToUse), array('language', (string)$LanguageToUse), array('color', (string)$ColorToUse));
$footerMessages = $TextOutcomes;
$footerMessages['password-short'] = $TextOutcomes['password-short'].' '.(int)($footerState['MinimumPasswordLength'] ?? 12).'.';
$footerFlags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE;
// / -----------------------------------------------------------------------------------
?>
    <script>
      // / The page supplies values, & HRC3-Functions.js & HRC3-SignIn.js supply behavior.
      if (typeof HRC3 !== 'undefined' && typeof HRC3.start === 'function') {
        HRC3.start(<?php echo json_encode(array('endpoint' => (string)($PageData['EntryPoint'] ?? 'cloudCore.php'), 'tokens' => (array)($PageData['Tokens'] ?? array()), 'choices' => $footerChoices, 'messages' => $footerMessages), $footerFlags); ?>);
      }
      <?php if (!empty($footerState['Enabled'])) { ?>
      if (typeof HRC3SignIn !== 'undefined' && typeof HRC3SignIn.start === 'function') {
        HRC3SignIn.start(<?php echo json_encode(array('endpoint' => (string)($PageData['EntryPoint'] ?? 'cloudCore.php'), 'prefix' => 'HRC3-SIGNIN:', 'signedIn' => !empty($footerState['SignedIn']),
          'life' => (int)($footerState['KeyLifetime'] ?? 30), 'interval' => (int)($footerState['ExchangeInterval'] ?? 6), 'tokens' => (array)($PageData['Tokens'] ?? array()),
          'workerPath' => (string)$InterfaceAssets['SignInTimer'], 'home' => $footerHome, 'here' => $footerHere,
          'notices' => array('signed-out' => $TextOutcomes['signed-out'], 'ended' => $TextOutcomes['ended'], 'password-changed' => $TextOutcomes['password-changed'])), $footerFlags); ?>);
      }
      <?php } ?>
    </script>
  </body>
</html>
<?php
// / Manually clean up sensitive memory. Helps to keep track of variable assignments.
// / A component is not a function, so its temporary variables are nulled & unset.
$footerState = $footerChoices = $footerHome = $footerHere = $footerMessages = $footerFlags = NULL;
unset($footerState, $footerChoices, $footerHome, $footerHere, $footerMessages, $footerFlags);
?>
