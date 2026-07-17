# Security policy

Report security issues privately through https://inspector-roofing.com/contact/ rather than a public issue.

Never commit DJI keys, Android signing material, API keys, WordPress tokens, homeowner addresses, customer photos, insurance documents, or production database exports. Rotate a field token if a device is lost or a token is exposed.

The Android application currently stores its configured WordPress endpoint and field token on the device. Production hardening should migrate the token to Android Keystore-backed storage before a public Play release.
