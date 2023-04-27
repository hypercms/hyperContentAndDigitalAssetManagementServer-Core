<?php
// ----------------------------------------- definitions -----------------------------------------------

// setting the protocol
$url_protocol = (!empty($_SERVER['HTTPS'])) ? 'https://' : 'http://';

// location of index file
$config['indexfile'] = "%abs_path_rep%/search/index/".$site.".dat";
// CSS style class for the div container of the tiel bar and all results
$config['css_div'] = "searchtext";
// CSS style class for the headline of the result table
$config['css_headline'] = "headline";
// color code for the div container of a single search result
$config['css_result'] = "searchresult";
// color code for the search result title in the search results
$config['css_title'] = "searchtitle";
// color code for the URL-presentation in the search results
$config['css_url'] = "searchurl";
// show icon for pdf documents
$config['icon_pdf'] = $url_protocol."%url_path_rep%/search/icon_pdf.png";
// results per page
$config['results'] = 10;
// max result pages
$config['maxpages'] = 20;
// show meta description in the results
$config['showdescription'] = false;
// show extract with highlighted found expressions in the results
$config['showextract'] = true;
// max length of the extract in characters
$config['maxextract'] = 180;
// show URL in the results
$config['showurl'] = true;
// search expression must match exactly a word in the content (if 'true' * can be used as wild card character)
$config['exactmatches'] = true;
// exclude path identifier (string) from indexing
$config['exclude_path'] = "/_";
// save search history log, provide location or leave empty
$config['search_log'] = "%abs_path_rep%/search/index/".$site.".log";

// ---------------------------- text entries for different languages -----------------------------------

$text[0]['de'] = "Treffer";
$text[0]['en'] = "hits";
$text[0]['al'] = "klikime";
$text[0]['sk'] = "z&aacute;znamy";
$text[0]['cs'] = "Nálezy";
$text[0]['hu'] = "tal&aacute;latok";
$text[0]['me'] = "pogodaka";
$text[0]['ua'] = "збігів";
$text[0]['ro'] = "Afisari";
$text[0]['hr'] = "rezultata";
$text[0]['rs'] = "pogodak";
$text[0]['pl'] = "Wyniki";
$text[0]['it'] = "visite";
$text[0]['ba'] = "Pretraga";
$text[0]['bg'] = "резултати";
$text[0]['ru'] = "попытки";
$text[0]['mk'] = "Погодоци";

$text[1]['de'] = "Zeige Ergebnisse %start% - %end% von %max% Treffern f&uuml;r die Suche nach '%query%'";
$text[1]['en'] = "Show results %start% - %end% of %max% hits for the search expression '%query%'";
$text[1]['al'] = "Shfaq rezultatet %start% - %end% nga %max% hits for the search expression '%query%':";
$text[1]['sk'] = "Uk&aacute;&#382; v&yacute;sledky %start% - %end% z %max% z&aacute;znamov pre h&#318;adan&yacute; v&yacute;raz '%query%'";
$text[1]['cs'] = "Výsledky %start% - %end% z %max% pro hledaný výraz '%query%'";
$text[1]['hu'] = "%start% - %end% tal&aacute;lat az &ouml;sszes %max% tal&aacute;lat alapj&aacute;n az '%query%' kifejez&eacute;sre";
$text[1]['me'] = "Prika&#382;i rezultate od %start%-%end% za pogotke pri pretrazi izraza '%query%'";
$text[1]['ua'] = "Результати %start%-%end% з %max% за пошуковим запитом '%query%'";
$text[1]['ro'] = "Afiseaza inregistrarile %start% - %end% din %max% inregistrari pentru textul cautat '%query%'";
$text[1]['hr'] = "Prikaz reultata pretrage  %start% - %end% za traženi pojam";
$text[1]['rs'] = "Prikaz rezultata pretrage %start% - %end% za traženi pojam";
$text[1]['pl'] = "Wyniki %start% - %end% z %max% rezultatów wyszukiwania słowa '%query%'";
$text[1]['it'] = "Mostra i risultati [%start% - %end%] di [%max%] risultati per la ricerca del termine '%query%'";
$text[1]['ba'] = "Prikaži rezultat [%start% - %end%]  pretrage za traženi izraz '%query%'";
$text[1]['bg'] = "Показване на [%start% - %end%] от [%max%] резултата от търсенето за '%query%'";
$text[1]['ru'] = "Показать результат %start% - %end% %max% попыток для поиска запрашиваемого выражения '%query%'";
$text[1]['mk'] = "Покажи резултати %start% - %end% od %max% погодоци пребаруваниот израз '%query%'";

