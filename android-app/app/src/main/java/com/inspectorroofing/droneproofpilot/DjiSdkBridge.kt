package com.inspectorroofing.droneproofpilot

import android.content.Context

data class DjiBridgeStatus(
    val ok: Boolean,
    val title: String,
    val detail: String
)

class DjiSdkBridge(private val context: Context) {
    fun registerSdk(): DjiBridgeStatus {
        val appKey = readManifestAppKey()
        if (appKey.isBlank()) {
            return DjiBridgeStatus(
                ok = false,
                title = "DJI App Key missing",
                detail = "Create a DJI Developer App Key for com.inspectorroofing.droneproofpilot and provide it as DJI_API_KEY before building."
            )
        }

        return DjiBridgeStatus(
            ok = true,
            title = "SDK bridge ready for official DJI wiring",
            detail = "App Key is present. Replace this dry-run bridge with DJI Mobile SDK registration and aircraft callbacks."
        )
    }

    fun prepareMission(mission: DroneProofMission): DjiBridgeStatus {
        if (!mission.hasGpsLock) {
            return DjiBridgeStatus(
                ok = false,
                title = "GPS mission required",
                detail = "Export a GPS-locked mission from WordPress before uploading waypoints to DJI."
            )
        }

        if (mission.waypoints.size < 5) {
            return DjiBridgeStatus(
                ok = false,
                title = "More capture points needed",
                detail = "Add overview, front, rear, returns, ridge/detail, and collateral coverage."
            )
        }

        return DjiBridgeStatus(
            ok = true,
            title = "Mission ready for DJI upload adapter",
            detail = "${mission.waypoints.size} waypoints parsed. Pilot approval is still required before launch."
        )
    }

    fun startMissionLocked(): DjiBridgeStatus {
        return DjiBridgeStatus(
            ok = false,
            title = "Start locked in starter build",
            detail = "Direct aircraft command is intentionally disabled until the official DJI SDK adapter is implemented and field-tested."
        )
    }

    private fun readManifestAppKey(): String {
        val info = context.packageManager.getApplicationInfo(
            context.packageName,
            android.content.pm.PackageManager.GET_META_DATA
        )
        return info.metaData?.getString("com.dji.sdk.API_KEY").orEmpty()
    }
}
