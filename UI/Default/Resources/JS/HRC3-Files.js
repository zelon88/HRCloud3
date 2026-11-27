// / -----------------------------------------------------------------------------------
// / COPYRIGHT INFORMATION ...
// / HRCloud3, Copyright on 11/27/2026 by Justin Grimes, www.github.com/zelon88
// /
// / LICENSE INFORMATION ...
// / This project is protected by the GNU GPLv3 Open-Source license.
// / https://www.gnu.org/licenses/gpl-3.0.html
// /
// / FILE INFORMATION ...
// / v1.0.0.
// / The Files page's behavior. filesGui.php draws an empty shell & hands this the strings it shows, & this fills it with what the
// / Engine's Storage Core answers. The server's half is Resources/Engine/Cores/storageCore.php.
// / Every request is a form posted by a script to the page's own endpoint, which HRC3-SignIn.js stamps with the page's key, so this
// / file never holds a key. Every answer is one line of JSON under Storage, & a request that cannot be read is said to have had none.
// / Every name a user wrote is put on the page as text & never as markup, so a file named like a tag is shown as its name.
// / A file is downloaded through a form posted into a frame, so a browser saves it as it arrives & never holds it whole in memory.
// / A library that refuses a download answers in the frame, where this reads why. A file that arrives answers nothing, & says nothing.
// / Each download has a new frame of its own, because a frame's first navigation replaces its blank page in the tab's history where any
// / later one would add to it, & the back button would then step through the frames rather than the folders.
// / The library & the folder open are kept in the address after #, so a reload, a key traded on reload, & back & forward keep a user
// / where they were. The address before # names this page & the interface, language & color in use, because a reload of an address a
// / script wrote asks for it again without the form that drew it. A page that is not signed in is never handed this file's work, because
// / filesGui.php draws no shell for it.
// / Every string the user reads is handed in by the page, because this file is static & cannot read a language pack.
// / See Documentation/ABOUT_STORAGE.txt.
// /
// / <3 Open-Source
// / -----------------------------------------------------------------------------------