$text[2]['de'] = "Ergebnisseite";
$text[2]['en'] = "Search result page";
$text[2]['al'] = "Faqja e rezultateve te kerkimit";
$text[2]['sk'] = "Vyh&#318;ad&aacute;vacia v&yacute;sledkov&aacute; strana";
$text[2]['cs'] = "Výsledek hledání";
$text[2]['hu'] = "Tal&aacute;lati oldalak";
$text[2]['me'] = "Strana sa rezultatima pretrage";
$text[2]['ua'] = "Сторінка з результатами пошуку";
$text[2]['ro'] = "Pagina rezultate cautare";
$text[2]['hr'] = "Rezultati pretraživanja";
$text[2]['rs'] = "Rezultati pretrage";
$text[2]['pl'] = "Przeszukaj stronę z wynikami";
$text[2]['it'] = "Risultato pagina di ricerca";
$text[2]['ba'] = "Rezultat pretraživanja stranice";
$text[2]['bg'] = "Резултати от търсенето";
$text[2]['ru'] = "Страница результатов поиска";
$text[2]['mk'] = "страница со резултати од пребарувањето";

$text[3]['de'] = "Es wurden keine &uuml;bereinstimmenden Dokumente bei der Suche nach '%query%' gefunden.";
$text[3]['en'] = "No documents were found including the expression '%query%'.";
$text[3]['al'] = "Nuk u gjend asnje document duke perfshire edhe : '%query%'.";
$text[3]['sk'] = "&#381;iadan&eacute; dokumenty neboli n&aacute;jden&eacute; vr&aacute;tane h&#318;adan&eacute;ho v&yacute;razu '%query%':";
$text[3]['cs'] = "Nebyly nalezeny žádné výsledky pro výraz '%query%'";
$text[3]['hu'] = "Nem tal&aacute;lhat&oacute; dokumentum, amelyben '%query%' szerepel.";
$text[3]['me'] = "Nije prona&#273;en nijedan dokument sa izrazom '%query%'.";
$text[3]['me'] = "Документів за пошуковим запитом '%query%' не знайдено.";
$text[3]['ro'] = "Nu au fost gasite documente care sa contina textul '%query%'";
$text[3]['hr'] = "Nije pronađen dokument sa traženim pojmom";
$text[3]['hr'] = "Nije pronađen dokument sa traženim pojmom";
$text[3]['pl'] = "Nie odnaleziono dokumentów zawierających wyrażenie '%query%'";
$text[3]['it'] = "Nessun documento che include il termine '%query%' è stato trovato ";
$text[3]['ba'] = "Nije pronađen dokument sa izrazom '%query%'";
$text[3]['bg'] = "Няма намерени резултати включващи фразата '%query%'.";
$text[3]['ru'] = "Ни одного документа, включающего выражение '%query%',не было найдено";
$text[3]['mk'] = "Ниеден документ не е пронајден вклучувајќи го и изразот %query%'.";

$text[4]['de'] = "Suchindex nicht vorhanden.";
$text[4]['en'] = "Search index is missing.";
$text[4]['al'] = "index I kerkimit mungon.";
$text[4]['sk'] = "Chyba h&#318;adan&yacute; index";
$text[4]['cs'] = "Chybí vyhledávací rejstřík.";
$text[4]['hu'] = "A keres&eacute;si felt&eacute;tel hi&aacute;nyzik.";
$text[4]['me'] = "Indeks za pretra&#382;ivanje nedostaje.";
$text[4]['ua'] = "Індекс пошуку відсутній.";
$text[4]['ro'] = "Indexul de cautare lipseste";
$text[4]['hr'] = "Nedostaje indeks za pretraživanje";
$text[4]['rs'] = "Registar pretrage nedostaje.";
$text[4]['pl'] = "indeksu brakuje.";
$text[4]['it'] = "Indice di ricerca mancante.";
$text[4]['ba'] = "Nedostaje registar pretrage";
$text[4]['bg'] = "Липсващ индекс за търсене";
$text[4]['ru'] = "Поисковый индекс отсутствует";
$text[4]['mk'] = "пребаруваниот индекс е промашуван.";

$text[5]['de'] = "Sie m&uuml;ssen einen Suchbegriff eingeben.";
$text[5]['en'] = "You have to enter a search expression.";
$text[5]['al'] = "Duhet te vendoset kritere kerkimi.";
$text[5]['sk'] = "Zadali ste vyh&#318;ad&aacute;van&yacute; vyraz:";
$text[5]['cs'] = "Musíte zadat hledaný výraz.";
$text[5]['hu'] = "&Iacute;rjon be keresend&#337; kifejez&eacute;st!";
$text[5]['me'] = "Morate unijeti izraz za pretra&#382;ivanje.";
$text[5]['ua'] = "Введіть параметри пошуку";
$text[5]['ro'] = "Trebuie sa introduceti un text pentru cautare.";
$text[5]['hr'] = "Morate unijeti pojam za pretraživanje.";
$text[5]['rs'] = "Morate da unesete pojam za pretragu.";
$text[5]['pl'] = "Musisz wpisać wyszukiwane słowo";
$text[5]['it'] = "Devi inserire un termine di ricerca.";
$text[5]['ba'] = "Morate unijeti pojam za pretragu.";
$text[5]['bg'] = "Моля, въведете фраза за търсене";
$text[5]['ru'] = "Вы должны ввести выражение для поиска.";
$text[5]['mk'] = "Морате да внесувате израз за пребарување.";
?>