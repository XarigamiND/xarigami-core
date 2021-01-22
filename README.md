# xarigami-core

## What is this?

This is a fork of the old Xarigami CMS updated to work with PHP 7.2+. Xarigami was a fork of Xaraya CMS 1.x branch.
Both of those are unmaintained nowadays.

The CMS itself is old fashioned, but has very nice features. Please read on www.xarigami.org for more.
This XarigamiND (New Development) fork is **not** maintanied by the original Xarigami developers.

### Main changes in ND

* Updated many third party libraries to their latest release
* Updated obsolete PHP commands and constructs
* Updated using PHP code analyzers and migration helpers

## Source code history

This fork is based on last stable public release of Xarigami 1.5.5. This git repo does not include Xarigami development history, 
because I could not get access to the original Monotone repo. Also, the unfinished Xarigami 1.6 may contain more cool features 
that are not included here. But the PHP 7.2 upgrade could be "replayed" on any of those.

## Modules

Xarigami-core contains the core system, and optional modules are included in separated repos. 
For an usable base system you'll need some of those modules too, as they were packaged in
xarigami-core, xarigami-base and xarigami-full releases originally.

Some of the 1.5.5-full modules are not included here, because they are so much outdated,
contain unmaintained exernal libraries and functionality is not generally useful. Deleted modules
are: crispbb, dphighlight, netquery, pdfgen.
