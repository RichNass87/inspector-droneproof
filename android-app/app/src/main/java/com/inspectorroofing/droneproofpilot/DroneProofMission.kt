package com.inspectorroofing.droneproofpilot

import org.json.JSONObject
import android.net.Uri
import org.json.JSONArray

data class DroneProofWaypoint(
    val id: String,
    val action: String,
    val altitudeFt: Double,
    val cameraPitchDeg: Double,
    val headingDeg: Double,
    val targetPlane: String,
    val capture: String,
    val latitude: Double? = null,
    val longitude: Double? = null
)

data class DroneProofPhoto(
    val id: String,
    val name: String,
    val uri: Uri,
    var remoteUrl: String? = null
)

data class DroneProofMission(
    val missionId: String,
    val address: String,
    val drone: String,
    val hasGpsLock: Boolean,
    val waypoints: List<DroneProofWaypoint>
) {
    val isReadyForSdkUpload: Boolean
        get() = hasGpsLock && waypoints.size >= 5
}

object DroneProofMissionParser {
    fun parse(json: String): DroneProofMission {
        return parse(JSONObject(json))
    }

    fun parse(root: JSONObject): DroneProofMission {
        val geo = root.optJSONObject("geo")
        val items = root.optJSONArray("flightInstructions")
        val waypoints = buildList {
            if (items != null) {
                for (index in 0 until items.length()) {
                    val item = items.getJSONObject(index)
                    add(
                        DroneProofWaypoint(
                            id = item.optString("id", "WP-${index + 1}"),
                            action = item.optString("action", "Capture point"),
                            altitudeFt = item.optDouble("altitudeFt", 50.0),
                            cameraPitchDeg = item.optDouble("cameraPitchDeg", -70.0),
                            headingDeg = item.optDouble("headingDeg", 0.0),
                            targetPlane = item.optString("targetPlane", "A"),
                            capture = item.optString("capture", ""),
                            latitude = item.optDoubleOrNull("lat"),
                            longitude = item.optDoubleOrNull("lng")
                        )
                    )
                }
            }
        }

        return DroneProofMission(
            missionId = root.optString("missionId", "DroneProof Mission"),
            address = root.optString("address", "No address"),
            drone = root.optString("drone", "DJI aircraft"),
            hasGpsLock = geo?.optBoolean("ok", false) == true,
            waypoints = waypoints
        )
    }
}

fun DroneProofMission.toJson(): JSONObject {
    val root = JSONObject()
    root.put("missionId", missionId)
    root.put("address", address)
    root.put("drone", drone)
    root.put("geo", JSONObject().put("ok", hasGpsLock))
    val items = JSONArray()
    waypoints.forEach { waypoint ->
        items.put(
            JSONObject()
                .put("id", waypoint.id)
                .put("action", waypoint.action)
                .put("altitudeFt", waypoint.altitudeFt)
                .put("cameraPitchDeg", waypoint.cameraPitchDeg)
                .put("headingDeg", waypoint.headingDeg)
                .put("targetPlane", waypoint.targetPlane)
                .put("capture", waypoint.capture)
                .apply {
                    waypoint.latitude?.let { put("lat", it) }
                    waypoint.longitude?.let { put("lng", it) }
                }
        )
    }
    root.put("flightInstructions", items)
    return root
}

private fun JSONObject.optDoubleOrNull(name: String): Double? {
    return if (has(name) && !isNull(name)) optDouble(name) else null
}
