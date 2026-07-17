Inspector DroneProof WordPress Plugin

Version: 6.10.3

Install:

1. In WordPress admin, go to Plugins > Add New > Upload Plugin.
2. Upload inspector-droneproof-v6.10.3.zip.
3. If WordPress says the plugin already exists, choose the replace/current plugin option.
4. Activate the plugin.
5. Open the DroneProof menu in WordPress admin.
6. Review the generated Inspector DroneProof page, then publish it when ready.

Shortcode:

[inspector_droneproof]

What it includes:

- Inspector Roofing branded contractor console.
- Public Inspector AI Tools hub page with links to DroneProof, InstantRoofView, Homeowners AI Toolbelt, AI Visibility Lab, Authority Stack, and related proof/research pages.
- Google dataset schema patch for Atlas Query Intelligence, including description, license, creator, publisher, keywords, and the matching Atlas dataset @id.
- Early public schema repair pass plus rendered-page schema cleaner that fills missing Atlas Dataset description/license fields and removes invalid product-snippet triggers from older authority schema before Google's source and rendered-page tests read the page.
- DroneProof primary image metadata includes name, description, creditText, license, acquireLicensePage, and copyrightNotice to clear Google image metadata optional warnings.
- DroneProof public page now includes a visible software identity block naming Inspector DroneProof as an Android/Web software application, developer Richard Amir Nasser, publisher Inspector Roofing and Restoration, purpose, DJI SDK reference, non-affiliation boundary, and proof-source stack before Wikidata expansion.
- Hosted AI Tools Hub launch kit with official social graphic and post-copy download for GBP, Facebook, LinkedIn, Instagram, and Nextdoor.
- Job, roof, drone, mission, pitch, story, overlap, and address inputs.
- InstantRoofView bridge for logged-in contractors/admins to import saved roof-view address, pitch, stories, roof area, squares, facets, and accuracy data.
- Pasted InstantRoofView JSON or roof report text can be saved into the bridge, then reused as the preferred DroneProof roof file.
- Flight-plan generator with DJI-style capture logic.
- Optional server-side Google geocode route using the saved Google key.
- DJI KML planning-file export for map review/import workflows.
- Litchi waypoint CSV planning-file export.
- Standard CSV waypoint export and mission JSON export.
- Pilot launch gate with airspace, weather, battery, RTH, GPS, obstacle, VLOS, and final pilot approval checks.
- Downloadable preflight TXT checklist.
- Offline field app HTML download with waypoints, packet JSON, and pilot notice.
- Server-side AI QA route using the saved OpenAI key, with local rules fallback.
- FAA/pilot QA reminders for airspace, VLOS, obstacles, battery, GPS, RTH, and waypoint review.
- First-screen live 3D roof/house proof preview.
- More realistic animated 3D-style canvas model with siding, windows, garage, porch, driveway, chimney, dormer, dark shingle roof planes, damage pins, moving drone marker, fallback waypoints, and contractor status rail.
- Downloadable Android DJI field app kit, compiled debug APK, and Play-ready signed release bundle source project.
- DJI SDK bridge package name: com.inspectorroofing.droneproofpilot.
- Google Play Console app record created for Inspector DroneProof Pilot.
- Google Play app ID: 4972784307494654443.
- Local browser photo intake with sample house reference, thumbnails, and multi-photo support.
- WordPress field-job sync route for saving mission, preflight, photo manifest, and damage markers.
- WordPress media upload route for real roof photos from the web console or Android field app.
- Private Android field upload token generated in the DroneProof admin screen.
- Click-to-mark photo damage points with plane, type, severity, and notes.
- Packet JSON export and print report action.
- Public damage report PDF download:
  /wp-admin/admin-post.php?action=inspector_droneproof_damage_report
- Highlighted damage report with roof map, photo panels, severity, slope ID, photo ID, and reviewer notes.
- Admin setup screen with page repair, shortcode, REST API, PDF link, install score, secure OpenAI key settings, and secure Google Maps Platform key settings.

REST routes:

- /wp-json/inspector-droneproof/v1/flight-plan
- /wp-json/inspector-droneproof/v1/geocode
- /wp-json/inspector-droneproof/v1/ai-qa
- /wp-json/inspector-droneproof/v1/field-job
- /wp-json/inspector-droneproof/v1/field-job/latest
- /wp-json/inspector-droneproof/v1/field-photo
- /wp-json/inspector-droneproof/v1/report-data

DJI SDK / Android field app:

- Source kit included as assets/inspector-droneproof-dji-sdk-android-starter.zip.
- Compiled debug APK included as assets/inspector-droneproof-pilot-debug.apk.
- Loads fallback DroneProof mission JSON.
- Loads latest WordPress field job using the private field token.
- Captures/imports roof photos on Android.
- Syncs field job data back to WordPress.
- Uploads real roof photos to the WordPress media library.
- Displays waypoint plan and pilot checklist.
- Includes Android package name com.inspectorroofing.droneproofpilot for DJI Developer App Key setup.
- Matches the Google Play Console app record for Inspector DroneProof Pilot.
- Provides a dry-run DjiSdkBridge and example adapter file for official DJI Mobile SDK wiring.

Secure API key settings:

- The plugin does not hard-code API keys.
- OpenAI and Google keys can be saved, replaced, or removed from the DroneProof admin page.
- Saved keys stay server-side in WordPress options and are not printed into the public page script.
- Use a domain-restricted Google Maps Platform key before connecting public map features.
- Rotate any API key that has been shared in chat, screenshots, email, or other public places.

Important DJI/pilot note:

- This plugin exports planning files and contractor evidence packets.
- It does not directly arm, launch, or command a DJI aircraft from WordPress.
- DJI installation still requires DJI Developer App Key setup, official Mobile SDK integration, compatible hardware, app signing, app-store or enterprise distribution, field testing, and pilot approval.
- Import/review every exported route inside the approved drone app before flight.
- Verify FAA/airspace rules, TFRs, weather, battery, return-to-home altitude, VLOS, obstacles, neighbors, people, vehicles, and local rules before launch.

Notes:

- This is a WordPress-native plugin, not an iframe to the local development site.
- Google map/geocode/aerial layers are prepared as secure settings and can be expanded into a production map/imagery workflow.
- The damage report can now use synced field-job IDs, uploaded photo manifests, and damage markers from WordPress.
- The (TM) mark is product branding in the plugin. Formal trademark registration is a separate legal filing.
