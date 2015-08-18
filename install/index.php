<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

// The FeatherBB version this script installs
define('FORUM_VERSION', '1.0.0');

define('FORUM_DB_REVISION', 21);
define('FORUM_SI_REVISION', 2);
define('FORUM_PARSER_REVISION', 2);

define('MIN_PHP_VERSION', '5.3.0');
define('MIN_MYSQL_VERSION', '4.1.2');
define('MIN_PGSQL_VERSION', '7.0.0');
define('FEATHER_SEARCH_MIN_WORD', 3);
define('FEATHER_SEARCH_MAX_WORD', 20);

define('FEATHER_ROOT', dirname(dirname(__FILE__)).'/');

// Send the Content-type header in case the web server is setup to send something else
header('Content-type: text/html; charset=utf-8');

// Load Slim Framework
require FEATHER_ROOT.'Slim/Slim.php';
\Slim\Slim::registerAutoloader();

// Instantiate Slim
$feather = new \Slim\Slim();

// Load the functions script
require FEATHER_ROOT.'include/functions.php';

// Load UTF-8 functions
require FEATHER_ROOT.'include/utf8/utf8.php';

//
// Check whether a file/folder is writable.
//
// This function also works on Windows Server where ACLs seem to be ignored.
//
function forum_is_writable($path)
{
    if (is_dir($path)) {
        $path = rtrim($path, '/').'/';
        return forum_is_writable($path.uniqid(mt_rand()).'.tmp');
    }

    // Check temporary file for read/write capabilities
    $rm = file_exists($path);
    $f = @fopen($path, 'a');

    if ($f === false) {
        return false;
    }

    fclose($f);

    if (!$rm) {
        @unlink($path);
    }

    return true;
}

//
// Removes any "bad" characters (characters which mess with the display of a page, are invisible, etc) from the given string
// See: http://kb.mozillazine.org/Network.IDN.blacklist_chars
//
function remove_bad_characters($array)
{
    static $bad_utf8_chars;

    if (!isset($bad_utf8_chars)) {
        $bad_utf8_chars = array(
            "\xcc\xb7"        => '',        // COMBINING SHORT SOLIDUS OVERLAY		0337	*
            "\xcc\xb8"        => '',        // COMBINING LONG SOLIDUS OVERLAY		0338	*
            "\xe1\x85\x9F"    => '',        // HANGUL CHOSEONG FILLER				115F	*
            "\xe1\x85\xA0"    => '',        // HANGUL JUNGSEONG FILLER				1160	*
            "\xe2\x80\x8b"    => '',        // ZERO WIDTH SPACE						200B	*
            "\xe2\x80\x8c"    => '',        // ZERO WIDTH NON-JOINER				200C
            "\xe2\x80\x8d"    => '',        // ZERO WIDTH JOINER					200D
            "\xe2\x80\x8e"    => '',        // LEFT-TO-RIGHT MARK					200E
            "\xe2\x80\x8f"    => '',        // RIGHT-TO-LEFT MARK					200F
            "\xe2\x80\xaa"    => '',        // LEFT-TO-RIGHT EMBEDDING				202A
            "\xe2\x80\xab"    => '',        // RIGHT-TO-LEFT EMBEDDING				202B
            "\xe2\x80\xac"    => '',        // POP DIRECTIONAL FORMATTING			202C
            "\xe2\x80\xad"    => '',        // LEFT-TO-RIGHT OVERRIDE				202D
            "\xe2\x80\xae"    => '',        // RIGHT-TO-LEFT OVERRIDE				202E
            "\xe2\x80\xaf"    => '',        // NARROW NO-BREAK SPACE				202F	*
            "\xe2\x81\x9f"    => '',        // MEDIUM MATHEMATICAL SPACE			205F	*
            "\xe2\x81\xa0"    => '',        // WORD JOINER							2060
            "\xe3\x85\xa4"    => '',        // HANGUL FILLER						3164	*
            "\xef\xbb\xbf"    => '',        // ZERO WIDTH NO-BREAK SPACE			FEFF
            "\xef\xbe\xa0"    => '',        // HALFWIDTH HANGUL FILLER				FFA0	*
            "\xef\xbf\xb9"    => '',        // INTERLINEAR ANNOTATION ANCHOR		FFF9	*
            "\xef\xbf\xba"    => '',        // INTERLINEAR ANNOTATION SEPARATOR		FFFA	*
            "\xef\xbf\xbb"    => '',        // INTERLINEAR ANNOTATION TERMINATOR	FFFB	*
            "\xef\xbf\xbc"    => '',        // OBJECT REPLACEMENT CHARACTER			FFFC	*
            "\xef\xbf\xbd"    => '',        // REPLACEMENT CHARACTER				FFFD	*
            "\xe2\x80\x80"    => ' ',        // EN QUAD								2000	*
            "\xe2\x80\x81"    => ' ',        // EM QUAD								2001	*
            "\xe2\x80\x82"    => ' ',        // EN SPACE								2002	*
            "\xe2\x80\x83"    => ' ',        // EM SPACE								2003	*
            "\xe2\x80\x84"    => ' ',        // THREE-PER-EM SPACE					2004	*
            "\xe2\x80\x85"    => ' ',        // FOUR-PER-EM SPACE					2005	*
            "\xe2\x80\x86"    => ' ',        // SIX-PER-EM SPACE						2006	*
            "\xe2\x80\x87"    => ' ',        // FIGURE SPACE							2007	*
            "\xe2\x80\x88"    => ' ',        // FEATHERCTUATION SPACE					2008	*
            "\xe2\x80\x89"    => ' ',        // THIN SPACE							2009	*
            "\xe2\x80\x8a"    => ' ',        // HAIR SPACE							200A	*
            "\xE3\x80\x80"    => ' ',        // IDEOGRAPHIC SPACE					3000	*
        );
    }

    if (is_array($array)) {
        return array_map('remove_bad_characters', $array);
    }

    // Strip out any invalid characters
    $array = utf8_bad_strip($array);

    // Remove control characters
    $array = preg_replace('%[\x00-\x08\x0b-\x0c\x0e-\x1f]%', '', $array);

    // Replace some "bad" characters
    $array = str_replace(array_keys($bad_utf8_chars), array_values($bad_utf8_chars), $array);

    return $array;
}

// Strip out "bad" UTF-8 characters
$_GET = remove_bad_characters($_GET);
$_POST = remove_bad_characters($_POST);
$_COOKIE = remove_bad_characters($_COOKIE);
$_REQUEST = remove_bad_characters($_REQUEST);

//
// Unset any variables instantiated as a result of register_globals being enabled
//
$register_globals = ini_get('register_globals');
if ($register_globals === '' || $register_globals === '0' || strtolower($register_globals) === 'off') {
    return;
}

// Prevent script.php?GLOBALS[foo]=bar
if (isset($_REQUEST['GLOBALS']) || isset($_FILES['GLOBALS'])) {
    exit('I\'ll have a steak sandwich and... a steak sandwich.');
}

// Variables that shouldn't be unset
$no_unset = array('GLOBALS', '_GET', '_POST', '_COOKIE', '_REQUEST', '_SERVER', '_ENV', '_FILES');

// Remove elements in $GLOBALS that are present in any of the superglobals
$input = array_merge($_GET, $_POST, $_COOKIE, $_SERVER, $_ENV, $_FILES, isset($_SESSION) && is_array($_SESSION) ? $_SESSION : array());
foreach ($input as $k => $v) {
    if (!in_array($k, $no_unset) && isset($GLOBALS[$k])) {
        unset($GLOBALS[$k]);
        unset($GLOBALS[$k]); // Double unset to circumvent the zend_hash_del_key_or_index hole in PHP <4.4.3 and <5.1.4
    }
}

// Disable error reporting for uninitialized variables
error_reporting(E_ALL);

// Force POSIX locale (to prevent functions such as strtolower() from messing up UTF-8 strings)
setlocale(LC_CTYPE, 'C');

// Turn off magic_quotes_runtime
if (get_magic_quotes_runtime()) {
    set_magic_quotes_runtime(0);
}

// Strip slashes from GET/POST/COOKIE (if magic_quotes_gpc is enabled)
if (get_magic_quotes_gpc()) {
    function stripslashes_array($array)
    {
        return is_array($array) ? array_map('stripslashes_array', $array) : stripslashes($array);
    }

    $_GET = stripslashes_array($_GET);
    $_POST = stripslashes_array($_POST);
    $_COOKIE = stripslashes_array($_COOKIE);
    $_REQUEST = stripslashes_array($_REQUEST);
}

// Turn off PHP time limit
@set_time_limit(0);


// If we've been passed a default language, use it
$install_lang = isset($_POST['install_lang']) ? feather_trim($_POST['install_lang']) : 'English';

// Make sure we got a valid language string
$install_lang = preg_replace('%[\.\\\/]%', '', $install_lang);

// If such a language pack doesn't exist, or isn't up-to-date enough to translate this page, default to English
if (!file_exists(FEATHER_ROOT.'lang/'.$feather->user->language.'/install.mo')) {
    $install_lang = 'English';
}

// Load l10n
require_once FEATHER_ROOT.'include/pomo/MO.php';
require_once FEATHER_ROOT.'include/l10n.php';

load_textdomain('featherbb', FEATHER_ROOT.'lang/'.$install_lang.'/install.mo');

