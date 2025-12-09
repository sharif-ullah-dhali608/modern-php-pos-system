<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Login - POS SaaS</title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link href="assets/css/styles.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center px-4 sm:px-0 overflow-y-auto">

<!-- Animated SVG Background -->
<div class="absolute inset-0 -z-10 overflow-hidden pointer-events-none">
<svg class="w-full h-full opacity-10 animate-pulse" viewBox="0 0 1440 320" xmlns="http://www.w3.org/2000/svg">
<path fill="#4f46e5" fill-opacity="0.3" d="M0,32L48,42.7C96,53,192,75,288,74.7C384,75,480,53,576,42.7C672,32,768,32,864,53.3C960,75,1056,117,1152,133.3C1248,149,1344,139,1392,133.3L1440,128L1440,0L1392,0C1344,0,1248,0,1152,0C1056,0,960,0,864,0C768,0,672,0,576,0C480,0,384,0,288,0C192,0,96,0,48,0L0,0Z" />
</svg>
<svg class="absolute right-0 bottom-0 w-64 h-64 opacity-20 animate-spin-slow" viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
<path fill="#ec4899" d="M41.6,-61.8C52.1,-56.7,59.5,-43.1,62.4,-29.6C65.3,-16,63.7,-2.7,62.2,11.8C60.8,26.2,59.6,41.7,51.5,52.4C43.3,63,28.2,68.7,14.2,66.4C0.3,64.1,-12.6,53.8,-24.3,45.6C-36.1,37.5,-46.8,31.4,-56.4,20.8C-65.9,10.1,-74.3,-4.9,-70.1,-17.3C-66,-29.7,-49.4,-39.5,-35.3,-46.7C-21.3,-53.9,-10.6,-58.4,2.7,-62.1C15.9,-65.7,31.7,-68.8,41.6,-61.8Z" transform="translate(100 100)" />
</svg>

<!-- Additional POS-Themed Animated SVGs -->
<svg class="absolute top-1/4 left-8 w-12 h-12 opacity-20 animate-bounce pointer-events-none" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
<rect x="3" y="3" width="18" height="18" rx="2" ry="2" />
<line x1="3" y1="9" x2="21" y2="9" />
<line x1="9" y1="21" x2="9" y2="9" />
</svg>

<svg class="absolute top-12 right-1/4 w-16 h-16 opacity-15 animate-spin-slow pointer-events-none" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
<rect x="2" y="2" width="20" height="20" rx="5" ry="5" />
<line x1="16" y1="2" x2="16" y2="22" />
<line x1="8" y1="2" x2="8" y2="22" />
</svg>

<!-- Refined Font Awesome Animated POS Icons -->
<div class="absolute top-[90%] left-[18%] text-green-400 text-4xl animate-bounce-slow pointer-events-none">
<i class="fas fa-cash-register"></i>
</div>

<div class="absolute bottom-[80px] right-[10%] text-blue-400 text-5xl animate-rotate-slow pointer-events-none">
<i class="fas fa-print"></i>
</div>

<div class="absolute top-[55%] left-[10%] text-pink-300 text-4xl animate-pulse pointer-events-none">
<i class="fas fa-barcode"></i>
</div>

<div class="absolute top-[55%] right-[10%] text-pink-300 text-4xl animate-pulse pointer-events-none">
<i class="fas fa-receipt"></i>
</div>

<!-- Animated POS icons -->
<svg class="absolute top-20 left-1/3 w-10 h-10 text-white/20 animate-bounce pointer-events-none" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
<path d="M3 6a1 1 0 011-1h16a1 1 0 011 1v10a2 2 0 01-2 2h-2v2h1a1 1 0 010 2H7a1 1 0 010-2h1v-2H6a2 2 0 01-2-2V6zm2 1v9h14V7H5zm5 11v2h4v-2h-4z"/>
</svg>

<svg class="absolute top-1/3 right-20 w-12 h-12 text-white/10 animate-spin-slow pointer-events-none" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
<path d="M4 4h16v2H4V4zm0 4h16v2H4V8zm0 4h10v2H4v-2zm0 4h16v2H4v-2z"/>
</svg>

<svg class="absolute bottom-16 left-10 w-14 h-14 text-white/20 animate-pulse pointer-events-none" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
<path d="M3 4a1 1 0 011-1h3.5a1 1 0 01.95.684L9.382 6H20a1 1 0 011 1v2H3V4zm0 5h18v11a1 1 0 01-1 1H4a1 1 0 01-1-1V9zm5 3a1 1 0 100 2h8a1 1 0 100-2H8z"/>
</svg>
</div>

<div class="w-full max-w-6xl flex flex-col md:flex-row bg-white shadow-md rounded-2xl overflow-hidden">

