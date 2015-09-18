## Installation

### Prerequisites

This extension requires:

* PHP 5.3.x or higher
* Behat 3.x or higher

#### Install with composer:

```bash
$ composer require --dev jarnaiz/behat-junit-formatter
```

## Basic usage

Activate the extension by specifying its class in your `behat.yml`:

```json
# behat.yml
default:
    suites:
    ...

    extensions:
        jarnaiz\JUnitFormatter\JUnitFormatterExtension:
            filename: report.xml
            outputDir: %paths.base%/build/tests
    ...
```

Be sure to call behat with the formatter:

behat -f junit

## Configuration

* `filename` - filename
* `outputDir` - dir to be created filename

you also could use JARNAIZ_JUNIT_FILENAME and JARNAIZ_JUNIT_OUTPUTDIR env variables

## Issue Submission

Feel free to [Create a new issue](https://github.com/j-arnaiz/behat-junit-formatter/issues/new).

## Thanks to

Thanks to emuse html extension that inspired me to created this one.
