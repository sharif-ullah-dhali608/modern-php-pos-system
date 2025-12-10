<?php
session_start();
if(isset($_SESSION['auth'])){
    header('Location: index.php');
    exit();
}

// 1. SESSION DATA RECOVERY
$old_email = isset($_SESSION['input_email']) ? $_SESSION['input_email'] : '';
$old_password = isset($_SESSION['input_password']) ? $_SESSION['input_password'] : ''; 
$error_field = isset($_SESSION['error_field']) ? $_SESSION['error_field'] : '';
$captcha_error = isset($_SESSION['captcha_error']) ? $_SESSION['captcha_error'] : ''; 

// 2. CHECK ERROR STATUS
$is_error = !empty($error_field); 

// 3. INPUT CLASSES
$base_input = "w-full rounded-lg px-4 py-2.5 md:py-3 outline-none transition-all duration-200 border text-sm font-medium";


$input_style = $is_error 
    ? $base_input . ' border-red-500 bg-white text-slate-800 focus:ring-2 focus:ring-red-500' 
    : $base_input . ' border-slate-200 bg-slate-50 text-slate-800 focus:ring-2 focus:ring-teal-500 focus:border-teal-500 focus:bg-white';

// 4. LABEL CLASSES (Always Normal)
$label_style = "block text-xs font-bold mb-1 uppercase tracking-wide text-slate-700";

// 5. ERROR MESSAGE VISIBILITY
$error_msg_display = $is_error ? '' : 'hidden';