<!-- Left: Welcome message -->
<div class="hidden md:flex flex-col justify-center items-start w-full md:w-1/2 bg-gradient-to-br from-indigo-800 via-purple-800 to-pink-700 text-white p-14 space-y-10 relative overflow-hidden">

<div class="relative z-10 space-y-6">
<h2 class="text-4xl sm:text-5xl font-extrabold tracking-tight leading-tight text-white drop-shadow-lg">
<span class="bg-clip-text">Start Selling Smarter</span>
</h2>
<p class="text-base sm:text-lg text-white/90 max-w-xl leading-relaxed font-medium">
<span class="inline-flex items-center gap-2">
<i class="fas fa-bolt text-yellow-300 animate-pulse"></i>
<span class="text-white font-semibold">Supercharge</span> your business
</span>
with an intuitive <span class="text-yellow-200">POS platform</span> that simplifies
<span class="text-yellow-200">sales</span>,
<span class="text-yellow-200">inventory</span>, and
<span class="text-yellow-200">insights</span> — all in one place.
</p>
</div>

<div class="relative z-10 max-w-lg space-y-4">
<p class="text-white/80 font-medium text-sm">Need help? Visit to:</p>
<nav class="flex space-x-8 text-white/90 font-medium text-base">
<a href="/faq" class="flex items-center gap-2 hover:text-blue-500 transition">
<i class="fas fa-question-circle text-blue-400 text-xl"></i> FAQ
</a>
<a href="/support" class="flex items-center gap-2 hover:text-yellow-500 transition">
<i class="fas fa-ticket-alt text-yellow-500 text-xl"></i> Help Center
</a>
<a href="https://wa.me/1234567890" target="_blank" class="flex items-center gap-2 hover:text-green-500 transition">
<i class="fab fa-whatsapp text-green-500 text-xl"></i> WhatsApp
</a>
<a href="mailto:support@possaas.com" class="flex items-center gap-2 hover:text-blue-500 transition">
<i class="fas fa-envelope text-blue-500 text-xl"></i> Email
</a>
</nav>
</div>

<hr class="border-t border-white/30 w-full my-4" />
<div class="relative z-10 flex items-center space-x-3 px-4">
<div class="flex -space-x-3">
<img src="https://i.pravatar.cc/40?img=21" class="rounded-full w-10 h-10 border-2 border-white shadow-lg" />
<img src="https://i.pravatar.cc/40?img=22" class="rounded-full w-10 h-10 border-2 border-white shadow-lg" />
<img src="https://i.pravatar.cc/40?img=23" class="rounded-full w-10 h-10 border-2 border-white shadow-lg" />
</div>
<div class="ml-8">
<p class="text-sm sm:text-base text-white font-sm ml-8">
Over <span class="font-bold text-yellow-200">12,000+</span> businesses are growing with <span class="font-semibold text-white">POS SaaS</span>. Join us today!
</p>
</div>
</div>

<div class="w-full flex justify-center mt-6">
<div class="flex flex-col sm:flex-row items-center sm:space-x-4 space-y-3 sm:space-y-0">
<a href="https://your-saas-domain.com/pricing" target="_blank" rel="noopener noreferrer"
class="inline-flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium text-sm sm:text-base px-6 py-2.5 rounded-md shadow transition-all w-48 text-center">
<i class="fas fa-tags text-white text-base"></i> Pricing
</a>
<a href="https://your-saas-domain.com" target="_blank" rel="noopener noreferrer"
class="inline-flex items-center justify-center gap-2 bg-gray-700 hover:bg-gray-800 text-white font-medium text-sm sm:text-base px-6 py-2.5 rounded-md shadow transition-all w-48 text-center">
<i class="fas fa-globe text-white text-base"></i> Visit Website
</a>
</div>
</div>
</div>

<!-- Right: Login form -->
<div class="w-full md:w-1/2 p-8 sm:p-10 lg:p-14 mt-8 bg-white">
<div class="flex flex-col items-center">
<img src="/assets/images/logo.svg" alt="Company Logo" class="w-40 h-auto mb-8" />
<h2 class="text-2xl font-bold mb-4">Log in to Manage Your Store</h2>
</div>
<form action="#" method="POST" class="space-y-4 pb-20">
<!-- Email -->
<div>
<label for="email" class="block text-sm font-medium mb-1">Email</label>
<input type="email" id="email" name="email" maxlength="50" class="w-full bg-gray-50 border border-gray-300 rounded px-3 py-2 focus:ring-2 focus:ring-indigo-500 outline-none peer" placeholder="Enter your email" required />
<p id="emailError" class="text-sm mt-1 min-h-[1.25rem] transition-opacity duration-300 ease-in-out text-error" style="opacity:0"></p>
</div>

