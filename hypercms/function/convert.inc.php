<?php
/*
 * This file is part of
 * hyper Content & Digital Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 */
 
// the following functions are deprecated
 
// --------------------------------- convert_html -------------------------------
// function: convert_html()
// input: text to be translated
// output: translated text without special characters

// description:
// function returns text but changing all the special characters to their html escaped aquivalent

function convert_html ($text)
{
  // special characters and their html escaped aquivalents
  $char["¡"]="&iexcl;";
  $char["¢"]="&cent;";
  $char["£"]="&pound;";
  $char["¤"]="&curren;";
  $char["¥"]="&yen;";
  $char["¦"]="&brvbar;";
  $char["§"]="&sect;";
  $char["¨"]="&uml;";
  $char["©"]="&copy;";
  $char["ª"]="&ordf;";
  $char["«"]="&laquo;";
  $char["¬"]="&not;";
  $char["\x7f"]="&shy;";
  $char["®"]="&reg;";
  $char["¯"]="&macr;";
  $char["°"]="&deg;";
  $char["±"]="&plusmn;";
  $char["²"]="&sup2;";
  $char["³"]="&sup3;";
  $char["´"]="&acute;";
  $char["µ"]="&micro;";
  $char["¶"]="&para;";
  $char["·"]="&middot;";
  $char["¸"]="&cedil;";
  $char["¹"]="&sup1;";
  $char["º"]="&ordm;";
  $char["»"]="&raquo;";
  $char["¼"]="&frac14;";
  $char["½"]="&frac12;";
  $char["¾"]="&frac34;";
  $char["¿"]="&iquest;";
  $char["À"]="&Agrave;";
  $char["Á"]="&Aacute;";
  $char["Â"]="&Acirc;";
  $char["Ã"]="&Atilde;";
  $char["Ä"]="&Auml;";
  $char["Å"]="&Aring;";
  $char["Æ"]="&AElig;";
  $char["Ç"]="&Ccedil;";
  $char["È"]="&Egrave;";
  $char["É"]="&Eacute;";
  $char["Ê"]="&Ecirc;";
  $char["Ë"]="&Euml;";
  $char["Ì"]="&Igrave;";
  $char["Í"]="&Iacute;";
  $char["Î"]="&Icirc;";
  $char["Ï"]="&Iuml;";
  $char["Ð"]="&ETH;";
  $char["Ñ"]="&Ntilde;";
  $char["Ò"]="&Ograve;";
  $char["Ó"]="&Oacute;";
  $char["Ô"]="&Ocirc;";
  $char["Õ"]="&Otilde;";
  $char["Ö"]="&Ouml;";
  $char["×"]="&times;";
  $char["Ø"]="&Oslash;";
  $char["Ù"]="&Ugrave;";
  $char["Ú"]="&Uacute;";
  $char["Û"]="&Ucirc;";
  $char["Ü"]="&Uuml;";
  $char["Ý"]="&Yacute;";
  $char["Þ"]="&THORN;";
  $char["ß"]="&szlig;";
  $char["à"]="&agrave;";
  $char["á"]="&aacute;";
  $char["â"]="&acirc;";
  $char["ã"]="&atilde;";
  $char["ä"]="&auml;";
  $char["å"]="&aring;";
  $char["æ"]="&aelig;";
  $char["ç"]="&ccedil;";
  $char["è"]="&egrave;";
  $char["é"]="&eacute;";
  $char["ê"]="&ecirc;";
  $char["ë"]="&euml;";
  $char["ì"]="&igrave;";
  $char["í"]="&iacute;";
  $char["î"]="&icirc;";
  $char["ï"]="&iuml;";
  $char["ð"]="&ieth;";
  $char["ñ"]="&ntilde;";
  $char["ò"]="&ograve;";
  $char["ó"]="&oacute;";
  $char["ô"]="&ocirc;";
  $char["õ"]="&otilde;";
  $char["ö"]="&ouml;";
  $char["÷"]="&divide;";
  $char["ø"]="&oslash;";
  $char["ù"]="&ugrave;";
  $char["ú"]="&uacute;";
  $char["û"]="&ucirc;";
  $char["ü"]="&uuml;";
  $char["ý"]="&yacute;";
  $char["þ"]="&thorn;";
  $char["ÿ"]="&yuml;";

  // translate all
  $text_new = strtr ($text, $char);

  return $text_new;
}

// --------------------------------- convert_unicode -------------------------------
// function: convert_unicode()
// input: text to be translated
// output: translated text without special characters

