<table>
  <tr>
    <td width="200" style="border: none;">
     <img src="assets/images/icon.png" alt="Module icon"/>
    </td>
    <td style="border: none;">
     <h2>CardDAV Middleware UI for FreePBX (formerly PhoneMiddleware)</h2>
    </td>
  </tr>
</table>

# For the users: All the downloads moved to <a href="https://phonemiddleware.000webhostapp.com/releases/">Releases</a>

## About this
This is not the complete module and *will not work as is!* This is only meant for developers who wants to build their backends and release a module based on that. So if you are an end user and you still reading this, well.. you shouldn't! Download the ready-to-use module from <a href="https://phonemiddleware.000webhostapp.com/releases/">here</a>

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