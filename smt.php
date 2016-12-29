#!/usr/bin/php
<?php

////////////////////////////////////////////////////////////////////////////////
//                     _                                                      //
//                    | |                                                     //
//    ____ _ __ ___  _| |_                                                    //
//   / ___| '_ ` _ \|_   _|                                                   //
//   \__ \| | | | | | | |_                                                    //
//   |___/|_| |_| |_|  \__|                                                   //
//                                                                            //
//   Social Media Tracker Utility                                             //
//   http://voicelink-velia.firewall-gateway.net/smt                          //
//   http://smt.smartlogics.net                                               //
//   Copyright (c) 2016 SmartLogics Software & Consulting GmbH                //
//                                                                            //
////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//  This program is free software: you can redistribute it and/or modify      //
//  it under the terms of the GNU General Public License as published by      //
//  the Free Software Foundation, either version 3 of the License, or         //
//  (at your option) any later version.                                       //
//                                                                            //
//  This program is distributed in the hope that it will be useful,           //
//  but WITHOUT ANY WARRANTY; without even the implied warranty of            //
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the             //
//  GNU General Public License for more details.                              //
//                                                                            //
//  You should have received a copy of the GNU General Public License         //
//  along with this program.  If not, see <http://www.gnu.org/licenses/>.     //
//                                                                            //
//  see facebook.php, JSON.php & JSON-LICENSE for additional information      //
//                                                                            //
////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//   Having said all that, the author would love you to send him:             //
//   Suggestions,  Modifications and Improvements for re-distribution.        //
//                                                                            //
//   http://smt.smartlogics.net/contribute                                    //
//                                                                            //
////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//   http://smt.smartlogics.net/history for a revision history.               //
//                                                                            //
////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//   Disclaimer: This is my first (and currently only) PHP applicaiton,       //
//               so my apologies if I don't follow PHP best practices.        //
//                                                                            //
////////////////////////////////////////////////////////////////////////////////
    
    $smtVersion = '1.2';
    
////////////////////////////////////////////////////////////////////////////////

    require_once __DIR__ . '/vendor/autoload.php';
	
////////////////////////////////////////////////////////////////////////////////
  
// you can include smt.php from another program
// see support/sample-mycmd.php for more information
  
  if (isset($smt_include)) {
    ob_end_clean(); // this avoids displaying the #!/usr/bin/php when included
    if ($smt_include_supressOutput) {
      ob_start();
    }
  } else {
    $smt_argv = $argv;
    $smt_argc = $argc;
  }
  
////////////////////////////////////////////////////////////////////////////////
  
// set the default arguments to be empty
  
  $smtCommand = '';
  $smtParams = Array();
  $smtPrefs = Array();
  
////////////////////////////////////////////////////////////////////////////////
  
// You can set an environment variable FBCMD to specify the location of
// your peronal files: sessionkeys.txt, prefs.php, postdata.txt, maildata.txt
  
// Defaults: Windows:          %USERPROFILE%\smt\ (c:\Users\YOURUSERNAME\smt\)
// Defaults: Mac/Linux/Other:  $HOME/.smt/        (~/.smt/)
  
  $smtBaseDir = getenv('SMT');
  if ($smtBaseDir) {
    $smtBaseDir = CleanPath($smtBaseDir);
  } else {
    if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') {
      if (getenv('USERPROFILE')) {
        $smtBaseDir = CleanPath(getenv('USERPROFILE')) . 'smt/';
      } else {
        $smtBaseDir = 'c:/smt/';
      }
    } else {
      $smtBaseDir = CleanPath(getenv('HOME')) . '.smt/';
    }
  }

////////////////////////////////////////////////////////////////////////////////
// This section sets your preferences
// see http://smt.smartlogics.net/preferences for more information
// STEP ONE: System Defaults
// Do NOT change these System Default preference values here:
// Modify your own prefs.php file instead
    
  AddPreference('appkey','1198793806879837');  // for testing purpose - original key '1198609853564899'
  AddPreference('appsecret','cbff68ff87ee790b807ceb104c973d0c'); // - original secret '22796ecf9f1258d3de565bcca73cbad8'
  AddPreference('access_token','');
  AddPreference('auto_mkdir','1');
  AddPreference('col', '80');
  AddPreference('client_token','7fb291c4d71851aee3a68f283d0c2d9b');
  AddPreference('dest_dir','');
  AddPreference('keyfile',"[datadir]sessionkeys.txt",'key');
  AddPreference('launch_exec','');
  AddPreference('mkdir_mode',0777);
  AddPreference('object','');
  AddPreference('prefs','');
  AddPreference('print_csv','0','csv');
  AddPreference('quiet','0','q');
  AddPreference('show_id','0','id');
  AddPreference('trace','0','t');
  AddPreference('update_branch','master');

  // Parameter Defaults
  AddPreference('default_addperm','create_event,friends_about_me,friends_activities,friends_birthday,friends_checkins,friends_education_history,friends_events,friends_groups,friends_hometown,friends_interests,friends_likes,friends_location,friends_notes,friends_online_presence,friends_photo_video_tags,friends_photos,friends_relationship_details,friends_relationships,friends_religion_politics,friends_status,friends_videos,friends_website,friends_work_history,manage_friendlists,manage_pages,offline_access,publish_checkins,publish_stream,read_friendlists,read_mailbox,read_requests,read_stream,rsvp_event,user_about_me,user_activities,user_birthday,user_checkins,user_education_history,user_events,user_groups,user_hometown,user_interests,user_likes,user_location,user_notes,user_online_presence,user_photo_video_tags,user_photos,user_relationship_details,user_relationships,user_religion_politics,user_status,user_videos,user_website,user_work_history');

  // STEP TWO: Load preferences from prefs.php in the base directory
  
  if (file_exists("{$smtBaseDir}prefs.php")) {
    include("{$smtBaseDir}prefs.php");
  }
  
  // STEP THREE: Read switches set from the command line
  // This also sets $smtCommand & $smtParams
  
  ParseArguments($smt_argv,$smt_argc);
  
  // STEP FOUR: if a "--prefs=filename.php" was specified
  
  if ($smtPrefs['prefs']) {
    if (file_exists($smtPrefs['prefs'])) {
      include($fsmtPrefs['prefs']);
    } else {
      SmtWarning("Could not load Preferences file {$smtPrefs['prefs']}");
    }
  }

////////////////////////////////////////////////////////////////////////////////
  
  $smtCommandList = array();

  AddCommand('AUTH',      'authcode~Sets your facebook authorization code for offline access');
  AddCommand('GO',        'destination [id]~Launches a web browser for the given destination');
  AddCommand('HELP',      '[command|preference]~Display this help message, or launch web browser for [command]');
  AddCommand('HOME',      '[webpage]~Launch a web browser to visit the FBCMD home page');
  AddCommand('RESET',     '<no parameters>~Reset any authorization codes set by AUTH');
  AddCommand('UPDATE',    '[branch] [dir] [trace] [ignore_err]~Update FBCMD to the latest version');
  AddCommand('USAGE',     '(same as HELP)');
  AddCommand('VERSION',   '[branch]~Check for the latest version of FBCMD available');
  AddCommand('WHOAMI',    '<no parameters>~Display the currently authorized user');
  
  if (isset($smt_include_newCommands)) {
    foreach ($smt_include_newCommands as $c) {
      AddCommand($c[0],$c[1]);
    }
  }

