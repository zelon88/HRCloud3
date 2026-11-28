# 🔐 ScanCore Security Policy

ScanCore exists to open files that somebody hostile wrote, which means it is built to be attacked. If you have found a way to make it miss, lie, fall over or be taken over, we want to hear about it.

> [!IMPORTANT]
> **Never report a vulnerability in a public issue, pull request or discussion.** A public report puts every installation at risk before a fix exists. Use the private channel below.

---

## ✅ Supported Versions

Security fixes are made for these two, and nothing else.

| Version | What it is | Supported |
| :--- | :--- | :---: |
| **The latest tagged release** | The newest `vX.Y.Z` tag. | ✅ |
| **Bleeding Edge** | The `master` branch, which is what `php ScanCore.php -updateapplication` installs. | ✅ |
| **Any older tagged release** | Fixes are never backported. Update to the latest tagged release to receive them. | ❌ |

A copy of ScanCore that another application bundles is covered by the policy of that application as well. Report it to either.

---

## 📬 Reporting A Vulnerability

1. Open the **Security** tab of [zelon88/ScanCore](https://github.com/zelon88/ScanCore).
2. Choose **Report a vulnerability**.
3. Describe it in the private advisory that opens. Only the maintainer and you can read it.

**Every report is accepted and read**, whatever its severity, and whether or not it carries a proof of concept.

Every part of this repository is covered: the core, the configuration file, the classifier definitions, the updater, the tools in `Documentation/Build` and the documentation. The virus definitions are published from [zelon88/ScanCore_Definitions](https://github.com/zelon88/ScanCore_Definitions), and a vulnerability in how they are built is reported there.

### 📝 A useful report says

| | |
| :--- | :--- |
| **Version** | A tag, or a commit on `master`. |
| **Installation** | The output of `php ScanCore.php -version`, where there is one. |
| **Configuration** | The entries and the switches involved, by their titles in `ScanCore_Config.php`. |
| **Reproduction** | The steps, and a proof of concept if you have one. |
| **Impact** | What an attacker gains. |

> [!TIP]
> **Send no live malware.** A file that reproduces a fault in ScanCore is nearly always a harmless one, and the smallest such file is the most useful.

---

## 🔄 How A Report Is Handled

1. The report is acknowledged in its private advisory.
2. It is reproduced against every supported version.
3. A fix is made on Bleeding Edge, and ships in the next tagged release.
4. The advisory is published once the fix has shipped. `CHANGELOG.txt` records the fix with the advisory's GHSA number.

Nothing about a vulnerability is made public before it is patched.

---

## 🏆 Credit

**Every reporter is credited publicly once the vulnerability is patched**, in the published advisory and in `CHANGELOG.txt`, under the name or handle you choose. If you would rather not be named, say so and you will not be.

---

## 🧱 The Security Model

> [!NOTE]
> **Every file is assumed to have been built to attack the scanner.** ScanCore reads each format itself, inside budgets, and runs no other program while it scans.

| ScanCore promises that | Which means |
| :--- | :--- |
| **It reads a file and changes nothing.** | A scanned file is never written to, moved, renamed or removed. |
| **It runs nothing while it scans.** | No shell, no other program and no extractor. Only an update runs another program. |
| **A header never decides how much is read.** | Every read stops at a budget, and a budget that is reached is reported. |
| **A file it could not read in full is never called clean.** | Such a file is reported on an `Incomplete:` line. |
| **A name cannot forge a line of the report.** | Every name is escaped before it is printed. |
| **One file cannot end a scan.** | A fault while one file is inspected is reported as error 640, and the scan goes on. |

A way to break one of these is a vulnerability. So is a file that makes ScanCore run code or write outside its report, a known sample that is read as clean, a file that takes more memory than the memory limit allows, and an update that installs anything from outside the repository it is configured for.

**ScanCore is not a sandbox.** An application that scans what strangers upload should run it as an account that can write nothing but its report, or inside a sandbox, as HRConvert2 and HRProtect both do. A way past ScanCore is still a vulnerability, and is still credited, even where such a sandbox contains it.

What ScanCore is known not to do is listed under `--Known Gaps--` in [DEVELOPMENT_NOTES.txt](DEVELOPMENT_NOTES.txt). A gap that is listed there is not a vulnerability by itself. A way to make one worse than it is described is.

| Read more | About |
| :--- | :--- |
| [ABOUT_SCANNING.txt](ABOUT_SCANNING.txt) | How a file is read, what is inspected and what is not. |
| [ABOUT_REPORTS.txt](ABOUT_REPORTS.txt) | What every line of output means, and what a calling program may rely on. |
| [ABOUT_QUALITY_CONTROL.txt](ABOUT_QUALITY_CONTROL.txt) | How each of these promises is tested. |
| [DEVELOPMENT_NOTES.txt](DEVELOPMENT_NOTES.txt) | Why each part is shaped the way it is, and the gaps that are known. |

The plain text source of truth for this policy is [SECURITY.txt](SECURITY.txt).
