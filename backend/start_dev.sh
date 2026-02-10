#!/bin/bash

# Function to kill all background processes on exit
cleanup() {
    echo "Stopping all services..."
    kill $(jobs -p) 2>/dev/null
    exit
}

trap cleanup SIGINT SIGTERM

echo "🚀 Starting AppSignals Development Environment..."

# Allow overriding default ports
PORT="${PORT:-8000}"
VITE_PORT="${VITE_PORT:-5173}"

if lsof -iTCP:"${PORT}" -sTCP:LISTEN -t >/dev/null 2>&1; then
    if [ "${AUTO_KILL_PORT}" = "true" ]; then
        echo "⚠️  Port ${PORT} in use. Stopping existing process..."
        kill $(lsof -iTCP:"${PORT}" -sTCP:LISTEN -t) 2>/dev/null
    else
        echo "❌ Port ${PORT} is already in use."
        echo "   Stop the existing process or rerun with PORT=8001 (or AUTO_KILL_PORT=true)."
        exit 1
    fi
fi

# 1. Start Laravel Server
echo "➡️  Starting Laravel Server..."
php artisan serve --host=127.0.0.1 --port="${PORT}" &

# 2. Start Reverb Server
echo "➡️  Starting Reverb Server..."
php artisan reverb:start &

# 3. Start Queue Worker
echo "➡️  Starting Queue Worker..."
php artisan queue:work &

# 4. Start Vite
echo "➡️  Starting Vite..."
npm run dev -- --host 127.0.0.1 --port="${VITE_PORT}" &

# Wait for all background processes
wait
