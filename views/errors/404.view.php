<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$role = $_SESSION['role'] ?? 'guest';
$homeLink = ($role !== 'guest') ? "/".PROJECT_DIR."/dashboard" : "/".PROJECT_DIR."/login";
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
            overflow: hidden;
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
            max-width: 450px;
            margin: 0 auto;
        }

        .mascot-img {
            width: 100%;
            height: auto;
            /* mix-blend-mode: multiply; */ /* Removing this as it might darken too much */
            filter: drop-shadow(0 20px 30px rgba(0,0,0,0.05));
            animation: float 6s ease-in-out infinite;
            mask-image: radial-gradient(circle, black 40%, transparent 80%);
            -webkit-mask-image: radial-gradient(circle, black 40%, transparent 80%);
        }

        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(0); }
            50% { transform: translateY(-20px) rotate(2deg); }
        }

        .error-code {
            font-size: clamp(8rem, 20vw, 12rem);
            line-height: 0.8;
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
<body class="min-h-screen flex items-center justify-center p-6 text-center">
    <div class="bg-gradient-mesh"></div>

    <div class="max-w-4xl w-full">
        <div class="mascot-container mb-4">
            <img src="/<?= PROJECT_DIR ?>/public/assets/img/errors/404_illustration.png" alt="404" class="mascot-img">
        </div>

        <div class="relative z-10">
            <h1 class="error-code mb-4">404</h1>
            <h2 class="text-3xl sm:text-4xl font-extrabold text-gray-900 mb-4 tracking-tight">Oops! Kung saan-saan ka nakakarating.</h2>
            <p class="text-lg text-gray-500 mb-10 max-w-lg mx-auto">
                Wala dito yung page na hinahanap mo. Parang maling test result lang, kailangang balikan para makasiguro.
            </p>

            <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                <a href="<?= $homeLink ?>" class="btn-premium w-full sm:w-auto px-8 py-4 text-white font-bold rounded-2xl flex items-center justify-center gap-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z" />
                    </svg>
                    Balik sa Dashboard
                </a>
                <button onclick="window.history.back()" class="w-full sm:w-auto px-8 py-4 bg-white border-2 border-gray-100 hover:border-gray-300 text-gray-700 font-bold rounded-2xl transition-all">
                    Bumalik sa Pinanggalingan
                </button>
            </div>
        </div>

        <div class="mt-16 pt-8 border-t border-gray-100/50">
            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">
                CITILIFE DIAGNOSTIC CENTER • INTERNAL SECURITY PROTOCOL
            </p>
        </div>
    </div>
</body>
</html>
