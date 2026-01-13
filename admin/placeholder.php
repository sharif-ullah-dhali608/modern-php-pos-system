<?php
session_start();
include('../includes/header.php');
?>
<div class="app-wrapper">
    <?php include('../includes/sidebar.php'); ?>
    <main id="main-content" class="lg:ml-64 flex flex-col h-screen">
        <div class="navbar-fixed-top"><?php include('../includes/navbar.php'); ?></div>
        <div class="p-6">
            <h1 class="text-2xl font-bold">Feature Coming Soon</h1>
            <p>This functionality is currently under development.</p>
            <a href="javascript:history.back()" class="text-blue-500 hover:underline">Go Back</a>
        </div>
    </main>
</div>
