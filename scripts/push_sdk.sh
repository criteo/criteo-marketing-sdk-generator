#!/usr/bin/env bash
set -ex

SCRIPT_ROOT="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )"

if [[ $1 != "" ]]; then
    BUILD_NUMBER=$1
else
    echo "Usage: $0 BUILD_NUMBER"
    exit 1
fi

git_clone() {
  git clone --depth 1 https://${GH_TOKEN}@github.com/$1.git
}

setup_git() {
  git config user.email ${USER_EMAIL}
  git config user.name "Travis CI"
}

git_add_files() {
  case "${1}" in
    java)
      files_to_add="**/*.java **/*.md"
      files_to_add="${files_to_add} pom.xml *.gradle gradlew gradlew.bat gradle/"
      files_to_add="${files_to_add} LICENSE.txt .gitignore .openapi-generator/"
      ;;
    python)
      files_to_add="**/*.py **/*.md"
      ;;
    *)
      exit 1
      ;;
  esac

  git add ${files_to_add}
}

git_commit_and_tag() {
  version=$1
  if [[ ${version} == "" ]]; then
    echo "version is not defined"
    exit 1
  fi
  git commit -am "Automatic update of SDK - ${version}" && git tag ${version}
}

git_push() {
  if [[ ${USER} == "travis" ]]; then
    git push --quiet && git push --tags --quiet
  else
    echo "Only user travis should be able to push."
  fi
}

process() {
  language=$1
  cd ${BUILD_DIR}/criteo
  REPO=criteo/criteo-${language}-marketing-sdk
  git_clone ${REPO}
  cd ${BUILD_DIR}/${REPO}

  cp -R ${SCRIPT_ROOT}/../generated-clients/${language}/ .

  # add files before doing the diff
  git_add_files ${language}

  # git diff, ignore version's modifications
  modification_count=$(git diff -U0 --staged | grep '^[+-]' | grep -Ev '^(--- a/|\+\+\+ b/)' | grep -Ev 'version|VERSION|Version|user_agent' | wc -l)
  next_version=$(cat "/tmp/travis_${BUILD_NUMBER}-build_sdk-${language}.version")

  if [[ ${modification_count} != 0 && ${next_version} != "" ]]; then
      setup_git
      git_commit_and_tag ${next_version}
      git_push
  else
      echo No push to Github. Modifications:
      git diff -U0
  fi
}

BUILD_DIR=${HOME}/build

LANGUAGES=("python" "java")

for language in "${LANGUAGES[@]}"
do
  echo "Starting upload for - ${language}"
  process ${language}
done
