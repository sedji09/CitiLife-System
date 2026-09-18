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
    <title>503 - Service Temporarily Unavailable | Citilife System</title>
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
                radial-gradient(at 0% 0%, rgba(37, 99, 235, 0.06) 0px, transparent 50%),
                radial-gradient(at 100% 0%, rgba(79, 70, 229, 0.05) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(37, 99, 235, 0.05) 0px, transparent 50%),
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
            background: linear-gradient(180deg, #2563eb 30%, rgba(37, 99, 235, 0.15) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 800;
            letter-spacing: -0.05em;
        }

        .btn-primary-blue {
            background: #2563eb;
            box-shadow: 0 10px 25px -5px rgba(37, 99, 235, 0.4);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .btn-primary-blue:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 35px -5px rgba(37, 99, 235, 0.5);
            background: #1d4ed8;
        }
    </style>
</head>

<body class="min-h-screen flex items-center justify-center p-4 text-center">
    <div class="bg-gradient-mesh"></div>

    <div class="max-w-xl w-full flex flex-col items-center">
        <div class="mascot-container mb-2">
            <img src="<?= url('public/assets/img/errors/503_illustration.png?v=' . filemtime(__DIR__ . '/../../public/assets/img/errors/503_illustration.png')) ?>" alt="503 System Under Maintenance" class="mascot-img">
        </div>

        <div class="relative z-10 w-full">
            <h1 class="error-code mb-2">503</h1>
            <h2 class="text-2xl sm:text-3xl font-extrabold text-gray-900 mb-2 tracking-tight">System Under Maintenance</h2>
            <p class="text-base text-gray-600 mb-6 max-w-md mx-auto leading-relaxed">
                Our diagnostic database service is currently undergoing routine maintenance or updates. Normal services will resume shortly.
            </p>

            <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-center gap-3 max-w-md mx-auto">
                <button type="button" onclick="window.location.reload()"
                    class="btn-primary-blue flex-1 w-full h-12 px-5 text-white font-bold rounded-2xl flex items-center justify-center gap-2 text-sm whitespace-nowrap cursor-pointer">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0 animate-spin-hover" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/>
                    </svg>
                    Refresh Page
                </button>
                <a href="<?= $homeLink ?>"
                    class="flex-1 w-full h-12 px-5 bg-white border border-gray-200 hover:border-gray-300 text-gray-700 font-bold rounded-2xl transition-all flex items-center justify-center gap-2 text-sm whitespace-nowrap shadow-xs">
                    Return to Login
                </a>
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
