#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DEST="$ROOT/.cache/tests"
mkdir -p "$DEST"

download() {
    local name="$1" url="$2" checksum="$3"
    local archive="$DEST/$name.tar.gz"
    if [[ ! -f "$archive" ]]; then
        curl --fail --location --silent --show-error --retry 2 "$url" -o "$archive"
    fi
    php -r 'if (hash_file("sha256", $argv[1]) !== $argv[2]) { fwrite(STDERR, "Archive checksum mismatch; remove the cached archive and retry.\n"); exit(1); }' "$archive" "$checksum"
    tar -xzf "$archive" -C "$DEST"
}

download wordpress-7.1.4 \
    https://github.com/WordPress/wordpress-develop/archive/refs/tags/7.1.4.tar.gz \
    7189398a54c042de387c8f4a61e4a8770839d6dfe382d6eb86eabf142a08d786
download acf-6.8.11 \
    https://github.com/AdvancedCustomFields/acf/archive/refs/tags/6.8.11.tar.gz \
    2215d88a371077ddc2f29fb10a439ad990b34e7cc2cea497a3319cde00dd9dd5

printf 'WordPress 7.1.4 and ACF Free 6.8.11 test sources ready. No database changes made.\n'
