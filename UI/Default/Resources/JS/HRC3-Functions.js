// / -----------------------------------------------------------------------------------
// / COPYRIGHT INFORMATION ...
// / HRCloud3, Copyright on 11/26/2026 by Justin Grimes, www.github.com/zelon88
// /
// / LICENSE INFORMATION ...
// / This project is protected by the GNU GPLv3 Open-Source license.
// / https://www.gnu.org/licenses/gpl-3.0.html
// /
// / FILE INFORMATION ...
// / v0.9.9.
// / The Default interface's script library. footer.php starts it on every page, with the values the page supplies.
// / A button naming a page in data-hrc3-go posts to it, carrying the session, the interface, the language & the color in use, so a
// / user's choices follow them from page to page. Where accounts are on, HRC3-SignIn.js adds the key to every post.
// / A form naming one of the Engine's account actions in data-hrc3-action is sent with the page's key, & says in its status what the
// / Engine answered, in the language the page handed in. A password change & a key that signs nobody in end the sign in.
// / Every string the user reads is handed in by the page, because this file is static & cannot read a language pack.
// /
// / <3 Open-Source
// / -----------------------------------------------------------------------------------

var HRC3 = {
  endpoint: 'cloudCore.php',
  tokens: { Token1: '', Token2: '' },
  choices: { interface: '', language: '', color: '' },
  messages: {},
  pendingAddress: '',

  // / Post to a page, carrying the session & the choices in use, & any fields given.
  go: function (page, fields) {
    var form = document.createElement('form');
    var all = [['Token1', this.tokens.Token1 || ''], ['Token2', this.tokens.Token2 || ''], ['page', page],
      ['interface', this.choices.interface || ''], ['language', this.choices.language || ''], ['color', this.choices.color || '']].concat(fields || []);
    form.method = 'post';
    form.action = this.endpoint;
    form.style.display = 'none';
    all.forEach(function (field) {
      var input = document.createElement('input');
      input.type = 'hidden';
      input.name = field[0];
      input.value = field[1];
      form.appendChild(input);
    });
    document.body.appendChild(form);
    form.submit();
  },

  // / Say in a form's status what the Engine answered. An answer the page names no words for says the server did not answer.
  say: function (form, outcome) {
    var status = form.querySelector('[data-hrc3-status]');
    if (!status) return;
    status.textContent = (outcome === '') ? '' : (this.messages[outcome] || this.messages['no-answer'] || '');
    status.hidden = (status.textContent === '');
  },

  // / Send one of the Engine's account actions with the page's key, & act on what it answers.
  act: function (form) {
    var self = this;
    var action = form.getAttribute('data-hrc3-action');
    var button = form.querySelector('button[type="submit"]');
    var fields, key, typedAddress;
    key = (typeof HRC3SignIn !== 'undefined' && typeof HRC3SignIn.presentable === 'function') ? HRC3SignIn.presentable() : '';
    if (!key) { this.say(form, 'signed-out'); return; }
    fields = [['SignInAction', action], ['SignInKey', key]];
    form.querySelectorAll('input[name^="SignIn"], select[name^="SignIn"]').forEach(function (input) { fields.push([input.name, input.value]); });
    typedAddress = form.querySelector('input[name="SignInEmail"]');
    if (typedAddress) this.pendingAddress = typedAddress.value;
    if (button) button.disabled = true;
    this.say(form, '');
    HRC3SignIn.ask(fields, function (reply) {
      var outcome = reply ? String(reply.Outcome || '') : 'no-answer';
      var confirmForm = document.getElementById('hrc3AddressConfirm');
      var addressForm = document.querySelector("form[data-hrc3-action='address']");
      var shownAddress = document.getElementById('hrc3CurrentAddress');
      if (button) button.disabled = false;
      form.querySelectorAll('input[type="password"]').forEach(function (input) { input.value = ''; });
      if (outcome === 'signed-out') { HRC3SignIn.end('signed-out'); return; }
      self.say(form, outcome);
      if (outcome === 'password-changed') HRC3SignIn.end('password-changed');
      else if (outcome === 'address-code-sent' && confirmForm) confirmForm.hidden = false;
      else if (outcome === 'address-changed') {
        // / The change is done, so the code form goes away & the address form is emptied, & says so in its own status.
        if (shownAddress && self.pendingAddress) shownAddress.textContent = self.pendingAddress;
        form.reset();
        self.say(form, '');
        if (confirmForm) confirmForm.hidden = true;
        if (addressForm && addressForm !== form) { addressForm.reset(); self.say(addressForm, outcome); }
      }
    });
  },

  // / Begin. options carries endpoint, tokens, choices & messages. Every button & form the page drew is bound here.
  // / An account form is caught on the document as it is submitted, before anything else hears of it, so it is only ever sent by
  // / act() & never posted as a page. footer.php starts this library before the sign in library, whose own listener comes after.
  start: function (options) {
    var self = this;
    this.endpoint = options.endpoint || this.endpoint;
    this.tokens = options.tokens || this.tokens;
    this.choices = options.choices || this.choices;
    this.messages = options.messages || {};
    document.querySelectorAll('[data-hrc3-go]').forEach(function (button) {
      button.addEventListener('click', function () { self.go(button.getAttribute('data-hrc3-go'), []); });
    });
    document.addEventListener('submit', function (event) {
      var form = event.target;
      if (!form || typeof form.getAttribute !== 'function' || !form.getAttribute('data-hrc3-action')) return;
      event.preventDefault();
      event.stopImmediatePropagation();
      self.act(form);
    }, true);
  }
};
