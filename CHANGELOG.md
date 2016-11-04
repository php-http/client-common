# Change Log


## 1.4.0 - 2016-11-04

### Added

- Add Path plugin
- Base URI plugin that combines Add Host and Add Path plugins


## 1.3.0 - 2016-10-16

### Changed

- Fix Emulated Trait to use Http based promise which respect the HttpAsyncClient interface
- Require Httplug 1.1 where we use HTTP specific promises.
- RedirectPlugin: use the full URL instead of the URI to properly keep track of redirects
- Add AddPathPlugin for API URLs with base path
- Add BaseUriPlugin that combines AddHostPlugin and AddPathPlugin


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
