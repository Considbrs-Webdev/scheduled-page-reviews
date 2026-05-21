#!/usr/bin/env node
/**
 * Fill empty msgstr entries in content-ownership-sv_SE.po.
 */
import fs from "fs";
import path from "path";
import { fileURLToPath } from "url";

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const poPath = path.join(__dirname, "../resources/languages/content-ownership-sv_SE.po");

/** @type {Record<string, string | [string, string]>} */
const sv = {
  "%d recipient(s)": "%d mottagare",
  "+%d more": "+%d till",
  Add: "Lägg till",
  "Add email": "Lägg till e-post",
  "Add group": "Lägg till grupp",
  "Add user": "Lägg till användare",
  "alerts@example.com": "varningar@exempel.se",
  "alerts@example.com, ops@example.com": "varningar@exempel.se, drift@exempel.se",
  "Applies to descendant pages: %s": "Gäller undersidor: %s",
  "Click to preview current members": "Klicka för att förhandsgranska nuvarande medlemmar",
  Collapse: "Fäll ihop",
  "Could not save settings.": "Kunde inte spara inställningarna.",
  "Cron batch size": "Cron-batchstorlek",
  "Cron run queued.": "Cron-körning köad.",
  "Days before review date": "Dagar före granskningsdatum",
  "Days between reviews": "Dagar mellan granskningar",
  "Default recipient emails": "Standardmottagare (e-post)",
  "Default recipients": "Standardmottagare",
  "Default review interval (days)": "Standardgranskningsintervall (dagar)",
  "e.g. 180 = twice a year": "t.ex. 180 = två gånger per år",
  "Email address": "E-postadress",
  Expand: "Expandera",
  "Failed to load settings:": "Kunde inte läsa in inställningarna:",
  "Failed to load.": "Kunde inte läsa in.",
  "Failed to save.": "Kunde inte spara.",
  "Failed to start cron.": "Kunde inte starta cron.",
  "Failed.": "Misslyckades.",
  "Filter groups…": "Filtrera grupper…",
  "General settings": "Allmänna inställningar",
  "Global default:": "Global standard:",
  "Global settings saved.": "Globala inställningar sparade.",
  "Groups (roles)": "Grupper (roller)",
  "Has local rule": "Har lokal regel",
  Inherit: "Ärv",
  "Inherited from ancestor": "Ärvs från förfader",
  "Inherited from page #%d:": "Ärvs från sida #%d:",
  "Inherits %1$s from page #%2$d": "Ärver %1$s från sida #%2$d",
  "Inherits %1$s from pages %2$s": "Ärver %1$s från sidorna %2$s",
  "Inherits %s from an ancestor page": "Ärver %s från en förfadersida",
  "Inherits from ancestor": "Ärver från förfader",
  "Inherits settings from an ancestor page": "Ärver inställningar från en förfadersida",
  "Loading page rule…": "Läser in sidregel…",
  "Loading pages…": "Läser in sidor…",
  "Loading settings…": "Läser in inställningar…",
  "Loading…": "Läser in…",
  "Local override on this page: %s": "Lokal åsidosättning på den här sidan: %s",
  "Local rule": "Lokal regel",
  "Local rule set on this page": "Lokal regel på den här sidan",
  Members: "Medlemmar",
  Never: "Aldrig",
  "Next review": "Nästa granskning",
  "Next review %1$s (%2$s)": "Nästa granskning %1$s (%2$s)",
  "No groups available.": "Inga grupper tillgängliga.",
  "No pages found. Create some pages first.": "Inga sidor hittades. Skapa sidor först.",
  "No recipients assigned.": "Inga mottagare tilldelade.",
  "No users currently in this group.": "Inga användare i den här gruppen just nu.",
  "No users found.": "Inga användare hittades.",
  "nobody configured": "ingen konfigurerad",
  "Nobody configured": "Ingen konfigurerad",
  "Notify before due": "Avisera före förfallodatum",
  "Notify days before": "Avisera dagar före",
  "On track": "I fas",
  "Open in editor": "Öppna i redigeraren",
  "Override and apply to subpages": "Åsidosätt och tillämpa på undersidor",
  "Override on this page only": "Åsidosätt endast på den här sidan",
  "Page #%d": "Sida #%d",
  Pages: "Sidor",
  Performance: "Prestanda",
  "Propagates to descendant pages": "Sprids till undersidor",
  "Propagates to subpages": "Sprids till undersidor",
  "Refresh tree": "Uppdatera träd",
  "Reminder cadence (days)": "Påminnelseintervall (dagar)",
  Reminders: "Påminnelser",
  Remove: "Ta bort",
  Reset: "Återställ",
  "Rule set on this page (applies to subpages): %s": "Regel på den här sidan (gäller undersidor): %s",
  "Run cron now": "Kör cron nu",
  "Save changes": "Spara ändringar",
  "Saved.": "Sparat.",
  "Saving…": "Sparar…",
  "Search pages…": "Sök sidor…",
  "Search users…": "Sök användare…",
  "Searching…": "Söker…",
  "Send reminders after due date": "Skicka påminnelser efter förfallodatum",
  "Separate multiple addresses with commas or whitespace.": "Separera flera adresser med kommatecken eller blanksteg.",
  Status: "Status",
  "Tune how aggressively cron scans the site.": "Styr hur aggressivt cron skannar webbplatsen.",
  "Untitled page #%d": "Sida utan titel #%d",
  Upcoming: "Kommande",
  "Update WordPress last modified date": "Uppdatera WordPress senast ändrad",
  "Used when a page has no rule of its own.": "Används när en sida saknar egen regel.",
  "User #%d": "Användare #%d",
  Users: "Användare",
  "Who to notify": "Vem ska aviseras",
  "%d page": ["%d sida", "%d sidor"],
  "%d days": "%d dagar",
  today: "idag",
  "You have unsaved changes. Discard them and leave this page?":
    "Du har osparade ändringar. Kasta dem och lämna sidan?",
  "Manage page review intervals, notification recipients and hierarchical inheritance for the whole site.":
    "Hantera granskningsintervall, aviseringsmottagare och hierariskt arv för hela webbplatsen.",
  "Select a page from the tree to view and edit its content-ownership rule.":
    "Välj en sida i trädet för att visa och redigera regeln för innehållsägarskap.",
  "How often pages must be reviewed and when reminders go out.":
    "Hur ofta sidor ska granskas och när påminnelser skickas.",
  'Window in which a page is "due soon".': 'Fönster då en sida är "snart dags".',
  "Control repeat emails while a page stays due or overdue. Marking a page reviewed starts a new cycle.":
    "Styr upprepade e-postmeddelanden medan en sida är förfallen eller försenad. Att markera en sida granskad startar en ny cykel.",
  "When off, each page is included in at most one digest per review cycle (until marked reviewed). When on, overdue pages can appear again after the cadence interval.":
    "Av: varje sida ingår högst en gång per granskningscykel (tills den markerats granskad). På: försenade sidor kan visas igen efter påminnelseintervallet.",
  "Minimum days before the same overdue page can appear in another digest. Currently tracked per page, not per recipient — see README.":
    "Minsta antal dagar innan samma försenade sida kan visas i ett nytt utskick. Spåras per sida, inte per mottagare — se README.",
  "What happens when someone marks a page as reviewed. Plugin review meta is always stored.":
    "Vad som händer när någon markerar en sida som granskad. Pluginets granskningsmetadata sparas alltid.",
  'Also updates the post\'s modified timestamp (shown as "updated" on the front-end). Does not create a new revision.':
    'Uppdaterar även inläggets ändringstidpunkt (visas som "uppdaterad" på webbplatsen). Skapar ingen ny revision.',
  "Recipients used as a fallback when no per-page or inherited recipient is set. Email addresses only here for now; per-page rules support users and roles too.":
    "Mottagare som reserv när ingen sida- eller ärvd mottagare finns. Endast e-postadresser här för tillfället; sidregler stödjer även användare och roller.",
  "Pages processed per cron tick. Larger values finish full scans faster but use more memory.":
    "Sidor som bearbetas per cron-tick. Större värden ger snabbare full skanning men mer minne.",
  "Add WordPress users, groups, or email addresses. Users and groups receive review reminders and see these pages in their dashboard. Standalone email addresses receive reminders only.":
    "Lägg till WordPress-användare, grupper eller e-postadresser. Användare och grupper får påminnelser och ser sidorna i sin instrumentpanel. Fristående e-post får endast påminnelser.",
  "Use the value from the closest ancestor (or global default).":
    "Använd värdet från närmaste förfader (eller global standard).",
  "Replace the inherited value here. Descendants keep inheriting from above.":
    "Ersätt det ärvda värdet här. Underliggande sidor fortsätter ärva ovanifrån.",
  "Replace the inherited value here AND become the new inherited value for descendants.":
    "Ersätt det ärvda värdet här OCH bli det nya arvvärdet för underliggande sidor.",
  'Show "due soon" in the dashboard widget this many days before the review date.':
    'Visa "snart dags" i instrumentpanelen så här många dagar före granskningsdatumet.',
};

