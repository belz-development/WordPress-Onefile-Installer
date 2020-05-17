<?php

/**
 * some defines
 */
define("DEBUG", true);
define("TITLE", "WordPress Onefile Installer");
define("WP_VERSION_URL", "https://wordpress.org/news/2019/04/minimum-php-version-update/");
define("WP_RELEASES_URL", "https://wordpress.org/download/releases/");
define("WP_URL", "https://wordpress.org/");

/**
 * check if server allow ... shell_exec,...
 *
 * @param [type] $func
 * @return boolean
 */
function isEnabled($func) {
    return is_callable($func) && false === stripos(ini_get('disable_functions'), $func);
}

/**
 * check if URL exists
 *
 * @param [type] $url
 * @return void
 */
function url_exists($url) {
    if (!$fp = curl_init($url)) return false;
    return true;
}

/**
 * if DEBUG is true
 * display errors and measure the time
 */
if (DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', true);
    $start = microtime(true); 
}

/**
 * start output / msg array
 */
$msg = array();

/**
 * if shell_exec works 
 */
if (!isEnabled('shell_exec')) {
    die("sry but shell_exec doesn't work");
}

/**
 * if shell_exec works 
 */
#if (!isEnabled('popen')) {
#    die("sry but popen doesn't work");
#}

// $msg['locale'] = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);

/**
 * check SSL
 */
if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] != 'on') {
    $msg['ssl  '] = "SSL: no SSL request";
} else {
    $msg['ssl'] = "SSL: enabled";
}

/**
 * get URL
 */
$url = 'http'.(isset($_SERVER['HTTPS'])?'s':'').'://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF']).'/';
$msg['url'] = 'URL: ' . $url;

/**
 * get PHP
 */
$msg['php_v'] =  'PHP version: ' . phpversion();

/**
 * get workspace
 */
$msg['dir'] = 'Workspace: ' . __DIR__;


/**
 * check if php version is enough
 */
if (version_compare(phpversion(), '5.6', '<')) {
    die("your php version isn't high enough: ".phpversion()."</br>take a look at ".WP_VERSION_URL);
} elseif (version_compare(phpversion(), '7.0', '<')) {
    if(date("Y") < '2021'){
        die("your php version isn't high enough: ".phpversion()."</br>take a look at ".WP_VERSION_URL);
    } else {
        $message = "your php version will no longer suffice in 2020: ".phpversion()."\ntake a look at ".WP_VERSION_URL;
        echo "<script type='text/javascript'>alert('$message');</script>";
    }
}

/**
 * if form sended - perform the action
 */
if (!empty($_POST)){
    $wpdl = htmlspecialchars($_POST["release"]);
    if (strpos($wpdl, WP_URL) !== false) {
        $file = str_replace( WP_URL, "", $wpdl);
        // all commands for exec
        $cmd[] = "wget " . $wpdl . ";";
        $cmd[] = "tar xfz " . $file . ";";
        $cmd[] = "mv wordpress/* ./;";
        $cmd[] = "rmdir ./wordpress/;";        
        $cmd[] = "rm -f latest.tar.gz readme.html license.txt liesmich.html ./wp-content/plugins/hello.php wpi.php ". $file . ";";
        foreach ($cmd as $key => $value) {
            shell_exec($value);
        }
        header("Location: " . $url);
        exit();
        /*
        foreach ($cmd as $key => $value) {
            while (@ ob_end_flush()); // end all output buffers if any
            $proc = popen($value, 'r');
            echo '<pre>';
            while (!feof($proc))
            {
                echo fread($proc, 4096);
                @ flush();
            }
            echo '</pre>';
        }
        */
    } else {
        die("Sry there is somewhere an error. The requested wp version isn't from wordpress.org");
    }
    exit();
}

/**
 * check if wp releases are online
 */
if (url_exists("WP_RELEASES_URL")) {
    $msg['wp_online'] = 'WP Releases: online - '. WP_RELEASES_URL;
} else {
    $msg['wp_online'] = 'WP Releases: offline - '. WP_RELEASES_URL;
}

/**
 * crawl WP_RELEASES_URL for all links with 
 */
$html = file_get_contents(WP_RELEASES_URL);
$matches = array();
// get all links
preg_match_all('~<a.*?href="(.*?)".*?>~', $html, $matches);
$release = array();
$ending = "tar.gz";
// only with $ending
foreach($matches[1] as $key => $value){
    if ( substr_compare($value, $ending, strlen($value)-strlen($ending), strlen($ending)) === 0 ) {
        $release[] = $value;
    }
}
// no duplicates
$release = array_unique($release);
// get the names

#echo "<pre>";
#print_r($release);
#echo "</pre>";

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo TITLE; ?></title>
    <style>
        #app{
            margin: auto;
            width: 50%;
            border: 2px solid #21759b;
            padding: 10px;
        }        
        #msg{
            border: 2px solid gray;
            padding: 10px;
            margin-bottom: 10px;
        }
        form{
            border: 2px solid green;
            padding: 10px;
            margin: 0;
        }
        select{
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div id="app">
        <div id="msg">
            <?php
            foreach($msg as $key => $value){
                echo "<p>".$value."</pre>";
            };
            ?>
        </div>

        <form action="<?php echo htmlentities($_SERVER['PHP_SELF']); ?>" method="POST" name="wpdl"> 
            <label>Select your WordPress version for download and extract (latest one is on top)</label>
            <select id="version" name="release">
                <?php
                foreach($release as $key => $value){
                    echo '<option value="' . $value . '">' . str_replace( WP_URL, "", $value) . '</option>';
                };
                ?>
            </select>
            <input type="submit" name="submit" value="Let's go"><br>
        </form>

    </div>
</body>
</html>

<?php

if (DEBUG) {
    $end = microtime(true) - $start; 
    echo "Processing the script in: $end Sec.";
}

?>