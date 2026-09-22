<?php
// Obtener Tasa BCV automáticamente usando PyDolarVenezuela API con caché (2 horas)
$cache_file = __DIR__ . '/bcv_rate.json';
$tasa_bcv = 849.56; // Fallback inicial según lo solicitado
$last_update = date('d/m/Y');

if (file_exists($cache_file) && (time() - filemtime($cache_file)) < 7200) {
    $cache = json_decode(file_get_contents($cache_file), true);
    if ($cache && isset($cache['rate'])) {
        $tasa_bcv = $cache['rate'];
        $last_update = $cache['last_update'];
    }
} else {
    // Fetch API
    $api_url = "https://pydolarvenezuela-api.vercel.app/api/v1/dollar/page?page=bcv";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code == 200 && $response) {
        $data = json_decode($response, true);
        if (isset($data['monitors']['bcv']['price'])) {
            $tasa_bcv = (float) $data['monitors']['bcv']['price'];
            $last_update = date('d/m/Y');
            file_put_contents($cache_file, json_encode(['rate' => $tasa_bcv, 'last_update' => $last_update]));
        }
    } elseif (file_exists($cache_file)) {
        // Fallback a caché vencido
        $cache = json_decode(file_get_contents($cache_file), true);
        if ($cache && isset($cache['rate'])) {
            $tasa_bcv = $cache['rate'];
            $last_update = $cache['last_update'];
        }
    }
}

