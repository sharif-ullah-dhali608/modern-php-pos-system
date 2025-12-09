<?php
session_start();
if(isset($_SESSION['auth'])){
    header('Location: index.php');
    exit();
}

// 1. SESSION DATA
$old_email = isset($_SESSION['input_email']) ? $_SESSION['input_email'] : '';
$error_field = isset($_SESSION['error_field']) ? $_SESSION['error_field'] : '';

// 2. INPUT CLASSES
$base_classes = "w-full rounded-lg px-4 py-3 outline-none transition-all duration-200 border";

$email_class = ($error_field == 'email' || $error_field == 'both') 
    ? $base_classes . ' border-red-500 text-red-900 placeholder-red-300 focus:ring-2 focus:ring-red-500 bg-red-50' 
    : $base_classes . ' border-gray-200 bg-gray-50 text-gray-900 focus:ring-2 focus:ring-teal-500 focus:border-teal-500 focus:bg-white';

$pass_class = ($error_field == 'password' || $error_field == 'both') 
    ? $base_classes . ' border-red-500 text-red-900 placeholder-red-300 focus:ring-2 focus:ring-red-500 bg-red-50' 
    : $base_classes . ' border-gray-200 bg-gray-50 text-gray-900 focus:ring-2 focus:ring-teal-500 focus:border-teal-500 focus:bg-white';

