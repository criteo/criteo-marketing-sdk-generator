#!/usr/bin/env bash
set -ex

# Get the latest patch number of the latest version for a given API_VERSION,
# build the SDK by incrementing the patch number.
# For example, the latest patch number of API_VERSION=1.0 is "v1.0.12",
# the next build is therefore "v1.0.13".

SCRIPT_ROOT="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )"
GRADLEW="${SCRIPT_ROOT}/../gradlew"
API_VERSION='1.0'

if [[ $1 != "" ]]; then
    BUILD_NUMBER=$1
else
    echo "Usage: $0 BUILD_NUMBER"
    exit 1
fi

get_next_patch_number() {
    latestPatch=$(git ls-remote --tags --refs $1 \
                    | awk '{print $2}' \
                    | grep -E "^refs\/tags\/v${API_VERSION}.[0-9]+$" \
                    | sed -E "s/^refs\/tags\/v${API_VERSION}.([0-9]+)$/\1/g" \
                    | sort -r | head -n1)
    if [[ ${latestPatch} == "" ]]; then
        echo "No tag started with 'v${API_VERSION}.' was found in '$1'."
        NEXT_PATCH_NUMBER=0
    else
        NEXT_PATCH_NUMBER=$((latestPatch + 1))
    fi
}

LANGUAGES=('python' 'java')

for language in "${LANGUAGES[@]}"
do
    echo "Building SDK - ${language}"

    get_next_patch_number "https://github.com/criteo/criteo-${language}-marketing-sdk"
    ${GRADLEW} :build-${language}:generateClient -Dorg.gradle.project.buildNumber=${NEXT_PATCH_NUMBER}

    # write version into a file
    nextVersion="v${API_VERSION}.${NEXT_PATCH_NUMBER}"
    VERSION_FILE="/tmp/travis_${BUILD_NUMBER}-build_sdk-${language}.version"
    echo "${nextVersion}" > ${VERSION_FILE}
done
