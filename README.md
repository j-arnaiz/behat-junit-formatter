## Installation

### Prerequisites

This extension requires:

* PHP 5.3.x or higher
* Behat 3.x or higher

#### Install with composer:

```bash
$ composer require --dev dizzy7/behat-junit-formatter
```

## Basic usage

Activate the extension by specifying its class in your `behat.yml`:

```json
# behat.yml
default:
    suites:
    ...

    extensions:
        dizzy7\JUnitFormatter\JUnitFormatterExtension:
            filename: report.xml
            outputDir: %paths.base%/build/tests
    ...
```

## Configuration

* `filename` - filename (not used if realtime flag is on)
* `outputDir` - dir to be created filename

you also could use BEHAT_JUNIT_FILENAME and BEHAT_JUNIT_OUTPUTDIR env variables

## Issue Submission

Feel free to [Create a new issue](https://github.com/dizzy7/behat-junit-formatter/issues/new).

## Thanks to

Thanks to emuse html extension that inspired me to created this one.
