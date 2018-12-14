# Criteo Marketing API - Python Client
[![Build Status](https://travis-ci.com/criteo/criteo-marketing-sdk-generator.svg?branch=master)](https://travis-ci.com/criteo/criteo-marketing-sdk-generator)
## Generate the client
To generate the code under, run:

```bash 
./gradlew build
```

The generated code can be found under `dist` folder.

## Modify templates
You can modify the generated code by changing the templates.
For example, the authentication token auto refresh feature is implemented in 
`templates/PythonCriteo/rest.mustache`.

If a template is missing, you can copy it from the original repository [Python templates](https://github.com/OpenAPITools/openapi-generator/tree/master/modules/openapi-generator/src/main/resources/python).

## Build Process
The generation of the client is wrapped in a [buid.gradle](build.gradle) where different options for the python generator are defined.

This script uses [https://api.criteo.com](https://api.criteo.com) public API.

A clean step has been added to the build process in order to delete the existing folder dist.
Otherwise some changes will not be applied by openapi-generator.

## Disclaimer

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.