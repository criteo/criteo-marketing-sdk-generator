#!/usr/bin/env bash
set -x

git_clone() {
  git clone --depth 1 https://${GH_TOKEN}@github.com/$1.git
}

setup_git() {
  git config user.email ${USER_EMAIL}
  git config user.name "Travis CI"
}

git_add_files() {
  git add "**/*.py" "**/*.md"
}

git_commit() {
  git commit -am "Automatic update of SDK - $TRAVIS_BUILD_NUMBER"
}

git_push() {
  git push --quiet
}


BUILD_DIR=${HOME}/build

cd ${BUILD_DIR}/criteo
REPO=criteo/criteo-python-marketing-sdk
git_clone ${REPO}
cd ${BUILD_DIR}/${REPO}

cp -R ${TRAVIS_BUILD_DIR}/dist/** .

# git diff, ignore version's modifications
modification_count=$(git diff -U0 | grep '^[+-]' | grep -Ev '^(--- a/|\+\+\+ b/)' | grep -Ev 'version|VERSION|Version|user_agent' | wc -l)

if [[ ${modification_count} != 0 ]]; then
    setup_git
    git_add_files
    git_commit
    git_push
else
    echo No push to Github. Modifications:
    git diff -U0
fi