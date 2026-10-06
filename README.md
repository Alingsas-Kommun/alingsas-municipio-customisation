# Alingsås anpassningar för Municipio

Ett WordPress-tillägg med verksamhets- och designanpassningar för Alingsås kommuns webbplats, som bygger på temat Municipio. Tillägget kompletterar Municipio och övriga installerade tillägg; det ersätter inte deras grundfunktionalitet.

## Förutsättningar

- WordPress med temat [Municipio](https://github.com/helsingborg-stad/Municipio).
- PHP 8.1 eller senare. Detta krävs bland annat av söktillägget `typesense-search`.
- Advanced Custom Fields och ACF Export Manager för tilläggets fältgrupper och inställningar.
- Modularity för de modulanpassningar som beskrivs nedan.
- `helsingborg-stad/api-event-manager-integration` för evenemangsfunktionen.

PHP-beroenden deklareras i `composer.json`. I den här driftsmiljön läses filen in av rotprojektets Composer-konfiguration, vilket också installerar beroendet `considbrs-webdev/typesense-search` som ett separat WordPress-tillägg.

## Sök

Webbplatsens sök tillhandahålls av paketet [`considbrs-webdev/typesense-search`](https://github.com/Considbrs-Webdev/typesense-search), inte av detta tillägg. Paketet indexerar valt WordPress-innehåll i Typesense och tillhandahåller både sökresultatsida och snabbsökning.

Konfigurera anslutning, innehållstyper, fasetter och snabbsökning under **Inställningar → Typesense Search**. Anslutningsuppgifter bör ligga i miljökonfigurationen med följande konstanter, så att administrativa nycklar inte sparas eller hanteras i WordPress-gränssnittet:

```php
define('TYPESENSE_HOST', 'https://search.example.se');
define('TYPESENSE_COLLECTION', 'alingsas');
define('TYPESENSE_ADMIN_KEY', '...');
define('TYPESENSE_SEARCH_KEY', '...');
// Valfritt om den publika adressen skiljer sig från den interna:
define('TYPESENSE_FRONTEND_HOST', 'https://search.example.se');
```

Efter ändringar av indexets schema eller de innehållstyper som ska indexeras behöver indexet byggas om. Exempel:

```bash
wp typesense rebuild --yes
```

För en fullständig beskrivning av inställningar, indexering och WP-CLI-kommandon, se [dokumentationen för typesense-search](vendor/considbrs-webdev/typesense-search/README.md).

Detta tillägg innehåller endast `includes/Search.php`, som anpassar söksidans rubrik till Municipios benämning för sökresultat. Den tidigare egna sökresultatsidan och dess posttypsfilter används inte längre.

## Mediesökning via WP-CLI

`alingsas find-unused-images` och `alingsas find-unused-pdfs` söker efter referenser och skriver rårapporter. De sparar tidpunkten för senaste lyckade kontroll per bilaga, men markerar inga bilagor som oanvända och raderar inga filer. Kör först ett begränsat urval på servern och följ loggen samt webbplatsens svarstider:

```sh
wp --url=https://www.alingsas.se alingsas find-unused-images --limit=100 --pause-ms=1000 --output=/sökväg/till/körning/image-report-raw.json
wp --url=https://www.alingsas.se alingsas find-unused-pdfs --limit=100 --pause-ms=1000 --output=/sökväg/till/körning/pdf-report-raw.json
```

Ange även installationens `--path` när kommandona körs från en annan katalog. `--limit=100` väljer de 100 senaste bilagorna enligt ID som **inte har kontrollerats de senaste sju dygnen**. Nästa körning fortsätter med nästa tillgängliga urval. När samtliga är kontrollerade blir rapporten tom tills någon kontroll har blivit sju dygn gammal eller nya bilagor tillkommer. Detta gäller både använda och oanvända bilagor, även när `--ids` anges.

Använd `--force` för att bortse från tidigare kontroller. Övriga urval, såsom filtyp, `--ids` och `--limit`, gäller fortfarande:

```sh
wp alingsas find-unused-images --limit=100 --force
wp alingsas find-unused-pdfs --ids=123,456 --force
```

`--limit=all` kontrollerar alla bilagor som är aktuella för kontroll; `--limit=all --force` kontrollerar samtliga utan veckofiltret. Sjudygnsintervallet är rullande, inte knutet till en kalendervecka. Filerna väljs fortfarande med nyaste ID först: endast en körning med `--limit=100` per vecka garanterar därför inte att hela biblioteket till slut täcks. Kör flera omgångar inom veckan eller använd `--limit=all` när hela biblioteket ska gås igenom.

- `--pause-ms` är pausen **mellan batcherna**, normalt 500 ms, med tillåtet intervall 0–60000. Pausen begränsar inte en enskild SQL-frågas körtid. `--batch-size` är fortfarande antalet bilagor per batch, normalt 50; det begränsar inte antalet databasrader som söks igenom.
- Ett gemensamt, icke-väntande fillås stoppar samtidiga bild- och PDF-sökningar för samma databas på samma värd. Även olika webbplatser i samma databas delar låset. Kör med samma systemanvändare och temporärkatalog. En andra körning avslutas med felkod, så schemalägg dem **sekventiellt i samma skript**. Låset samordnar inte andra jobb, exempelvis Typesense, och är inte ett distribuerat lås mellan servrar.
- Varje SQL-resultat kontrolleras för databasfel. Ett fel stoppar sökningen med felkod och ingen ny rapport publiceras. Även fel vid JSON-kodning eller rapportskrivning ger felkod. Använd `set -e` i körskriptet, eller `&&` mellan kommandon, så att efterföljande steg inte använder en gammal rapport efter ett fel.
- Rapporten skrivs först till en temporär fil i målkatalogen och ersätts sedan atomiskt. En tidigare rapport ligger kvar om sökningen misslyckas. Använd en separat katalog per körning och kontrollera rapportens `generated_at`. En lyckad sökning utan bilagor skriver en tom rapport i stället för att lämna gamla kandidater kvar.
- Tidpunkten sparas som Unix-tid i bilagans postmeta `_alingsas_media_last_scanned_at`, separat för respektive webbplats. Den sparas först **efter att hela rapporten har skrivits**. SQL- eller rapportfel gör därför inte att bilagor hoppas över vid nästa försök. Om själva sparandet av historiken misslyckas finns rapporten kvar, kommandot returnerar felkod och bilagor utan sparad historik kontrolleras igen. Kontrollens starttid används så att en veckokörning kan kontrollera bilagorna vid samma starttid nästa vecka.
- Varje rapport innehåller endast den aktuella körningens urval; tidigare omgångar slås inte ihop. Använd olika `--output` eller separata körningskataloger för att bevara samtliga omgångars resultat. Historiken sparas oberoende av rapportens sökväg.
- Loggen visar tid per batch, sammanlagd SQL-tid, antal frågor och minnesåtgång. Frågor som tar minst fem sekunder ger en varning. Rapportens metadata innehåller även `scan_complete`, `database_query_count` och `database_query_seconds`.

Låsfilen ligger i PHP:s temporärkatalog och lämnas kvar efter körningen för att undvika låsningsrace. Det aktiva låset släpps när körningen avslutas; en kvarliggande fil betyder inte att en sökning fortfarande körs. Radera inte låsfilen medan en körning pågår.

Rårapporterna används därefter av `check-unused-images` respektive `check-unused-pdfs` tillsammans med en aktuell Worddown-export. Fortsätt sedan med manuell granskning och `mark-unused-*`. En fil utan hittad referens är en granskningskandidat, inte ett bevis på att filen säkert kan tas bort.

Skydden kan testas utan WordPress eller anslutning till en serverdatabas, från tilläggets katalog. Testerna använder SQLite i minnet för urvalsfrågorna och kräver PHP-tillägget `pdo_sqlite`:

```sh
php tests/media-scan-safety.php
```

## Funktioner

### Utseende och sidinställningar

- En inställningssida under **Utseende → Alingsås** för egna färger, teman och temaval baserat på URL-sökväg.
- Teman kan väljas per sida och genereras som CSS-variabler på webbplatsen.
- Extra sidinställningar för att dölja titel, brödsmulor eller högerspalt.
- Högerspalten visas som standard på enskilda innehållssidor, om den inte uttryckligen har dolts.

### Modularity och komponenter

- Extra modulinställningar för bakgrundsremsa, över- och undermarginal samt ankarlänk.
- Inställningar för kort, inlay-listor och manuella inmatningsmoduler.
- Fritextsökning i modulen Manuell inmatning när den aktiveras i modulens inställningar.
- En egen komponent för evenemangskort.
- Anpassade vyer och komponentvägar för Modularity och Blade.

### Evenemang och lediga jobb

- Anpassad visning och sortering av evenemang, inklusive evenemangskort i inläggsmodulen.
- Anpassade mallar för evenemang och enskilda lediga jobb.
- Länkar från evenemang till filtrerade evenemangsarkiv.
- Kompletterande information om exempelvis anställningsstart, anställningsform och anställningsperiod på lediga jobb.

### Digital anslagstavla, nyheter och webbsändningar

- Anpassningar för innehållstypen `anslagstavla`: validering, administration, visning av anslags- och arkivdatum samt hantering av arkiverade anslag.
- Schemalagd arkivering av anslag enligt deras inställningar.
- Egen status för arkiverade nyheter och en inställning för hur många dagar publicerade nyheter ska ligga kvar innan de arkiveras.
- Inbäddning av webbsändningar från ett ACF-fält och avstängda kommentarer för innehållstypen `webcast`.

### Media, import och övriga anpassningar

- WP-CLI-jobb för att hitta, markera, kontrollera och radera oanvända bilder och PDF:er. Mediebiblioteket kan filtreras på markerade, oanvända mediafiler.
- Stöd för att flytta temporära avpubliceringsfält till rätt metadata efter import via WP All Import.
- Anpassningar av tillgänglighetsmeny, knappar, postutdrag, översättningar och Content Security Policy.

## Struktur

- `acf/` – exporterade ACF-fältgrupper i PHP- och JSON-format.
- `components/` – egna Blade-komponenter, bland annat evenemangskortet.
- `data/` – rapportmallar och genererade rapporter för mediekontroller.
- `dist/` – byggda JavaScript- och CSS-filer från Vite. Skapas av byggsteget.
- `helpers/` – återanvändbara hjälpklasser för bland annat utseende och evenemang.
- `includes/` – tilläggets PHP-funktionalitet. Filerna läses in automatiskt från huvudfilen.
- `languages/` – översättningsfiler för textdomänen `municipio-customisation`.
- `src/` – källkod för JavaScript, Sass och administrations-CSS.
- `views/` – mallöverskrivningar för evenemang, lediga jobb och moduler.

## Utveckling och bygge

Installera JavaScript-beroenden och starta Vites utvecklingsserver:

```bash
npm ci
npm run dev
```

Bygg produktionsfiler:

```bash
npm run build
```

I utvecklingsmiljö (`wp_get_environment_type() === 'development'`) laddas Vites utvecklingsserver. I övriga miljöer laddas filer från `dist/manifest.json`. Bygg därför om tillgångarna innan de tas i bruk i produktion.

Skapa om språkunderlaget efter ändringar i översättningsbara strängar:

```bash
npm run make-pot
```

## Versionering

Projektet följer [semantisk versionshantering](https://semver.org/) (`MAJOR.MINOR.PATCH`). Releasetaggar använder samma versionsnummer, till exempel `1.0.0`. Versionen ska vara densamma i `municipio-customisation.php`, `Plugin::VERSION` och `composer.json`.

Aktuell version är **1.0.2**.

## Författare

Utvecklad av [Consid](https://www.consid.se) för Alingsås kommun.
