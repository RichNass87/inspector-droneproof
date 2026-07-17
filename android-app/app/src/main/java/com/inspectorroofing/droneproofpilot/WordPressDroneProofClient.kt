package com.inspectorroofing.droneproofpilot

import android.content.ContentResolver
import android.net.Uri
import org.json.JSONArray
import org.json.JSONObject
import java.io.ByteArrayOutputStream
import java.net.HttpURLConnection
import java.net.URL

data class WordPressJobResult(
    val jobId: String,
    val mission: DroneProofMission,
    val photos: List<DroneProofPhoto>,
    val reportUrl: String
)

data class UploadedPhotoResult(
    val photoId: String,
    val remoteUrl: String
)

class WordPressDroneProofClient(
    private val baseUrl: String,
    private val fieldToken: String,
    private val contentResolver: ContentResolver
) {
    fun latestJob(): WordPressJobResult {
        requireToken()
        val payload = JSONObject(requestBytes("GET", "/field-job/latest"))
        if (!payload.optBoolean("ok")) {
            throw IllegalStateException(payload.optString("message", "No field job found in WordPress."))
        }
        return parseJob(payload)
    }

    fun saveJob(mission: DroneProofMission, photos: List<DroneProofPhoto>): WordPressJobResult {
        requireToken()
        val body = JSONObject()
            .put("source", "DroneProof Android field app")
            .put("jobId", mission.missionId)
            .put("mission", mission.toJson())
            .put("preflight", JSONObject().put("androidFieldReview", true))
            .put("photos", JSONArray().apply {
                photos.forEach { photo ->
                    put(
                        JSONObject()
                            .put("id", photo.id)
                            .put("name", photo.name)
                            .put("url", photo.remoteUrl.orEmpty())
                            .put("size", 0)
                    )
                }
            })
            .put("markers", JSONArray())

        val payload = JSONObject(requestText("POST", "/field-job", body.toString(), "application/json"))
        if (!payload.optBoolean("ok")) {
            throw IllegalStateException(payload.optString("message", "WordPress rejected the field job."))
        }
        return parseJob(payload)
    }

    fun uploadPhoto(jobId: String, photo: DroneProofPhoto): UploadedPhotoResult {
        requireToken()
        val boundary = "DroneProofBoundary${System.currentTimeMillis()}"
        val bytes = buildMultipart(boundary, jobId, photo)
        val payload = JSONObject(requestBytes("POST", "/field-photo", bytes, "multipart/form-data; boundary=$boundary"))
        if (!payload.optBoolean("ok")) {
            throw IllegalStateException(payload.optString("message", "WordPress rejected ${photo.name}."))
        }
        val item = payload.optJSONObject("photo") ?: JSONObject()
        return UploadedPhotoResult(
            photoId = item.optString("id", photo.id),
            remoteUrl = item.optString("url", "")
        )
    }

    private fun parseJob(payload: JSONObject): WordPressJobResult {
        val job = payload.optJSONObject("job") ?: JSONObject()
        val missionObject = job.optJSONObject("mission")
            ?: JSONObject().put("missionId", payload.optString("jobId", "DroneProof Mission"))
        val mission = DroneProofMissionParser.parse(missionObject)
        val photos = mutableListOf<DroneProofPhoto>()
        val photoItems = job.optJSONArray("photos") ?: JSONArray()
        for (index in 0 until photoItems.length()) {
            val item = photoItems.optJSONObject(index) ?: continue
            val url = item.optString("url", "")
            photos.add(
                DroneProofPhoto(
                    id = item.optString("id", "P-${index + 1}"),
                    name = item.optString("name", "roof-photo-${index + 1}.jpg"),
                    uri = if (url.isBlank()) Uri.EMPTY else Uri.parse(url),
                    remoteUrl = url.ifBlank { null }
                )
            )
        }
        return WordPressJobResult(
            jobId = payload.optString("jobId", mission.missionId),
            mission = mission,
            photos = photos,
            reportUrl = payload.optString("reportUrl", "")
        )
    }

    private fun requestText(method: String, path: String, body: String? = null, contentType: String? = null): String {
        return requestBytes(method, path, body?.toByteArray(Charsets.UTF_8), contentType)
    }

    private fun requestBytes(method: String, path: String, body: ByteArray? = null, contentType: String? = null): String {
        val connection = (URL(baseUrl + path).openConnection() as HttpURLConnection).apply {
            requestMethod = method
            connectTimeout = 15000
            readTimeout = 25000
            setRequestProperty("Accept", "application/json")
            if (fieldToken.isNotBlank()) {
                setRequestProperty("Authorization", "Bearer $fieldToken")
            }
            if (contentType != null) {
                setRequestProperty("Content-Type", contentType)
            }
            if (body != null) {
                doOutput = true
                outputStream.use { it.write(body) }
            }
        }

        val stream = if (connection.responseCode in 200..299) {
            connection.inputStream
        } else {
            connection.errorStream ?: connection.inputStream
        }
        val text = stream.bufferedReader().use { it.readText() }
        if (connection.responseCode !in 200..299) {
            throw IllegalStateException(text.ifBlank { "HTTP ${connection.responseCode}" })
        }
        return text
    }

    private fun buildMultipart(boundary: String, jobId: String, photo: DroneProofPhoto): ByteArray {
        val line = "\r\n"
        val output = ByteArrayOutputStream()

        fun textPart(name: String, value: String) {
            output.write("--$boundary$line".toByteArray())
            output.write("Content-Disposition: form-data; name=\"$name\"$line$line".toByteArray())
            output.write(value.toByteArray())
            output.write(line.toByteArray())
        }

        textPart("jobId", jobId)
        textPart("photoId", photo.id)

        val mime = contentResolver.getType(photo.uri) ?: "image/jpeg"
        val filename = photo.name.ifBlank { "${photo.id}.jpg" }
        val photoBytes = contentResolver.openInputStream(photo.uri)?.use { it.readBytes() }
            ?: throw IllegalStateException("Cannot open ${photo.name}.")

        output.write("--$boundary$line".toByteArray())
        output.write("Content-Disposition: form-data; name=\"photo\"; filename=\"$filename\"$line".toByteArray())
        output.write("Content-Type: $mime$line$line".toByteArray())
        output.write(photoBytes)
        output.write(line.toByteArray())
        output.write("--$boundary--$line".toByteArray())
        return output.toByteArray()
    }

    private fun requireToken() {
        if (fieldToken.isBlank()) {
            throw IllegalStateException("Paste the private DroneProof field upload token from WordPress setup first.")
        }
    }
}
