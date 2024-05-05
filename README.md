# Html Viewer
> View static HTML files directly in Nextcloud

HTML files can be a great way to illustrate content in an interactive and responsive format.
The HTML viewer app enables viewing and editing of static HTML files directly within Nextcloud.

### Security information
Loading HTML files within the context of Nextcloud carries a risk of exposing user information to third parties, depending on the configuration of the server.
The HTML viewer app uses the sandbox and CSP features for iframes to disable JavaScript and prevent access to user information and websites outside your Nextcloud, but this can not guarantee that no information is ever transferred to third parties.

Administrators should also be aware that some users may not be able to differentiate between the content of an HTML file they have opened and the normal user interface of Nextcloud.

### JavaScript
JavaScript for HTML files is disabled by default. Enabling it is a bad idea and we do not recommend this at all.
Although security features like sandboxing, CSP and CORS limit what can be done, it still means that unknown code is being executed within Nextcloud.

If you still wish to enable JS, this occ command will enable it:
```bash
./occ config:app:set htmlviewer allowJs --value=1
```

### File Size Limit
By default, the app will load HTML files up to 32MB. To change the file size, use this occ command:
```bash
 ./occ config:app:set htmlviewer maxSize --value=SizeInMegabyte
```

### Iframe Permissions
The app automatically configures the iframe with an CSP for [supported browsers](https://caniuse.com/mdn-api_htmliframeelement_csp) and a sandbox to limit code execution and data access.
Both policies can be changed through config settings with the OCC commands shown below.
Please be aware that the general CSP of your Nextcloud may still block some access regardless of what CSP and sandbox settings you configure.

To change the CSP or sandbox settings, use these occ commands:
```bash
 ./occ config:app:set htmlviewer csp --value=CustomCspSettings
 ./occ config:app:set htmlviewer sandbox --value=CustomSandboxSettings
```