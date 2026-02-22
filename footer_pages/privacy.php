<?php
session_start();
include('../config/dbcon.php');

$page_title = "Privacy Policy - Velocity POS";
include('../includes/header.php');
?>

<div class="app-wrapper">
    <?php include('../includes/sidebar.php'); ?>

    <main id="main-content" class="lg:ml-64 flex flex-col h-screen">
        <div class="navbar-fixed-top">
            <?php include('../includes/navbar.php'); ?>
        </div>

        <div class="content-scroll-area custom-scroll">
            <div class="p-6">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-8 max-w-4xl mx-auto">
                    <h1 class="text-3xl font-bold text-slate-800 mb-6">Privacy Policy</h1>
                    
                    <div class="space-y-6 text-slate-600 leading-relaxed">
                        <p>Last updated: <?php echo date("F j, Y"); ?></p>

                        <h2 class="text-xl font-bold text-slate-800 mt-4">1. Introduction</h2>
                        <p>Welcome to Velocity POS. We respect your privacy and are committed to protecting your personal data. This privacy policy will inform you as to how we look after your personal data when you visit our application and tell you about your privacy rights and how the law protects you.</p>

                        <h2 class="text-xl font-bold text-slate-800 mt-4">2. Data We Collect</h2>
                        <p>We may collect, use, store and transfer different kinds of personal data about you which we have grouped together follows:</p>
                        <ul class="list-disc pl-5 mt-2 space-y-1">
                            <li><strong>Identity Data:</strong> includes first name, last name, username or similar identifier.</li>
                            <li><strong>Contact Data:</strong> includes billing address, delivery address, email address and telephone numbers.</li>
                            <li><strong>Transaction Data:</strong> includes details about payments to and from you and other details of products and services you have purchased from us.</li>
                            <li><strong>Technical Data:</strong> includes internet protocol (IP) address, your login data, browser type and version, time zone setting and location.</li>
                        </ul>

                        <h2 class="text-xl font-bold text-slate-800 mt-4">3. How We Use Your Data</h2>
                        <p>We will only use your personal data when the law allows us to. Most commonly, we will use your personal data in the following circumstances:</p>
                        <ul class="list-disc pl-5 mt-2 space-y-1">
                            <li>Where we need to perform the contract we are about to enter into or have entered into with you.</li>
                            <li>Where it is necessary for our legitimate interests (or those of a third party) and your interests and fundamental rights do not override those interests.</li>
                            <li>Where we need to comply with a legal or regulatory obligation.</li>
                        </ul>

                        <h2 class="text-xl font-bold text-slate-800 mt-4">4. Data Security</h2>
                        <p>We have put in place appropriate security measures to prevent your personal data from being accidentally lost, used or accessed in an unauthorized way, altered or disclosed.</p>

                        <h2 class="text-xl font-bold text-slate-800 mt-4">5. Contact Us</h2>
                        <p>If you have any questions about this privacy policy or our privacy practices, please contact the support team.</p>
                    </div>
                </div>
            </div>
            <?php include('../includes/footer.php'); ?>
        </div>
    </main>
</div>
