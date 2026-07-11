#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
LOG_DIR="$ROOT_DIR/runtime/logs"
PID_DIR="$ROOT_DIR/runtime/pids"

mkdir -p "$LOG_DIR" "$PID_DIR"

start_server() {
  local name="$1"
  shift

  local pid_file="$PID_DIR/$name.pid"
  local log_file="$LOG_DIR/$name.log"

  if [[ -f "$pid_file" ]]; then
    local old_pid
    old_pid="$(cat "$pid_file")"
    if [[ -n "$old_pid" ]] && kill -0 "$old_pid" 2>/dev/null; then
      echo "$name đã chạy với PID $old_pid"
      return 0
    fi
    rm -f "$pid_file"
  fi

  (
    cd "$ROOT_DIR"
    if command -v setsid >/dev/null 2>&1; then
      setsid "$@" >"$log_file" 2>&1 &
    else
      nohup "$@" >"$log_file" 2>&1 &
    fi
    echo $! >"$pid_file"
  )

  echo "Đã bật $name: PID $(cat "$pid_file"), log $log_file"
}

start_server customer-api php yii serve --docroot=customer/web --port=8081
start_server admin-api php yii serve --docroot=backend/web --port=8082
start_server customer-frontend php -S localhost:8083 -t customer-frontend
start_server admin-frontend php -S localhost:8084 -t admin-frontend

sleep 1

failed=0
for pid_file in "$PID_DIR"/*.pid; do
  [[ -e "$pid_file" ]] || continue
  name="$(basename "$pid_file" .pid)"
  pid="$(cat "$pid_file")"
  if ! kill -0 "$pid" 2>/dev/null; then
    echo "$name không chạy được. Log gần nhất:"
    tail -n 40 "$LOG_DIR/$name.log" || true
    failed=1
  fi
done

if [[ "$failed" -ne 0 ]]; then
  exit 1
fi

cat <<'INFO'

Demo local đang chạy:
- Customer API:      http://localhost:8081
- Admin backend/API: http://localhost:8082
- Customer frontend: http://localhost:8083/index.php
- Admin frontend:    http://localhost:8084/admin/login.php

Dừng demo bằng: ./stop-demo.sh
INFO
