# CourtWatch — udhëzues për stafin

Ky udhëzues është për stafin e organizatës: **monitoruesit**, **redaktorët** (menaxherët e
programit), **analistët** dhe **administratorët**. Versioni në anglisht është
[user-guide-en.md](user-guide-en.md).

## 1. Kyçja

1. Hapni panelin e stafit në `/admin` (p.sh. `https://courtwatch.example.org/admin`).
2. Shkruani email-in dhe fjalëkalimin.
3. **Herën e parë** duhet të konfiguroni verifikimin me dy hapa (2FA):
   - instaloni në telefon një aplikacion autentifikimi (Aegis, Google Authenticator,
     Microsoft Authenticator, 1Password…);
   - shtypni **Set up**, konfirmoni fjalëkalimin, skanoni kodin QR (ose shkruani kodin që shfaqet
     poshtë tij);
   - shkruani kodin me 6 shifra nga aplikacioni dhe shtypni **Enable authenticator app**;
   - **ruani kodet e rikuperimit** në vend të sigurt (në një menaxher fjalëkalimesh ose në letër,
     në sirtar të mbyllur): secili prej tyre zëvendëson aplikacionin një herë nëse humbni telefonin.
4. Pas kësaj, çdo kyçje kërkon kodin me 6 shifra nga aplikacioni.

Mirë ta dini:

- Gjuha (Shqip / English) gjendet në menynë nën inicialet tuaja, lart djathtas.
- Pas **30 minutash pa aktivitet** çkyçeni automatikisht. Dy minuta para kësaj, një dritare ju
  pyet nëse doni të qëndroni të kyçur; aktiviteti në cilëndo skedë i mban të gjitha skedat aktive.
  Formularët ruhen si draft para çkyçjes, që të mos humbni punën.
- Pas disa fjalëkalimeve ose kodeve të gabuara, kyçja bllokohet për disa minuta. Nëse keni humbur
  telefonin dhe kodet e rikuperimit, kërkojini një administratori të rivendosë 2FA-në tuaj.

## 2. Kush çfarë mund të bëjë

| Roli | Mund të bëjë |
| --- | --- |
| Monitorues | Krijon lëndë dhe seanca në gjykatat që i janë caktuar; redakton draftet e veta dhe regjistrat që janë në shqyrtim; nuk fshin |
| Redaktor | Shqyrton, aprovon ose kthen dorëzimet; redakton çdo lëndë; menaxhon të dhënat referuese; publikon raportet |
| Analist | Lexon gjithçka, përdor panelin e analizave dhe eksporton (vetëm lexim) |
| Super administrator | Gjithçka, përfshirë përdoruesit, rolet, aprovimin e hulumtuesve dhe fshirjet |

Paneli tregon vetëm atë që lejon roli juaj, dhe serveri e kontrollon përsëri çdo veprim.

## 3. Menyja

| Grupi | Përmban |
| --- | --- |
| **Paneli** (në krye) | Treguesit dhe grafikët, ose draftet dhe seancat tuaja të kthyera |
| **Monitorimi** | Lëndët, Seancat, Radha e shqyrtimit |
| **Analiza** | Regjistri i eksporteve |
| **Portali publik** | Raporte dhe publikime |
| **Të dhënat referuese** | Gjykatat, degët, departamentet, gjyqtarët, prokurorët, veprat penale dhe listat e tjera |
| **Administrimi** | Përdoruesit, rolet, Hulumtuesit, Regjistri i auditimit, Rishikimi i importit |

Shtypni **Ctrl+K** (ose **Cmd+K**) kudo për të kërkuar: numra lënde, aktakuze ose policie
(gabimet e vogla të shkrimit pranohen), emra gjyqtarësh, vepra penale dhe emra palësh (vetëm emër
i plotë ose mbiemër).

## 4. Lëndët

Lënda regjistrohet **një herë**; çdo seancë lidhet me të, kështu që të dhënat e lëndës nuk
shkruhen më kurrë përsëri.

### Krijimi i një lënde

1. **Monitorimi → Lëndët → Krijo lëndë**.
2. Zgjidhni llojin e lëndës (penale — korrupsion, krime të rënda, e përgjithshme, departamenti
   special; administrative; civile; ekonomike), gjykatën, degën dhe departamentin, gjyqtarin dhe
   prokurorin, si dhe numrat e lëndës.
3. Nëse në atë gjykatë ekziston tashmë një lëndë me të njëjtin numër, një paralajmërim ju ofron
   ta hapni atë: shmangni dublikatat.
4. Lëndët administrative, civile dhe ekonomike kanë fusha shtesë (vendimi, mjeti juridik, kërkesa,
   përfaqësimi…), që shfaqen vetëm për ato lloje.
5. Shtypni **Krijo**. Lënda fillon si **Draft**.

Vlerat e përdorura së fundi (gjykatat, gjyqtarët…) shfaqen të parat në lista.

### Hapësira e lëndës

Kur hapni një lëndë shihni këto skeda:

