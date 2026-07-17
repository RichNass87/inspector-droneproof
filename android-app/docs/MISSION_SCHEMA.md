# DroneProof Mission Schema

The WordPress plugin exports missions as JSON. The Android starter currently reads:

```json
{
  "missionId": "IR-CLAIM-001",
  "address": "123 Main St",
  "drone": "DJI Mavic 3 Enterprise",
  "geo": { "ok": true, "lat": 34.0, "lng": -84.0 },
  "flightInstructions": [
    {
      "id": "WP-01",
      "action": "Four-corner overview",
      "altitudeFt": 86,
      "cameraPitchDeg": -58,
      "headingDeg": 35,
      "targetPlane": "ALL",
      "capture": "Property context"
    }
  ]
}
```

GPS-locked production missions should include per-waypoint latitude/longitude fields before upload to the DJI adapter.
