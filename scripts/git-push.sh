#!/usr/bin/env bash
# =============================================================================
# git-push.sh — Otomasi commit + push ke GitHub untuk QHSSE App
# =============================================================================
# Usage:
#   ./scripts/git-push.sh                          # auto commit semua + push ke develop
#   ./scripts/git-push.sh "pesan commit"           # commit dengan pesan custom
#   ./scripts/git-push.sh "pesan" main             # push ke branch tertentu
#   ./scripts/git-push.sh "pesan" develop feat/xyz # push ke feature branch baru
# =============================================================================

set -euo pipefail

REPO_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$REPO_DIR"

# --- Argumen ---
COMMIT_MSG="${1:-"chore: auto-commit $(date '+%Y-%m-%d %H:%M')"}"
TARGET_BRANCH="${2:-develop}"
FEATURE_BRANCH="${3:-}"

# --- Warna terminal ---
RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'
BLUE='\033[0;34m'; CYAN='\033[0;36m'; NC='\033[0m'

echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${CYAN}  QHSSE App — Auto Git Push                        ${NC}"
echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"

# --- Cek ada perubahan ---
if git diff --quiet && git diff --staged --quiet && [ -z "$(git ls-files --others --exclude-standard)" ]; then
    echo -e "${YELLOW}⚠  Tidak ada perubahan untuk di-commit.${NC}"
    exit 0
fi

# --- Status singkat ---
echo -e "${BLUE}📋 Perubahan yang akan di-commit:${NC}"
git status --short | head -20
CHANGED=$(git status --short | wc -l)
echo -e "${BLUE}   Total: ${CHANGED} file(s)${NC}\n"

# --- Feature branch jika diminta ---
if [ -n "$FEATURE_BRANCH" ]; then
    echo -e "${BLUE}🌿 Membuat branch: ${FEATURE_BRANCH}${NC}"
    git checkout -b "$FEATURE_BRANCH" 2>/dev/null || git checkout "$FEATURE_BRANCH"
fi

# --- Stage semua ---
git add -A

# --- Commit ---
echo -e "${BLUE}💾 Commit: ${COMMIT_MSG}${NC}"
git commit -m "$COMMIT_MSG"

# --- Push ---
CURRENT_BRANCH=$(git branch --show-current)
echo -e "${BLUE}🚀 Push ke origin/${CURRENT_BRANCH}...${NC}"
git push -u origin "$CURRENT_BRANCH" 2>&1

echo -e "\n${GREEN}✅ Berhasil push ke https://github.com/topiksarip/qhsse-app/tree/${CURRENT_BRANCH}${NC}"
echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