<!-- Password -->
<div>
<label for="password" class="block text-sm font-medium mb-1">Password</label>
<div class="relative">
<input type="password" id="password" name="password" class="w-full bg-gray-50 border border-gray-300 rounded px-3 py-2 pr-10 focus:ring-2 focus:ring-indigo-500 outline-none peer" placeholder="Enter password" required />
<button type="button" class="absolute right-3 top-2.5 text-gray-500" onclick="togglePassword()">
<i id="toggleIcon" class="fas fa-eye"></i>
</button>
</div>
<p id="passwordError" class="text-sm mt-1 min-h-[1.25rem] transition-opacity duration-300 ease-in-out text-error" style="opacity:0"></p>
</div>

<!-- Remember me + Forgot -->
<div class="flex items-center justify-between text-sm">
<label class="flex items-center space-x-2">
<input type="checkbox" name="remember" class="form-checkbox">
<span>Remember me</span>
</label>
<button type="button" onclick="openForgotModal()" class="text-indigo-600 hover:underline">Forgot password?</button>
</div>

<!-- CAPTCHA placeholder -->
<div class="bg-gray-100 border border-gray-300 p-3 text-sm text-gray-500 rounded">
CAPTCHA placeholder (e.g. Google reCAPTCHA / Cloudflare Turnstile)
</div>

<!-- Submit -->
<button type="submit" class="w-full btn-primary font-medium text-base py-2.5 rounded-md transition-all disabled:btn-primary:disabled" disabled>
<i class="fas fa-sign-in-alt mr-2"></i> Sign in
</button>

<!-- Important Contact Link -->
<!-- <div class="text-center mt-3">-->
<!-- <a href="/important-contact" class="inline-flex items-center text-sm text-indigo-600 hover:underline">-->
<!-- <i class="fa-regular fa-circle-question mr-1"></i> Important Contact-->
<!-- </a>-->
<!-- </div>-->

<div class="flex items-center mt-10 pt-4">
<div class="flex-auto mt-px border-t"></div>
<div class="mx-2 text-gray-500 text-sm">Web App Version: 1.0.0</div>
<div class="flex-auto mt-px border-t"></div>
</div>
</form>

<!-- Forgot Password Modal -->
<div id="forgotModal" class="fixed inset-0 z-50 hidden items-center justify-center modal-overlay">
<div class="flex flex-col w-full max-w-md bg-white rounded-lg shadow-xl overflow-hidden">
<!-- Header -->
<div class="flex items-center justify-between h-16 px-6 bg-indigo-600 text-white">
<div class="text-lg font-medium">Reset Your Password</div>
<button type="button" onclick="closeForgotModal()" aria-label="Close modal" class="text-white hover:text-indigo-300 focus:outline-none">
<i class="fas fa-times"></i>
</button>
</div>

<!-- Form -->
<div class="p-6 flex flex-col">
<form id="forgotPasswordForm" class="flex flex-col space-y-4">
<label for="resetEmail" class="block text-sm font-medium">Enter your email address</label>
<input
type="email"
id="resetEmail"
name="resetEmail"
class="w-full border border-gray-300 rounded px-3 py-2 focus:ring-2 focus:ring-indigo-500 outline-none"
placeholder="you@example.com"
required
/>
<div class="flex justify-end space-x-3 mt-4">
<button type="button" onclick="closeForgotModal()" class="px-4 py-2 text-sm bg-gray-200 rounded hover:bg-gray-300">Cancel</button>
<button type="submit" class="px-4 py-2 text-sm btn-primary rounded-md transition-all disabled:btn-primary:disabled" disabled>Submit</button>
</div>
</form>
</div>
</div>
</div>
</div>
</div>

<!-- Body footer copyright -->
<footer class="w-full text-center py-4 bg-gray-100 text-gray-500 text-sm select-none fixed bottom-0 left-0">
&copy; 2025 POS SaaS Inc. All rights reserved.
</footer>

<script>
// Input validation and error feedback
const emailInput = document.getElementById("email");
emailInput.focus();
const passwordInput = document.getElementById("password");
const submitBtn = document.querySelector("button[type='submit']");
const emailError = document.getElementById("emailError");
const passwordError = document.getElementById("passwordError");