////////////////////////////////////////////////////////////////////////////////
  
  if (!in_array($smtCommand,$smtCommandList)&&($smtCommand != '')) {
    SmtFatalError("Unknown Command: [{$smtCommand}] try smt HELP");
  }
  
////////////////////////////////////////////////////////////////////////////////
  
  if (($smtCommand == 'HELP')||($smtCommand == 'USAGE')) {
    ValidateParamCount(0,1);
    if (ParamCount() == 0) {
      displayHelp();
    }
    if (in_array(strtoupper($smtParams[1]),$smtCommandList)) {
      LaunchBrowser('http://smt.smartlogics.net/commands/' . strtolower($smtParams[1]));
      return;
    }
    if (isset($smtPrefs[$smtParams[1]])) {
      LaunchBrowser('http://smt.smartlogics.net/preferences/' . strtolower($smtParams[1]));
      return;
    }
    SmtWarning("HELP: did not recognize [{$smtParams[1]}]");
    displayHelp();
  }

////////////////////////////////////////////////////////////////////////////////
  
  if ($smtCommand == 'UPDATE') {
    ValidateParamCount(0,4);
    $updatePhp = CleanPath(realpath(dirname($argv[0]))) . 'smt_update.php';
    if (!file_exists($updatePhp)) {
      $updatePhpAlt = CleanPath(realpath($smtPrefs['install_dir'])) . 'smt_update.php';
      if (file_exists($updatePhpAlt)) {
        $updatePhp = $updatePhpAlt;
      } else {
        FbcmdFatalError("Could not locate [{$updatePhp}]");
      }
    }
    $execCmd = "php \"$updatePhp\"";
    if (ParamCount() >= 1) {
      $execCmd .= " \"{$smtParams[1]}\"";
    }
    if (ParamCount() >= 2) {
      $execCmd .= " \"{$smtParams[2]}\"";
    }
    if (ParamCount() >= 3) {
      $execCmd .= " {$smtParams[3]}";
    }
    if (ParamCount() >= 4) {
      $execCmd .= " {$smtParams[4]}";
    }
    passthru($execCmd);
    return;
  }
  
////////////////////////////////////////////////////////////////////////////////
  
  if ($smtCommand == 'HOME') {
    ValidateParamCount(0,1);
    SetDefaultParam(1,'');
    LaunchBrowser('http://smt.smartlogics.net/' . strtolower($smtParams[1]));
  }
  
////////////////////////////////////////////////////////////////////////////////
  $urlAuth = "http://www.facebook.com/code_gen.php?v=1.0&api_key={$smtPrefs['appkey']}";
  $urlAccess = "https://www.facebook.com/dialog/oauth?client_id={$smtPrefs['appkey']}&redirect_uri=http://www.facebook.com/connect/login_success.html";

  $goDestinations = array();
  $goDestinationsHelp = array();
  $goDestinationsUrl = array();
  
  AddGoDestination('access',      'Allow smt to (initially) access your account',$urlAccess);
  AddGoDestination('album',       '#An album from the ALBUM command');
  AddGoDestination('app',         'The smt page on facebook','http://facebook.com/smt');
  AddGoDestination('auth',        'Authorize smt for permanent access',$urlAuth);
  AddGoDestination('contribute',  'The smt contact page','http://smt.smartlogics.net/contribute');
  AddGoDestination('editapps',    'The facebook edit applications page','http://www.facebook.com/editapps.php');
  AddGoDestination('event',       '#An event from the EVENT command');
  AddGoDestination('faq',         'The smt FAQ','http://smt.smartlogics.net/faq');
  AddGoDestination('friend.name', 'The facebook page of your friend...uses status tagging','http://smt.smartlogics.net/faq');
  AddGoDestination('github',      'The source repository at github','http://github.com/dtompkins/smt');
  AddGoDestination('group',       'The smt discussion group','http://groups.google.com/group/smt');
  AddGoDestination('help',        'the smt help page','http://smt.smartlogics.net/help');
  AddGoDestination('home',        'The smt home page','http://smt.smartlogics.net');
  AddGoDestination('inbox',       'Your facebook inbox','http://www.facebook.com/inbox');
  AddGoDestination('install',     'The smt installation page','http://smt.smartlogics.net/installation');
  AddGoDestination('link',        '#A link from a post from the STREAM command');
  AddGoDestination('msg',         '#A mail thread from he INBOX command');
  AddGoDestination('notice',      '#A notice from the NOTICES command');
  AddGoDestination('post',        '#A post from the STREAM command');
  AddGoDestination('stream',      'Your facebook home page','http://www.facebook.com/home.php');
  AddGoDestination('update',      'The smt update page','http://smt.smartlogics.net/update');
  AddGoDestination('wall',        'Your facebook profile');
  AddGoDestination('wiki',        'The smt wiki','http://smt.smartlogics.net');
  AddGoDestination('a',           '#shortcut for [album]');
  AddGoDestination('e',           '#shortcut for [event]');
  AddGoDestination('m',           '#shortcut for [msg]');
  AddGoDestination('n',           '#shortcut for [notice]');
  AddGoDestination('p',           '#shortcut for [post]');
  AddGoDestination('l',           '#shortcut for [link]');

  if ($smtCommand == 'GO') {
    if (ParamCount() == 0) {
      print "\nGO Destinations:\n\n";
      foreach ($goDestinations as $key) {
        $desc = $goDestinationsHelp[$key];
        if (substr($desc,0,1) == "#") {
          print str_pad("  go {$key} id",19,' ') . substr($desc,1) . "\n";
        } else {
          print str_pad("  go {$key}",19,' ') . $desc . "\n";
        }
      }
      print "\n";
      return;
    } else {
      if (isset($goDestinationsUrl[strtolower($smtParams[1])])) {
        LaunchBrowser($goDestinationsUrl[strtolower($smtParams[1])]);
        return;
      }
    }
  }
  
  ////////////////////////////////////////////////////////////////////////////////
  
  require_once('facebook/facebook.php');
  require_once('facebook/facebook_device.php');
  
  ////////////////////////////////////////////////////////////////////////////////
  
  $smtKeyFileName = str_replace('[datadir]',$smtBaseDir,$smtPrefs['keyfile']);
  
  if ($smtCommand == 'RESET') {
    ValidateParamCount(0);
    VerifyOutputDir($smtKeyFileName);
    if (@file_put_contents($smtKeyFileName,"EMPTY\nEMPTY\n# only the first two lines of this file are read\n# use smt RESET to replace this file\n") == false) {
      SmtFatalError("Could not generate keyfile {$smtKeyFileName}");
    }
    if (!$smtPrefs['quiet']) {
      print "keyfile {$smtKeyFileName} has been RESET\n";
    }
  }

