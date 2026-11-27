<?php
// / -----------------------------------------------------------------------------------
// / COPYRIGHT INFORMATION ...
// / HRCloud3, Copyright on 11/27/2026 by Justin Grimes, www.github.com/zelon88
// /
// / LICENSE INFORMATION ...
// / This project is protected by the GNU GPLv3 Open-Source license.
// / https://www.gnu.org/licenses/gpl-3.0.html
// /
// / APPLICATION INFORMATION ...
// / HRCloud3 is a home-hosted cloud storage platform built on the HRProprietary Engine.
// /
// / FILE INFORMATION ...
// / v1.0.0.
// / The Files page: the libraries a signed in user keeps files in, a folder of one, & its trash.
// / It draws an empty shell, & HRC3-Files.js fills it with what the Engine's Storage Core answers, so a folder of ten thousand
// / names costs this page nothing & every name a user wrote reaches the page as text & never as markup.
// / Every string the script shows is handed to it here, in the page's language, because the script is static.
// / A download is sent to a frame the script makes for it, so a library that refuses one answers there & the page can say why.
// / The dialog's form is never sent. HRC3-Files.js closes the dialog with the button that sent it, once the browser has checked what was
// / typed. It names about:blank as where it would go, so HRC3-SignIn.js never takes it for one of the page's own posts, which it holds
// / back while a key is being traded & stamps with the key.
// / It reads $PageData, $EnginePageState, $InterfaceAssets, $InterfaceToUse, $LanguageToUse, $ColorToUse & the language pack, & no request.
// / See Documentation/CREATING_INTERFACES.txt & Documentation/ABOUT_STORAGE.txt.
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
$filesState = (isset($EnginePageState) && is_array($EnginePageState)) ? $EnginePageState : array();
$filesSignedIn = !empty($filesState['SignedIn']);
$filesFlags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE;
// / The address this page is drawn again from, which a reload asks for. It names the choices in use, as footer.php names them for home.
$filesAddress = (string)($PageData['EntryPoint'] ?? 'cloudCore.php').'?page=files&interface='.rawurlencode((string)$InterfaceToUse).'&language='.rawurlencode((string)$LanguageToUse).'&color='.rawurlencode((string)$ColorToUse);
$filesText = array('personal' => $TextLibraryPersonal, 'shared' => $TextLibraryShared, 'sharedReadOnly' => $TextLibrarySharedReadOnly, 'download' => $TextDownload,
  'rename' => $TextRename, 'move' => $TextMove, 'copy' => $TextCopy, 'delete' => $TextDelete, 'restore' => $TextRestore, 'deleteForever' => $TextDeleteForever,
  'openFolder' => $TextOpenFolder, 'top' => $TextTopOfLibrary, 'folderEmpty' => $TextFolderEmpty, 'trashEmpty' => $TextTrashEmpty, 'loading' => $TextLoading,
  'noLibraries' => $TextNoLibraries, 'newFolder' => $TextNewFolder, 'confirmDelete' => $TextConfirmDelete, 'confirmPurge' => $TextConfirmPurge,
  'confirmEmpty' => $TextConfirmEmpty, 'uploading' => $TextUploading, 'uploadedSome' => $TextUploadedSome, 'uploadedAll' => $TextUploadedAll, 'trashKeeps' => $TextTrashKeeps,
  'trashKeepsForever' => $TextTrashKeepsForever, 'folderCut' => $TextFolderCut, 'sizeUnits' => $TextSizeUnits, 'columnName' => $TextColumnName,
  'columnSize' => $TextColumnSize, 'columnChanged' => $TextColumnChanged, 'columnFrom' => $TextColumnFrom, 'columnDeleted' => $TextColumnDeleted,
  'filesCaption' => $TextFilesCaption, 'trashCaption' => $TextTrashCaption, 'showTrash' => $TextShowTrash, 'showFiles' => $TextShowFiles);
