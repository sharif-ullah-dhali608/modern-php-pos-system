<?php
session_start();
include('../config/dbcon.php');

$page_title = "Support - Velocity POS";
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
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 max-w-5xl mx-auto">
                    
                    <!-- Contact Information -->
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 flex flex-col justify-between h-full"> 
                        <div>
                            <h1 class="text-2xl font-bold text-slate-800 mb-4">Contact Support</h1>
                            <p class="text-slate-500 mb-6 text-sm">Our support team is available to assist you with any issues or questions you may have.</p>
                            
                            <div class="space-y-6">
                                <div class="flex items-start gap-3">
                                    <div class="w-10 h-10 rounded-full bg-indigo-50 flex items-center justify-center text-indigo-600 flex-shrink-0">
                                        <i class="fas fa-envelope"></i>
                                    </div>
                                    <div>
                                        <h3 class="font-bold text-slate-800 text-base">Email Us</h3>
                                        <p class="text-slate-400 text-xs mb-1">For general inquiries and technical support.</p>
                                        <a href="mailto:support@pos-system.com" class="text-indigo-600 font-medium hover:underline text-sm">support@pos-system.com</a>
                                    </div>
                                </div>

                                <div class="flex items-start gap-3">
                                    <div class="w-10 h-10 rounded-full bg-emerald-50 flex items-center justify-center text-emerald-600 flex-shrink-0">
                                        <i class="fas fa-phone-alt"></i>
                                    </div>
                                    <div>
                                        <h3 class="font-bold text-slate-800 text-base">Call Us</h3>
                                        <p class="text-slate-400 text-xs mb-1">Mon-Fri from 9am to 6pm.</p>
                                        <a href="tel:+1234567890" class="text-emerald-600 font-medium hover:underline text-sm">+1 (234) 567-890</a>
                                    </div>
                                </div>

                                <div class="flex items-start gap-3">
                                    <div class="w-10 h-10 rounded-full bg-orange-50 flex items-center justify-center text-orange-600 flex-shrink-0">
                                        <i class="fas fa-map-marker-alt"></i>
                                    </div>
                                    <div>
                                        <h3 class="font-bold text-slate-800 text-base">Visit Us</h3>
                                        <p class="text-slate-400 text-xs">
                                            123 POS Street, Tech City<br>
                                            Innovation District, 45678
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- FAQ / Support Form Placeholder -->
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 h-full flex flex-col"> 
                        <h2 class="text-2xl font-bold text-slate-800 mb-4">Send a Message</h2>
                        <form action="#" method="POST" class="space-y-5 flex-grow flex flex-col mt-2">
                            <div class="relative group">
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1.5 ml-1">Subject</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none z-10">
                                        <i class="fas fa-tag text-slate-400 group-focus-within:text-indigo-600 transition-colors bg-white pr-2 border-r border-slate-100"></i>
                                    </div>
                                    <select class="block w-full pl-12 pr-10 py-3 text-sm font-semibold text-slate-700 bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all duration-200 appearance-none cursor-pointer hover:border-indigo-200">
                                        <option>Technical Issue</option>
                                        <option>Feature Request</option>
                                        <option>Billing Inquiry</option>
                                        <option>Other</option>
                                    </select>
                                    <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none z-10">
                                        <i class="fas fa-chevron-down text-slate-400 text-xs"></i>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="flex-grow flex flex-col relative group">
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1.5 ml-1">Message</label>
                                <div class="relative flex-grow flex flex-col">
                                    <div class="absolute top-3.5 left-3 pointer-events-none z-10">
                                        <i class="fas fa-comment-dots text-slate-400 group-focus-within:text-indigo-600 transition-colors bg-white pr-2 border-r border-slate-100 h-6 flex items-center"></i>
                                    </div>
                                    <textarea class="block w-full pl-12 pr-4 py-3 text-sm font-semibold text-slate-700 bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all duration-200 flex-grow resize-none hover:border-indigo-200 placeholder-slate-400 leading-relaxed" placeholder="Describe your issue here..."></textarea>
                                </div>
                            </div>
                            
                            <button type="button" onclick="Swal.fire('Sent!', 'Your message has been sent to our support team.', 'success')" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3.5 rounded-xl transition-all shadow-lg hover:shadow-indigo-500/30 hover:-translate-y-0.5 mt-auto flex items-center justify-center gap-3 group/btn">
                                <span class="bg-indigo-500/30 p-1.5 rounded-lg group-hover/btn:bg-indigo-500/50 transition-colors">
                                    <i class="fas fa-paper-plane text-sm"></i>
                                </span>
                                <span>Send Message</span>
                            </button>
                        </form>
                    </div>

                </div>

                <!-- FAQ Section -->
                <div class="max-w-5xl mx-auto mt-6 mb-4">
                    <h2 class="text-xl font-bold text-slate-800 mb-4">Frequently Asked Questions</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-white p-4 rounded-xl border border-slate-100 shadow-sm hover:shadow-md transition-shadow">
                            <h4 class="font-bold text-slate-700 text-sm mb-1">How do I reset my password?</h4>
                            <p class="text-slate-500 text-xs leading-relaxed">You can reset your password by clicking on the "Forgot Password" link on the login page.</p>
                        </div>
                        <div class="bg-white p-4 rounded-xl border border-slate-100 shadow-sm hover:shadow-md transition-shadow">
                            <h4 class="font-bold text-slate-700 text-sm mb-1">Can I create multiple stores?</h4>
                            <p class="text-slate-500 text-xs leading-relaxed">Yes! You can manage multiple store locations from a single dashboard under the 'Stores' menu.</p>
                        </div>
                        <div class="bg-white p-4 rounded-xl border border-slate-100 shadow-sm hover:shadow-md transition-shadow">
                            <h4 class="font-bold text-slate-700 text-sm mb-1">How do I verify stock levels?</h4>
                            <p class="text-slate-500 text-xs leading-relaxed">Go to Reports > Stock Report to view real-time inventory levels across all your stores.</p>
                        </div>
                        <div class="bg-white p-4 rounded-xl border border-slate-100 shadow-sm hover:shadow-md transition-shadow">
                            <h4 class="font-bold text-slate-700 text-sm mb-1">Is my data secure?</h4>
                            <p class="text-slate-500 text-xs leading-relaxed">Absolutely. We use industry-standard encryption to protect your business and customer data.</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php include('../includes/footer.php'); ?>
        </div>
    </main>
</div>
