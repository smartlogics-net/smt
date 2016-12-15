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
    
    $smtVersion = '1.0';
    
////////////////////////////////////////////////////////////////////////////////

    require_once __DIR__ . '/vendor/autoload.php';
	
	process(is_array($argv) ? $argv : array());
	
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
		
		$check       = in_array('--check', $argv);
		$help        = in_array('--help', $argv);
		$force       = in_array('--force', $argv);
		$quiet       = in_array('--quiet', $argv);
		$channel     = in_array('--snapshot', $argv) ? 'snapshot' : (in_array('--preview', $argv) ? 'preview' : 'stable');
		$disableTls  = in_array('--disable-tls', $argv);
		$destDir     = getOptValue('--dest-dir', $argv, '..');
		$version     = getOptValue('--version', $argv, false);
		$object      = getOptValue('--object', $argv, 'me');
		$accessToken = getOptValue('--access-token', $argv, false);
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
			download($version, $destDir, $object, $quiet, $disableTls, $accessToken, $channel);
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
		--access-token       accepts a Access Token string for user request authorization
			
			deprecated:
			
		--disable-tls        disable SSL/TLS security for file downloads
		--cafile="..."       accepts a path to a Certificate Authority (CA) certificate file for SSL/TLS verification
EOF;
	}
	
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
	function download($version, $destDir, $object, $quiet, $disableTls, $accessToken, $channel)
	{
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
										  'app_id' => 'OCxgZ3g1zQmNAZg_ZCLvalS_Tzg',
										  'app_secret' => 'cbff68ff87ee790b807ceb104c973d0c',
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
		
		if (! isset($accessToken)) {
			echo 'No OAuth data could be obtained from the signed request. User has not authorized your app yet.';

            try {
                $fbObject = new FacebookDesktop($smtPrefs['appkey'], $smtPrefs['appsecret']);
                $session = $fbObject->do_get_session($smtParams[1]);
                TraceReturn($session);
            } catch (Exception $e) {
                FbcmdException($e,'Invalid AUTH code / could not authorize session');
            }
            $smtUserSessionKey = $session['session_key'];
            $smtUserSecretKey = $session['secret'];
            VerifyOutputDir($smtKeyFileName);
            if (@file_put_contents ($smtKeyFileName,"{$smtUserSessionKey}\n{$smtUserSecretKey}\n# only the first two lines of this file are read\n# use smt RESET to replace this file\n") == false) {
                FbcmdFatalError("Could not generate keyfile {$smtKeyFileName}");
            }
            try {
                $fbObject->api_client->session_key = $smtUserSessionKey;
                $fbObject->secret = $smtUserSecretKey;
                $fbObject->api_client->secret = $smtUserSecretKey;
                $fbUser = $fbObject->api_client->users_getLoggedInUser();
                $fbReturn = $fbObject->api_client->users_getInfo($fbUser,array('name'));
                TraceReturn($fbReturn);
            } catch (Exception $e) {
                FbcmdException($e,'Invalid AUTH code / could not generate session key');
            }
            if (!$smtPrefs['quiet']) {
                print "\nsmt [v$smtVersion] AUTH Code accepted.\nWelcome to SMT, {$fbReturn[0]['name']}!\n\n";
            }
		}

		// Logged in
		$facebook->setDefaultAccessToken($accessToken);

		$retries = 3;
		while ($retries--) {
			if (!$quiet) {
				out("Downloading $version...", 'info');
			}
			
			$url       = "/{$object}/posts?limit=5";
			$errorHandler = new ErrorHandler();
			set_error_handler(array($errorHandler, 'handleError'));
			
			$resp = $facebook->get($url);

			#var_dump($resp);
			if (! isset($resp)) {
				continue;
			}
			$posts = $resp->getGraphEdge();
			$i=0;
			do {
				foreach ($posts as $post) {
					$time = $post['created_time']->format(DateTime::COOKIE);
					#echo "<h3>{$time}</h3>";
					#if(isset($post['message']) && $post['message']) echo "<p>".make_links($post['message'])."</p>";
					#if(isset($post['story']) && $post['story']) echo "<p>".make_links($post['story'])."</p>";
					
					$file = $dir.DIRECTORY_SEPARATOR.$post['id'].'.txt';
					
					if (is_readable($file)){
						#@unlink($file);
						continue;
					}
					$fh = fopen($file, 'w');
					if (!$fh) {
						out('Could not create file '.$file.': '.$errorHandler->message, 'error');
					}
					if (!fwrite($fh, (in_array('story', $post->getPropertyNames()) ? $post['story'].'---'."\r\n" : '').(in_array('message', $post->getPropertyNames()) ? $post['message'] : ' - no content - '))) {
						out('Download failed: '.$errorHandler->message, 'error');
					}
					fclose($fh);
					
					if($i !== count($posts)-1){
						#echo '<hr>';
					}
					if (!$quiet) {
						echo ".";
					}
					
					$i++;
				}
			} while ($posts = $facebook->next($posts));
			
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
	
?>
