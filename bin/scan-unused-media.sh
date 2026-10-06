#!/usr/bin/env bash
#
# Scan a small batch of images and PDFs for references and mark the unreferenced ones.
# For each media type it runs: find-unused-<type> -> check-unused-<type> -> mark-unused-<type>.
# Only marks (postmeta _marked_unused). It never deletes anything.
#
# Intended for cron. Attachments scanned within the last seven days are skipped
# by find-unused-images, so every run picks up the next batch.
#
# Configuration: defaults below are for the production server. Override any of
# them in scan-unused-media.env next to this script (see
# scan-unused-media.env.example). Values in that file win over the environment.
#   WP_URL        Site URL passed to wp --url
#   WP_BIN        WP-CLI binary
#   WP_PATH       WordPress path for wp --path
#   WP_EXTRA_ARGS Extra global WP-CLI args, e.g. --allow-root
#   MEDIA_TYPES   Types to scan, in order: images, pdfs (default: "images pdfs")
#   BATCH_SIZE    Attachments per type and run
#   BATCH_SIZE_IMAGES / BATCH_SIZE_PDFS  Override BATCH_SIZE for one type
#   MIN_AGE_DAYS  Only check attachments uploaded more than this many days ago (default: 90, 0 = all)
#   FIND_BATCH_SIZE  Attachments per search batch inside find (default: 50)
#   PAUSE_MS      Pause between find batches
#   MARKDOWN_DIR  Worddown export directory
#   RUN_DIR       Where reports and the run lock are written
#
# If one type fails the other still runs; the script then exits with status 1.
#   DRY_RUN=1     Run find and check, preview the marking, save no scan history
#
# Cron example (every 30 minutes; keep clear of the nightly Worddown export at 03:00):
#   */30 4-23 * * * /usr/local/akwww_root/scripts/scan-unused-media.sh >> /usr/local/akwww_root/tmp/logs/unused-media.log 2>&1

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ENV_FILE="${SCRIPT_DIR}/scan-unused-media.env"
if [[ -f "${ENV_FILE}" ]]; then
    set -a
    # shellcheck disable=SC1090
    source "${ENV_FILE}"
    set +a
fi

WP_URL="${WP_URL:-https://www.alingsas.se}"
WP_BIN="${WP_BIN:-/usr/local/bin/wp}"
WP_PATH="${WP_PATH:-/usr/local/akwww_root/www/wp}"
WP_EXTRA_ARGS="${WP_EXTRA_ARGS---allow-root}"
MEDIA_TYPES="${MEDIA_TYPES:-images pdfs}"
BATCH_SIZE="${BATCH_SIZE:-50}"
BATCH_SIZE_IMAGES="${BATCH_SIZE_IMAGES:-${BATCH_SIZE}}"
BATCH_SIZE_PDFS="${BATCH_SIZE_PDFS:-${BATCH_SIZE}}"
FIND_BATCH_SIZE="${FIND_BATCH_SIZE:-50}"
PAUSE_MS="${PAUSE_MS:-1000}"
MIN_AGE_DAYS="${MIN_AGE_DAYS:-90}"
MARKDOWN_DIR="${MARKDOWN_DIR:-/usr/local/akwww_root/www/wp-content/uploads/networks/1/sites/2/worddown-export}"
RUN_DIR="${RUN_DIR:-/usr/local/akwww_root/tmp/unused-images}"
DRY_RUN="${DRY_RUN:-0}"

SECONDS=0
format_duration() {
    local total="$1"
    if (( total >= 3600 )); then
        printf '%dh %dm %ds' $((total / 3600)) $((total % 3600 / 60)) $((total % 60))
    elif (( total >= 60 )); then
        printf '%dm %ds' $((total / 60)) $((total % 60))
    else
        printf '%ds' "${total}"
    fi
}

log() { printf '[%s] %s\n' "$(date '+%Y-%m-%d %H:%M:%S')" "$*"; }

if [[ ! -d "${MARKDOWN_DIR}" ]]; then
    log "Markdown directory not found: ${MARKDOWN_DIR}" >&2
    exit 2
