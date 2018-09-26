#!/usr/bin/env sh

# Safe check - skips relese commit generation when already tagged commit
if [[ $(git name-rev --name-only --tags HEAD) = "v$VERSION" ]]; then
  echo "Already tagged or no new commits introduced. Skipping.."
  exit
fi

# Guess new version number
RECOMMENDED_BUMP=$(conventional-recommended-bump -p angular)

# Split version by dots
V=( ${VERSION//./ } )

# Ignore postfix like "-dev"
((V[2]++))
((V[2]--))
OLD_VERSION_SEM="${V[0]}.${V[1]}.${V[2]}"

# Increment either major, minor or patch number
if [[ "${RECOMMENDED_BUMP}" = "major" ]]; then ((V[0]++));
elif [[ "${RECOMMENDED_BUMP}" = "minor" ]]; then ((V[1]++));
elif [[ "${RECOMMENDED_BUMP}" = "patch" ]]; then ((V[2]++));
else
    echo "Could not bump version"
    exit
fi

NEW_VERSION_SEM="${V[0]}.${V[1]}.${V[2]}"
NEW_VERSION=${VERSION//${OLD_VERSION_SEM}/${NEW_VERSION_SEM}}

echo "Releasing version: ${NEW_VERSION}"

# Tag to regenerate changelog with new version included
git tag "v${NEW_VERSION}"
conventional-changelog -p angular -i CHANGELOG.md -s -r 0
git tag -d "v${NEW_VERSION}"

# Update version in composer.json
sed -e "s/\"version\": \"v${VERSION}\",/\"version\": \"v${NEW_VERSION}\",/g" composer.json > composer.json.tmp && mv composer.json.tmp composer.json

# Create release commit and tag
git add CHANGELOG.md composer.json
git commit -m "chore(release): v${NEW_VERSION} :tada:
$(conventional-changelog)
"
git tag "v${NEW_VERSION}"

# Push commit and tag
git remote add authorized https://travis:${GH_TOKEN}@github.com/k911/swoole-bundle.git
git push authorized HEAD:master --tags
git push authorized HEAD:develop

# Make github release
conventional-github-releaser -p angular -t ${GH_TOKEN}
