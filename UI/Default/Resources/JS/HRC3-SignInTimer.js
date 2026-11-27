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
// / The timer a signed in page trades its key on, run as a worker. It was taken from HRConvert2 v4.3.4's HRC2-SignInTimer.js unchanged.
// / A browser slows a hidden tab's own timers to once a minute, & a worker's are not slowed that way, so a key is traded in time
// / while the user looks at another tab. It holds no key. It says tick, & the page does the trading.
// / It is told the seconds between ticks, & 0 stops it.
// /
// / <3 Open-Source
// / -----------------------------------------------------------------------------------

var timerHandle = null;
onmessage = function (event) {
  var seconds = Number(event.data) || 0;
  if (timerHandle) { clearInterval(timerHandle); timerHandle = null; }
  if (seconds > 0) timerHandle = setInterval(function () { postMessage('tick'); }, seconds * 1000);
};
