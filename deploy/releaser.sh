#!/usr/bin/env bash

# Safe check - skips relese commit generation when already tagged commit
if [[ $(git name-rev --name-only --tags HEAD) = "v$VERSION" ]]; then
  echo "Already tagged or no new commits introduced. Skipping.."
  exit
fi

# Guess new version number
RECOMMENDED_BUMP=$(conventional-recommended-bump -p angular)

# Split version by dots
IFS='.' read -r -a V <<< "$VERSION"

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
NEW_VERSION=${VERSION//${OLD_VERSION_SEM}/${NEW_VERSION_SEM}}

echo "Releasing version: ${NEW_VERSION}"

# Tag to update changelog with new version included
git tag "v${NEW_VERSION}"
conventional-changelog -p angular -i CHANGELOG.md -s -r 2
git tag -d "v${NEW_VERSION}"

# Create release commit and tag
git add CHANGELOG.md
git commit -m "chore(release): v${NEW_VERSION} :tada:
$(conventional-changelog)
"
git tag "v${NEW_VERSION}"

# Push commit and tag
git remote add authorized "https://travis:${GH_TOKEN}@github.com/k911/swoole-bundle.git"
git push authorized HEAD:master --tags
git push authorized HEAD:develop

# Make github release
conventional-github-releaser -p angular -t "${GH_TOKEN}"
