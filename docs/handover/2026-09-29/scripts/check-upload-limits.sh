#!/usr/bin/env bash
# READ-ONLY check of upload-size limits on the Contabo box (staging + production).
# Changes nothing: prints effective PHP-FPM and nginx limits only. No secrets, no .env reads.
# Needed: dispute evidence uploads are 5 files x 10 MB (DisputeAuditReportRequest), so each
# limit must be >= ~52M. Run with: ssh rozine-staging 'bash -s' < check-upload-limits.sh
set -euo pipefail

echo "== PHP-FPM pools (effective ini, per pool config) =="
for pool in rozine rozine-prod; do
  for f in /etc/php/8.5/fpm/pool.d/${pool}.conf; do
    [ -f "$f" ] || { echo "$pool: pool file not found ($f)"; continue; }
    echo "-- $pool ($f): overrides"
    grep -E '^\s*php_(admin_)?value\[(upload_max_filesize|post_max_size|max_file_uploads|memory_limit)\]' "$f" || echo "   (no overrides; uses php.ini)"
  done
done

echo "== php.ini (FPM 8.5) =="
grep -E '^\s*(upload_max_filesize|post_max_size|max_file_uploads|memory_limit)\s*=' /etc/php/8.5/fpm/php.ini || true
for d in /etc/php/8.5/fpm/conf.d; do
  grep -rhE '^\s*(upload_max_filesize|post_max_size|max_file_uploads)\s*=' "$d" 2>/dev/null | sed 's/^/   conf.d: /' || true
done

echo "== nginx client_max_body_size (all configs) =="
grep -rnE 'client_max_body_size' /etc/nginx/ 2>/dev/null || echo "   (not set anywhere: nginx default is 1m)"

echo "== nginx server_name blocks (to map which file serves which host) =="
grep -rnE '^\s*server_name' /etc/nginx/sites-enabled/ 2>/dev/null || true
