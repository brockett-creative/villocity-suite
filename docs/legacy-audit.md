# Audit of VilloCity Suite 2.9.58 (the Gemini-built version)

Reviewed October 2026 from `villocity-suite_2.zip`: 40 files, about 18,700 lines of PHP with CSS and JS mixed in.
Every PHP file passes a syntax check, and the plugin was activated and tested on WordPress 7.1.3. Findings marked **(verified)** were reproduced on that test install. The rest come from reading the code.

Use this file as the porting checklist. Each module section ends with what to keep and what to fix when it moves into 3.x.

---

## 1. What the plugin is

The plugin is one WordPress plugin with a "hub" admin dashboard and 16 feature modules. Each site picks which modules are on from a checkbox list on the hub. Most modules follow the same pattern: a custom post type, meta boxes, a "hub" admin page with tabs and stats, and one or more shortcodes that print the front-end HTML with inline `<style>` and `<script>`.

| Module (ID) | What it actually does | Size |
|---|---|---|
| Alert Bar (`alert_bar`) | A banner with severity (info, warning or critical), a message, an optional button and a dismiss "X". Only shows where `[villocity_alert_bar]` or `[villocity_ticker]` is placed. | 313 |
| Properties (`properties`) | `voc_property` CPT for IDA real-estate listings (zoning, utilities, site maps) with `[villocity_available_properties]`. | 296 |
| Board & Staff (`staff`) | `bcg_staff` CPT, a grid shortcode, and an AJAX pop-up bio modal. | 1,359 |
| Business Directory (`directory`) | `bcg_directory` CPT and categories, a public "submit your business" form, a single-business template, "super search" across meta and categories, view and click analytics with CSV export, ad slots. Auto-creates a "Business Directory" page. | 2,650 |
| Cemetery (`cemetery`) | `voc_grave` CPT and sections, a searchable records table, a clickable SVG section map, a CSV importer and a mock-data generator. | 556 |
| Resources (`resources`) | `voc_resource` CPT shown as a card grid via `[villocity_resources]`. | 210 |
| Destinations (`destinations`) | `voc_destination` CPT with county and theme taxonomies, a Leaflet map, "nearby sites" and a dummy-data generator. | 1,089 |
| History / Archives (`history`) | `bcg_artifact` CPT for digitized artifacts with an archive grid and "recent artifacts". | 561 |
| Restaurant Menu (`restaurant-menu`) | `voc_menu_item` CPT and categories, multi-size pricing, category hero images, a mock-data generator. Can attach to a directory business. | 619 |
| Timeline (`timeline`) | `voc_timeline_event` CPT and eras, a scroll-animated vertical timeline, a demo generator. | 697 |
| Events (`events`) | `bcg_event` CPT, a public submission form with a "magic link" to edit your own event, auto-rollover of annual events, festival schedule and sponsors, clone-event, mini-feed shortcodes. | 2,658 |
| News (`news`) | Pulls RSS feeds on a schedule, keyword-filters them, stores the articles in a single option, shows a filterable feed, sends a weekly email digest, and tracks clicks. | 2,128 |
| Marketplace (`marketplace`) | `bcg_listing` CPT for yard sales and farm stands, a public submission form with image upload and a magic edit link, an optional Stripe redirect. | 1,404 |
| Departments (`departments`) | `voc_department` CPT with contacts, downloadable forms and a staff roster link. | 613 |
| Nav Menu (`nav-menu`) | Replaces ElementsKit with a custom `Walker_Nav_Menu`, mega-menu options, a mobile drawer and a search modal. | 1,142 |
| Docs (`docs`) | **Not** what the module list says. The hub calls it "Public Documents & Archives: searchable repository for board minutes, local laws, agendas". In reality it's an admin-only help page about how to use the other modules. There is no public documents feature. | 419 |
| WP Login (`wp-login`) | Listed on the hub and on by default, but **the folder doesn't exist** in the zip. | 0 |

The main file (`villocity-suite.php`, 1,880 lines) also does all of the following, whatever modules are on:

- Replaces the site's search results page with its own template (`template_include`, then `get_header()`, its own HTML, `get_footer()` and `exit`).
- Prints CSS variables and a big admin stylesheet inline.
- Adds an "Executive Hub" dashboard that shows counts, an activity log, a Site Kit link card, an "Audit & Compliance" health score and the module checkboxes.
- **Redirects every admin from the WordPress Dashboard to the VilloCity hub**, and redirects admins there after login.
- Forces its menu to the very top of wp-admin, three different ways.
- Purges LiteSpeed, WP Rocket, W3TC and SiteGround caches on many triggers.
- Loads a front-end view and click tracker on every page.

## 2. Overall verdict

The plugin works as a demo. It looks polished, and the feature ideas are good and clearly based on real client needs. That makes it a useful product spec.

It is not a sound base to keep building on, for three reasons:

