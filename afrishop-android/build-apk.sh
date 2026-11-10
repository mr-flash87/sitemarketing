#!/usr/bin/env bash
# Construit l'APK AfriShop (WebView vers https://afrishopp.online) sans Gradle,
# avec les outils Android d'Ubuntu :
#   sudo apt-get install aapt apksigner zipalign android-sdk-platform-23 dalvik-exchange
set -euo pipefail
cd "$(dirname "$0")"
ANDROID_JAR=${ANDROID_JAR:-/usr/lib/android-sdk/platforms/android-23/android.jar}
B=build
rm -rf "$B" && mkdir -p "$B"/{gen,classes,dex}

# 1. Ressources -> R.java
aapt package -f -m -J "$B/gen" -M AndroidManifest.xml -S res -I "$ANDROID_JAR"

# 2. Java -> .class -> classes.dex
javac -source 8 -target 8 -nowarn -bootclasspath "$ANDROID_JAR" -d "$B/classes" \
  $(find src "$B/gen" -name '*.java') 2>&1 | grep -v 'bootstrap\|warning\|JAVA_TOOL_OPTIONS' || true
dalvik-exchange --dex --output="$B/dex/classes.dex" "$B/classes"

# 3. Empaquetage + dex
aapt package -f -M AndroidManifest.xml -S res -I "$ANDROID_JAR" -F "$B/unsigned.apk"
(cd "$B/dex" && aapt add ../unsigned.apk classes.dex >/dev/null)

# 4. Alignement + signature
zipalign -f 4 "$B/unsigned.apk" "$B/aligned.apk"
KS=${KEYSTORE:-afrishop.keystore}
if [ ! -f "$KS" ]; then
  keytool -genkeypair -keystore "$KS" -alias afrishop -keyalg RSA -keysize 2048 \
    -validity 10000 -storepass android -keypass android -dname "CN=AfriShop" 2>/dev/null
fi
apksigner sign --ks "$KS" --ks-key-alias afrishop --ks-pass pass:android \
  --key-pass pass:android --out "$B/AfriShop.apk" "$B/aligned.apk"
apksigner verify "$B/AfriShop.apk"
echo "APK prêt : $(pwd)/$B/AfriShop.apk"
