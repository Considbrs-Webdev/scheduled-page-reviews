#!/usr/bin/env node
/**
 * Fill empty msgstr entries in content-ownership-sv_SE.po from the map below.
 * Handles single-line and multiline msgid/msgstr blocks.
 */
import fs from "fs";
import path from "path";
import { fileURLToPath } from "url";

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const poPath = path.join(__dirname, "../resources/languages/content-ownership-sv_SE.po");

/** @type {Record<string, string | [string, string]>} */
const sv = {
  "%d recipient(s)": "%d mottagare",
  " on %s": " på %s",
  "[Content review] %1$d overdue, %2$d upcoming%3$s":
    "[Innehållsgranskning] %1$d försenade, %2$d kommande%3$s",
  "[Content review] %1$d page overdue%2$s": [
    "[Innehållsgranskning] %1$d sida försenad%2$s",
    "[Innehållsgranskning] %1$d sidor försenade%2$s",
  ],
  "[Content review] %1$d page upcoming%2$s": [
    "[Innehållsgranskning] %1$d kommande sida%2$s",
    "[Innehållsgranskning] %1$d kommande sidor%2$s",
  ],
  "+%d more": "+%d till",
  "%d days": "%d dagar",
  Add: "Lägg till",
  "Add email": "Lägg till e-post",
  "Add group": "Lägg till grupp",
  "Add user": "Lägg till användare",
  "alerts@example.com": "varningar@exempel.se",
  "alerts@example.com, ops@example.com": "varningar@exempel.se, drift@exempel.se",
  "Applies to descendant pages: %s": "Gäller undersidor: %s",
  "Automatic scan": "Automatisk skanning",
  "Background mode (schedules batched ticks via WP-Cron)":
    "Bakgrundsläge (schemalägger batchade tick via WP-Cron)",
  "Batch size": "Batchstorlek",
  "Click to preview current members": "Klicka för att förhandsgranska nuvarande medlemmar",
  Collapse: "Fäll ihop",
  "Could not save schedule settings.": "Kunde inte spara schemainställningarna.",
  "Could not save settings.": "Kunde inte spara inställningarna.",
  "Cron batch size": "Cron-batchstorlek",
  "Cron run queued.": "Cron-körning köad.",
  Daily: "Daglig",
  "Days before review date": "Dagar före granskningsdatum",
  "Days between reviews": "Dagar mellan granskningar",
  "Default recipient emails": "Standardmottagare (e-post)",
  "Default recipients": "Standardmottagare",
  "Default review interval (days)": "Standardgranskningsintervall (dagar)",
  "e.g. 180 = twice a year": "t.ex. 180 = två gånger per år",
  "Email address": "E-postadress",
  "Edit:": "Redigera:",
  Expand: "Expandera",
  "Failed to load settings:": "Kunde inte läsa in inställningarna:",
  "Failed to load.": "Kunde inte läsa in.",
  "Failed to run scan.": "Kunde inte köra skanning.",
  "Failed to save.": "Kunde inte spara.",
  "Failed to start cron.": "Kunde inte starta cron.",
  "Failed.": "Misslyckades.",
  "Filter groups…": "Filtrera grupper…",
  General: "Allmänt",
  "General settings": "Allmänna inställningar",
  "Global default:": "Global standard:",
  "Global settings saved.": "Globala inställningar sparade.",
  "Groups (roles)": "Grupper (roller)",
  "Has local rule": "Har lokal regel",
  "How often the scheduled scan is registered.": "Hur ofta den schemalagda skanningen registreras.",
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
  "Loading schedule settings…": "Läser in schemainställningar…",
  "Loading settings…": "Läser in inställningar…",
  "Loading…": "Läser in…",
  "Local override on this page: %s": "Lokal åsidosättning på den här sidan: %s",
  "Local rule": "Lokal regel",
  "Local rule set on this page": "Lokal regel på den här sidan",
  "Main navigation": "Huvudnavigering",
  "Mark as reviewed": "Markera som granskad",
  Members: "Medlemmar",
  Never: "Aldrig",
  "Next review": "Nästa granskning",
  "Next review %1$s (%2$s)": "Nästa granskning %1$s (%2$s)",
  "Next scheduled:": "Nästa schemalagd:",
  "No groups available.": "Inga grupper tillgängliga.",
  "No pages found. Create some pages first.": "Inga sidor hittades. Skapa sidor först.",
  "No recipients assigned.": "Inga mottagare tilldelade.",
  "No users currently in this group.": "Inga användare i den här gruppen just nu.",
  "No users found.": "Inga användare hittades.",
  "nobody configured": "ingen konfigurerad",
  "Nobody configured": "Ingen konfigurerad",
  "Not scheduled": "Inte schemalagd",
  "Notify before due": "Avisera före förfallodatum",
  "Notify days before": "Avisera dagar före",
  "On track": "I fas",
  "Open content ownership settings": "Öppna inställningar för innehållsägarskap",
  "Open in editor": "Öppna i redigeraren",
  "Overdue (%d)": "Försenade (%d)",
  Page: "Sida",
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
  "Review interval": "Granskningsintervall",
  "Review intervals": "Granskningsintervall",
  "Rule set on this page (applies to subpages): %s": "Regel på den här sidan (gäller undersidor): %s",
  "Run cron now": "Kör cron nu",
  "Run scan immediately (recommended when WP-Cron is disabled)":
    "Kör skanning direkt (rekommenderas när WP-Cron är inaktiverat)",
  "Run scan now": "Kör skanning nu",
  "Run scans from the command line or server crontab for reliable execution — especially when WP-Cron is disabled.":
    "Kör skanningar från kommandoraden eller server-crontab för tillförlitlig körning — särskilt när WP-Cron är inaktiverat.",
  "Save changes": "Spara ändringar",
  "Saved.": "Sparat.",
  "Saving…": "Sparar…",
  Schedule: "Schema",
  "Schedule a recurring scan via WordPress cron.":
    "Schemalägg en återkommande skanning via WordPress cron.",
  "Schedule settings saved.": "Schemainställningar sparade.",
  "Scan complete — %1$d pages processed, %2$d emails sent.":
    "Skanning klar — %1$d sidor bearbetade, %2$d e-post skickade.",
  "Scan frequency": "Skanningsfrekvens",
  "Scan time": "Skanningstid",
  "Scanning…": "Skannar…",
  "Search pages…": "Sök sidor…",
  "Search users…": "Sök användare…",
  "Searching…": "Söker…",
  "Send reminders after due date": "Skicka påminnelser efter förfallodatum",
  "Sign in to the %s to review your pages.": "Logga in i %s för att granska dina sidor.",
  "Sign in to the WordPress dashboard to review your pages: %s":
    "Logga in i WordPress-administrationen för att granska dina sidor: %s",
  "Separate multiple addresses with commas or whitespace.":
    "Separera flera adresser med kommatecken eller blanksteg.",
  Settings: "Inställningar",
  "Server crontab — sync scan daily at 22:00":
    "Server-crontab — synkron skanning dagligen kl. 22:00",
  Status: "Status",
  "This message was sent by the Content Ownership plugin.":
    "Detta meddelande skickades av pluginet Content Ownership.",
  "Time of day to register the scan (server time).":
    "Tid på dagen då skanningen registreras (servertid).",
  "Tune how aggressively cron scans the site.": "Styr hur aggressivt cron skannar webbplatsen.",
  "Untitled page #%d": "Sida utan titel #%d",
  Upcoming: "Kommande",
  "Upcoming (%d)": "Kommande (%d)",
  "Update WordPress last modified date": "Uppdatera WordPress senast ändrad",
  "Used when a page has no rule of its own.": "Används när en sida saknar egen regel.",
  "User #%d": "Användare #%d",
  Users: "Användare",
  Weekly: "Veckovis",
  "Who to notify": "Vem ska aviseras",
  "WordPress dashboard": "WordPress-administrationen",
  "WP Cron": "WP Cron",
  "WP-CLI & server crontab": "WP-CLI och server-crontab",
  today: "idag",
  "You have unsaved changes. Discard them and leave this page?":
    "Du har osparade ändringar. Kasta dem och lämna sidan?",
  "Alternative: background kickoff + execute due WP events":
    "Alternativ: bakgrundstart + kör förfallna WP-händelser",
  "Manage page review intervals, notification recipients and hierarchical inheritance for the whole site.":
    "Hantera granskningsintervall, aviseringsmottagare och hierariskt arv för hela webbplatsen.",
  "Select a page from the tree to view and edit its content-ownership rule.":
    "Välj en sida i trädet för att visa och redigera regeln för innehållsägarskap.",
  "Hello %1$s, the following pages on %2$s need your attention.":
    "Hej %1$s, följande sidor på %2$s behöver din uppmärksamhet.",
  "Hello %1$s, the following pages on %2$s (%3$s) need your attention.":
    "Hej %1$s, följande sidor på %2$s (%3$s) behöver din uppmärksamhet.",
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
  "Pages processed per background tick. Larger values finish scheduled scans faster but use more memory.":
    "Sidor som bearbetas per bakgrundstick. Större värden ger snabbare schemalagda skanningar men mer minne.",
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
  "Registers an automatic scan in WordPress at the chosen time. This schedules the scan — it does not run until something executes scheduled WP events.":
    "Registrerar en automatisk skanning i WordPress vid vald tid. Detta schemalägger skanningen — den körs inte förrän något kör schemalagda WP-händelser.",
  "WP-Cron is disabled on this site. Scheduled scans require something to execute due events (for example wp cron event run --due-now from server crontab), or use the WP-CLI examples below.":
    "WP-Cron är inaktiverat på den här webbplatsen. Schemalagda skanningar kräver att något kör förfallna händelser (t.ex. wp cron event run --due-now från server-crontab), eller använd WP-CLI-exemplen nedan.",
  "%d page": ["%d sida", "%d sidor"],
};