if (file_exists(FEATHER_ROOT.'include/config.php')) {
    // Check to see whether FeatherBB is already installed
    include FEATHER_ROOT.'include/config.php';

    // If FEATHER is defined, config.php is probably valid and thus the software is installed
    if (defined('FEATHER')) {
        exit(__('Already installed'));
    }
}

// Define FEATHER because email.php requires it
define('FEATHER', 1);

// If the cache directory is not specified, we use the default setting
if (!defined('FORUM_CACHE_DIR')) {
    define('FORUM_CACHE_DIR', FEATHER_ROOT.'cache/');
}

// Make sure we are running at least MIN_PHP_VERSION
if (!function_exists('version_compare') || version_compare(PHP_VERSION, MIN_PHP_VERSION, '<')) {
    exit(sprintf(__('You are running error'), 'PHP', PHP_VERSION, FORUM_VERSION, MIN_PHP_VERSION));
}



//
// Display a simple error message
//
function error($message, $file = null, $line = null, $db_error = false)
{
    global $feather_config;
    // Set some default settings if the script failed before $feather_config could be populated
    if (empty($feather_config)) {
        $feather_config = array(
            'o_board_title'    => 'FluxBB',
            'o_gzip'        => '0'
        );
    }
    // Empty all output buffers and stop buffering
    while (@ob_end_clean());
    // "Restart" output buffering if we are using ob_gzhandler (since the gzip header is already sent)
    if ($feather_config['o_gzip'] && extension_loaded('zlib')) {
        ob_start('ob_gzhandler');
    }
    // Send no-cache headers
    header('Expires: Thu, 21 Jul 1977 07:30:00 GMT'); // When yours truly first set eyes on this world! :)
    header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
    header('Cache-Control: post-check=0, pre-check=0', false);
    header('Pragma: no-cache'); // For HTTP/1.0 compatibility
    // Send the Content-type header in case the web server is setup to send something else
    header('Content-type: text/html; charset=utf-8');
    ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<?php $page_title = array(feather_escape($feather_config['o_board_title']), 'Error') ?>
<title><?php echo generate_page_title($page_title) ?></title>
<style type="text/css">
<!--
BODY {MARGIN: 10% 20% auto 20%; font: 10px Verdana, Arial, Helvetica, sans-serif}
#errorbox {BORDER: 1px solid #B84623}
H2 {MARGIN: 0; COLOR: #FFFFFF; BACKGROUND-COLOR: #B84623; FONT-SIZE: 1.1em; PADDING: 5px 4px}
#errorbox DIV {PADDING: 6px 5px; BACKGROUND-COLOR: #F1F1F1}
-->
</style>
</head>
<body>

<div id="errorbox">
	<h2>An error was encountered</h2>
	<div>
<?php
    if (defined('FEATHER_DEBUG') && !is_null($file) && !is_null($line)) {
        echo "\t\t".'<strong>File:</strong> '.$file.'<br />'."\n\t\t".'<strong>Line:</strong> '.$line.'<br /><br />'."\n\t\t".'<strong>FluxBB reported</strong>: '.$message."\n";
        if ($db_error) {
            echo "\t\t".'<br /><br /><strong>Database reported:</strong> '.feather_escape($db_error['error_msg']).(($db_error['error_no']) ? ' (Errno: '.$db_error['error_no'].')' : '')."\n";
            if ($db_error['error_sql'] != '') {
                echo "\t\t".'<br /><br /><strong>Failed query:</strong> '.feather_escape($db_error['error_sql'])."\n";
            }
        }
    } else {
        echo "\t\t".'Error: <strong>'.$message.'.</strong>'."\n";
    }
    ?>
	</div>
</div>

</body>
</html>
<?php
    // If a database connection was established (before this error) we close it
    if ($db_error) {
        $GLOBALS['db']->close();
    }
    exit;
}


//
// Generate output to be used for config.php
//
function generate_config_file()
{
    global $db_type, $db_host, $db_name, $db_username, $db_password, $db_prefix, $cookie_name, $cookie_seed;

    return '<?php'."\n\n".'$feather_user_settings = array('."\n\t".'\'db_type\' => \''.$db_type."',\n\t".'\'db_host\' => \''.$db_host."',\n\t".'\'db_name\' => \''.addslashes($db_name)."',\n\t".'\'db_user\' => \''.addslashes($db_username)."',\n\t".'\'db_pass\' => \''.addslashes($db_password)."',\n\t".'\'db_prefix\' => \''.addslashes($db_prefix)."'\n);\n\n".'$p_connect = false;'."\n\n".'$cookie_name = '."'".$cookie_name."';\n".'$cookie_domain = '."'';\n".'$cookie_path = '."'/';\n".'$cookie_secure = 0;'."\n".'$cookie_seed = \''.random_key(16, false, true)."';\n";
}


if (isset($_POST['generate_config'])) {
    header('Content-Type: text/x-delimtext; name="config.php"');
    header('Content-disposition: attachment; filename=config.php');

    $db_type = $_POST['db_type'];
    $db_host = $_POST['db_host'];
    $db_name = $_POST['db_name'];
    $db_username = $_POST['db_username'];
    $db_password = $_POST['db_password'];
    $db_prefix = $_POST['db_prefix'];
    $cookie_name = $_POST['cookie_name'];
    $cookie_seed = $_POST['cookie_seed'];

    echo generate_config_file();
    exit;
}


if (!isset($_POST['form_sent'])) {
    // Make an educated guess regarding base_url
    $base_url  = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 'https://' : 'http://';    // protocol
    $base_url .= preg_replace('%:(80|443)$%', '', $_SERVER['HTTP_HOST']);                            // host[:port]
    $base_url .= str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));                            // path

    if (substr($base_url, -1) == '/') {
        $base_url = str_replace('/install', '', $base_url);
        $base_url = substr($base_url, 0, -1);
    }

    $db_type = $db_name = $db_username = $db_prefix = $username = $email = '';
    $db_host = 'localhost';
    $title = __('My FeatherBB Forum');
    $description = '<p><span>'.__('Description').'</span></p>';
    $default_lang = $install_lang;
    $default_style = 'FeatherBB';
} else {
    $db_type = $_POST['req_db_type'];
    $db_host = feather_trim($_POST['req_db_host']);
    $db_name = feather_trim($_POST['req_db_name']);
    $db_username = feather_trim($_POST['db_username']);
    $db_password = feather_trim($_POST['db_password']);
    $db_prefix = feather_trim($_POST['db_prefix']);
    $username = feather_trim($_POST['req_username']);
    $email = strtolower(feather_trim($_POST['req_email']));
    $password1 = feather_trim($_POST['req_password1']);
    $password2 = feather_trim($_POST['req_password2']);
    $title = feather_trim($_POST['req_title']);
    $description = feather_trim($_POST['desc']);
    $base_url = feather_trim($_POST['req_base_url']);
    $default_lang = feather_trim($_POST['req_default_lang']);
    $default_style = 'FeatherBB';
    $alerts = array();

    // Make sure base_url doesn't end with a slash
    if (substr($base_url, -1) == '/') {
        $base_url = substr($base_url, 0, -1);
    }

    // Validate username and passwords
    if (feather_strlen($username) < 2) {
        $alerts[] = __('Username 1');
    } elseif (feather_strlen($username) > 25) { // This usually doesn't happen since the form element only accepts 25 characters
        $alerts[] = __('Username 2');
    } elseif (!strcasecmp($username, 'Guest')) {
        $alerts[] = __('Username 3');
    } elseif (preg_match('%[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}%', $username) || preg_match('%((([0-9A-Fa-f]{1,4}:){7}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){6}:[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){5}:([0-9A-Fa-f]{1,4}:)?[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){4}:([0-9A-Fa-f]{1,4}:){0,2}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){3}:([0-9A-Fa-f]{1,4}:){0,3}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){2}:([0-9A-Fa-f]{1,4}:){0,4}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){6}((\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b)\.){3}(\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b))|(([0-9A-Fa-f]{1,4}:){0,5}:((\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b)\.){3}(\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b))|(::([0-9A-Fa-f]{1,4}:){0,5}((\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b)\.){3}(\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b))|([0-9A-Fa-f]{1,4}::([0-9A-Fa-f]{1,4}:){0,5}[0-9A-Fa-f]{1,4})|(::([0-9A-Fa-f]{1,4}:){0,6}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){1,7}:))%', $username)) {
        $alerts[] = __('Username 4');
    } elseif ((strpos($username, '[') !== false || strpos($username, ']') !== false) && strpos($username, '\'') !== false && strpos($username, '"') !== false) {
        $alerts[] = __('Username 5');
    } elseif (preg_match('%(?:\[/?(?:b|u|i|h|colou?r|quote|code|img|url|email|list)\]|\[(?:code|quote|list)=)%i', $username)) {
        $alerts[] = __('Username 6');
    }

    if (feather_strlen($password1) < 6) {
        $alerts[] = __('Short password');
    } elseif ($password1 != $password2) {
        $alerts[] = __('Passwords not match');
    }

    // Validate email
    require FEATHER_ROOT.'include/email.php';

    if (!is_valid_email($email)) {
        $alerts[] = __('Wrong email');
    }

    if ($title == '') {
        $alerts[] = __('No board title');
    }

    $languages = forum_list_langs();
    if (!in_array($default_lang, $languages)) {
        $alerts[] = __('Error default language');
    }

    $styles = forum_list_styles();
    if (!in_array($default_style, $styles)) {
        $alerts[] = __('Error default style');
    }
}

