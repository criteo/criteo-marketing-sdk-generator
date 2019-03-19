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

  if [[ $1 = "java" ]]; then
    extension="java"
  elif [[ $1 = "python" ]]; then
    extension="py"
  else
    exit -1
  fi

  git add "**/*."${extension} "**/*.md"
}

git_commit() {
  git commit -am "Automatic update of SDK - $TRAVIS_BUILD_NUMBER"
}

git_push() {
  git push --quiet
}

process() {
  cd ${BUILD_DIR}/criteo
  REPO=criteo/criteo-$1-marketing-sdk
  git_clone ${REPO}
  cd ${BUILD_DIR}/${REPO}

  cp -R ${TRAVIS_BUILD_DIR}/generated-clients/$1/** .

  # add files before doing the diff
  git_add_files $1

  # git diff, ignore version's modifications
  modification_count=$(git diff -U0 --staged | grep '^[+-]' | grep -Ev '^(--- a/|\+\+\+ b/)' | grep -Ev 'version|VERSION|Version|user_agent' | wc -l)

  if [[ ${modification_count} != 0 ]]; then
      setup_git
      git_commit
      git_push
  else
      echo No push to Github. Modifications:
      git diff -U0
  fi
}

LANGUAGES=("python" "java")
BUILD_DIR=${HOME}/build

for var in "${LANGUAGES[@]}"
do
  echo "Starting upload for - ${var}"
  process $var
done