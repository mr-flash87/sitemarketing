package online.afrishopp.app;

import android.app.Activity;
import android.content.ActivityNotFoundException;
import android.content.Intent;
import android.graphics.Bitmap;
import android.net.Uri;
import android.os.Build;
import android.os.Bundle;
import android.view.View;
import android.webkit.CookieManager;
import android.webkit.DownloadListener;
import android.webkit.ValueCallback;
import android.webkit.WebChromeClient;
import android.webkit.WebResourceError;
import android.webkit.WebResourceRequest;
import android.webkit.WebSettings;
import android.webkit.WebView;
import android.webkit.WebViewClient;
import android.widget.FrameLayout;
import android.widget.ProgressBar;

/**
 * Application AfriShop : affiche https://afrishopp.online dans une WebView
 * plein écran, avec envoi de fichiers (photos produits, pièces KYC),
 * liens WhatsApp / réseaux sociaux ouverts dans leurs applications,
 * et une page hors connexion avec bouton « Réessayer ».
 */
public class MainActivity extends Activity {
    private static final String HOME = "https://afrishopp.online/";
    private static final String HOST = "afrishopp.online";
    private static final int FILE_REQUEST = 42;

    private WebView webView;
    private ProgressBar progress;
    private ValueCallback<Uri[]> fileCallback;
    private boolean showingError = false;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        if (Build.VERSION.SDK_INT >= 21) {
            getWindow().setStatusBarColor(0xFFFF8C42);
        }

        FrameLayout root = new FrameLayout(this);
        webView = new WebView(this);
        progress = new ProgressBar(this, null, android.R.attr.progressBarStyleHorizontal);
        progress.setMax(100);
        root.addView(webView, new FrameLayout.LayoutParams(-1, -1));
        root.addView(progress, new FrameLayout.LayoutParams(-1, dp(3)));
        setContentView(root);

        WebSettings s = webView.getSettings();
        s.setJavaScriptEnabled(true);
        s.setDomStorageEnabled(true);
        s.setDatabaseEnabled(true);
        s.setLoadWithOverviewMode(true);
        s.setUseWideViewPort(true);
        s.setMediaPlaybackRequiresUserGesture(false);
        s.setAllowFileAccess(false);
        // Permet au site de savoir qu'il tourne dans l'app (ex. masquer « Installer l'app »)
        s.setUserAgentString(s.getUserAgentString() + " AfriShopApp/1.0");

        CookieManager cm = CookieManager.getInstance();
        cm.setAcceptCookie(true);
        cm.setAcceptThirdPartyCookies(webView, true);

        webView.setWebViewClient(new Client());
        webView.setWebChromeClient(new Chrome());
        webView.setDownloadListener(new DownloadListener() {
            @Override
            public void onDownloadStart(String url, String ua, String cd, String mime, long len) {
                openExternal(url);
            }
        });

