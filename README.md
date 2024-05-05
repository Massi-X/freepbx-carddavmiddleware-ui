<div align="center">
  <img src="assets/images/icon.png" alt="Module icon" width="164">
  <h3>CardDAV Middleware UI</h3>
</div>

> [!CAUTION]
> This is not intended for the end user! You can <a href="https://carddavmiddleware.massi-x.dev/latest">download the latest release here</a>

## About this
CardDAV Middleware UI (formerly known as PhoneMiddleware) is the base to build a complete moduoe for FreePBX/Incredible PBX and izpbx systems meant to fill the gap between cardDAV servers and the internal PBX contact system.

This is not a complete module and *will not work as is!* It's only meant for developers who want to build their backends and release a module based on that. So if you are an end user and you still reading this, well.. you shouldn't! Find more info <a href="https://carddavmiddleware.massi-x.dev">here</a>.

## Development
First pull the main repo or, even better, one of the tagged versions available, then start developing by having a look at the main files in the project:
- `core/CoreInterface.php` is the main interface that defines all the mandatory and optional mathods you can implement. Have a look inside for all the comments beside the declarations. Implement your logic following the requirements of the UI too. Your core class **MUST** be `Core` and the file **MUST** be `core.php`
- `numbertocnam.php` and `carddavtoxml.php` are the files that are exposed to the web server, the user will call those URLs to get a CNAM or a phonebook XML respectively
- When you have tested everything and ready to deploy the module, start the build script inside a shell, you will be guided step-by-step and you'll receive a nice packaged module back

## Donation
If you like to support me, you can donate. Any help is greatly appreciated. Thank you!

<a target="_blank" href="https://paypal.me/firemetris"><img src="https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif" alt="paypal"/></a>

**Bitcoin:** 1Pig6XJxXGBj1v3r6uLrFUSHwyX8GPopRs

**Monero:** 89qdmpDsMm9MUvpsG69hbRMcGWeG2D26BdATw1iXXZwi8DqSEstJGcWNkenrXtThCAAJTpjkUNtZuQvxK1N5xSyb18eXzPD

## License
`SPDX-License-Identifier: CC-BY-NC-ND-4.0`<br>
This work is licensed under <a target="_blank" href="https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode.txt">Creative Commons Attribution-NonCommercial-NoDerivatives 4.0 International</a><br>
By using this module, building your own one by extending it or any other similar matter you agree to the terms. Licenses for the included libraries are available below, credits go to the original author only.
- [Tagify](https://github.com/yairEO/tagify)
- [DragSort](https://github.com/yairEO/dragsort)