function escapePo(str) {
  return str.replace(/\\/g, "\\\\").replace(/"/g, '\\"').replace(/\n/g, "\\n");
}

let po = fs.readFileSync(poPath, "utf8");
let filled = 0;

// Singular msgstr ""
po = po.replace(
  /^(msgid "((?:\\.|[^"])*)")\n(msgstr) ""$/gm,
  (match, msgidLine, raw) => {
    const msgid = raw.replace(/\\n/g, "\n").replace(/\\"/g, '"');
    const tr = sv[msgid];
    if (tr === undefined) return match;
    if (Array.isArray(tr)) return match;
    filled++;
    return `${msgidLine}\nmsgstr "${escapePo(tr)}"`;
  },
);

// Plural msgstr[0] and [1] empty
po = po.replace(
  /^msgid "((?:\\.|[^"])*)"\nmsgid_plural "((?:\\.|[^"])*)"\nmsgstr\[0\] ""\nmsgstr\[1\] ""$/gm,
  (match, rawSingular, rawPlural) => {
    const singular = rawSingular.replace(/\\n/g, "\n").replace(/\\"/g, '"');
    const tr = sv[singular];
    if (!Array.isArray(tr)) return match;
    filled++;
    return `msgid "${rawSingular}"\nmsgid_plural "${rawPlural}"\nmsgstr[0] "${escapePo(tr[0])}"\nmsgstr[1] "${escapePo(tr[1])}"`;
  },
);

fs.writeFileSync(poPath, po);
console.log(`Filled ${filled} translation(s) in ${path.basename(poPath)}`);
