<?php
session_start();
if(isset($_SESSION['auth'])){
    header('Location: index.php');
    exit();
}

// 1. RETRIEVE PREVIOUS INPUT AND ERROR STATE
$old_email = isset($_SESSION['input_email']) ? $_SESSION['input_email'] : '';
$error_field = isset($_SESSION['error_field']) ? $_SESSION['error_field'] : '';

// 2. DEFINE ERROR CLASSES
$base_classes = "w-full rounded px-3 py-2 outline-none transition-all";

$email_class = ($error_field == 'email' || $error_field == 'both') 
    ? $base_classes . ' border border-red-500 text-red-900 placeholder-red-300 focus:ring-2 focus:ring-red-500 bg-red-50' 
    : $base_classes . ' border border-gray-300 bg-gray-50/80 text-gray-900 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500';

$pass_class = ($error_field == 'password' || $error_field == 'both') 
    ? $base_classes . ' border border-red-500 text-red-900 placeholder-red-300 focus:ring-2 focus:ring-red-500 bg-red-50' 
    : $base_classes . ' border border-gray-300 bg-gray-50/80 text-gray-900 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500';

// 3. CLEAN UP SESSION
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
        
        /* 1. REALISTIC BACKGROUND SETUP */
        .bg-realistic {
            background-image: url('https://images.unsplash.com/photo-1556742049-0cfed4f7a07d?q=80&w=2070&auto=format&fit=crop'); 
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }

        /* 2. NOISE TEXTURE */
        .bg-noise {
            position: absolute;
            inset: 0;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noiseFilter'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.65' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noiseFilter)' opacity='0.05'/%3E%3C/svg%3E");
            pointer-events: none;
            z-index: 1;
        }

        /* Smooth Animations */
        @keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .animate-slide-up { animation: slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
        
        /* Glass Modal */
        .glass-modal {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
        }
    </style>
</head>

<body class="h-screen w-full flex items-center justify-center bg-realistic overflow-hidden relative">

    <div class="absolute inset-0 bg-slate-900/80 backdrop-blur-sm z-0"></div>
    <div class="bg-noise"></div>

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

    <div class="relative z-10 w-full max-w-5xl h-[90vh] md:h-[600px] bg-white/95 backdrop-blur-xl rounded-3xl shadow-2xl overflow-hidden flex animate-slide-up border border-white/20">


    <div class="absolute inset-0 -z-10 overflow-hidden pointer-events-none opacity-5">
<svg class="w-full h-full" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="none">
<defs>
<pattern id="dot-pattern" width="10" height="10" patternUnits="userSpaceOnUse">
<circle cx="1" cy="1" r="1" fill="#0d9488" />
</pattern>
</defs>
<rect width="100%" height="100%" fill="url(#dot-pattern)" />
</svg>
</div>

<div class="w-full max-w-6xl flex flex-col md:flex-row bg-white shadow-2xl rounded-2xl overflow-hidden border border-gray-100">

<div class="hidden md:flex flex-col justify-between w-full md:w-1/2 bg-gradient-to-br from-teal-700 via-teal-800 to-cyan-900 text-white p-14 space-y-10 relative overflow-hidden">
<div class="absolute -top-10 -right-10 w-40 h-40 bg-white/10 rounded-full animate-pulse-slow"></div>
<div class="absolute bottom-10 -left-10 w-24 h-24 bg-white/10 rounded-full animate-bounce-slow"></div>

<div class="relative z-10 space-y-6">
<h2 class="text-4xl sm:text-5xl font-extrabold tracking-tight leading-tight text-white drop-shadow-lg">
<span class="bg-clip-text">Velocity. Clarity. Success.</span>
</h2>
<p class="text-lg text-white/90 max-w-xl leading-relaxed font-medium">
<span class="inline-flex items-center gap-2">
<i class="fas fa-chart-line text-orange-300"></i>
<span class="text-white font-semibold">Accelerate</span> your growth
</span>
with the *Velocity POS Suite* that delivers
<span class="text-orange-200">real-time sales data</span>,
<span class="text-orange-200">smarter inventory control</span>, and
<span class="text-orange-200">effortless operation</span>.
</p>
</div>
<nav class="flex space-x-6 text-white/90 font-medium text-sm">
<a href="/faq" class="flex items-center gap-2 hover:text-orange-300 transition">
<i class="fas fa-question-circle text-orange-400 text-xl"></i> FAQ
</a>
<a href="/support" class="flex items-center gap-2 hover:text-orange-300 transition">
<i class="fas fa-ticket-alt text-orange-400 text-xl"></i> Support
</a>
<a href="mailto:support@possaas.com" class="flex items-center gap-2 hover:text-orange-300 transition">
<i class="fas fa-envelope text-orange-400 text-xl"></i> Email
</a>
</nav>
<div class="relative z-10 max-w-lg space-y-6">
<hr class="border-t border-white/30" />
<div class="flex items-center space-x-3">
<div class="flex -space-x-3">
<img src="https://i.pravatar.cc/40?img=21" class="rounded-full w-10 h-10 border-2 border-white shadow-lg" alt="User Avatar 1"/>
<img src="https://i.pravatar.cc/40?img=22" class="rounded-full w-10 h-10 border-2 border-white shadow-lg" alt="User Avatar 2"/>
<img src="https://i.pravatar.cc/40?img=23" class="rounded-full w-10 h-10 border-2 border-white shadow-lg" alt="User Avatar 3"/>
</div>
<p class="text-sm sm:text-base text-white font-sm ml-8">
Join <span class="font-bold text-orange-200">12,000+</span> businesses achieving <span class="font-semibold text-white">Peak Performance</span>.
</p>
</div>
</div>
</div>


        <div class="w-full md:w-7/12 bg-white/60 relative flex flex-col h-full">
            
            <div class="absolute top-6 right-8 text-right z-20">
                <div id="clock" class="text-2xl font-bold text-slate-800 font-mono tracking-tight">00:00:00</div>
                <div id="date" class="text-xs text-slate-500 font-semibold uppercase tracking-wider mt-0.5">Loading...</div>
            </div>

            <div class="flex-1 flex flex-col justify-center px-8 md:px-16 overflow-y-auto">
                
                <div class="text-center mb-8 mt-10">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-indigo-600 to-indigo-700 rounded-xl shadow-lg mb-6">
                        <i class="fas fa-store text-3xl text-white"></i>
                    </div>
                    
                    <h2 class="text-2xl font-bold text-slate-800">Login Yourself</h2>
                    <p class="text-slate-500 text-sm mt-2">Please identify yourself to proceed.</p>
                </div>

                <form action="config/auth_code.php" method="POST" id="loginForm" class="space-y-5">
                    
                    <div>
                        <label for="email" class="block text-sm font-medium mb-1 text-slate-700">Email Address</label>
                        <input type="email" name="email" id="email" 
                            value="<?= htmlspecialchars($old_email); ?>"
                            class="<?= $email_class; ?>" 
                            placeholder="Enter your email" required>
                        <p id="emailError" class="text-sm mt-1 text-red-500 hidden">
                            <i class="fas fa-exclamation-circle"></i> Invalid email format
                        </p>
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium mb-1 text-slate-700">Password</label>
                        <div class="relative">
                            <input type="password" name="password" id="password" 
                                class="<?= $pass_class; ?> pr-10" 
                                placeholder="Enter password" required>
                            
                            <button type="button" onclick="togglePassword()" class="absolute right-3 top-2.5 text-gray-400 hover:text-indigo-600 transition">
                                <i id="eyeIcon" class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <input type="hidden" name="captcha_code" id="hiddenCaptchaInput">

                    <div class="flex items-center justify-between mt-2">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="remember" class="w-4 h-4 rounded text-indigo-600 focus:ring-indigo-500 border-gray-300">
                            <span class="text-sm text-slate-600">Remember me</span>
                        </label>
                        <a href="#" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">Forgot password?</a>
                    </div>

                    <button type="button" id="openModalBtn" disabled 
                        class="w-full bg-indigo-600 hover:bg-indigo-700 disabled:bg-slate-300 disabled:text-slate-500 disabled:cursor-not-allowed text-white font-bold py-3 rounded shadow hover:shadow-indigo-500/40 transition-all transform active:scale-[0.98] flex items-center justify-center gap-2 mt-4">
                        <span class="text-base">Verify Identity</span> 
                        <i class="fas fa-fingerprint"></i>
                    </button>

                    <button type="submit" name="login_btn" id="realSubmitBtn" class="hidden"></button>
                </form>
            </div>

            <div class="py-4 text-center border-t border-slate-200/60 bg-white/40 backdrop-blur-sm">
                <p class="text-xs text-slate-400 font-medium">
                    &copy; 2025 POS SaaS. v1.0.0
                </p>
            </div>

        </div>
    </div>

    <div id="captchaModal" class="fixed inset-0 z-[99] hidden flex items-center justify-center">
        <div class="absolute inset-0 bg-slate-900/80 backdrop-blur-md transition-opacity opacity-0" id="modalBackdrop"></div>
        
        <div class="relative w-full max-w-sm glass-modal rounded-3xl shadow-2xl p-8 transform scale-95 opacity-0 transition-all duration-300 border border-white/20" id="modalContent">
            
            <button onclick="closeModal()" class="absolute top-4 right-4 text-slate-400 hover:text-red-500 transition p-2">
                <i class="fas fa-times text-xl"></i>
            </button>

            <div class="text-center">
                <div class="w-16 h-16 bg-indigo-50 text-indigo-600 rounded-2xl flex items-center justify-center mx-auto mb-4 text-2xl shadow-inner">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h3 class="text-xl font-bold text-slate-800">Security Check</h3>
                <p class="text-sm text-slate-500 mt-2 mb-6">Enter the code displayed below to authenticate your session.</p>

                <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-200 mb-6 flex items-center justify-between">
                    <img src="config/captcha.php" id="captchaImage" alt="Code" class="h-10 rounded">
                    <button onclick="refreshCaptcha()" class="text-slate-400 hover:text-indigo-600 transition p-2 rotate-0 hover:rotate-180 duration-500">
                        <i class="fas fa-sync-alt text-lg"></i>
                    </button>
                </div>

                <input type="number" id="modalCaptchaInput" 
                    class="w-full text-center text-3xl font-mono font-bold tracking-[0.5em] text-slate-800 border-b-2 border-slate-200 focus:border-indigo-600 outline-none py-2 bg-transparent placeholder-slate-200 transition-colors" 
                    placeholder="•••••">

                <button type="button" onclick="verifyAndLogin()" 
                    class="w-full mt-8 bg-slate-900 text-white font-bold py-3.5 rounded-xl hover:bg-black transition-all shadow-lg flex justify-center items-center gap-2">
                    <span>Access Dashboard</span> <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </div>
    </div>

    <script>
        // 1. LIVE CLOCK (Updated IDs)
        function updateClock() {
            const now = new Date();
            document.getElementById('clock').innerText = now.toLocaleTimeString('en-US', { hour12: false });
            document.getElementById('date').innerText = now.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
        }
        setInterval(updateClock, 1000);
        updateClock();

        // 2. VALIDATION & UI LOGIC
        const emailInput = document.getElementById('email');
        const passwordInput = document.getElementById('password');
        const openModalBtn = document.getElementById('openModalBtn');
        const emailError = document.getElementById('emailError');

        function checkInputs() {
            const emailVal = emailInput.value.trim();
            const passVal = passwordInput.value.trim();
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            const isEmailValid = emailRegex.test(emailVal);
            
            // Format Validation
            if(emailVal && !isEmailValid) {
                emailError.classList.remove('hidden');
                emailInput.classList.add('border-red-500', 'focus:ring-red-500');
                emailInput.classList.remove('focus:ring-indigo-500');
            } else if (isEmailValid) {
                emailError.classList.add('hidden');
                if(!emailInput.classList.contains('bg-red-50')) {
                     emailInput.classList.remove('border-red-500', 'focus:ring-red-500');
                     emailInput.classList.add('focus:ring-indigo-500');
                }
            }

            // Button State
            if(isEmailValid && passVal) {
                openModalBtn.disabled = false;
            } else {
                openModalBtn.disabled = true;
            }
        }

        // Initialize
        checkInputs();

        emailInput.addEventListener('input', checkInputs);
        passwordInput.addEventListener('input', checkInputs);

        // 3. PASSWORD TOGGLE
        function togglePassword() {
            const pwd = document.getElementById('password');
            const icon = document.getElementById('eyeIcon');
            if(pwd.type === 'password') {
                pwd.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                pwd.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }

        // 4. MODAL LOGIC
        const modal = document.getElementById('captchaModal');
        const backdrop = document.getElementById('modalBackdrop');
        const content = document.getElementById('modalContent');

        openModalBtn.addEventListener('click', () => {
            modal.classList.remove('hidden');
            refreshCaptcha();
            setTimeout(() => {
                backdrop.classList.remove('opacity-0');
                content.classList.remove('scale-95', 'opacity-0');
                content.classList.add('scale-100', 'opacity-100');
                document.getElementById('modalCaptchaInput').focus();
            }, 10);
        });

        function closeModal() {
            backdrop.classList.add('opacity-0');
            content.classList.remove('scale-100', 'opacity-100');
            content.classList.add('scale-95', 'opacity-0');
            setTimeout(() => {
                modal.classList.add('hidden');
            }, 300);
        }

        // 5. CAPTCHA LOGIC
        function refreshCaptcha() {
            document.getElementById('captchaImage').src = 'config/captcha.php?' + new Date().getTime();
            document.getElementById('modalCaptchaInput').value = '';
        }

        function verifyAndLogin() {
            const code = document.getElementById('modalCaptchaInput').value.trim();
            if(!code) {
                content.classList.add('animate-bounce');
                setTimeout(()=> content.classList.remove('animate-bounce'), 500);
                return;
            }

            document.getElementById('hiddenCaptchaInput').value = code;
            
            const btn = document.querySelector('#captchaModal button:last-child');
            btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Processing...';
            btn.disabled = true;

            document.getElementById('realSubmitBtn').click();
        }
    </script>
</body>
</html>