function unescapePo(str) {
  return str.replace(/\\n/g, "\n").replace(/\\"/g, '"').replace(/\\\\/g, "\\");
}

function escapePo(str) {
  return str.replace(/\\/g, "\\\\").replace(/"/g, '\\"').replace(/\n/g, "\\n");
}

function readPoString(lines, startIndex, key = "msgid") {
  let i = startIndex;
  let value = "";

  if (lines[i] === `${key} ""`) {
    i += 1;
    while (i < lines.length && lines[i].startsWith('"')) {
      value += unescapePo(lines[i].slice(1, -1));
      i += 1;
    }
    return { value, nextIndex: i };
  }

  const match = lines[i].match(new RegExp(`^${key} "((?:\\\\.|[^"])*)"$`));
  if (!match) {
    return { value: "", nextIndex: i + 1 };
  }
  value = unescapePo(match[1]);
  return { value, nextIndex: i + 1 };
}

function readMsgstrBlock(lines, startIndex, prefix) {
  let i = startIndex;
  if (i >= lines.length || !lines[i].startsWith(prefix)) {
    return { value: null, nextIndex: i, empty: true };
  }

  if (lines[i] === `${prefix} ""`) {
    i += 1;
    let value = "";
    while (i < lines.length && lines[i].startsWith('"')) {
      value += unescapePo(lines[i].slice(1, -1));
      i += 1;
    }
    return { value, nextIndex: i, empty: value === "" };
  }

  const match = lines[i].match(new RegExp(`^${prefix} "((?:\\\\.|[^"])*)"$`));
  if (!match) {
    return { value: null, nextIndex: i + 1, empty: true };
  }
  const value = unescapePo(match[1]);
  return { value, nextIndex: i + 1, empty: value === "" };
}

function formatPoString(prefix, text) {
  if (text.length <= 70 && !text.includes("\n")) {
    return [`${prefix} "${escapePo(text)}"`];
  }

  const out = [`${prefix} ""`];
  let remaining = text;
  while (remaining.length > 0) {
    let chunkSize = Math.min(76, remaining.length);
    if (remaining.length > 76) {
      const slice = remaining.slice(0, 76);
      const breakAt = Math.max(slice.lastIndexOf(" "), slice.lastIndexOf("—"), 40);
      if (breakAt > 0) {
        chunkSize = breakAt + 1;
      }
    }
    const chunk = remaining.slice(0, chunkSize);
    remaining = remaining.slice(chunkSize);
    out.push(`"${escapePo(chunk)}"`);
  }
  return out;
}

function isEmptyMsgstr(lines, index) {
  const block = readMsgstrBlock(lines, index, "msgstr");
  return block.empty;
}

function parseEntries(po) {
  const lines = po.split("\n");
  const entries = [];
  let i = 0;

  while (i < lines.length) {
    if (lines[i].trim() === "") {
      i += 1;
      continue;
    }

    const start = i;
    const comments = [];
    while (i < lines.length && (lines[i].startsWith("#") || lines[i].trim() === "")) {
      if (lines[i].startsWith("#")) {
        comments.push(lines[i]);
      }
      i += 1;
    }

    if (i >= lines.length || !lines[i].startsWith("msgid")) {
      i = Math.max(i, start + 1);
      continue;
    }

    const msgid = readPoString(lines, i, "msgid");
    i = msgid.nextIndex;

    let msgidPlural = null;
    if (i < lines.length && lines[i].startsWith("msgid_plural")) {
      msgidPlural = readPoString(lines, i, "msgid_plural");
      i = msgidPlural.nextIndex;
    }

    const msgstrs = [];
    while (i < lines.length && lines[i].startsWith("msgstr")) {
      const pluralMatch = lines[i].match(/^msgstr\[(\d+)\]/);
      const prefix = pluralMatch ? `msgstr[${pluralMatch[1]}]` : "msgstr";
      const msgstr = readMsgstrBlock(lines, i, prefix);
      msgstrs.push(msgstr);
      i = msgstr.nextIndex;
    }

    entries.push({
      comments,
      msgid: msgid.value,
      msgidPlural: msgidPlural?.value ?? null,
      msgstrs,
      start,
      end: i,
    });
  }

  return { lines, entries };
}

function renderEntry(entry) {
  const out = [...entry.comments.filter((c) => !c.startsWith("#, fuzzy"))];

  if (entry.msgid.includes("\n") || entry.msgid.length > 70) {
    out.push('msgid ""', ...formatPoString("", entry.msgid).slice(1));
  } else {
    out.push(`msgid "${escapePo(entry.msgid)}"`);
  }

  if (entry.msgidPlural) {
    if (entry.msgidPlural.includes("\n") || entry.msgidPlural.length > 70) {
      out.push('msgid_plural ""', ...formatPoString("", entry.msgidPlural).slice(1));
    } else {
      out.push(`msgid_plural "${escapePo(entry.msgidPlural)}"`);
    }
  }

  for (let n = 0; n < entry.msgstrs.length; n += 1) {
    const prefix = entry.msgstrs.length > 1 ? `msgstr[${n}]` : "msgstr";
    const text = entry.msgstrs[n].value ?? "";
    out.push(...formatPoString(prefix, text));
  }

  return out.join("\n");
}

let filled = 0;
let corrected = 0;
const po = fs.readFileSync(poPath, "utf8");
const { entries } = parseEntries(po);
const rendered = [];

for (const entry of entries) {
  if (entry.msgid === "") {
    rendered.push(renderEntry(entry));
    continue;
  }

  const tr = sv[entry.msgid];
  const needsFill = entry.msgstrs.some((m) => m.empty);

  if (Array.isArray(tr) && entry.msgidPlural) {
    entry.msgstrs = [
      { value: tr[0], nextIndex: 0, empty: false },
      { value: tr[1], nextIndex: 0, empty: false },
    ];
    filled += 1;
  } else if (typeof tr === "string") {
    if (needsFill || entry.msgstrs[0]?.value !== tr) {
      entry.msgstrs = [{ value: tr, nextIndex: 0, empty: false }];
      if (needsFill) {
        filled += 1;
      } else {
        corrected += 1;
      }
    }
  } else if (needsFill) {
    // Leave untranslated; pipeline should surface missing keys.
  }

  rendered.push(renderEntry(entry));
}

fs.writeFileSync(poPath, `${rendered.join("\n\n")}\n`);
console.log(
  `Filled ${filled} empty and corrected ${corrected} fuzzy translation(s) in ${path.basename(poPath)}`,
);
