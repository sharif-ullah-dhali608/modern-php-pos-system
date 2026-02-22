<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if(!isset($_SESSION['auth'])){
    header("Location: /pos/signin.php");
    exit(0);
}

$page_title = isset($page_title) ? $page_title : "Dashboard - Velocity POS";
include_once __DIR__ . '/permission_helper.php';

// Global Currency Initialization for JavaScript
$sess_store_id = isset($_SESSION['store_id']) ? intval($_SESSION['store_id']) : 0;
if($sess_store_id > 0) {
    // 1. Fetch Currency for Active Store
    $header_curr_q = mysqli_query($conn, "SELECT c.* FROM stores s JOIN currencies c ON s.currency_id = c.id WHERE s.id = '$sess_store_id' LIMIT 1");
} else {
    // 2. Fallback: Default/First Active Store
    $header_curr_q = mysqli_query($conn, "SELECT c.* FROM stores s JOIN currencies c ON s.currency_id = c.id WHERE s.status = 1 ORDER BY s.id ASC LIMIT 1");
}
$header_curr = mysqli_fetch_assoc($header_curr_q) ?: ['symbol_left' => '৳', 'currency_name' => 'Taka'];
$global_symbol = $header_curr['symbol_left'] ?: ($header_curr['symbol_right'] ?: '৳');
$global_name = $header_curr['currency_name'] ?: 'Taka';

// --- Dynamic Favicon Logic ---
$site_favicon = '/pos/assets/images/favicon_logo.png'; // Default
if(isset($conn)) {
    // 1. Determine Store ID (Session or Default)
    $f_sid = isset($_SESSION['store_id']) ? $_SESSION['store_id'] : 1;
    
    // 2. Try DB
    $fav_q = mysqli_query($conn, "SELECT setting_value FROM pos_settings WHERE store_id = '$f_sid' AND setting_key = 'favicon'");
    $found_fav = false;
    
    if($fav_q && mysqli_num_rows($fav_q) > 0) {
        $r = mysqli_fetch_assoc($fav_q);
        if(!empty($r['setting_value'])) {
             $db_fav = $r['setting_value'];
             // Validate
             if(file_exists($_SERVER['DOCUMENT_ROOT'] . $db_fav) && strpos($db_fav, 'logo.png') === false) {
                 $site_favicon = $db_fav;
                 $found_fav = true;
             }
        }
    }
    
    // 3. Fallback: Scan for latest favicon in uploads/branding
    if(!$found_fav) {
        $fav_dir = __DIR__ . '/../uploads/branding/';
        if(is_dir($fav_dir)) {
            // Match favicon_ or checks for .ico too if named differently? 
            // Usually standard uploads might be favicon_* or logo_* used as favicon.
            // Let's assume favicon files start with favicon_ or allow logo if favicon explicit not found?
            // Actually, usually users upload explicit favicon.
            $files = glob($fav_dir . 'favicon_*.*');
            if($files && count($files) > 0) {
                usort($files, function($a, $b) { return filemtime($b) - filemtime($a); });
                $site_favicon = '/pos/uploads/branding/' . basename($files[0]);
            }
        }
    }
}
?>
<script>
    window.currencySymbol = "<?= $global_symbol; ?>";
    window.currencyName = "<?= $global_name; ?>";
</script>
<?php ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Velocity POS - Modern Point of Sale System">
    <title><?= $page_title; ?></title>
    <link rel="icon" type="image/x-icon" href="<?= $site_favicon; ?>" />
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="/pos/assets/css/output.css">
    <link rel="stylesheet" href="/pos/assets/css/customizer.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <style>

    body {
        font-family: 'Plus Jakarta Sans', sans-serif;
        color: #1e293b; 
        background: linear-gradient(135deg, #ffffff, #f7f9fb);
        margin: 0;
        padding: 0;
        overflow: hidden; 
    }

   
    .app-wrapper {
        display: flex;
        height: 100vh;
        width: 100vw;
        overflow: hidden;
    }

    #main-content {
        flex: 1;
        display: flex;
        flex-direction: column;
        min-width: 0;
        transition: margin-left 300ms cubic-bezier(0.4, 0, 0.2, 1);
        height: 100vh;
    }

    .navbar-fixed-top {
        flex-shrink: 0;
        z-index: 50;
    }

 
    .content-scroll-area {
        flex: 1;
        overflow-y: auto;
        overflow-x: hidden;
        background-color: transparent;
        scroll-behavior: smooth;
    }


    .content-scroll-area::-webkit-scrollbar { width: 6px; }
    .content-scroll-area::-webkit-scrollbar-thumb { background: #0d9488; border-radius: 10px; }

    @media (max-width: 1023px) {
        #sidebar { transform: translateX(-100%); z-index: 1000; }
        #sidebar.sidebar-open { transform: translateX(0); }
        #main-content { margin-left: 0 !important; }
    }
</style>
</head>
<body class="min-h-screen">