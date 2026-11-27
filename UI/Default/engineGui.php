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
// / The page the Engine names, which signs a visitor in, makes an account, confirms an address & recovers an account.
// / It is drawn in place of every other page, between the same header & footer, & no taskbar, because nobody signed in is shown it.
// / It shows the section the Engine named & holds the others, & HRC3-SignIn.js switches between them & sends each with a script,
// / so a key comes back to the page that asked for it & never travels in a page.
// / It was taken from HRConvert2 v4.3.4's Default interface, with only its names changed. See ENGINE_RECONCILIATION_HRCLOUD3.txt.
// / Everything it shows comes from $EnginePageState, $PageData & the language pack, & it reads no request itself.
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
// / What the Engine says about this request. A missing value reads as the most careful one.
$enginePage = (isset($EnginePageState) && is_array($EnginePageState)) ? $EnginePageState : array();
$enginePageName = (isset($enginePage['Page']) && in_array($enginePage['Page'], array('signin', 'register', 'confirm', 'recover', 'reset'), TRUE)) ? $enginePage['Page'] : 'signin';
$engineAvailable = !empty($enginePage['Available']);
$engineMayRegister = !empty($enginePage['AllowRegistration']);
$engineMayEmail = !empty($enginePage['EmailAvailable']);
$engineMayStayAnonymous = !empty($enginePage['AllowAnonymous']);
$engineMinimum = (int)($enginePage['MinimumPasswordLength'] ?? 12);
// / The page a user goes to once signed in is the one their request asked for, or home, in the interface, language & color they chose.
$engineHome = 'cloudCore.php?page='.rawurlencode((string)($PageData['Resume'] ?? 'home')).'&interface='.rawurlencode((string)$InterfaceToUse).'&language='.rawurlencode((string)$LanguageToUse).'&color='.rawurlencode((string)$ColorToUse);
// / Every outcome the Engine may answer with, & what the page says for it. A short password says how short a password may be.
$engineMessages = $TextOutcomes;
$engineMessages['password-short'] = $TextOutcomes['password-short'].' '.$engineMinimum.'.';
// / -----------------------------------------------------------------------------------
?>
    <main class='hrc3-main hrc3-signin' id='hrc3Main'>
      <div class='hrc3-card hrc3-signin-card'>
        <h1 class='hrc3-brand hrc3-signin-brand'><?php echo htmlspecialchars((string)($PageData['Application'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></h1>
        <p id='hrc3SignInMessage' class='hrc3-status' role='status' aria-live='polite' hidden></p>
        <?php if (!$engineAvailable) { ?>
        <h2><?php echo htmlspecialchars($TextSignInHeading, ENT_QUOTES, 'UTF-8'); ?></h2>
        <p><?php echo htmlspecialchars($TextSigningInUnavailable, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php } else { ?>
        <section data-hrc3-page='signin'>
          <h2><?php echo htmlspecialchars($TextSignInHeading, ENT_QUOTES, 'UTF-8'); ?></h2>
          <form class='hrc3-form' data-hrc3-signin='signin' action='cloudCore.php' method='post'>
            <label><?php echo htmlspecialchars($TextUsername, ENT_QUOTES, 'UTF-8'); ?><input type='text' name='SignInUsername' autocomplete='username' maxlength='64' required dir='ltr'></label>
            <label><?php echo htmlspecialchars($TextPassword, ENT_QUOTES, 'UTF-8'); ?><input type='password' name='SignInPassword' autocomplete='current-password' maxlength='1024' required></label>
            <button type='submit' class='hrc3-button'><?php echo htmlspecialchars($TextSignIn, ENT_QUOTES, 'UTF-8'); ?></button>
          </form>
          <p class='hrc3-links'>
            <?php if ($engineMayRegister) { ?><a href='#' data-hrc3-show='register'><?php echo htmlspecialchars($TextRegisterHeading, ENT_QUOTES, 'UTF-8'); ?></a><?php } ?>
            <?php if ($engineMayEmail) { ?><a href='#' data-hrc3-show='recover'><?php echo htmlspecialchars($TextForgotPassword, ENT_QUOTES, 'UTF-8'); ?></a><?php } ?>
            <?php if ($engineMayRegister) { ?><a href='#' data-hrc3-show='confirm'><?php echo htmlspecialchars($TextHaveCode, ENT_QUOTES, 'UTF-8'); ?></a><?php } ?>
            <?php if ($engineMayStayAnonymous) { ?><a href='#' data-hrc3-home='1'><?php echo htmlspecialchars($TextContinueWithout, ENT_QUOTES, 'UTF-8'); ?></a><?php } ?>
          </p>
        </section>
        <?php if ($engineMayRegister) { ?>
        <section data-hrc3-page='register' hidden>
          <h2><?php echo htmlspecialchars($TextRegisterHeading, ENT_QUOTES, 'UTF-8'); ?></h2>
          <form class='hrc3-form' data-hrc3-signin='register' action='cloudCore.php' method='post'>
            <label><?php echo htmlspecialchars($TextUsername, ENT_QUOTES, 'UTF-8'); ?><input type='text' name='SignInUsername' autocomplete='username' maxlength='64' required dir='ltr'></label>
            <label><?php echo htmlspecialchars($TextEmail, ENT_QUOTES, 'UTF-8'); ?><input type='email' name='SignInEmail' autocomplete='email' maxlength='254' required dir='ltr'></label>
            <label><?php echo htmlspecialchars($TextPassword, ENT_QUOTES, 'UTF-8'); ?><input type='password' name='SignInPassword' autocomplete='new-password' minlength='<?php echo $engineMinimum; ?>' maxlength='1024' required></label>
            <button type='submit' class='hrc3-button'><?php echo htmlspecialchars($TextRegisterHeading, ENT_QUOTES, 'UTF-8'); ?></button>
          </form>
          <p class='hrc3-links'><a href='#' data-hrc3-show='signin'><?php echo htmlspecialchars($TextBackToSignIn, ENT_QUOTES, 'UTF-8'); ?></a></p>
        </section>
        <section data-hrc3-page='confirm' hidden>
          <h2><?php echo htmlspecialchars($TextConfirmHeading, ENT_QUOTES, 'UTF-8'); ?></h2>
          <form class='hrc3-form' data-hrc3-signin='confirm' action='cloudCore.php' method='post'>
            <label><?php echo htmlspecialchars($TextUsername, ENT_QUOTES, 'UTF-8'); ?><input type='text' name='SignInUsername' autocomplete='username' maxlength='64' required dir='ltr'></label>
            <label><?php echo htmlspecialchars($TextCode, ENT_QUOTES, 'UTF-8'); ?><input type='text' name='SignInCode' autocomplete='one-time-code' maxlength='10' required dir='ltr' class='hrc3-code'></label>
            <button type='submit' class='hrc3-button'><?php echo htmlspecialchars($TextConfirmButton, ENT_QUOTES, 'UTF-8'); ?></button>
          </form>
          <form class='hrc3-form hrc3-form-again' data-hrc3-signin='resend' action='cloudCore.php' method='post'>
            <input type='hidden' name='SignInUsername'>
            <button type='submit' class='hrc3-button hrc3-button-quiet'><?php echo htmlspecialchars($TextSendAgain, ENT_QUOTES, 'UTF-8'); ?></button>
          </form>
          <p class='hrc3-links'><a href='#' data-hrc3-show='signin'><?php echo htmlspecialchars($TextBackToSignIn, ENT_QUOTES, 'UTF-8'); ?></a></p>
        </section>
        <?php } ?>
        <?php if ($engineMayEmail) { ?>
        <section data-hrc3-page='recover' hidden>
          <h2><?php echo htmlspecialchars($TextRecoverHeading, ENT_QUOTES, 'UTF-8'); ?></h2>
          <form class='hrc3-form' data-hrc3-signin='recover' action='cloudCore.php' method='post'>
            <label><?php echo htmlspecialchars($TextUsername, ENT_QUOTES, 'UTF-8'); ?><input type='text' name='SignInUsername' autocomplete='username' maxlength='64' required dir='ltr'></label>
            <button type='submit' class='hrc3-button'><?php echo htmlspecialchars($TextSendCode, ENT_QUOTES, 'UTF-8'); ?></button>
          </form>
          <p class='hrc3-links'><a href='#' data-hrc3-show='reset'><?php echo htmlspecialchars($TextHaveCode, ENT_QUOTES, 'UTF-8'); ?></a> <a href='#' data-hrc3-show='signin'><?php echo htmlspecialchars($TextBackToSignIn, ENT_QUOTES, 'UTF-8'); ?></a></p>
        </section>
        <section data-hrc3-page='reset' hidden>
          <h2><?php echo htmlspecialchars($TextResetHeading, ENT_QUOTES, 'UTF-8'); ?></h2>
          <form class='hrc3-form' data-hrc3-signin='reset' action='cloudCore.php' method='post'>
            <label><?php echo htmlspecialchars($TextUsername, ENT_QUOTES, 'UTF-8'); ?><input type='text' name='SignInUsername' autocomplete='username' maxlength='64' required dir='ltr'></label>
            <label><?php echo htmlspecialchars($TextCode, ENT_QUOTES, 'UTF-8'); ?><input type='text' name='SignInCode' autocomplete='one-time-code' maxlength='10' required dir='ltr' class='hrc3-code'></label>
            <label><?php echo htmlspecialchars($TextNewPassword, ENT_QUOTES, 'UTF-8'); ?><input type='password' name='SignInPassword' autocomplete='new-password' minlength='<?php echo $engineMinimum; ?>' maxlength='1024' required></label>
            <button type='submit' class='hrc3-button'><?php echo htmlspecialchars($TextSavePassword, ENT_QUOTES, 'UTF-8'); ?></button>
          </form>
          <p class='hrc3-links'><a href='#' data-hrc3-show='signin'><?php echo htmlspecialchars($TextBackToSignIn, ENT_QUOTES, 'UTF-8'); ?></a></p>
        </section>
        <?php } else { ?>
        <section data-hrc3-page='recover' hidden><h2><?php echo htmlspecialchars($TextRecoverHeading, ENT_QUOTES, 'UTF-8'); ?></h2><p><?php echo htmlspecialchars($TextNoEmail, ENT_QUOTES, 'UTF-8'); ?></p><p class='hrc3-links'><a href='#' data-hrc3-show='signin'><?php echo htmlspecialchars($TextBackToSignIn, ENT_QUOTES, 'UTF-8'); ?></a></p></section>
        <section data-hrc3-page='reset' hidden><h2><?php echo htmlspecialchars($TextResetHeading, ENT_QUOTES, 'UTF-8'); ?></h2><p><?php echo htmlspecialchars($TextNoEmail, ENT_QUOTES, 'UTF-8'); ?></p><p class='hrc3-links'><a href='#' data-hrc3-show='signin'><?php echo htmlspecialchars($TextBackToSignIn, ENT_QUOTES, 'UTF-8'); ?></a></p></section>
        <?php } ?>
        <?php } ?>
      </div>
    </main>
    <script>
      // / The page supplies values, & HRC3-SignIn.js supplies behavior.
      if (typeof HRC3SignInPage === 'undefined' || typeof HRC3SignInPage.configure !== 'function') {
        if (window.console && window.console.error) window.console.error('HRCloud3. HRC3-SignIn.js did not load, so signing in cannot run. Reload with a cleared cache.');
      } else {
        HRC3SignInPage.configure(<?php echo json_encode(array('page' => $enginePageName, 'home' => $engineHome, 'tokens' => (array)($PageData['Tokens'] ?? array()), 'messages' => $engineMessages), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE); ?>);
      }
    </script>
<?php
// / Manually clean up sensitive memory. Helps to keep track of variable assignments.
// / A component is not a function, so its temporary variables are nulled & unset.
$enginePage = $enginePageName = $engineAvailable = $engineMayRegister = $engineMayEmail = $engineMayStayAnonymous = $engineMinimum = $engineHome = $engineMessages = NULL;
unset($enginePage, $enginePageName, $engineAvailable, $engineMayRegister, $engineMayEmail, $engineMayStayAnonymous, $engineMinimum, $engineHome, $engineMessages);
?>
