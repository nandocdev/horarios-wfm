#!/bin/bash

APP_DIR="/var/www/wfm-scheduler"
PID_FILE="/var/run/cisco-sync.pid"
LOG_FILE="/var/log/cisco-sync.log"

# Lista de comandos a ejecutar en segundo plano
COMMANDS=(
    "php artisan cisco:sync --loop --interval=5 --isolated=1"
    
)

# Función de logging
log() {
    echo "$(date '+%Y-%m-%d %H:%M:%S') - $1" | tee -a "$LOG_FILE"
}

# Función para verificar si al menos uno de los procesos está corriendo
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

# Función para detener todos los procesos en el PID_FILE
stop_process() {
    if [ -f "$PID_FILE" ]; then
        log "Deteniendo procesos existentes..."
        while read -r PID; do
            if [ -n "$PID" ] && ps -p "$PID" > /dev/null 2>&1; then
                log "Enviando SIGTERM a PID: $PID"
                kill "$PID"
            fi
        done < "$PID_FILE"
        
        sleep 5
        
        # Forzar terminación si siguen activos
        while read -r PID; do
            if [ -n "$PID" ] && ps -p "$PID" > /dev/null 2>&1; then
                log "Forzando SIGKILL a PID: $PID"
                kill -9 "$PID"
            fi
        done < "$PID_FILE"
        
        rm -f "$PID_FILE"
    fi
}

# Función para iniciar todos los comandos
start_process() {
    cd "$APP_DIR" || {
        log "ERROR: No se puede acceder a $APP_DIR"
        exit 1
    }
    
    log "Iniciando suite de comandos Cisco Sync..."
    
    # Limpiar PID_FILE antes de iniciar
    > "$PID_FILE"

    for CMD in "${COMMANDS[@]}"; do
        log "Ejecutando: $CMD"
        nohup $CMD >> "$LOG_FILE" 2>&1 &
        echo $! >> "$PID_FILE"
    done

    log "Todos los procesos iniciados. PIDs guardados en $PID_FILE"
    log "Para monitorear: tail -f $LOG_FILE"
}

# Main
log "========================================="
log "Ejecución diaria del sincronizador Cisco"
log "========================================="

# Verificar si ya está corriendo
if is_running; then
    log "El proceso ya está activo con PID: $(cat $PID_FILE)"
    log "Reiniciando proceso para asegurar estado fresco..."
    stop_process
fi

# Iniciar nuevo proceso
start_process

log "Script completado. El proceso continuará ejecutándose en segundo plano."
log "PID: $(cat $PID_FILE)"