unset($_SESSION['input_email']);
unset($_SESSION['error_field']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>POS Terminal Login</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <style>
        body { font-family: 'Public Sans', sans-serif; }
        
        /* === 1. MOVING GRADIENT (Light Green <-> Deep Teal) === */
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

        /* === 2. FLOATING ANIMATIONS === */
        .float-slow { animation: float 8s ease-in-out infinite; }
        .float-medium { animation: float 6s ease-in-out infinite; }
        .float-fast { animation: float 4s ease-in-out infinite; }

        @keyframes float {
            0% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-30px) rotate(5deg); }
            100% { transform: translateY(0px) rotate(0deg); }
        }

        /* Stronger Shadow for Visibility */
        .shape-shadow {
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2), 0 10px 10px -5px rgba(0, 0, 0, 0.1);
        }
        .icon-shadow {
            filter: drop-shadow(0 10px 10px rgba(0, 0, 0, 0.25));
        }

        /* Delays */
        .delay-1000 { animation-delay: 1s; }
        .delay-2000 { animation-delay: 2s; }
        .delay-3000 { animation-delay: 3s; }

        /* Animation for Card */
        @keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .animate-slide-up { animation: slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards; }

        /* Glass Modal */
        .glass-modal {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
        }

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

        <div class="absolute top-[10%] left-[40%] text-teal-800/15 float-fast delay-3000">
            <i class="fas fa-shopping-bag text-6xl icon-shadow"></i>
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

    <div class="relative z-10 w-full max-w-5xl h-[90vh] md:h-[600px] flex animate-slide-up px-4 md:px-0">

        <div class="w-full flex flex-col md:flex-row bg-white shadow-[0_35px_60px_-15px_rgba(0,0,0,0.3)] rounded-3xl overflow-hidden border border-white/40">

            <div class="hidden md:flex flex-col justify-between w-full md:w-1/2 bg-gradient-to-br from-teal-900 via-teal-800 to-emerald-900 text-white p-12 relative overflow-hidden">
                
                <div class="absolute top-0 right-0 w-64 h-64 bg-white/5 rounded-full blur-3xl -mr-16 -mt-16 pointer-events-none"></div>
                <div class="absolute bottom-0 left-0 w-48 h-48 bg-emerald-400/10 rounded-full blur-2xl -ml-10 -mb-10 pointer-events-none"></div>

                <div class="relative z-10 space-y-8 mt-4">
                    <h4 class="text-3xl lg:text-4xl font-extrabold tracking-tight leading-tight text-white">
                        Velocity. <br/> Clarity. <span class="text-teal-300">Success.</span>
                    </h4>
                    
                    <p class="text-lg text-teal-50/90 leading-relaxed font-light">
                        <span class="font-semibold text-white">Accelerate your growth</span> with the 
                        <span class="italic text-white">Velocity POS Suite</span> that delivers 
                        <span class="text-teal-200 font-medium">real-time sales data</span>, 
                        <span class="text-teal-200 font-medium">smarter inventory control</span>, and 
                        <span class="text-teal-200 font-medium">effortless operation</span>.
                    </p>

                    <div class="flex items-center space-x-5 pt-16">
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
                
                <div class="absolute top-6 right-8 text-right z-20">
                    <div id="clock" class="text-2xl font-bold text-slate-800 font-mono tracking-tight">00:00:00</div>
                    <div id="date" class="text-xs text-slate-500 font-semibold uppercase tracking-wider mt-0.5">Loading...</div>
                </div>

                <div class="flex-1 flex flex-col justify-center px-8 md:px-16 overflow-y-auto">
                    
                    <div class="text-center mb-8 mt-12">
                        
                        <div class="inline-flex items-center justify-center mb-1">
                            <img src="assets/images/logo.png" alt="POS Logo" class="h-20 w-auto object-contain drop-shadow-md hover:scale-105 transition-transform duration-300">
                        </div>
                        
                        <h2 class="text-2xl font-bold text-slate-800">Welcome Back</h2>
                        <p class="text-slate-500 text-sm mt-2">Please identify yourself to proceed.</p>
                    </div>

                    <form action="config/auth_code.php" method="POST" id="loginForm" class="space-y-5">
                        <div>
                            <label for="email" class="block text-sm font-semibold mb-1.5 text-slate-700">Email Address</label>
                            <input type="email" name="email" id="email" value="<?= htmlspecialchars($old_email); ?>" class="<?= $email_class; ?>" placeholder="name@store.com" required>
                            <p id="emailError" class="text-sm mt-1 text-red-500 hidden font-medium">
                                <i class="fas fa-exclamation-circle mr-1"></i> Invalid email format
                            </p>
                        </div>

                        <div>
                            <label for="password" class="block text-sm font-semibold mb-1.5 text-slate-700">Password</label>
                            <div class="relative">
                                <input type="password" name="password" id="password" class="<?= $pass_class; ?> pr-10" placeholder="••••••••" required>
                                <button type="button" onclick="togglePassword()" class="absolute right-3 top-3.5 text-gray-400 hover:text-teal-600 transition">
                                    <i id="eyeIcon" class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <input type="hidden" name="captcha_code" id="hiddenCaptchaInput">

                        <div class="flex items-center justify-between mt-1">
                            <label class="flex items-center gap-2 cursor-pointer select-none">
                                <input type="checkbox" name="remember" class="w-4 h-4 rounded text-teal-600 focus:ring-teal-500 border-gray-300">
                                <span class="text-sm text-slate-600 font-medium">Remember me</span>
                            </label>
                            <a href="#" class="text-sm font-semibold text-teal-600 hover:text-teal-800">Forgot password?</a>
                        </div>

                        <button type="button" id="openModalBtn" disabled class="w-full bg-slate-900 hover:bg-black disabled:bg-slate-200 disabled:text-slate-400 disabled:cursor-not-allowed text-white font-bold py-3.5 rounded-xl shadow-lg hover:shadow-xl transition-all transform active:scale-[0.98] flex items-center justify-center gap-2 mt-4">
                            <span class="text-base">Verify & Login</span> <i class="fas fa-arrow-right"></i>
                        </button>

                        <button type="submit" name="login_btn" id="realSubmitBtn" class="hidden"></button>
                    </form>
                </div>

                <div class="py-5 text-center border-t border-slate-100 bg-slate-50/50">
                    <p class="text-xs text-slate-400 font-medium">&copy; 2025 POS SaaS. v1.0.0</p>
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
                <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 mb-6 flex items-center justify-between">
                    <img src="config/captcha.php" id="captchaImage" alt="Code" class="h-10 rounded mix-blend-multiply">
                    <button onclick="refreshCaptcha()" class="text-slate-400 hover:text-teal-600 transition p-2 rotate-0 hover:rotate-180 duration-500"><i class="fas fa-sync-alt"></i></button>
                </div>
                <input type="number" id="modalCaptchaInput" class="w-full text-center text-3xl font-mono font-bold tracking-[0.5em] text-slate-800 border-b-2 border-slate-200 focus:border-teal-500 outline-none py-2 bg-transparent placeholder-slate-200 transition-colors" placeholder="•••••">
                <button type="button" onclick="verifyAndLogin()" class="w-full mt-8 bg-slate-900 text-white font-bold py-3 rounded-lg hover:bg-black transition-all shadow-md flex justify-center items-center gap-2"><span>Access Dashboard</span> <i class="fas fa-check"></i></button>
            </div>
        </div>
    </div>

    <script>
        function updateClock() {
            const now = new Date();
            document.getElementById('clock').innerText = now.toLocaleTimeString('en-US', { hour12: false });
            document.getElementById('date').innerText = now.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' });
        }
        setInterval(updateClock, 1000); updateClock();

        const emailInput = document.getElementById('email');
        const passwordInput = document.getElementById('password');
        const openModalBtn = document.getElementById('openModalBtn');
        const emailError = document.getElementById('emailError');

        function checkInputs() {
            const emailVal = emailInput.value.trim();
            const passVal = passwordInput.value.trim();
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            const isEmailValid = emailRegex.test(emailVal);
            if(emailVal && !isEmailValid) { emailError.classList.remove('hidden'); } else { emailError.classList.add('hidden'); }
            if(isEmailValid && passVal) { openModalBtn.disabled = false; } else { openModalBtn.disabled = true; }
        }
        checkInputs();
        emailInput.addEventListener('input', checkInputs);
        passwordInput.addEventListener('input', checkInputs);

        function togglePassword() {
            const pwd = document.getElementById('password');
            const icon = document.getElementById('eyeIcon');
            if(pwd.type === 'password') { pwd.type = 'text'; icon.classList.replace('fa-eye', 'fa-eye-slash'); } else { pwd.type = 'password'; icon.classList.replace('fa-eye-slash', 'fa-eye'); }
        }

        const modal = document.getElementById('captchaModal');
        const backdrop = document.getElementById('modalBackdrop');
        const content = document.getElementById('modalContent');

        openModalBtn.addEventListener('click', () => {
            modal.classList.remove('hidden');
            refreshCaptcha();
            setTimeout(() => { backdrop.classList.remove('opacity-0'); content.classList.remove('scale-95', 'opacity-0'); content.classList.add('scale-100', 'opacity-100'); document.getElementById('modalCaptchaInput').focus(); }, 10);
        });

        function closeModal() {
            backdrop.classList.add('opacity-0');
            content.classList.remove('scale-100', 'opacity-100');
            content.classList.add('scale-95', 'opacity-0');
            setTimeout(() => { modal.classList.add('hidden'); }, 300);
        }

        function refreshCaptcha() {
            document.getElementById('captchaImage').src = 'config/captcha.php?' + new Date().getTime();
            document.getElementById('modalCaptchaInput').value = '';
        }

        function verifyAndLogin() {
            const code = document.getElementById('modalCaptchaInput').value.trim();
            if(!code) { content.classList.add('animate-bounce'); setTimeout(()=> content.classList.remove('animate-bounce'), 500); return; }
            document.getElementById('hiddenCaptchaInput').value = code;
            const btn = document.querySelector('#captchaModal button:last-child');
            btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Checking...';
            btn.disabled = true;
            document.getElementById('realSubmitBtn').click();
        }
    </script>
</body>
</html>