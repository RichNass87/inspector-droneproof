# Testing matrix

| Test | Status | Evidence or next action |
| --- | --- | --- |
| Android source build | Local release bundle exists | Bundle hash recorded in `artifacts/android-artifact.json` |
| Package/target SDK | Verified from source | `com.inspectorroofing.droneproofpilot`, target SDK 35 |
| Secret scan | Pass for staged release | Validator scans public text and metadata; signing material remains excluded |
| Mission parsing | Source present | Fallback JSON and Kotlin parser included |
| WordPress client | Source present | Authorized load, sync, photo upload, and report-data routes included |
| Pilot checklist gate | Source present | Checklist must be acknowledged before the bridge reports ready |
| Direct DJI control | Not implemented | No DJI SDK binary or real adapter in the release |
| Android hardware test | Not recorded | Required before production promotion |
| Play pre-launch report | Not recorded | Required before production promotion |
| Closed-test requirement | Not recorded | Complete if required by the Play developer account |
| Privacy and terms live-page check | Deployment required | WordPress plugin creates or repairs the two pages |

Passing the repository validator does not replace device, console, pre-launch, accessibility, security, or field-safety testing.
