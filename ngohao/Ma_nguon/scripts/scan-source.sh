#!/usr/bin/env bash
set -uo pipefail

if [[ $# -ne 1 ]]; then
  echo 'Usage: scan-source.sh DIRECTORY' >&2
  exit 2
fi

root="$1"

if [[ ! -d "$root" || ! -r "$root" ]]; then
  echo "ERROR: directory not found or unreadable: $root" >&2
  exit 2
fi

if ! command -v php >/dev/null 2>&1; then
  echo 'ERROR: php is required' >&2
  exit 2
fi

list="$(mktemp)" || exit 2
trap 'rm -f -- "$list"' EXIT

if ! find "$root" -type f -name '*.php' -print0 > "$list"; then
  echo "ERROR: cannot read directory: $root" >&2
  exit 2
fi

total=0
passed=0
failed=0

while IFS= read -r -d '' file; do
  total=$((total + 1))
  if php -l "$file" >/dev/null 2>&1; then
    passed=$((passed + 1))
  else
    failed=$((failed + 1))
    echo "FAIL $file" >&2
    php -l "$file" >&2 || true
  fi
done < "$list"

printf 'TOTAL=%s PASS=%s FAIL=%s\n' "$total" "$passed" "$failed"

if [[ "$total" -eq 0 ]]; then
  exit 2
fi
if [[ "$failed" -gt 0 ]]; then
  exit 1
fi

exit 0
