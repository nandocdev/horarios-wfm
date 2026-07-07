<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mantenimiento - WFM CSS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: #0f172a;
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
    <div class="max-w-2xl w-full bg-slate-800/90 rounded-md p-12 text-center shadow-md border border-slate-700/50">
        <div class="mb-8 flex justify-center">
            <div class="w-24 h-24 bg-slate-700 rounded-md flex items-center justify-center shadow-md">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
            </div>
        </div>

        <h1 class="text-3xl font-bold text-white mb-4 tracking-tight">Mejorando tu Experiencia</h1>
        <p class="text-slate-400 text-lg mb-8 leading-relaxed">
            {{ $message }}
        </p>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm text-slate-500">
            <div class="p-4 rounded-md bg-slate-800">
                <span class="block text-slate-300 font-semibold mb-1">Motivo</span>
                Actualización de Sistema
            </div>
            <div class="p-4 rounded-md bg-slate-800">
                <span class="block text-slate-300 font-semibold mb-1">Duración</span>
                Estimada 15 min
            </div>
            <div class="p-4 rounded-md bg-slate-800">
                <span class="block text-slate-300 font-semibold mb-1">Soporte</span>
                Mesa de Ayuda
            </div>
        </div>

        <div class="mt-12">
            <p class="text-xs text-slate-600 uppercase tracking-widest">WFM CSS &copy; 2026</p>
        </div>
    </div>
</body>
</html>