fi
for size in "${BATCH_SIZE_IMAGES}" "${BATCH_SIZE_PDFS}" "${FIND_BATCH_SIZE}"; do
    if ! [[ "${size}" =~ ^[0-9]+$ ]] || (( size < 1 )); then
        log "BATCH_SIZE_IMAGES, BATCH_SIZE_PDFS and FIND_BATCH_SIZE must be positive integers (got: '${size}')." >&2
        exit 2
    fi
done
if ! [[ "${MIN_AGE_DAYS}" =~ ^[0-9]+$ ]]; then
    log "MIN_AGE_DAYS must be a non-negative integer (got: '${MIN_AGE_DAYS}')." >&2
    exit 2
fi
for type in ${MEDIA_TYPES}; do
    if [[ "${type}" != "images" && "${type}" != "pdfs" ]]; then
        log "Unknown media type in MEDIA_TYPES: ${type} (use images and/or pdfs)." >&2
        exit 2
    fi
done

mkdir -p "${RUN_DIR}"

# Stop overlapping cron runs (the plugin also has its own scan lock).
if command -v flock >/dev/null 2>&1; then
    exec 9>"${RUN_DIR}/.lock"
    if ! flock -n 9; then
        log "Another run is in progress, exiting."
        exit 0
    fi
fi

wp() {
    local global_args=(--url="${WP_URL}")
    [[ -n "${WP_PATH}" ]] && global_args+=(--path="${WP_PATH}")
    # shellcheck disable=SC2206 # intentional word splitting of extra args
    [[ -n "${WP_EXTRA_ARGS}" ]] && global_args+=(${WP_EXTRA_ARGS})
    "${WP_BIN}" "${global_args[@]}" "$@"
}

FIND_ARGS=(--min-age-days="${MIN_AGE_DAYS}")
if [[ "${DRY_RUN}" == "1" ]]; then
    # A dry run must not use up the batch: leave scan history untouched.
    FIND_ARGS+=(--skip-history)
fi

# Usage: scan_type <images|pdfs> <batch-size>
# Returns non-zero on the first failing step, so mark never follows a failed
# find/check and never reads a stale report.
scan_type() {
    local type="$1" batch="$2"
    local singular="${type%s}"
    local raw="${RUN_DIR}/${singular}-report-raw.json"
    local final="${RUN_DIR}/${singular}-report.json"
    local mark_args=()
    [[ "${DRY_RUN}" == "1" ]] && mark_args+=(--dry-run)

    rm -f "${raw}" "${final}"

    log "[${type}] Step 1/3: find-unused-${type} (limit ${batch})"
    wp alingsas "find-unused-${type}" \
        --limit="${batch}" \
        --batch-size="${FIND_BATCH_SIZE}" \
        --pause-ms="${PAUSE_MS}" \
        --output="${raw}" \
        "${FIND_ARGS[@]+"${FIND_ARGS[@]}"}" || return 1

    log "[${type}] Step 2/3: check-unused-${type}"
    wp alingsas "check-unused-${type}" \
        --report="${raw}" \
        --markdown-dir="${MARKDOWN_DIR}" \
        --output="${final}" || return 1

    log "[${type}] Step 3/3: mark-unused-${type}"
    wp alingsas "mark-unused-${type}" "${final}" \
        "${mark_args[@]+"${mark_args[@]}"}" || return 1
}

failed=()
for type in ${MEDIA_TYPES}; do
    case "${type}" in
        images) batch="${BATCH_SIZE_IMAGES}" ;;
        pdfs)   batch="${BATCH_SIZE_PDFS}" ;;
    esac
    type_started="${SECONDS}"
    if scan_type "${type}" "${batch}"; then
        log "[${type}] Finished in $(format_duration $((SECONDS - type_started)))"
    else
        log "[${type}] FAILED after $(format_duration $((SECONDS - type_started)))"
        failed+=("${type}")
    fi
done

if (( ${#failed[@]} > 0 )); then
    log "Done with errors in: ${failed[*]}. Total time: $(format_duration "${SECONDS}")" >&2
    exit 1
fi
log "Done. Total time: $(format_duration "${SECONDS}")"
