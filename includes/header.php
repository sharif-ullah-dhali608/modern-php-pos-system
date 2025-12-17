<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if(!isset($_SESSION['auth'])){
    header("Location: /pos/signin.php");
    exit(0);
}

$page_title = isset($page_title) ? $page_title : "Dashboard - Velocity POS";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Velocity POS - Modern Point of Sale System">
    <title><?= $page_title; ?></title>

    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@3.4.13/dist/tailwind.min.css">
    <script src="https://cdn.tailwindcss.com"></script>

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">

    <style>
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
    </style>
</head>
<body class="min-h-screen">