// description:
// function returns text but changing all the special chars to their unicode aquivalent

function convert_unicode ($text)
{
  // ISO 8859-1 special characters and their aquivalent unicode
  $char[" "] = "&#X160;";
  $char["¡"] = "&#X161;";
  $char["¢"] = "&#X162;";
  $char["£"] = "&#X163;";
  $char["¤"] = "&#X164;";
  $char["¥"] = "&#X165;";
  $char["¦"] = "&#X166;";
  $char["§"] = "&#X167;";
  $char["¨"] = "&#X168;";
  $char["©"] = "&#X169;";
  $char["ª"] = "&#X170;";
  $char["«"] = "&#X171;";
  $char["¬"] = "&#X172;";
  $char["&#173;"] = "&#X173;";
  $char["®"] = "&#X174;";
  $char["¯"] = "&#X175;";
  $char["°"] = "&#X176;";
  $char["±"] = "&#X177;";
  $char["²"] = "&#X178;";
  $char["³"] = "&#X179;";
  $char["´"] = "&#X180;";
  $char["µ"] = "&#X181;";
  $char["¶"] = "&#X182;";
  $char["·"] = "&#X183;";
  $char["¸"] = "&#X184;";
  $char["¹"] = "&#X185;";
  $char["º"] = "&#X186;";
  $char["»"] = "&#X187;";
  $char["¼"] = "&#X188;";
  $char["½"] = "&#X189;";
  $char["¾"] = "&#X190;";
  $char["+"] = "&#X191;";
  $char["À"] = "&#X192;";
  $char["-"] = "&#X193;";
  $char["Â"] = "&#X194;";
  $char["Ã"] = "&#X195;";
  $char["Ä"] = "&#X196;";
  $char["Å"] = "&#X197;";
  $char["Æ"] = "&#X198;";
  $char["Ç"] = "&#X199;";
  $char["È"] = "&#X200;";
  $char["É"] = "&#X201;";
  $char["Ê"] = "&#X202;";
  $char["Ë"] = "&#X203;";
  $char["Ì"] = "&#X204;";
  $char["Í"] = "&#X205;";
  $char["Î"] = "&#X206;";
  $char["Ï"] = "&#X207;";
  $char["Ð"] = "&#X208;";
  $char["Ñ"] = "&#X209;";
  $char["Ò"] = "&#X210;";
  $char["Ó"] = "&#X211;";
  $char["Ô"] = "&#X212;";
  $char["Õ"] = "&#X213;";
  $char["Ö"] = "&#X214;";
  $char["×"] = "&#X215;";
  $char["Ø"] = "&#X216;";
  $char["Ù"] = "&#X217;";
  $char["Ú"] = "&#X218;";
  $char["Û"] = "&#X219;";
  $char["Ü"] = "&#X220;";
  $char["Ý"] = "&#X221;";
  $char["Þ"] = "&#X222;";
  $char["ß"] = "&#X223;";
  $char["à"] = "&#X224;";
  $char["á"] = "&#X225;";
  $char["â"] = "&#X226;";
  $char["ã"] = "&#X227;";
  $char["ä"] = "&#X228;";
  $char["å"] = "&#X229;";
  $char["æ"] = "&#X230;";
  $char["ç"] = "&#X231;";
  $char["è"] = "&#X232;";
  $char["é"] = "&#X233;";
  $char["ê"] = "&#X234;";
  $char["ë"] = "&#X235;";
  $char["ì"] = "&#X236;";
  $char["í"] = "&#X237;";
  $char["î"] = "&#X238;";
  $char["ï"] = "&#X239;";
  $char["ð"] = "&#X240;";
  $char["ñ"] = "&#X241;";
  $char["ò"] = "&#X242;";
  $char["ó"] = "&#X243;";
  $char["ô"] = "&#X244;";
  $char["õ"] = "&#X245;";
  $char["ö"] = "&#X246;";
  $char["÷"] = "&#X247;";
  $char["ø"] = "&#X248;";
  $char["ù"] = "&#X249;";
  $char["ú"] = "&#X250;";
  $char["û"] = "&#X251;";
  $char["ü"] = "&#X252;";
  $char["ý"] = "&#X253;";
  $char["þ"] = "&#X254;";
  $char["ÿ"] = "&#X255;";

  // translate all
  $text_new = strtr ($text, $char);

  return $text_new;
}