////////////////////////////////////////////////////////////////////////////////
  
  if ($smtCommand == 'AUTH') {
    ValidateParamCount(1);
      
/*
    try {
        $fbDevice = new FacebookDevice($smtPrefs['appkey'], $smtPrefs['client_key']);
        $fbReturn = $fbDevice->login();
        var_dump($fbReturn);
    } catch (Exception $e) {
        SmtException($e,'Invalid AUTH code / could not authorize session');
    }
/*
    try {
      $fbObject = new FacebookDesktop($smtPrefs['appkey'], $smtPrefs['appsecret']);
      $session = $fbObject->do_get_session($smtParams[1]);
      TraceReturn($session);
    } catch (Exception $e) {
      SmtException($e,'Invalid AUTH code / could not authorize session');
    }
*/
    $smtUserSessionKey = 'SESSION_KEY'; //$session['session_key'];
    $smtUserSecretKey = $smtPrefs['access_token']; //$session['secret'];
    VerifyOutputDir($smtKeyFileName);
    if (@file_put_contents ($smtKeyFileName,"{$smtUserSessionKey}\n{$smtUserSecretKey}\n# only the first two lines of this file are read\n# use smt RESET to replace this file\n") == false) {
      FbcmdFatalError("Could not generate keyfile {$smtKeyFileName}");
    }
    try {
      $fbReturn = $smtUserSecretKey;
      TraceReturn($fbReturn);
    } catch (Exception $e) {
      FbcmdException($e,'Invalid AUTH code / could not generate session key');
    }
    if (!$smtPrefs['quiet']) {
      print "\nsmt [v$smtVersion] AUTH Code accepted.\nWelcome to SMT!"; //, {$fbReturn[0]['name']}!\n\n";
      print "most SMT commands require additional permissions.\n";
      print "to grant default permissions, execute: smt addperm\n";
    }
    return;
  }
  
////////////////////////////////////////////////////////////////////////////////
  
  if (!file_exists($smtKeyFileName)) {
    print "\n";
    print "Welcome to SMT! [version $smtVersion]\n\n";
    //print "It appears to be the first time you are running the application\n";
    //print "as smt could not locate your keyfile: [{$smtKeyFileName}]\n\n";
    ShowAuth();
    return;
  }
  
////////////////////////////////////////////////////////////////////////////////
  
  $smtKeyFile = file($smtKeyFileName,FILE_IGNORE_NEW_LINES);
  if (count($smtKeyFile) < 2) {
    SmtFatalError("Invalid keyfile {$smtKeyFileName}");
  }
  $smtUserSessionKey = $smtKeyFile[0];
  $smtUserSecretKey = $smtKeyFile[1];
  
  if (strncmp($smtUserSessionKey,'EMPTY',5) == 0) {
    ShowAuth();
    return;
  }
  
  ////////////////////////////////////////////////////////////////////////////////
  
  // create the Facebook Object
  /*
  try {
    $fbObject = new FacebookDesktop($smtPrefs['appkey'], $smtPrefs['appsecret']);
    $fbObject->api_client->session_key = $smtUserSessionKey;
    $fbObject->secret = $smtUserSecretKey;
    $fbObject->api_client->secret = $smtUserSecretKey;
    $fbUser = $fbObject->api_client->users_getLoggedInUser();
  } catch (Exception $e) {
    SmtException($e,'Could not use session key / log in user');
  }
  */
  
////////////////////////////////////////////////////////////////////////////////

  $allPermissions = 'ads_management,create_event,email,friends_about_me,friends_activities,friends_birthday,friends_checkins,friends_education_history,friends_events,friends_groups,friends_hometown,friends_interests,friends_likes,friends_location,friends_notes,friends_online_presence,friends_photo_video_tags,friends_photos,friends_relationship_details,friends_relationships,friends_religion_politics,friends_status,friends_videos,friends_website,friends_work_history,manage_friendlists,manage_pages,offline_access,publish_checkins,publish_stream,read_friendlists,read_insights,read_mailbox,read_requests,read_stream,rsvp_event,sms,user_about_me,user_activities,user_birthday,user_checkins,user_education_history,user_events,user_groups,user_hometown,user_interests,user_likes,user_location,user_notes,user_online_presence,user_photo_video_tags,user_photos,user_relationship_details,user_relationships,user_religion_politics,user_status,user_videos,user_website,user_work_history,xmpp_login';

  if ($smtCommand == 'ADDPERM') {
    ValidateParamCount(0,1);
    SetDefaultParam(1, $smtPrefs['default_addperm']);
    if (strtoupper($smtParams[1]) == 'ALL') {
      $smtParams[1] = $allPermissions;
    }
    $url = "{$urlAccess}&scope={$smtParams[1]}";
    print "launching: $url\n";
    LaunchBrowser($url);
  }

////////////////////////////////////////////////////////////////////////////////
  
  process(is_array($smt_argv) ? $smt_argv : array());
	
	/**
	 * processes the downloader
	 */
	function process($argv)
	{
		// Determine ANSI output from --ansi and --no-ansi flags
		setUseAnsi($argv);
		
		if (in_array('--help', $argv)) {
			displayHelp();
			exit(0);
		}

        $auth        = in_array('--auth', $argv);
		$check       = in_array('--check', $argv);
		$help        = in_array('--help', $argv);
		$force       = in_array('--force', $argv);
		$quiet       = in_array('--quiet', $argv);
		$channel     = in_array('--snapshot', $argv) ? 'snapshot' : (in_array('--preview', $argv) ? 'preview' : 'stable');
		$disableTls  = in_array('--disable-tls', $argv);
		$destDir     = getOptValue('--dest-dir', $argv, '..');
		$version     = getOptValue('--version', $argv, false);
		$object      = getOptValue('--object', $argv, 'me');
		$accessToken = getOptValue('--access-token', $argv, $GLOBALS['smtUserSecretKey']);
        $partName    = getOptValue('--part', $argv, false);
		$cafile      = getOptValue('--cafile', $argv, false);
		
		if (!checkParams($destDir, $version, $accessToken)) {
			exit(1);
		}
		
		$ok = checkPlatform($warnings, $quiet, $disableTls);
		
		if ($check) {
			// Only show warnings if we haven't output any errors
			if ($ok) {
				showWarnings($warnings);
				showSecurityWarning($disableTls);
			}
			exit($ok ? 0 : 1);
		}
		
		if ($ok || $force) {
			setTimezone("UTC");
			download($version, $destDir, $object, $partName, $quiet, $disableTls, $accessToken, $channel);
			showWarnings($warnings);
			showSecurityWarning($disableTls);
			exit(0);
		}
		
		exit(1);
	}
	
	/**
	 * displays the help
	 */
	function displayHelp()
	{
		echo <<<EOF
		Facebook SMT Downloader
		------------------
		Options
		--help               this help
		--check              for checking environment only
		--force              forces the installation
		--ansi               force ANSI color output
		--no-ansi            disable ANSI color output
		--quiet              do not output unimportant messages
		--dest-dir="..."     accepts a target download directory
		--preview            install the latest version from the preview (alpha/beta/rc) channel instead of stable
		--snapshot           install the latest version from the snapshot (dev builds) channel instead of stable
		--version="..."      accepts a specific version to install instead of the latest
		--object="..."       accepts a source object (default: me)
		--access-token="..." accepts a Access Token string for user request authorization
        --parts="..."        accepts a specific part of the facebook object graph
            
			deprecated:
			
		--disable-tls        disable SSL/TLS security for file downloads
		--cafile="..."       accepts a path to a Certificate Authority (CA) certificate file for SSL/TLS verification
EOF;
	}