// 6. CLEAR SESSION (Flash Data)
unset($_SESSION['input_email']);
unset($_SESSION['input_password']); 
unset($_SESSION['error_field']);
unset($_SESSION['captcha_error']); 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>POS Terminal Login</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <style>
        body { font-family: 'Public Sans', sans-serif; }
        
        /* === 1. MOVING GRADIENT === */
        .animate-gradient {
            background: linear-gradient(-45deg, #ccfbf1, #5eead4, #0f766e, #115e59, #99f6e4);
            background-size: 400% 400%;
            animation: gradientBG 15s ease infinite;
        }
        @keyframes gradientBG {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* === 2. GENERATIVE TEXT ANIMATION (Buttery Smooth Loop) === */
        .gen-char {
            display: inline-block;
            opacity: 0;
            filter: blur(10px);
            transform: translateY(15px);
            /* Infinite loop: 6s cycle */
            animation: butterLoop 6s cubic-bezier(0.16, 1, 0.3, 1) infinite;
        }

        /* Logic: Enter (0-15%), Stay (15-75%), Exit (75-90%), Wait (90-100%) */
        @keyframes butterLoop {
            0% { opacity: 0; filter: blur(10px); transform: translateY(15px); }
            15% { opacity: 1; filter: blur(0); transform: translateY(0); }
            75% { opacity: 1; filter: blur(0); transform: translateY(0); }
            90% { opacity: 0; filter: blur(5px); transform: translateY(-10px); }
            100% { opacity: 0; filter: blur(10px); transform: translateY(15px); }
        }

        /* === 3. FLOATING ANIMATIONS === */
        .float-slow { animation: float 8s ease-in-out infinite; }
        .float-medium { animation: float 6s ease-in-out infinite; }
        .float-fast { animation: float 4s ease-in-out infinite; }
        @keyframes float {
            0% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-30px) rotate(5deg); }
            100% { transform: translateY(0px) rotate(0deg); }
        }

        .shape-shadow { box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2), 0 10px 10px -5px rgba(0, 0, 0, 0.1); }
        .icon-shadow { filter: drop-shadow(0 10px 10px rgba(0, 0, 0, 0.25)); }

        .animate-slide-up { animation: slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
        @keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

        .glass-modal { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(20px); }
        
        /* Tooltip */
        .tooltip-group { position: relative; }
        .tooltip-group:hover .tooltip { opacity: 1; transform: translateY(0); }
        .tooltip {
            pointer-events: none; position: absolute; bottom: 100%; left: 50%;
            transform: translateX(-50%) translateY(5px);
            background: rgba(0,0,0,0.8); color: white; padding: 4px 8px;
            border-radius: 4px; font-size: 10px; white-space: nowrap;
            opacity: 0; transition: all 0.2s; margin-bottom: 5px;
        }

        /* Hide the browser's default password reveal button (Edge/IE) */
        input::-ms-reveal,
        input::-ms-clear {
        display: none;
        }

        /* Disable scrollbars completely */
        ::-webkit-scrollbar { display: none; }
        * { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>

<body class="h-screen w-full flex items-center justify-center overflow-hidden relative">

    <div class="absolute inset-0 animate-gradient z-0"></div>

    <div class="absolute inset-0 pointer-events-none z-0 overflow-hidden">
        <div class="absolute top-10 left-[5%] w-32 h-32 bg-teal-900/20 rounded-3xl shape-shadow float-slow backdrop-blur-sm border border-white/10"></div>
        <div class="absolute bottom-20 right-[5%] w-48 h-48 bg-emerald-900/15 rounded-full shape-shadow float-medium delay-1000 backdrop-blur-sm"></div>
        <div class="absolute top-[40%] left-[10%] w-16 h-16 bg-teal-800/20 rounded-xl shape-shadow float-fast delay-2000 rotate-12"></div>
        <div class="absolute top-[15%] right-[20%] text-teal-900/20 float-slow delay-2000">
            <i class="fas fa-cash-register text-9xl icon-shadow"></i>
        </div>
        <div class="absolute bottom-[15%] left-[20%] text-emerald-900/20 float-medium delay-1000">
            <i class="fas fa-chart-pie text-8xl icon-shadow"></i>
        </div>
    </div>

    <?php if(isset($_SESSION['message'])): ?>
    <script>
        Swal.fire({
            icon: 'error',
            title: 'Access Denied',
            text: '<?= $_SESSION['message']; ?>',
            toast: true, position: 'top-end', showConfirmButton: false, timer: 3000, timerProgressBar: true, 
            background: '#1e293b', color: '#fff'
        });
    </script>
    <?php unset($_SESSION['message']); endif; ?>
    
    <?php if(!empty($captcha_error)): ?>
    <script>
         document.addEventListener('DOMContentLoaded', function() {
            // Display Swal, then reopen modal and refresh captcha
            Swal.fire({
                icon: 'error',
                title: 'Captcha Error',
                text: '<?= $captcha_error; ?>',
                toast: true, position: 'top-end', showConfirmButton: false, timer: 3000, timerProgressBar: true, 
                background: '#dc2626', color: '#fff' // Red background for distinction
            });
            // Reopen the modal immediately after showing the error
            openModal(); 
        });
    </script>
    <?php endif; ?>


    <div class="relative z-10 w-full max-w-5xl md:h-[600px] flex animate-slide-up px-4 md:px-0">

        <div class="w-full flex flex-col md:flex-row bg-white shadow-[0_35px_60px_-15px_rgba(0,0,0,0.3)] rounded-3xl overflow-hidden border border-white/40">

            <div class="hidden md:flex flex-col justify-between w-full md:w-1/2 bg-gradient-to-br from-teal-900 via-teal-800 to-emerald-900 text-white p-12 relative overflow-hidden">
                <div class="absolute top-0 right-0 w-64 h-64 bg-white/5 rounded-full blur-3xl -mr-16 -mt-16 pointer-events-none"></div>
                <div class="absolute bottom-0 left-0 w-48 h-48 bg-emerald-400/10 rounded-full blur-2xl -ml-10 -mb-10 pointer-events-none"></div>

                <div class="relative z-10 space-y-8 mt-4">
                    <h2 id="genText" class="text-3xl lg:text-4xl font-extrabold tracking-tight leading-tight text-white min-h-[100px]">
                        </h2>
                    
                    <p class="text-lg text-teal-50/90 leading-relaxed font-light">
                        <span class="font-semibold text-white">Accelerate your growth</span> with the 
                        <span class="italic text-white">Velocity POS Suite</span> that delivers 
                        <span class="text-teal-200 font-medium">real-time sales data</span>, 
                        <span class="text-teal-200 font-medium">smarter inventory control</span>, and 
                        <span class="text-teal-200 font-medium">effortless operation</span>.
                    </p>

                    <div class="flex items-center space-x-5 pt-12">
                        <a href="/faq" class="tooltip-group w-10 h-10 flex items-center justify-center rounded-full bg-white/10 hover:bg-white/20 transition-all border border-white/10">
                            <i class="fas fa-question text-teal-100"></i><span class="tooltip">FAQ</span>
                        </a>
                        <a href="/support" class="tooltip-group w-10 h-10 flex items-center justify-center rounded-full bg-white/10 hover:bg-white/20 transition-all border border-white/10">
                            <i class="fas fa-headset text-teal-100"></i><span class="tooltip">Support</span>
                        </a>
                        <a href="https://wa.me/123456789" class="tooltip-group w-10 h-10 flex items-center justify-center rounded-full bg-white/10 hover:bg-white/20 transition-all border border-white/10">
                            <i class="fab fa-whatsapp text-teal-100"></i><span class="tooltip">WhatsApp</span>
                        </a>
                        <a href="mailto:support@pos.com" class="tooltip-group w-10 h-10 flex items-center justify-center rounded-full bg-white/10 hover:bg-white/20 transition-all border border-white/10">
                            <i class="fas fa-envelope text-teal-100"></i><span class="tooltip">Email</span>
                        </a>
                    </div>
                </div>

                <div class="relative z-10">
                    <hr class="border-t border-white/20 mb-6" />
                    <div class="flex items-center gap-4 mb-6">
                        <div class="flex -space-x-3">
                            <img src="https://i.pravatar.cc/100?img=33" class="w-10 h-10 rounded-full border-2 border-teal-800" alt="User">
                            <img src="https://i.pravatar.cc/100?img=47" class="w-10 h-10 rounded-full border-2 border-teal-800" alt="User">
                            <img src="https://i.pravatar.cc/100?img=12" class="w-10 h-10 rounded-full border-2 border-teal-800" alt="User">
                        </div>
                        <div class="text-sm">
                            <p class="font-medium text-white">Join 12,000+ businesses</p>
                            <p class="text-teal-200 text-xs">achieving Peak Performance.</p>
                        </div>
                    </div>
                    
                    <div class="flex gap-3">
                        <a href="#" class="px-5 py-2.5 rounded-lg bg-white text-teal-900 text-sm font-bold shadow hover:bg-teal-50 transition-colors">Get Started</a>
                        <a href="#" class="px-5 py-2.5 rounded-lg bg-teal-900/50 text-white text-sm font-semibold border border-white/20 hover:bg-teal-900/70 transition-colors">View Pricing</a>
                    </div>
                </div>
            </div>

            <div class="w-full md:w-1/2 bg-white relative flex flex-col h-full">
                
                <div class="absolute top-6 right-8 text-right z-20 hidden md:block">
                    <div id="liveTime" class="text-xl font-bold text-slate-800 font-mono tracking-tight">--:--:--</div>
                    <div id="liveDate" class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-1">---, --- --</div>
                </div>

                <div class="flex-1 flex flex-col justify-center px-6 md:px-16 py-6 md:py-0">
                    
                    <div class="md:mt-12 mb-4 md:mb-6 text-center">
                         <div class="w-16 h-16 rounded-full border-2 border-teal-600 flex items-center justify-center mx-auto mb-3 md:mb-5 text-teal-700 bg-white shadow-sm p-3">
                             <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0V12a2.25 2.25 0 01-2.25 2.25H5.25" />
                            </svg>
                        </div>
                        <h2 class="text-xl md:text-2xl font-bold text-slate-800">Welcome Back</h2>
                        <p class="text-slate-500 text-xs md:text-sm mt-1">Please identify yourself to proceed.</p>
                    </div>

                    <form action="/pos/config/auth_user.php" method="POST" id="loginForm" class="space-y-3 md:space-y-5 w-full">
                        <div>
                            <label for="email" class="<?= $label_style; ?>">Email Address</label>
                            
                            <input type="email" name="email" id="email" value="<?= htmlspecialchars($old_email); ?>" class="<?= $input_style; ?>" placeholder="name@store.com" required autofocus>
                            
                            <p id="emailError" class="text-xs mt-1 text-red-500 font-medium <?= $error_msg_display; ?>">Invalid email or password</p>
                        </div>

                        <div>
                            <label for="password" class="<?= $label_style; ?>">Password</label>
                            <div class="relative">
                                <input type="password" name="password" id="password" value="<?= htmlspecialchars($old_password); ?>" class="<?= $input_style; ?> pr-10" placeholder="••••••••" required>
                                <button type="button" onclick="togglePassword()" class="absolute right-3 top-2.5 md:top-3 text-gray-400 hover:text-teal-600 transition">
                                    <i id="eyeIcon" class="fas fa-eye text-sm"></i>
                                </button>
                            </div>
                            
                            <p id="passwordError" class="text-xs mt-1 text-red-500 font-medium <?= $error_msg_display; ?>">Invalid email or password</p>
                        </div>

                        <input type="hidden" name="captcha_code" id="hiddenCaptchaInput">
                        <input type="hidden" name="action_type" value="verify_login">

                        <div class="flex items-center justify-between mt-1">
                            <label class="flex items-center gap-2 cursor-pointer select-none">
                                <input type="checkbox" name="remember" class="w-4 h-4 rounded accent-teal-600 focus:ring-teal-500 border-gray-300">
                                <span class="text-xs md:text-sm text-slate-600 font-medium">Remember me</span>
                            </label>
                            <a href="#" class="text-xs md:text-sm font-bold text-teal-600 hover:text-teal-800">Forgot password?</a>
                        </div>

                        <button type="button" id="openModalBtn" disabled 
                            class="w-full bg-gradient-to-br from-teal-900 via-teal-800 to-emerald-900 hover:to-emerald-800 disabled:from-slate-300 disabled:to-slate-300 disabled:text-slate-500 disabled:cursor-not-allowed text-white font-bold py-3 md:py-3.5 rounded-xl shadow-lg hover:shadow-xl transition-all transform active:scale-[0.98] flex items-center justify-center gap-2 mt-2 md:mt-4">
                            <span class="text-sm md:text-base">Verify & Login</span> <i class="fas fa-arrow-right"></i>
                        </button>

                        <button type="submit" name="login_btn" id="realSubmitBtn" class="hidden"></button>
                    </form>

                    <div class="mt-4 md:mt-auto pt-4 md:pt-8 md:pb-5 text-center">
                        <p class="text-[10px] md:text-xs text-slate-400 font-medium">&copy; 2025 POS SaaS. v1.0.0</p>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <div id="captchaModal" class="fixed inset-0 z-[99] hidden flex items-center justify-center">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity opacity-0" id="modalBackdrop"></div>
        <div class="relative w-full max-w-sm glass-modal rounded-2xl shadow-2xl p-8 transform scale-95 opacity-0 transition-all duration-300 border border-white/40" id="modalContent">
            <button onclick="closeModal()" class="absolute top-4 right-4 text-slate-400 hover:text-red-500 transition p-2"><i class="fas fa-times text-lg"></i></button>
            <div class="text-center">
                <div class="w-14 h-14 bg-teal-50 text-teal-600 rounded-xl flex items-center justify-center mx-auto mb-4 text-xl shadow-sm"><i class="fas fa-shield-alt"></i></div>
                <h3 class="text-lg font-bold text-slate-800">Security Check</h3>
                <p class="text-sm text-slate-500 mt-2 mb-6">Please match the captcha code below.</p>
                
                <div class="mb-6 flex items-center justify-center gap-4">
                    <img src="/pos/config/captcha.php" id="captchaImage" alt="Code" class="h-16 rounded mix-blend-multiply">
                    <button onclick="refreshCaptcha()" class="text-slate-400 hover:text-teal-600 transition p-2 rotate-0 hover:rotate-180 duration-500 text-lg" type="button">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>
                <input type="text" inputmode="numeric" id="modalCaptchaInput" 
                       class="w-full text-center text-3xl font-mono font-bold tracking-[0.5em] text-slate-800 border-b-2 border-slate-200 focus:border-teal-500 outline-none py-2 bg-transparent placeholder-slate-200 transition-colors" 
                       placeholder="•••••" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 5);">
                
                <button type="button" id="confirmBtn" onclick="submitFormWithCaptcha()" 
                    class="w-full mt-8 bg-gradient-to-br from-teal-900 via-teal-800 to-emerald-900 hover:to-emerald-800 text-white font-bold py-3 rounded-lg shadow-md flex justify-center items-center gap-2 hover:shadow-lg transition-all">
                    <span>Access Dashboard</span> <i class="fas fa-check"></i>
                </button>
            </div>
        </div>
    </div>

    <script>
        const emailInput = document.getElementById('email');
        const passwordInput = document.getElementById('password');
        const openModalBtn = document.getElementById('openModalBtn');
        const emailError = document.getElementById('emailError');
        const passwordError = document.getElementById('passwordError');
        const hiddenCaptchaInput = document.getElementById('hiddenCaptchaInput');
        const realSubmitBtn = document.getElementById('realSubmitBtn');
        const modal = document.getElementById('captchaModal');
        const backdrop = document.getElementById('modalBackdrop');
        const content = document.getElementById('modalContent');
        const confirmBtn = document.getElementById('confirmBtn');


        function checkInputs() {
            if(openModalBtn.innerHTML.includes('fa-spinner') || openModalBtn.innerHTML.includes('fa-circle-notch')) return;

            const emailVal = emailInput.value.trim();
            const passVal = passwordInput.value.trim();
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            const isEmailValid = emailRegex.test(emailVal);
            
            if(isEmailValid && passVal) { openModalBtn.disabled = false; } else { openModalBtn.disabled = true; }
        }
        
        emailInput.addEventListener('input', checkInputs);
        passwordInput.addEventListener('input', checkInputs);

        function togglePassword() {
            const pwd = document.getElementById('password');
            const icon = document.getElementById('eyeIcon');
            if(pwd.type === 'password') { pwd.type = 'text'; icon.classList.replace('fa-eye', 'fa-eye-slash'); } else { pwd.type = 'password'; icon.classList.replace('fa-eye-slash', 'fa-eye'); }
        }

        function openModal() {
             modal.classList.remove('hidden');
             refreshCaptcha();
             setTimeout(() => { 
                 backdrop.classList.remove('opacity-0'); 
                 content.classList.remove('scale-95', 'opacity-0'); 
                 content.classList.add('scale-100', 'opacity-100'); 
                 document.getElementById('modalCaptchaInput').focus(); 
             }, 10);
         }


        openModalBtn.addEventListener('click', () => {
            const originalContent = openModalBtn.innerHTML;
            openModalBtn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Please wait...';
            openModalBtn.classList.add('cursor-not-allowed', 'opacity-75');
            openModalBtn.disabled = true;

            setTimeout(() => {
                // Remove error state classes before opening modal
                emailInput.classList.remove('border-red-500', 'bg-white', 'focus:ring-red-500');
                passwordInput.classList.remove('border-red-500', 'bg-white', 'focus:ring-red-500');
                document.getElementById('emailError').classList.add('hidden');
                document.getElementById('passwordError').classList.add('hidden');

                openModal();
                
                openModalBtn.innerHTML = originalContent;
                openModalBtn.classList.remove('cursor-not-allowed', 'opacity-75');
                checkInputs(); 
            }, 500);
        });

        function closeModal() {
            backdrop.classList.add('opacity-0');
            content.classList.remove('scale-100', 'opacity-100');
            content.classList.add('scale-95', 'opacity-0');
            setTimeout(() => { modal.classList.add('hidden'); }, 300);
        }

        function refreshCaptcha() {
            document.getElementById('captchaImage').src = '/pos/config/captcha.php?' + new Date().getTime();
            document.getElementById('modalCaptchaInput').value = '';
        }
        
        function submitFormWithCaptcha() {
            const code = document.getElementById('modalCaptchaInput').value.trim();
            const originalContent = confirmBtn.innerHTML;
            
            if(!code) { 
                content.classList.add('animate-bounce'); setTimeout(()=> content.classList.remove('animate-bounce'), 500); return; 
            }
            
            confirmBtn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Checking...';
            confirmBtn.disabled = true;
            confirmBtn.classList.add('cursor-not-allowed', 'opacity-80');

            fetch('/pos/config/auth_user.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `captcha_code=${code}&action_type=captcha_verify` 
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Server connectivity issue (404/500)');
                }
                return response.json();
            })
            .then(data => {
                if (data.status === 'success') {
                    hiddenCaptchaInput.value = code;
                    realSubmitBtn.click(); 
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Invalid Captcha',
                        text: 'Please try the new code.',
                        toast: true, position: 'top-end', showConfirmButton: false, timer: 3000, timerProgressBar: true,
                        background: '#1e293b', color: '#fff'
                    });
                    
                    refreshCaptcha(); 
                    
                    confirmBtn.innerHTML = originalContent;
                    confirmBtn.disabled = false;
                    confirmBtn.classList.remove('cursor-not-allowed', 'opacity-80');
                }
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'System Error',
                    text: 'Could not submit captcha: ' + error.message,
                    toast: true, position: 'top-end', showConfirmButton: false, timer: 4000
                });
                refreshCaptcha(); 
                confirmBtn.innerHTML = originalContent;
                confirmBtn.disabled = false;
                confirmBtn.classList.remove('cursor-not-allowed', 'opacity-80');
            });
        }

        /* === NEW: REAL-TIME DATE & CLOCK LOGIC === */
        function updateClock() {
            const now = new Date();
            // Time: 09:49:48 (24h format)
            const timeString = now.toLocaleTimeString('en-GB', { hour12: false });
            // Date: WED, DEC 10
            const options = { weekday: 'short', month: 'short', day: 'numeric' };
            const dateString = now.toLocaleDateString('en-US', options).toUpperCase();
            
            const timeEl = document.getElementById('liveTime');
            const dateEl = document.getElementById('liveDate');
            
            if(timeEl) timeEl.innerText = timeString;
            if(dateEl) dateEl.innerText = dateString;
        }
        setInterval(updateClock, 1000);
        updateClock(); // Run immediately on load

        /* === NEW: BUTTERY SMOOTH GENERATIVE TEXT LOGIC === */
        const textPhrase = "Sync your business  - anytime & anywhere";
        const textContainer = document.getElementById('genText');
        textContainer.innerHTML = ''; // Clear initial content

        // Generate spans with staggered delays
        let delay = 0;
        let htmlContent = '';
        
        // Split logical parts for layout (Break at the hyphen)
        const parts = textPhrase.split('-'); 
        // Part 1: "keep your business synced "
        // Part 2: " anytime & anywhere"

        // Helper to wrap chars
        function wrapChars(str, isTeal = false) {
            let result = '';
            str.split('').forEach(char => {
                delay += 0.05; // 50ms delay per char for smooth generation
                
                let classes = 'gen-char';
                if(isTeal) classes += ' text-teal-300';
                
                if (char === ' ') {
                    result += `<span style="display:inline-block; width:0.3em;"></span>`; // Handle space
                } else {
                    result += `<span class="${classes}" style="animation-delay: ${delay}s">${char}</span>`;
                }
            });
            return result;
        }

        htmlContent += wrapChars(parts[0].trim()); // First line (White)
        htmlContent += '<br/>'; // Visual break
        htmlContent += wrapChars(parts[1].trim(), true); // Second line (Teal)

        textContainer.innerHTML = htmlContent;

        emailInput.focus();
        checkInputs();
    </script>
</body>
</html>