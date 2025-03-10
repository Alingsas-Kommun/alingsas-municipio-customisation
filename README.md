# Alingsås tillägg för Municipio-anpassning

Ett WordPress-tillägg som ger anpassad funktionalitet och förbättringar för Alingsås kommuns Municipio-baserade webbplats. Innehåller skräddarsydd kod för att uppnå design och innehållsstruktur som varit svår att åstadkomma med grunduppsättningen.

## Teknisk struktur och implementation

Tillägget är byggt på objektorienterad princip och använder huvudfilen för att läsa in och initiera de klasser som används. Composer tillsammans med packagist.org gör det hela enkelt installerbart i Municipios composer-filer.

För att utveckla och bygga tillägget används Vite. Under utvecklings används Vites utvecklingsserver för att läsa in stilmallar och skript. `dist`-mappen får då inte vara tillgänglig utan måste tas bort, annas läses filerna härifrån. För att starta utvecklingsläge använd `npm run dev`. Används istället `npm run build` för att bygga filerna för att testa produktionsläge.

TODO: Förbättring här skulle kunna vara att använda WordPress environment-variabel tillsammans med `dist`-mappen för att aktivera produktionsfiler. Om miljön är `development` strunta i att inkludera distributionsfilerna även om de finns på plats.

Strukturerat enligt följande princip: 

- **`/acf`**: Definition för fältgrupper
- **`/components`**: Skräddarsydda komponenter
- **`/dist`**: Skapas när tillägget byggs och innehåller stilmallar och skript
- **`/helpers`**: Stödfunktioner som återanvänds av olika funktioner
- **`/includes`**: Merparten av tilläggets funktionalitet ligger här. Allt i denna mapp läses in vid inladdning
- **`/languages`**: Språkfiler (byggs med `npm run make-pot` och översätts med POEdit)
- **`/src`**: Källkod för stilmallar och skriptfiler. Stilmallar är uppdelade och använder Sass CSS
- **`/views`**: Anpassade vyer för sök, evenemang och nyheter

## Nyckelfunktioner

### Allmän extrafunktionalitet

- **Digital anslagstavla**: Ny posttyp för att husera innehåll för digital anslagstavla
- **Webbsändningar**: Stöd för externa webbsändningar
- **Lediga jobb**: Läses in externt från Visma till skräddarsydd posttyp. Integration ligger utanför detta tillägg.
- **Anpassade komponenter**: Skräddarsydda komponenter som inte återfinns i Municipios grundinstallation
- **Skräddarsydda stilmallar och skript**: Läses in för att anpassa utseende och funktionalitet
- **Schemalagda jobb**: Skräddarsydda schemalagda jobb återfinns i `includes/Cron.php`. I dagsläget endast för att avpublicera inlägg för digitala anslagstavlan.

### Stöd för anpassat utseende

Filerna `includes/AppearanceSettings.php` och `helpers/Appearance.php` utgör grunden för de anpassningar som kan göras för utseendet med avseende på särprofiler. Här användaren att skapa en grund som sedan kan appliceras på särskilda sidor och på vissa komponenter. 

- **Särprofiler**: Skapa och tillämpa teman med specifika färger på olika delar av webbplatsen
- **Sökvägsbaserad tillämpning**: Använd teman automatiskt baserat på särskild sökväg
- **Sidspecifik anpassning**: Ställ in tema på specifika sidor
- **CSS-variabler**: Genererar automatiskt CSS-variabler för alla anpassade färger och teman som kan användas i temat

### Utökad sökfunktionalitet

Den grundläggande sökfunktionen är väldigt begränsad och presenterar sökresultat rakt upp och ned. Sökresutatsidan har anpassats och så har även de individuella sökresultaten.

Filen `includes/Search.php` innehåller merparten av koden för detta och den skräddarsydda sidan återfinns i `views/custom-search.blade.php`.

- **Anpassad sökresultatsida**
  - Flikar för innehållstyp
  - Träffantal per innehållstyp
  - Brödsmulor för individuella träffar
  - Stöd för paginering av sökträffar

- **Filtrera sökresultat efter posttyp**
  - Sidor
  - Nyheter
  - Lediga jobb
  - Evenemang
  - Driftinformation

### Evenemangsanpassning

Alingsås ville ha ett skräddarsytt utseende för evenemangskorten vilket möjliggjorts med en egen komponent som har ett tillhörande utseende. Utöver detta har även extra kod lagts till för att evenemangen ska presenteras i korrekt ordning i inläggsmodulen. Här hänvisas till kod från evenemangstillägget för att inte duplicera någon kod. Anpassningen sker generellt i `includes/Events.php`.

- **Anpassad visning i inläggsmodulen**: `views/events.blade.php`
- **Korrekt sortering i inläggsmodulen**: `includes/Events.php`
- **Ny komponent för visning av evenemang**: `components/Event`
- **Stödklass för att formatera data korrekt**: `helpers/Event.php`
- **Korrekt visning på arkivsidan**: `includes/controllers/AkEventTemplate.php`

### Advanced Custom Fields

För extrainställningar och extra fält används tillägget som är branschstandard för ändamålet - Advanced Custom Fields. Detta tillsammans med Helsingsborgs funktion för automatisk import och export och översättning av fältgrupper gör det möjligt att utöka sidor och moduler med extra inställningar. Dessa återfinns i `acf/json` och `acf/php` beroende på önskat format.

- **Fältgrupper**:
  - Sidinställningar
  - Utseendeinställningar
  - Allmänna modulinställningar
  - Inställningar för kortmodulen
  - Inställningar för innehåll på digital anslagstavla
  - Inställningar för webbsändningar

## Version

Current version: 0.1.20

## Författare

Utvecklad av Consid (https://www.consid.se) för Alingsås kommun
