# Html Viewer
> View static HTML files directly in Nextcloud

The HTML viewer app loads static HTML files directly within Nextcloud as an iframe, so you can see the file instead of its source code.

### Security information
Loading HTML files within the context of Nextcloud carries a risk of exposing user information to third parties.
The HTML viewer app attempts to improve this situation by using the sandbox feature for iframes to disable JavaScript and prevent access to user information. 


### Enabling JavaScript
Enabling JavaScript for HTML files is a bad idea and we do not recommend this at all.
Although security features like sandboxing, CSP and CORS limit what can be done, it still means that unknown code is being executed within Nextcloud.

If you still wish to enable JS, this occ command will enable it:
```bash
./occ config:app:set htmlviewer allowJs --value=1
```

### Increase File Size Limit
By default, the app will load HTML files up to 32MB. To increase the file size, use this occ command:
```bash
 ./occ config:app:set htmlviewer maxSize --value=SizeInMegabyte
```