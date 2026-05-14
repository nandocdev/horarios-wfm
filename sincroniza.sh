#!/bin/bash

set -e

SRC="/home/ferncastillo/Projects/WorkForce/horarios-wfm/"
DEST="ferncastillo@10.19.15.240:/var/www/wfm-scheduler/"

# Validar que estamos en la rama develop
cd "$SRC"
CURRENT_BRANCH=$(git branch --show-current)

if [ "$CURRENT_BRANCH" != "develop" ]; then
    echo "ERROR: Debes estar en la rama 'develop' para sincronizar."
    echo "Rama actual: $CURRENT_BRANCH"
    echo "Ejecuta: git checkout develop"
    exit 1
fi

echo "✓ Validación exitosa: Estás en la rama 'develop'"
echo "Iniciando sincronización..."

rsync -avzPr \
  --delete \
  --exclude=".git" \
  --exclude="storage/" \
  --exclude="*.log" \
  --exclude=".idea" \
  --exclude=".vscode" \
  --exclude="bootstrap/cache/" \
  --exclude="public/hot" \
  --exclude=".env" \
  "$SRC" "$DEST"

echo "✓ Sincronización completada"