<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$role = $_SESSION['role'] ?? 'guest';
$homeLink = ($role !== 'guest') ? url('dashboard') : url('login');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - Access Denied | Citilife System</title>
    <link rel="icon" type="image/png" href="<?= function_exists('getSystemLogoUrl') ? getSystemLogoUrl() : url('public/assets/img/logo/citilife-logo.png') ?>">
    <link rel="stylesheet" href="<?= url('tailwind/src/output.css') ?>">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap');

        body {
            font-family: 'Outfit', sans-serif;
            background: #fdfdfd;
            overflow-x: hidden;
        }

        .bg-gradient-mesh {
            position: fixed;
            inset: 0;
            z-index: -1;
            background-color: #fdfdfd;
            background-image:
                radial-gradient(at 0% 0%, rgba(220, 38, 38, 0.06) 0px, transparent 50%),
                radial-gradient(at 100% 0%, rgba(217, 119, 6, 0.05) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(220, 38, 38, 0.05) 0px, transparent 50%),
                radial-gradient(at 0% 100%, rgba(30, 41, 59, 0.05) 0px, transparent 50%);
        }

        .mascot-container {
            position: relative;
            width: 100%;
            max-width: 280px;
            margin: 0 auto;
        }

        .mascot-img {
            width: 100%;
            height: auto;
            filter: drop-shadow(0 15px 25px rgba(0, 0, 0, 0.07));
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% {
                transform: translateY(0) rotate(0);
            }
            50% {
                transform: translateY(-12px) rotate(1.5deg);
            }
        }

        .error-code {
            font-size: clamp(5.5rem, 15vw, 7.5rem);
            line-height: 0.9;
            background: linear-gradient(180deg, #dc2626 30%, rgba(185, 28, 28, 0.15) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 800;
            letter-spacing: -0.05em;
        }

        .btn-premium {
            background: #dc2626;
            box-shadow: 0 10px 25px -5px rgba(220, 38, 38, 0.4);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .btn-premium:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 35px -5px rgba(220, 38, 38, 0.5);
            background: #b91c1c;
        }
    </style>
</head>

<body class="min-h-screen flex items-center justify-center p-4 text-center">
    <div class="bg-gradient-mesh"></div>

    <div class="max-w-xl w-full flex flex-col items-center">
        <div class="mascot-container mb-2">
            <img src="<?= url('public/assets/img/errors/403_illustration.png?v=' . filemtime(__DIR__ . '/../../public/assets/img/errors/403_illustration.png')) ?>" alt="403 Access Denied" class="mascot-img">
        </div>

        <div class="relative z-10 w-full">
            <h1 class="error-code mb-2">403</h1>
            <h2 class="text-2xl sm:text-3xl font-extrabold text-gray-900 mb-2 tracking-tight">Access Denied</h2>
            <p class="text-base text-gray-600 mb-3 max-w-md mx-auto leading-relaxed">
                You do not have permission to view this medical record or page.
            </p>
            <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-red-50 border border-red-200 text-red-700 text-xs font-semibold mb-6 shadow-xs">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-red-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                Protected under the Philippine Data Privacy Act of 2012
            </div>

            <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-center gap-3 max-w-md mx-auto">
                <a href="<?= $homeLink ?>" class="btn-premium flex-1 w-full h-12 px-5 text-white font-bold rounded-2xl flex items-center justify-center gap-2 text-sm whitespace-nowrap">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                        <polyline points="9 22 9 12 15 12 15 22"></polyline>
                    </svg>
                    Return to Dashboard
                </a>
                <button type="button" onclick="if(window.history.length > 1) { window.history.back(); } else { window.location.href='<?= $homeLink ?>'; }"
                    class="flex-1 w-full h-12 px-5 bg-white border border-gray-200 hover:border-gray-300 text-gray-700 font-bold rounded-2xl transition-all flex items-center justify-center gap-2 text-sm whitespace-nowrap shadow-xs cursor-pointer">
                    Go Back
                </button>
            </div>
        </div>

        <div class="mt-10 pt-4 border-t border-gray-100 w-full max-w-xs">
            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">
                CITILIFE DIAGNOSTIC CENTER
            </p>
        </div>
    </div>
</body>

</html>
