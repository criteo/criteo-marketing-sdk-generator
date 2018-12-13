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
  git clone --depth 1 https://${GH_TOKEN}@github.com/criteo/$1.git
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

cd ${TRAVIS_BUILD_DIR}
REPO=criteo-python-marketing-sdk
git_clone ${REPO}
cd ${TRAVIS_BUILD_DIR}/${REPO}

SLUG=(${TRAVIS_REPO_SLUG//// })
CURRENT_REPO_NAME=${SLUG[1]}
cp -R ${TRAVIS_BUILD_DIR}/${CURRENT_REPO_NAME}/dist/ .


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


echo SHOULD PUSH = ${should_push}
if [[ ${should_push} != 0 ]]; then
    setup_git
    git_push
else
    echo No push to Github
fi