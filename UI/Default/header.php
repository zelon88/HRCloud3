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
// / The start of every page: the document, its head & the opening of its body. footer.php closes both.
// / It links the icon, the stylesheet & the script library by the addresses the Page Core worked out, & the sign in library only
// / where accounts are on. Each address names the interface its file came from, so an error a browser reports names it too.
// / It reads $InterfaceAssets, $LanguageToUse, $ColorToUse, $PageName, $PageData, $EnginePageState & the language pack, & no request.
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
$headerAccountsAreOn = (isset($EnginePageState) && is_array($EnginePageState) && !empty($EnginePageState['Enabled']));
$headerDirection = (isset($LanguageDirection) && $LanguageDirection === 'rtl') ? 'rtl' : 'ltr';
$headerTitle = (isset($TextPageTitles[$PageName]) ? $TextPageTitles[$PageName].' - ' : '').(string)($PageData['Application'] ?? '');
// / -----------------------------------------------------------------------------------
?>
<!DOCTYPE html>
<html lang='<?php echo htmlspecialchars((string)$LanguageToUse, ENT_QUOTES, 'UTF-8'); ?>' dir='<?php echo $headerDirection; ?>' data-color='<?php echo htmlspecialchars((string)$ColorToUse, ENT_QUOTES, 'UTF-8'); ?>'>
  <head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1'>
    <meta name='referrer' content='same-origin'>
    <meta name='robots' content='noindex, nofollow'>
    <title><?php echo htmlspecialchars($headerTitle, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel='icon' type='image/svg+xml' href='<?php echo htmlspecialchars((string)$InterfaceAssets['Icon'], ENT_QUOTES, 'UTF-8'); ?>'>
    <link rel='stylesheet' href='<?php echo htmlspecialchars((string)$InterfaceAssets['Stylesheet'], ENT_QUOTES, 'UTF-8'); ?>'>
    <script src='<?php echo htmlspecialchars((string)$InterfaceAssets['Library'], ENT_QUOTES, 'UTF-8'); ?>'></script>
    <?php if ($headerAccountsAreOn) { ?>
    <script src='<?php echo htmlspecialchars((string)$InterfaceAssets['SignIn'], ENT_QUOTES, 'UTF-8'); ?>'></script>
    <?php } ?>
  </head>
  <body>
<?php
// / Manually clean up sensitive memory. Helps to keep track of variable assignments.
// / A component is not a function, so its temporary variables are nulled & unset.
$headerAccountsAreOn = $headerDirection = $headerTitle = NULL;
unset($headerAccountsAreOn, $headerDirection, $headerTitle);
?>