| Skeda | Çfarë bëni aty |
| --- | --- |
| **Përmbledhje** | Të dhënat e lëndës dhe statusi i saj |
| **Palët dhe akuzat** | Të akuzuarit dhe personat juridikë, palët e dëmtuara, paditësit dhe të paditurit, me gjininë, kombësinë, pozitën, statusin ekonomik, mbrojtjen; dhe akuzat (neni i kodit penal) në secilën fazë |
| **Rrjedha e fazave** | Kallëzimi penal → hetimi → aktakuza → shqyrtimi fillestar → shqyrtimi gjyqësor → aktgjykimi → ankesa → Gjykata Supreme, me data dhe rezultate. Datat jashtë radhës refuzohen me shpjegim |
| **Masat dhe dënimet** | Masat e sigurisë, sekuestrimi dhe konfiskimi (të kërkuara / të aprovuara), dënimet e kërkuara dhe të shqiptuara |
| **Seancat** | Të gjitha seancat e monitoruara të lëndës; këtu shtoni një të re (**Krijo seancë**) |
| **Dokumentet** | Shtojcat (PDF, JPG, PNG deri në 20 MB). Skedarët janë privatë: shkarkimi bëhet me lidhje që skadojnë pas pak minutash |
| **Historiku** | Çdo ndryshim statusi, me komentet e shqyrtuesve |

> [!IMPORTANT]
> Emrat e palëve janë të dhëna personale. Ata ruhen të enkriptuar, nuk shfaqen kurrë në portalin
> publik, në të dhënat për hulumtuesit apo në eksporte, dhe nuk duhet të kopjohen në fusha me tekst
> të lirë si shënimet.

## 5. Regjistrimi i një seance

1. Në lëndë, hapni **Seancat → Krijo seancë** (ose **Monitorimi → Seancat → Krijo seancë** dhe
   zgjidhni lëndën). Gjykata, gjyqtari dhe prokurori plotësohen nga lënda.
2. Përgjigjuni pyetjeve. Pyetjet vijuese shfaqen vetëm kur duhen — për shembull minutat dhe
   shkaku i vonesës vetëm nëse seanca filloi me vonesë, arsyet vetëm nëse u shty, kush përdori
   telefonin vetëm nëse u përdor telefon.
3. Data e seancës nuk mund të jetë në të ardhmen.
4. Formulari **ruan automatikisht një draft** çdo 20 sekonda dhe kur çkyçeni; kur ktheheni,
   drafti rikthehet.
5. Ruajeni seancën, pastaj shtypni **Dorëzo për shqyrtim** kur të jetë e plotë.

### Aplikacioni i monitoruesit (celular, punon pa internet)

Menyja nën inicialet tuaja ka **Aplikacioni i monitoruesit (celular)** (`/m`). Në telefon,
shtojeni në ekranin kryesor ("Install" / "Add to Home Screen").

- Shfaq lëndët e gjykatave tuaja; zgjidhni njërën dhe plotësoni formularin e shkurtër të seancës.
- **Pa internet**, seanca ruhet në telefon ("Pa internet"); dërgohet automatikisht kur kthehet
  lidhja, dhe asnjëherë dy herë.
- **Ruaj draftin** e mban në telefon; **Dërgo për shqyrtim** e dorëzon.
- Mos u çkyçni dhe mos i fshini të dhënat e shfletuesit derisa seancat të jenë dërguar.

## 6. Rrjedha e shqyrtimit

Çdo lëndë dhe seancë kalon nëpër këto statuse:

**Draft → Dorëzuar → Në shqyrtim → Aprovuar → Publikuar**, ose **Kthyer** (me koment) për
korrigjim.

- **Monitoruesit** shtypin **Dorëzo për shqyrtim**. Një regjistër i kthyer tregon komentin e
  shqyrtuesit; korrigjojeni dhe dorëzojeni përsëri.
- **Redaktorët** hapin **Monitorimi → Radha e shqyrtimit** (shenja tregon sa presin), shtypin
  **Fillo shqyrtimin**, shohin **Shiko ndryshimet** (çfarë ndryshoi që nga versioni i fundit i
  shqyrtuar), pastaj:
  - **Aprovo**: regjistri tani llogaritet në panel, në portalin publik (i anonimizuar) dhe në të
    dhënat për hulumtuesit;
  - **Kthe me koment**: komenti është i detyrueshëm dhe monitoruesi njoftohet;
  - **Publiko** e ngrin një regjistër të aprovuar (vetëm Super administratori mund ta redaktojë më);
    **Hiq publikimin** e rihap.
- Njoftimet shfaqen te zilja, lart djathtas, dhe me email.

Vetëm regjistrat **e aprovuar** dhe **të publikuar** përdoren për statistikat, portalin dhe
hulumtimin.

## 7. Listat, filtrat dhe eksportet

- Çdo listë ka fushë kërkimi dhe filtra (gjykata, lloji, statusi, datat, gjyqtari…). Filtrat
  mbeten në adresën e faqes, kështu që lidhjen mund ta ndani me një koleg.
