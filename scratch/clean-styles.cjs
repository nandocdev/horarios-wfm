const fs = require('fs');
const path = require('path');

// Directorios a escanear
const targetDirs = [
    path.join(__dirname, '../resources/views'),
    path.join(__dirname, '../app')
];

let modifiedFilesCount = 0;
let totalFilesCount = 0;

// Función para buscar archivos recursivamente
function walkDir(dir, callback) {
    fs.readdirSync(dir).forEach(f => {
        let dirPath = path.join(dir, f);
        let isDirectory = fs.statSync(dirPath).isDirectory();
        if (isDirectory) {
            walkDir(dirPath, callback);
        } else {
            callback(dirPath);
        }
    });
}

// Procesar cada archivo blade
function processFile(filePath) {
    if (!filePath.endsWith('.blade.php')) return;
    
    totalFilesCount++;
    let content = fs.readFileSync(filePath, 'utf8');
    let originalContent = content;

    // 1. Reemplazar rounded-xl, 2xl, 3xl, lg -> rounded-md
    content = content.replace(/\brounded-(xl|2xl|3xl|lg)\b/g, 'rounded-md');

    // 2. Reemplazar shadow-lg, xl, 2xl y sus variantes con hover -> shadow-md / hover:shadow-md
    content = content.replace(/\b(hover:)?shadow-(lg|xl|2xl)\b/g, '$1shadow-md');

    // 3. Quitar gradientes (bg-gradient-to-*, from-*, via-*, to-*)
    content = content.replace(/\b(bg-gradient-to-[a-z]+|from-[a-z0-9-]+(?:\/\d+)?|via-[a-z0-9-]+(?:\/\d+)?|to-[a-z0-9-]+(?:\/\d+)?)\b/g, '');

    // 4. Quitar blurs y backdrop-blurs
    content = content.replace(/\b(backdrop-blur|blur)(?:-[a-z0-9]+)?\b/g, '');

    // 5. Reemplazar clase glass por bg-slate-50/90 (excluyendo magnifying-glass, eye-glass, etc.)
    content = content.replace(/(?<!magnifying-|hourglass-|eye-)\bglass\b/g, 'bg-slate-50/90');

    // 6. Limpieza estética de espacios duplicados dentro de atributos class
    content = content.replace(/class="([^"]*)"/g, (match, classList) => {
        const cleaned = classList
            .replace(/\s+/g, ' ')
            .trim();
        return `class="${cleaned}"`;
    });

    // Si hubo cambios, guardar
    if (content !== originalContent) {
        fs.writeFileSync(filePath, content, 'utf8');
        modifiedFilesCount++;
        console.log(`[MODIFICADO] ${path.relative(path.join(__dirname, '..'), filePath)}`);
    }
}

console.log('Iniciando limpieza masiva de estilos en archivos blade...');
targetDirs.forEach(dir => {
    if (fs.existsSync(dir)) {
        walkDir(dir, processFile);
    }
});

console.log(`\nProceso completado. Archivos analizados: ${totalFilesCount}. Archivos modificados: ${modifiedFilesCount}.`);
