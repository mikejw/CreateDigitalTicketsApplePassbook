<?php





// ----------------------------------------------------------------------------------------------------
// - Display Errors
// ----------------------------------------------------------------------------------------------------
ini_set('display_errors', 'On');
ini_set('html_errors', 0);

// ----------------------------------------------------------------------------------------------------
// - Error Reporting
// ----------------------------------------------------------------------------------------------------
error_reporting(-1);

// ----------------------------------------------------------------------------------------------------
// - Shutdown Handler
// ----------------------------------------------------------------------------------------------------
function ShutdownHandler()
{
    if(@is_array($error = @error_get_last()))
    {
        return(@call_user_func_array('ErrorHandler', $error));
    };

    return(TRUE);
};

register_shutdown_function('ShutdownHandler');

// ----------------------------------------------------------------------------------------------------
// - Error Handler
// ----------------------------------------------------------------------------------------------------
function ErrorHandler($type, $message, $file, $line)
{
    $_ERRORS = Array(
        0x0001 => 'E_ERROR',
        0x0002 => 'E_WARNING',
        0x0004 => 'E_PARSE',
        0x0008 => 'E_NOTICE',
        0x0010 => 'E_CORE_ERROR',
        0x0020 => 'E_CORE_WARNING',
        0x0040 => 'E_COMPILE_ERROR',
        0x0080 => 'E_COMPILE_WARNING',
        0x0100 => 'E_USER_ERROR',
        0x0200 => 'E_USER_WARNING',
        0x0400 => 'E_USER_NOTICE',
        0x0800 => 'E_STRICT',
        0x1000 => 'E_RECOVERABLE_ERROR',
        0x2000 => 'E_DEPRECATED',
        0x4000 => 'E_USER_DEPRECATED'
    );

    if(!@is_string($name = @array_search($type, @array_flip($_ERRORS))))
    {
        $name = 'E_UNKNOWN';
    };

    return(print(@sprintf("%s Error in file \xBB%s\xAB at line %d: %s\n", $name, @basename($file), $line, $message)));
};

$old_error_handler = set_error_handler("ErrorHandler");

// other php code


























// error_reporting(E_ALL|E_STRICT);
// ini_set('display_errors', true);
/**
 * Disable Varnish cache - From AppFog forum
 * Pragma: no-cache
 * Cache-Control: s-maxage=0, max-age=0, must-revalidate, no-cache
 */
header('Pragma: no-cache');
header('Cache-Control: s-maxage=0, max-age=0, must-revalidate, no-cache');
header('content-type: text/html; charset=UTF-8');

// Basic definitions
define(
    'APPLE_CERTIFICATE',
    dirname(__FILE__) . '/../data/Certificate/AppleWWDRCA.pem'
);

$config = array();
$config['app'] = array(
    'name' => 'My Passbook (test) Server',
    'templates.path' => dirname(__FILE__) . '/../templates/',
    'log.enabled' => true,
    'log.level' => 3, // Equivalent to \Slim\Log::INFO
    'passes.path' => 'templates/passes',
    'passes.store' => 'data/passes',
    'passes.passType' => 'YourPassType',
    'passes.data' => array(
        // The name of this key must match with the corresponding
        // keys in pass.json
        'passTypeIdentifier' => 'pass.your.passTypeID',
        'teamIdentifier' => 'YourTeamIdentifier',
        'organizationName' => 'Your Company Name',
        'description' => 'Your Pass Description',
        'logoText' => 'YourLogo',
        'foregroundColor' => 'rgb(nnn, nnn, nnn)',
        'backgroundColor' => 'rgb(nnn, nnn, nnn)',
    ),
    'passes.certfile' => dirname(__FILE__) . '/../data/Certificate/YourPassCertificate.pem',
    'passes.certpass' => 'YourCertificatePassword',
    'smtp.host' => 'mail.yoursite.com',
    'smtp.port' => 25,
    'smtp.username' => 'info@yoursite.com',
    'smtp.password' => 'Secret',
    'smtp.from' => array('info@yoursite.com' => 'Your Name / Your Company'),
);

// Default config = local dev
$configDir = dirname(__FILE__) . '/../config';
$configFile = "$configDir/local.php";

// Check for the AppFog env
if ($services = getenv('VCAP_SERVICES')) {
    $services = json_decode($services, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        exit("Unable to load VCAP_SERVICES");
    }
    $configFile = "$configDir/appfog.php";
}

// Check other mode file
$modeFile = dirname(__FILE__) . '/../.mode';
if (is_readable($modeFile)) {
    $mode = trim(file_get_contents($modeFile));
    $configFile = "$configDir/$mode.php";
}

// Try to load the config file
if (is_readable($configFile)) {
    require_once $configFile;
    if (!empty($config['app']['mode'])) {
        $config['app']['mode'] = $mode;
    }
} else {
    exit("Unable to load config file");
}

require_once dirname(__FILE__) . '/../vendor/autoload.php';

// Init ORM
ORM::configure(sprintf('mysql:host=%s;dbname=%s', $config['db']['host'], $config['db']['name']));
ORM::configure('username', $config['db']['user']);
ORM::configure('password', $config['db']['password']);

// Enables bulk actions (eg. delete) on multiple records
ORM::configure('return_result_sets', true);


// Init App
$app = new Slim\Slim($config['app']);

// Init Logging
$app->log = $app->getLog();
$app->log->setWriter(
    new Slim\Extras\Log\DateTimeFileWriter(
        array('path' => dirname(__FILE__) . '/../data/logs')
    )
);


// Init Headers detection
// Please Note: Apache is required
// If you are using another web server you must adapt the following code
// Grabs the real HTTP headers from the web server and extract the "Authorization"
$app->hook('slim.before', function () use($app) {

    // Replace this line with your server's version
    $headers = apache_request_headers();
    if (!empty($headers['Authorization'])) {
        $env = $app->environment();
        $env['Authorization'] = $headers['Authorization'];
    }
}, 5);
