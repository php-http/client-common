# Change Log

## 1.3.0 - Unreleased

- Add HttpClientPool client to leverage load balancing and fallback mechanism [see the documentation](http://docs.php-http.org/en/latest/components/client-common.html) for more details

## 1.2.1 - 2016-07-26

### Changed

- AddHostPlugin also sets the port if specified


## 1.2.0 - 2016-07-14

### Added

- Suggest separate plugins in composer.json
- Introduced `debug_plugins` option for `PluginClient`


## 1.1.0 - 2016-05-04

### Added

- Add a flexible http client providing both contract, and only emulating what's necessary
- HTTP Client Router: route requests to underlying clients
- Plugin client and core plugins moved here from `php-http/plugins`

### Deprecated

- Extending client classes, they will be made final in version 2.0


## 1.0.0 - 2016-01-27

### Changed

- Remove useless interface in BatchException


## 0.2.0 - 2016-01-12

### Changed

- Updated package files
- Updated HTTPlug to RC1


## 0.1.1 - 2015-12-26

### Added

- Emulated clients


## 0.1.0 - 2015-12-25

### Added

- Batch client from utils
- Methods client from utils
- Emulators and decorators from client-tools