// ============================================================
// VISTA EXCLUSIVA PARA PWA (SOLO LOGIN)
// ============================================================
if (isset($_GET['pwa']) && $_GET['pwa'] == '1') {
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
        <meta name="theme-color" content="#0f1f3d">
        <title>Acceder - TuInventario</title>
        <link rel="manifest" href="/manifest.json">
        <link rel="icon" href="/icons/icon-192x192.png">
        <link href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700&display=swap" rel="stylesheet">
        <style>
            body { margin: 0; background: #0f1f3d; color: #fff; font-family: 'Figtree', system-ui, sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 24px; box-sizing: border-box; }
            .card { background: #fff; color: #0f1f3d; padding: 32px 24px; border-radius: 24px; width: 100%; max-width: 400px; box-shadow: 0 20px 40px rgba(0,0,0,0.4); text-align: center; }
            .card img { width: 56px; height: 56px; margin-bottom: 16px; border-radius: 12px; }
            h1 { margin: 0 0 8px; font-size: 1.5rem; font-weight: 700; letter-spacing: -0.02em; }
            p { color: #4d5a72; margin: 0 0 24px; font-size: 0.95rem; }
            .form-group { text-align: left; margin-bottom: 16px; position: relative; }
            label { display: block; margin-bottom: 6px; font-size: 0.9rem; font-weight: 600; }
            input { width: 100%; padding: 14px 16px; border: 1.5px solid #d6e0da; border-radius: 12px; background: #f6f9f7; font-size: 1rem; box-sizing: border-box; font-family: inherit; transition: border-color 0.2s; }
            input:focus { outline: none; border-color: #2350d8; background: #fff; }
            .btn { width: 100%; padding: 14px; background: #2350d8; color: #fff; border: none; border-radius: 12px; font-weight: 700; font-size: 1rem; cursor: pointer; margin-top: 8px; display: flex; align-items: center; justify-content: center; gap: 8px; transition: background 0.2s; }
            .btn:hover { background: #1a3ba8; }
            .toggle-pw { position: absolute; right: 12px; top: 38px; background: none; border: none; cursor: pointer; color: #4d5a72; padding: 4px; }
            .loader { display: none; width: 20px; height: 20px; border: 3px solid rgba(255,255,255,.3); border-radius: 50%; border-top-color: #fff; animation: spin 1s ease-in-out infinite; }
            @keyframes spin { to { transform: rotate(360deg); } }
            .btn-loading { pointer-events: none; opacity: 0.8; }
            .btn-loading span:not(.loader) { display: none; }
            .btn-loading .loader { display: inline-block; }
            .error-box { margin-top: 16px; color: #b3261e; font-size: 0.9rem; font-weight: 600; padding: 12px; background: #fde3e0; border-radius: 12px; text-align: left; }
        </style>
    </head>
    <body>
        <div class="card">
            <img src="/iconos_negocio/logo1.png" alt="TuInventario">
            <h1>TuInventario</h1>
            <p>Ingresa tus datos para continuar.</p>
            <form action="/auth/login" method="POST" onsubmit="document.getElementById('pwaSubmitBtn').classList.add('btn-loading');">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                <input type="hidden" name="is_pwa" value="1">
                <div class="form-group">
                    <label>Cédula o Correo</label>
                    <input type="text" name="username" placeholder="V-12345678" required autocomplete="username">
                </div>
                <div class="form-group">
                    <label>Contraseña</label>
                    <input type="password" id="pwaPassword" name="password" placeholder="••••••••" required autocomplete="current-password" style="padding-right: 40px;">
                    <button type="button" class="toggle-pw" onclick="var el=document.getElementById('pwaPassword'); el.type=el.type==='password'?'text':'password';" aria-label="Mostrar contraseña">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                    </button>
                </div>
                <button type="submit" id="pwaSubmitBtn" class="btn">
                    <span>Iniciar Sesión</span>
                    <div class="loader"></div>
                </button>
            </form>
            <?php if(!empty($_SESSION['login_error'])): ?>
                <div class="error-box">
                    <?= htmlspecialchars($_SESSION['login_error']) ?>
                </div>
                <?php unset($_SESSION['login_error']); ?>
            <?php endif; ?>
        </div>
    </body>
    </html>
    <?php
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<!-- Sin user-scalable=no: el usuario puede hacer zoom (accesibilidad) -->
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#0f1f3d">
<title>TuInventario</title>
<meta name="description" content="Sistema de inventario, punto de venta, kardex, créditos y arqueo de caja para tu negocio. Precios en dólares y bolívares. Un solo plan: $3 al mes. Incluye la app PagaPues para Android.">
<meta property="og:title" content="TuInventario · Controla tu negocio en dólares y bolívares">
<meta property="og:description" content="Inventario, punto de venta, cierre de caja y tienda online en un solo sistema. $3 al mes.">
<meta property="og:type" content="website">
<meta property="og:url" content="https://www.tuinventario.app/">
<!-- Agrega aquí tu og:image (1200x630) para que se vea bien al compartir por WhatsApp -->
<link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Crect width='32' height='32' rx='8' fill='%230f1f3d'/%3E%3Ctext x='16' y='23' font-family='Arial' font-weight='800' font-size='20' text-anchor='middle' fill='white'%3ET%3C/text%3E%3C/svg%3E">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,600;12..96,700;12..96,800&family=Figtree:wght@400;500;600;700&display=swap" rel="stylesheet">
<!-- Google Tag Manager: pega aquí tu snippet GTM-NHRNGKB2 (head) -->
<script type="application/ld+json">
{"@context":"https://schema.org","@type":"SoftwareApplication","name":"TuInventario","applicationCategory":"BusinessApplication","operatingSystem":"Web, Android","description":"Sistema de inventario, punto de venta y cobros para negocios.","offers":{"@type":"Offer","price":"3.00","priceCurrency":"USD"}}
</script>
<style>
:root{
  --ink:#0f1f3d;
  --blue:#2350d8;
  --blue-deep:#1a3ba8;
  --blue-soft:#e6edff;
  --paper:#f6f9f7;
  --white:#ffffff;
  --green:#14875f;
  --green-soft:#dcf3e9;
  --amber:#b96f00;
  --amber-soft:#fdf0d2;
  --red:#b3261e;
  --red-soft:#fde3e0;
  --line:#d6e0da;
  --muted:#4d5a72;
  --font-display:"Bricolage Grotesque","Trebuchet MS",system-ui,sans-serif;
  --font-body:"Figtree",system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;
  --wrap:1280px;
}
*,*::before,*::after{box-sizing:border-box}
html{scroll-behavior:smooth;scroll-padding-top:84px}
body{margin:0;background:var(--paper);color:var(--ink);font-family:var(--font-body);font-size:1.0625rem;line-height:1.55;-webkit-font-smoothing:antialiased}
img,svg{max-width:100%;display:block}
a{color:inherit}
h1,h2,h3{font-family:var(--font-display);margin:0;letter-spacing:-.02em;line-height:1.08}
h1{font-size:clamp(2.3rem,5.2vw,3.8rem);font-weight:800}
h2{font-size:clamp(1.75rem,3.2vw,2.45rem);font-weight:700}
h3{font-size:1.25rem;font-weight:700;letter-spacing:-.01em}
p{margin:0}
.wrap{max-width:var(--wrap);margin:0 auto;padding:0 24px}
.lead{font-size:1.15rem;color:var(--muted);max-width:56ch}
:focus-visible{outline:3px solid var(--blue);outline-offset:3px;border-radius:6px}
.skip{position:absolute;left:-999px;top:8px;background:var(--ink);color:#fff;padding:10px 16px;border-radius:10px;z-index:100;text-decoration:none;font-weight:600}
.skip:focus{left:8px}

/* Botones */
.btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;font:600 1rem/1 var(--font-body);padding:15px 22px;border-radius:12px;border:2px solid transparent;text-decoration:none;cursor:pointer;transition:background .15s,border-color .15s,color .15s}
.btn-primary{background:var(--blue);color:#fff}
.btn-primary:hover{background:var(--blue-deep)}
.btn-ghost{border-color:var(--ink);color:var(--ink);background:transparent}
.btn-ghost:hover{background:var(--ink);color:#fff}
.btn-light{background:#fff;color:var(--ink)}
.btn-light:hover{background:var(--blue-soft)}
.btn-sm{padding:11px 16px;font-size:.95rem;border-radius:10px}

/* Header */
.site-header{position:sticky;top:0;z-index:30;background:rgba(246,249,247,.92);backdrop-filter:blur(10px);border-bottom:1px solid var(--line)}
.bar{display:flex;align-items:center;gap:28px;height:68px}
.brand{display:flex;align-items:center;gap:10px;text-decoration:none;font:800 1.35rem/1 var(--font-display);letter-spacing:-.02em}
.brand b{color:var(--blue);font-weight:800}
.brand-mark{width:34px;height:34px;border-radius:10px;background:var(--ink);color:#fff;display:grid;place-items:center;font-size:1.05rem}
.nav{display:flex;gap:22px;margin-left:auto;align-items:center}
.nav a{text-decoration:none;font-weight:600;font-size:.98rem;color:var(--ink);padding:6px 2px}
.nav a:hover{color:var(--blue)}
.bar-actions{display:flex;gap:10px;align-items:center}
.menu-btn{display:none;background:none;border:2px solid var(--ink);border-radius:10px;padding:8px 12px;font:600 .95rem var(--font-body);color:var(--ink);cursor:pointer}

/* Hero */
.hero{padding:52px 0 64px}
.hero-grid{display:grid;grid-template-columns:1fr 1.1fr;gap:56px;align-items:center}
.hero h1{margin-bottom:20px}
.hero .lead{margin-bottom:30px}
.hero-cta{display:flex;flex-wrap:wrap;gap:12px;margin-bottom:26px}
.hero-facts{display:flex;flex-wrap:wrap;gap:8px 22px;color:var(--muted);font-size:.95rem;font-weight:500;padding:0;margin:0;list-style:none}
.hero-facts li{display:flex;align-items:center;gap:8px}
.hero-facts li::before{content:"";width:8px;height:8px;border-radius:50%;background:var(--green)}
.seg{display:flex;flex-wrap:wrap;gap:6px;margin-bottom:12px;align-items:center}
.seg-label{font-size:.9rem;font-weight:600;color:var(--muted);margin-right:4px}
.seg button{font:600 .9rem var(--font-body);padding:8px 14px;border-radius:999px;border:1.5px solid var(--line);background:#fff;color:var(--ink);cursor:pointer}
.seg button:hover{border-color:var(--blue)}
.seg button[aria-pressed="true"]{background:var(--ink);border-color:var(--ink);color:#fff}

/* POS interactivo */
.pos{background:var(--white);border:1px solid var(--line);border-radius:22px;box-shadow:0 18px 50px -20px rgba(15,31,61,.28);overflow:hidden}
.pos-top{display:flex;justify-content:space-between;align-items:center;padding:14px 18px;border-bottom:1px solid var(--line);font-size:.9rem;color:var(--muted);font-weight:500}
.pos-top strong{color:var(--ink);font-weight:700}
.sync{display:inline-flex;align-items:center;gap:7px;color:var(--green);font-weight:600}
.sync::before{content:"";width:8px;height:8px;border-radius:50%;background:var(--green)}
.pos-body{display:grid;grid-template-columns:1.05fr 1fr}
.pos-products{padding:16px;display:grid;grid-template-columns:1fr 1fr;gap:10px;align-content:start;border-right:1px solid var(--line)}
.prod{display:flex;flex-direction:column;gap:6px;text-align:left;background:var(--paper);border:1.5px solid transparent;border-radius:14px;padding:12px;cursor:pointer;font-family:var(--font-body);color:var(--ink);transition:border-color .15s,background .15s}
.prod:hover:not(:disabled){border-color:var(--blue);background:var(--blue-soft)}
.prod:disabled{opacity:.5;cursor:not-allowed}
.prod-tile{width:36px;height:36px;border-radius:10px;display:grid;place-items:center;font:800 1rem var(--font-display);color:#fff}
.prod-name{font-weight:600;font-size:.95rem;line-height:1.2}
.prod-price{font-weight:700;color:var(--blue)}
.stock{align-self:flex-start;font-size:.78rem;font-weight:600;padding:2px 8px;border-radius:999px}
.stock.ok{background:var(--green-soft);color:var(--green)}
.stock.low{background:var(--amber-soft);color:var(--amber)}
.stock.out{background:var(--red-soft);color:var(--red)}
.ticket{padding:16px;display:flex;flex-direction:column;min-height:340px}
.ticket-title{font-weight:700;font-size:.95rem;margin-bottom:10px}
.ticket-lines{flex:1;list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:10px}
.ticket-empty{color:var(--muted);font-size:.93rem;padding:6px 0}
.line{display:grid;grid-template-columns:1fr auto;gap:2px 8px;font-size:.93rem}
.line-name{font-weight:600}
.line-total{font-weight:700;text-align:right}
.qty{display:flex;align-items:center;gap:8px;color:var(--muted)}
.qty button{width:26px;height:26px;border-radius:8px;border:1.5px solid var(--line);background:#fff;color:var(--ink);font-weight:700;cursor:pointer;line-height:1}
.qty button:hover{border-color:var(--blue);color:var(--blue)}
.totals{border-top:1px dashed var(--line);margin-top:14px;padding-top:14px}
.total-usd{font:800 2.3rem/1 var(--font-display);letter-spacing:-.02em}
.total-bs{margin-top:4px;font-weight:700;color:var(--blue);font-size:1.05rem}
.rate{display:inline-block;margin-top:8px;font-size:.78rem;font-weight:600;background:var(--blue-soft);color:var(--blue-deep);padding:3px 9px;border-radius:999px}
.pay{margin-top:14px;width:100%}
.pos-foot{padding:11px 18px;background:var(--paper);border-top:1px solid var(--line);font-size:.88rem;color:var(--muted);display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap}
.pos-foot strong{color:var(--ink)}
.toast{min-height:1.4em;font-size:.9rem;font-weight:600;color:var(--green);margin-top:10px}
.demo-hint{margin-top:12px;font-size:.9rem;color:var(--muted);text-align:center}

/* Secciones */
.sec{padding:84px 0}
.sec-head{margin-bottom:40px;max-width:640px}
.sec-head h2{margin-bottom:12px}

/* Antes / Después */
.vs-wrap{border:1px solid var(--line);border-radius:22px;overflow:hidden;background:var(--white)}
.vs{width:100%;border-collapse:collapse}
.vs th{text-align:left;font:700 1.05rem var(--font-display);padding:18px 24px;border-bottom:1px solid var(--line);width:50%}
.vs th:last-child{background:var(--blue);color:#fff;border-bottom-color:var(--blue)}
.vs th:first-child{color:var(--muted)}
.vs td{padding:18px 24px;vertical-align:top;border-top:1px solid var(--line)}
.vs tbody tr:first-child td{border-top:0}
.vs td:first-child{color:var(--muted)}
.vs td:last-child{background:var(--blue-soft);font-weight:600}

/* Módulos con pestañas */
.tabs{display:flex;gap:6px;border-bottom:1px solid var(--line);margin-bottom:34px;overflow-x:auto;scrollbar-width:thin}
.tab{background:none;border:0;border-bottom:3px solid transparent;padding:12px 16px;font:600 1rem var(--font-body);color:var(--muted);cursor:pointer;margin-bottom:-1px;white-space:nowrap}
.tab:hover{color:var(--ink)}
.tab[aria-selected="true"]{color:var(--blue);border-bottom-color:var(--blue)}
.panel{display:none;grid-template-columns:1fr 1.15fr;gap:48px;align-items:center}
.panel.active{display:grid}
.panel h3{font-size:1.6rem;margin-bottom:12px}
.panel p{color:var(--muted);margin-bottom:18px}
.checks{list-style:none;padding:0;margin:0;display:grid;gap:10px}
.checks li{display:flex;gap:10px;font-weight:500}
.checks li::before{content:"";flex:none;width:20px;height:20px;margin-top:2px;border-radius:50%;background:var(--green-soft) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20'%3E%3Cpath d='M5.5 10.5l3 3 6-6.5' fill='none' stroke='%2314875f' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E") center/contain no-repeat}
.shot{background:var(--white);border:1px solid var(--line);border-radius:18px;padding:18px;box-shadow:0 14px 40px -22px rgba(15,31,61,.3);overflow-x:auto}
.shot table{width:100%;border-collapse:collapse;font-size:.93rem;min-width:380px}
.shot th{text-align:left;font-weight:600;color:var(--muted);font-size:.85rem;padding:8px 10px;border-bottom:1px solid var(--line)}
.shot td{padding:11px 10px;border-bottom:1px solid var(--paper)}
.shot td.n,.shot th.n{text-align:right}
.pos-neg{color:var(--red);font-weight:700}
.pos-pos{color:var(--green);font-weight:700}
.mini-btn{font:600 .82rem var(--font-body);padding:6px 11px;border-radius:8px;border:1.5px solid var(--green);color:var(--green);background:#fff;cursor:default}
.arqueo{display:grid;gap:12px;min-width:320px}
.arqueo-row{display:flex;justify-content:space-between;align-items:baseline;padding:12px 14px;border-radius:12px;background:var(--paper)}
.arqueo-row span{color:var(--muted);font-weight:500}
.arqueo-row strong{font:700 1.3rem var(--font-display)}
.arqueo-row.diff{background:var(--amber-soft)}
.arqueo-row.diff strong{color:var(--amber)}
.arqueo-note{font-size:.9rem;color:var(--muted)}
.kpis{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:18px;min-width:320px}
.kpi{background:var(--paper);border-radius:12px;padding:12px 14px}
.kpi span{display:block;font-size:.85rem;color:var(--muted);font-weight:500}
.kpi strong{font:700 1.4rem var(--font-display)}
.bars{display:flex;align-items:flex-end;gap:10px;height:150px;min-width:320px;border-bottom:1px solid var(--line);padding:0 4px}
.bar-col{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:flex-end;gap:6px;height:100%}
.bar-col i{display:block;width:100%;max-width:44px;background:var(--blue);border-radius:8px 8px 0 0}
.bar-col span{font-size:.75rem;color:var(--muted);font-weight:600}
.bar-days{display:flex;gap:10px;min-width:320px;padding:6px 4px 0}
.bar-days span{flex:1;text-align:center;font-size:.8rem;color:var(--muted);font-weight:600}
.store{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;min-width:340px}
.store-item{background:var(--paper);border-radius:14px;padding:12px}
.store-img{height:72px;border-radius:10px;margin-bottom:10px}
.store-item b{display:block;font-size:.92rem;line-height:1.2}
.store-item span{display:block;font-weight:700;color:var(--blue);margin-top:4px}
.store-item small{color:var(--muted);font-size:.8rem}
.more-mods{margin-top:34px;color:var(--muted);font-size:.98rem}

/* Loader CSS */
.loader { display: none; width: 20px; height: 20px; border: 3px solid rgba(255,255,255,.3); border-radius: 50%; border-top-color: #fff; animation: spin 1s ease-in-out infinite; }
@keyframes spin { to { transform: rotate(360deg); } }
.btn-loading { pointer-events: none; opacity: 0.8; }
.btn-loading .btn-text { display: none; }
.btn-loading .loader { display: inline-block; }

/* Cómo empezar (es una secuencia real) */
.start{list-style:none;counter-reset:st;margin:0;padding:0;display:grid;grid-template-columns:repeat(3,1fr);gap:0;border-top:1px solid var(--line)}
.start li{counter-increment:st;padding:26px 28px 0 0}
.start li::before{content:counter(st);display:grid;place-items:center;width:34px;height:34px;border-radius:50%;background:var(--ink);color:#fff;font:700 .95rem var(--font-display);margin-bottom:14px}
.start b{display:block;font:700 1.2rem var(--font-display);margin-bottom:6px}
.start span{color:var(--muted)}

/* Apps */
.apps{background:var(--ink);color:#fff}
.apps h2{color:#fff}
.apps .lead{color:#c3cde2}
.apps-grid{display:grid;grid-template-columns:1.1fr 1fr;gap:56px;align-items:start}
.badge-ver{display:inline-block;font-size:.85rem;font-weight:600;background:rgba(255,255,255,.12);padding:4px 11px;border-radius:999px;margin-bottom:16px}
.apps-mid{display:grid;grid-template-columns:1fr auto;gap:32px;align-items:center;margin-top:30px}
.steps{list-style:none;counter-reset:s;margin:0;padding:0;display:grid;gap:14px}
.steps li{counter-increment:s;display:flex;gap:14px;align-items:flex-start;color:#e5ebf7}
.steps li::before{content:counter(s);flex:none;width:28px;height:28px;border-radius:50%;background:var(--blue);display:grid;place-items:center;font-weight:700;font-size:.9rem}
.phone{width:224px;border:8px solid #2b3d66;border-radius:32px;background:#fff;color:var(--ink);padding:16px 12px;box-shadow:0 20px 40px -20px rgba(0,0,0,.6)}
.phone-h{font:700 .95rem var(--font-display);margin-bottom:10px}
.phone-saldo{background:var(--green);color:#fff;border-radius:14px;padding:12px}
.phone-saldo small{display:block;font-size:.72rem;font-weight:600;opacity:.9}
.phone-saldo strong{font:800 1.5rem var(--font-display)}
.phone-saldo em{display:block;font-style:normal;font-size:.78rem;opacity:.9}
.phone-list{list-style:none;margin:12px 0;padding:0;display:grid;gap:8px}
.phone-list li{display:flex;justify-content:space-between;gap:8px;font-size:.78rem;padding:8px 10px;background:var(--paper);border-radius:10px}
.phone-list b{display:block;font-size:.82rem}
.phone-list span{color:var(--muted)}
.phone-list strong{align-self:center}
.phone-list .paid strong{color:var(--green)}
.phone-list .due strong{color:var(--red)}
.phone-wa{display:block;text-align:center;background:#1faa59;color:#fff;font-weight:700;font-size:.85rem;padding:10px;border-radius:10px}
.dl{background:var(--white);color:var(--ink);border-radius:22px;padding:26px}
.dl h3{margin-bottom:4px}
.dl .sub{color:var(--muted);font-size:.95rem;margin-bottom:18px}
.dl .btn{width:100%;margin-bottom:20px}
.specs{margin:0;display:grid;grid-template-columns:auto 1fr;gap:10px 18px;font-size:.93rem}
.specs dt{color:var(--muted);font-weight:500}
.specs dd{margin:0;font-weight:600;word-break:break-all}
.specs code{font:500 .82rem ui-monospace,SFMono-Regular,Menlo,monospace;background:var(--paper);padding:2px 6px;border-radius:6px}
.qr-slot{margin-top:22px;border:2px dashed var(--line);border-radius:14px;padding:18px;text-align:center;color:var(--muted);font-size:.9rem;display:flex;flex-direction:column;align-items:center}
.qr-slot strong{display:block;color:var(--ink);margin-bottom:8px}
.more-apps{padding:64px 0 8px}
.more-apps h3{font-size:1.5rem;margin-bottom:8px}
.app-rows{margin-top:22px;border-top:1px solid var(--line)}
.app-row{display:grid;grid-template-columns:auto 1fr auto;gap:18px;align-items:center;padding:18px 4px;border-bottom:1px solid var(--line)}
.app-ico{width:44px;height:44px;border-radius:12px;display:grid;place-items:center;color:#fff;font:800 1.1rem var(--font-display)}
.app-row b{display:block;font-size:1.05rem}
.app-row span{color:var(--muted);font-size:.95rem}
.pill{font-size:.82rem;font-weight:600;padding:4px 11px;border-radius:999px;background:var(--green-soft);color:var(--green);white-space:nowrap}
.pill.soon{background:var(--amber-soft);color:var(--amber)}

/* Precio único */
.price-panel{display:grid;grid-template-columns:.85fr 1.15fr;border:1px solid var(--line);border-radius:24px;overflow:hidden;background:var(--white)}
.price-main{background:var(--blue-soft);padding:38px 34px;display:flex;flex-direction:column;gap:14px;align-items:flex-start;justify-content:center}
.plan-tag{font-size:.8rem;font-weight:700;background:var(--blue);color:#fff;padding:4px 11px;border-radius:999px}
.plan-price{font:800 4.4rem/1 var(--font-display);letter-spacing:-.03em}
.plan-price small{font:500 1.1rem var(--font-body);color:var(--muted);letter-spacing:0}
.price-bs{font-weight:700;color:var(--blue-deep)}
.price-note{font-size:.9rem;color:var(--muted)}
.price-incl{padding:38px 34px}
.price-incl h3{margin-bottom:18px}
.two-col{grid-template-columns:1fr 1fr;gap:12px 24px}
.try-first{margin-top:18px;display:flex;flex-wrap:wrap;gap:8px 22px;align-items:center;color:var(--muted);font-weight:500}
.try-first a{font-weight:700;color:var(--blue);text-underline-offset:3px}

/* FAQ */
.faq{max-width:780px}
.faq details{border-bottom:1px solid var(--line);padding:4px 0}
.faq summary{cursor:pointer;list-style:none;font:600 1.12rem var(--font-body);padding:18px 40px 18px 0;position:relative}
.faq summary::-webkit-details-marker{display:none}
.faq summary::after{content:"+";position:absolute;right:6px;top:50%;transform:translateY(-50%);font-size:1.5rem;font-weight:500;color:var(--blue);transition:transform .2s}
.faq details[open] summary::after{transform:translateY(-50%) rotate(45deg)}
.faq details p{color:var(--muted);padding:0 40px 20px 0;max-width:64ch}

/* CTA final + footer */
.final{background:var(--blue);color:#fff;padding:72px 0}
.final-in{display:flex;justify-content:space-between;align-items:center;gap:32px;flex-wrap:wrap}
.final h2{color:#fff;max-width:20ch}
.final p{color:#dbe5ff;margin-top:10px;max-width:46ch}
.final-actions{display:flex;gap:12px;flex-wrap:wrap}
.final .btn-ghost{border-color:#fff;color:#fff}
.final .btn-ghost:hover{background:#fff;color:var(--blue)}
.foot{padding:36px 0 90px;font-size:.94rem;color:var(--muted)}
.foot-in{display:flex;justify-content:space-between;gap:20px;flex-wrap:wrap}
.foot-links{display:flex;gap:20px;flex-wrap:wrap}
.foot a{text-decoration:none}
.foot a:hover{color:var(--blue);text-decoration:underline}

/* WhatsApp flotante */
.wa{position:fixed;right:18px;bottom:18px;z-index:40;display:flex;align-items:center;gap:10px;background:#1faa59;color:#fff;text-decoration:none;font-weight:700;padding:13px 18px;border-radius:999px;box-shadow:0 10px 26px -8px rgba(15,31,61,.5)}
.wa:hover{background:#178a47}
.wa svg{width:22px;height:22px}

/* Movimiento: una sola entrada en el hero */
@media (prefers-reduced-motion:no-preference){
  .pos{animation:rise .7s ease-out both}
  @keyframes rise{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:none}}
}
@media (prefers-reduced-motion:reduce){html{scroll-behavior:auto}}

/* Responsive */
@media (max-width:960px){
  .hero-grid,.apps-grid,.panel.active,.price-panel{grid-template-columns:1fr;gap:36px}
  .price-panel{gap:0}
  .start{grid-template-columns:1fr}
  .start li{padding:22px 0 8px}
}
@media (max-width:860px){
  .menu-btn{display:flex; align-items:center; justify-content:center; padding:6px; margin-left:4px;}
  .btn-txt{display:none;}
  .btn-ico.hide-lg{display:block !important;}
  .bar-actions .btn{padding:8px 10px; border-radius:10px; gap:0;}
  .nav{display:none;position:absolute;left:0;right:0;top:68px;background:var(--paper);border-bottom:1px solid var(--line);flex-direction:column;align-items:flex-start;padding:14px 24px 20px;gap:6px;margin:0}
  .nav.open{display:flex}
  .nav a{padding:10px 0;font-size:1.05rem}
  .bar{gap:12px}
  .bar-actions{margin-left:auto; display:flex; gap:6px; align-items:center;}
}
@media (max-width:640px){
  .apps-mid,.apps-mid{grid-template-columns:1fr}
  .phone{margin:0 auto}
  .two-col{grid-template-columns:1fr}
  .vs th,.vs td{padding:14px 14px;font-size:.95rem}
}
@media (max-width:560px){
  .pos-body{grid-template-columns:1fr}
  .pos-products{border-right:0;border-bottom:1px solid var(--line)}
  .sec{padding:64px 0}
  .app-row{grid-template-columns:auto 1fr}
  .app-row .pill{grid-column:2;justify-self:start}
  .wa span{display:none}
  .wa{padding:14px}
  .plan-price{font-size:3.6rem}
  .price-main,.price-incl{padding:28px 22px}
}
</style>
</head>
<body>
<a class="skip" href="#contenido">Saltar al contenido</a>
<!-- Google Tag Manager (noscript): pega aquí tu snippet GTM-NHRNGKB2 (body) -->

<header class="site-header">
  <div class="wrap bar">
    <a class="brand" href="#inicio" aria-label="TuInventario, inicio">
      <span class="brand-mark" aria-hidden="true" style="background:transparent; padding:0;">
        <img src="/iconos_negocio/logo1.png" alt="Logo" style="width:34px; height:34px; object-fit:contain;">
      </span>
      <span style="color:var(--ink); font-weight:800;">TuInventario</span>
    </a>
    <nav class="nav" id="nav" aria-label="Principal">
      <a href="#modulos">Módulos</a>
      <a href="/qrmenu">Menú QR</a>
      <a href="#apps">Apps</a>
      <a href="#precio">Precio</a>
      <a href="#preguntas">Preguntas</a>
      <a href="#contacto">Contacto</a>
    </nav>
    <div class="bar-actions" style="display:flex; gap:8px; align-items:center;">
      <a class="btn btn-ghost btn-sm" href="javascript:void(0)" onclick="document.getElementById('login-modal').style.display='flex';" data-evt="click_acceder">
        <svg class="btn-ico hide-lg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none;"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path><polyline points="10 17 15 12 10 7"></polyline><line x1="15" y1="12" x2="3" y2="12"></line></svg>
        <span class="btn-txt">Acceder</span>
      </a>
      <a class="btn btn-primary btn-sm" href="/auth/register" data-evt="click_register_header">
        <svg class="btn-ico hide-lg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none;"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg>
        <span class="btn-txt">Regístrate</span>
      </a>
      <button class="menu-btn" id="menuBtn" aria-expanded="false" aria-controls="nav" aria-label="Abrir menú">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
      </button>
    </div>
  </div>
</header>

<main id="contenido">

<!-- HERO -->
<section class="hero" id="inicio">
  <div class="wrap hero-grid">
    <div>
      <h1>Vende, cobra y controla tu inventario en dólares y bolívares.</h1>
      <p class="lead">Punto de venta, inventario, compras y cierre de caja en un solo sistema. Y cuando necesites cobrar sin conexión, PagaPues funciona sin internet.</p>
      <div class="hero-cta">
        <a class="btn btn-primary" href="/auth/register" data-evt="click_register_hero">Regístrate</a>
        <a class="btn btn-ghost" href="https://wa.me/584145176772" data-evt="click_whatsapp_hero">Hablar por WhatsApp</a>
      </div>
      <ul class="hero-facts">
        <li>Todo el sistema por $3 al mes</li>
        <li>Precios en $ y en Bs. al instante</li>
        <li>App Android incluida</li>
      </ul>
    </div>

    <!-- Demo interactiva: el visitante prueba una venta y elige su tipo de negocio -->
    <div>
      <div class="seg" role="group" aria-label="Elige el tipo de negocio para la demo">
        <span class="seg-label">Mira cómo se ve en:</span>
        <button type="button" data-cat="resto" aria-pressed="true">Restaurante</button>
        <button type="button" data-cat="bodega" aria-pressed="false">Bodega</button>
        <button type="button" data-cat="ropa" aria-pressed="false">Tienda de ropa</button>
      </div>
      <div class="pos" aria-label="Demostración del punto de venta">
        <div class="pos-top">
          <span id="posCtx"><strong>Caja 1</strong> · Mesa 4</span>
          <span class="sync">Sincronizado</span>
        </div>
        <div class="pos-body">
          <div class="pos-products" id="products"></div>
          <div class="ticket">
            <div class="ticket-title">Ticket</div>
            <ul class="ticket-lines" id="lines"></ul>
            <div class="totals">
              <div class="total-usd" id="totalUsd">$0,00</div>
              <div class="total-bs" id="totalBs">Bs. 0,00</div>
              <span class="rate" id="rateChip"></span>
            </div>
            <button class="btn btn-primary pay" id="payBtn" type="button">Procesar pago</button>
            <div class="toast" id="toast" role="status" aria-live="polite"></div>
          </div>
        </div>
        <div class="pos-foot">
          <span>Venta de hoy: <strong id="soldToday"></strong></span>
          <span>Cada venta descuenta el stock</span>
        </div>
      </div>
      <p class="demo-hint">Pruébalo: toca un producto y procesa el pago.</p>
    </div>
  </div>
</section>

<!-- ANTES / DESPUÉS -->
<section class="sec" style="padding-top:24px" aria-labelledby="vs-t">
  <div class="wrap">
    <div class="sec-head">
      <h2 id="vs-t">Lo que cambia cuando dejas el cuaderno y la calculadora</h2>
    </div>
    <div class="vs-wrap">
      <table class="vs">
        <thead>
          <tr><th scope="col">Hoy, con cuaderno y calculadora</th><th scope="col">Con TuInventario</th></tr>
        </thead>
        <tbody>
          <tr><td>Calculas a mano cuántos bolívares son cada precio.</td><td>Cada precio y cada total salen en dólares y en bolívares.</td></tr>
          <tr><td>Te enteras de que algo se acabó cuando el cliente lo pide.</td><td>El sistema te avisa cuando el stock de un producto baja.</td></tr>
          <tr><td>El cuaderno de fiados se pierde, se moja o no cuadra.</td><td>Cada deuda queda registrada y la recuerdas por WhatsApp.</td></tr>
          <tr><td>La caja no cuadra y nadie sabe por qué.</td><td>El arqueo muestra la diferencia y el kardex, cada movimiento.</td></tr>
          <tr><td>Solo vendes mientras el local está abierto.</td><td>Tu catálogo en línea y tu menú QR siguen disponibles.</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</section>

<!-- MÓDULOS -->
<section class="sec" id="modulos" style="padding-top:24px">
  <div class="wrap">
    <div class="sec-head">
      <h2>Todo lo que pasa en tu negocio, en un solo lugar</h2>
      <p class="lead">Elige un módulo para ver cómo funciona.</p>
      <!-- Sustituye estas vistas de ejemplo por capturas reales de tu sistema -->
    </div>

    <div class="tabs" role="tablist" aria-label="Módulos del sistema">
      <button class="tab" role="tab" id="t-inv" aria-selected="true" aria-controls="p-inv">Inventario</button>
      <button class="tab" role="tab" id="t-kar" aria-selected="false" aria-controls="p-kar" tabindex="-1">Kardex</button>
      <button class="tab" role="tab" id="t-arq" aria-selected="false" aria-controls="p-arq" tabindex="-1">Arqueo de caja</button>
      <button class="tab" role="tab" id="t-cre" aria-selected="false" aria-controls="p-cre" tabindex="-1">Créditos</button>
      <button class="tab" role="tab" id="t-rep" aria-selected="false" aria-controls="p-rep" tabindex="-1">Reportes</button>
      <button class="tab" role="tab" id="t-tie" aria-selected="false" aria-controls="p-tie" tabindex="-1">Tienda online</button>
    </div>

    <div class="panel active" role="tabpanel" id="p-inv" aria-labelledby="t-inv">
      <div>
        <h3>Sabe qué tienes y qué te falta</h3>
        <p>Ve tu stock en tiempo real y recibe aviso antes de quedarte sin producto.</p>
        <ul class="checks">
          <li>Alertas de stock bajo por producto</li>
          <li>Varios almacenes en una sola vista</li>
          <li>Los más vendidos, a la mano</li>
        </ul>
      </div>
      <div class="shot">
        <table>
          <thead><tr><th>Producto</th><th class="n">Stock</th><th class="n">Mínimo</th><th>Estado</th></tr></thead>
          <tbody>
            <tr><td>Burger clásica</td><td class="n">24</td><td class="n">10</td><td><span class="stock ok">Óptimo</span></td></tr>
            <tr><td>Papas fritas</td><td class="n">8</td><td class="n">10</td><td><span class="stock low">Stock bajo</span></td></tr>
            <tr><td>Coca-Cola 1,5 L</td><td class="n">40</td><td class="n">12</td><td><span class="stock ok">Óptimo</span></td></tr>
            <tr><td>Pan de hamburguesa</td><td class="n">0</td><td class="n">20</td><td><span class="stock out">Agotado</span></td></tr>
          </tbody>
        </table>
      </div>
    </div>

    <div class="panel" role="tabpanel" id="p-kar" aria-labelledby="t-kar" hidden>
      <div>
        <h3>Cada entrada y salida, registrada</h3>
        <p>El historial de cada producto: qué entró, qué salió y quién lo movió. Así detectas faltantes y errores a tiempo.</p>
        <ul class="checks">
          <li>Movimiento por producto, con fecha y usuario</li>
          <li>Saldo después de cada operación</li>
          <li>Ajustes de inventario con motivo</li>
        </ul>
      </div>
      <div class="shot">
        <table>
          <thead><tr><th>Fecha</th><th>Movimiento</th><th class="n">Cant.</th><th class="n">Saldo</th></tr></thead>
          <tbody>
            <tr><td>12/09</td><td>Compra a proveedor</td><td class="n pos-pos">+24</td><td class="n">32</td></tr>
            <tr><td>13/09</td><td>Venta #0412</td><td class="n pos-neg">−3</td><td class="n">29</td></tr>
            <tr><td>13/09</td><td>Venta #0419</td><td class="n pos-neg">−5</td><td class="n">24</td></tr>
            <tr><td>14/09</td><td>Ajuste: producto dañado</td><td class="n pos-neg">−1</td><td class="n">23</td></tr>
          </tbody>
        </table>
      </div>
    </div>

    <div class="panel" role="tabpanel" id="p-arq" aria-labelledby="t-arq" hidden>
      <div>
        <h3>Cierra el turno sin discusiones</h3>
        <p>Compara lo que el sistema dice que debe haber en caja con lo que realmente contaste.</p>
        <ul class="checks">
          <li>Diferencias visibles al instante</li>
          <li>Cierre por turno y por cajero</li>
          <li>Efectivo, transferencias y punto de venta por separado</li>
        </ul>
      </div>
      <div class="shot">
        <div class="arqueo">
          <div class="arqueo-row"><span>Efectivo esperado</span><strong style="display:flex; flex-direction:column; align-items:flex-end;">$320,00 <small style="font-weight:normal; font-size:0.75rem; color:var(--muted);">Bs. <?= number_format(320 * $tasa_bcv, 2, ',', '.') ?></small></strong></div>
          <div class="arqueo-row"><span>Efectivo contado</span><strong style="display:flex; flex-direction:column; align-items:flex-end;">$318,50 <small style="font-weight:normal; font-size:0.75rem; color:var(--muted);">Bs. <?= number_format(318.50 * $tasa_bcv, 2, ',', '.') ?></small></strong></div>
          <div class="arqueo-row diff"><span>Diferencia</span><strong style="display:flex; flex-direction:column; align-items:flex-end;">−$1,50 <small style="font-weight:normal; font-size:0.75rem; color:var(--muted);">Bs. <?= number_format(-1.50 * $tasa_bcv, 2, ',', '.') ?></small></strong></div>
          <p class="arqueo-note">Cierre de Caja 1 · Turno de la tarde</p>
        </div>
      </div>
    </div>

    <div class="panel" role="tabpanel" id="p-cre" aria-labelledby="t-cre" hidden>
      <div>
        <h3>Sabe quién te debe y cuánto</h3>
        <p>Vende a crédito sin perder el rastro. Cada deuda queda ligada al cliente y a la venta que la originó.</p>
        <ul class="checks">
          <li>Historial de compras y deudas por cliente</li>
          <li>Recordatorio de cobro por WhatsApp</li>
          <li>Cuentas por pagar a tus proveedores</li>
        </ul>
      </div>
      <div class="shot">
        <table>
          <thead><tr><th>Cliente</th><th class="n">Debe</th><th>Desde</th><th></th></tr></thead>
          <tbody>
            <tr><td>Carlos Romero</td><td class="n">$120,00 <br><small style="color:var(--muted); font-size:0.75rem; font-weight:normal;">Bs. <?= number_format(120 * $tasa_bcv, 2, ',', '.') ?></small></td><td>hace 2 días</td><td><span class="mini-btn">Recordar</span></td></tr>
            <tr><td>María González</td><td class="n">$45,50 <br><small style="color:var(--muted); font-size:0.75rem; font-weight:normal;">Bs. <?= number_format(45.50 * $tasa_bcv, 2, ',', '.') ?></small></td><td>hoy</td><td><span class="mini-btn">Recordar</span></td></tr>
            <tr><td>Luis Pérez</td><td class="n">$80,00 <br><small style="color:var(--muted); font-size:0.75rem; font-weight:normal;">Bs. <?= number_format(80 * $tasa_bcv, 2, ',', '.') ?></small></td><td><span class="stock low">hace 9 días</span></td><td><span class="mini-btn">Recordar</span></td></tr>
          </tbody>
        </table>
      </div>
    </div>

    <div class="panel" role="tabpanel" id="p-rep" aria-labelledby="t-rep" hidden>
      <div>
        <h3>Mira cuánto ganas, no solo cuánto vendes</h3>
        <p>Tus ventas, gastos y ganancias en una vista clara, para decidir qué comprar y qué vender más.</p>
        <ul class="checks">
          <li>Ventas por día, semana y mes</li>
          <li>Productos más y menos vendidos</li>
          <li>Ganancias y gastos del periodo</li>
        </ul>
      </div>
      <div class="shot">
        <div class="kpis">
          <div class="kpi"><span>Ventas de la semana</span><strong style="display:flex; flex-direction:column;">$2.140,00 <small style="font-weight:normal; font-size:0.8rem; color:var(--muted); margin-top:2px;">Bs. <?= number_format(2140 * $tasa_bcv, 2, ',', '.') ?></small></strong></div>
          <div class="kpi"><span>Ganancia estimada</span><strong style="display:flex; flex-direction:column;">$610,00 <small style="font-weight:normal; font-size:0.8rem; color:var(--muted); margin-top:2px;">Bs. <?= number_format(610 * $tasa_bcv, 2, ',', '.') ?></small></strong></div>
        </div>
        <div class="bars" role="img" aria-label="Ventas por día de la semana: lunes 220, martes 260, miércoles 240, jueves 310, viernes 420, sábado 480, domingo 210 dólares">
          <div class="bar-col"><i style="height:46%"></i></div>
          <div class="bar-col"><i style="height:54%"></i></div>
          <div class="bar-col"><i style="height:50%"></i></div>
          <div class="bar-col"><i style="height:65%"></i></div>
          <div class="bar-col"><i style="height:88%"></i></div>
          <div class="bar-col"><i style="height:100%"></i></div>
          <div class="bar-col"><i style="height:44%"></i></div>
        </div>
        <div class="bar-days" aria-hidden="true"><span>L</span><span>M</span><span>M</span><span>J</span><span>V</span><span>S</span><span>D</span></div>
      </div>
    </div>

    <div class="panel" role="tabpanel" id="p-tie" aria-labelledby="t-tie" hidden>
      <div>
        <h3>Tu tienda abierta las 24 horas</h3>
        <p>Publica tus productos en línea. El catálogo se actualiza solo con tu inventario, con precios en dólares y bolívares.</p>
        <ul class="checks">
          <li>Sin duplicar trabajo: un solo inventario</li>
          <li>Menú QR para restaurantes y cafés</li>
          <li>Pedidos por WhatsApp</li>
        </ul>
      </div>
      <div class="shot">
        <div class="store">
          <div class="store-item"><div class="store-img" style="background: #c9d8ff url(https://images.unsplash.com/photo-1598033129183-c4f50c736f10?auto=format&fit=crop&w=300&q=80) center/cover;"></div><b>Camisa casual</b><span>$29,99</span><small>Bs. <?= number_format(29.99 * $tasa_bcv, 2, ',', '.') ?></small></div>
          <div class="store-item"><div class="store-img" style="background: #f9d9c4 url(https://images.unsplash.com/photo-1568901346375-23c9450c58cd?auto=format&fit=crop&w=300&q=80) center/cover;"></div><b>Pack hamburguesas</b><span>$14,90</span><small>Bs. <?= number_format(14.90 * $tasa_bcv, 2, ',', '.') ?></small></div>
          <div class="store-item"><div class="store-img" style="background: #d4efe2 url(https://images.unsplash.com/photo-1622483767028-3f66f32aef97?auto=format&fit=crop&w=300&q=80) center/cover;"></div><b>Refresco en lata</b><span>$1,50</span><small>Bs. <?= number_format(1.50 * $tasa_bcv, 2, ',', '.') ?></small></div>
        </div>
      </div>
    </div>

    <p class="more-mods">También incluye compras, proveedores, clientes y facturación.</p>
  </div>
</section>

<!-- CÓMO EMPEZAR -->
<section class="sec" style="padding-top:8px" aria-labelledby="start-t">
  <div class="wrap">
    <div class="sec-head"><h2 id="start-t">Empieza en tres pasos</h2></div>
    <ol class="start">
      <li><b>Crea tu cuenta</b><span>Solo necesitas tu cédula y una contraseña.</span></li>
      <li><b>Carga tus productos</b><span>Con su precio en dólares y la cantidad que tienes.</span></li>
      <li><b>Empieza a vender</b><span>Cada venta descuenta el stock y suma a tu caja.</span></li>
    </ol>
  </div>
</section>

<!-- APPS -->
<section class="sec apps" id="apps">
  <div class="wrap apps-grid">
    <div>
      <span class="badge-ver">App para Android · v1.0</span>
      <h2>PagaPues: cobra lo que te deben, aunque no tengas internet</h2>
      <p class="lead" style="margin-top:14px">Registra ventas y deudas en tu teléfono, trabaja en dólares y bolívares y envía recordatorios de cobro por WhatsApp.</p>
      <div class="apps-mid">
        <ol class="steps">
          <li>Descarga el archivo APK.</li>
          <li>Ábrelo y acepta la instalación.</li>
          <li>Si Android te lo pide, autoriza la instalación desde este origen.</li>
        </ol>
        <!-- Vista de ejemplo: sustitúyela por una captura real de la app -->
        <div class="phone" aria-label="Vista de ejemplo de PagaPues">
          <div class="phone-h">PagaPues</div>
          <div class="phone-saldo"><small>Saldo por cobrar</small><strong>$4.500,00</strong><em>Bs. <?= number_format(4500 * $tasa_bcv, 2, ',', '.') ?></em></div>
          <ul class="phone-list">
            <li class="due"><div><b>Carlos R.</b><span>Hace 2 días</span></div><strong style="display:flex; flex-direction:column; align-items:flex-end;">$120,00 <small style="font-weight:normal; font-size:0.75rem; color:var(--muted);">Bs. <?= number_format(120 * $tasa_bcv, 2, ',', '.') ?></small></strong></li>
            <li><div><b>María G.</b><span>Hoy, 10:00</span></div><strong style="display:flex; flex-direction:column; align-items:flex-end;">$45,50 <small style="font-weight:normal; font-size:0.75rem; color:var(--muted);">Bs. <?= number_format(45.50 * $tasa_bcv, 2, ',', '.') ?></small></strong></li>
            <li class="paid"><div><b>José L.</b><span>Pagado</span></div><strong style="display:flex; flex-direction:column; align-items:flex-end;">$300,00 <small style="font-weight:normal; font-size:0.75rem; color:var(--muted);">Bs. <?= number_format(300 * $tasa_bcv, 2, ',', '.') ?></small></strong></li>
          </ul>
          <span class="phone-wa">Enviar recordatorio</span>
        </div>
      </div>
    </div>

    <div class="dl">
      <h3>Descargar PagaPues</h3>
      <p class="sub">Gratis · No necesitas cuenta para instalarla</p>
      <a class="btn btn-primary" href="/assets/PagaPues.apk" download data-evt="click_apk_pagapues">Descargar APK (v1.0)</a>
      <!-- Completa estos datos con los reales de tu APK -->
      <dl class="specs">
        <dt>Versión</dt><dd>1.0</dd>
        <dt>Requiere</dt><dd>Android 5 o superior</dd>
        <dt>Tamaño</dt><dd>75 MB</dd>
        <dt>Actualizada</dt><dd>Ayer</dd>
        <dt>SHA-256</dt><dd><code>e625a6dc5b4e63e3ed9ad01fd3522b075c366d121bd87ed2d485180a1247ba9f</code></dd>
        <dt>Permisos</dt><dd>Notificaciones, Importación de contactos, Cámara</dd>
      </dl>
      <div class="qr-slot">
        <strong>¿Estás en una computadora?</strong>
        <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=https://www.tuinventario.app/assets/PagaPues.apk" alt="QR Code PagaPues" style="margin: 12px auto; width: 140px; height: 140px; border-radius: 12px; display: block;">
        <span style="font-size: 0.85rem; display: block; margin-top: 8px;">Escanea este código con tu teléfono para descargar PagaPues.</span>
      </div>
    </div>
  </div>
</section>

<!-- MÁS APPS -->
<section class="wrap more-apps" aria-labelledby="more-apps-t">
  <h3 id="more-apps-t">Más apps de TuInventario</h3>
  <p class="lead">Cada app resuelve una tarea de tu negocio y se conecta con el resto.</p>
  <div class="app-rows">
    <div class="app-row">
      <div class="app-ico" style="background:var(--blue)">P</div>
      <div><b>PagaPues</b><span>Cobros, deudas y recordatorios por WhatsApp</span></div>
      <span class="pill">Disponible</span>
    </div>
    <div class="app-row">
      <div class="app-ico" style="background:var(--green)">Q</div>
      <div><b>Menú QR</b><span>Carta digital para tu restaurante o café</span></div>
      <a class="pill" href="/qrmenu" style="text-decoration:none" data-evt="click_menu_qr">Disponible en la web</a>
    </div>
    <div class="app-row">
      <div class="app-ico" style="background:var(--amber)">+</div>
      <div><b>Nueva app en camino</b><span>Escribe el nombre y la función de tu próxima app aquí</span></div>
      <span class="pill soon">Próximamente</span>
    </div>
  </div>
</section>

<!-- PRECIO ÚNICO -->
<section class="sec" id="precio">
  <div class="wrap">
    <div class="sec-head">
      <h2>Un solo plan, un solo precio</h2>
      <p class="lead">Todo el sistema por $3 al mes. Sin planes que comparar.</p>
    </div>
    <div class="price-panel">
      <div class="price-main">
        <span class="plan-tag">Precio de lanzamiento</span>
        <div class="plan-price">$3 <small>al mes</small></div>
        <p class="price-bs" id="priceBs"></p>
        <a class="btn btn-primary" href="/auth/register" data-evt="click_register_precio">Crear mi cuenta</a>
        <p class="price-note">¿Dudas sobre cómo pagar? Mira las preguntas frecuentes.</p>
      </div>
      <div class="price-incl">
        <h3>Esto va incluido</h3>
        <ul class="checks two-col">
          <li>Punto de venta (POS)</li>
          <li>Inventario y alertas de stock</li>
          <li>Compras y proveedores</li>
          <li>Clientes y créditos</li>
          <li>Kardex</li>
          <li>Arqueo de caja</li>
          <li>Reportes y ganancias</li>
          <li>Facturación</li>
          <li>Tienda online</li>
          <li>Menú QR</li>
          <li>Precios en $ y en Bs.</li>
          <li>App PagaPues para Android</li>
        </ul>
      </div>
    </div>
    <p class="try-first">Antes de pagar:
      <a href="#apps" data-evt="click_pagapues_precio">Descargar PagaPues gratis</a>
    </p>
  </div>
</section>

<!-- FAQ -->
<section class="sec" id="preguntas" style="padding-top:8px">
  <div class="wrap">
    <div class="sec-head"><h2>Preguntas frecuentes</h2></div>
    <div class="faq">
      <details>
        <summary>¿Cuánto cuesta y qué incluye?</summary>
        <p>Cuesta $3 al mes y incluye todo el sistema: punto de venta, inventario, compras, proveedores, clientes y créditos, kardex, arqueo de caja, reportes, tienda online y Menú QR. La app PagaPues es gratis.</p>
      </details>
      <details>
        <summary>¿Cómo pago los $3 al mes?</summary>
        <p>Indica aquí los métodos que aceptas (por ejemplo, pago móvil, transferencia o Zelle) y cómo confirmas el pago para activar la cuenta.</p>
      </details>
      <details>
        <summary>¿Puedo probarlo antes de pagar?</summary>
        <p>Sí. Puedes ver la demo sin registrarte y descargar PagaPues gratis para probarla en tu teléfono.</p>
      </details>
      <details>
        <summary>¿Funciona sin internet?</summary>
        <p>La app PagaPues sí: funciona sin conexión y guarda los datos en tu teléfono. El sistema web de inventario y punto de venta necesita conexión a internet.</p>
      </details>
      <details>
        <summary>¿Puedo cobrar en dólares y en bolívares?</summary>
        <p>Sí. Cada precio y cada total se muestran en las dos monedas, calculados con la tasa que configures.</p>
      </details>
      <details>
        <summary>¿Cómo instalo el APK de PagaPues?</summary>
        <p>Descarga el archivo desde esta página, ábrelo y acepta la instalación. Si Android te avisa que el origen es desconocido, autoriza la instalación solo para este archivo.</p>
      </details>
      <details>
        <summary>¿Qué pasa con mis datos?</summary>
        <p>No vendemos ni compartimos tus datos con terceros, salvo requerimiento legal. Puedes exportar tu información o pedir la eliminación de tu cuenta cuando quieras.</p>
      </details>
      <details>
        <summary>¿Cómo pido ayuda si algo falla?</summary>
        <p>Escríbenos por WhatsApp o Telegram. Indica aquí tu horario real de atención para que el cliente sepa cuándo esperar respuesta.</p>
      </details>
    </div>
  </div>
</section>

<!-- CTA FINAL -->
<section class="final" id="contacto">
  <div class="wrap final-in">
    <div>
      <h2>Deja el cuaderno y ordena tu negocio hoy</h2>
      <p>Todo el sistema por $3 al mes. Prueba la demo sin registrarte o cuéntanos qué vendes y te ayudamos a empezar.</p>
    </div>
    <div class="final-actions">
      <a class="btn btn-light" href="/auth/register" data-evt="click_register_final">Crear mi cuenta</a>
      <a class="btn btn-ghost" href="https://wa.me/584145176772" data-evt="click_whatsapp_final">Escribir por WhatsApp</a>
    </div>
  </div>
</section>
</main>

<footer class="foot">
  <div class="wrap foot-in">
    <span>© 2026 TuInventario. Todos los derechos reservados.</span>
    <div class="foot-links">
      <a href="https://t.me/MaomOllarves" data-evt="click_telegram">Telegram</a>
      <a href="mailto:videocode.info@gmail.com">videocode.info@gmail.com</a>
      <a href="javascript:void(0)" onclick="document.getElementById('terms-modal').style.display='flex';">Términos y condiciones</a>
      <a href="javascript:void(0)" onclick="document.getElementById('privacy-modal').style.display='flex';">Política de privacidad</a>
    </div>
  </div>
</footer>

<!-- Modal de Login -->
<div id="login-modal" style="display: none; align-items: center; justify-content: center; position: fixed; inset: 0; background: rgba(15,31,61,0.6); backdrop-filter: blur(4px); z-index: 100;">
    <div style="background: #fff; padding: 32px; border-radius: 24px; max-width: 400px; width: 90%; position: relative;">
        <button onclick="document.getElementById('login-modal').style.display = 'none'" style="position: absolute; right: 16px; top: 16px; background: none; border: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
        <h3 style="margin-bottom: 8px; font-size: 1.5rem;">Acceder a TuInventario</h3>
        <p style="color: var(--muted); margin-bottom: 24px; font-size: 0.95rem;">Ingresa tus datos para continuar.</p>
        <form action="/auth/login" method="POST" id="loginForm" style="display: flex; flex-direction: column; gap: 16px;" onsubmit="document.getElementById('loginSubmitBtn').classList.add('btn-loading');">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
            <div>
                <label style="display: block; margin-bottom: 6px; font-size: 0.9rem; font-weight: 600;">Cédula o Correo</label>
                <input type="text" name="username" placeholder="V-12345678" required style="width: 100%; padding: 12px 16px; border: 1px solid var(--line); border-radius: 12px; background: var(--paper); font-size: 1rem;">
            </div>
            <div style="position: relative;">
                <label style="display: block; margin-bottom: 6px; font-size: 0.9rem; font-weight: 600;">Contraseña</label>
                <input type="password" id="loginPassword" name="password" placeholder="••••••••" required style="width: 100%; padding: 12px 40px 12px 16px; border: 1px solid var(--line); border-radius: 12px; background: var(--paper); font-size: 1rem;">
                <button type="button" onclick="var el=document.getElementById('loginPassword'); el.type=el.type==='password'?'text':'password';" style="position: absolute; right: 12px; top: 35px; background: none; border: none; cursor: pointer; color: var(--muted); display: flex; align-items: center; justify-content: center; padding: 4px;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                </button>
            </div>
            <button type="submit" id="loginSubmitBtn" style="display: flex; align-items: center; justify-content: center; width: 100%; padding: 14px; background: var(--blue); color: #fff; border: none; border-radius: 12px; font-weight: 700; font-size: 1rem; cursor: pointer; margin-top: 8px;">
                <span class="btn-text">Iniciar Sesión</span>
                <span class="loader"></span>
            </button>
        </form>
    </div>
</div>

<!-- Modal Términos -->
<div id="terms-modal" style="display: none; align-items: center; justify-content: center; position: fixed; inset: 0; background: rgba(15,31,61,0.6); backdrop-filter: blur(4px); z-index: 100;">
    <div style="background: #fff; padding: 32px; border-radius: 24px; max-width: 800px; width: 90%; max-height: 80vh; overflow-y: auto; position: relative;">
        <button onclick="document.getElementById('terms-modal').style.display = 'none'" style="position: absolute; right: 16px; top: 16px; background: none; border: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
        <h3 style="margin-bottom: 16px; font-size: 1.5rem;">Términos y Condiciones de Uso</h3>
        <div style="color: var(--muted); font-size: 0.95rem; line-height: 1.6; display: flex; flex-direction: column; gap: 12px;">
            <p><strong>1. Aceptación de los Términos:</strong> Al acceder y utilizar la plataforma y los servicios proporcionados por TuInventario (incluyendo la aplicación web, aplicaciones móviles y cualquier API asociada), usted acepta estar sujeto a estos términos y condiciones. Si no está de acuerdo con alguna parte de estos términos, no debe utilizar nuestros servicios.</p>
            <p><strong>2. Uso del Servicio:</strong> TuInventario provee herramientas de gestión de inventario, punto de venta y analíticas para comercios. Usted acepta utilizar el servicio única y exclusivamente con fines lícitos y comerciales, garantizando que todos los datos de productos y ventas ingresados cumplen con las normativas fiscales vigentes en su jurisdicción.</p>
            <p><strong>3. Disponibilidad y Mantenimiento:</strong> Hacemos nuestro mejor esfuerzo para mantener el servicio operativo 24/7. Sin embargo, no garantizamos el acceso ininterrumpido a la plataforma, dado que pueden ocurrir mantenimientos programados o interrupciones por causas de fuerza mayor. El uso de la aplicación offline (PagaPues) mitiga riesgos de conexión, recayendo en el usuario la responsabilidad de sincronización posterior.</p>
            <p><strong>4. Limitación de Responsabilidad:</strong> TuInventario se proporciona "tal cual". En ningún caso seremos responsables por la pérdida de datos, lucro cesante o daños indirectos derivados del uso de nuestra plataforma. Es responsabilidad del usuario mantener respaldos físicos o fiscales según lo requiera la ley de su país.</p>
            <p><strong>5. Pagos y Suscripciones:</strong> El uso continuo del sistema requiere el pago oportuno del plan mensual publicado. Nos reservamos el derecho de suspender o cancelar cuentas que mantengan deudas, sin perjuicio de permitir la exportación de sus datos durante un periodo de gracia de 30 días.</p>
            <p><strong>6. Modificaciones:</strong> Nos reservamos el derecho de modificar estos términos en cualquier momento, lo cual será notificado a través del correo asociado a su cuenta con al menos 15 días de anticipación.</p>
        </div>
    </div>
</div>

<!-- Modal Privacidad -->
<div id="privacy-modal" style="display: none; align-items: center; justify-content: center; position: fixed; inset: 0; background: rgba(15,31,61,0.6); backdrop-filter: blur(4px); z-index: 100;">
    <div style="background: #fff; padding: 32px; border-radius: 24px; max-width: 800px; width: 90%; max-height: 80vh; overflow-y: auto; position: relative;">
        <button onclick="document.getElementById('privacy-modal').style.display = 'none'" style="position: absolute; right: 16px; top: 16px; background: none; border: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
        <h3 style="margin-bottom: 16px; font-size: 1.5rem;">Política de Privacidad Integral</h3>
        <div style="color: var(--muted); font-size: 0.95rem; line-height: 1.6; display: flex; flex-direction: column; gap: 12px;">
            <p><strong>1. Recopilación de la Información:</strong> Recopilamos la información mínima necesaria para el funcionamiento de su cuenta: nombre completo, correo electrónico, datos fiscales de su negocio y credenciales de acceso. La aplicación PagaPues solicitará permisos de almacenamiento para base de datos local y, ocasionalmente, cámara (para escaneo QR) e importación de contactos (para recordatorios de cobro).</p>
            <p><strong>2. Uso de los Datos:</strong> Sus datos comerciales, incluyendo inventario, precios, facturación y clientes registrados, le pertenecen íntegramente. Estos datos se procesan estrictamente para proveerle el servicio y generar sus propios reportes (kardex y analíticas). <strong>Jamás vendemos, alquilamos ni compartimos su base de datos comercial con terceros.</strong></p>
            <p><strong>3. Seguridad:</strong> Implementamos medidas de seguridad estándar de la industria (cifrado SSL en tránsito, bases de datos aisladas) para proteger su información contra acceso no autorizado, alteración o destrucción.</p>
            <p><strong>4. Retención y Eliminación:</strong> Conservamos sus datos mientras su cuenta permanezca activa. Si decide cancelar su suscripción, sus datos serán eliminados permanentemente de nuestros servidores principales tras 60 días, otorgándole tiempo suficiente para su exportación.</p>
            <p><strong>5. Cookies y Analíticas:</strong> Empleamos cookies técnicas necesarias para mantener su sesión activa de forma segura. Asimismo, empleamos servicios de terceros (como Google Analytics) de manera anonimizada para entender tendencias de uso general y mejorar nuestra plataforma.</p>
            <p><strong>6. Sus Derechos:</strong> Usted tiene derecho a solicitar acceso, corrección, exportación o eliminación de sus datos en cualquier momento enviando un correo a videocode.info@gmail.com.</p>
        </div>
    </div>
</div>

<a class="wa" href="https://wa.me/584145176772" aria-label="Escribir por WhatsApp" data-evt="click_whatsapp_flotante">
  <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38a9.9 9.9 0 0 0 4.74 1.21c5.46 0 9.91-4.45 9.91-9.91S17.5 2 12.04 2zm0 18.15c-1.48 0-2.93-.4-4.2-1.15l-.3-.18-3.12.82.83-3.04-.2-.31a8.2 8.2 0 0 1-1.26-4.38c0-4.54 3.7-8.24 8.25-8.24s8.24 3.7 8.24 8.24-3.7 8.24-8.24 8.24zm4.52-6.17c-.25-.12-1.47-.72-1.69-.81-.23-.08-.39-.12-.56.13-.16.25-.64.81-.78.97-.14.17-.29.19-.54.06-.25-.12-1.05-.39-2-1.23-.74-.66-1.23-1.47-1.38-1.72-.14-.25-.02-.38.11-.5.11-.11.25-.29.37-.43.12-.15.16-.25.25-.41.08-.17.04-.31-.02-.43-.06-.12-.56-1.34-.76-1.84-.2-.48-.4-.42-.56-.42h-.48c-.17 0-.43.06-.66.31-.22.25-.86.85-.86 2.07s.88 2.4 1 2.56c.12.17 1.75 2.67 4.23 3.74.59.26 1.05.41 1.41.52.59.19 1.13.16 1.56.1.48-.07 1.47-.6 1.67-1.18.21-.58.21-1.07.14-1.18-.06-.11-.22-.17-.47-.29z"/></svg>
  <span>WhatsApp</span>
</a>

<script>
(function(){
  /* ---------- Configuración ---------- */
  var RATE = <?= json_encode($tasa_bcv) ?>;   // Obtenida dinámicamente vía API PHP
  var PLAN_USD = 3;    // Precio mensual único

  var CATALOGS = {
    resto:{ctx:'<strong>Caja 1</strong> · Mesa 4', sold:1243.58, items:[
      {id:'a', name:'Burger clásica',  price:12.50, stock:24, color:'#c2571a', img:'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?auto=format&fit=crop&w=150&q=80'},
      {id:'b', name:'Papas fritas',    price:4.00,  stock:8,  color:'#a87706', img:'https://images.unsplash.com/photo-1576107232684-1279f390859f?auto=format&fit=crop&w=150&q=80'},
      {id:'c', name:'Coca-Cola 1,5 L', price:2.50,  stock:40, color:'#c4262e', img:'https://images.unsplash.com/photo-1622483767028-3f66f32aef97?auto=format&fit=crop&w=150&q=80'},
      {id:'d', name:'Burger doble',    price:18.00, stock:15, color:'#7a3b12', img:'https://images.unsplash.com/photo-1586816001966-79b736744398?auto=format&fit=crop&w=150&q=80'}]},
    bodega:{ctx:'<strong>Caja 1</strong> · Mostrador', sold:486.20, items:[
      {id:'a', name:'Harina de maíz',      price:1.40, stock:60, color:'#a87706', img:'https://images.unsplash.com/photo-1508344928928-7165b67de128?auto=format&fit=crop&w=150&q=80'},
      {id:'b', name:'Arroz 1 kg',          price:1.60, stock:9,  color:'#2f6f8f', img:'https://images.unsplash.com/photo-1586201375761-83865001e31c?auto=format&fit=crop&w=150&q=80'},
      {id:'c', name:'Aceite 1 L',          price:3.20, stock:18, color:'#3d7a1f', img:'https://images.unsplash.com/photo-1474979266404-7eaacbcd87c5?auto=format&fit=crop&w=150&q=80'},
      {id:'d', name:'Café 250 g',          price:2.80, stock:22, color:'#5b3a29', img:'https://images.unsplash.com/photo-1559525839-b184a4d698c7?auto=format&fit=crop&w=150&q=80'}]},
    ropa:{ctx:'<strong>Caja 1</strong> · Tienda', sold:812.40, items:[
      {id:'a', name:'Camisa casual',  price:29.99, stock:12, color:'#2350d8', img:'https://images.unsplash.com/photo-1598033129183-c4f50c736f10?auto=format&fit=crop&w=150&q=80'},
      {id:'b', name:'Jean clásico',   price:34.00, stock:7,  color:'#1a3ba8', img:'https://images.unsplash.com/photo-1541099649105-f69ad21f3246?auto=format&fit=crop&w=150&q=80'},
      {id:'c', name:'Franela básica', price:9.50,  stock:30, color:'#14875f', img:'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?auto=format&fit=crop&w=150&q=80'},
      {id:'d', name:'Gorra',          price:8.00,  stock:14, color:'#7a3b12', img:'https://images.unsplash.com/photo-1588850561407-ed78c282e89b?auto=format&fit=crop&w=150&q=80'}]}
  };
  var current = 'resto';
  var cart = {};

  /* ---------- Utilidades ---------- */
  var nf = function(n){ return n.toLocaleString('de-DE',{minimumFractionDigits:2,maximumFractionDigits:2}); };
  var fUsd = function(n){ return '$' + nf(n); };
  var fBs  = function(n){ return 'Bs. ' + nf(n); };
  function track(name){ window.dataLayer = window.dataLayer || []; window.dataLayer.push({event:name}); }

  var elProducts = document.getElementById('products');
  var elLines = document.getElementById('lines');
  var elUsd = document.getElementById('totalUsd');
  var elBs = document.getElementById('totalBs');
  var elToast = document.getElementById('toast');
  var elSold = document.getElementById('soldToday');
  var elCtx = document.getElementById('posCtx');
  document.getElementById('rateChip').textContent = 'Tasa (BCV ' + <?= json_encode($last_update) ?> + '): Bs. ' + nf(RATE) + ' por $';
  document.getElementById('priceBs').textContent = '≈ ' + fBs(PLAN_USD * RATE);

  function cat(){ return CATALOGS[current]; }
  function byId(id){ return cat().items.filter(function(p){return p.id===id;})[0]; }
  function stockClass(s){ return s === 0 ? 'out' : (s <= 10 ? 'low' : 'ok'); }
  function stockLabel(s){ return s === 0 ? 'Agotado' : (s <= 10 ? 'Quedan ' + s : 'Stock: ' + s); }

  function renderProducts(){
    elProducts.innerHTML = '';
    cat().items.forEach(function(p){
      var left = p.stock - (cart[p.id] || 0);
      var b = document.createElement('button');
      b.type = 'button';
      b.className = 'prod';
      b.disabled = left <= 0;
      b.setAttribute('aria-label', 'Agregar ' + p.name + ', ' + fUsd(p.price));
      b.innerHTML =
        '<span class="prod-tile" style="background: ' + p.color + (p.img ? ' url(' + p.img + ') center/cover' : '') + ';">' + (p.img ? '' : p.name.charAt(0)) + '</span>' +
        '<span class="prod-name">' + p.name + '</span>' +
        '<span class="prod-price" style="display:flex; flex-direction:column;">' + fUsd(p.price) + ' <small style="font-size:0.75rem; color:var(--muted); font-weight:600;">' + fBs(p.price * RATE) + '</small></span>' +
        '<span class="stock ' + stockClass(left) + '">' + stockLabel(left) + '</span>';
      b.addEventListener('click', function(){ add(p.id); track('demo_add_product'); });
      elProducts.appendChild(b);
    });
  }

  function renderTicket(){
    elLines.innerHTML = '';
    elCtx.innerHTML = cat().ctx;
    elSold.textContent = fUsd(cat().sold);
    var ids = Object.keys(cart).filter(function(k){return cart[k] > 0;});
    var total = 0;
    if(!ids.length){
      var li = document.createElement('li');
      li.className = 'ticket-empty';
      li.textContent = 'Toca un producto para agregarlo al ticket.';
      elLines.appendChild(li);
    }
    ids.forEach(function(id){
      var p = byId(id), q = cart[id], lt = p.price * q;
      total += lt;
      var li = document.createElement('li');
      li.className = 'line';
      li.innerHTML =
        '<span class="line-name">' + p.name + '</span>' +
        '<span class="line-total">' + fUsd(lt) + '</span>' +
        '<span class="qty"><button type="button" aria-label="Quitar uno de ' + p.name + '">−</button><span>' + q + '</span><button type="button" aria-label="Agregar uno de ' + p.name + '">+</button></span>';
      var btns = li.querySelectorAll('button');
      btns[0].addEventListener('click', function(){ remove(id); });
      btns[1].addEventListener('click', function(){ add(id); });
      elLines.appendChild(li);
    });
    elUsd.textContent = fUsd(total);
    elBs.textContent = fBs(total * RATE);
    renderProducts();
  }

  function add(id){
    var p = byId(id);
    if((cart[id] || 0) < p.stock){ cart[id] = (cart[id] || 0) + 1; elToast.textContent = ''; renderTicket(); }
  }
  function remove(id){
    if(cart[id]){ cart[id] -= 1; if(cart[id] <= 0){ delete cart[id]; } renderTicket(); }
  }

  document.getElementById('payBtn').addEventListener('click', function(){
    var ids = Object.keys(cart);
    if(!ids.length){ elToast.style.color = 'var(--amber)'; elToast.textContent = 'Agrega un producto para poder cobrar.'; return; }
    var total = 0;
    ids.forEach(function(id){ var p = byId(id); total += p.price * cart[id]; p.stock -= cart[id]; });
    cat().sold += total;
    cart = {};
    elToast.style.color = 'var(--green)';
    elToast.textContent = 'Venta registrada. El inventario ya se actualizó.';
    track('demo_pay');
    renderTicket();
  });

  /* Selector de tipo de negocio */
  var segBtns = Array.prototype.slice.call(document.querySelectorAll('.seg button'));
  segBtns.forEach(function(b){
    b.addEventListener('click', function(){
      current = b.getAttribute('data-cat');
      cart = {};
      elToast.textContent = '';
      segBtns.forEach(function(x){ x.setAttribute('aria-pressed', x === b ? 'true' : 'false'); });
      track('demo_switch_' + current);
      renderTicket();
    });
  });

  renderTicket();

  /* ---------- Pestañas de módulos ---------- */
  var tabs = Array.prototype.slice.call(document.querySelectorAll('.tab'));
  function selectTab(tab){
    tabs.forEach(function(t){
      var on = t === tab;
      t.setAttribute('aria-selected', on ? 'true' : 'false');
      t.tabIndex = on ? 0 : -1;
      var panel = document.getElementById(t.getAttribute('aria-controls'));
      panel.classList.toggle('active', on);
      panel.hidden = !on;
    });
    track('modulo_' + tab.id);
  }
  tabs.forEach(function(t, i){
    t.addEventListener('click', function(){ selectTab(t); });
    t.addEventListener('keydown', function(e){
      var n = null;
      if(e.key === 'ArrowRight'){ n = tabs[(i + 1) % tabs.length]; }
      if(e.key === 'ArrowLeft'){ n = tabs[(i - 1 + tabs.length) % tabs.length]; }
      if(n){ e.preventDefault(); selectTab(n); n.focus(); }
    });
  });

  /* ---------- Menú móvil ---------- */
  var menuBtn = document.getElementById('menuBtn');
  var nav = document.getElementById('nav');
  menuBtn.addEventListener('click', function(){
    var open = nav.classList.toggle('open');
    menuBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
  });
  nav.addEventListener('click', function(e){
    if(e.target.tagName === 'A'){ nav.classList.remove('open'); menuBtn.setAttribute('aria-expanded','false'); }
  });

  /* ---------- Analítica (Google Tag Manager) ---------- */
  document.addEventListener('click', function(e){
    var el = e.target.closest ? e.target.closest('[data-evt]') : null;
    if(el){ track(el.getAttribute('data-evt')); }
  });

  // Auto-abrir modal si viene con ?login=1 o hay error de login
  if (window.location.search.indexOf('login=1') !== -1 || <?= !empty($_SESSION['login_error']) ? 'true' : 'false' ?>) {
      document.getElementById('login-modal').style.display = 'flex';
  }
})();
</script>
</body>
</html>
