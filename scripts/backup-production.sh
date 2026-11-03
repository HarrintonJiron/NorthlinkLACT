#!/bin/sh
set -eu

backup_dir="${BACKUP_DIR:-/backups}"
timestamp="$(date +%Y%m%d-%H%M%S)"
mkdir -p "$backup_dir"
output="$backup_dir/northlink-$timestamp.dump"

pg_dump --format=custom --no-owner --no-acl --file="$output"
pg_restore --list "$output" >/dev/null
printf '%s\n' "$output"
