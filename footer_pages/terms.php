<?php
session_start();
include('../config/dbcon.php');

$page_title = "Terms of Service - Velocity POS";
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
                    <h1 class="text-3xl font-bold text-slate-800 mb-6">Terms of Service</h1>

                    <div class="space-y-6 text-slate-600 leading-relaxed">
                        <p>Last updated: <?php echo date("F j, Y"); ?></p>

                        <h2 class="text-xl font-bold text-slate-800 mt-4">1. Acceptance of Terms</h2>
                        <p>By accessing or using the Velocity POS system, you agree to be bound by these Terms of Service and all applicable laws and regulations.</p>

                        <h2 class="text-xl font-bold text-slate-800 mt-4">2. Use License</h2>
                        <p>Permission is granted to temporarily download one copy of the materials (information or software) on Velocity POS for personal, non-commercial transitory viewing only. This is the grant of a license, not a transfer of title, and under this license you may not:</p>
                        <ul class="list-disc pl-5 mt-2 space-y-1">
                            <li>modify or copy the materials;</li>
                            <li>use the materials for any commercial purpose, or for any public display (commercial or non-commercial);</li>
                            <li>attempt to decompile or reverse engineer any software contained on Velocity POS;</li>
                            <li>remove any copyright or other proprietary notations from the materials; or</li>
                            <li>transfer the materials to another person or "mirror" the materials on any other server.</li>
                        </ul>

                        <h2 class="text-xl font-bold text-slate-800 mt-4">3. Disclaimer</h2>
                        <p>The materials on Velocity POS are provided on an 'as is' basis. Makes no warranties, expressed or implied, and hereby disclaims and negates all other warranties including, without limitation, implied warranties or conditions of merchantability, fitness for a particular purpose, or non-infringement of intellectual property or other violation of rights.</p>

                        <h2 class="text-xl font-bold text-slate-800 mt-4">4. Limitations</h2>
                        <p>In no event shall Velocity POS or its suppliers be liable for any damages (including, without limitation, damages for loss of data or profit, or due to business interruption) arising out of the use or inability to use the materials on Velocity POS.</p>

                        <h2 class="text-xl font-bold text-slate-800 mt-4">5. Governing Law</h2>
                        <p>These terms and conditions are governed by and construed in accordance with the laws and you irrevocably submit to the exclusive jurisdiction of the courts in that location.</p>
                    </div>
                </div>
            </div>
            <?php include('../includes/footer.php'); ?>
        </div>
    </main>
</div>
