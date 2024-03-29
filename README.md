# This project is deprecated
We've built a new set of SDKs to help you use our [Criteo's API](https://developers.criteo.com/).

You can find the new generator project here : https://github.com/criteo/criteo-api-sdk-generator

# Criteo Marketing API - Clients
[![Build Status](https://travis-ci.com/criteo/criteo-marketing-sdk-generator.svg?branch=master)](https://travis-ci.com/criteo/criteo-marketing-sdk-generator)

This project generates code for the [Java](https://github.com/criteo/criteo-java-marketing-sdk) and [Python](https://github.com/criteo/criteo-python-marketing-sdk) Marketing client libraries.
## Generate the clients
To generate the Python code, run:

```bash 
./gradlew :build-python:generateClient -Dorg.gradle.project.buildNumber=$TRAVIS_BUILD_NUMBER
```
The generated code can be found under `generated-clients/python` folder.

To generate the Java code, run:

```bash 
./gradlew :build-java:generateClient -Dorg.gradle.project.buildNumber=$TRAVIS_BUILD_NUMBER
```

The generated code can be found under `generated-clients/java` folder.

## Modify templates
You can modify the generated code by changing the templates.
For example, the authentication token auto refresh feature is implemented in 
`build-python/resources/templates/rest.mustache`.

If a template is missing, you can copy it from the original repository [Python templates](https://github.com/OpenAPITools/openapi-generator/tree/master/modules/openapi-generator/src/main/resources/python).

## Build Process
The generation of the clients is wrapped in a [buid.gradle](build.gradle).
The specific options for each language are defined in other build.gradle files ([python](build-python/build.gradle), [java](build-java/build.gradle)).

This script uses [https://api.criteo.com](https://api.criteo.com) public API.

A clean step has been added to the build process in order to delete the folder of previous generated code.
Otherwise some changes will not be applied by openapi-generator.

## Disclaimer

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. 
IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
