![Release](https://img.shields.io/github/v/release/getwebspace/platform)
![Docker builder](https://github.com/getwebspace/platform/workflows/Docker%20builder/badge.svg)
![License](https://img.shields.io/github/license/getwebspace/platform)
![Visitors](https://visitor-badge.glitch.me/badge?page_id=getwebspace.platform)

## WebSpace Engine
[Website](https://getwebspace.org/) |
[Documentation](https://github.com/getwebspace/platform/wiki) |
[Official Repository](https://github.com/getwebspace/platform) |
[Issue Tracker](https://github.com/getwebspace/platform/issues) |
[Docker template](https://github.com/getwebspace/platform-template) |
[Demo website here](https://demo.getwebspace.org)

Simple free open source multi-user site engine with great functionality,
primarily intended for: online stores and mass media or blogs.

![Demo site](image.png)

## Features
- Static pages
- Users:
  * User groups
  * Permissions
  * Mailing list
- Publications
    * News
    * Categories
- Shop:
    * Catalog
    * Products
    * Attributes
    * Orders
    * Statistic
- Dynamic forms
- Guestbook
- Files and image optimization (GD -> WebP)
- Background tasks
- Templates in Twig
- Mailing, via:
    * SMTP
    * SendPulse
- Editor, files:
    * Theme
    * Resource
- Plugins API, types:
    * Default
    * OAuth
    * Delivery
    * Payment
    * Language (locale)
    * Legacy
- HTTP API:
    * Dedicated REST API
    * Each public controller as API
- reCAPTCHA
- Docker compatible

and more..

## Themes
- [Default shop theme](https://github.com/getwebspace/platform-default-theme)

## Plugins
- [Search optimization](https://github.com/getwebspace/platform-plugin-seo)
- [ClearCache](https://github.com/getwebspace/platform-plugin-clearcache)
- [Turbo PWA](https://github.com/getwebspace/platform-plugin-turbo)

## Languages
- English (default)
- Ukrainian ([plugin](https://github.com/getwebspace/platform-lang-ukrainian))
- Russian ([plugin](https://github.com/getwebspace/platform-lang-russian))

## Roadmap
- Products series
- New attributes

## Quickstart install
### Production mode
[Installation instructions](https://github.com/getwebspace/platform/wiki/Installation-(Docker)) from Docker template [getwebspace/platform-template](https://github.com/getwebspace/platform-template)

### Developer mode
Use docker by running the command: `docker-compose up -d`, then open `http://localhost:9000`

### Environment variables
You can define the next environment variables

| Type    | Variable Name      | Default        | description                                                       |
|---------|--------------------|----------------|-------------------------------------------------------------------|
| Build   | BUILD_DEPENDENCIES |                | Dependencies used in the build                                    |   
| Build   | DEPENDENCIES       |                | Core Libraries                                                    |   
| Build   | EXTRA_EXTENSIONS   | pdo_mysql      | Additional Libraries                                              |   
| Build   | PLATFORM_HOME      | /var/container | Home directory                                                    |   
| Runtime | DEBUG              | 0              | Debug mode                                                        |   
| Runtime | TEST               | 0              | Test mode                                                         |   
| Runtime | DATABASE           |                | PDO Database params (default: sqlite)                             |   
| Runtime | SIMPLE_PHONE_CHECK | 0              | Checking the user's phone number for compliance with the standard |   
| Runtime | TZ                 |                | TimeZone (default: UTC)                                           |   

**Database Example**:
`mysql://my_user:my_pass@127.0.0.0:3306/example`

#### Install dependencies
```shell script
./composer install
```

#### Static analyzer
```shell script
./phpcs
```

#### Unit tests
```shell script
./phpunit [..]
```

#### Post install check chmod's
```shell script
chmod -R 0777 plugin
chmod -R 0777 public/resource
chmod -R 0777 theme
chmod -R 0777 var
```

## Collaborators
<a href="https://github.com/alksily"><img src="https://avatars.githubusercontent.com/u/5148853?v=4" alt="alksily" width="40"/></a>

## Copyright & license
Licensed under the MIT license. See [License File](LICENSE.md) for more information.
