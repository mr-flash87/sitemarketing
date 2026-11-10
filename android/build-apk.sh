#!/usr/bin/env bash
# Construit l'APK Pixel Impact sans Gradle, avec les outils Android d'Ubuntu :
#   sudo apt-get install aapt apksigner zipalign android-sdk-platform-23 dalvik-exchange
set -euo pipefail
cd "$(dirname "$0")"
ANDROID_JAR=${ANDROID_JAR:-/usr/lib/android-sdk/platforms/android-23/android.jar}
B=build
rm -rf "$B" && mkdir -p "$B"/{gen,classes,assets/www,dex}

# 1. Copie du site dans les assets
cp ../Index.html ../style.css ../script.js "$B/assets/www/"
for d in images img assets fonts; do [ -d "../$d" ] && cp -r "../$d" "$B/assets/www/"; done

# 2. Ressources -> R.java
aapt package -f -m -J "$B/gen" -M AndroidManifest.xml -S res -I "$ANDROID_JAR"

# 3. Compilation Java -> .class -> classes.dex
javac -source 8 -target 8 -nowarn -bootclasspath "$ANDROID_JAR" -d "$B/classes" \
  $(find src "$B/gen" -name '*.java') 2>&1 | grep -v 'bootstrap\|warning' || true
dalvik-exchange --dex --output="$B/dex/classes.dex" "$B/classes"

# 4. Empaquetage + dex
aapt package -f -M AndroidManifest.xml -S res -A "$B/assets" -I "$ANDROID_JAR" -F "$B/unsigned.apk"
(cd "$B/dex" && aapt add ../unsigned.apk classes.dex >/dev/null)

# 5. Alignement + signature
zipalign -f 4 "$B/unsigned.apk" "$B/aligned.apk"
KS=${KEYSTORE:-pixelimpact.keystore}
if [ ! -f "$KS" ]; then
  keytool -genkeypair -keystore "$KS" -alias pixelimpact -keyalg RSA -keysize 2048 \
    -validity 10000 -storepass android -keypass android -dname "CN=Pixel Impact" 2>/dev/null
fi
apksigner sign --ks "$KS" --ks-key-alias pixelimpact --ks-pass pass:android \
  --key-pass pass:android --out "$B/PixelImpact.apk" "$B/aligned.apk"
apksigner verify "$B/PixelImpact.apk"
echo "APK prêt : $(pwd)/$B/PixelImpact.apk"
