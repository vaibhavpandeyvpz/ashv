# vaibhavpandeyvpz/ashv
The lean [PSR-7](https://github.com/php-fig/http-message)/[PSR-15](https://github.com/php-fig/fig-standards/blob/master/proposed/http-middleware/middleware.md) micro-framework, uses [vaibhavpandeyvpz/vidyut](https://github.com/vaibhavpandeyvpz/vidyut) underneath.

> Ashv: `अश्‍व` (Horse)

[![Build status][build-status-image]][build-status-url]
[![Code Coverage][code-coverage-image]][code-coverage-url]
[![Latest Version][latest-version-image]][latest-version-url]
[![Downloads][downloads-image]][downloads-url]
[![PHP Version][php-version-image]][php-version-url]
[![License][license-image]][license-url]

[![SensioLabsInsight][insights-image]][insights-url]

Preview (झलक)
-----
```php
<?php

$app = new Ashv\Application();

$app->get('/', function ($response) {
    $response->getBody()->write('Hello world!');
    return $response;
});

$app->get('/hello/{name}', function ($name, $response) {
    $response->getBody()->write("Hello {$name}!");
    return $response;
});

/**
 * @desc Life is this simple.
 */
$app->handle();
```

Documentation
-------
To view installation and usage instructions, visit this [Wiki](https://github.com/vaibhavpandeyvpz/ashv/wiki).

License
-------
See [LICENSE.md][license-url] file.

[build-status-image]: https://img.shields.io/travis/vaibhavpandeyvpz/ashv.svg?style=flat-square
[build-status-url]: https://travis-ci.org/vaibhavpandeyvpz/ashv
[code-coverage-image]: https://img.shields.io/codecov/c/github/vaibhavpandeyvpz/ashv.svg?style=flat-square
[code-coverage-url]: https://codecov.io/gh/vaibhavpandeyvpz/ashv
[latest-version-image]: https://img.shields.io/github/release/vaibhavpandeyvpz/ashv.svg?style=flat-square
[latest-version-url]: https://github.com/vaibhavpandeyvpz/ashv/releases
[downloads-image]: https://img.shields.io/packagist/dt/vaibhavpandeyvpz/ashv.svg?style=flat-square
[downloads-url]: https://packagist.org/packages/vaibhavpandeyvpz/ashv
[php-version-image]: http://img.shields.io/badge/php-5.4+-8892be.svg?style=flat-square
[php-version-url]: https://packagist.org/packages/vaibhavpandeyvpz/ashv
[license-image]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[license-url]: LICENSE.md
[insights-image]: https://insight.sensiolabs.com/projects/28e2f13a-cc85-4087-8d88-99403a03d77c/small.png
[insights-url]: https://insight.sensiolabs.com/projects/28e2f13a-cc85-4087-8d88-99403a03d77c
