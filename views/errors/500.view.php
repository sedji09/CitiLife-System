<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Palaging ibabalik sa login page, i-logout muna para sigurado
$homeLink = "/" . PROJECT_DIR . "/logout";
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 - Server Error | CitiLife System</title>
    <link rel="stylesheet" href="/<?= PROJECT_DIR ?>/tailwind/src/output.css">
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
                radial-gradient(at 0% 0%, rgba(220, 38, 38, 0.08) 0px, transparent 50%),
                radial-gradient(at 100% 0%, rgba(59, 130, 246, 0.02) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(220, 38, 38, 0.08) 0px, transparent 50%),
                radial-gradient(at 0% 100%, rgba(59, 130, 246, 0.02) 0px, transparent 50%);
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
            filter: drop-shadow(0 20px 30px rgba(0, 0, 0, 0.05));
            animation: float 6s ease-in-out infinite;
            mask-image: radial-gradient(circle, black 40%, transparent 80%);
            -webkit-mask-image: radial-gradient(circle, black 40%, transparent 80%);
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0) rotate(0);
            }

            50% {
                transform: translateY(-12px) rotate(1.5deg);
            }
        }

        .error-code {
            font-size: clamp(5.5rem, 15vw, 7.5rem);
            line-height: 0.9;
            background: linear-gradient(180deg, #dc2626 30%, rgba(220, 38, 38, 0.1) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 800;
            letter-spacing: -0.05em;
        }

        .btn-dark {
            background: #111827;
            box-shadow: 0 10px 25px -5px rgba(17, 24, 39, 0.4);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .btn-dark:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 35px -5px rgba(17, 24, 39, 0.5);
            background: #000000;
        }
    </style>
</head>

<body class="min-h-screen flex items-center justify-center p-4 text-center">
    <div class="bg-gradient-mesh"></div>

    <div class="max-w-xl w-full flex flex-col items-center">
        <div class="mascot-container mb-2">
            <img src="/<?= PROJECT_DIR ?>/public/assets/img/errors/500_illustrations.png" alt="500" class="mascot-img">
        </div>

        <div class="relative z-10 w-full">
            <h1 class="error-code mb-2">500</h1>
            <h2 class="text-2xl sm:text-3xl font-extrabold text-gray-900 mb-2 tracking-tight">System Under Maintenance.
            </h2>
            <p class="text-base text-gray-500 mb-6 max-w-md mx-auto leading-relaxed">
                Medyo nag-hang ang system. Inaayos na ito ng aming technical team. Pasensya na sa abala!
            </p>

            <div class="flex flex-col sm:flex-row items-center justify-center gap-3 max-w-md mx-auto">
                <a href="<?= $_SERVER['REQUEST_URI'] ?>"
                    class="bg-red-600 w-full px-8 py-4 text-white font-bold rounded-2xl flex items-center justify-center gap-2 text-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Refresh the Page
                </a>
                <a href="<?= $homeLink ?>"
                    class="w-full px-8 py-4 bg-white border border-gray-200 hover:border-gray-300 text-gray-700 font-bold rounded-2xl transition-all flex items-center justify-center gap-2 text-sm">
                    Go Back
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