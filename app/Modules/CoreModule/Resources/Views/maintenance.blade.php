<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mantenimiento - Antigravity WFM</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background: radial-gradient(circle at top left, #0f172a, #020617);
        }
        .glass {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .animate-float {
            animation: float 6s ease-in-out infinite;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-6 overflow-hidden">
    <!-- Fondo decorativo -->
    <div class="absolute top-0 left-0 w-full h-full overflow-hidden pointer-events-none">
        <div class="absolute top-[-10%] left-[-10%] w-96 h-96 bg-blue-500/20 rounded-full blur-[120px]"></div>
        <div class="absolute bottom-[-10%] right-[-10%] w-96 h-96 bg-indigo-500/20 rounded-full blur-[120px]"></div>
    </div>

    <div class="max-w-2xl w-full glass rounded-3xl p-12 text-center relative z-10 shadow-2xl">
        <div class="mb-8 flex justify-center">
            <div class="w-24 h-24 bg-indigo-600 rounded-2xl flex items-center justify-center shadow-lg shadow-indigo-500/40 animate-float">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
            </div>
        </div>

        <h1 class="text-4xl font-bold text-white mb-4 tracking-tight">Mejorando tu Experiencia</h1>
        <p class="text-slate-400 text-lg mb-8 leading-relaxed">
            {{ $message }}
        </p>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-sm text-slate-500">
            <div class="p-4 rounded-xl bg-slate-800/50">
                <span class="block text-indigo-400 font-semibold mb-1">Motivo</span>
                Actualización de Sistema
            </div>
            <div class="p-4 rounded-xl bg-slate-800/50">
                <span class="block text-indigo-400 font-semibold mb-1">Duración</span>
                Estimada 15 min
            </div>
            <div class="p-4 rounded-xl bg-slate-800/50">
                <span class="block text-indigo-400 font-semibold mb-1">Soporte</span>
                Mesa de Ayuda
            </div>
        </div>

        <div class="mt-12">
            <p class="text-xs text-slate-600 uppercase tracking-widest">Antigravity Workforce Management &copy; 2026</p>
        </div>
    </div>
</body>
</html>
