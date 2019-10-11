#!/usr/bin/env bash

if [[ "" = "$CURRENT_VERSION" ]]; then
  CURRENT_VERSION="$(git describe --abbrev=0 --tags | sed -E 's/v(.*)/\1/')"
fi

# Safe check - skips relese commit generation when already tagged commit
if [[ $(git name-rev --name-only --tags HEAD) = "v$CURRENT_VERSION" ]]; then
    echo "Already tagged or no new commits introduced. Skipping.."
    exit
fi

DRY_RUN="${DRY_RUN:-0}"

if [[ "1" = "${DRY_RUN}" ]]; then
    echo "Dry running.."
fi

# Guess new version number
RECOMMENDED_BUMP=$(conventional-recommended-bump -p angular)

# Split version by dots
IFS='.' read -r -a V <<< "$CURRENT_VERSION"

# Ignore postfix like "-dev"
((V[2]++))
((V[2]--))
OLD_VERSION_SEM="${V[0]}.${V[1]}.${V[2]}"

# When version is 0.x.x it is allowed to make braking changes on minor version
if [[ "0" = "${V[0]}" ]] && [[ "${RECOMMENDED_BUMP}" = "major" ]]; then
    RECOMMENDED_BUMP="minor";
fi;

# Increment semantic version numbers major.minor.patch
if [[ "${RECOMMENDED_BUMP}" = "major" ]]; then
    ((V[0]++));
    V[1]=0;
    V[2]=0;
elif [[ "${RECOMMENDED_BUMP}" = "minor" ]]; then
    ((V[1]++));
    V[2]=0;
elif [[ "${RECOMMENDED_BUMP}" = "patch" ]]; then ((V[2]++));
else
    echo "Could not bump version"
    exit
fi

NEW_VERSION_SEM="${V[0]}.${V[1]}.${V[2]}"
NEW_VERSION=${CURRENT_VERSION//${OLD_VERSION_SEM}/${NEW_VERSION_SEM}}

echo "Releasing version: ${NEW_VERSION}"

RELEASE_TAG="v${NEW_VERSION}"
GH_COMMITER_NAME="${GH_COMMITER_NAME:-k911}"
GH_COMMITER_EMAIL="${GH_COMMITER_EMAIL:-konradobal@gmail.com}"
GH_REPOSITORY="${GH_REPOSITORY:-k911/swoole-bundle}"

# Configure git
git config user.name "${GH_COMMITER_NAME}"
git config user.email "${GH_COMMITER_EMAIL}"

# Save release notes
git tag "${RELEASE_TAG}" > /dev/null 2>&1
GH_RELEASE_NOTES_HEADER="$(conventional-changelog -p angular -r 2 | awk 'NR > 4 { print }' | head -n 1)"
git tag -d "${RELEASE_TAG}" > /dev/null 2>&1
GH_RELEASE_NOTES="$(conventional-changelog -p angular | awk 'NR > 3 { print }')"
if [ "" = "$(echo -n "$GH_RELEASE_NOTES" | xargs)" ]; then
    GH_RELEASE_NOTES="### Miscellaneous

* Minor fixes"
fi

# Save changelog
CHANGELOG="$GH_RELEASE_NOTES_HEADER

[Full changelog](https://github.com/${GH_REPOSITORY}/compare/v${CURRENT_VERSION}...v${NEW_VERSION})

$GH_RELEASE_NOTES
"
NEXT_LINES="10"
LINES="$(wc -l <<< "$CHANGELOG")"
LINES=$((LINES+NEXT_LINES))

# Update CHANGELOG.md
if [ "0" = "$DRY_RUN" ]; then
    echo "$CHANGELOG" >> CHANGELOG.md
else
    echo "Changelog file: (first $LINES lines)"
    echo "⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽"
    echo "$CHANGELOG"
    head -n "$NEXT_LINES" < CHANGELOG.md
    echo "⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺"
fi

# Create release commit and tag it
COMMIT_MESSAGE="chore(release): ${RELEASE_TAG} :tada:
$(conventional-changelog | awk 'NR > 1 { print }')
"

if [ "0" = "$DRY_RUN" ]; then
    git add CHANGELOG.md
    git commit -m "${COMMIT_MESSAGE}"
    git tag "${RELEASE_TAG}"
else
    echo "Commit message:"
    echo "⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽"
    echo "${COMMIT_MESSAGE}"
    echo "⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺"
fi

# Push commit and tag
if [ "0" = "$DRY_RUN" ]; then
    GH_TOKEN="${GH_TOKEN:?"Provide \"GH_TOKEN\" variable with GitHub Personal Access Token"}"
    git remote add authorized "https://${GH_COMMITER_NAME}:${GH_TOKEN}@github.com/${GH_REPOSITORY}.git"
    REVS="$RELEASE_TAG HEAD:master HEAD:develop"
    for REV in $REVS
    do
        git push authorized "$REV"
    done
    git remote remove authorized
fi

# Make github release
GH_RELEASE_DRAFT="${GH_RELEASE_DRAFT:-false}"
GH_RELEASE_PRERELEASE="${GH_RELEASE_PRERELEASE:-false}"
GH_RELEASE_DESCRIPTION="## Changelog

[Full changelog](https://github.com/${GH_REPOSITORY}/compare/v${CURRENT_VERSION}...v${NEW_VERSION})

${GH_RELEASE_NOTES}

## Installation

\`\`\`sh
composer require ${GH_REPOSITORY} ^${NEW_VERSION}
\`\`\`
"
GH_RELEASE_DESCRIPTION_ESCAPED="${GH_RELEASE_DESCRIPTION//\"/\\\"}"
GH_RELEASE_DESCRIPTION_ESCAPED="${GH_RELEASE_DESCRIPTION_ESCAPED//$'\n'/\\n}"
GH_RELEASE_REQUEST_BODY="{
    \"tag_name\": \"${RELEASE_TAG}\",
    \"target_commitish\": \"master\",
    \"name\": \"${RELEASE_TAG}\",
    \"body\": \"${GH_RELEASE_DESCRIPTION_ESCAPED}\",
    \"draft\": ${GH_RELEASE_DRAFT},
    \"prerelease\": ${GH_RELEASE_PRERELEASE}
}"

if [ "0" = "$DRY_RUN" ]; then
    curl -u "${GH_COMMITER_NAME}:${GH_TOKEN}" -X POST "https://api.github.com/repos/${GH_REPOSITORY}/releases" \
        -H "Content-Type: application/json" \
        --data "${GH_RELEASE_REQUEST_BODY}"
else
    echo "Release description:"
    echo "⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽"
    echo "${GH_RELEASE_DESCRIPTION}"
    echo "⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺"
    echo "Release request body:"
    echo "⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽⎽"
    echo "${GH_RELEASE_REQUEST_BODY}"
    echo "⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺⎺"
fi