// --------------------------------- deconvert_html -------------------------------
// function: deconvert_html()
// input: text to be translated
// output: translated text with special characters

// description:
// function returns text but changing all the html escaped aquivalent to the special characters

function deconvert_html ($text)
{
  // special characters and their html escaped aquivalents
  // list of transformations
  $simb["&iexcl;"]="¡";
  $simb["&cent;"]="¢";
  $simb["&pound;"]="£";
  $simb["&curren;"]="¤";
  $simb["&yen;"]="¥";
  $simb["&brvbar;"]="¦";
  $simb["&sect;"]="§";
  $simb["&uml;"]="¨";
  $simb["©"]="&copy;";
  $simb["ª"]="&ordf;";
  $simb["«"]="&laquo;";
  $simb["¬"]="&not;";
  $simb["\x7f"]="&shy;";
  $simb["®"]="&reg;";
  $simb["¯"]="&macr;";
  $simb["°"]="&deg;";
  $simb["±"]="&plusmn;";
  $simb["²"]="&sup2;";
  $simb["³"]="&sup3;";
  $simb["´"]="&acute;";
  $simb["µ"]="&micro;";
  $simb["¶"]="&para;";
  $simb["·"]="&middot;";
  $simb["¸"]="&cedil;";
  $simb["¹"]="&sup1;";
  $simb["º"]="&ordm;";
  $simb["»"]="&raquo;";
  $simb["¼"]="&frac14;";
  $simb["½"]="&frac12;";
  $simb["¾"]="&frac34;";
  $simb["¿"]="&iquest;";
  $simb["À"]="&Agrave;";
  $simb["Á"]="&Aacute;";
  $simb["Â"]="&Acirc;";
  $simb["Ã"]="&Atilde;";
  $simb["Ä"]="&Auml;";
  $simb["Å"]="&Aring;";
  $simb["Æ"]="&AElig;";
  $simb["Ç"]="&Ccedil;";
  $simb["È"]="&Egrave;";
  $simb["É"]="&Eacute;";
  $simb["Ê"]="&Ecirc;";
  $simb["Ë"]="&Euml;";
  $simb["Ì"]="&Igrave;";
  $simb["Í"]="&Iacute;";
  $simb["Î"]="&Icirc;";
  $simb["Ï"]="&Iuml;";
  $simb["Ð"]="&ETH;";
  $simb["Ñ"]="&Ntilde;";
  $simb["Ò"]="&Ograve;";
  $simb["Ó"]="&Oacute;";
  $simb["Ô"]="&Ocirc;";
  $simb["Õ"]="&Otilde;";
  $simb["Ö"]="&Ouml;";
  $simb["×"]="&times;";
  $simb["Ø"]="&Oslash;";
  $simb["Ù"]="&Ugrave;";
  $simb["Ú"]="&Uacute;";
  $simb["Û"]="&Ucirc;";
  $simb["Ü"]="&Uuml;";
  $simb["Ý"]="&Yacute;";
  $simb["Þ"]="&THORN;";
  $simb["ß"]="&szlig;";
  $simb["à"]="&agrave;";
  $simb["á"]="&aacute;";
  $simb["â"]="&acirc;";
  $simb["ã"]="&atilde;";
  $simb["ä"]="&auml;";
  $simb["å"]="&aring;";
  $simb["æ"]="&aelig;";
  $simb["ç"]="&ccedil;";
  $simb["è"]="&egrave;";
  $simb["é"]="&eacute;";
  $simb["ê"]="&ecirc;";
  $simb["ë"]="&euml;";
  $simb["ì"]="&igrave;";
  $simb["í"]="&iacute;";
  $simb["î"]="&icirc;";
  $simb["ï"]="&iuml;";
  $simb["ð"]="&ieth;";
  $simb["ñ"]="&ntilde;";
  $simb["ò"]="&ograve;";
  $simb["ó"]="&oacute;";
  $simb["ô"]="&ocirc;";
  $simb["õ"]="&otilde;";
  $simb["ö"]="&ouml;";
  $simb["÷"]="&divide;";
  $simb["ø"]="&oslash;";
  $simb["ù"]="&ugrave;";
  $simb["ú"]="&uacute;";
  $simb["û"]="&ucirc;";
  $simb["ü"]="&uuml;";
  $simb["ý"]="&yacute;";
  $simb["þ"]="&thorn;";
  $simb["ÿ"]="&yuml;";

  // translate all
  $text_new = strtr ($text, $char);

  return $text_new;
}