- **Ruaj filtrat** i ruan filtrat aktualë me një emër, për veten ose të ndarë me ekipin.
- Zgjidhni rreshtat për veprimet në grup: cakto monitoruesin, ndrysho statusin (sipas rregullave
  të shqyrtimit), eksporto të zgjedhurat, fshi (vetëm Super administratori).
- **Eksporto** e shkruan listën e filtruar në CSV ose Excel. Çdo eksport regjistrohet te
  **Analiza → Regjistri i eksporteve**, dhe eksportet nuk përmbajnë kurrë emra palësh.

## 8. Paneli i analizave

**Paneli** tregon, për periudhën, gjykatën dhe llojin e lëndës që zgjidhni: seancat me kalimin e
kohës, shtyrjet (shkalla dhe arsyet, sipas gjykatës, gjyqtarit dhe llojit të lëndës), përpikërinë
dhe shkaqet e vonesave, seancat publike dhe të mbyllura, incizimin dhe përkthimin, ditët mesatare
(medianën) ndërmjet fazave, rezultatet e aktakuzave, dënimet dhe lirimet, dënimet e shqiptuara,
marrëveshjet për pranimin e fajësisë, rezultatet e ankesave, si dhe sekuestrimin dhe konfiskimin
e kërkuar dhe të aprovuar. Monitoruesit shohin në vend të kësaj draftet dhe seancat e tyre të
kthyera.

## 9. Të dhënat referuese (redaktorët dhe administratorët)

**Të dhënat referuese** përmbajnë listat që përdoren në formularë (gjykatat, degët,
departamentet, divizionet, prokuroritë, gjyqtarët, prokurorët, monitoruesit, parashtruesit,
kodet dhe veprat penale, kategoritë, kombësitë, pozitat, gjendjet ekonomike, llojet e masave të
sigurisë).

- Çdo element ka emër në shqip dhe në anglisht dhe mund të bëhet joaktiv në vend që të fshihet.
- **Bashko dublikatat**: zgjidhni dublikatat dhe atë që mbetet; çdo lëndë dhe seancë kalon te ai,
  ndërsa të tjerët arkivohen.
- **Importo CSV**: shkarkoni shabllonin, plotësojeni dhe importojeni; një përmbledhje tregon çfarë
  u krijua, u përditësua ose u anashkalua.

## 10. Përmbajtja e portalit publik (redaktorët)

**Portali publik → Raporte dhe publikime**: ngarkoni raportet e organizatës (PDF) me titull dhe
përmbledhje në të dy gjuhët dhe shënoni **Publikuar** për t'i shfaqur në portal. Statistikat dhe
lëndët e anonimizuara të portalit përditësohen vetë nga të dhënat e aprovuara; numrat nën 5
shfaqen si "<5" për të mbrojtur privatësinë.

## 11. Administrimi (Super administratori)

- **Përdoruesit**: krijoni llogari, zgjidhni rolin, u **caktoni gjykata monitoruesve**,
  çaktivizoni ata që largohen (mos i fshini) dhe **rivendosni 2FA-në** për dikë që ka humbur
  telefonin.
- **Rolet**: lejet e secilit rol (ndryshojini me kujdes).
- **Hulumtuesit**: aprovoni, refuzoni (me arsye) ose pezulloni llogaritë e hulumtuesve. Pezullimi
  i revokon menjëherë tokenat e tyre API.
- **Regjistri i auditimit**: kush u kyç (dhe kush dështoi), çfarë u krijua, ndryshua ose fshi,
  eksportet dhe ndryshimet e lejeve.
- **Rishikimi i importit**: vlera nga baza e vjetër që importi nuk arriti t'i hartëzojë. Nëse
  duhet, korrigjoni lëndën e importuar, pastaj shënojeni elementin si të zgjidhur.
- Paneli i radhëve të punës (`/horizon`) është për Super administratorët me 2FA.

## 12. Rregulla për të gjithë

- Mos vendosni kurrë të dhëna personale reale në sisteme demo ose testimi, dhe mos dërgoni jashtë
  organizatës eksporte ose pamje ekrani me emra.
- Mos e ndani llogarinë ose kodet 2FA; çkyçuni në kompjuterë të përbashkët.
- Njoftoni menjëherë një administrator nëse humbni telefonin ose dyshoni për problem me llogarinë.

## 13. Ndihmë

| Problemi | Çfarë të bëni |
| --- | --- |
| "Shumë përpjekje" gjatë kyçjes | Prisni minutat që tregohen, pastaj provoni përsëri me kujdes. |
| Humbët telefonin / nuk keni kode | Një administrator ua rivendos 2FA-në; e konfiguroni përsëri në kyçjen e radhës. |
| Nuk e shoh një lëndë | Monitoruesit shohin vetëm lëndët e gjykatave që u janë caktuar; pyesni një administrator. |
| Një datë refuzohet | Datat e fazave duhet të ndjekin radhën e procedurës; lexoni mesazhin. |
| Seanca ka mbetur në telefon | Hapni `/m` me internet; dërgohet automatikisht. Mos i fshini të dhënat e shfletuesit. |