// Check if the cache directory is writable
if (!forum_is_writable(FORUM_CACHE_DIR)) {
    $alerts[] = sprintf(__('Alert cache'), FORUM_CACHE_DIR);
}

// Check if default avatar directory is writable
if (!forum_is_writable(FEATHER_ROOT.'img/avatars/')) {
    $alerts[] = sprintf(__('Alert avatar'), FEATHER_ROOT.'img/avatars/');
}

if (!isset($_POST['form_sent']) || !empty($alerts)) {
    // Determine available database extensions
    $dual_mysql = false;
    $db_extensions = array();
    $mysql_innodb = false;
    if (function_exists('mysqli_connect')) {
        $db_extensions[] = array('mysqli', 'MySQL Improved');
        $db_extensions[] = array('mysqli_innodb', 'MySQL Improved (InnoDB)');
        $mysql_innodb = true;
    }
    if (function_exists('mysql_connect')) {
        $db_extensions[] = array('mysql', 'MySQL Standard');
        $db_extensions[] = array('mysql_innodb', 'MySQL Standard (InnoDB)');
        $mysql_innodb = true;

        if (count($db_extensions) > 2) {
            $dual_mysql = true;
        }
    }
    if (function_exists('sqlite_open')) {
        $db_extensions[] = array('sqlite', 'SQLite');
    }
    if (class_exists('SQLite3')) {
        $db_extensions[] = array('sqlite3', 'SQLite3');
    }
    if (function_exists('pg_connect')) {
        $db_extensions[] = array('pgsql', 'PostgreSQL');
    }

    if (empty($db_extensions)) {
        error(__('No DB extensions'));
    }

    // Fetch a list of installed languages
    $languages = forum_list_langs();

    ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" dir="ltr">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php _e('FeatherBB Installation') ?></title>
<link rel="stylesheet" type="text/css" href="../style/<?php echo $default_style ?>.css" />
<script type="text/javascript">
/* <![CDATA[ */
function process_form(the_form)
{
	var required_fields = {
		"req_db_type": "<?php _e('Database type') ?>",
		"req_db_host": "<?php _e('Database server hostname') ?>",
		"req_db_name": "<?php _e('Database name') ?>",
		"req_username": "<?php _e('Administrator username') ?>",
		"req_password1": "<?php _e('Password') ?>",
		"req_password2": "<?php _e('Confirm password') ?>",
		"req_email": "<?php _e('Administrator email') ?>",
		"req_title": "<?php _e('Board title') ?>",
		"req_base_url": "<?php _e('Base URL') ?>"
	};
	if (document.all || document.getElementById)
	{
		for (var i = 0; i < the_form.length; ++i)
		{
			var elem = the_form.elements[i];
			if (elem.name && required_fields[elem.name] && !elem.value && elem.type && (/^(?:text(?:area)?|password|file)$/i.test(elem.type)))
			{
				alert('"' + required_fields[elem.name] + '" <?php _e('Required field') ?>');
				elem.focus();
				return false;
			}
		}
	}
	return true;
}
/* ]]> */
</script>
</head>
<body onload="document.getElementById('install').req_db_type.focus();document.getElementById('install').start.disabled=false;" onunload="">

<div id="puninstall" class="pun">
<div class="top-box"><div><!-- Top Corners --></div></div>
<div class="punwrap">

<section class="container">
	<div id="brdheader" class="block">
		<div class="box">
			<div id="brdtitle" class="inbox">
				<h1><span><?php _e('FeatherBB Installation') ?></span></h1>
				<div id="brddesc"><p><?php _e('Welcome') ?></p></div>
			</div>
		</div>
	</div>
</section>

<section class="container">
	<div id="brdmain">
	<?php if (count($languages) > 1): ?><div class="blockform">
		<h2><span><?php _e('Choose install language') ?></span></h2>
		<div class="box">
			<form id="install" method="post" action="">
				<div class="inform">
					<fieldset>
						<legend><?php _e('Install language') ?></legend>
						<div class="infldset">
							<p><?php _e('Choose install language info') ?></p>
							<label><strong><?php _e('Install language') ?></strong>
							<br /><select name="install_lang">
<?php

            foreach ($languages as $temp) {
                if ($temp == $install_lang) {
                    echo "\t\t\t\t\t".'<option value="'.$temp.'" selected="selected">'.$temp.'</option>'."\n";
                } else {
                    echo "\t\t\t\t\t".'<option value="'.$temp.'">'.$temp.'</option>'."\n";
                }
            }

    ?>
							</select>
							<br /></label>
						</div>
					</fieldset>
				</div>
				<p class="buttons"><input type="submit" name="start" value="<?php _e('Change language') ?>" /></p>
			</form>
		</div>
	</div>
	<?php endif;
    ?>

	<div class="blockform">
		<h2><span><?php echo sprintf(__('Install'), FORUM_VERSION) ?></span></h2>
		<div class="box">
			<form id="install" method="post" action="" onsubmit="this.start.disabled=true;if(process_form(this)){return true;}else{this.start.disabled=false;return false;}">
			<div><input type="hidden" name="form_sent" value="1" /><input type="hidden" name="install_lang" value="<?php echo feather_escape($install_lang) ?>" /></div>
				<div class="inform">
	<?php if (!empty($alerts)): ?>				<div class="forminfo error-info">
						<h3><?php _e('Errors') ?></h3>
						<ul class="error-list">
<?php

    foreach ($alerts as $cur_alert) {
        echo "\t\t\t\t\t\t".'<li><strong>'.$cur_alert.'</strong></li>'."\n";
    }
    ?>
						</ul>
					</div>
	<?php endif;
    ?>			</div>
				<div class="inform">
					<div class="forminfo">
						<h3><?php _e('Database setup') ?></h3>
						<p><?php _e('Info 1') ?></p>
					</div>
					<fieldset>
					<legend><?php _e('Select database') ?></legend>
						<div class="infldset">
							<p><?php _e('Info 2') ?></p>
							<label class="required"><strong><?php _e('Database type') ?> <span><?php _e('Required') ?></span></strong>
							<br /><select name="req_db_type">
<?php

        foreach ($db_extensions as $temp) {
            if ($temp[0] == $db_type) {
                echo "\t\t\t\t\t\t\t".'<option value="'.$temp[0].'" selected="selected">'.$temp[1].'</option>'."\n";
            } else {
                echo "\t\t\t\t\t\t\t".'<option value="'.$temp[0].'">'.$temp[1].'</option>'."\n";
            }
        }

    ?>
							</select>
							<br /></label>
						</div>
					</fieldset>
				</div>
				<div class="inform">
					<fieldset>
						<legend><?php _e('Database hostname') ?></legend>
						<div class="infldset">
							<p><?php _e('Info 3') ?></p>
							<label class="required"><strong><?php _e('Database server hostname') ?> <span><?php _e('Required') ?></span></strong><br /><input type="text" name="req_db_host" value="<?php echo feather_escape($db_host) ?>" size="50" /><br /></label>
						</div>
					</fieldset>
				</div>
				<div class="inform">
					<fieldset>
						<legend><?php _e('Database enter name') ?></legend>
						<div class="infldset">
							<p><?php _e('Info 4') ?></p>
							<label class="required"><strong><?php _e('Database name') ?> <span><?php _e('Required') ?></span></strong><br /><input id="req_db_name" type="text" name="req_db_name" value="<?php echo feather_escape($db_name) ?>" size="30" /><br /></label>
						</div>
					</fieldset>
				</div>
				<div class="inform">
					<fieldset>
						<legend><?php _e('Database enter informations') ?></legend>
						<div class="infldset">
							<p><?php _e('Info 5') ?></p>
							<label class="conl"><?php _e('Database username') ?><br /><input type="text" name="db_username" value="<?php echo feather_escape($db_username) ?>" size="30" /><br /></label>
							<label class="conl"><?php _e('Database password') ?><br /><input type="password" name="db_password" size="30" /><br /></label>
							<div class="clearer"></div>
						</div>
					</fieldset>
				</div>
				<div class="inform">
					<fieldset>
						<legend><?php _e('Database enter prefix') ?></legend>
						<div class="infldset">
							<p><?php _e('Info 6') ?></p>
							<label><?php _e('Table prefix') ?><br /><input id="db_prefix" type="text" name="db_prefix" value="<?php echo feather_escape($db_prefix) ?>" size="20" maxlength="30" /><br /></label>
						</div>
					</fieldset>
				</div>
				<div class="inform">
					<div class="forminfo">
						<h3><?php _e('Administration setup') ?></h3>
						<p><?php _e('Info 7') ?></p>
					</div>
					<fieldset>
						<legend><?php _e('Administration setup') ?></legend>
						<div class="infldset">
							<p><?php _e('Info 8') ?></p>
							<label class="required"><strong><?php _e('Administrator username') ?> <span><?php _e('Required') ?></span></strong><br /><input type="text" name="req_username" value="<?php echo feather_escape($username) ?>" size="25" maxlength="25" /><br /></label>
							<label class="conl required"><strong><?php _e('Password') ?> <span><?php _e('Required') ?></span></strong><br /><input id="req_password1" type="password" name="req_password1" size="16" /><br /></label>
							<label class="conl required"><strong><?php _e('Confirm password') ?> <span><?php _e('Required') ?></span></strong><br /><input type="password" name="req_password2" size="16" /><br /></label>
							<div class="clearer"></div>
							<label class="required"><strong><?php _e('Administrator email') ?> <span><?php _e('Required') ?></span></strong><br /><input id="req_email" type="text" name="req_email" value="<?php echo feather_escape($email) ?>" size="50" maxlength="80" /><br /></label>
						</div>
					</fieldset>
				</div>
				<div class="inform">
					<div class="forminfo">
						<h3><?php _e('Board setup') ?></h3>
						<p><?php _e('Info 11') ?></p>
					</div>
					<fieldset>
						<legend><?php _e('General information') ?></legend>
						<div class="infldset">
							<label class="required"><strong><?php _e('Board title') ?> <span><?php _e('Required') ?></span></strong><br /><input id="req_title" type="text" name="req_title" value="<?php echo feather_escape($title) ?>" size="60" maxlength="255" /><br /></label>
							<label><?php _e('Board description') ?><br /><input id="desc" type="text" name="desc" value="<?php echo feather_escape($description) ?>" size="60" maxlength="255" /><br /></label>
							<label class="required"><strong><?php _e('Base URL') ?> <span><?php _e('Required') ?></span></strong><br /><input id="req_base_url" type="text" name="req_base_url" value="<?php echo feather_escape($base_url) ?>" size="60" maxlength="100" /><br /></label>
							<label class="required"><strong><?php _e('Default language') ?> <span><?php _e('Required') ?></span></strong><br /><select id="req_default_lang" name="req_default_lang">
<?php

            $languages = forum_list_langs();
    foreach ($languages as $temp) {
        if ($temp == $default_lang) {
            echo "\t\t\t\t\t\t\t\t\t\t\t".'<option value="'.$temp.'" selected="selected">'.$temp.'</option>'."\n";
        } else {
            echo "\t\t\t\t\t\t\t\t\t\t\t".'<option value="'.$temp.'">'.$temp.'</option>'."\n";
        }
    }

    ?>
							</select><br /></label>
						</div>
					</fieldset>
				</div>
				<p class="buttons"><input type="submit" name="start" value="<?php _e('Start install') ?>" /></p>
			</form>
		</div>
	</div>
	</div>
</section>

</div>
<div class="end-box"><div><!-- Bottom Corners --></div></div>
</div>

</body>
</html>
<?php

} else {
    // Load the appropriate DB layer class
    switch ($db_type) {
        case 'mysql':
            require FEATHER_ROOT.'install/dblayer/mysql.php';
            break;

        case 'mysql_innodb':
            require FEATHER_ROOT.'install/dblayer/mysql_innodb.php';
            break;

        case 'mysqli':
            require FEATHER_ROOT.'install/dblayer/mysqli.php';
            break;

        case 'mysqli_innodb':
            require FEATHER_ROOT.'install/dblayer/mysqli_innodb.php';
            break;

        case 'pgsql':
            require FEATHER_ROOT.'install/dblayer/pgsql.php';
            break;

        case 'sqlite':
            require FEATHER_ROOT.'install/dblayer/sqlite.php';
            break;

        case 'sqlite3':
            require FEATHER_ROOT.'install/dblayer/sqlite3.php';
            break;

        default:
            error(sprintf(__('DB type not valid'), feather_escape($db_type)));
    }

    // Create the database object (and connect/select db)
    $db = new DBLayer($db_host, $db_username, $db_password, $db_name, $db_prefix, false);

    // Validate prefix
    if (strlen($db_prefix) > 0 && (!preg_match('%^[a-zA-Z_][a-zA-Z0-9_]*$%', $db_prefix) || strlen($db_prefix) > 40)) {
        error(sprintf(__('Table prefix error'), $db->prefix));
    }

    // Do some DB type specific checks
    switch ($db_type) {
        case 'mysql':
        case 'mysqli':
        case 'mysql_innodb':
        case 'mysqli_innodb':
            $mysql_info = $db->get_version();
            if (version_compare($mysql_info['version'], MIN_MYSQL_VERSION, '<')) {
                error(sprintf(__('You are running error'), 'MySQL', $mysql_info['version'], FORUM_VERSION, MIN_MYSQL_VERSION));
            }
            break;

        case 'pgsql':
            $pgsql_info = $db->get_version();
            if (version_compare($pgsql_info['version'], MIN_PGSQL_VERSION, '<')) {
                error(sprintf(__('You are running error'), 'PostgreSQL', $pgsql_info['version'], FORUM_VERSION, MIN_PGSQL_VERSION));
            }
            break;

        case 'sqlite':
        case 'sqlite3':
            if (strtolower($db_prefix) == 'sqlite_') {
                error(__('Prefix reserved'));
            }
            break;
    }


    // Make sure FeatherBB isn't already installed
    $result = $db->query('SELECT 1 FROM '.$db_prefix.'users WHERE id=1');
    if ($db->num_rows($result)) {
        error(sprintf(__('Existing table error'), $db_prefix, $db_name));
    }

    // Check if InnoDB is available
    if ($db_type == 'mysql_innodb' || $db_type == 'mysqli_innodb') {
        $result = $db->query('SHOW VARIABLES LIKE \'have_innodb\'');
        list(, $result) = $db->fetch_row($result);
        if ((strtoupper($result) != 'YES')) {
            error(__('InnoDB off'));
        }
    }


    // Start a transaction
    $db->start_transaction();


    // Create all tables
    $schema = array(
        'FIELDS'        => array(
            'id'            => array(
                'datatype'        => 'SERIAL',
                'allow_null'    => false
            ),
            'username'        => array(
                'datatype'        => 'VARCHAR(200)',
                'allow_null'    => true
            ),
            'ip'            => array(
                'datatype'        => 'VARCHAR(255)',
                'allow_null'    => true
            ),
            'email'            => array(
                'datatype'        => 'VARCHAR(80)',
                'allow_null'    => true
            ),
            'message'        => array(
                'datatype'        => 'VARCHAR(255)',
                'allow_null'    => true
            ),
            'expire'        => array(
                'datatype'        => 'INT(10) UNSIGNED',
                'allow_null'    => true
            ),
            'ban_creator'    => array(
                'datatype'        => 'INT(10) UNSIGNED',
                'allow_null'    => false,
                'default'        => '0'
            )
        ),
        'PRIMARY KEY'    => array('id'),
        'INDEXES'        => array(
            'username_idx'    => array('username')
        )
    );

    if ($db_type == 'mysql' || $db_type == 'mysqli' || $db_type == 'mysql_innodb' || $db_type == 'mysqli_innodb') {
        $schema['INDEXES']['username_idx'] = array('username(25)');
    }

    $db->create_table('bans', $schema) or error('Unable to create bans table', __FILE__, __LINE__, $db->error());


    $schema = array(
        'FIELDS'        => array(
            'id'            => array(
                'datatype'        => 'SERIAL',
                'allow_null'    => false
            ),
            'cat_name'        => array(
                'datatype'        => 'VARCHAR(80)',
                'allow_null'    => false,
                'default'        => '\'New Category\''
            ),
            'disp_position'    => array(
                'datatype'        => 'INT(10)',
                'allow_null'    => false,
                'default'        => '0'
            )
        ),
        'PRIMARY KEY'    => array('id')
    );

    $db->create_table('categories', $schema) or error('Unable to create categories table', __FILE__, __LINE__, $db->error());


    $schema = array(
        'FIELDS'        => array(
            'id'            => array(
                'datatype'        => 'SERIAL',
                'allow_null'    => false
            ),
            'search_for'    => array(
                'datatype'        => 'VARCHAR(60)',
                'allow_null'    => false,
                'default'        => '\'\''
            ),
            'replace_with'    => array(
                'datatype'        => 'VARCHAR(60)',
                'allow_null'    => false,
                'default'        => '\'\''
            )
        ),
        'PRIMARY KEY'    => array('id')
    );

    $db->create_table('censoring', $schema) or error('Unable to create censoring table', __FILE__, __LINE__, $db->error());


    $schema = array(
        'FIELDS'        => array(
            'conf_name'        => array(
                'datatype'        => 'VARCHAR(255)',
                'allow_null'    => false,
                'default'        => '\'\''
            ),
            'conf_value'    => array(
                'datatype'        => 'TEXT',
                'allow_null'    => true
            )
        ),
        'PRIMARY KEY'    => array('conf_name')
    );

    $db->create_table('config', $schema) or error('Unable to create config table', __FILE__, __LINE__, $db->error());


    $schema = array(
        'FIELDS'        => array(
            'group_id'        => array(
                'datatype'        => 'INT(10)',
                'allow_null'    => false,
                'default'        => '0'
            ),
            'forum_id'        => array(
                'datatype'        => 'INT(10)',
                'allow_null'    => false,
                'default'        => '0'
            ),
            'read_forum'    => array(
                'datatype'        => 'TINYINT(1)',
                'allow_null'    => false,
                'default'        => '1'
            ),
            'post_replies'    => array(
                'datatype'        => 'TINYINT(1)',
                'allow_null'    => false,
                'default'        => '1'
            ),
            'post_topics'    => array(
                'datatype'        => 'TINYINT(1)',
                'allow_null'    => false,
                'default'        => '1'
            )
        ),
        'PRIMARY KEY'    => array('group_id', 'forum_id')
    );

    $db->create_table('forum_perms', $schema) or error('Unable to create forum_perms table', __FILE__, __LINE__, $db->error());


    $schema = array(
        'FIELDS'        => array(
            'id'            => array(
                'datatype'        => 'SERIAL',
                'allow_null'    => false
            ),
            'forum_name'    => array(
                'datatype'        => 'VARCHAR(80)',
                'allow_null'    => false,
                'default'        => '\'New forum\''
            ),
            'forum_desc'    => array(
                'datatype'        => 'TEXT',
                'allow_null'    => true
            ),
            'redirect_url'    => array(
                'datatype'        => 'VARCHAR(100)',
                'allow_null'    => true
            ),
            'moderators'    => array(
                'datatype'        => 'TEXT',
                'allow_null'    => true
            ),
            'num_topics'    => array(
                'datatype'        => 'MEDIUMINT(8) UNSIGNED',
                'allow_null'    => false,
                'default'        => '0'
            ),
            'num_posts'        => array(
                'datatype'        => 'MEDIUMINT(8) UNSIGNED',
                'allow_null'    => false,
                'default'        => '0'
            ),
            'last_post'        => array(
                'datatype'        => 'INT(10) UNSIGNED',
                'allow_null'    => true
            ),
            'last_post_id'    => array(
                'datatype'        => 'INT(10) UNSIGNED',
                'allow_null'    => true
            ),
            'last_poster'    => array(
                'datatype'        => 'VARCHAR(200)',
                'allow_null'    => true
            ),
            'sort_by'        => array(
                'datatype'        => 'TINYINT(1)',
                'allow_null'    => false,
                'default'        => '0'
            ),
            'disp_position'    => array(
                'datatype'        => 'INT(10)',
                'allow_null'    => false,
                'default'        =>    '0'
            ),
            'cat_id'        => array(
                'datatype'        => 'INT(10) UNSIGNED',
                'allow_null'    => false,
                'default'        =>    '0'
            )
        ),
        'PRIMARY KEY'    => array('id')
    );

    $db->create_table('forums', $schema) or error('Unable to create forums table', __FILE__, __LINE__, $db->error());


    $schema = array(
        'FIELDS'        => array(
            'g_id'                        => array(
                'datatype'        => 'SERIAL',
                'allow_null'    => false
            ),
            'g_title'                    => array(
                'datatype'        => 'VARCHAR(50)',
                'allow_null'    => false,
                'default'        => '\'\''
            ),
            'g_user_title'                => array(
                'datatype'        => 'VARCHAR(50)',
                'allow_null'    => true
            ),
            'g_promote_min_posts'        => array(
                'datatype'        => 'INT(10) UNSIGNED',
                'allow_null'    => false,
                'default'        => '0'
            ),
            'g_promote_next_group'        => array(
                'datatype'        => 'INT(10) UNSIGNED',
                'allow_null'    => false,
                'default'        => '0'
            ),
            'g_moderator'                => array(
                'datatype'        => 'TINYINT(1)',
                'allow_null'    => false,
                'default'        => '0'
            ),
            'g_mod_edit_users'            => array(
                'datatype'        => 'TINYINT(1)',
                'allow_null'    => false,
                'default'        => '0'
            ),
            'g_mod_rename_users'        => array(
                'datatype'        => 'TINYINT(1)',
                'allow_null'    => false,
                'default'        => '0'
            ),
            'g_mod_change_passwords'    => array(
                'datatype'        => 'TINYINT(1)',
                'allow_null'    => false,
                'default'        => '0'
            ),
            'g_mod_ban_users'            => array(
                'datatype'        => 'TINYINT(1)',
                'allow_null'    => false,
                'default'        => '0'
            ),
            'g_mod_promote_users'            => array(
                'datatype'        => 'TINYINT(1)',
                'allow_null'    => false,
                'default'        => '0'
            ),
            'g_read_board'                => array(
                'datatype'        => 'TINYINT(1)',
                'allow_null'    => false,
                'default'        => '1'
            ),
            'g_view_users'                => array(
                'datatype'        => 'TINYINT(1)',
                'allow_null'    => false,
                'default'        => '1'
            ),
            'g_post_replies'            => array(
                'datatype'        => 'TINYINT(1)',
                'allow_null'    => false,
                'default'        => '1'
            ),
            'g_post_topics'                => array(
                'datatype'        => 'TINYINT(1)',
                'allow_null'    => false,
                'default'        => '1'
            ),
            'g_edit_posts'                => array(
                'datatype'        => 'TINYINT(1)',
                'allow_null'    => false,
                'default'        => '1'
            ),
            'g_delete_posts'            => array(
                'datatype'        => 'TINYINT(1)',
                'allow_null'    => false,
                'default'        => '1'
            ),
            'g_delete_topics'            => array(
                'datatype'        => 'TINYINT(1)',
                'allow_null'    => false,
                'default'        => '1'
            ),
            'g_post_links'                => array(
                'datatype'        => 'TINYINT(1)',
                'allow_null'    => false,
                'default'        => '1'
            ),
            'g_set_title'                => array(
                'datatype'        => 'TINYINT(1)',
                'allow_null'    => false,
                'default'        => '1'
            ),
            'g_search'                    => array(
                'datatype'        => 'TINYINT(1)',
                'allow_null'    => false,
                'default'        => '1'
            ),
            'g_search_users'            => array(
                'datatype'        => 'TINYINT(1)',
                'allow_null'    => false,
                'default'        => '1'
            ),
            'g_send_email'                => array(
                'datatype'        => 'TINYINT(1)',
                'allow_null'    => false,
                'default'        => '1'
            ),
            'g_post_flood'                => array(
                'datatype'        => 'SMALLINT(6)',
                'allow_null'    => false,
                'default'        => '30'
            ),
            'g_search_flood'            => array(
                'datatype'        => 'SMALLINT(6)',
                'allow_null'    => false,
                'default'        => '30'
            ),
            'g_email_flood'                => array(
                'datatype'        => 'SMALLINT(6)',
                'allow_null'    => false,
                'default'        => '60'
            ),
            'g_report_flood'            => array(
                'datatype'        => 'SMALLINT(6)',
                'allow_null'    => false,
                'default'        => '60'
            )
        ),
        'PRIMARY KEY'    => array('g_id')
    );

    $db->create_table('groups', $schema) or error('Unable to create groups table', __FILE__, __LINE__, $db->error());


    $schema = array(
        'FIELDS'        => array(
            'user_id'        => array(
                'datatype'        => 'INT(10) UNSIGNED',
                'allow_null'    => false,
                'default'        => '1'
            ),
            'ident'            => array(
                'datatype'        => 'VARCHAR(200)',
                'allow_null'    => false,
                'default'        => '\'\''
            ),
            'logged'        => array(
                'datatype'        => 'INT(10) UNSIGNED',
                'allow_null'    => false,
                'default'        => '0'
            ),
            'idle'            => array(
                'datatype'        => 'TINYINT(1)',
                'allow_null'    => false,
                'default'        => '0'
            ),
            'last_post'            => array(
                'datatype'        => 'INT(10) UNSIGNED',
                'allow_null'    => true
            ),
            'last_search'        => array(
                'datatype'        => 'INT(10) UNSIGNED',
                'allow_null'    => true
            ),
        ),
        'UNIQUE KEYS'    => array(
            'user_id_ident_idx'    => array('user_id', 'ident')
        ),
        'INDEXES'        => array(
            'ident_idx'        => array('ident'),
            'logged_idx'    => array('logged')
        )
    );

    if ($db_type == 'mysql' || $db_type == 'mysqli' || $db_type == 'mysql_innodb' || $db_type == 'mysqli_innodb') {
        $schema['UNIQUE KEYS']['user_id_ident_idx'] = array('user_id', 'ident(25)');
        $schema['INDEXES']['ident_idx'] = array('ident(25)');
    }

    if ($db_type == 'mysql_innodb' || $db_type == 'mysqli_innodb') {
        $schema['ENGINE'] = 'InnoDB';
    }

    $db->create_table('online', $schema) or error('Unable to create online table', __FILE__, __LINE__, $db->error());


    $schema = array(
        'FIELDS'        => array(
            'id'            => array(
                'datatype'        => 'SERIAL',
                'allow_null'    => false
            ),
            'poster'        => array(
                'datatype'        => 'VARCHAR(200)',
                'allow_null'    => false,
                'default'        => '\'\''
            ),
            'poster_id'        => array(
                'datatype'        => 'INT(10) UNSIGNED',
                'allow_null'    => false,
                'default'        => '1'
            ),
            'poster_ip'        => array(
                'datatype'        => 'VARCHAR(39)',
                'allow_null'    => true
            ),
            'poster_email'    => array(
                'datatype'        => 'VARCHAR(80)',
                'allow_null'    => true
            ),
            'message'        => array(
                'datatype'        => 'MEDIUMTEXT',
                'allow_null'    => true
            ),
            'hide_smilies'    => array(
                'datatype'        => 'TINYINT(1)',
                'allow_null'    => false,
                'default'        => '0'
            ),
            'posted'        => array(
                'datatype'        => 'INT(10) UNSIGNED',
                'allow_null'    => false,
                'default'        => '0'
            ),
            'edited'        => array(
                'datatype'        => 'INT(10) UNSIGNED',
                'allow_null'    => true
            ),
            'edited_by'        => array(
                'datatype'        => 'VARCHAR(200)',
                'allow_null'    => true
            ),
            'topic_id'        => array(
                'datatype'        => 'INT(10) UNSIGNED',
                'allow_null'    => false,
                'default'        => '0'
            )
        ),
        'PRIMARY KEY'    => array('id'),
        'INDEXES'        => array(
            'topic_id_idx'    => array('topic_id'),
            'multi_idx'        => array('poster_id', 'topic_id')
        )
    );

    $db->create_table('posts', $schema) or error('Unable to create posts table', __FILE__, __LINE__, $db->error());


    $schema = array(
        'FIELDS'        => array(
            'id'            => array(
                'datatype'        => 'SERIAL',
                'allow_null'    => false
            ),
            'post_id'        => array(
                'datatype'        => 'INT(10) UNSIGNED',
                'allow_null'    => false,
                'default'        => '0'
            ),
            'topic_id'        => array(
                'datatype'        => 'INT(10) UNSIGNED',
                'allow_null'    => false,
                'default'        => '0'
            ),
            'forum_id'        => array(
                'datatype'        => 'INT(10) UNSIGNED',
                'allow_null'    => false,
                'default'        => '0'
            ),
            'reported_by'    => array(
                'datatype'        => 'INT(10) UNSIGNED',
                'allow_null'    => false,
                'default'        => '0'
            ),
            'created'        => array(
                'datatype'        => 'INT(10) UNSIGNED',
                'allow_null'    => false,
                'default'        => '0'
            ),
            'message'        => array(
                'datatype'        => 'TEXT',
                'allow_null'    => true
            ),
            'zapped'        => array(
                'datatype'        => 'INT(10) UNSIGNED',
                'allow_null'    => true
            ),
            'zapped_by'        => array(
                'datatype'        => 'INT(10) UNSIGNED',
                'allow_null'    => true
            )
        ),
        'PRIMARY KEY'    => array('id'),
        'INDEXES'        => array(
            'zapped_idx'    => array('zapped')
        )
    );

    $db->create_table('reports', $schema) or error('Unable to create reports table', __FILE__, __LINE__, $db->error());


    $schema = array(
        'FIELDS'        => array(
            'id'            => array(
                'datatype'        => 'INT(10) UNSIGNED',
                'allow_null'    => false,
                'default'        => '0'
            ),
            'ident'            => array(
                'datatype'        => 'VARCHAR(200)',
                'allow_null'    => false,
                'default'        => '\'\''
            ),
            'search_data'    => array(
                'datatype'        => 'MEDIUMTEXT',
                'allow_null'    => true
            )
        ),
        'PRIMARY KEY'    => array('id'),
        'INDEXES'        => array(
            'ident_idx'    => array('ident')
        )
    );

    if ($db_type == 'mysql' || $db_type == 'mysqli' || $db_type == 'mysql_innodb' || $db_type == 'mysqli_innodb') {
        $schema['INDEXES']['ident_idx'] = array('ident(8)');
    }

    $db->create_table('search_cache', $schema) or error('Unable to create search_cache table', __FILE__, __LINE__, $db->error());


    $schema = array(
        'FIELDS'        => array(
            'post_id'        => array(
                'datatype'        => 'INT(10) UNSIGNED',
                'allow_null'    => false,
                'default'        => '0'
            ),
            'word_id'        => array(
                'datatype'        => 'INT(10) UNSIGNED',
                'allow_null'    => false,
                'default'        => '0'
            ),
            'subject_match'    => array(
                'datatype'        => 'TINYINT(1)',
                'allow_null'    => false,
                'default'        => '0'
            )
        ),
        'INDEXES'        => array(
            'word_id_idx'    => array('word_id'),
            'post_id_idx'    => array('post_id')
        )
    );

    $db->create_table('search_matches', $schema) or error('Unable to create search_matches table', __FILE__, __LINE__, $db->error());


    $schema = array(
        'FIELDS'        => array(
            'id'            => array(
                'datatype'        => 'SERIAL',
                'allow_null'    => false
            ),
            'word'            => array(
                'datatype'        => 'VARCHAR(20)',
                'allow_null'    => false,
                'default'        => '\'\'',
                'collation'        => 'bin'
            )
        ),
        'PRIMARY KEY'    => array('word'),
        'INDEXES'        => array(
            'id_idx'    => array('id')
        )
    );

    if ($db_type == 'sqlite' || $db_type == 'sqlite3') {
        $schema['PRIMARY KEY'] = array('id');
        $schema['UNIQUE KEYS'] = array('word_idx'    => array('word'));
    }

    $db->create_table('search_words', $schema) or error('Unable to create search_words table', __FILE__, __LINE__, $db->error());


    $schema = array(
        'FIELDS'        => array(
            'user_id'        => array(
                'datatype'        => 'INT(10) UNSIGNED',
                'allow_null'    => false,
                'default'        => '0'
            ),
            'topic_id'        => array(
                'datatype'        => 'INT(10) UNSIGNED',
                'allow_null'    => false,
                'default'        => '0'
            )
        ),
        'PRIMARY KEY'    => array('user_id', 'topic_id')
    );

    $db->create_table('topic_subscriptions', $schema) or error('Unable to create topic subscriptions table', __FILE__, __LINE__, $db->error());


    $schema = array(
        'FIELDS'        => array(
            'user_id'        => array(
                'datatype'        => 'INT(10) UNSIGNED',
                'allow_null'    => false,
                'default'        => '0'
            ),
            'forum_id'        => array(
                'datatype'        => 'INT(10) UNSIGNED',
                'allow_null'    => false,
                'default'        => '0'
            )
        ),
        'PRIMARY KEY'    => array('user_id', 'forum_id')
    );

    $db->create_table('forum_subscriptions', $schema) or error('Unable to create forum subscriptions table', __FILE__, __LINE__, $db->error());


    $schema = array(
        'FIELDS'        => array(
            'id'            => array(
                'datatype'        => 'SERIAL',
                'allow_null'    => false
            ),
            'poster'        => array(
                'datatype'        => 'VARCHAR(200)',
                'allow_null'    => false,
                'default'        => '\'\''
            ),
            'subject'        => array(
                'datatype'        => 'VARCHAR(255)',
                'allow_null'    => false,
                'default'        => '\'\''
            ),
            'posted'        => array(
                'datatype'        => 'INT(10) UNSIGNED',
                'allow_null'    => false,
                'default'        => '0'
            ),
            'first_post_id'    => array(
                'datatype'        => 'INT(10) UNSIGNED',
                'allow_null'    => false,
                'default'        => '0'
            ),
            'last_post'        => array(
                'datatype'        => 'INT(10) UNSIGNED',
                'allow_null'    => false,
                'default'        => '0'
            ),
            'last_post_id'    => array(
                'datatype'        => 'INT(10) UNSIGNED',
                'allow_null'    => false,
                'default'        => '0'
            ),
            'last_poster'    => array(
                'datatype'        => 'VARCHAR(200)',
                'allow_null'    => true
            ),
            'num_views'        => array(
                'datatype'        => 'MEDIUMINT(8) UNSIGNED',
                'allow_null'    => false,
                'default'        => '0'
            ),
            'num_replies'    => array(
                'datatype'        => 'MEDIUMINT(8) UNSIGNED',
                'allow_null'    => false,
                'default'        => '0'
            ),
            'closed'        => array(
                'datatype'        => 'TINYINT(1)',
                'allow_null'    => false,
                'default'        => '0'
            ),
            'sticky'        => array(
                'datatype'        => 'TINYINT(1)',
                'allow_null'    => false,
                'default'        => '0'
            ),
            'moved_to'        => array(
                'datatype'        => 'INT(10) UNSIGNED',
                'allow_null'    => true
            ),
            'forum_id'        => array(
                'datatype'        => 'INT(10) UNSIGNED',
                'allow_null'    => false,
                'default'        => '0'
            )
        ),
        'PRIMARY KEY'    => array('id'),
        'INDEXES'        => array(
            'forum_id_idx'        => array('forum_id'),
            'moved_to_idx'        => array('moved_to'),
            'last_post_idx'        => array('last_post'),
            'first_post_id_idx'    => array('first_post_id')
        )
    );

    $db->create_table('topics', $schema) or error('Unable to create topics table', __FILE__, __LINE__, $db->error());


    $schema = array(
        'FIELDS'        => array(
            'id'                => array(
                'datatype'        => 'SERIAL',
                'allow_null'    => false
            ),
            'group_id'            => array(
                'datatype'        => 'INT(10) UNSIGNED',
                'allow_null'    => false,
                'default'        => '3'
            ),
            'username'            => array(
                'datatype'        => 'VARCHAR(200)',
                'allow_null'    => false,
                'default'        => '\'\''
            ),
            'password'            => array(
                'datatype'        => 'VARCHAR(40)',
                'allow_null'    => false,
                'default'        => '\'\''
            ),
            'email'                => array(
                'datatype'        => 'VARCHAR(80)',
                'allow_null'    => false,
                'default'        => '\'\''
            ),
            'title'                => array(
                'datatype'        => 'VARCHAR(50)',
                'allow_null'    => true
            ),
            'realname'            => array(
                'datatype'        => 'VARCHAR(40)',
                'allow_null'    => true
            ),
            'url'                => array(
                'datatype'        => 'VARCHAR(100)',
                'allow_null'    => true
            ),
            'jabber'            => array(
                'datatype'        => 'VARCHAR(80)',
                'allow_null'    => true
            ),
            'icq'                => array(
                'datatype'        => 'VARCHAR(12)',
                'allow_null'    => true
            ),
            'msn'                => array(
                'datatype'        => 'VARCHAR(80)',
                'allow_null'    => true
            ),
            'aim'                => array(
                'datatype'        => 'VARCHAR(30)',
                'allow_null'    => true
            ),
            'yahoo'                => array(
                'datatype'        => 'VARCHAR(30)',
                'allow_null'    => true
            ),
            'location'            => array(
                'datatype'        => 'VARCHAR(30)',
                'allow_null'    => true
            ),
            'signature'            => array(
                'datatype'        => 'TEXT',
                'allow_null'    => true
            ),
            'disp_topics'        => array(
                'datatype'        => 'TINYINT(3) UNSIGNED',
                'allow_null'    => true
            ),
            'disp_posts'        => array(
                'datatype'        => 'TINYINT(3) UNSIGNED',
                'allow_null'    => true
            ),
            'email_setting'        => array(
                'datatype'        => 'TINYINT(1)',
                'allow_null'    => false,
                'default'        => '1'
            ),
            'notify_with_post'    => array(
                'datatype'        => 'TINYINT(1)',
                'allow_null'    => false,
                'default'        => '0'
            ),
            'auto_notify'        => array(
                'datatype'        => 'TINYINT(1)',
                'allow_null'    => false,
                'default'        => '0'
            ),
            'show_smilies'        => array(
                'datatype'        => 'TINYINT(1)',
                'allow_null'    => false,
                'default'        => '1'
            ),
            'show_img'            => array(
                'datatype'        => 'TINYINT(1)',
                'allow_null'    => false,
                'default'        => '1'
            ),
            'show_img_sig'        => array(
                'datatype'        => 'TINYINT(1)',
                'allow_null'    => false,
                'default'        => '1'
            ),
            'show_avatars'        => array(
                'datatype'        => 'TINYINT(1)',
                'allow_null'    => false,
                'default'        => '1'
            ),
            'show_sig'            => array(
                'datatype'        => 'TINYINT(1)',
                'allow_null'    => false,
                'default'        => '1'
            ),
            'timezone'            => array(
                'datatype'        => 'FLOAT',
                'allow_null'    => false,
                'default'        => '0'
            ),
            'dst'                => array(
                'datatype'        => 'TINYINT(1)',
                'allow_null'    => false,
                'default'        => '0'
            ),
            'time_format'        => array(
                'datatype'        => 'TINYINT(1)',
                'allow_null'    => false,
                'default'        => '0'
            ),
            'date_format'        => array(
                'datatype'        => 'TINYINT(1)',
                'allow_null'    => false,
                'default'        => '0'
            ),
            'language'            => array(
                'datatype'        => 'VARCHAR(25)',
                'allow_null'    => false,
                'default'        => '\''.$db->escape($default_lang).'\''
            ),
            'style'                => array(
                'datatype'        => 'VARCHAR(25)',
                'allow_null'    => false,
                'default'        => '\''.$db->escape($default_style).'\''
            ),
            'num_posts'            => array(
                'datatype'        => 'INT(10) UNSIGNED',
                'allow_null'    => false,
                'default'        => '0'
            ),
            'last_post'            => array(
                'datatype'        => 'INT(10) UNSIGNED',
                'allow_null'    => true
            ),
            'last_search'        => array(
                'datatype'        => 'INT(10) UNSIGNED',
                'allow_null'    => true
            ),
            'last_email_sent'    => array(
                'datatype'        => 'INT(10) UNSIGNED',
                'allow_null'    => true
            ),
            'last_report_sent'    => array(
                'datatype'        => 'INT(10) UNSIGNED',
                'allow_null'    => true
            ),
            'registered'        => array(
                'datatype'        => 'INT(10) UNSIGNED',
                'allow_null'    => false,
                'default'        => '0'
            ),
            'registration_ip'    => array(
                'datatype'        => 'VARCHAR(39)',
                'allow_null'    => false,
                'default'        => '\'0.0.0.0\''
            ),
            'last_visit'        => array(
                'datatype'        => 'INT(10) UNSIGNED',
                'allow_null'    => false,
                'default'        => '0'
            ),
            'admin_note'        => array(
                'datatype'        => 'VARCHAR(30)',
                'allow_null'    => true
            ),
            'activate_string'    => array(
                'datatype'        => 'VARCHAR(80)',
                'allow_null'    => true
            ),
            'activate_key'        => array(
                'datatype'        => 'VARCHAR(8)',
                'allow_null'    => true
            ),
        ),
        'PRIMARY KEY'    => array('id'),
        'UNIQUE KEYS'    => array(
            'username_idx'        => array('username')
        ),
        'INDEXES'        => array(
            'registered_idx'    => array('registered')
        )
    );

    if ($db_type == 'mysql' || $db_type == 'mysqli' || $db_type == 'mysql_innodb' || $db_type == 'mysqli_innodb') {
        $schema['UNIQUE KEYS']['username_idx'] = array('username(25)');
    }

    $db->create_table('users', $schema) or error('Unable to create users table', __FILE__, __LINE__, $db->error());


    $now = time();

    // Insert the four preset groups
    $db->query('INSERT INTO '.$db->prefix.'groups ('.($db_type != 'pgsql' ? 'g_id, ' : '').'g_title, g_user_title, g_moderator, g_mod_edit_users, g_mod_rename_users, g_mod_change_passwords, g_mod_ban_users, g_read_board, g_view_users, g_post_replies, g_post_topics, g_edit_posts, g_delete_posts, g_delete_topics, g_set_title, g_search, g_search_users, g_send_email, g_post_flood, g_search_flood, g_email_flood, g_report_flood) VALUES('.($db_type != 'pgsql' ? '1, ' : '').'\''.$db->escape(__('Administrators')).'\', \''.$db->escape(__('Administrator')).'\', 0, 0, 0, 0, 0, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 0, 0, 0, 0)') or error('Unable to add group', __FILE__, __LINE__, $db->error());

    $db->query('INSERT INTO '.$db->prefix.'groups ('.($db_type != 'pgsql' ? 'g_id, ' : '').'g_title, g_user_title, g_moderator, g_mod_edit_users, g_mod_rename_users, g_mod_change_passwords, g_mod_ban_users, g_mod_promote_users, g_read_board, g_view_users, g_post_replies, g_post_topics, g_edit_posts, g_delete_posts, g_delete_topics, g_set_title, g_search, g_search_users, g_send_email, g_post_flood, g_search_flood, g_email_flood, g_report_flood) VALUES('.($db_type != 'pgsql' ? '2, ' : '').'\''.$db->escape(__('Moderators')).'\', \''.$db->escape(__('Moderator')).'\', 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 0, 0, 0, 0)') or error('Unable to add group', __FILE__, __LINE__, $db->error());

    $db->query('INSERT INTO '.$db->prefix.'groups ('.($db_type != 'pgsql' ? 'g_id, ' : '').'g_title, g_user_title, g_moderator, g_mod_edit_users, g_mod_rename_users, g_mod_change_passwords, g_mod_ban_users, g_read_board, g_view_users, g_post_replies, g_post_topics, g_edit_posts, g_delete_posts, g_delete_topics, g_set_title, g_search, g_search_users, g_send_email, g_post_flood, g_search_flood, g_email_flood, g_report_flood) VALUES('.($db_type != 'pgsql' ? '3, ' : '').'\''.$db->escape(__('Guests')).'\', NULL, 0, 0, 0, 0, 0, 1, 1, 0, 0, 0, 0, 0, 0, 1, 1, 0, 60, 30, 0, 0)') or error('Unable to add group', __FILE__, __LINE__, $db->error());

    $db->query('INSERT INTO '.$db->prefix.'groups ('.($db_type != 'pgsql' ? 'g_id, ' : '').'g_title, g_user_title, g_moderator, g_mod_edit_users, g_mod_rename_users, g_mod_change_passwords, g_mod_ban_users, g_read_board, g_view_users, g_post_replies, g_post_topics, g_edit_posts, g_delete_posts, g_delete_topics, g_set_title, g_search, g_search_users, g_send_email, g_post_flood, g_search_flood, g_email_flood, g_report_flood) VALUES('.($db_type != 'pgsql' ? '4, ' : '').'\''.$db->escape(__('Members')).'\', NULL, 0, 0, 0, 0, 0, 1, 1, 1, 1, 1, 1, 1, 0, 1, 1, 1, 60, 30, 60, 60)') or error('Unable to add group', __FILE__, __LINE__, $db->error());

    // Insert guest and first admin user
    $db->query('INSERT INTO '.$db_prefix.'users (group_id, username, password, email) VALUES(3, \''.$db->escape(__('Guest')).'\', \''.$db->escape(__('Guest')).'\', \''.$db->escape(__('Guest')).'\')')
        or error('Unable to add guest user. Please check your configuration and try again', __FILE__, __LINE__, $db->error());

    $db->query('INSERT INTO '.$db_prefix.'users (group_id, username, password, email, language, style, num_posts, last_post, registered, registration_ip, last_visit) VALUES(1, \''.$db->escape($username).'\', \''.feather_hash($password1).'\', \''.$email.'\', \''.$db->escape($default_lang).'\', \''.$db->escape($default_style).'\', 1, '.$now.', '.$now.', \''.$db->escape(get_remote_address()).'\', '.$now.')')
        or error('Unable to add administrator user. Please check your configuration and try again', __FILE__, __LINE__, $db->error());

    // Enable/disable avatars depending on file_uploads setting in PHP configuration
    $avatars = in_array(strtolower(@ini_get('file_uploads')), array('on', 'true', '1')) ? 1 : 0;

    // Insert config data
    $feather_config = array(
        'o_cur_version'                => FORUM_VERSION,
        'o_database_revision'        => FORUM_DB_REVISION,
        'o_searchindex_revision'    => FORUM_SI_REVISION,
        'o_parser_revision'            => FORUM_PARSER_REVISION,
        'o_board_title'                => $title,
        'o_board_desc'                => $description,
        'o_default_timezone'        => 0,
        'o_time_format'                => 'H:i:s',
        'o_date_format'                => 'Y-m-d',
        'o_timeout_visit'            => 1800,
        'o_timeout_online'            => 300,
        'o_redirect_delay'            => 1,
        'o_show_version'            => 0,
        'o_show_user_info'            => 1,
        'o_show_post_count'            => 1,
        'o_signatures'                => 1,
        'o_smilies'                    => 1,
        'o_smilies_sig'                => 1,
        'o_make_links'                => 1,
        'o_default_lang'            => $default_lang,
        'o_default_style'            => $default_style,
        'o_default_user_group'        => 4,
        'o_topic_review'            => 15,
        'o_disp_topics_default'        => 30,
        'o_disp_posts_default'        => 25,
        'o_indent_num_spaces'        => 4,
        'o_quote_depth'                => 3,
        'o_quickpost'                => 1,
        'o_users_online'            => 1,
        'o_censoring'                => 0,
        'o_show_dot'                => 0,
        'o_topic_views'                => 1,
        'o_quickjump'                => 1,
        'o_gzip'                    => 0,
        'o_additional_navlinks'        => '',
        'o_report_method'            => 0,
        'o_regs_report'                => 0,
        'o_default_email_setting'    => 1,
        'o_mailing_list'            => $email,
        'o_avatars'                    => $avatars,
        'o_avatars_dir'                => 'img/avatars',
        'o_avatars_width'            => 60,
        'o_avatars_height'            => 60,
        'o_avatars_size'            => 10240,
        'o_search_all_forums'        => 1,
        'o_base_url'                => $base_url,
        'o_admin_email'                => $email,
        'o_webmaster_email'            => $email,
        'o_forum_subscriptions'        => 1,
        'o_topic_subscriptions'        => 1,
        'o_smtp_host'                => null,
        'o_smtp_user'                => null,
        'o_smtp_pass'                => null,
        'o_smtp_ssl'                => 0,
        'o_regs_allow'                => 1,
        'o_regs_verify'                => 0,
        'o_announcement'            => 0,
        'o_announcement_message'    => __('Announcement'),
        'o_rules'                    => 0,
        'o_rules_message'            => __('Rules'),
        'o_maintenance'                => 0,
        'o_maintenance_message'        => __('Maintenance message'),
        'o_default_dst'                => 0,
        'o_feed_type'                => 2,
        'o_feed_ttl'                => 0,
        'p_message_bbcode'            => 1,
        'p_message_img_tag'            => 1,
        'p_message_all_caps'        => 1,
        'p_subject_all_caps'        => 1,
        'p_sig_all_caps'            => 1,
        'p_sig_bbcode'                => 1,
        'p_sig_img_tag'                => 0,
        'p_sig_length'                => 400,
        'p_sig_lines'                => 4,
        'p_allow_banned_email'        => 1,
        'p_allow_dupe_email'        => 0,
        'p_force_guest_email'        => 1
    );

    foreach ($feather_config as $conf_name => $conf_value) {
        $db->query('INSERT INTO '.$db_prefix.'config (conf_name, conf_value) VALUES(\''.$conf_name.'\', '.(is_null($conf_value) ? 'NULL' : '\''.$db->escape($conf_value).'\'').')')
            or error('Unable to insert into table '.$db_prefix.'config. Please check your configuration and try again', __FILE__, __LINE__, $db->error());
    }

    // Insert some other default data
    $subject = __('Test post');
    $message = __('Message');

    $db->query('INSERT INTO '.$db_prefix.'categories (cat_name, disp_position) VALUES(\''.$db->escape(__('Test category')).'\', 1)')
        or error('Unable to insert into table '.$db_prefix.'categories. Please check your configuration and try again', __FILE__, __LINE__, $db->error());

    $db->query('INSERT INTO '.$db_prefix.'forums (forum_name, forum_desc, num_topics, num_posts, last_post, last_post_id, last_poster, disp_position, cat_id) VALUES(\''.$db->escape(__('Test forum')).'\', \''.$db->escape(__('This is just a test forum')).'\', 1, 1, '.$now.', 1, \''.$db->escape($username).'\', 1, 1)')
        or error('Unable to insert into table '.$db_prefix.'forums. Please check your configuration and try again', __FILE__, __LINE__, $db->error());

    $db->query('INSERT INTO '.$db_prefix.'topics (poster, subject, posted, first_post_id, last_post, last_post_id, last_poster, forum_id) VALUES(\''.$db->escape($username).'\', \''.$db->escape($subject).'\', '.$now.', 1, '.$now.', 1, \''.$db->escape($username).'\', 1)')
        or error('Unable to insert into table '.$db_prefix.'topics. Please check your configuration and try again', __FILE__, __LINE__, $db->error());

    $db->query('INSERT INTO '.$db_prefix.'posts (poster, poster_id, poster_ip, message, posted, topic_id) VALUES(\''.$db->escape($username).'\', 2, \''.$db->escape(get_remote_address()).'\', \''.$db->escape($message).'\', '.$now.', 1)')
        or error('Unable to insert into table '.$db_prefix.'posts. Please check your configuration and try again', __FILE__, __LINE__, $db->error());

    $db->end_transaction();


    $alerts = array();

    // Check if we disabled uploading avatars because file_uploads was disabled
    if ($avatars == '0') {
        $alerts[] = __('Alert upload');
    }

    // Add some random bytes at the end of the cookie name to prevent collisions
    $cookie_name = 'feather_cookie_'.random_key(6, false, true);

    // Generate the config.php file data
    $config = generate_config_file();

    // Attempt to write config.php and serve it up for download if writing fails
    $written = false;
    if (forum_is_writable(FEATHER_ROOT)) {
        $fh = @fopen(FEATHER_ROOT.'include/config.php', 'wb');
        if ($fh) {
            fwrite($fh, $config);
            fclose($fh);

            $written = true;
        }
    }

    // Rename htaccess if rewrite is possible
    if (function_exists('apache_get_modules') && in_array('mod_rewrite', apache_get_modules())) {
        rename(FEATHER_ROOT.'.htaccess.dist', FEATHER_ROOT.'.htaccess');
    }


    ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php _e('FeatherBB Installation') ?></title>
<link rel="stylesheet" type="text/css" href="../style/<?php echo $default_style ?>.css" />
</head>
<body>

<div id="puninstall" class="pun">
<div class="top-box"><div><!-- Top Corners --></div></div>
<div class="punwrap">

<section class="container">
    <div id="brdheader" class="block">
            <div class="box">
                    <div id="brdtitle" class="inbox">
                            <h1><span><?php _e('FeatherBB Installation') ?></span></h1>
                            <div id="brddesc"><p><?php _e('FeatherBB has been installed') ?></p></div>
                    </div>
            </div>
    </div>

    <div id="brdmain">

    <div class="blockform">
            <h2><span><?php _e('Final instructions') ?></span></h2>
            <div class="box">
    <?php

    if (!$written) {
        ?>
                    <form method="post" action="">
                            <div class="inform">
                                    <div class="forminfo">
                                            <p><?php _e('Info 17') ?></p>
                                            <p><?php _e('Info 18') ?></p>
                                    </div>
                                    <input type="hidden" name="generate_config" value="1" />
                                    <input type="hidden" name="db_type" value="<?php echo $db_type;
        ?>" />
                                    <input type="hidden" name="db_host" value="<?php echo $db_host;
        ?>" />
                                    <input type="hidden" name="db_name" value="<?php echo feather_escape($db_name);
        ?>" />
                                    <input type="hidden" name="db_username" value="<?php echo feather_escape($db_username);
        ?>" />
                                    <input type="hidden" name="db_password" value="<?php echo feather_escape($db_password);
        ?>" />
                                    <input type="hidden" name="db_prefix" value="<?php echo feather_escape($db_prefix);
        ?>" />
                                    <input type="hidden" name="cookie_name" value="<?php echo feather_escape($cookie_name);
        ?>" />
                                    <input type="hidden" name="cookie_seed" value="<?php echo feather_escape($cookie_seed);
        ?>" />

    <?php if (!empty($alerts)): ?>				<div class="forminfo error-info">
                                            <ul class="error-list">
    <?php

    foreach ($alerts as $cur_alert) {
        echo "\t\t\t\t\t".'<li>'.$cur_alert.'</li>'."\n";
    }
        ?>
                                            </ul>
                                    </div>
    <?php endif;
        ?>			</div>
                            <p class="buttons"><input type="submit" value="<?php _e('Download config.php file') ?>" /></p>
                    </form>

    <?php

    } else {
        ?>
                    <div class="fakeform">
                            <div class="inform">
                                    <div class="forminfo">
                                            <p><?php _e('FeatherBB fully installed') ?></p>
                                    </div>
                            </div>
                    </div>
    <?php

    }

        ?>
            </div>
    </div>
</section>

</div>

</div>
<div class="end-box"><div><!-- Bottom Corners --></div></div>
</div>

</body>
</html>
<?php

}