////////////////////////////////////////////////////////////////////////////////
  
  function displayHelpCmd($cmd) {
    global $smtCommandList;
    global $smtCommandHelp;
    
    if (!isset($smtCommandHelp[$cmd])) {
      $smtCommandHelp[$cmd] = "[No Help Available]\n";
    }
    $helpText = explode('~',$smtCommandHelp[$cmd]);
    print "  " . str_pad($cmd, 10, ' ') . $helpText[0]. "\n";
    for ($j=1; $j < count($helpText); $j++) {
      print "            " . $helpText[$j] . "\n";
    }
    print "\n";
  }
	
////////////////////////////////////////////////////////////////////////////////
  /**
	 * Sets the USE_ANSI define for colorizing output
	 *
	 * @param array $argv Command-line arguments
	 */
	function setUseAnsi($argv)
	{
		// --no-ansi wins over --ansi
		if (in_array('--no-ansi', $argv)) {
			define('USE_ANSI', false);
		} elseif (in_array('--ansi', $argv)) {
			define('USE_ANSI', true);
		} else {
			// On Windows, default to no ANSI, except in ANSICON and ConEmu.
			// Everywhere else, default to ANSI if stdout is a terminal.
			define('USE_ANSI',
				   (DIRECTORY_SEPARATOR == '\\')
				   ? (false !== getenv('ANSICON') || 'ON' === getenv('ConEmuANSI'))
				   : (function_exists('posix_isatty') && posix_isatty(1))
			);
		}
	}
	
	/**
	 * Returns the value of a command-line option
	 *
	 * @param string $opt The command-line option to check
	 * @param array $argv Command-line arguments
	 * @param mixed $default Default value to be returned
	 *
	 * @return mixed The command-line value or the default
	 */
	function getOptValue($opt, $argv, $default)
	{
		$optLength = strlen($opt);
		
		foreach ($argv as $key => $value) {
			$next = $key + 1;
			if (0 === strpos($value, $opt)) {
				if ($optLength === strlen($value) && isset($argv[$next])) {
					return trim($argv[$next]);
				} else {
					return trim(substr($value, $optLength + 1));
				}
			}
		}
		
		return $default;
	}
	
	/**
	 * Checks that user-supplied params are valid
	 *
	 * @param mixed $destDir The required istallation directory
	 * @param mixed $version The required composer version to install
	 * @param mixed $accessToken Certificate Authority file
	 *
	 * @return bool True if the supplied params are okay
	 */
	function checkParams($destDir, $version, $accessToken)
	{
		$result = true;
		
		if (false !== $destDir && !is_dir($destDir)) {
			out("The defined install dir ({$destDir}) does not exist.", 'info');
			$result = false;
		}
		
		if (false !== $version && 1 !== preg_match('/^\d+\.\d+\.\d+(\-(alpha|beta)\d+)*$/', $version)) {
			out("The defined install version ({$version}) does not match release pattern.", 'info');
			$result = false;
		}
		
		/*
		if (false !== $cafile && (!file_exists($cafile) || !is_readable($cafile))) {
			out("The defined Certificate Authority (CA) cert file ({$cafile}) does not exist or is not readable.", 'info');
			$result = false;
		}
		*/
		if (false !== $accessToken && !validateAccessToken($accessToken)) {
			out("The defined Access Token (${accessToken}) is not valid.", 'info');
		}
		
		return $result;
	}
	
	/**
	 * Checks the platform for possible issues running Composer
	 *
	 * Errors are written to the output, warnings are saved for later display.
	 *
	 * @param array $warnings Populated by method, to be shown later
	 * @param bool $quiet Quiet mode
	 * @param bool $disableTls Bypass tls
	 *
	 * @return bool True if there are no errors
	 */
	function checkPlatform(&$warnings, $quiet, $disableTls)
	{
		getPlatformIssues($errors, $warnings);
		
		// Make openssl warning an error if tls has not been specifically disabled
		if (isset($warnings['openssl']) && !$disableTls) {
			$errors['openssl'] = $warnings['openssl'];
			unset($warnings['openssl']);
		}
		
		if (!empty($errors)) {
			out('Some settings on your machine make Downloader unable to work properly.', 'error');
			out('Make sure that you fix the issues listed below and run this script again:', 'error');
			outputIssues($errors);
			return false;
		}
		
		if (empty($warnings) && !$quiet) {
			out('All settings correct for using Downloader', 'success');
		}
		return true;
	}
	
	/**
	 * Checks platform configuration for common incompatibility issues
	 *
	 * @param array $errors Populated by method
	 * @param array $warnings Populated by method
	 *
	 * @return bool If any errors or warnings have been found
	 */
	function getPlatformIssues(&$errors, &$warnings)
	{
		$errors = array();
		$warnings = array();
		
		if ($iniPath = php_ini_loaded_file()) {
			$iniMessage = PHP_EOL.'The php.ini used by your command-line PHP is: ' . $iniPath;
		} else {
			$iniMessage = PHP_EOL.'A php.ini file does not exist. You will have to create one.';
		}
		$iniMessage .= PHP_EOL.'If you can not modify the ini file, you can also run `php -d option=value` to modify ini values on the fly. You can use -d multiple times.';
		
		if (ini_get('detect_unicode')) {
			$errors['unicode'] = array(
            'The detect_unicode setting must be disabled.',
            'Add the following to the end of your `php.ini`:',
            '    detect_unicode = Off',
            $iniMessage
									   );
		}
		
		if (extension_loaded('suhosin')) {
			$suhosin = ini_get('suhosin.executor.include.whitelist');
			$suhosinBlacklist = ini_get('suhosin.executor.include.blacklist');
			if (false === stripos($suhosin, 'phar') && (!$suhosinBlacklist || false !== stripos($suhosinBlacklist, 'phar'))) {
				$errors['suhosin'] = array(
										   'The suhosin.executor.include.whitelist setting is incorrect.',
										   'Add the following to the end of your `php.ini` or suhosin.ini (Example path [for Debian]: /etc/php5/cli/conf.d/suhosin.ini):',
										   '    suhosin.executor.include.whitelist = phar '.$suhosin,
										   $iniMessage
										   );
			}
		}
		
		if (!function_exists('json_decode')) {
			$errors['json'] = array(
									'The json extension is missing.',
									'Install it or recompile php without --disable-json'
									);
		}
		
		if (!extension_loaded('Phar')) {
			$errors['phar'] = array(
									'The phar extension is missing.',
									'Install it or recompile php without --disable-phar'
									);
		}
		
		if (!extension_loaded('filter')) {
			$errors['filter'] = array(
									  'The filter extension is missing.',
									  'Install it or recompile php without --disable-filter'
									  );
		}
		
		if (!extension_loaded('hash')) {
			$errors['hash'] = array(
									'The hash extension is missing.',
									'Install it or recompile php without --disable-hash'
									);
		}
		
		if (!extension_loaded('iconv') && !extension_loaded('mbstring')) {
			$errors['iconv_mbstring'] = array(
											  'The iconv OR mbstring extension is required and both are missing.',
											  'Install either of them or recompile php without --disable-iconv'
											  );
		}
		
		if (!ini_get('allow_url_fopen')) {
			$errors['allow_url_fopen'] = array(
											   'The allow_url_fopen setting is incorrect.',
											   'Add the following to the end of your `php.ini`:',
											   '    allow_url_fopen = On',
											   $iniMessage
											   );
		}
		
		if (extension_loaded('ionCube Loader') && ioncube_loader_iversion() < 40009) {
			$ioncube = ioncube_loader_version();
			$errors['ioncube'] = array(
            'Your ionCube Loader extension ('.$ioncube.') is incompatible with Phar files.',
            'Upgrade to ionCube 4.0.9 or higher or remove this line (path may be different) from your `php.ini` to disable it:',
            '    zend_extension = /usr/lib/php5/20090626+lfs/ioncube_loader_lin_5.3.so',
            $iniMessage
									   );
		}
		
		if (version_compare(PHP_VERSION, '5.3.2', '<')) {
			$errors['php'] = array(
								   'Your PHP ('.PHP_VERSION.') is too old, you must upgrade to PHP 5.3.2 or higher.'
								   );
		}
		
		if (version_compare(PHP_VERSION, '5.3.4', '<')) {
			$warnings['php'] = array(
									 'Your PHP ('.PHP_VERSION.') is quite old, upgrading to PHP 5.3.4 or higher is recommended.',
									 'Composer works with 5.3.2+ for most people, but there might be edge case issues.'
									 );
		}
		
		if (!extension_loaded('openssl')) {
			$warnings['openssl'] = array(
										 'The openssl extension is missing, which means that secure HTTPS transfers are impossible.',
										 'If possible you should enable it or recompile php with --with-openssl'
										 );
		}
		
		if (extension_loaded('openssl') && OPENSSL_VERSION_NUMBER < 0x1000100f) {
			// Attempt to parse version number out, fallback to whole string value.
			$opensslVersion = trim(strstr(OPENSSL_VERSION_TEXT, ' '));
			$opensslVersion = substr($opensslVersion, 0, strpos($opensslVersion, ' '));
			$opensslVersion = $opensslVersion ? $opensslVersion : OPENSSL_VERSION_TEXT;
			
			$warnings['openssl_version'] = array(
												 'The OpenSSL library ('.$opensslVersion.') used by PHP does not support TLSv1.2 or TLSv1.1.',
												 'If possible you should upgrade OpenSSL to version 1.0.1 or above.'
												 );
		}
		
		if (!defined('HHVM_VERSION') && !extension_loaded('apcu') && ini_get('apc.enable_cli')) {
			$warnings['apc_cli'] = array(
										 'The apc.enable_cli setting is incorrect.',
										 'Add the following to the end of your `php.ini`:',
										 '    apc.enable_cli = Off',
										 $iniMessage
										 );
		}
		
		if (extension_loaded('xdebug')) {
			$warnings['xdebug_loaded'] = array(
											   'The xdebug extension is loaded, this can slow down Composer a little.',
											   'Disabling it when using Composer is recommended.'
											   );
			
			if (ini_get('xdebug.profiler_enabled')) {
				$warnings['xdebug_profile'] = array(
													'The xdebug.profiler_enabled setting is enabled, this can slow down Composer a lot.',
													'Add the following to the end of your `php.ini` to disable it:',
													'    xdebug.profiler_enabled = 0',
													$iniMessage
													);
			}
		}
		
		if (!extension_loaded('zlib')) {
			$warnings['zlib'] = array(
									  'The zlib extension is not loaded, this can slow down Composer a lot.',
									  'If possible, install it or recompile php with --with-zlib',
									  $iniMessage
									  );
		}
		
		ob_start();
		phpinfo(INFO_GENERAL);
		$phpinfo = ob_get_clean();
		if (preg_match('{Configure Command(?: *</td><td class="v">| *=> *)(.*?)(?:</td>|$)}m', $phpinfo, $match)) {
			$configure = $match[1];
			
			if (false !== strpos($configure, '--enable-sigchild')) {
				$warnings['sigchild'] = array(
											  'PHP was compiled with --enable-sigchild which can cause issues on some platforms.',
											  'Recompile it without this flag if possible, see also:',
											  '    https://bugs.php.net/bug.php?id=22999'
											  );
			}
			
			if (false !== strpos($configure, '--with-curlwrappers')) {
				$warnings['curlwrappers'] = array(
												  'PHP was compiled with --with-curlwrappers which will cause issues with HTTP authentication and GitHub.',
												  'Recompile it without this flag if possible'
												  );
			}
		}
		
		// Stringify the message arrays
		foreach ($errors as $key => $value) {
			$errors[$key] = PHP_EOL.implode(PHP_EOL, $value);
		}
		
		foreach ($warnings as $key => $value) {
			$warnings[$key] = PHP_EOL.implode(PHP_EOL, $value);
		}
		
		return !empty($errors) || !empty($warnings);
	}
	
	
	/**
	 * Outputs an array of issues
	 *
	 * @param array $issues
	 */
	function outputIssues($issues)
	{
		foreach ($issues as $issue) {
			out($issue, 'info');
		}
		out('');
	}
	
	/**
	 * Outputs any warnings found
	 *
	 * @param array $warnings
	 */
	function showWarnings($warnings)
	{
		if (!empty($warnings)) {
			out('Some settings on your machine may cause stability issues with Composer.', 'error');
			out('If you encounter issues, try to change the following:', 'error');
			outputIssues($warnings);
		}
	}
	
	/**
	 * Outputs an end of process warning if tls has been bypassed
	 *
	 * @param bool $disableTls Bypass tls
	 */
	function showSecurityWarning($disableTls)
	{
		if ($disableTls) {
			out('You have instructed the Downloader not to enforce SSL/TLS security on remote HTTPS requests.', 'info');
			out('This will leave all downloads during installation vulnerable to Man-In-The-Middle (MITM) attacks', 'info');
		}
	}
	
	/**
	 * installs composer to the current working directory
	 */
	function download($version, $destDir, $object, $partName, $quiet, $disableTls, $accessToken, $channel)
	{
        global $smtPrefs;
        
        $destPath = (is_dir($destDir) ? rtrim($destDir, '/').'/' : '') . $object;
		$destDir  = realpath($destDir) ? realpath($destDir) : getcwd();
		$dir      = $destDir.DIRECTORY_SEPARATOR.$object;
		
		if (!is_dir($dir)) {
			#			@unlink($dir);
			@mkdir($dir, 0777, true);
		}
		
		$home = getHomeDir();
		
		if (!is_dir($home)) {
			@mkdir($home, 0777, true);
		}

		$facebook = new Facebook\Facebook([
										  'app_id' => $smtPrefs['appkey'],
										  'app_secret' => $smtPrefs['appsecret'],
										  'default_graph_version' => 'v2.8',
										  ]);
		
		$helper = $facebook->getCanvasHelper();
		
		if (! isset($accessToken) || $accessToken === false) {
			try {
				$accessToken = $helper->getAccessToken();
			} catch(Facebook\Exceptions\FacebookResponseException $e) {
				// When Graph returns an error
				echo 'Graph returned an error: ' . $e->getMessage();
				exit;
			} catch(Facebook\Exceptions\FacebookSDKException $e) {
				// When validation fails or other local issues
				echo 'Facebook SDK returned an error: ' . $e->getMessage();
				exit;
			}
		}
		
		if (! isset($accessToken) || $accessToken === false) {
			echo 'No OAuth data could be obtained from the signed request. User has not authorized your app yet.';
		}

		// Logged in
		$facebook->setDefaultAccessToken($accessToken);

		$retries = 3;
		while ($retries--) {
			if (!$quiet) {
				out("Downloading $version...", 'info');
			}
			
			$errorHandler = new ErrorHandler();
			set_error_handler(array($errorHandler, 'handleError'));
			
            if (!$quiet) {
            
                $url       = "/{$object}";
                $resp = $facebook->get($url);
                
                #var_dump($resp);
                if (! isset($resp)) {
                    continue;
                }
                $obj = $resp->getGraphEdge();
                
                out("  from object '{$obj['name']} (ID: {$obj['id']})...", 'info');
            }

            if (!partName) {
                downloadParts($facebook, $errorHandler, $destDir, $object, 'posts', $quiet) || continue;
                downloadParts($facebook, $errorHandler, $destDir, $object, 'feed', $quiet) || continue;
                downloadParts($facebook, $errorHandler, $destDir, $object, 'likes', $quiet) || continue;
            }
            else {
                downloadParts($facebook, $errorHandler, $destDir, $object, $partName) || continue;
            }
			
			break;
		}
		
		if ($errorHandler->message) {
			out('The download failed repeatedly, aborting.', 'error');
			var_dump($errorHandler->message);
			exit(1);
		}
		
		#chmod($file, 0755);
		
		if (!$quiet) {
			out(PHP_EOL."Downloader successfully proceed to: " . $object, 'success', false);
			#out(PHP_EOL."Use it: php $destPath", 'info');
		}
	}
	
    function downloadParts($facebook, $errorHandler, $destDir, $object, $partName, $quiet, $limit = 5) {
        $targetDir = $destDir.DIRECTOR_SEPARATOR.$object.DIRECTOR_SEPARATOR.$partName;
        if (!is_dir($targetDir)) {
            #			@unlink($dir);
            @mkdir($targetDir, 0777, true);
        }
        
        if (!$quiet) {
            out("  Recieving /{$partName} (package size: {$limit})...", 'info');
        }

        $url       = "/{$object}/{$partName}?limit={$limit}";
        $resp = $facebook->get($url);
        
        #var_dump($resp);
        if (! isset($resp)) {
            return false;
        }
        $parts = $resp->getGraphEdge();
        $i=0;
        do {
            foreach ($parts as $part) {
                $time = $part['created_time']->format(DateTime::COOKIE);
                #echo "<h3>{$time}</h3>";
                #if(isset($post['message']) && $post['message']) echo "<p>".make_links($post['message'])."</p>";
                #if(isset($post['story']) && $post['story']) echo "<p>".make_links($post['story'])."</p>";
                
                $file = $targetDir.DIRECTORY_SEPARATOR.$part['id'].'.txt';
                
                if (is_readable($file)){
                    #@unlink($file);
                    continue;
                }
                $fh = fopen($file, 'w');
                if (!$fh) {
                    out('Could not create file '.$file.': '.$errorHandler->message, 'error');
                }
                if (!fwrite($fh, (in_array('story', $part->getPropertyNames()) ? $part['story'].'---'."\r\n" : '').(in_array('message', $part->getPropertyNames()) ? $part['message'] : ' - no content - '))) {
                    out('Download failed: '.$errorHandler->message, 'error');
                }
                fclose($fh);
                
                if($i !== count($parts)-1){
                    #echo '<hr>';
                }
                if (!$quiet) {
                    echo ".";
                }
                
                $i++;
            }
        } while ($parts = $facebook->next($parts));
        
        return true;
    }
    
	/**
	 * convert links
	 */
	function make_links($text, $class='', $target='_blank'){
		return preg_replace('!((http\:\/\/|ftp\:\/\/|https\:\/\/)|www\.)([-a-zA-Zа-яА-Я0-9\~\!\@\#\$\%\^\&\*\(\)_\-\=\+\\\/\?\.\:\;\'\,]*)?!ism', '<a class="'.$class.'" href="//$3" target="'.$target.'">$1$3</a>', $text);
	}

	/**
	 * colorize output
	 */
	function out($text, $color = null, $newLine = true)
	{
		$styles = array(
						'success' => "\033[0;32m%s\033[0m",
						'error' => "\033[31;31m%s\033[0m",
						'info' => "\033[33;33m%s\033[0m"
						);
		
		$format = '%s';
		
		if (isset($styles[$color]) && USE_ANSI) {
			$format = $styles[$color];
		}
		
		if ($newLine) {
			$format .= PHP_EOL;
		}
		
		printf($format, $text);
	}
	
	function setTimezone($default) {
		$timezone = "";
		
		// On many systems (Mac, for instance) "/etc/localtime" is a symlink
		// to the file with the timezone info
		if (is_link("/etc/localtime")) {
			
			// If it is, that file's name is actually the "Olsen" format timezone
			$filename = readlink("/etc/localtime");
			
			$pos = strpos($filename, "zoneinfo");
			if ($pos) {
				// When it is, it's in the "/usr/share/zoneinfo/" folder
				$timezone = substr($filename, $pos + strlen("zoneinfo/"));
			} else {
				// If not, bail
				$timezone = $default;
			}
		}
		else {
			// On other systems, like Ubuntu, there's file with the Olsen time
			// right inside it.
			$timezone = file_get_contents("/etc/timezone");
			if (!strlen($timezone)) {
				$timezone = $default;
			}
		}
		date_default_timezone_set($timezone);
	}
	
	/**
	 * Returns the system-dependent Composer home location, which may not exist
	 *
	 * @return string
	 */
	function getHomeDir()
	{
		$home = getenv('SMT_HOME');
		
		if (!$home) {
			$userDir = getUserDir();
			
			if (defined('PHP_WINDOWS_VERSION_MAJOR')) {
				$home = $userDir.'/SocialMediaTracker';
			} else {
				$home = $userDir.'/.smt';
				
				if (!is_dir($home) && useXdg()) {
					// XDG Base Directory Specifications
					if (!($xdgConfig = getenv('XDG_CONFIG_HOME'))) {
						$xdgConfig = $userDir.'/.config';
					}
					$home = $xdgConfig.'/smt';
				}
			}
		}
		return $home;
	}
	
	/**
	 * Returns the location of the user directory from the environment
	 * @throws Runtime Exception If the environment value does not exists
	 *
	 * @return string
	 */
	function getUserDir()
	{
		$userEnv = defined('PHP_WINDOWS_VERSION_MAJOR') ? 'APPDATA' : 'HOME';
		$userDir = getenv($userEnv);
		
		if (!$userDir) {
			throw new RuntimeException('The '.$userEnv.' or SMT_HOME environment variable must be set for composer to run correctly');
		}
		
		return rtrim(strtr($userDir, '\\', '/'), '/');
	}
	
	/**
	 * @return bool
	 */
	function useXdg()
	{
		foreach (array_keys($_SERVER) as $key) {
			if (substr($key, 0, 4) === 'XDG_') {
				return true;
			}
		}
		return false;
	}
	
	function validateCaFile($contents)
	{
		// assume the CA is valid if php is vulnerable to
		// https://www.sektioneins.de/advisories/advisory-012013-php-openssl_x509_parse-memory-corruption-vulnerability.html
		if (
			PHP_VERSION_ID <= 50327
			|| (PHP_VERSION_ID >= 50400 && PHP_VERSION_ID < 50422)
			|| (PHP_VERSION_ID >= 50500 && PHP_VERSION_ID < 50506)
			) {
			return !empty($contents);
		}
		
		return (bool) openssl_x509_parse($contents);
	}
	
	function validateAccessToken($contents) {
		return (bool) !empty($contents);
	}

	class ErrorHandler
	{
		public $message = '';
		
		public function handleError($code, $msg)
		{
			if ($this->message) {
				$this->message .= "\n";
			}
			$this->message .= preg_replace('{^copy\(.*?\): }', '', $msg);
		}
	}