1. **It has real security and performance bugs.** These include anonymous visitors purging the whole site cache, anonymous file uploads into the media library, and the cron option being rewritten on every page load. Section 3 has the full list.
2. **It fights the host site.** A global CSS reset loads on every front-end and admin page. It takes over the Dashboard and the search page, and Leaflet loads from unpkg on every page.
3. **It isn't maintainable.** HTML, CSS and JS are built as PHP strings (1,516 `!important`, 49 inline `<style>` blocks, 44 inline `<script>` blocks, only 3 properly enqueued scripts). There are 367 `function_exists()` guards papering over load-order problems, almost no i18n (1 translatable string), and duplicate implementations of the same feature (two RSS fetch engines, two staff-bio AJAX handlers).

Recommendation: rebuild module by module on a clean core, keeping the **same post-type names and meta keys** so existing client content carries over. Treat the Gemini code as a spec and a source of copy and markup ideas, not as code to refactor in place.

## 3. Issues, most serious first

### Critical: fix or remove before this goes on more client sites

1. **Any visitor can purge the site's whole page cache, repeatedly. (verified)**
   `POST /wp-json/voc-news/v1/track-click` is public. It updates the `voc_saved_news_articles_db` option, and the main file purges *all* caches whenever any `voc_*` or `villocity_*` option changes (`updated_option` hook). On the test install, 3 anonymous requests caused 3 full purges. On a busy LiteSpeed site that's a trivial way to knock it over. Normal admin saves are affected too: one "Save" on the hub calls `update_option` about 15 times, and each one purges the cache.

2. **Anonymous file uploads into the Media Library.**
   The Events and Marketplace public forms call `media_handle_upload()` for logged-out visitors. The form says "images", but the server accepts anything WordPress allows (PDF, DOCX, audio, video…). There's no size limit, no CAPTCHA and no rate limit. Only the Events form has even a honeypot; the Directory and Marketplace forms have none.

3. **Published listings can be changed without re-approval.**
   The "magic link" edit (Events and Marketplace) calls `wp_update_post()` on an already-published post and leaves it published. Whoever holds the link can change an approved listing's text and photo, and it goes live immediately. The admin only gets an email. Cloning an event also copies the secret edit token onto the clone.

4. **The site can be used as a spam relay.**
   The public forms email a user-supplied address with a user-supplied title in the subject and body ("Your event has been received! (*anything*)"). Bots love this.

5. **The global CSS reset breaks themes and wp-admin.**
   `assets/css/villocity-tailwind.min.css` loads on **every** front-end and admin page. It sets `*{border-width:0}`, `img,svg{display:block}`, restyles `body` and form controls, and defines `.hidden{display:none !important}`, `.flex`, `.grid` and `.block` with `!important`. WordPress admin, Elementor and most themes use those class names.

6. **The cron option is rewritten on every request when the News module has no feeds. (verified)**
   `voc_ensure_news_cron_scheduled()` runs on `init` for every visitor. When no feeds are configured, the "last fetched" time is never set, so on every page load it clears and reschedules the cron event, which is a database write. With feeds configured it also does the RSS fetch inside a visitor's request whenever the last fetch is more than 12 hours old.

### High

7. **View counts are double or triple counted, and each page view writes to the database. (verified)**
   The global tracker's AJAX handler increments the meta, then calls `villocity_log_interaction()`, which increments it again (1 view = 2 on the test install). Directory pages add a third increment in `wp_head`, and rewrite a serialized array of up to 2,000 timestamps in post meta on every view. The tracking endpoints are unauthenticated and unthrottled, so anyone can inflate them. With page caching the `wp_head` counter doesn't run at all, so the numbers are unreliable either way.
8. **The Dashboard and login are hijacked.** Admins can never reach the normal WordPress Dashboard (`index.php` redirects to the hub), and login always lands on the hub. This confuses clients and breaks plugins that add dashboard widgets. Several modules add dashboard widgets that can then never be seen.
9. **The search page is replaced globally.** Any URL with `?s=` or `?bcg_search=` gets the plugin's own page, and `exit` skips the theme's template entirely. The search also force-limits post types site-wide in `pre_get_posts`.
10. **Third-party scripts load site-wide.** Leaflet CSS and JS load from `unpkg.com` on every front-end page (verified), not only on map pages. That's a privacy and performance cost and a third-party dependency. Directory admin loads Font Awesome from cdnjs.
11. **Mock-data generators sit on production screens.** Cemetery ("10 sample burial records"), Restaurant Menu, Timeline, Destinations and Staff all have one-click buttons that publish fake content on a live client site. The Destinations generator fires from a plain GET link with no nonce, so a forged link can trigger it.
12. **Unbounded queries.** There are 46 `posts_per_page => -1` queries. Directory "super search" runs 3 extra full-table queries (including 8 `LIKE` meta clauses) on every search. Events auto-rollover runs on page render, loads every annual event and writes meta.
13. **Saves skip capability checks.** Meta-box save handlers check a nonce but almost none check `current_user_can( 'edit_post', $id )`. Only 8 do.
14. **Staff bios leak drafts.** The public AJAX bio endpoint returns any `bcg_staff` post by ID, including drafts, pending and private ones.
15. **Raw admin input is printed on the front end.** The cemetery SVG polygon code and directory ad slots are saved unfiltered and printed raw. Only admins can set them, but on multisite or with editor roles that becomes stored XSS.

