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
    <title>500 - Server Error | CitiLife System</title>
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
                radial-gradient(at 0% 0%, rgba(220, 38, 38, 0.08) 0px, transparent 50%),
                radial-gradient(at 100% 0%, rgba(59, 130, 246, 0.02) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(220, 38, 38, 0.08) 0px, transparent 50%),
                radial-gradient(at 0% 100%, rgba(59, 130, 246, 0.02) 0px, transparent 50%);
        }

        .error-code {
            font-size: clamp(8rem, 20vw, 12rem);
            line-height: 0.8;
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

        .pulse-red {
            animation: pulse-red 2s infinite;
        }

        @keyframes pulse-red {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.05); opacity: 0.8; }
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-6 text-center">
    <div class="bg-gradient-mesh"></div>

    <div class="max-w-4xl w-full">
        <div class="mb-12 flex justify-center">
            <div class="w-32 h-32 bg-red-50 rounded-[40px] flex items-center justify-center shadow-inner pulse-red">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
        </div>

        <div class="relative z-10">
            <h1 class="error-code mb-4">500</h1>
            <h2 class="text-3xl sm:text-4xl font-extrabold text-gray-900 mb-4 tracking-tight">System Under Maintenance.</h2>
            <p class="text-lg text-gray-500 mb-10 max-w-lg mx-auto">
                Medyo nag-hang ang system. Inaayos na ito ng aming technical team. Pasensya na sa abala!
            </p>

            <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                <a href="<?= $_SERVER['REQUEST_URI'] ?>" class="btn-dark w-full sm:w-auto px-10 py-4 text-white font-bold rounded-2xl flex items-center justify-center gap-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    I-refresh ang Page
                </a>
                <a href="<?= $homeLink ?>" class="w-full sm:w-auto px-10 py-4 bg-white border-2 border-gray-100 hover:border-gray-300 text-gray-700 font-bold rounded-2xl transition-all">
                    Bumalik sa Dashboard
                </a>
            </div>
        </div>

        <div class="mt-16 pt-8 border-t border-gray-100/50">
            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">
                CITILIFE DIAGNOSTIC CENTER • CRITICAL ERROR REPORTED
            </p>
        </div>
    </div>
</body>
</html>
