When CKEditor will be updated, the following changes need to be done:

1. Load the custom CKEditor config of hyperCMS (will be done by default via hyperCMS UI function showeditor)

2. Load Plugins hcms_linkbrowsebuttons and hcms_video in custom hyperCMS config (will be done by default by config)

3. Overwrite the files of CKEditor Plugin image (ckeditor/plugins/image) with the files of hcms_image (ckeditor_custom/hcms_image)
See also comment in file:
// to avoid overwritting of scaled values

4. Copy hyperCMS skin from ckeditor/skins/hypercms to ckeditor/skins