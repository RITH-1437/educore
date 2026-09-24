#!/usr/bin/env sh
set -eu

# Install Node dependencies on first run.
if [ ! -d node_modules ] || [ ! -f node_modules/.package-lock.json ]; then
    npm install --no-audit --no-fund
fi

exec "$@"