////////////////////////////////////////////////////////////////////////////////
  
  function AddCommand($cmd,$help) {
    global $smtCommandList;
    global $smtCommandHelp;
    $smtCommandList[] = $cmd;
    $smtCommandHelp[$cmd] = $help;
  }
  
////////////////////////////////////////////////////////////////////////////////
  
  function AddGoDestination($goCmd,$display,$url = '') {
    global $goDestinations;
    global $goDestinationsHelp;
    global $goDestinationsUrl;
    $goDestinations[] = $goCmd;
    $goDestinationsHelp[$goCmd] = $display;
    if ($url) {
      $goDestinationsUrl[$goCmd] = $url;
    }
  }
  
////////////////////////////////////////////////////////////////////////////////
  
  function AddPreference($pref, $value, $shortcut = '') {
    global $smtPrefs;
    global $smtPrefAliases;
    $smtPrefs[$pref] = $value;
    if ($shortcut) {
      $smtPrefAliases[$shortcut] = $pref;
    }
  }

////////////////////////////////////////////////////////////////////////////////
  
  function CleanPath($curPath)
  {
    if ($curPath == '') {
      return './';
    } else {
      $curPath = str_replace('\\', '/', $curPath);
      if ($curPath[strlen($curPath)-1] != '/') {
        $curPath .= '/';
      }
    }
    return $curPath;
  }
  
