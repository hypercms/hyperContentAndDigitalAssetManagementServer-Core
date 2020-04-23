When CKEditor will be updated, the following changes need to be done:

1. Load the custom CKEditor config of hyperCMS (will be done by default via hyperCMS UI function showeditor)

2. Load custom Plugins 'hcms_linkbrowsebuttons' and 'hcms_video' in custom hyperCMS config (will be done by default by config)

3. Overwrite the files of CKEditor Plugin 'image' (ckeditor/plugins/image) with the files of hcms_image (ckeditor_custom/hcms_image)
See also comments in file:
// Avoid overwritting of scaled values
// Use scaled dimensions from media_select

4. Correct UTF-8 in Notepad++ of these files (UTF-8 without BOM is not working):
lang/de.js
ckeditor.js