function validateInputs() {
// Remove red border and animation on input change
emailInput.classList.remove("border-red-500", "animate-slideInDown");
passwordInput.classList.remove("border-red-500", "animate-slideInDown");

const emailVal = emailInput.value.trim();
const passwordVal = passwordInput.value.trim();

// email error message
if (!emailVal && emailInput === document.activeElement) {
emailInput.classList.add("border-red-500");
emailInput.classList.remove("focus:ring-2", "focus:ring-indigo-500");
emailError.textContent = "Please enter your email";
emailError.classList.add("animate-slideInDown");
emailError.style.opacity = "1";
} else {
emailInput.classList.remove("border-red-500");
emailInput.classList.add("focus:ring-2", "focus:ring-indigo-500");
emailError.classList.remove("animate-slideInDown");
emailError.style.opacity = "0";
}

// Password error message
if (!passwordVal && passwordInput === document.activeElement) {
passwordInput.classList.add("border-red-500");
passwordInput.classList.remove("focus:ring-2", "focus:ring-indigo-500");
passwordError.textContent = "Please enter your password";
passwordError.classList.add("animate-slideInDown");
passwordError.style.opacity = "1";
} else {
passwordInput.classList.remove("border-red-500");
passwordInput.classList.add("focus:ring-2", "focus:ring-indigo-500");
passwordError.classList.remove("animate-slideInDown");
passwordError.style.opacity = "0";
}

// Enable submit button if both fields are filled
if (emailVal && passwordVal) {
submitBtn.disabled = false;
} else {
submitBtn.disabled = true;
}
}

emailInput.addEventListener("input", validateInputs);
passwordInput.addEventListener("input", validateInputs);
emailInput.addEventListener("blur", validateInputs);
passwordInput.addEventListener("blur", validateInputs);

function togglePassword() {
const pwd = document.getElementById("password");
const icon = document.getElementById("toggleIcon");
if (pwd.type === "password") {
pwd.type = "text";
icon.classList.remove("fa-eye");
icon.classList.add("fa-eye-slash");
} else {
pwd.type = "password";
icon.classList.remove("fa-eye-slash");
icon.classList.add("fa-eye");
}
}

function openForgotModal() {
const modal = document.getElementById("forgotModal");
modal.classList.remove("hidden");
modal.classList.add("flex", "animate-fadeSlideIn");
}

function closeForgotModal() {
const modal = document.getElementById("forgotModal");

// Remove any fade-in animation classes
modal.classList.remove("animate-fadeSlideIn");

// Add fade-out animation class
modal.classList.add("animate-fadeSlideOut");

// After animation completes (~300ms), hide the modal and remove fade-out class
modal.addEventListener('animationend', () => {
modal.classList.remove("flex", "animate-fadeSlideOut");
modal.classList.add("hidden");
}, { once: true });
}


const resetEmailInput = document.getElementById("resetEmail");
const forgotSubmitBtn = document.querySelector("#forgotPasswordForm button[type='submit']");

// Initially disable the submit button in the modal
forgotSubmitBtn.disabled = true;

resetEmailInput.addEventListener("input", () => {
const emailVal = resetEmailInput.value.trim();
forgotSubmitBtn.disabled = !emailVal || !resetEmailInput.checkValidity();
});

document.getElementById("forgotPasswordForm").addEventListener("submit", function (e) {
e.preventDefault();

forgotSubmitBtn.disabled = true;
forgotSubmitBtn.innerHTML = `<i class="fas fa-spinner fa-spin mr-2"></i> Checking...`;

setTimeout(() => {
Swal.fire({
icon: 'success',
text: 'If your email is correct, the reset link has been sent to ' + resetEmailInput.value,
toast: true,
position: 'top-end',
showConfirmButton: false,
timer: 3000,
timerProgressBar: true
});

forgotSubmitBtn.disabled = false;
forgotSubmitBtn.innerHTML = 'Submit';
document.getElementById("forgotPasswordForm").reset();
closeForgotModal();
}, 2000);
});

document.querySelector("form").addEventListener("submit", function (e) {
e.preventDefault();

// Disable button and show loading state
submitBtn.disabled = true;
submitBtn.innerHTML = `<i class="fas fa-spinner fa-spin mr-2"></i> Accessing your store...`;

// Simulate async login (replace with actual login logic)
setTimeout(() => {
const emailVal = emailInput.value.trim();
const passwordVal = passwordInput.value.trim();

// Dummy validation - accept only email "admin" and password "password"
if (emailVal === "admin@email.com" && passwordVal === "password") {
// Redirect to dashboard or other page
window.location.href = "/dashboard"; // change this to your actual page
} else {
// Show toast error
Swal.fire({
toast: true,
position: 'top-end',
icon: 'error',
title: 'Invalid email or password',
showConfirmButton: false,
timer: 3000,
timerProgressBar: true,
background: '#fef2f2', // light red background
color: '#b91c1c' // red text color
});

// Add red border and animation to inputs
emailInput.classList.add("border-red-500");
emailError.classList.add("animate-slideInDown");
passwordInput.classList.add("border-red-500");
passwordError.classList.add("animate-slideInDown");

// Show error messages under inputs
emailError.textContent = "Invalid email or password";
emailError.style.opacity = "1";
passwordError.textContent = "Invalid email or password";
passwordError.style.opacity = "1";

// Restore button text and enable button
submitBtn.disabled = false;
submitBtn.innerHTML = `<i class="fas fa-sign-in-alt mr-2"></i> Sign in`;
}
}, 2000);
});
</script>
</body>
</html>