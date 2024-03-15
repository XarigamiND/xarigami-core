# Changelog

Please note that this file is based on XarigamiND Core repo changes only.
Any individual module changes are listed in the module's own CHANGELOG.
Thus this does not work as a Xarigami ND release changelog!

## [next version] - tbd

_Xarigami 1.5.5 updated to work with PHP 8, plus some small improvements_

### Changed

- **Breaking** Short URLs won't do partial parsing and return a page on "identified part". Rather the full URL must match certain pattern to return a page. So far Articles module is updated. E.g. ``/news/233/some/crap/on/url`` worked before, not anymore. This URL must stop at ``/news/233``. This is to make sure a page exists only on certain URLs and not unlimited number of URLs.
- Upgrade ADOdb to 5.22.7

### Fixed

- A lot of PHP language features as required by PHP 8+

## [1.5.5-1] - 2022-01-26

_Original Xarigami 1.5.5 updated to work with PHP 7.x_

This version is available as a tag ``for-php7.2`` in core and all modules.

### Added

- Sort file names in Dynamic FileList Property

### Changed

- Upgrade PHPMailer to 6.5.3
- Upgrade ADOdb to 5.20.18

### Removed 

- File tampering security check from index.php!
- Any references to Monotone SCM (file checks etc)

### Fixed

- A lot of PHP language features as required by PHP 7.2+
- Also several typos and similar stuff identified by PHPstan, PHP7mar, PHP7cc
- "Send a test" mail was not able to display occurred errors
- Fix broken xaraya.com links to xarigami.org module pages
- Fix error that startnum=%% appeared in paging URLs
- Fix that base/html block didn't work



