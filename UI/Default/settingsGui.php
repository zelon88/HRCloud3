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
// / The settings page: the signed in user's account, & the interface, language & color they see.
// / The Engine's file lifetime is not offered, because HRCloud3 keeps a user's files until the user deletes them.
// / Each account form names the Engine's action in data-hrc3-action, & HRC3-Functions.js sends it with the page's key & says what the
// / Engine answered in the form's status. The appearance form is an ordinary form, which posts back to this page with the choices.
// / A form an administrator turned off is not drawn, & neither is one this server cannot carry out, as changing an address without email.
// / It reads $PageData, $PageChoices, $InterfaceToUse, $LanguageToUse, $ColorToUse, $EnginePageState & the language pack, & no request.
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
$settingsState = (isset($EnginePageState) && is_array($EnginePageState)) ? $EnginePageState : array();
$settingsSignedIn = !empty($settingsState['SignedIn']);
$settingsMinimum = (int)($settingsState['MinimumPasswordLength'] ?? 12);
$settingsChoice = $settingsName = '';
$settingsHasChoices = (count((array)($PageChoices['Interfaces'] ?? array())) > 1 or count((array)($PageChoices['Languages'] ?? array())) > 1 or count((array)($PageChoices['Colors'] ?? array())) > 1);
// / -----------------------------------------------------------------------------------
?>
    <main class='hrc3-main' id='hrc3Main'>
      <h1 class='hrc3-page-heading'><?php echo htmlspecialchars($TextSettings, ENT_QUOTES, 'UTF-8'); ?></h1>
      <?php if (!empty($settingsState['Enabled']) && !$settingsSignedIn) { ?>
      <section class='hrc3-card'>
        <p><?php echo htmlspecialchars($TextNotSignedIn, ENT_QUOTES, 'UTF-8'); ?></p>
      </section>
      <?php } ?>
      <?php if ($settingsSignedIn) { ?>
      <section class='hrc3-card' aria-labelledby='hrc3AccountHeading'>
        <h2 id='hrc3AccountHeading'><?php echo htmlspecialchars($TextAccountHeading, ENT_QUOTES, 'UTF-8'); ?></h2>
        <dl class='hrc3-facts'>
          <dt><?php echo htmlspecialchars($TextUsername, ENT_QUOTES, 'UTF-8'); ?></dt>
          <dd><?php echo htmlspecialchars((string)$settingsState['Username'], ENT_QUOTES, 'UTF-8'); ?></dd>
          <dt><?php echo htmlspecialchars($TextAddressLabel, ENT_QUOTES, 'UTF-8'); ?></dt>
          <dd id='hrc3CurrentAddress' dir='ltr'><?php echo htmlspecialchars((string)($settingsState['Address'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></dd>
        </dl>
      </section>
      <section class='hrc3-card' aria-labelledby='hrc3PasswordHeading'>
        <h2 id='hrc3PasswordHeading'><?php echo htmlspecialchars($TextChangePasswordHeading, ENT_QUOTES, 'UTF-8'); ?></h2>
        <p class='hrc3-note'><?php echo htmlspecialchars($TextChangePasswordNote, ENT_QUOTES, 'UTF-8'); ?></p>
        <form class='hrc3-form' data-hrc3-action='password' action='cloudCore.php' method='post'>
          <label><?php echo htmlspecialchars($TextCurrentPassword, ENT_QUOTES, 'UTF-8'); ?><input type='password' name='SignInPassword' autocomplete='current-password' maxlength='1024' required></label>
          <label><?php echo htmlspecialchars($TextNewPassword, ENT_QUOTES, 'UTF-8'); ?><input type='password' name='SignInNewPassword' autocomplete='new-password' minlength='<?php echo $settingsMinimum; ?>' maxlength='1024' required></label>
          <button type='submit' class='hrc3-button'><?php echo htmlspecialchars($TextChangePasswordButton, ENT_QUOTES, 'UTF-8'); ?></button>
          <p class='hrc3-status' role='status' aria-live='polite' data-hrc3-status hidden></p>
        </form>
      </section>
      <section class='hrc3-card' aria-labelledby='hrc3AddressHeading'>
        <h2 id='hrc3AddressHeading'><?php echo htmlspecialchars($TextChangeAddressHeading, ENT_QUOTES, 'UTF-8'); ?></h2>
        <?php if (!empty($settingsState['EmailAvailable'])) { ?>
        <p class='hrc3-note'><?php echo htmlspecialchars($TextChangeAddressNote, ENT_QUOTES, 'UTF-8'); ?></p>
        <form class='hrc3-form' data-hrc3-action='address' action='cloudCore.php' method='post'>
          <label><?php echo htmlspecialchars($TextCurrentPassword, ENT_QUOTES, 'UTF-8'); ?><input type='password' name='SignInPassword' autocomplete='current-password' maxlength='1024' required></label>
          <label><?php echo htmlspecialchars($TextNewAddress, ENT_QUOTES, 'UTF-8'); ?><input type='email' name='SignInEmail' autocomplete='email' maxlength='254' required dir='ltr'></label>
          <button type='submit' class='hrc3-button'><?php echo htmlspecialchars($TextSendAddressCode, ENT_QUOTES, 'UTF-8'); ?></button>
          <p class='hrc3-status' role='status' aria-live='polite' data-hrc3-status hidden></p>
        </form>
        <form class='hrc3-form' data-hrc3-action='address-confirm' action='cloudCore.php' method='post' id='hrc3AddressConfirm' hidden>
          <label><?php echo htmlspecialchars($TextCode, ENT_QUOTES, 'UTF-8'); ?><input type='text' name='SignInCode' autocomplete='one-time-code' maxlength='10' required dir='ltr' class='hrc3-code'></label>
          <button type='submit' class='hrc3-button'><?php echo htmlspecialchars($TextConfirmAddressButton, ENT_QUOTES, 'UTF-8'); ?></button>
          <p class='hrc3-status' role='status' aria-live='polite' data-hrc3-status hidden></p>
        </form>
        <?php } else { ?>
        <p class='hrc3-note'><?php echo htmlspecialchars($TextNoEmail, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php } ?>
      </section>
      <?php } ?>
      <?php if ($settingsHasChoices) { ?>
      <section class='hrc3-card' aria-labelledby='hrc3AppearanceHeading'>
        <h2 id='hrc3AppearanceHeading'><?php echo htmlspecialchars($TextAppearanceHeading, ENT_QUOTES, 'UTF-8'); ?></h2>
        <form class='hrc3-form' action='cloudCore.php' method='post'>
          <input type='hidden' name='page' value='settings'>
          <input type='hidden' name='Token1' value='<?php echo htmlspecialchars((string)($PageData['Tokens']['Token1'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>'>
          <input type='hidden' name='Token2' value='<?php echo htmlspecialchars((string)($PageData['Tokens']['Token2'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>'>
          <?php if (count((array)($PageChoices['Interfaces'] ?? array())) > 1) { ?>
          <label><?php echo htmlspecialchars($TextInterface, ENT_QUOTES, 'UTF-8'); ?>
            <select name='interface'>
              <?php foreach ((array)$PageChoices['Interfaces'] as $settingsChoice) { ?>
              <option value='<?php echo htmlspecialchars((string)$settingsChoice, ENT_QUOTES, 'UTF-8'); ?>'<?php echo ($settingsChoice === $InterfaceToUse) ? ' selected' : ''; ?>><?php echo htmlspecialchars((string)$settingsChoice, ENT_QUOTES, 'UTF-8'); ?></option>
              <?php } ?>
            </select>
          </label>
          <?php } else { ?>
          <input type='hidden' name='interface' value='<?php echo htmlspecialchars((string)$InterfaceToUse, ENT_QUOTES, 'UTF-8'); ?>'>
          <?php } ?>
          <?php if (count((array)($PageChoices['Languages'] ?? array())) > 1) { ?>
          <label><?php echo htmlspecialchars($TextLanguage, ENT_QUOTES, 'UTF-8'); ?>
            <select name='language'>
              <?php foreach ((array)$PageChoices['Languages'] as $settingsChoice => $settingsName) { ?>
              <option value='<?php echo htmlspecialchars((string)$settingsChoice, ENT_QUOTES, 'UTF-8'); ?>' lang='<?php echo htmlspecialchars((string)$settingsChoice, ENT_QUOTES, 'UTF-8'); ?>'<?php echo ($settingsChoice === $LanguageToUse) ? ' selected' : ''; ?>><?php echo htmlspecialchars((string)$settingsName, ENT_QUOTES, 'UTF-8'); ?></option>
              <?php } ?>
            </select>
          </label>
          <?php } else { ?>
          <input type='hidden' name='language' value='<?php echo htmlspecialchars((string)$LanguageToUse, ENT_QUOTES, 'UTF-8'); ?>'>
          <?php } ?>
          <?php if (count((array)($PageChoices['Colors'] ?? array())) > 1) { ?>
          <label><?php echo htmlspecialchars($TextColor, ENT_QUOTES, 'UTF-8'); ?>
            <select name='color'>
              <?php foreach ((array)$PageChoices['Colors'] as $settingsChoice) { ?>
              <option value='<?php echo htmlspecialchars((string)$settingsChoice, ENT_QUOTES, 'UTF-8'); ?>'<?php echo ($settingsChoice === $ColorToUse) ? ' selected' : ''; ?>><?php echo htmlspecialchars((string)($TextColorNames[$settingsChoice] ?? $settingsChoice), ENT_QUOTES, 'UTF-8'); ?></option>
              <?php } ?>
            </select>
          </label>
          <?php } else { ?>
          <input type='hidden' name='color' value='<?php echo htmlspecialchars((string)$ColorToUse, ENT_QUOTES, 'UTF-8'); ?>'>
          <?php } ?>
          <button type='submit' class='hrc3-button'><?php echo htmlspecialchars($TextSave, ENT_QUOTES, 'UTF-8'); ?></button>
        </form>
      </section>
      <?php } ?>
    </main>
<?php
// / Manually clean up sensitive memory. Helps to keep track of variable assignments.
// / A component is not a function, so its temporary variables are nulled & unset.
$settingsState = $settingsSignedIn = $settingsMinimum = $settingsChoice = $settingsName = $settingsHasChoices = NULL;
unset($settingsState, $settingsSignedIn, $settingsMinimum, $settingsChoice, $settingsName, $settingsHasChoices);
?>