////////////////////////////////////////////////////////////////////////////////
  
  function GetGithubVersion($branch = 'master') {
    try {
      $phpFile = @file_get_contents("http://github.com/smartlogics-net/smt/raw/{$branch}/smt.php");
      preg_match ("/smtVersion\s=\s'([^']+)'/",$phpFile,$matches);
      if (isset($matches[1])) {
        $githubVersion = $matches[1];
      } else {
        $githubVersion = 'err';
      }
      
    } catch (Exception $e) {
      $githubVersion = 'unavailable';
    }
    return $githubVersion;
  }

////////////////////////////////////////////////////////////////////////////////
  
  function IsEmpty($obj) {
    if (is_array($obj)) {
      foreach ($obj as $o) {
        if (!IsEmpty($o)) {
          return false;
        }
      }
      return true;
    } else {
      if ($obj) {
        return false;
      } else {
        return true;
      }
    }
  }
  
////////////////////////////////////////////////////////////////////////////////
  
  function LaunchBrowser($url) {
    global $smtPrefs;
    global $hasLaunched;
    $hasLaunched = true;
    if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') {
      pclose(popen("start \"\" /B \"{$url}\"", "r"));
    } else {
      if ($smtPrefs['launch_exec']) {
        $execString = str_replace('[url]', $url, $smtPrefs['launch_exec']);
        exec($execString);
      } else {
        if (strtoupper(substr(PHP_OS, 0, 6)) == 'DARWIN') {
          exec("open \"{$url}\" > /dev/null 2>&1 &");
        } else {
          exec("xdg-open \"{$url}\" > /dev/null 2>&1 &");
        }
      }
    }
  }
  
