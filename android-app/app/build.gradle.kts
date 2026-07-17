import java.util.Properties

plugins {
    id("com.android.application")
    id("org.jetbrains.kotlin.android")
}

val localProperties = Properties().apply {
    val localFile = rootProject.file("local.properties")
    if (localFile.exists()) {
        localFile.inputStream().use { load(it) }
    }
}

fun stringProperty(name: String): String {
    return (project.findProperty(name) as String?)
        ?: localProperties.getProperty(name)
        ?: ""
}

android {
    namespace = "com.inspectorroofing.droneproofpilot"
    compileSdk = 35

    defaultConfig {
        applicationId = "com.inspectorroofing.droneproofpilot"
        minSdk = 26
        targetSdk = 35
        versionCode = 4
        versionName = "0.4.0-droneproof"

        manifestPlaceholders["DJI_API_KEY"] = stringProperty("DJI_API_KEY")
    }

    signingConfigs {
        create("release") {
            val releaseStoreFile = stringProperty("RELEASE_STORE_FILE")
            if (releaseStoreFile.isNotBlank()) {
                storeFile = file(releaseStoreFile)
                storePassword = stringProperty("RELEASE_STORE_PASSWORD")
                keyAlias = stringProperty("RELEASE_KEY_ALIAS")
                keyPassword = stringProperty("RELEASE_KEY_PASSWORD")
            }
        }
    }

    buildTypes {
        getByName("release") {
            isMinifyEnabled = false
            signingConfig = signingConfigs.getByName("release")
        }
    }

    compileOptions {
        sourceCompatibility = JavaVersion.VERSION_17
        targetCompatibility = JavaVersion.VERSION_17
    }

    kotlinOptions {
        jvmTarget = "17"
    }
}

dependencies {
    implementation("androidx.core:core-ktx:1.13.1")
    implementation("androidx.appcompat:appcompat:1.7.0")

    // Add DJI Mobile SDK here using the current official DJI instructions for your aircraft.
    // Keep DjiSdkBridge as the boundary so the UI and mission parser stay testable.
}
