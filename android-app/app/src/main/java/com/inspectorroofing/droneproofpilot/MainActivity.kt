package com.inspectorroofing.droneproofpilot

import android.Manifest
import android.app.Activity
import android.content.Intent
import android.content.SharedPreferences
import android.content.pm.PackageManager
import android.net.Uri
import android.os.Bundle
import android.provider.MediaStore
import android.view.View
import android.widget.Button
import android.widget.EditText
import android.widget.LinearLayout
import android.widget.ScrollView
import android.widget.TextView
import androidx.core.app.ActivityCompat
import androidx.core.content.ContextCompat
import androidx.core.content.FileProvider
import java.io.File

class MainActivity : Activity() {
    private lateinit var statusView: TextView
    private lateinit var missionView: TextView
    private lateinit var photoView: TextView
    private lateinit var endpointInput: EditText
    private lateinit var tokenInput: EditText
    private lateinit var bridge: DjiSdkBridge
    private lateinit var prefs: SharedPreferences
    private var mission: DroneProofMission? = null
    private val photos = mutableListOf<DroneProofPhoto>()
    private var pendingCameraUri: Uri? = null

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        prefs = getSharedPreferences("droneproof-field", MODE_PRIVATE)
        bridge = DjiSdkBridge(this)
        mission = loadAssetMission()
        setContentView(buildContent())
        renderMission()
        renderPhotos()
        renderStatus(bridge.registerSdk())
    }

    private fun buildContent(): View {
        val root = LinearLayout(this).apply {
            orientation = LinearLayout.VERTICAL
            setPadding(32, 32, 32, 32)
        }

        val title = TextView(this).apply {
            text = "Inspector DroneProof Field"
            textSize = 26f
            setTextColor(0xff071827.toInt())
        }

        statusView = TextView(this).apply {
            textSize = 16f
            setPadding(0, 18, 0, 18)
            setTextColor(0xff526377.toInt())
        }

        endpointInput = EditText(this).apply {
            hint = "WordPress API base"
            setSingleLine(true)
            setText(prefs.getString("endpoint", DEFAULT_ENDPOINT))
        }

        tokenInput = EditText(this).apply {
            hint = "Field upload token"
            setSingleLine(true)
            setText(prefs.getString("token", ""))
        }

        val saveSettingsButton = Button(this).apply {
            text = "Save API Settings"
            setOnClickListener {
                prefs.edit()
                    .putString("endpoint", cleanEndpoint(endpointInput.text.toString()))
                    .putString("token", tokenInput.text.toString().trim())
                    .apply()
                renderStatus(DjiBridgeStatus(true, "Settings saved", "Endpoint and field token are stored on this Android device."))
            }
        }

        val downloadMissionButton = Button(this).apply {
            text = "Load Latest WordPress Job"
            setOnClickListener { loadLatestMissionFromWordPress() }
        }

        val captureButton = Button(this).apply {
            text = "Capture Roof Photo"
            setOnClickListener { capturePhoto() }
        }

        val importButton = Button(this).apply {
            text = "Import Roof Photo"
            setOnClickListener { importPhoto() }
        }

        val syncJobButton = Button(this).apply {
            text = "Sync Job + Markers"
            setOnClickListener { syncJob() }
        }

        val uploadPhotosButton = Button(this).apply {
            text = "Upload Photos to WordPress"
            setOnClickListener { uploadPhotos() }
        }

        val qaButton = Button(this).apply {
            text = "Run Pilot QA"
            setOnClickListener { renderStatus(bridge.prepareMission(currentMission())) }
        }

        val startButton = Button(this).apply {
            text = "Start Mission - Locked"
            setOnClickListener { renderStatus(bridge.startMissionLocked()) }
        }

        missionView = TextView(this).apply {
            textSize = 15f
            setTextColor(0xff071827.toInt())
            setPadding(0, 16, 0, 16)
        }

        photoView = TextView(this).apply {
            textSize = 15f
            setTextColor(0xff071827.toInt())
            setPadding(0, 16, 0, 16)
        }

        root.addView(title)
        root.addView(statusView)
        root.addView(endpointInput)
        root.addView(tokenInput)
        root.addView(saveSettingsButton)
        root.addView(downloadMissionButton)
        root.addView(captureButton)
        root.addView(importButton)
        root.addView(syncJobButton)
        root.addView(uploadPhotosButton)
        root.addView(qaButton)
        root.addView(startButton)
        root.addView(missionView)
        root.addView(photoView)

        return ScrollView(this).apply { addView(root) }
    }

    private fun currentMission(): DroneProofMission {
        return mission ?: DroneProofMission(
            missionId = "IR-FIELD-${System.currentTimeMillis()}",
            address = "No address loaded",
            drone = "DJI aircraft",
            hasGpsLock = false,
            waypoints = emptyList()
        )
    }

    private fun loadAssetMission(): DroneProofMission? {
        return runCatching {
            val json = assets.open("droneproof-mission.json").bufferedReader().use { it.readText() }
            DroneProofMissionParser.parse(json)
        }.getOrNull()
    }

    private fun client(): WordPressDroneProofClient {
        val endpoint = cleanEndpoint(prefs.getString("endpoint", DEFAULT_ENDPOINT).orEmpty())
        val token = prefs.getString("token", "").orEmpty().trim()
        return WordPressDroneProofClient(endpoint, token, contentResolver)
    }

    private fun loadLatestMissionFromWordPress() {
        runBackground("Loading latest WordPress job") {
            val loaded = client().latestJob()
            mission = loaded.mission
            photos.clear()
            photos.addAll(loaded.photos)
            runOnUiThread {
                renderMission()
                renderPhotos()
                renderStatus(DjiBridgeStatus(true, "Latest job loaded", "${loaded.jobId} is ready for field review."))
            }
        }
    }

    private fun syncJob() {
        runBackground("Syncing field job") {
            val result = client().saveJob(currentMission(), photos)
            runOnUiThread {
                renderStatus(DjiBridgeStatus(true, "Job synced", "${result.jobId} saved. Report: ${result.reportUrl}"))
            }
        }
    }

    private fun uploadPhotos() {
        val localPhotos = photos.filter { it.remoteUrl.isNullOrBlank() }
        if (localPhotos.isEmpty()) {
            renderStatus(DjiBridgeStatus(true, "No new photos", "All selected photos are already uploaded or no photos are selected."))
            return
        }

        runBackground("Uploading photos") {
            var count = 0
            localPhotos.forEach { photo ->
                val uploaded = client().uploadPhoto(currentMission().missionId, photo)
                photo.remoteUrl = uploaded.remoteUrl
                count += 1
                runOnUiThread { renderStatus(DjiBridgeStatus(true, "Uploading photos", "$count/${localPhotos.size} uploaded.")) }
            }
            client().saveJob(currentMission(), photos)
            runOnUiThread {
                renderPhotos()
                renderStatus(DjiBridgeStatus(true, "Photos uploaded", "$count roof photos are stored in WordPress media."))
            }
        }
    }

    private fun capturePhoto() {
        if (ContextCompat.checkSelfPermission(this, Manifest.permission.CAMERA) != PackageManager.PERMISSION_GRANTED) {
            ActivityCompat.requestPermissions(this, arrayOf(Manifest.permission.CAMERA), REQ_CAMERA_PERMISSION)
            return
        }

        val file = File.createTempFile("droneproof-roof-", ".jpg", externalCacheDir ?: cacheDir)
        val uri = FileProvider.getUriForFile(this, "$packageName.fileprovider", file)
        pendingCameraUri = uri
        startActivityForResult(Intent(MediaStore.ACTION_IMAGE_CAPTURE).apply {
            putExtra(MediaStore.EXTRA_OUTPUT, uri)
            addFlags(Intent.FLAG_GRANT_WRITE_URI_PERMISSION or Intent.FLAG_GRANT_READ_URI_PERMISSION)
        }, REQ_CAPTURE_PHOTO)
    }

    private fun importPhoto() {
        startActivityForResult(Intent(Intent.ACTION_OPEN_DOCUMENT).apply {
            addCategory(Intent.CATEGORY_OPENABLE)
            type = "image/*"
            addFlags(Intent.FLAG_GRANT_READ_URI_PERMISSION or Intent.FLAG_GRANT_PERSISTABLE_URI_PERMISSION)
        }, REQ_IMPORT_PHOTO)
    }

    override fun onActivityResult(requestCode: Int, resultCode: Int, data: Intent?) {
        super.onActivityResult(requestCode, resultCode, data)
        if (resultCode != RESULT_OK) return

        val uri = when (requestCode) {
            REQ_CAPTURE_PHOTO -> pendingCameraUri
            REQ_IMPORT_PHOTO -> data?.data
            else -> null
        } ?: return

        if (requestCode == REQ_IMPORT_PHOTO) {
            runCatching {
                contentResolver.takePersistableUriPermission(uri, Intent.FLAG_GRANT_READ_URI_PERMISSION)
            }
        }

        photos.add(
            DroneProofPhoto(
                id = "P-${(photos.size + 1).toString().padStart(3, '0')}",
                name = "roof-photo-${photos.size + 1}.jpg",
                uri = uri
            )
        )
        renderPhotos()
        renderStatus(DjiBridgeStatus(true, "Photo ready", "Photo added locally. Upload it when the field set is ready."))
    }

    private fun runBackground(label: String, block: () -> Unit) {
        renderStatus(DjiBridgeStatus(true, label, "Working..."))
        Thread {
            try {
                block()
            } catch (error: Exception) {
                runOnUiThread {
                    renderStatus(DjiBridgeStatus(false, "Field sync error", error.message ?: "Unknown Android sync error."))
                }
            }
        }.start()
    }

    private fun renderMission() {
        val current = currentMission()
        val waypointText = current.waypoints.joinToString(separator = "\n\n") { waypoint ->
            "${waypoint.id} - ${waypoint.action}\n" +
                "Plane ${waypoint.targetPlane} | ${waypoint.altitudeFt.toInt()} ft | camera ${waypoint.cameraPitchDeg.toInt()} deg | heading ${waypoint.headingDeg.toInt()} deg\n" +
                waypoint.capture
        }
        missionView.text =
            "Mission: ${current.missionId}\n" +
            "Address: ${current.address}\n" +
            "Drone: ${current.drone}\n" +
            "GPS lock: ${if (current.hasGpsLock) "Yes" else "No - planning only"}\n" +
            "Waypoints: ${current.waypoints.size}\n\n" +
            waypointText
    }

    private fun renderPhotos() {
        photoView.text = if (photos.isEmpty()) {
            "Photos: none yet"
        } else {
            photos.joinToString(separator = "\n") { photo ->
                "${photo.id} - ${photo.name} - ${if (photo.remoteUrl.isNullOrBlank()) "local" else "uploaded"}"
            }
        }
    }

    private fun renderStatus(status: DjiBridgeStatus) {
        statusView.text = "${status.title}\n${status.detail}"
        statusView.setTextColor(if (status.ok) 0xff0f75bc.toInt() else 0xffed1b24.toInt())
    }

    companion object {
        private const val DEFAULT_ENDPOINT = "https://inspector-roofing.com/wp-json/inspector-droneproof/v1"
        private const val REQ_IMPORT_PHOTO = 4101
        private const val REQ_CAPTURE_PHOTO = 4102
        private const val REQ_CAMERA_PERMISSION = 4103
    }
}

private fun cleanEndpoint(value: String): String {
    return value.trim().trimEnd('/')
}
