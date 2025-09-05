<?php
/**
 * plugins/gestion/front/ors_modal.inc.php
 * - Modal Leaflet + OpenRouteService (ORS)
 * - Géolocalisation : utilise la position courante si dispo, sinon adresse par défaut
 * - Boutons Google Maps / Waze / Plans (Apple)
 * - Backdrop non-static, règles CSS scopées
 */

include(GLPI_ROOT . "/inc/includes.php");

$config = new PluginRtConfig();

if (!defined('PLUGIN_GESTION_ORS_MODAL')) {
   define('PLUGIN_GESTION_ORS_MODAL', 1);

   $ORS_API_KEY = $config->ORS_API_KEY();
   // Ne PAS return : on rend le modal quand même et on affichera une erreur dans l’UI
   // Si vide, ORS_API_KEY côté JS sera "null"
   // echo 'ORS_API_KEY : '.$ORS_API_KEY;

   // Valeurs par défaut (surcharge possibles avant include)
   $ORIGIN_LABEL = isset($ORIGIN_LABEL) ? $ORIGIN_LABEL : '193 rue du Général Metman, 57070 Metz, France';
   $ORIGIN_LAT   = isset($ORIGIN_LAT)   ? $ORIGIN_LAT   : 49.11450;
   $ORIGIN_LON   = isset($ORIGIN_LON)   ? $ORIGIN_LON   : 6.21600;
?>
<!-- Modal ORS -->
<div class="modal fade" id="orsModal" tabindex="-1"
     data-bs-keyboard="true"
     aria-labelledby="orsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
    <div class="modal-content">
      <!-- overlay spinner couvrant tout le modal -->
      <div id="modal-spinner" class="modal-spinner-overlay" hidden>
        <div class="spinner"></div>
        <div class="spinner-text">Chargement de l’itinéraire…</div>
      </div>

      <div class="modal-header">
        <h5 class="modal-title" id="orsModalLabel">Itinéraire</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
      </div>

      <div class="modal-body">
        <!-- Message si API key absente -->
        <div id="api-missing" class="alert alert-danger d-none" role="alert">
          <div class="fw-bold mb-1">Clé OpenRouteService manquante</div>
          <div>Impossible d’afficher l’itinéraire sans clé API ORS. Veuillez renseigner la clé dans la configuration puis réessayer.</div>
        </div>

        <div id="ors-body" class="row g-3">
          <div class="col-12 col-lg-7">
            <div id="ors-map" style="width:100%; height:520px; border-radius:12px; overflow:hidden; background:#f5f5f5"></div>
          </div>

          <div class="col-12 col-lg-5">
            <div class="card p-3 info-panel">
              <div class="mb-2">
                <strong>Départ :</strong><br>
                <span id="originLabel"><?= htmlspecialchars($ORIGIN_LABEL, ENT_QUOTES, 'UTF-8') ?></span>
                <small id="originStatus" class="text-muted ms-2"></small>
              </div>
              <div class="mb-2"><strong>Arrivée :</strong><br><span id="destLabel"></span></div>
              <hr/>
              <div class="mb-1"><strong>Distance :</strong> <span id="ors-distance">—</span></div>
              <div class="mb-1"><strong>Durée :</strong> <span id="ors-duration">—</span></div>

              <!-- Profil sur UNE ligne, sans coupure -->
              <div class="mb-1 d-flex flex-nowrap align-items-center gap-2">
                <span class="text-muted flex-shrink-0">Profil :</span>
                <span id="ors-profile-label" class="fw-semibold flex-shrink-0">Voiture</span>
              </div>

              <div class="mt-3">
                <label class="form-label">Mode / Profil ORS</label>
                <select id="ors-profile" class="form-select form-select-sm">
                  <option value="driving-car" selected>Voiture (driving-car)</option>
                  <option value="driving-hgv">Poids-lourd (driving-hgv)</option>
                  <option value="cycling-regular">Vélo (cycling-regular)</option>
                  <option value="foot-walking">À pied (foot-walking)</option>
                </select>
              </div>

              <!-- Paramètres poids-lourd — cachés par défaut -->
              <div id="hgv-panel" class="mt-3" hidden>
                <div class="d-flex align-items-center justify-content-between">
                  <h6 class="mb-2">Paramètres poids-lourd</h6>
                  <span class="badge bg-secondary">m / t</span>
                </div>
                <div class="row g-2">
                  <div class="col-6">
                    <label class="form-label">Hauteur (m)</label>
                    <input id="hgv-height" type="number" step="0.1" min="0" class="form-control form-control-sm" placeholder="ex: 3.5">
                  </div>
                  <div class="col-6">
                    <label class="form-label">Largeur (m)</label>
                    <input id="hgv-width" type="number" step="0.1" min="0" class="form-control form-control-sm" placeholder="ex: 2.55">
                  </div>
                  <div class="col-6">
                    <label class="form-label">Longueur (m)</label>
                    <input id="hgv-length" type="number" step="0.1" min="0" class="form-control form-control-sm" placeholder="ex: 12.0">
                  </div>
                  <div class="col-6">
                    <label class="form-label">Poids total (t)</label>
                    <input id="hgv-weight" type="number" step="0.1" min="0" class="form-control form-control-sm" placeholder="ex: 26">
                  </div>
                  <div class="col-6">
                    <label class="form-label">Charge essieu (t)</label>
                    <input id="hgv-axle" type="number" step="0.1" min="0" class="form-control form-control-sm" placeholder="ex: 11.5">
                  </div>
                  <div class="col-6">
                    <label class="form-label">Matières dangereuses</label>
                    <select id="hgv-hazmat" class="form-select form-select-sm">
                      <option value="">Aucune</option>
                      <option value="true">Oui</option>
                    </select>
                  </div>
                  <div class="col-6">
                    <div class="form-check mt-4">
                      <input id="avoid-tolls" class="form-check-input" type="checkbox">
                      <label for="avoid-tolls" class="form-check-label">Éviter péages</label>
                    </div>
                  </div>
                  <div class="col-6">
                    <div class="form-check mt-4">
                      <input id="avoid-ferries" class="form-check-input" type="checkbox">
                      <label for="avoid-ferries" class="form-check-label">Éviter ferries</label>
                    </div>
                  </div>
                </div>
                <button id="apply-hgv" class="btn btn-sm btn-outline-primary mt-2">Recalculer avec ces paramètres</button>
              </div>

              <!-- Liens externes -->
              <div class="mt-3 d-grid gap-2">
                <a id="gmapsLink" class="btn btn-outline-secondary btn-sm" target="_blank" rel="noopener">Ouvrir dans Google Maps</a>
                <a id="wazeLink"  class="btn btn-outline-secondary btn-sm" target="_blank" rel="noopener">Ouvrir dans Waze</a>
                <a id="appleLink" class="btn btn-outline-secondary btn-sm" target="_blank" rel="noopener">Ouvrir dans Plans (Apple)</a>
              </div>

              <!-- Crédits -->
              <div class="credits-line text-muted mt-3 no-wrap">
                © OpenStreetMap contributors <br> Routage © OpenRouteService
              </div>

              <div id="ors-error" class="text-danger mt-2" hidden></div>
            </div>
          </div>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Fermer</button>
      </div>
    </div>
  </div>
</div>

<!-- Leaflet -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

<script>
(function(){
  // Si la clé est vide côté PHP, on l’émet en null côté JS
  const ORS_KEY  = <?= $ORS_API_KEY ? json_encode($ORS_API_KEY) : 'null' ?>;

  // Origine par défaut (PHP) + origine courante (JS, potentiellement géolocalisée)
  const ORIGIN_DEFAULT = {
    lat: <?= json_encode($ORIGIN_LAT) ?>,
    lon: <?= json_encode($ORIGIN_LON) ?>,
    label: <?= json_encode($ORIGIN_LABEL) ?>
  };
  let ORIGIN = { ...ORIGIN_DEFAULT };
  let originIsGeo = false;

  let map, routeLayer, originMarker, destMarker;
  let geocodeController = null;
  let routeController   = null;
  const destCache = new Map(); // key: adresse => {lat,lon,label,ts}

  function fmtDistance(m){ return (m/1000).toFixed(1) + ' km'; }
  function fmtDuration(s){ return Math.round(s/60) + ' min'; }
  function profileLabel(p){
    switch(p){
      case 'driving-car': return 'Voiture';
      case 'driving-hgv': return 'Poids-lourd';
      case 'cycling-regular': return 'Vélo';
      case 'foot-walking': return 'À pied';
      default: return p;
    }
  }
  function showPanel(show){ document.getElementById('hgv-panel').hidden = !show; }

  // Spinner plein écran (sur tout le modal)
  function showSpinner(){
    const el = document.getElementById('modal-spinner');
    el.dataset.waiting = '1';
    setTimeout(()=>{ if(el.dataset.waiting==='1') el.hidden = false; }, 250);
  }
  function hideSpinner(){
    const el = document.getElementById('modal-spinner');
    el.dataset.waiting = '0';
    el.hidden = true;
  }

  function setError(msg){
    const el=document.getElementById('ors-error');
    if(msg){el.textContent=msg; el.hidden=false;} else {el.textContent=''; el.hidden=true;}
  }

  function withTimeout(promise, ms, onAbort){
    let t;
    return Promise.race([
      promise,
      new Promise((_,rej)=>t=setTimeout(()=>{onAbort&&onAbort(); rej(new Error('timeout'));},ms))
    ]).finally(()=>clearTimeout(t));
  }

  // --- Géolocalisation : essaie de récupérer la position de l'appareil ---
  function resolveOrigin() {
    const status = document.getElementById('originStatus');
    const label  = document.getElementById('originLabel');

    // Valeurs initiales (fallback par défaut)
    originIsGeo = false;
    ORIGIN = { ...ORIGIN_DEFAULT };
    label.textContent = ORIGIN.label;
    status.textContent = 'Détection de la localisation…';

    return new Promise((resolve) => {
      if (!('geolocation' in navigator)) {
        status.textContent = 'Localisation non accessible — adresse par défaut';
        return resolve(false);
      }

      const opts = { enableHighAccuracy: true, timeout: 5000, maximumAge: 120000 };
      let settled = false;

      navigator.geolocation.getCurrentPosition(
        (pos) => {
          if (settled) return;
          settled = true;
          const { latitude, longitude } = (pos && pos.coords) || {};
          if (typeof latitude === 'number' && typeof longitude === 'number') {
            ORIGIN = { lat: latitude, lon: longitude, label: 'Ma position' };
            originIsGeo = true;
            label.textContent = ORIGIN.label;
            status.textContent = 'Localisation actuelle';
            resolve(true);
          } else {
            status.textContent = 'Localisation non accessible — adresse par défaut';
            resolve(false);
          }
        },
        (_err) => {
          if (settled) return;
          settled = true;
          status.textContent = 'Localisation non accessible — adresse par défaut';
          resolve(false);
        },
        opts
      );

      // Sécurité : timeout manuel au cas où (si le browser ignore le timeout)
      setTimeout(() => {
        if (!settled) {
          settled = true;
          status.textContent = 'Localisation non accessible — adresse par défaut';
          resolve(false);
        }
      }, 5500);
    });
  }

  async function geocodeORS(text){
    if (!ORS_KEY) throw new Error('ORS API key missing');
    const cached = destCache.get(text); const now = Date.now();
    if (cached && (now - cached.ts) < 120000) return cached;

    if (geocodeController) geocodeController.abort();
    geocodeController = new AbortController();

    const url = 'https://api.openrouteservice.org/geocode/search?api_key=' + encodeURIComponent(ORS_KEY)
              + '&text=' + encodeURIComponent(text) + '&size=1&language=fr';

    const res = await withTimeout(fetch(url, { signal: geocodeController.signal }), 10000, () => geocodeController.abort());
    if(!res.ok) throw new Error('Geocode HTTP ' + res.status);
    const data = await res.json();
    if(!data.features || !data.features[0]) throw new Error('Adresse introuvable : ' + text);
    const [lon, lat] = data.features[0].geometry.coordinates;
    const out = { lat, lon, label: data.features[0].properties.label || text, ts: now };
    destCache.set(text, out);
    return out;
  }

  function buildHgvRestrictions(){
    const height = parseFloat(document.getElementById('hgv-height').value);
    const width  = parseFloat(document.getElementById('hgv-width').value);
    const length = parseFloat(document.getElementById('hgv-length').value);
    const weight = parseFloat(document.getElementById('hgv-weight').value);
    const axle   = parseFloat(document.getElementById('hgv-axle').value);
    const hazmat = (document.getElementById('hgv-hazmat').value === 'true');

    const restrictions = {};
    if(!isNaN(height)) restrictions.height = height;
    if(!isNaN(width))  restrictions.width  = width;
    if(!isNaN(length)) restrictions.length = length;
    if(!isNaN(weight)) restrictions.weight = weight;
    if(!isNaN(axle))   restrictions.axleload = axle;
    if(hazmat)         restrictions.hazmat = true;

    const avoid_features = [];
    if (document.getElementById('avoid-tolls').checked)   avoid_features.push('tollways');
    if (document.getElementById('avoid-ferries').checked) avoid_features.push('ferries');

    const body = {};
    if (Object.keys(restrictions).length) body.profile_params = { restrictions };
    if (avoid_features.length)            body.options        = { avoid_features };
    return body;
  }

  async function routeORS(profile, origin, dest){
    if (!ORS_KEY) throw new Error('ORS API key missing');
    if (routeController) routeController.abort();
    routeController = new AbortController();

    const url = 'https://api.openrouteservice.org/v2/directions/' + encodeURIComponent(profile) + '/geojson';
    const baseBody = {
      coordinates: [[origin.lon, origin.lat], [dest.lon, dest.lat]],
      instructions: false,
      units: 'm'
    };
    const body = (profile === 'driving-hgv') ? { ...baseBody, ...buildHgvRestrictions() } : baseBody;

    const res = await withTimeout(fetch(url, {
      method: 'POST',
      headers: { 'Authorization': ORS_KEY, 'Content-Type': 'application/json; charset=utf-8' },
      body: JSON.stringify(body),
      signal: routeController.signal
    }), 12000, () => routeController.abort());
    if(!res.ok) throw new Error('Directions HTTP ' + res.status);
    const data = await res.json();
    if(!data.features || !data.features[0]) throw new Error('Route introuvable');
    const feat = data.features[0];
    const summary = feat.properties.summary;
    return { geojson: feat, distance: summary.distance, duration: summary.duration };
  }

  function initMap(center){
    if(map){ map.setView(center, 13); return; }
    map = L.map('ors-map').setView(center, 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OpenStreetMap contributors' }).addTo(map);
  }

  function drawRoute(geojson, origin, dest){
    if(routeLayer){ routeLayer.clearLayers(); } else { routeLayer = L.geoJSON(null, { style: { weight: 5 } }).addTo(map); }
    routeLayer.addData(geojson);

    if(originMarker) originMarker.remove();
    if(destMarker)   destMarker.remove();
    originMarker = L.marker([origin.lat, origin.lon]).addTo(map);
    destMarker   = L.marker([dest.lat, dest.lon]).addTo(map);

    const b = L.latLngBounds([]);
    geojson.geometry.coordinates.forEach(c => b.extend([c[1], c[0]]));
    map.fitBounds(b, { padding: [40,40] });
  }

  async function renderItinerary(destText, profile){
    setError('');
    if (!ORS_KEY) {
      // Pas de requêtes ni de spinner : on laisse le bloc erreur faire le job
      return;
    }
    showSpinner();
    try{
      const dest = await geocodeORS(destText);
      initMap([ORIGIN.lat, ORIGIN.lon]);

      const route = await routeORS(profile, ORIGIN, dest);

      document.getElementById('ors-distance').textContent = fmtDistance(route.distance);
      document.getElementById('ors-duration').textContent = fmtDuration(route.duration);
      document.getElementById('ors-profile-label').textContent = profileLabel(profile);
      drawRoute(route.geojson, ORIGIN, dest);

      // Liens externes
      document.getElementById('gmapsLink').href =
        'https://www.google.com/maps/dir/?api=1'
        + '&origin=' + encodeURIComponent(originIsGeo ? (ORIGIN.lat + ',' + ORIGIN.lon) : ORIGIN.label)
        + '&destination=' + encodeURIComponent(destText)
        + '&travelmode=' + (profile.startsWith('driving') ? 'driving' : (profile.startsWith('foot') ? 'walking' : 'bicycling'));

      document.getElementById('wazeLink').href =
        'https://waze.com/ul?ll=' + dest.lat + ',' + dest.lon + '&navigate=yes';

      document.getElementById('appleLink').href =
        'http://maps.apple.com/?daddr=' + dest.lat + ',' + dest.lon + '&dirflg=d';

    }catch(err){
      console.error(err);
      document.getElementById('ors-distance').textContent = 'Erreur';
      document.getElementById('ors-duration').textContent = '—';
      setError('Impossible de calculer l’itinéraire. Vérifiez les paramètres et réessayez.');
    }finally{
      hideSpinner();
    }
  }

  function toggleApiMissingUI(show){
    const miss = document.getElementById('api-missing');
    const body = document.getElementById('ors-body');
    const links = ['gmapsLink','wazeLink','appleLink'].map(id=>document.getElementById(id));

    if (show) {
      miss.classList.remove('d-none');
      body.classList.add('d-none');
      // Désactive les boutons externes
      links.forEach(a => { if (a) { a.classList.add('disabled'); a.setAttribute('aria-disabled','true'); a.removeAttribute('href'); } });
    } else {
      miss.classList.add('d-none');
      body.classList.remove('d-none');
      // Réactive (les href seront recalculés après le rendu)
      links.forEach(a => { if (a) { a.classList.remove('disabled'); a.removeAttribute('aria-disabled'); } });
    }
  }

  function openModal(destText){
    // Libellés init
    document.getElementById('destLabel').textContent         = destText || '';
    document.getElementById('ors-distance').textContent      = '—';
    document.getElementById('ors-duration').textContent      = '—';
    document.getElementById('ors-profile').value             = 'driving-car';
    document.getElementById('ors-profile-label').textContent = 'Voiture';
    document.getElementById('originLabel').textContent       = ORIGIN_DEFAULT.label;
    document.getElementById('originStatus').textContent      = '';
    showPanel(false);
    document.getElementById('orsModalLabel').textContent     = destText ? ('Itinéraire vers ' + destText) : 'Itinéraire';

    // Affichage erreur si clé manquante
    toggleApiMissingUI(!ORS_KEY);

    const modalEl = document.getElementById('orsModal');

    // IMPORTANT: placer le modal en enfant direct de <body> pour un backdrop correct
    if (modalEl.parentElement !== document.body) {
      document.body.appendChild(modalEl);
    }

    // Modal Bootstrap : backdrop par défaut (clic extérieur ferme le modal)
    const bsModal = new bootstrap.Modal(modalEl, {
      backdrop: true,
      keyboard: true,
      focus: true
    });
    bsModal.show();

    // Ajouter une classe sur le backdrop du modal ORS seulement
    modalEl.addEventListener('shown.bs.modal', () => {
      const bd = document.querySelector('.modal-backdrop');
      if (bd) bd.classList.add('ors-backdrop');
    }, { once: true });

    // Nettoyage à la fermeture (spinner, etc.)
    modalEl.addEventListener('hidden.bs.modal', () => {
      const el = document.getElementById('modal-spinner');
      if (el) { el.dataset.waiting = '0'; el.hidden = true; }
    }, { once: true });

    // Si pas de clé, on n’essaie rien d’autre.
    if (!ORS_KEY) return;

    // Résoudre l'origine (géoloc si possible), puis calculer l'itinéraire
    resolveOrigin().then(() => {
      setTimeout(() => renderItinerary(destText, 'driving-car'), 50);
    });
  }

  // Ouverture via bouton externe ".js-open-route[data-dest]"
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('.js-open-route');
    if (!btn) return;
    openModal(btn.getAttribute('data-dest') || '');
  });

  // Changement de profil
  document.getElementById('ors-profile').addEventListener('change', function(){
    if (!ORS_KEY) return; // rien à faire si pas de clé
    const profile = this.value;
    document.getElementById('ors-profile-label').textContent = profileLabel(profile);
    showPanel(profile === 'driving-hgv');
    const destText = document.getElementById('destLabel').textContent;
    if (destText) renderItinerary(destText, profile);
  });

  // Recalcul explicite après modification des paramètres PL
  document.getElementById('apply-hgv')?.addEventListener('click', function(){
    if (!ORS_KEY) return;
    const destText = document.getElementById('destLabel').textContent;
    if (destText) renderItinerary(destText, 'driving-hgv');
  });
})();
</script>