////////////////////////////////////////////////////////////////////////////////

  function ParamCount()
  {
    global $smtParams;
    return count($smtParams)-1;
  }

////////////////////////////////////////////////////////////////////////////////
  
  function ParseArguments($in_argv,$in_argc) {
    global $smtCommand;
    global $smtParams;
    global $smtPrefs;
    global $smtPrefAliases;
    
    for ($i=1; $i < $in_argc; $i++) {
      $curArg = $in_argv[$i];
      if (substr($curArg,0,2) == '--') {
        while (substr($curArg,0,2) == '--') {
          $curArg = substr($curArg,2);
        }
        if (strpos($curArg,"=")) {
          $switchKey = strtr(substr($curArg,0,strpos($curArg,"=")), array('-' => '_', '_' => '-'));
          $switchValue = substr($curArg,strpos($curArg,"=")+1);
          if ($switchValue == '') {
            $switchValue = '0';
          }
        } else {
          $switchKey = $curArg;
          $switchValue = '1';
        }
        $switchKey = strtolower($switchKey);
        if (isset($smtPrefAliases[$switchKey])) {
          $switchKey = $smtPrefAliases[$switchKey];
        }
        if (isset($smtPrefs[$switchKey])) {
          $smtPrefs[$switchKey] = $switchValue;
        } else {
          SmtWarning("Ignoring Parameter {$i}: Unknown Switch [{$switchKey}]");
        }
      } else {
        if ($smtCommand == '') {
          $smtCommand = strtoupper($curArg);
          $smtParams[] = $smtCommand;
        } else {
          $smtParams[] = $curArg;
        }
      }
    }
  }

  ////////////////////////////////////////////////////////////////////////////////
  
  function PrintFinish() {
    global $smtPrefs;
    global $printMatrix;
    if ($smtPrefs['print_csv']) {
      return;
    }
    /*
    if (isset($printMatrix)) {
      $columnWidth = Array();
      if (count($printMatrix) > 0) {
        foreach ($printMatrix as $row) {
          while (count($row) > count($columnWidth)) {
            $columnWidth[] = 0;
          }
          for ($i=0; $i<count($row); $i++) {
            if (strlen($row[$i])>$columnWidth[$i]) {
              $columnWidth[$i]=strlen($row[$i]);
            }
          }
        }
        for ($i=0; $i<count($columnWidth)-1; $i++) {
          $columnWidth[$i] += $smtPrefs['print_col_padding'];
        }
        
        if ($smtPrefs['print_wrap']) {
          $consoleWidth = $smtPrefs['print_wrap_width'];
          if ($smtPrefs['print_wrap_env_var']) {
            if (getenv($smtPrefs['print_wrap_env_var'])) {
              $consoleWidth = getenv($smtPrefs['print_wrap_env_var']);
            }
          }
          $colToWrap = count($columnWidth) - 1;
          $wrapWidth = $consoleWidth - array_sum($columnWidth) + $columnWidth[$colToWrap] - 1;
          if ($wrapWidth < $smtPrefs['print_wrap_min_width']) {
            $wrapWidth = $columnWidth[$colToWrap]+1;
          }
          $backupMatrix = $printMatrix;
          $printMatrix = array();
          foreach ($backupMatrix as $row) {
            if (isset($row[$colToWrap])) {
              $rightCol = array_pop($row);
              $wrapped = wordwrap($rightCol,$wrapWidth,"\n",$smtPrefs['print_wrap_cut']);
              $newRows = explode("\n",$wrapped);
              foreach ($newRows as $nr) {
                $addRow = $row;
                array_push($addRow,$nr);
                $printMatrix[] = CleanColumns($addRow);
              }
            } else {
              $printMatrix[] = $row;
            }
          }
        } else {
          if ($smtPrefs['print_linefeed_subst']) {
            $colToWrap = count($columnWidth) - 1;
            for ($j=0; $j < count($printMatrix); $j++) {
              if (isset($printMatrix[$j][$colToWrap])) {
                $printMatrix[$j][$colToWrap] = str_replace("\n", $smtPrefs['print_linefeed_subst'], $printMatrix[$j][$colToWrap]);
              }
            }
          }
        }
        
        foreach ($printMatrix as $row) {
          for ($i=0; $i<count($row); $i++) {
            if ($i < count($row)-1) {
              print str_pad($row[$i], $columnWidth[$i], ' ');
            } else {
              print $row[$i];
            }
          }
          print "\n";
        }
      }
    }
    */
  }
  
