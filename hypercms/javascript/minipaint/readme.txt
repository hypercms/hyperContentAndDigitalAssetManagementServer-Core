When miniPaint will be updated, the following changes need to be done (based on Version 4.9.1):

1. Change the menu in dist/bundle.js:
Add new "Quick Save" menu item:
                    w = [{
                        name: "Quick Save",
                        children: [{
                            name: "Full layers data",
                            target: "file/quicksave.quicksave"
                        }, {
                            name: "PNG",
                            target: "file/quicksave.png"
                        }, {
                            name: "JPEG",
                            target: "file/quicksave.jpeg"
                        }]

Remove File menu according to old version:
  Remove Search Images
  Rename Export to Save as
  Remove Save As (save JSON)
  Remove Save As Data URL
  Remove Quick Save and Quick Load

Remove External:
                        }, {
                            name: "External",
                            children: [{
                                name: "TINYPNG - Compress PNG and JPEG",
                                href: "https://tinypng.com"
                            }, {
                                name: "REMOVE.BG - Remove Image Background",
                                href: "https://www.remove.bg"
                            }, {
                                name: "PNGTOSVG - Convert Image to SVG",
                                href: "https://www.pngtosvg.com"
                            }, {
                                name: "SQUOOSH - Compress and Compare Images",
                                href: "https://squoosh.app"
                            }]

2. Change colors of theme-dark in dist/bundle.js:
Other browsers:
background: #666d6f -> #7d7d7d;
background-color-area: #7d7d7d;
background-color-section: #7d7d7d;
background-color-menu: #2D2D2D -> #424242;
background-color-active: #adecab -> #FF8411;
area-background-color: #464d4f -> transparent;

3. Change mobile menu button in dist/bundle.js:
right_mobile_menu{\n\tposition:absolute;\n\twidth:32px;\n\theight:32px;\n\tbackground: url("images/sprites.png") no-repeat 2px -96px;\n\tfilter: invert(1);\n\tdisplay:block;\n\ttop:50px;\n\tright:2px;\n\tz-index:200;\n\tborder:0;\n\toutline:0;\n\tcursor: pointer;\n}
REMOVE obsolete entry: right_mobile_menu{right:0;}

4. Change logic in dist/bundle.js

Replace Quickload:
                        key: "quickload",
                        value: function() {
                            var e = localStorage.getItem("quicksave_data");
                            if ("" == e || null == e) return !1;
                            this.File_open.load_json(e)
                        }

By:
                        key: "quickload",
                        value: function() {
                            var e = document.forms['mediaconfig'].elements['jsondata'].value;
                            if ("" == e || null == e) return !1;
                            this.File_open.load_json(e)
                        }

Replace Quicksave:
  key: "quicksave"
    ...
    if (e.length > 5e6) return l().error("Sorry, image is too big, max 5 MB."), !1;
  localStorage.setItem("quicksave_data", e)
    ...
By: 
  key: "quicksave"
    ...
    window.savejsoncontent(e, '')
    ...

Replace for save JSON data:
                            else if ("JSON" == l) {
                                0 == this.Helper.strpos(r, ".json") && (r += ".json");
                                var h = this.export_as_json(),
                                    f = new Blob ([h], {
                                    mime-type: text
                                });
                                p().saveAs(f, r)
                            }
By:
                            else if ("JSON" == l) {
                                0 == this.Helper.strpos(r, ".json") && (r += ".json");
                                var h = this.export_as_json(),
                                    f = [h];
                                window.savejsoncontent(f, r)
                            }

Replace for readonly of ile name:
                                        else {
                                            var d = "text";
                                            "" == n.placeholder || isNaN(n.placeholder) || (d = "number"), null != n.value && "number" == typeof n.value && (d = "number");
                                            var h = "";
                                            void 0 !== n.comment && (h = '<span class="field_comment trn">' + n.comment + "</span>"), e += '<td colspan="2"><input type="' + d + '" id="pop_data_' + n.name + '" onchange="POP.onChangeEvent();" value="' + n.value + '" placeholder="' + n.placeholder + '" ' + (n.prevent_submission ? 'data-prevent-submission=""' : "") + " />" + h + "</td>"
                                        }
By:
                                        else {
                                            var d = "text";
                                            var readonly = "";
                                            if (n.name == "name") readonly = "readonly";
                                            "" == n.placeholder || isNaN(n.placeholder) || (d = "number"), null != n.value && "number" == typeof n.value && (d = "number");
                                            var h = "";
                                            void 0 !== n.comment && (h = '<span class="field_comment trn">' + n.comment + "</span>"), e += '<td colspan="2"><input type="' + d + '" id="pop_data_' + n.name + '" onchange="POP.onChangeEvent();" value="' + n.value + '" '+ readonly + ' placeholder="' + n.placeholder + '" ' + (n.prevent_submission ? 'data-prevent-submission=""' : "") + " />" + h + "</td>"
                                        }

Relpace parameters for Save as:
Replace:
                            var p = {
                                title: t,
                                ...
                                }, {
                                    name: "layers",
                                    title: "Save layers:",
                                    values: ["All", "Selected", "Separated", "Separated (original types)"]

By:
                            var p = {
                                title: t,
                                ...
                                }, {
                                    name: "layers",
                                    title: "Save layers:",
                                    values: ["All", "Selected"]

5. Replace Quickload in dist/bundle.js:
Replace:
                        key: "quickload",
                        value: function() {
                            var e = localStorage.getItem("quicksave_data");
                            if ("" == e || null == e) return !1;
                            this.File_open.load_json(e)
                        }
By:
                        key: "quickload",
                        value: function() {
                            var e = localStorage.getItem("quicksave_data");
                            if ("" == e || null == e) return !1;
                            this.File_open.load_json(e)
                        }

6. In order to support the hyperCMS save function savemediacontent:
Replace navigator.msSaveOrOpenBlob with window.savemediacontent in minipaint/dist/bundle.js for IE10+.
Replace u.default.saveAs with window.savemediacontent in minipaint/dist/bundle.js for other browsers.

7. Create/update the file image_minipaint.php:
Add PHP code according to the existing file.

8. Prepare HTML head and add main.css, main.js and JQuery library:
<head>
  <title>hyperCMS - miniPaint</title>
  <meta charset="utf-8" />
  <meta http-equiv="x-ua-compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" type="text/css" />
  <link rel="stylesheet" href="<?php echo getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css"); ?>" />
  <script type="text/javascript" src="<?php echo $mgmt_config['url_path_cms']; ?>javascript/main.min.js"></script>
  <script type="text/javascript" src="javascript/jquery/jquery-3.5.1.min.js"></script>
  <!-- miniPaint -->
  <base href="<?php echo $mgmt_config['url_path_cms']; ?>/javascript/minipaint/" />
  <script type="text/javascript" src="<?php echo $mgmt_config['url_path_cms']; ?>javascript/minipaint/dist/bundle.js"></script>
</head>

8. Add close button:
<!-- top bar close button -->
<?php
echo "<div style=\"position:fixed; top:0px; right:0px; width:36px; padding:0; margin:0; z-index:1000;\"><a href=\"javascript:closeminipaint();\" onMouseOut=\"hcms_swapImgRestore();\" onMouseOver=\"hcms_swapImage('close_button','','".getthemelocation()."img/button_close_over.png',1);\"><img name=\"close_button\" src=\"".getthemelocation()."img/button_close.png\" class=\"hcmsButton hcmsButtonSizeSquare\" alt=\"".getescapedtext ($hcms_lang['close'][$lang])."\" title=\"".getescapedtext ($hcms_lang['close'][$lang])."\" /></a></div>\n";
?>

9. Add save JS code at the end including the invisible img tag:
See code at the end of the file image_minipaint.php