### Medium: correctness and quality

16. **Bugs found while reading:**
    - In Resources, the editor's "Button Link Text" field shows the literal text `' . esc_attr( $btn_text ) . '` (a PHP string inside HTML).
    - The `columns` attribute on `[villocity_resources]` is ignored.
    - On the hub, the "One-click presets" button applies a preset, but the same form post then saves the old field values over it, so the wording never changes.
    - The hub counts `voc_alert` posts, but the alert bar stores options and never creates posts, so the tile always shows 0.
    - The alert bar's "Enable site-wide" checkbox does nothing unless the shortcode is placed somewhere.
    - The module header advertises "Scheduled public notices" and "ICS downloads", but neither exists.
    - The health score has a floor of 50%. It can never show below 50.
    - The Departments hub calls a non-existent icon (`pin`), and the Docs page calls `edit`.
    - `schema.org/Grave` doesn't exist, and interment date is tagged as `deathDate`.
17. **Data storage:** news articles live in one big option array; a custom table or CPT would scale better. View logs are serialized arrays in post meta. A custom table `wp_villocity_activity_log` is created but only partly used.
18. **Hardcoded client data:** Clinton, Kirkland, Clark Mills, "villageofclinton.com" placeholders, Hamilton College news keywords and default locations (32 occurrences).
19. **Branding loads from brockettcreative.com:** the admin logo and menu icon are hotlinked images. If that site is down or the uploads path changes, every client's admin shows broken images.
20. **No uninstall or deactivation cleanup:** cron events keep firing after deactivation. Rewrite rules are flushed from four different places, one of them every 24 hours on `admin_init`.
21. **Accessibility claims are overstated:** "ADA/WCAG compliant" labels appear throughout, but contrast isn't checked (white on `#d97706` amber is about 3.2:1, below 4.5:1). The tracker attaches to any element with `data-id`. Some focus states are removed with `outline: none`.

## 4. Per-module porting notes

Keep the CPT slug, taxonomy slug, meta keys and shortcode names when porting, so existing content and pages keep working. Change everything else freely.

- **Alert Bar:** ported in 3.0 as the reference options-based module. Six options are now one, with an automatic migration. It adds real scheduling, an auto-insert after `<body>`, per-message dismissal and accessible colours.
- **Resources:** ported in 3.0 as the reference CPT module. It fixes the editor bug, the columns attribute and capability checks, and registers its meta for the block editor.
- **Directory:** this is the most valuable module. Port the submission form with spam protection (honeypot plus time-trap, or Turnstile) and **no** anonymous uploads. Replace "super search" with one query, or a meta-index table if it gets slow. Rebuild analytics on a custom table with daily aggregate rows rather than per-view timestamp arrays, or drop it in favour of GA4 events. Make ad slots a capability-checked `wp_kses_post` field.
- **Events:** decide whether to keep it at all. BCG already maintains the separate *BCG Events Calendar* plugin, so merging the two or making this module a thin wrapper would avoid maintaining two calendars. If kept: magic-link edits should set the post back to `pending`, add real ICS export, move rollover to a daily cron, and don't copy edit tokens on clone.
- **Marketplace:** same submission fixes as Events, plus confirm that "Stripe" is only a redirect. Nothing verifies payment, so approval must stay manual.
- **News:** one fetch engine, run from cron only. Store articles as a CPT (`bcg_news` already exists) or a custom table, count clicks without touching options, and never purge cache from a public request.
- **Staff / Departments:** straightforward CPT ports. The bio endpoint must check `post_status === 'publish'`. Consider the REST API or rendering bios into the page instead of AJAX.
- **Cemetery:** move the CSV import to a background batch for large files. Sanitize the SVG with an allow-list (`polygon`, `path`, `rect` and their attributes). Hide the mock generator behind `WP_DEBUG`.
- **Destinations / Properties / History / Timeline / Restaurant Menu:** straightforward. Enqueue Leaflet from the plugin, only on pages with a map, and remove the demo generators from production.
- **Nav Menu:** consider whether this belongs in this plugin. It's theme territory and couples the plugin to header markup. If kept, make it a block or Elementor widget.
- **Docs:** rename it to "Help" in the admin. If clients really need a public documents library (minutes, agendas, local laws), that's a new module to build.
- **Executive Hub dashboard:** rebuild last, once modules expose their own counts through a small interface. Remove the dashboard and login redirects, the forced menu position, and the cache purge on every option save.
- **Global search template:** make it an opt-in module, and use the theme's template with `pre_get_posts` adjustments only.