////////////////////////////////////////////////////////////////////////////////
  
  function PrintFolderHeader() {
    global $smtPrefs;
    $threadInfo = array();
    if ($smtPrefs['mail_save']) {
      $threadInfo[] = '[#]';
    }
    if ($smtPrefs['folder_show_threadid']) {
      $threadInfo[] = 'THREAD_ID';
    }
    PrintHeader($threadInfo,'FIELD','VALUE');
    if ($smtPrefs['folder_blankrow']) {
      PrintRow('');
    }
  }

////////////////////////////////////////////////////////////////////////////////
    
  function ShowAuth() {
    global $smtPrefs, $urlAccess, $urlAuth;
    print "\n";
    print "This application needs to be authorized to access your facebook account.\n";
    print "\n";
    print "Step 1: Allow basic (initial) access to your acount via this url:\n\n";
    print "{$urlAccess}\n";
    print "to launch this page, execute: smt go access\n";
    print "\n";
    print "Step 2: Generate an offline authorization code at this url:\n\n";
    print "{$urlAuth}\n";
    print "to launch this page, execute: smt go auth\n";
    print "\n";
    print "obtain your authorization code (XXXXXX) and then execute: smt auth XXXXXX\n\n";
  }


////////////////////////////////////////////////////////////////////////////////
  
  function SmtException(Exception $e, $defaultCommand = true) {
    if ($defaultCommand) {
      global $smtCommand;
      $defaultCommand = $smtCommand;
    }
    $eCode = $e->getCode();
    SmtFatalError("{$defaultCommand}\n[{$eCode}] {$e->getMessage()}");
  }
  
////////////////////////////////////////////////////////////////////////////////
  
  function SmtFatalError($err) {
    global $smtVersion;
    print "smt [v{$smtVersion}] ERROR: {$err}";
    PrintFinish();
    exit;
  }
  
////////////////////////////////////////////////////////////////////////////////
  
  function SmtWarning($err) {
    global $smtVersion;
    print "smt [v{$smtVersion}] WARNING: {$err}";
  }
  
////////////////////////////////////////////////////////////////////////////////
  
  function TraceReturn($obj) {
    global $smtPrefs;
    if ($smtPrefs['trace']) {
      print_r ($obj);
    }
  }
  
////////////////////////////////////////////////////////////////////////////////
  
  function ValidateParamCount($a, $b=null)
  {
    global $smtParams;
    global $smtCommand;
    $num  = ParamCount();
    $showHelp = false;
    if (is_array($a)) {
      if (!in_array($num,$a)) {
        $showHelp = true;
      }
    } else {
      if ($b == null) {
        if ($num != $a) {
          $showHelp = true;
        }
      } else {
        if (($num < $a)||($num > $b)) {
          $showHelp = true;
        }
      }
    }
    if ($showHelp) {
      print "\n";
      SmtWarning("[{$smtCommand}] Invalid number of parameters");
      print "\n";
      print "try:        [smt help ". strtolower($smtCommand). "]\nto launch:  http://smt.smartlogics.net/commands/" . strtolower($smtCommand) . "\n\nbasic help:\n\n";
      displayHelpCmd($smtCommand);
      exit;
    }
  }
  
////////////////////////////////////////////////////////////////////////////////
  
  function VerifyOutputDir($fileName) {
    global $smtPrefs;
    $fileName = str_replace('\\', '/', $fileName);
    if (strrpos($fileName,'/')) {
      $filePath = CleanPath(substr($fileName, 0, strrpos($fileName, '/')));
      if (!file_exists($filePath)) {
        if ($smtPrefs['auto_mkdir']) {
          if (!mkdir($filePath, $smtPrefs['mkdir_mode'], true)) {
            SmtFatalError("Could Not Create Path: {$filePath}");
          }
        } else {
          SmtFatalError("Invalid Path: {$filePath}");
        }
      }
    }
  }
  
////////////////////////////////////////////////////////////////////////////////

?>
