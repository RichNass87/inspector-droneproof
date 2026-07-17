# Inspector DroneProof DJI SDK Android Field App

This is the native Android companion field app for Inspector DroneProof. It is designed to load a DroneProof mission from WordPress, capture or import roof photos, sync the field job back to WordPress, upload photos into the WordPress media library, run a pilot safety gate, and provide the bridge point for DJI Mobile SDK wiring.

## What this package does

- Loads `droneproof-mission.json` from app assets as a fallback.
- Loads the latest synced DroneProof job from WordPress.
- Stores the WordPress API base and private field upload token on the Android device.
- Captures roof photos with the camera or imports roof photos from the device.
- Syncs the mission, photo manifest, and field status into WordPress.
- Uploads real roof photos to `/wp-json/inspector-droneproof/v1/field-photo`.
- Parses DroneProof waypoints, altitude, heading, camera pitch, and target roof plane.
- Displays a pilot checklist and blocks flight start until the checks are acknowledged.
- Provides `DjiSdkBridge.kt` as the integration boundary for DJI Mobile SDK.
- Includes `DjiSdkRealAdapter.kt.example` to show where official DJI SDK calls should go.

## What this package does not do yet

- It does not include DJI's SDK binaries.
- It does not arm, launch, or command an aircraft until DJI SDK registration, aircraft connection, mission upload, and field testing are completed.
- It does not replace the pilot's FAA, airspace, VLOS, weather, obstacle, battery, or return-to-home checks.

## DJI setup required

DJI's official docs require a DJI Developer account, an App Key, and a matching Android package name. DJI's Android sample setup also places the App Key in the `com.dji.sdk.API_KEY` AndroidManifest metadata field before compiling and testing with a real controller/aircraft.

Package name in this starter:

```text
com.inspectorroofing.droneproofpilot
```

Google Play Console app record:

```text
App name: Inspector DroneProof Pilot
App ID: 4972784307494654443
Package: com.inspectorroofing.droneproofpilot
```

Set your key in Android Studio/Gradle:

```bash
./gradlew assembleDebug -PDJI_API_KEY=YOUR_DJI_APP_KEY
```

Or add this to a local, uncommitted `local.properties` file in this project folder:

```text
DJI_API_KEY=YOUR_DJI_APP_KEY
```

This project is configured to read `DJI_API_KEY` from `local.properties` first, then from Gradle properties. Keep `local.properties` private and do not upload it to WordPress or GitHub.

## Build

1. Open this folder in Android Studio.
2. Let Android Studio sync Gradle.
3. Add DJI Mobile SDK per the current official DJI instructions for your aircraft and controller.
4. Paste or provide your DJI App Key for package `com.inspectorroofing.droneproofpilot`.
5. Build `app`.
6. Test first with simulator/dry-run mode, then with props removed, then in a controlled field test.

## Mission flow

1. Open the WordPress DroneProof setup page.
2. Copy the field upload token from the secure settings card.
3. Open the Android app.
4. Set API base to:

```text
https://inspector-roofing.com/wp-json/inspector-droneproof/v1
```

5. Paste the field upload token and tap **Save API Settings**.
6. Tap **Load Latest WordPress Job** or use the fallback asset mission.
7. Capture/import roof photos.
8. Tap **Sync Job + Markers**.
9. Tap **Upload Photos to WordPress**.
10. Open the WordPress report PDF for the synced job.
11. Use the DJI bridge only after the official SDK adapter is implemented and verified.

## WordPress routes used

```text
GET  /wp-json/inspector-droneproof/v1/field-job/latest
POST /wp-json/inspector-droneproof/v1/field-job
POST /wp-json/inspector-droneproof/v1/field-photo
GET  /wp-json/inspector-droneproof/v1/report-data
```

Android sends the field token as:

```text
Authorization: Bearer YOUR_FIELD_TOKEN
```

## Source references

- DJI Mobile SDK documentation: https://developer.dji.com/mobile-sdk/documentation/introduction/index.html
- DJI sample/app-key setup: https://developer.dji.com/mobile-sdk/documentation/quick-start/index.html
