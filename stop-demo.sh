#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PID_DIR="$ROOT_DIR/runtime/pids"

if [[ ! -d "$PID_DIR" ]]; then
  echo "Không có thư mục PID: $PID_DIR"
  exit 0
fi

shopt -s nullglob
pid_files=("$PID_DIR"/*.pid)

if [[ "${#pid_files[@]}" -eq 0 ]]; then
  echo "Không có process demo nào đang được quản lý bằng PID file."
  exit 0
fi

for pid_file in "${pid_files[@]}"; do
  name="$(basename "$pid_file" .pid)"
  pid="$(cat "$pid_file")"

  if [[ -z "$pid" ]] || ! kill -0 "$pid" 2>/dev/null; then
    echo "$name không còn chạy; xóa PID file."
    rm -f "$pid_file"
    continue
  fi

  echo "Đang dừng $name với PID $pid"
  kill "$pid" 2>/dev/null || true

  for _ in {1..20}; do
    if ! kill -0 "$pid" 2>/dev/null; then
      break
    fi
    sleep 0.25
  done

  if kill -0 "$pid" 2>/dev/null; then
    echo "$name chưa dừng sau SIGTERM; gửi SIGKILL."
    kill -9 "$pid" 2>/dev/null || true
  fi

  rm -f "$pid_file"
done

echo "Đã dừng demo local."