// --------------------------------- deconvert_unicode -------------------------------
// function: deconvert_unicode()
// input: text to be translated
// output: translated text without special characters

// description:
// function returns text but changing the unicode aquivalent to the special character

function deconvert_unicode ($text)
{
  // ISO 8859-1 special characters and their aquivalent unicode
  $char["&#X160;"] = " ";
  $char["&#X161;"] = "¡";
  $char["&#X162;"] = "¢";
  $char["&#X163;"] = "£";
  $char["&#X164;"] = "¤";
  $char["&#X165;"] = "¥";
  $char["&#X166;"] = "¦";
  $char["&#X167;"] = "§";
  $char["&#X168;"] = "¨";
  $char["&#X169;"] = "©";
  $char["&#X170;"] = "ª";
  $char["&#X171;"] = "«";
  $char["&#X172;"] = "¬";
  $char["&#X173;"] = "&#173;";
  $char["&#X174;"] = "®";
  $char["&#X175;"] = "¯";
  $char["&#X176;"] = "°";
  $char["&#X177;"] = "±";
  $char["&#X178;"] = "²";
  $char["&#X179;"] = "³";
  $char["&#X180;"] = "´";
  $char["&#X181;"] = "µ";
  $char["&#X182;"] = "¶";
  $char["&#X183;"] = "·";
  $char["&#X184;"] = "¸";
  $char["&#X185;"] = "¹";
  $char["&#X186;"] = "º";
  $char["&#X187;"] = "»";
  $char["&#X188;"] = "¼";
  $char["&#X189;"] = "½";
  $char["&#X190;"] = "¾";
  $char["&#X191;"] = "+";
  $char["&#X192;"] = "À";
  $char["&#X193;"] = "-";
  $char["&#X194;"] = "Â";
  $char["&#X195;"] = "Ã";
  $char["&#X196;"] = "Ä";
  $char["&#X197;"] = "Å";
  $char["&#X198;"] = "Æ";
  $char["&#X199;"] = "Ç";
  $char["&#X200;"] = "È";
  $char["&#X201;"] = "É";
  $char["&#X202;"] = "Ê";
  $char["&#X203;"] = "Ë";
  $char["&#X204;"] = "Ì";
  $char["&#X205;"] = "Í";
  $char["&#X206;"] = "Î";
  $char["&#X207;"] = "Ï";
  $char["&#X208;"] = "Ð";
  $char["&#X209;"] = "Ñ";
  $char["&#X210;"] = "Ò";
  $char["&#X211;"] = "Ó";
  $char["&#X212;"] = "Ô";
  $char["&#X213;"] = "Õ";
  $char["&#X214;"] = "Ö";
  $char["&#X215;"] = "×";
  $char["&#X216;"] = "Ø";
  $char["&#X217;"] = "Ù";
  $char["&#X218;"] = "Ú";
  $char["&#X219;"] = "Û";
  $char["&#X220;"] = "Ü";
  $char["&#X221;"] = "Ý";
  $char["&#X222;"] = "Þ";
  $char["&#X223;"] = "ß";
  $char["&#X224;"] = "à";
  $char["&#X225;"] = "á";
  $char["&#X226;"] = "â";
  $char["&#X227;"] = "ã";
  $char["&#X228;"] = "ä";
  $char["&#X229;"] = "å";
  $char["&#X230;"] = "æ";
  $char["&#X231;"] = "ç";
  $char["&#X232;"] = "è";
  $char["&#X233;"] = "é";
  $char["&#X234;"] = "ê";
  $char["&#X235;"] = "ë";
  $char["&#X236;"] = "ì";
  $char["&#X237;"] = "í";
  $char["&#X238;"] = "î";
  $char["&#X239;"] = "ï";
  $char["&#X240;"] = "ð";
  $char["&#X241;"] = "ñ";
  $char["&#X242;"] = "ò";
  $char["&#X243;"] = "ó";
  $char["&#X244;"] = "ô";
  $char["&#X245;"] = "õ";
  $char["&#X246;"] = "ö";
  $char["&#X247;"] = "÷";
  $char["&#X248;"] = "ø";
  $char["&#X249;"] = "ù";
  $char["&#X250;"] = "ú";
  $char["&#X251;"] = "û";
  $char["&#X252;"] = "ü";
  $char["&#X253;"] = "ý";
  $char["&#X254;"] = "þ";
  $char["&#X255;"] = "ÿ";

  // translate all
  $text_new = strtr ($text, $char);

  return $text_new;
}
?>