<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if(!isset($_SESSION['auth'])){
    header("Location: /pos/signin.php");
    exit(0);
}

$page_title = isset($page_title) ? $page_title : "Dashboard - Velocity POS";

// Global Currency Initialization for JavaScript
$header_curr_q = mysqli_query($conn, "SELECT c.* FROM stores s JOIN currencies c ON s.currency_id = c.id WHERE s.status = 1 LIMIT 1");
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
        transition: margin-left 300ms ease-in-out;
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
    
    <!-- <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            min-height: 100vh;
            /* Dark text */
            color: #1e293b; 
            /* Clear Light Background: Pure White / Very slight Grey */
            background: linear-gradient(135deg, #ffffff, #f7f9fb);
            background-size: 400% 400%;
            animation: gradientBG 15s ease infinite;
            overflow-x: hidden; 
        }
        @keyframes gradientBG {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        :root {
            /* Sidebar remains dark for high contrast navigation */
            --sidebar-bg: #008080;
            --sidebar-hover: #008080;
            --sidebar-active: #008080;
            
            /* Light Mode Variables */
            --main-bg: transparent; 
            --card-bg: #ffffff; /* Pure white cards */
            --text-primary: #1e293b; /* Dark slate text */
            --text-secondary: #475569; /* Gray text */
            --border-color: #e2e8f0; /* Light gray border */
        }
        .main-content {
        transition: margin-left 300ms ease-in-out; 
        }

        /* Scrollbar styles remain dark for visibility */
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: var(--main-bg); }
        ::-webkit-scrollbar-thumb { background: var(--sidebar-hover); border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--sidebar-active); }

        @keyframes slideIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .slide-in { animation: slideIn 0.5s ease-out; }

        /* Clear Card Style: Pure white with defined shadow and border */
        .glass-card {
            background: var(--card-bg);
            backdrop-filter: none;
            border: 1px solid var(--border-color); 
            box-shadow: 0 4px 20px -5px rgba(0,0,0,0.08); /* Lighter, cleaner shadow */
        }

        /* Data Table Styles adjusted for clear light mode */
        .data-table { background: var(--card-bg); border-radius: 12px; overflow: hidden; }
        .data-table table { width: 100%; border-collapse: separate; border-spacing: 0; }
        .data-table thead { background: #f1f5f9; } /* Very light background for header */
        .data-table th { padding: 16px; text-align: left; font-weight: 600; color: var(--text-primary); border-bottom: 2px solid var(--border-color); }
        .data-table td { padding: 16px; color: var(--text-secondary); border-bottom: 1px solid var(--border-color); }
        .data-table tbody tr:hover { background: #f8fafc; } /* Lighter hover effect */

        /* Status badges and action buttons remain colorful for contrast */
        .status-badge { display: inline-flex; align-items: center; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .status-active { background: rgba(16, 185, 129, 0.2); color: #10b981; }
        .status-inactive { background: rgba(239, 68, 68, 0.2); color: #ef4444; }

        .btn-action { padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; transition: all 0.3s; border: none; cursor: pointer; }
        .btn-edit { background: rgba(59, 130, 246, 0.2); color: #3b82f6; }
        .btn-edit:hover { background: #3b82f6; color: white; }
        .btn-delete { background: rgba(239, 68, 68, 0.2); color: #ef4444; }
        .btn-delete:hover { background: #ef4444; color: white; }
        .btn-view { background: rgba(16, 185, 129, 0.2); color: #10b981; }
        .btn-view:hover { background: #10b981; color: white; }

        @media (max-width: 1023px) {
            #sidebar { transform: translateX(-100%); }
            #sidebar.sidebar-open { transform: translateX(0); }
            .main-content { margin-left: 0 !important; }
        }
    </style> -->
</head>
<body class="min-h-screen">