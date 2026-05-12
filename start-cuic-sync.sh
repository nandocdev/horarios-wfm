#!/bin/bash

# Directorio de la aplicación (ajustar según entorno)
APP_DIR="/var/www/wfm-scheduler"
if [ ! -d "$APP_DIR" ]; then
    APP_DIR=$(pwd)
fi

PID_FILE="/var/run/cuic-sync.pid"
LOG_FILE="/var/log/cuic-sync.log"

# Lista de comandos CUIC a ejecutar en segundo plano
# comando 1: cuic-sync-realtime (ejecutar cada 15 segundos): 
# comando 2: cuic-sync (ejecutar cada 300 segundos):
# Solo se ejecuta el comando si la variable de entorno CUIC_SYNC_REALTIME o CUIC_SYNC está en true
# Esto es para poder ejecutar los comandos de forma independiente
COMMANDS=(
    "php artisan cuic:sync-realtime --loop --interval=15"
    "php artisan cuic:sync --loop --interval=300 --minutes=60"
)

# Función de logging
log() {
    echo "$(date '+%Y-%m-%d %H:%M:%S') - $1" | tee -a "$LOG_FILE"
}

# Función para verificar procesos
is_running() {
    if [ -f "$PID_FILE" ]; then
        while read -r PID; do
            if [ -n "$PID" ] && ps -p "$PID" > /dev/null 2>&1; then
                return 0
            fi
        done < "$PID_FILE"
    fi
    return 1
}

# Función para detener procesos
stop_process() {
    if [ -f "$PID_FILE" ]; then
        log "Deteniendo procesos CUIC existentes..."
        while read -r PID; do
            if [ -n "$PID" ] && ps -p "$PID" > /dev/null 2>&1; then
                log "Enviando SIGTERM a PID: $PID"
                kill "$PID"
            fi
        done < "$PID_FILE"
        
        sleep 3
        
        while read -r PID; do
            if [ -n "$PID" ] && ps -p "$PID" > /dev/null 2>&1; then
                log "Forzando SIGKILL a PID: $PID"
                kill -9 "$PID"
            fi
        done < "$PID_FILE"
        
        rm -f "$PID_FILE"
    fi
}

# Función para iniciar procesos
start_process() {
    cd "$APP_DIR" || exit 1
    log "Iniciando suite de comandos CUIC Sync (Realtime + ETL)..."
    
    > "$PID_FILE"

    for CMD in "${COMMANDS[@]}"; do
        log "Ejecutando: $CMD"
        nohup $CMD >> "$LOG_FILE" 2>&1 &
        echo $! >> "$PID_FILE"
    done

    log "Procesos CUIC iniciados."
}

# Main
log "=== Gestión de Sincronización CUIC ==="

if is_running; then
    log "Reiniciando procesos para actualizar configuración..."
    stop_process
fi

start_process
log "PIDs activos: $(cat $PID_FILE | tr '\n' ' ')"