        if (savedInstanceState != null) {
            webView.restoreState(savedInstanceState);
        } else {
            loadFromIntent(getIntent());
        }
    }

    private void loadFromIntent(Intent intent) {
        Uri data = intent != null ? intent.getData() : null;
        if (data != null && isOwnHost(data)) {
            webView.loadUrl(data.toString());
        } else {
            webView.loadUrl(HOME);
        }
    }

    @Override
    protected void onNewIntent(Intent intent) {
        super.onNewIntent(intent);
        if (intent.getData() != null) loadFromIntent(intent);
    }

    private int dp(int v) {
        return (int) (v * getResources().getDisplayMetrics().density);
    }

    private static boolean isOwnHost(Uri u) {
        String h = u.getHost();
        return h != null && (h.equals(HOST) || h.endsWith("." + HOST));
    }

    /** Liens qui doivent s'ouvrir dans une autre application. */
    private static boolean isExternal(Uri u) {
        String scheme = u.getScheme() == null ? "" : u.getScheme().toLowerCase();
        if (!scheme.equals("http") && !scheme.equals("https")) return true; // tel:, mailto:, whatsapp:, intent:...
        String h = u.getHost() == null ? "" : u.getHost().toLowerCase();
        return h.equals("wa.me") || h.endsWith("whatsapp.com")
                || h.endsWith("facebook.com") || h.equals("fb.me") || h.equals("m.me")
                || h.endsWith("instagram.com") || h.endsWith("tiktok.com")
                || h.equals("t.me") || h.endsWith("telegram.me")
                || h.endsWith("youtube.com") || h.equals("youtu.be")
                || h.endsWith("play.google.com") || h.endsWith("maps.google.com");
    }

    private void openExternal(String url) {
        try {
            Intent i;
            if (url.startsWith("intent:")) {
                i = Intent.parseUri(url, Intent.URI_INTENT_SCHEME);
            } else {
                i = new Intent(Intent.ACTION_VIEW, Uri.parse(url));
            }
            i.addCategory(Intent.CATEGORY_BROWSABLE);
            startActivity(i);
        } catch (Exception e) {
            // Aucune application disponible : on ignore
        }
    }

    private void showOffline() {
        showingError = true;
        String html = "<!doctype html><html><head><meta charset='utf-8'>"
                + "<meta name='viewport' content='width=device-width,initial-scale=1'>"
                + "<style>body{font-family:sans-serif;background:#FDFCF8;color:#111B28;display:flex;"
                + "flex-direction:column;align-items:center;justify-content:center;height:100vh;margin:0;"
                + "text-align:center;padding:24px;box-sizing:border-box}"
                + "h1{font-size:22px;margin:16px 0 8px}p{color:#5b6475;margin:0 0 24px}"
                + "a{background:#016ABE;color:#fff;padding:14px 28px;border-radius:12px;"
                + "text-decoration:none;font-weight:bold}.b{color:#F3821D}</style></head><body>"
                + "<div style='font-size:56px'>📶</div><h1>Afri<span class='b'>Shopp</span> est hors ligne</h1>"
                + "<p>Vérifiez votre connexion Internet puis réessayez.</p>"
                + "<a href='" + HOME + "'>Réessayer</a></body></html>";
        webView.loadDataWithBaseURL(HOME, html, "text/html", "utf-8", null);
    }

    private class Client extends WebViewClient {
        @Override
        public boolean shouldOverrideUrlLoading(WebView view, String url) {
            Uri u = Uri.parse(url);
            if (isExternal(u)) {
                openExternal(url);
                return true;
            }
            return false; // le site, les paiements (PayPal, Mobile Money...) restent dans l'app
        }

        @Override
        public void onPageStarted(WebView view, String url, Bitmap favicon) {
            progress.setVisibility(View.VISIBLE);
        }

        @Override
        public void onPageFinished(WebView view, String url) {
            progress.setVisibility(View.GONE);
            CookieManager.getInstance().flush();
        }

        @Override
        public void onReceivedError(WebView view, int code, String desc, String failingUrl) {
            if (failingUrl != null && failingUrl.equals(view.getUrl())) showOffline();
        }

        @Override
        public void onReceivedError(WebView view, WebResourceRequest req, WebResourceError err) {
            if (req.isForMainFrame()) showOffline();
        }
    }

    private class Chrome extends WebChromeClient {
        @Override
        public void onProgressChanged(WebView view, int p) {
            progress.setProgress(p);
        }

        @Override
        public boolean onShowFileChooser(WebView view, ValueCallback<Uri[]> callback,
                                         FileChooserParams params) {
            if (fileCallback != null) fileCallback.onReceiveValue(null);
            fileCallback = callback;
            Intent i = params.createIntent();
            i.addCategory(Intent.CATEGORY_OPENABLE);
            if (params.getMode() == FileChooserParams.MODE_OPEN_MULTIPLE) {
                i.putExtra(Intent.EXTRA_ALLOW_MULTIPLE, true);
            }
            try {
                startActivityForResult(Intent.createChooser(i, "Choisir un fichier"), FILE_REQUEST);
            } catch (ActivityNotFoundException e) {
                fileCallback = null;
                return false;
            }
            return true;
        }
    }

    @Override
    protected void onActivityResult(int requestCode, int resultCode, Intent data) {
        if (requestCode == FILE_REQUEST && fileCallback != null) {
            Uri[] result = null;
            if (resultCode == RESULT_OK && data != null) {
                if (data.getClipData() != null) {
                    int n = data.getClipData().getItemCount();
                    result = new Uri[n];
                    for (int k = 0; k < n; k++) result[k] = data.getClipData().getItemAt(k).getUri();
                } else if (data.getData() != null) {
                    result = new Uri[]{data.getData()};
                }
            }
            fileCallback.onReceiveValue(result);
            fileCallback = null;
            return;
        }
        super.onActivityResult(requestCode, resultCode, data);
    }

    @Override
    protected void onSaveInstanceState(Bundle outState) {
        super.onSaveInstanceState(outState);
        webView.saveState(outState);
    }

    @Override
    protected void onPause() {
        super.onPause();
        CookieManager.getInstance().flush();
    }

    @Override
    public void onBackPressed() {
        if (showingError) {
            showingError = false;
            webView.loadUrl(HOME);
        } else if (webView.canGoBack()) {
            webView.goBack();
        } else {
            super.onBackPressed();
        }
    }
}
