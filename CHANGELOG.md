# Change Log
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]

## 2.0.3 – 2023-10-17

Maintenance update

### Changed

- Add Nextcloud 28 support
- Update npm packages
- Localization (l10n) updates

### Fixed

- Fixed bad character encoding displayed in old dashboard widget component (https://github.com/nextcloud/integration_mastodon/issues/53)
- Other minor fixes

## 2.0.2 – 2023-05-10

### Changed

- add NC 27 support
- update npm pkgs

## 2.0.1 – 2023-02-27
### Changed
- reduce log output
- update npm pkgs
- polish search providers

## 2.0.0 – 2023-01-30
### Added
- search providers
- reference providers associated with the search

## 1.0.5 – 2023-01-29
### Changed
- use https by default
  [#40](https://github.com/nextcloud/integration_mastodon/issues/40) @ianrenton

### Fixed
- trim Mastodon URL (slashes, spaces etc...)
  [#1](https://github.com/nextcloud/integration_mastodon/issues/1) @juliushaertl
- open posts in user's instance instead of author's one
  [#41](https://github.com/nextcloud/integration_mastodon/issues/41) @BenDundon
- fix reblog content

## 1.0.4 – 2023-01-29
### Changed
- update npm pkgs
- adjust to @nextcloud/vue 7.x
- navigation link opens Mastodon in a new tab

### Fixed
- safer network error handling

## 1.0.3 – 2022-09-03
### Added
- allow connection via oauth from the dashboard
- admin settings to set the default mastodon url
- optionally use a popup to authenticate

### Changed
- bump js libs
- use material icons
- use node 16, adjust to new eslint config
- get ready for NC 25, remove svg api, etc...

## 1.0.1 – 2021-06-28
### Changed
- stop polling widget content when document is hidden
- bump js libs
- get rid of all deprecated stuff
- bump min NC version to 22
- cleanup backend code

## 1.0.0 – 2021-03-19
### Changed
- bump js libs

## 0.0.13 – 2021-02-16
### Changed
- app certificate

## 0.0.12 – 2021-02-12
### Changed
- bump js libs
- bump max NC version

### Fixed
- import nc dialog style
- use latest axios

## 0.0.11 – 2020-12-10
### Changed
- bump js libs

### Fixed
- avoid crash when accessibility app is not installed

## 0.0.10 – 2020-11-08
### Added
- optional navigation link to mastodon instance

### Changed
- bump js libs

### Fixed
- make app icon dimensions square
## 0.0.9 – 2020-10-22
### Added
- automatic release

### Fixed
- finally use browser side redirect URI

## 0.0.8 – 2020-10-19
### Fixed
- mismatch redirect URL between server side and browser side (possibly because of overwrite.cli.url)

## 0.0.7 – 2020-10-18
### Changed
- more logs on oauth app declaration error

## 0.0.6 – 2020-10-18
### Changed
- improve avatar hostname check
- use webpack 5 and style lint
- more error logs on OAuth problems

## 0.0.5 – 2020-10-12
### Changed
- restrict avatar URL proxying

### Fixed
- various small design issues

## 0.0.4 – 2020-10-02
### Added
- lots of translations

### Changed
- improve items content (handle reblog, remove @mentions...)

## 0.0.3 – 2020-09-21
### Added
* second widget displaying home timeline

### Changed
* improve authentication design
* improve widgets empty content

## 0.0.2 – 2020-09-02
### Fixed
* image loading with new webpack config

## 0.0.1 – 2020-09-02
### Added
* the app
