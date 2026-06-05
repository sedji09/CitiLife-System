<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$role = $_SESSION['role'] ?? 'guest';
$homeLink = ($role !== 'guest') ? "/" . PROJECT_DIR . "/dashboard" : "/" . PROJECT_DIR . "/login";
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page Not Found | CitiLife System</title>
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
                radial-gradient(at 0% 0%, rgba(220, 38, 38, 0.05) 0px, transparent 50%),
                radial-gradient(at 100% 0%, rgba(59, 130, 246, 0.05) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(220, 38, 38, 0.05) 0px, transparent 50%),
                radial-gradient(at 0% 100%, rgba(59, 130, 246, 0.05) 0px, transparent 50%);
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
            background: linear-gradient(180deg, #111827 30%, rgba(17, 24, 39, 0.1) 100%);
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
            <img src="/<?= PROJECT_DIR ?>/public/assets/img/errors/404_illustration.png" alt="404" class="mascot-img">
        </div>

        <div class="relative z-10 w-full">
            <h1 class="error-code mb-2">404</h1>
            <h2 class="text-2xl sm:text-3xl font-extrabold text-gray-900 mb-2 tracking-tight">Page Not Found</h2>
            <p class="text-base text-gray-500 mb-6 max-w-md mx-auto leading-relaxed">
                Oops! The page you are looking for doesn't exist.
            </p>

            <div class="flex items-center justify-center">
                <button onclick="window.history.back()" class="btn-premium px-8 py-4 text-white font-bold rounded-2xl flex items-center justify-center text-sm">
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