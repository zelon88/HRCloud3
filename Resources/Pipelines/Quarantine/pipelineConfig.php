<?php
// / -----------------------------------------------------------------------------------
// / Copyright Information ...
// / HRProprietary Engine, Copyright on 11/16/2026 by Justin Grimes, www.github.com/zelon88
// /
// / License Information ...
// / This project is protected by the GNU GPLv3 Open-Source license.
// / https://www.gnu.org/licenses/gpl-3.0.html
// /
// / File Information ...
// / v4.3.1.
// / The Quarantine pipeline. It moves a file into a quarantine, lists what a quarantine holds, puts a file back & removes one.
// /
// / It is a pipeline of kind file. Moving a file out of reach is the same work whatever the file holds, which is why it declares no formats.
// / It decides nothing. Which file is quarantined, where the quarantine is & who may ask are the application's to say,
// / so the quarantine folder is handed to every operation & this pipeline names no application & no setting of one.
// /
// / <3 Open-Source
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A component may only be loaded by the core.
if (!isset($CoreLoaded) or $CoreLoaded !== TRUE) die('ERROR!!! HRConvert2-34000, A pipeline configuration cannot be loaded directly!');
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / The version this pipeline reports. Pinned in the application's contract.
$PipelineVersion = 'v4.3.1';

// / What this pipeline does. A file operation rather than a conversion or a scan.
$PipelineKind = 'file';

// / Where it may be reached from. It walks to a folder by changing the working folder of the process, which only a
// / command line process has to itself, so it is reached from a terminal or a schedule & from nowhere else.
$PipelineUsage = array('cli');

// / The family & the name shown to a user.
$PipelineFamily = 'Quarantine';
$PipelineDisplayName = 'Quarantine';

// / Chosen by name rather than by extension, so priority decides nothing here.
$PipelinePriority = 100;

// / The function the application calls for every operation.
$PipelineEntryPoint = 'operateOnQuarantine';

// / The subsystem this belongs to, for the readiness report.
$PipelineSubsystem = 'Security';

// / A file operation claims every file, so the lists are empty & that is the honest
// / declaration rather than an omission. The Pipeline Core exempts this kind from the rule
// / that a pipeline must read & write something.
$Capabilities = array(
  'Input' => array(),
  'Output' => array());

// / It takes no request fields. The caller hands it an operation, a folder & what the operation needs.
$PipelineRequestFields = array();

// / Nothing shared is needed. It uses only what the kernel already provides.
$PipelineSharedModules = array();

// / It runs no tool, so it names no dependency.
$PipelineDependencies = array();

// / Nothing is excluded.
$PipelineExclude = array();
// / -----------------------------------------------------------------------------------
?>