// / -----------------------------------------------------------------------------------
?>
    <main class='hrc3-main hrc3-files' id='hrc3Main'>
      <h1 class='hrc3-page-heading'><?php echo htmlspecialchars($TextFilesHeading, ENT_QUOTES, 'UTF-8'); ?></h1>
      <?php if (!$filesSignedIn) { ?>
      <section class='hrc3-card'>
        <p><?php echo htmlspecialchars($TextFilesNotSignedIn, ENT_QUOTES, 'UTF-8'); ?></p>
      </section>
      <?php } else { ?>
      <section class='hrc3-card' id='hrc3FilesCard' aria-labelledby='hrc3FilesCaption'>
        <div class='hrc3-files-bar'>
          <label class='hrc3-files-library'><?php echo htmlspecialchars($TextLibrary, ENT_QUOTES, 'UTF-8'); ?>
            <select id='hrc3FilesLibrary' disabled></select>
          </label>
          <div class='hrc3-files-tools'>
            <label class='hrc3-button hrc3-files-upload' id='hrc3FilesUploadLabel' hidden><?php echo htmlspecialchars($TextUpload, ENT_QUOTES, 'UTF-8'); ?>
              <input type='file' id='hrc3FilesUpload' multiple>
            </label>
            <button type='button' class='hrc3-button hrc3-button-quiet' id='hrc3FilesNewFolder' hidden><?php echo htmlspecialchars($TextNewFolder, ENT_QUOTES, 'UTF-8'); ?></button>
            <button type='button' class='hrc3-button hrc3-button-quiet' id='hrc3FilesTrashToggle' hidden><?php echo htmlspecialchars($TextShowTrash, ENT_QUOTES, 'UTF-8'); ?></button>
            <button type='button' class='hrc3-button hrc3-button-quiet' id='hrc3FilesEmptyTrash' hidden><?php echo htmlspecialchars($TextEmptyTrash, ENT_QUOTES, 'UTF-8'); ?></button>
          </div>
        </div>
        <p class='hrc3-note' id='hrc3FilesLibraryNote'></p>
        <nav class='hrc3-files-trail' aria-label='<?php echo htmlspecialchars($TextFolderTrail, ENT_QUOTES, 'UTF-8'); ?>'>
          <ol id='hrc3FilesTrail'></ol>
        </nav>
        <p class='hrc3-status' id='hrc3FilesStatus' role='status' aria-live='polite' hidden></p>
        <p class='hrc3-note' id='hrc3FilesNote' hidden></p>
        <div class='hrc3-files-scroll' id='hrc3FilesDrop'>
          <table class='hrc3-files-table' id='hrc3FilesTable'>
            <caption id='hrc3FilesCaption'><?php echo htmlspecialchars($TextFilesCaption, ENT_QUOTES, 'UTF-8'); ?></caption>
            <thead>
              <tr>
                <th scope='col' id='hrc3FilesColumnName'><?php echo htmlspecialchars($TextColumnName, ENT_QUOTES, 'UTF-8'); ?></th>
                <th scope='col' id='hrc3FilesColumnSecond'><?php echo htmlspecialchars($TextColumnSize, ENT_QUOTES, 'UTF-8'); ?></th>
                <th scope='col' id='hrc3FilesColumnThird'><?php echo htmlspecialchars($TextColumnChanged, ENT_QUOTES, 'UTF-8'); ?></th>
                <th scope='col'><?php echo htmlspecialchars($TextColumnActions, ENT_QUOTES, 'UTF-8'); ?></th>
              </tr>
            </thead>
            <tbody id='hrc3FilesBody'></tbody>
          </table>
          <p class='hrc3-empty' id='hrc3FilesEmpty'><?php echo htmlspecialchars($TextLoading, ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
      </section>
      <dialog class='hrc3-dialog' id='hrc3FilesDialog' aria-labelledby='hrc3FilesDialogMessage'>
        <form class='hrc3-form' id='hrc3FilesDialogForm' action='about:blank'>
          <p id='hrc3FilesDialogMessage'></p>
          <label id='hrc3FilesDialogNameLabel'><?php echo htmlspecialchars($TextNameLabel, ENT_QUOTES, 'UTF-8'); ?><input type='text' id='hrc3FilesDialogName' maxlength='255' autocomplete='off' spellcheck='false'></label>
          <label id='hrc3FilesDialogLibraryLabel'><?php echo htmlspecialchars($TextIntoLibrary, ENT_QUOTES, 'UTF-8'); ?><select id='hrc3FilesDialogLibrary'></select></label>
          <label id='hrc3FilesDialogFolderLabel'><?php echo htmlspecialchars($TextIntoFolder, ENT_QUOTES, 'UTF-8'); ?><input type='text' id='hrc3FilesDialogFolder' maxlength='4096' autocomplete='off' spellcheck='false'><span class='hrc3-note'><?php echo htmlspecialchars($TextIntoFolderNote, ENT_QUOTES, 'UTF-8'); ?></span></label>
          <div class='hrc3-dialog-buttons'>
            <button type='submit' class='hrc3-button' id='hrc3FilesDialogConfirm' value='confirm'></button>
            <button type='submit' class='hrc3-button hrc3-button-quiet' value='cancel' formnovalidate><?php echo htmlspecialchars($TextCancel, ENT_QUOTES, 'UTF-8'); ?></button>
          </div>
        </form>
      </dialog>
      <script src='<?php echo htmlspecialchars((string)$InterfaceAssets['Files'], ENT_QUOTES, 'UTF-8'); ?>'></script>
      <script>
        // / The page supplies values, & HRC3-Files.js supplies behavior. It begins once the sign in library has, because every request it sends carries the key.
        if (typeof HRC3Files !== 'undefined' && typeof HRC3Files.configure === 'function') {
          HRC3Files.configure(<?php echo json_encode(array('endpoint' => (string)($PageData['EntryPoint'] ?? 'cloudCore.php'), 'address' => $filesAddress, 'tokens' => (array)($PageData['Tokens'] ?? array()), 'text' => $filesText, 'outcomes' => $TextFileOutcomes), $filesFlags); ?>);
        }
      </script>
      <?php } ?>
    </main>
<?php
// / Manually clean up sensitive memory. Helps to keep track of variable assignments.
// / A component is not a function, so its temporary variables are nulled & unset.
$filesState = $filesSignedIn = $filesFlags = $filesAddress = $filesText = NULL;
unset($filesState, $filesSignedIn, $filesFlags, $filesAddress, $filesText);
?>
