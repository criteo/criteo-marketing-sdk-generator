#!/usr/bin/env bash

arrays_are_equal() {
    if [[ ${#version_only[@]} == ${#changed_files[@]} ]]; then
        for i in "${changed_files[@]}"
        do
           if [[ -z ${version_only["$i"]} ]]; then
                exit 1
           fi
        done
        exit 0
    else
        exit 1
    fi
}

git_clone() {
  git clone --depth 1 https://${GH_TOKEN}@github.com/$1.git
}

setup_git() {
  git config user.email ${USER_EMAIL}
  git config user.name "Travis CI"
}

git_commit() {
  git commit --message "Automatic update of SDK - $TRAVIS_BUILD_NUMBER"
}

git_push() {
  git push --quiet
}


BUILD_DIR=${HOME}/build

cd ${BUILD_DIR}/criteo
REPO=criteo/criteo-python-marketing-sdk
git_clone ${REPO}
cd ${BUILD_DIR}/${REPO}


cp -R ${TRAVIS_BUILD_DIR}/dist/ .


# These files are always modified when the version is changed
declare -A version_only=(["README.md"]="README.md"
                         ["criteo_marketing/__init__.py"]="criteo_marketing/__init__.py"
                         ["criteo_marketing/api_client.py"]="criteo_marketing/api_client.py"
                         ["criteo_marketing/configuration.py"]="criteo_marketing/configuration.py"
                         ["setup.py"]="setup.py")

# git diff
mapfile -t changed_files < <( git diff --name-only ':!*.gitignore' )

if [[ ${#changed_files[@]} == 0 ]]; then
    should_push=0
else
    $(arrays_are_equal)
    should_push=$?
fi


if [[ ${should_push} != 0 ]]; then
    setup_git
    git_push
else
    echo No push to Github
fi