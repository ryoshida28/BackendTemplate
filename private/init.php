<?php
    require_once('db_config.php');
?>

<?php
    define('PRIVATE_PATH', dirname(__FILE__));
    define('ROOT', dirname(PRIVATE_PATH));
    define('BASE_URL', 'http://localhost/BackendTemplate');
    define('ACCOUNTS_PATH', ROOT . '/accounts');
    define('SESSIONS_PATH', ROOT . '/sessions');
    

    //  Define Session Types
    define('SESSION_GUEST', 'session_guest');
    define('SESSION_USER', 'session_user');
    define('SESSION_STAFF', 'session_staff');
    define('SESSION_ADMIN', 'session_admin');
?>

<?php
    if (!is_configured()) {     // Configure Tables if not already configured
        config_tables();
    }

    if (!admin_set() && basename($_SERVER['REQUEST_URI']) != 'admin_config.php') {
        header('Location: ' . PRIVATE_PATH . '/admin_config.php');
    }

    session_start();
    if (!isset($_SESSION['permissions'])) {
        $_SESSION['permissions'] = SESSION_GUEST;
    }
    
    if (!isset($_SESSION['accountID'])) {
        $_SESSION['accountID'] = 0;
    }

    date_default_timezone_set('America/Los_Angeles');
?>