var HRC3Files = {
  endpoint: 'cloudCore.php',
  address: 'cloudCore.php?page=files',
  tokens: { Token1: '', Token2: '' },
  text: {},
  outcomes: {},
  libraries: [],
  library: '',
  folder: [],
  view: 'files',
  writable: false,
  purgeable: false,
  uploadLimit: 0,
  trashDays: 30,
  uploading: false,
  frames: 0,
  dialogAction: null,
  language: '',

  // / Find an element of the page by its id.
  element: function (id) {
    return document.getElementById(id);
  },

  // / Fill a sentence's places, each written {place}, with the values given.
  fill: function (sentence, values) {
    return String(sentence || '').replace(/\{([a-z]+)\}/g, function (whole, place) {
      return Object.prototype.hasOwnProperty.call(values, place) ? String(values[place]) : whole;
    });
  },

  // / Choose the sentence a number needs, from those a language pack gives for each plural category, & fill it.
  plural: function (sentences, count, values) {
    var category = 'other';
    try { category = new Intl.PluralRules(this.language || undefined).select(count); } catch (thrown) { category = (count === 1) ? 'one' : 'other'; }
    if (typeof sentences === 'string') return this.fill(sentences, values);
    return this.fill((sentences && (sentences[category] || sentences.other)) || '', values);
  },

  // / Write a number of bytes as a person reads them, in the units the language pack names.
  size: function (bytes) {
    var units = this.text.sizeUnits || ['B', 'KB', 'MB', 'GB', 'TB'];
    var value = Number(bytes) || 0;
    var unit = 0;
    while (value >= 1024 && unit < units.length - 1) { value = value / 1024; unit++; }
    try { return value.toLocaleString(this.language || undefined, { maximumFractionDigits: unit === 0 ? 0 : 1 }) + ' ' + units[unit]; }
    catch (thrown) { return (unit === 0 ? String(value) : value.toFixed(1)) + ' ' + units[unit]; }
  },

  // / Write a moment, given in seconds, as a person reads it in the page's language.
  moment: function (seconds) {
    var when = new Date((Number(seconds) || 0) * 1000);
    try { return when.toLocaleString(this.language || undefined, { dateStyle: 'medium', timeStyle: 'short' }); }
    catch (thrown) { return when.toLocaleString(); }
  },

  // / Say something in the page's status, or nothing.
  say: function (sentence) {
    var status = this.element('hrc3FilesStatus');
    if (!status) return;
    status.textContent = String(sentence || '');
    status.hidden = (status.textContent === '');
  },

  // / Say what a library answered, in the page's language. An answer the page names no words for says the server did not answer.
  sayOutcome: function (outcome, prefix) {
    this.say((prefix || '') + (this.outcomes[outcome] || this.outcomes['no-answer'] || ''));
  },

  // / Find the line of JSON an answer carries under Storage, or null.
  readReply: function (replyText) {
    var lines = String(replyText || '').split(/\r?\n/);
    var index, parsed;
    for (index = lines.length - 1; index >= 0; index--) {
      try { parsed = JSON.parse(lines[index].trim()); }
      catch (thrown) { parsed = null; }
      if (parsed && typeof parsed === 'object' && parsed.Storage && typeof parsed.Storage === 'object') return parsed.Storage;
    }
    return null;
  },

  // / An answer that says the page is signed out ends its sign in, which says so & goes home. Returns TRUE when it did.
  signedOut: function (reply) {
    if (!reply || reply.Outcome !== 'signed-out') return false;
    if (typeof HRC3SignIn !== 'undefined' && typeof HRC3SignIn.end === 'function') HRC3SignIn.end('signed-out');
    else this.sayOutcome('signed-out');
    return true;
  },

  // / Send one action to the page's endpoint, with a file where one is uploaded, & hand its answer back, or null where there is none.
  // / An upload reports how far it has gone, & is given as long as it takes. Every other request is given a minute.
  send: function (fields, file, onReply, onProgress) {
    var request = new XMLHttpRequest();
    var form = new FormData();
    var self = this;
    var answered = false;
    var answer = function (reply) { if (!answered) { answered = true; onReply(reply); } };
    form.append('Token1', this.tokens.Token1 || '');
    form.append('Token2', this.tokens.Token2 || '');
    fields.forEach(function (field) { form.append(field[0], field[1]); });
    if (file) form.append('StorageUpload[]', file, file.name);
    request.open('POST', this.endpoint, true);
    if (!file) request.timeout = 60000;
    if (file && onProgress && request.upload) request.upload.onprogress = function (event) { if (event.lengthComputable) onProgress(event.loaded, event.total); };
    request.onload = function () { answer(request.status === 200 ? self.readReply(request.responseText) : null); };
    request.onerror = function () { answer(null); };
    request.ontimeout = function () { answer(null); };
    request.onabort = function () { answer(null); };
    request.send(form);
  },

  // / The fields that name the library & the folder open, which nearly every action sends.
  here: function () {
    return [['StorageLibrary', this.library], ['StorageFolder', this.folder.join('/')]];
  },

  // / The library open, as the libraries action described it.
  openLibrary: function () {
    var self = this;
    var found = null;
    this.libraries.forEach(function (library) { if (library.Name === self.library) found = library; });
    return found;
  },

  // / Read the library & the folder from the address, as #library/folder/folder, each part encoded.
  readAddress: function () {
    var hash = String(window.location.hash || '').replace(/^#/, '');
    var parts = hash === '' ? [] : hash.split('/');
    var decoded = [];
    parts.forEach(function (part) { try { decoded.push(decodeURIComponent(part)); } catch (thrown) { decoded.push(''); } });
    return { library: decoded.length ? decoded[0] : '', folder: decoded.slice(1).filter(function (name) { return name !== ''; }) };
  },

  // / Write the library & the folder open into the address, so a reload & the back button come back here. The first is written over
  // / the address the page was drawn at, so back from the first folder opened comes back to where the page began.
  writeAddress: function (replace) {
    var parts = [encodeURIComponent(this.library)].concat(this.folder.map(function (name) { return encodeURIComponent(name); }));
    var target = document.createElement('a');
    target.href = this.address + '#' + parts.join('/');
    if (window.location.href !== target.href) {
      try { window.history[replace ? 'replaceState' : 'pushState'](null, '', target.href); } catch (thrown) { window.location.hash = '#' + parts.join('/'); }
    }
  },

  // / Go to a library & a folder of it, & list it. remember is push where the user went there, replace where the page began there, & false
  // / where the address already says it, as it does after back & forward.
  open: function (library, folder, remember) {
    this.library = library;
    this.folder = folder.slice(0);
    this.view = 'files';
    if (remember) this.writeAddress(remember === 'replace');
    if (remember === 'push') this.say('');
    this.list();
  },

  // / Draw the trail of folders above the list, each a button that opens it, the library's own name first.
  drawTrail: function () {
    var trail = this.element('hrc3FilesTrail');
    var self = this;
    var crumbs = [this.library].concat(this.folder);
    if (!trail) return;
    trail.textContent = '';
    crumbs.forEach(function (name, index) {
      var item = document.createElement('li');
      var button;
      if (index === crumbs.length - 1 && self.view === 'files') {
        item.textContent = name;
        item.setAttribute('aria-current', 'location');
      }
      else {
        button = document.createElement('button');
        button.type = 'button';
        button.className = 'hrc3-link-button';
        button.textContent = name;
        button.addEventListener('click', function () { self.open(self.library, self.folder.slice(0, index), 'push'); });
        item.appendChild(button);
      }
      trail.appendChild(item);
    });
  },

  // / Show what the library open lets this user do, & say who sees it.
  drawTools: function () {
    var library = this.openLibrary();
    var note = this.element('hrc3FilesLibraryNote');
    var inTrash = (this.view === 'trash');
    this.element('hrc3FilesUploadLabel').hidden = inTrash || !this.writable;
    this.element('hrc3FilesNewFolder').hidden = inTrash || !this.writable;
    this.element('hrc3FilesTrashToggle').hidden = !this.writable;
    this.element('hrc3FilesTrashToggle').textContent = inTrash ? this.text.showFiles : this.text.showTrash;
    this.element('hrc3FilesEmptyTrash').hidden = !inTrash || !this.purgeable;
    if (note && library) note.textContent = (library.Kind === 'personal') ? this.text.personal : (library.Writable ? this.text.shared : this.text.sharedReadOnly);
  },

  // / Write the headings of the list's columns, which a folder & the trash name differently.
  drawHeadings: function () {
    var inTrash = (this.view === 'trash');
    this.element('hrc3FilesCaption').textContent = inTrash ? this.text.trashCaption : this.text.filesCaption;
    this.element('hrc3FilesColumnSecond').textContent = inTrash ? this.text.columnFrom : this.text.columnSize;
    this.element('hrc3FilesColumnThird').textContent = inTrash ? this.text.columnDeleted : this.text.columnChanged;
  },

  // / Make a small button that does something to one entry.
  rowButton: function (label, onPress) {
    var button = document.createElement('button');
    button.type = 'button';
    button.className = 'hrc3-button hrc3-button-quiet hrc3-button-small';
    button.textContent = label;
    button.addEventListener('click', onPress);
    return button;
  },

  // / Draw one entry of a folder. A folder's name opens it, & a file's name downloads it.
  drawEntry: function (entry) {
    var self = this;
    var row = document.createElement('tr');
    var nameCell = document.createElement('td');
    var sizeCell = document.createElement('td');
    var changedCell = document.createElement('td');
    var actionCell = document.createElement('td');
    var opener = document.createElement('button');
    var actions = document.createElement('div');
    opener.type = 'button';
    opener.className = 'hrc3-link-button hrc3-entry-' + (entry.Kind === 'folder' ? 'folder' : 'file');
    opener.textContent = entry.Name;
    if (entry.Kind === 'folder') {
      opener.setAttribute('aria-label', this.fill(this.text.openFolder, { name: entry.Name }));
      opener.addEventListener('click', function () { self.open(self.library, self.folder.concat([entry.Name]), 'push'); });
    }
    else opener.addEventListener('click', function () { self.download(entry.Name); });
    nameCell.appendChild(opener);
    sizeCell.textContent = (entry.Kind === 'file') ? this.size(entry.Size) : '';
    changedCell.textContent = this.moment(entry.Modified);
    actions.className = 'hrc3-row-actions';
    if (entry.Kind === 'file') actions.appendChild(this.rowButton(this.text.download, function () { self.download(entry.Name); }));
    if (this.writable) {
      actions.appendChild(this.rowButton(this.text.rename, function () { self.askRename(entry); }));
      actions.appendChild(this.rowButton(this.text.move, function () { self.askMove(entry); }));
    }
    if (this.libraries.some(function (library) { return library.Writable; })) actions.appendChild(this.rowButton(this.text.copy, function () { self.askCopy(entry); }));
    if (this.writable) actions.appendChild(this.rowButton(this.text.delete, function () { self.askDelete(entry); }));
    actionCell.appendChild(actions);
    sizeCell.className = 'hrc3-number';
    row.appendChild(nameCell);
    row.appendChild(sizeCell);
    row.appendChild(changedCell);
    row.appendChild(actionCell);
    return row;
  },

  // / Draw one entry of the trash, with the folder it was deleted from.
  drawTrashEntry: function (entry) {
    var self = this;
    var row = document.createElement('tr');
    var nameCell = document.createElement('td');
    var fromCell = document.createElement('td');
    var deletedCell = document.createElement('td');
    var actionCell = document.createElement('td');
    var actions = document.createElement('div');
    nameCell.textContent = entry.Name;
    nameCell.className = 'hrc3-entry-' + (entry.Kind === 'folder' ? 'folder' : 'file');
    fromCell.textContent = (entry.Folder === '') ? this.text.top : entry.Folder;
    deletedCell.textContent = this.moment(entry.Deleted);
    actions.className = 'hrc3-row-actions';
    actions.appendChild(this.rowButton(this.text.restore, function () { self.restore(entry); }));
    // / Each entry says whether this user may delete it forever, which in a shared library is an administrator or whoever added it.
    if (entry.Purgeable === true) actions.appendChild(this.rowButton(this.text.deleteForever, function () { self.askPurge(entry); }));
    actionCell.appendChild(actions);
    row.appendChild(nameCell);
    row.appendChild(fromCell);
    row.appendChild(deletedCell);
    row.appendChild(actionCell);
    return row;
  },

  // / Draw a list of entries, or say the list is empty.
  drawEntries: function (entries, emptySentence) {
    var body = this.element('hrc3FilesBody');
    var empty = this.element('hrc3FilesEmpty');
    var rows = document.createDocumentFragment();
    var self = this;
    body.textContent = '';
    entries.forEach(function (entry) { rows.appendChild(self.view === 'trash' ? self.drawTrashEntry(entry) : self.drawEntry(entry)); });
    body.appendChild(rows);
    empty.textContent = emptySentence;
    empty.hidden = (entries.length > 0);
    this.element('hrc3FilesTable').hidden = (entries.length === 0);
  },

  // / Say something under the tools, or nothing.
  note: function (sentence) {
    var note = this.element('hrc3FilesNote');
    note.textContent = String(sentence || '');
    note.hidden = (note.textContent === '');
  },

  // / List the folder open. A folder that is not there any more opens the top of its library.
  list: function () {
    var self = this;
    this.drawTrail();
    this.drawHeadings();
    this.send([['StorageAction', 'list']].concat(this.here()), null, function (reply) {
      if (self.signedOut(reply)) return;
      if (reply && reply.Outcome === 'listed') {
        self.writable = !!reply.Writable;
        self.purgeable = !!reply.Purgeable;
        self.drawTools();
        self.note(reply.Cut ? self.plural(self.text.folderCut, reply.Entries.length, { count: reply.Entries.length }) : '');
        self.drawEntries(reply.Entries || [], self.text.folderEmpty);
      }
      else if (reply && reply.Outcome === 'missing' && self.folder.length > 0) {
        self.sayOutcome('missing');
        self.open(self.library, [], 'replace');
      }
      else {
        self.drawEntries([], '');
        self.sayOutcome(reply ? reply.Outcome : 'no-answer');
      }
    });
  },

  // / List the library's trash, & say how long it keeps what it holds.
  listTrash: function () {
    var self = this;
    this.view = 'trash';
    this.drawTrail();
    this.drawHeadings();
    this.drawTools();
    this.send([['StorageAction', 'trash']].concat(this.here()), null, function (reply) {
      if (self.signedOut(reply)) return;
      if (reply && reply.Outcome === 'trash-listed') {
        self.purgeable = !!reply.Purgeable;
        self.drawTools();
        self.note(self.trashDays > 0 ? self.plural(self.text.trashKeeps, self.trashDays, { days: self.trashDays }) : self.text.trashKeepsForever);
        self.drawEntries(reply.Entries || [], self.text.trashEmpty);
      }
      else {
        self.drawEntries([], '');
        self.sayOutcome(reply ? reply.Outcome : 'no-answer');
      }
    });
  },

  // / Show what the view open shows, again, after something changed it.
  refresh: function () {
    if (this.view === 'trash') this.listTrash();
    else this.list();
  },

  // / Send an action on the library open, say what it answered, & show the view again.
  act: function (fields) {
    var self = this;
    this.send(fields, null, function (reply) {
      if (self.signedOut(reply)) return;
      self.sayOutcome(reply ? reply.Outcome : 'no-answer');
      self.refresh();
    });
  },

  // / Download a file of the folder open, through a frame of its own. The frame goes once it has answered, or after a minute, by which
  // / time a file that is arriving has been handed to the browser's downloads.
  download: function (fileName) {
    var self = this;
    var form = document.createElement('form');
    var frame = document.createElement('iframe');
    var fields = [['Token1', this.tokens.Token1 || ''], ['Token2', this.tokens.Token2 || ''], ['StorageAction', 'download']].concat(this.here()).concat([['StorageName', fileName]]);
    var remove = function () { if (frame.parentNode) frame.parentNode.removeChild(frame); };
    this.frames = (this.frames || 0) + 1;
    frame.name = 'hrc3FilesFrame' + this.frames;
    frame.title = this.text.download;
    frame.className = 'hrc3-files-frame';
    frame.hidden = true;
    document.body.appendChild(frame);
    frame.addEventListener('load', function () { if (self.readFrame(frame)) remove(); });
    setTimeout(remove, 60000);
    form.method = 'post';
    form.action = this.endpoint;
    form.target = frame.name;
    form.hidden = true;
    fields.forEach(function (field) {
      var input = document.createElement('input');
      input.type = 'hidden';
      input.name = field[0];
      input.value = field[1];
      form.appendChild(input);
    });
    document.body.appendChild(form);
    this.say('');
    form.submit();
    document.body.removeChild(form);
  },

  // / Read what a library answered in a download's frame, where it refused the download. A file that arrived leaves nothing to read.
  // / Returns TRUE where the frame held an answer, which is the frame's last use.
  readFrame: function (frame) {
    var reply = null;
    try { reply = (frame && frame.contentDocument && frame.contentDocument.body) ? this.readReply(frame.contentDocument.body.textContent) : null; }
    catch (thrown) { reply = null; }
    if (!reply) return false;
    if (reply.Outcome === 'sent' || this.signedOut(reply)) return true;
    this.sayOutcome(reply.Outcome);
    if (reply.Outcome === 'missing') this.refresh();
    return true;
  },

  // / Upload files to the folder open, one at a time, each saying how far it has gone, & say what became of them.
  // / A file larger than the server accepts is not sent, & is named with why. Where a name is taken, both are kept.
  upload: function (files) {
    var self = this;
    var queue = Array.prototype.slice.call(files || []);
    var uploaded = 0;
    var failure = null;
    var next = function () {
      var file = queue.shift();
      if (!file) {
        self.uploading = false;
        if (failure) self.say(self.plural(self.text.uploadedSome, uploaded, { count: uploaded, name: failure.name }) + (self.outcomes[failure.outcome] || self.outcomes['no-answer'] || ''));
        else self.say(self.plural(self.text.uploadedAll, uploaded, { count: uploaded }));
        self.list();
        return;
      }
      if (self.uploadLimit > 0 && file.size > self.uploadLimit) {
        if (!failure) failure = { name: file.name, outcome: 'too-large' };
        next();
        return;
      }
      self.say(self.fill(self.text.uploading, { name: file.name, percent: 0 }));
      self.send([['StorageAction', 'upload'], ['StorageWhenTaken', 'keep']].concat(self.here()), file, function (reply) {
        if (self.signedOut(reply)) return;
        if (reply && reply.Outcome === 'uploaded') uploaded++;
        else if (!failure) failure = { name: file.name, outcome: reply ? reply.Outcome : 'no-answer' };
        next();
      }, function (sent, total) {
        self.say(self.fill(self.text.uploading, { name: file.name, percent: Math.floor((sent / Math.max(total, 1)) * 100) }));
      });
    };
    if (this.uploading || queue.length === 0 || this.view !== 'files' || !this.writable) return;
    this.uploading = true;
    next();
  },

  // / Open the dialog, with the message & the fields an action needs, & run the action once the user confirms it.
  // / shown names the fields to show, which are name, library & folder.
  ask: function (message, confirmLabel, shown, values, onConfirm) {
    var dialog = this.element('hrc3FilesDialog');
    var nameField = this.element('hrc3FilesDialogName');
    var folderField = this.element('hrc3FilesDialogFolder');
    var librarySelect = this.element('hrc3FilesDialogLibrary');
    var self = this;
    this.element('hrc3FilesDialogMessage').textContent = message;
    this.element('hrc3FilesDialogConfirm').textContent = confirmLabel;
    this.element('hrc3FilesDialogNameLabel').hidden = (shown.indexOf('name') < 0);
    this.element('hrc3FilesDialogLibraryLabel').hidden = (shown.indexOf('library') < 0);
    this.element('hrc3FilesDialogFolderLabel').hidden = (shown.indexOf('folder') < 0);
    nameField.required = (shown.indexOf('name') >= 0);
    nameField.value = values.name || '';
    folderField.value = values.folder || '';
    librarySelect.textContent = '';
    this.libraries.forEach(function (library) {
      var option;
      if (!library.Writable) return;
      option = document.createElement('option');
      option.value = library.Name;
      option.textContent = library.Name;
      option.selected = (library.Name === self.library);
      librarySelect.appendChild(option);
    });
    this.dialogAction = onConfirm;
    dialog.returnValue = '';
    if (typeof dialog.showModal === 'function') dialog.showModal();
    else dialog.setAttribute('open', '');
    if (shown.indexOf('name') >= 0) { nameField.focus(); nameField.setSelectionRange(0, (values.name || '').lastIndexOf('.') > 0 ? values.name.lastIndexOf('.') : (values.name || '').length); }
    else if (shown.indexOf('folder') >= 0) folderField.focus();
  },

  // / The dialog closed. A confirmed one runs its action with what was typed & chosen.
  answerDialog: function () {
    var dialog = this.element('hrc3FilesDialog');
    var action = this.dialogAction;
    this.dialogAction = null;
    if (dialog.returnValue !== 'confirm' || !action) return;
    action({ name: this.element('hrc3FilesDialogName').value, library: this.element('hrc3FilesDialogLibrary').value,
      folder: this.element('hrc3FilesDialogFolder').value.split('/').filter(function (name) { return name !== ''; }).join('/') });
  },

  // / Ask for the name of a new folder in the folder open, & make it.
  askNewFolder: function () {
    var self = this;
    this.ask(this.text.newFolder, this.text.newFolder, ['name'], {}, function (typed) {
      self.act([['StorageAction', 'folder']].concat(self.here()).concat([['StorageNewName', typed.name]]));
    });
  },

  // / Ask for a new name for an entry, with the part of its name before its extension chosen, & give it that name.
  askRename: function (entry) {
    var self = this;
    this.ask(entry.Name, this.text.rename, ['name'], { name: entry.Name }, function (typed) {
      self.act([['StorageAction', 'rename']].concat(self.here()).concat([['StorageName', entry.Name], ['StorageNewName', typed.name]]));
    });
  },

  // / Ask which folder of this library an entry goes to, & move it there.
  askMove: function (entry) {
    var self = this;
    this.ask(entry.Name, this.text.move, ['folder'], { folder: this.folder.join('/') }, function (typed) {
      self.act([['StorageAction', 'move']].concat(self.here()).concat([['StorageName', entry.Name], ['StorageDestinationFolder', typed.folder]]));
    });
  },

  // / Ask which library this user may write, & which folder of it, an entry is copied to, & copy it there. Where its name is taken,
  // / both are kept.
  askCopy: function (entry) {
    var self = this;
    this.ask(entry.Name, this.text.copy, ['library', 'folder'], { folder: this.writable ? this.folder.join('/') : '' }, function (typed) {
      self.act([['StorageAction', 'copy'], ['StorageWhenTaken', 'keep']].concat(self.here()).concat([['StorageName', entry.Name],
        ['StorageDestinationLibrary', typed.library], ['StorageDestinationFolder', typed.folder]]));
    });
  },

  // / Ask before an entry goes to the trash, & send it there.
  askDelete: function (entry) {
    var self = this;
    this.ask(this.fill(this.text.confirmDelete, { name: entry.Name }), this.text.delete, [], {}, function () {
      self.act([['StorageAction', 'delete']].concat(self.here()).concat([['StorageName', entry.Name]]));
    });
  },

  // / Ask before an entry of the trash is deleted forever, & delete it.
  askPurge: function (entry) {
    var self = this;
    this.ask(this.fill(this.text.confirmPurge, { name: entry.Name }), this.text.deleteForever, [], {}, function () {
      self.act([['StorageAction', 'purge']].concat(self.here()).concat([['StorageTrashId', entry.Id]]));
    });
  },

  // / Ask before the whole trash is deleted forever, & empty it.
  askEmpty: function () {
    var self = this;
    this.ask(this.text.confirmEmpty, this.text.deleteForever, [], {}, function () {
      self.act([['StorageAction', 'empty']].concat(self.here()));
    });
  },

  // / Put an entry of the trash back where it was deleted from. Nothing is lost by it, so nothing is asked first.
  restore: function (entry) {
    this.act([['StorageAction', 'restore']].concat(this.here()).concat([['StorageTrashId', entry.Id]]));
  },

  // / Fill the choice of library, & open the one the address names, or the first.
  drawLibraries: function () {
    var select = this.element('hrc3FilesLibrary');
    var wanted = this.readAddress();
    var self = this;
    var known = this.libraries.some(function (library) { return library.Name === wanted.library; });
    select.textContent = '';
    this.libraries.forEach(function (library) {
      var option = document.createElement('option');
      option.value = library.Name;
      option.textContent = library.Name;
      select.appendChild(option);
    });
    select.disabled = (this.libraries.length < 2);
    if (this.libraries.length === 0) { this.drawEntries([], this.text.noLibraries); return; }
    if (known) this.open(wanted.library, wanted.folder, 'replace');
    else this.open(this.libraries[0].Name, [], 'replace');
    select.value = this.library;
  },

  // / Bind the page's tools, ask which libraries there are, & open one. The sign in library has begun by now.
  begin: function () {
    var self = this;
    var dialog = this.element('hrc3FilesDialog');
    var drop = this.element('hrc3FilesDrop');
    if (!this.element('hrc3FilesCard')) return;
    this.language = document.documentElement.getAttribute('lang') || '';
    this.element('hrc3FilesLibrary').addEventListener('change', function (event) { self.open(event.target.value, [], 'push'); });
    this.element('hrc3FilesUpload').addEventListener('change', function (event) { self.upload(event.target.files); event.target.value = ''; });
    this.element('hrc3FilesNewFolder').addEventListener('click', function () { self.askNewFolder(); });
    this.element('hrc3FilesEmptyTrash').addEventListener('click', function () { self.askEmpty(); });
    this.element('hrc3FilesTrashToggle').addEventListener('click', function () {
      self.say('');
      if (self.view === 'trash') { self.view = 'files'; self.list(); }
      else self.listTrash();
    });
    // / The dialog's form is checked by the browser & never sent. The button that sent it decides how the dialog closes.
    this.element('hrc3FilesDialogForm').addEventListener('submit', function (event) {
      event.preventDefault();
      dialog.close((event.submitter && event.submitter.value) ? event.submitter.value : 'confirm');
    });
    dialog.addEventListener('close', function () { self.answerDialog(); });
    drop.addEventListener('dragover', function (event) { if (self.writable && self.view === 'files') { event.preventDefault(); drop.classList.add('hrc3-dropping'); } });
    drop.addEventListener('dragleave', function () { drop.classList.remove('hrc3-dropping'); });
    drop.addEventListener('drop', function (event) {
      drop.classList.remove('hrc3-dropping');
      if (!self.writable || self.view !== 'files' || !event.dataTransfer) return;
      event.preventDefault();
      self.upload(event.dataTransfer.files);
    });
    window.addEventListener('popstate', function () {
      var wanted = self.readAddress();
      if (wanted.library !== '' && self.libraries.some(function (library) { return library.Name === wanted.library; })) {
        self.element('hrc3FilesLibrary').value = wanted.library;
        self.open(wanted.library, wanted.folder, false);
      }
    });
    this.send([['StorageAction', 'libraries']], null, function (reply) {
      if (self.signedOut(reply)) return;
      if (!reply || reply.Outcome !== 'libraries') { self.drawEntries([], ''); self.sayOutcome(reply ? reply.Outcome : 'no-answer'); return; }
      self.libraries = Array.isArray(reply.Libraries) ? reply.Libraries : [];
      self.uploadLimit = Number(reply.UploadLimit) || 0;
      self.trashDays = Number(reply.TrashDays) || 0;
      self.drawLibraries();
    });
  },

  // / options carries endpoint, address, tokens, text & outcomes. It begins once the page has loaded, which is after footer.php has started
  // / the sign in library, so the first request carries the key.
  configure: function (options) {
    var self = this;
    this.endpoint = options.endpoint || this.endpoint;
    this.address = options.address || this.address;
    this.tokens = options.tokens || this.tokens;
    this.text = options.text || {};
    this.outcomes = options.outcomes || {};
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', function () { self.begin(); });
    else setTimeout(function () { self.begin(); }, 0);
  }
};
// / <3 Open-Source
// / -----------------------------------------------------------------------------------
