## axisJS and Axis-WP Roadmap

This is a very rough roadmap for axisJS and Axis for WordPress. Features and
bug-fixes will be added in the order they're listed.

Versioning is in [SemVer](http://www.semver.org), meaning milestone releases are
not backwards compatible, major releases involve significant changes to the API
and minor releases that are backwards compatible bug-fix releases.
See [semver.org](http://semver.org) for full details.

### axisJS

#### 0.1.1

Bug-fix release intended to target a variety of issues.

- [x] fix excess whitespace beneath charts with no title » times/axisJS#8
- [x] add ability to do stacked bar charts » times/axisJS#7

#### 0.1.2

Maintenance release intended to facilitate future development.

- [ ] fix PNG output in Safari » times/axisJS#5
- [ ] fix chart legend centring bug » times/axisJS#9
- [ ] abstract charting library functionalities into a provider
- [ ] abstract styling configuration into a provider
- [ ] abstract output formats into a provider
- [ ] abstract input formats into a provider
- [ ] add unit tests for all of the above

### Axis for WordPress

#### 0.1.1

Bug-fix release intended to fix a number of display-related issues, add unit
tests.

- [x] fix chart centring bug » times/axisJS#1
- [x] create filter for front-end that replaces data-uri PNG with bonafide C3 chart

#### 0.1.2

Feature release, mainly to add interactive charts.

- [ ] add unit tests
- [ ] add new ways to import data
