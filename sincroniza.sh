#!/bin/bash

set -e

SRC="/home/ferncastillo/Projects/WorkForce/horarios-wfm/"
DEST="ferncastillo@10.19.15.240:/var/www/wfm-scheduler/"

rsync -avzPr \
  --delete \
  --exclude=".git" \
  --exclude="storage/" \
  --exclude="*.log" \
  --exclude=".idea" \
  --exclude=".vscode" \
  --exclude="bootstrap/cache/" \
  --exclude="public/hot" \
  "$SRC" "$DEST"
