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

I utvecklingsmiljö (`wp_get_environment_type() === 'development'`) laddas Vites utvecklingsserver. I övriga miljöer laddas filer från `dist/.vite/manifest.json`. Bygg därför om tillgångarna innan de tas i bruk i produktion.

Skapa om språkunderlaget efter ändringar i översättningsbara strängar:

```bash
npm run make-pot
```

## Versionering

Projektet följer [semantisk versionshantering](https://semver.org/) (`MAJOR.MINOR.PATCH`). Releasetaggar använder prefixet `v`, till exempel `v1.0.0`. Versionen ska vara densamma i `municipio-customisation.php`, `Plugin::VERSION` och `composer.json`.

Aktuell version är **1.0.0**.

## Författare

Utvecklad av [Consid](https://www.consid.se) för Alingsås kommun.