<style>
  /* largeur XL propre et responsive */
  #orsModal .modal-dialog.modal-xl {
    max-width: none !important;
    width: min(1200px, 96vw) !important;
  }

  /* z-index du modal ORS uniquement */
  #orsModal { z-index: 1090; }

  /* z-index du backdrop, mais uniquement pour l’ORS */
  .modal-backdrop.ors-backdrop { z-index: 1085; }

  /* Le modal a besoin d'un contexte pour l'overlay */
  #orsModal .modal-content { position: relative; border-radius: 14px; }
  #orsModal .card { border: none; box-shadow: 0 1px 6px rgba(0,0,0,.05); border-radius: 12px; }

  /* Empêcher la coupure (profil / crédits) */
  .no-wrap { white-space: nowrap; }

  /* Overlay spinner couvrant TOUT le modal */
  .modal-spinner-overlay{
    position:absolute; inset:0;
    display:flex; align-items:center; justify-content:center;
    background:rgba(245,247,250,.78);
    backdrop-filter: blur(1px);
    z-index: 2000;
  }
  .spinner { width:36px; height:36px; border-radius:50%;
             border:4px solid rgba(0,0,0,.15); border-top-color: rgba(0,0,0,.6);
             animation: spin .8s linear infinite; margin-right:12px; }
  .spinner-text { font-size:.95rem; color:#333; }
  @keyframes spin { to { transform: rotate(360deg); } }
</style>
<?php } // fin de la garde d'inclusion ?>
