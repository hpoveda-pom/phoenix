<!doctype html>
<html lang="es" data-bs-theme="dark">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Phoenix — Dashboard Diario del Gestor</title>

  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

  <style>
    :root{
      --soft-border: rgba(255,255,255,.10);
      --soft-bg: rgba(255,255,255,.04);
    }

    body{
      background: var(--bs-body-bg);
      color: var(--bs-body-color);
    }

    .app-header{
      border-bottom: 1px solid var(--soft-border);
      background: linear-gradient(180deg, rgba(255,255,255,.02), transparent);
    }

    .card-soft{
      border: 1px solid var(--soft-border);
      background: var(--soft-bg);
      border-radius: 14px;
    }

    /* KPI */
    .kpi-card{
      border: 1px solid var(--soft-border);
      background: var(--soft-bg);
      border-radius: 14px;
      transition: transform .06s ease, filter .06s ease;
      cursor: pointer;
      user-select:none;
    }
    .kpi-card:hover{ filter: brightness(1.06); }
    .kpi-card:active{ transform: translateY(1px); }

    .kpi-card.active{
      outline: 2px solid rgba(13,110,253,.65);
      box-shadow: 0 0 0 6px rgba(13,110,253,.15);
    }

    .kpi-value{
      font-size: 2.1rem;
      font-weight: 800;
      letter-spacing: .2px;
      line-height: 1;
    }

    .kpi-label{
      font-size: .85rem;
      opacity: .85;
      font-weight: 700;
    }

    .mini-help{
      font-size: .85rem;
      opacity: .75;
    }

    /* Table */
    .table thead th{
      font-size: .75rem;
      text-transform: uppercase;
      letter-spacing: .04em;
      opacity: .8;
      border-bottom: 1px solid var(--soft-border);
    }

    .badge-soft{
      border: 1px solid var(--soft-border);
      background: rgba(255,255,255,.05);
      font-weight: 600;
    }

    .mono{ font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; }

    /* Channel icons */
    .ch-icon{
      width: 26px; height: 26px;
      display:inline-flex;
      align-items:center;
      justify-content:center;
      border-radius: 9px;
      border: 1px solid rgba(255,255,255,.10);
      background: rgba(255,255,255,.05);
    }
    .ch-icon.wa{ color:#25D366; }
    .ch-icon.sms{ color:#ffd166; }
    .ch-icon.mail{ color:#7aa2ff; }
    .ch-icon.call{ color:#ff6b6b; }

    .num-pill{
      min-width: 22px;
      height: 20px;
      padding: 0 6px;
      display:inline-flex;
      align-items:center;
      justify-content:center;
      border-radius: 999px;
      font-size: .75rem;
      font-weight: 800;
      background: rgba(255,255,255,.08);
      border: 1px solid rgba(255,255,255,.10);
    }
  </style>
</head>

<body>

  <!-- HEADER -->
  <div class="app-header py-3">
    <div class="container-fluid px-4">
      <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
        <div>
          <div class="d-flex align-items-center gap-2">
            <h1 class="h5 mb-0 fw-bold">Phoenix — Dashboard Diario del Gestor</h1>
            <span class="badge badge-soft text-secondary">Autosupervisión</span>
          </div>
          <div class="text-secondary small mt-1">
            Enfocado en <b>HOY</b> · Meta operativa: <b>65 promesas/día</b> · Click en un indicador → te muestra el problema abajo
          </div>
        </div>

        <div class="d-flex align-items-center gap-2">
          <div class="small text-secondary">
            <span>Fecha local:</span> <b id="now"></b>
          </div>

          <button id="btnTheme" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-circle-half"></i> Tema
          </button>
        </div>
      </div>
    </div>
  </div>

  <main class="container-fluid px-4 py-4">

    <!-- FILTROS -->
    <div class="row g-3">
      <div class="col-12 col-lg-3">
        <div class="card-soft p-3 h-100">
          <div class="text-secondary small fw-semibold mb-2">
            <i class="bi bi-calendar-event"></i> Filtro · Día
          </div>
          <label class="form-label small text-secondary mb-1">Fecha (HOY)</label>
          <input id="fDate" type="date" class="form-control form-control-sm">
        </div>
      </div>

      <div class="col-12 col-lg-3">
        <div class="card-soft p-3 h-100">
          <div class="text-secondary small fw-semibold mb-2">
            <i class="bi bi-person-badge"></i> Filtro · Gestor
          </div>
          <label class="form-label small text-secondary mb-1">Gestor</label>
          <select id="fGestor" class="form-select form-select-sm"></select>
        </div>
      </div>

      <div class="col-12 col-lg-3">
        <div class="card-soft p-3 h-100">
          <div class="text-secondary small fw-semibold mb-2">
            <i class="bi bi-diagram-3"></i> Filtro · Estado
          </div>
          <label class="form-label small text-secondary mb-1">Estado</label>
          <select id="fEstado" class="form-select form-select-sm"></select>
        </div>
      </div>

      <div class="col-12 col-lg-3">
        <div class="card-soft p-3 h-100">
          <div class="text-secondary small fw-semibold mb-2">
            <i class="bi bi-lightning-charge"></i> Filtro · Acción
          </div>
          <label class="form-label small text-secondary mb-1">Acción requerida</label>
          <select id="fAccion" class="form-select form-select-sm"></select>
        </div>
      </div>

      <div class="col-12 col-lg-8">
        <div class="card-soft p-3 h-100">
          <div class="text-secondary small fw-semibold mb-2">
            <i class="bi bi-chat-square-dots"></i> Filtro · Canal predominante
          </div>

          <label class="form-label small text-secondary mb-1">Canal</label>
          <select id="fCanal" class="form-select form-select-sm">
            <option value="">Todos</option>
            <option value="WhatsApp">WhatsApp</option>
            <option value="SMS">SMS</option>
            <option value="Correo">Correo</option>
            <option value="Llamada">Llamada</option>
            <option value="Mixto">Mixto</option>
            <option value="Ninguno">Ninguno</option>
          </select>

          <div class="mini-help text-secondary mt-2">
            Tip: Esto ayuda a detectar dónde aplicar robots (por ejemplo SMS solo en No Contactados).
          </div>
        </div>
      </div>

      <div class="col-12 col-lg-4">
        <div class="card-soft p-3 h-100">
          <div class="text-secondary small fw-semibold mb-2">
            <i class="bi bi-crosshair"></i> Modo revisión (control)
          </div>

          <div class="alert alert-primary py-2 px-3 small mb-3" id="reviewMode">
            Ninguno. Mostrando todo lo filtrado.
          </div>

          <div class="d-flex gap-2 flex-wrap justify-content-end">
            <button class="btn btn-outline-secondary btn-sm" id="btnClearReview">
              <i class="bi bi-x-circle"></i> Quitar revisión
            </button>
            <button class="btn btn-outline-secondary btn-sm" id="btnReset">
              <i class="bi bi-arrow-counterclockwise"></i> Reset
            </button>
            <button class="btn btn-primary btn-sm" id="btnApply">
              <i class="bi bi-check2-circle"></i> Aplicar
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- KPIs -->
    <div class="row g-3 mt-1">

      <div class="col-12 col-lg-4">
        <div class="kpi-card p-3 h-100" id="kpiPromesas" data-review="PROMESAS_FALTANTES">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <div class="kpi-label text-secondary">
                <i class="bi bi-flag"></i> Promesas HOY (Meta 65)
              </div>
              <div class="kpi-value">
                <span id="kPromesas">—</span>
                <span class="text-secondary fs-6">/ 65</span>
              </div>
            </div>
            <span class="badge text-bg-danger" id="bPromesas">FALTAN —</span>
          </div>

          <div class="mt-2 small text-secondary">
            Falta por cumplir: <b id="kFaltan">—</b>
          </div>

          <div class="progress mt-2" style="height:10px;">
            <div class="progress-bar" id="pPromesas" style="width:0%"></div>
          </div>

          <div class="mini-help mt-2">
            Click → te muestra casos donde deberías cerrar promesa hoy.
          </div>
        </div>
      </div>

      <div class="col-12 col-lg-4">
        <div class="kpi-card p-3 h-100" id="kpiNoContact" data-review="NO_CONTACTADOS">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <div class="kpi-label text-secondary">
                <i class="bi bi-exclamation-triangle"></i> Error HOY · No contactados
              </div>
              <div class="kpi-value" id="kNoContact">—</div>
            </div>
            <span class="badge text-bg-danger">CRÍTICO</span>
          </div>

          <div class="mini-help mt-2">
            0 WhatsApp/SMS/Correo/Llamada → aquí aplica robot (no llamadas / no SMS manual).
          </div>
        </div>
      </div>

      <div class="col-12 col-lg-4">
        <div class="kpi-card p-3 h-100" id="kpiChecklist" data-review="CHECKLIST_INCOMPLETO">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <div class="kpi-label text-secondary">
                <i class="bi bi-list-check"></i> Disciplina HOY · Checklist incompleto
              </div>
              <div class="kpi-value" id="kChecklist">—</div>
            </div>
            <span class="badge text-bg-warning text-dark">BLOQUEA</span>
          </div>

          <div class="mini-help mt-2">
            Click → te lista exactamente los casos donde el gestor se está saltando el control.
          </div>
        </div>
      </div>

      <div class="col-12 col-lg-4">
        <div class="kpi-card p-3 h-100" id="kpiSinAt" data-review="SIN_ATENCION_ALTA">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <div class="kpi-label text-secondary">
                <i class="bi bi-hourglass-split"></i> Riesgo HOY · Sin atención
              </div>
              <div class="kpi-value">
                <span id="kSinAt">—</span> <span class="fs-6 text-secondary">min</span>
              </div>
            </div>
            <span class="badge text-bg-secondary" id="bSinAt">—</span>
          </div>

          <div class="mini-help mt-2">
            Click → lista los casos con espera alta (donde se está perdiendo el día).
          </div>
        </div>
      </div>

      <div class="col-12 col-lg-4">
        <div class="kpi-card p-3 h-100" id="kpiAcciones" data-review="ACCION_REQUERIDA">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <div class="kpi-label text-secondary">
                <i class="bi bi-lightning-charge"></i> Trabajo HOY · Acciones pendientes
              </div>
              <div class="kpi-value" id="kAccPend">—</div>
            </div>
            <span class="badge text-bg-warning text-dark">EN COLA</span>
          </div>

          <div class="mini-help mt-2">
            Click → muestra acciones por ejecutar YA (Llamar / SMS).
          </div>
        </div>
      </div>

      <div class="col-12 col-lg-4">
        <div class="kpi-card p-3 h-100" id="kpiPrioridad" data-review="INCUMPLIDAS">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <div class="kpi-label text-secondary">
                <i class="bi bi-fire"></i> Urgente HOY · Incumplidas (prioridad)
              </div>
              <div class="kpi-value" id="kIncumplidas">—</div>
            </div>
            <span class="badge text-bg-danger">URGENTE</span>
          </div>

          <div class="mini-help mt-2">
            Click → te lista solo “Incumplida (prioridad)” para atacar primero.
          </div>
        </div>
      </div>
    </div>

    <!-- Tabla -->
    <div class="row g-3 mt-1">
      <div class="col-12">
        <div class="card-soft p-3" id="tblCard">
          <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-2">
            <div>
              <div class="fw-bold">
                <i class="bi bi-list-task"></i> Lista para ejecutar (HOY)
              </div>
              <div class="text-secondary small">
                Orden: Incumplida (prioridad) → mayor SinAtención → menor Progreso
              </div>
            </div>
            <span class="badge badge-soft text-secondary">Contact Center Ready</span>
          </div>

          <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
              <thead>
                <tr>
                  <th>Prioridad</th>
                  <th>Operación</th>
                  <th>Estado</th>
                  <th>Acción</th>
                  <th class="text-end">SinAt</th>
                  <th class="text-end">Progreso</th>
                  <th>Canal</th>
                  <th>No contactado</th>
                  <th>Checklist</th>

                  <th class="text-end">
                    <span class="ch-icon wa" title="WhatsApp"><i class="bi bi-whatsapp"></i></span>
                  </th>
                  <th class="text-end">
                    <span class="ch-icon sms" title="SMS"><i class="bi bi-chat-dots"></i></span>
                  </th>
                  <th class="text-end">
                    <span class="ch-icon mail" title="Correo"><i class="bi bi-envelope"></i></span>
                  </th>
                  <th class="text-end">
                    <span class="ch-icon call" title="Llamada"><i class="bi bi-telephone"></i></span>
                  </th>
                </tr>
              </thead>
              <tbody id="tbody"></tbody>
            </table>
          </div>

        </div>
      </div>
    </div>

  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    /** =========================
     *  DATA (ejemplo)
     *  ========================= */
    const raw = [
      {"Operacion":"PPP_115375","FechaAsignada":"30/1/2026 13:33","FechaUltimaAccion":"30/1/2026 13:33","Gestor":"David Alejandro Rodriguez Bolaños","Estado":"Esperando respuesta","WhatsApp":0,"SMS":0,"Correo":0,"Llamada":0,"Progreso":"0%","SinAtencion":"29 min","PromesadoCRC":0,"PromesadoUSD":0,"RecuperadoCRC":0,"RecuperadoUSD":0,"AccionRequerida":"Llamar"},
      {"Operacion":"PTC_110731000","FechaAsignada":"14/1/2026 16:36","FechaUltimaAccion":"30/1/2026 13:18","Gestor":"David Alejandro Rodriguez Bolaños","Estado":"Esperando respuesta","WhatsApp":1,"SMS":0,"Correo":0,"Llamada":0,"Progreso":"25%","SinAtencion":"44 min","PromesadoCRC":0,"PromesadoUSD":0,"RecuperadoCRC":0,"RecuperadoUSD":0,"AccionRequerida":"Llamar"},
      {"Operacion":"PTC_110703616","FechaAsignada":"13/1/2026 12:46","FechaUltimaAccion":"30/1/2026 13:11","Gestor":"David Alejandro Rodriguez Bolaños","Estado":"Esperando respuesta","WhatsApp":1,"SMS":0,"Correo":1,"Llamada":0,"Progreso":"50%","SinAtencion":"51 min","PromesadoCRC":0,"PromesadoUSD":0,"RecuperadoCRC":0,"RecuperadoUSD":0,"AccionRequerida":"Llamar"},
      {"Operacion":"PTC_111006757","FechaAsignada":"9/12/2025 08:26","FechaUltimaAccion":"30/1/2026 11:46","Gestor":"David Alejandro Rodriguez Bolaños","Estado":"Incumplida (prioridad)","WhatsApp":5,"SMS":0,"Correo":3,"Llamada":1,"Progreso":"75%","SinAtencion":"136 min","PromesadoCRC":0,"PromesadoUSD":0,"RecuperadoCRC":0,"RecuperadoUSD":0,"AccionRequerida":"Enviar SMS"},
      {"Operacion":"PTC_110698133","FechaAsignada":"9/12/2025 08:26","FechaUltimaAccion":"30/1/2026 11:46","Gestor":"David Alejandro Rodriguez Bolaños","Estado":"Incumplida (prioridad)","WhatsApp":3,"SMS":0,"Correo":3,"Llamada":2,"Progreso":"75%","SinAtencion":"136 min","PromesadoCRC":0,"PromesadoUSD":0,"RecuperadoCRC":0,"RecuperadoUSD":0,"AccionRequerida":"Enviar SMS"},
      {"Operacion":"PTC_111220300","FechaAsignada":"24/1/2026 12:55","FechaUltimaAccion":"30/1/2026 10:11","Gestor":"David Alejandro Rodriguez Bolaños","Estado":"Incumplida (prioridad)","WhatsApp":0,"SMS":0,"Correo":0,"Llamada":0,"Progreso":"0%","SinAtencion":"231 min","PromesadoCRC":0,"PromesadoUSD":0,"RecuperadoCRC":0,"RecuperadoUSD":0,"AccionRequerida":"Llamar"}
    ];

    /** =========================
     *  Helpers
     *  ========================= */
    const $ = (id)=>document.getElementById(id);

    function parseCRDateTime(s){
      const [dpart, tpart] = s.split(" ");
      const [dd, mm, yyyy] = dpart.split("/").map(n=>parseInt(n,10));
      const [HH, Min] = tpart.split(":").map(n=>parseInt(n,10));
      return new Date(yyyy, mm-1, dd, HH, Min, 0);
    }
    function toISODate(d){
      const z = n => String(n).padStart(2,"0");
      return `${d.getFullYear()}-${z(d.getMonth()+1)}-${z(d.getDate())}`;
    }
    function pctToNum(s){ return Number(String(s).replace("%","")) || 0; }
    function minsFromSinAt(s){ return Number(String(s).replace(" min","")) || 0; }

    function canalPred(r){
      const w=r.WhatsApp||0, s=r.SMS||0, c=r.Correo||0, l=r.Llamada||0;
      const sum = w+s+c+l;
      if(sum===0) return "Ninguno";
      const max = Math.max(w,s,c,l);
      const winners = [];
      if(w===max) winners.push("WhatsApp");
      if(s===max) winners.push("SMS");
      if(c===max) winners.push("Correo");
      if(l===max) winners.push("Llamada");
      return winners.length>1 ? "Mixto" : winners[0];
    }

    function noContactado(r){ return (r.WhatsApp+r.SMS+r.Correo+r.Llamada)===0; }
    function checklistCompleto(r){ return (pctToNum(r.Progreso)>0) || !noContactado(r); }
    function prioridad(r){
      if(String(r.Estado).toLowerCase().includes("incumplida")) return 1;
      if(noContactado(r)) return 2;
      return 3;
    }

    function unique(arr, keyFn){ return Array.from(new Set(arr.map(keyFn))).sort(); }

    function canalIcon(c){
      if(c==="WhatsApp") return `<span class="ch-icon wa" title="WhatsApp"><i class="bi bi-whatsapp"></i></span>`;
      if(c==="SMS") return `<span class="ch-icon sms" title="SMS"><i class="bi bi-chat-dots"></i></span>`;
      if(c==="Correo") return `<span class="ch-icon mail" title="Correo"><i class="bi bi-envelope"></i></span>`;
      if(c==="Llamada") return `<span class="ch-icon call" title="Llamada"><i class="bi bi-telephone"></i></span>`;
      if(c==="Mixto") return `<span class="ch-icon" title="Mixto"><i class="bi bi-shuffle"></i></span>`;
      return `<span class="ch-icon" title="Ninguno"><i class="bi bi-slash-circle"></i></span>`;
    }

    /** =========================
     *  State
     *  ========================= */
    const state = { date:"", gestor:"", estado:"", accion:"", canal:"", reviewMode:"" };

    function baseRows(){
      return raw.map(r=>({
        ...r,
        _dt: parseCRDateTime(r.FechaUltimaAccion),
        _date: toISODate(parseCRDateTime(r.FechaUltimaAccion)),
        _pct: pctToNum(r.Progreso),
        _mins: minsFromSinAt(r.SinAtencion),
        _canal: canalPred(r),
        _no: noContactado(r),
        _chk: checklistCompleto(r),
        _prio: prioridad(r)
      }));
    }

    function filtered(){
      return baseRows().filter(r=>{
        if(state.date && r._date !== state.date) return false;
        if(state.gestor && r.Gestor !== state.gestor) return false;
        if(state.estado && r.Estado !== state.estado) return false;
        if(state.accion && r.AccionRequerida !== state.accion) return false;
        if(state.canal && r._canal !== state.canal) return false;
        return true;
      });
    }

    function applyReview(rows){
      const mode = state.reviewMode;
      if(!mode) return rows;

      if(mode==="NO_CONTACTADOS") return rows.filter(r=>r._no);
      if(mode==="CHECKLIST_INCOMPLETO") return rows.filter(r=>!r._chk);
      if(mode==="SIN_ATENCION_ALTA") return rows.filter(r=>r._mins >= 180);
      if(mode==="ACCION_REQUERIDA") return rows.filter(r=>String(r.AccionRequerida||"").trim()!=="");
      if(mode==="INCUMPLIDAS") return rows.filter(r=>String(r.Estado).toLowerCase().includes("incumplida"));

      // PROMESAS_FALTANTES (proxy hasta tener promesas reales)
      if(mode==="PROMESAS_FALTANTES"){
        return rows.filter(r => (r._pct>=50) && (!r._no) && (r.PromesadoCRC===0 && r.PromesadoUSD===0));
      }
      return rows;
    }

    function setReviewMode(mode){
      state.reviewMode = mode || "";

      // highlight KPI
      document.querySelectorAll(".kpi-card").forEach(x=>x.classList.remove("active"));
      if(state.reviewMode){
        const el = document.querySelector(`.kpi-card[data-review="${state.reviewMode}"]`);
        if(el) el.classList.add("active");
      }

      const reviewEl = $("reviewMode");
      if(!state.reviewMode){
        reviewEl.className = "alert alert-primary py-2 px-3 small mb-3";
        reviewEl.textContent = "Ninguno. Mostrando todo lo filtrado.";
      }else{
        reviewEl.className = "alert alert-warning py-2 px-3 small mb-3";
        reviewEl.innerHTML = `<b>Revisión activa:</b> ${state.reviewMode} · Mostrando SOLO el problema.`;
      }

      render();
      $("tblCard").scrollIntoView({behavior:"smooth", block:"start"});
    }

    function renderKPIs(rows){
      // Promesas HOY (placeholder): Promesado > 0
      const promesas = rows.filter(r => (r.PromesadoCRC>0 || r.PromesadoUSD>0)).length;
      const meta = 65;
      const faltan = Math.max(0, meta - promesas);
      const pct = Math.min(100, Math.round((promesas/meta)*100));

      $("kPromesas").textContent = promesas;
      $("kFaltan").textContent = faltan;

      const b = $("bPromesas");
      const p = $("pPromesas");
      p.style.width = pct + "%";

      if(faltan===0){
        b.className = "badge text-bg-success";
        b.textContent = "CUMPLIDA";
        p.className = "progress-bar bg-success";
      }else if(pct>=60){
        b.className = "badge text-bg-warning text-dark";
        b.textContent = `FALTAN ${faltan}`;
        p.className = "progress-bar bg-warning";
      }else{
        b.className = "badge text-bg-danger";
        b.textContent = `FALTAN ${faltan}`;
        p.className = "progress-bar bg-danger";
      }

      $("kNoContact").textContent = rows.filter(r=>r._no).length;
      $("kChecklist").textContent = rows.filter(r=>!r._chk).length;

      const avgSinAt = rows.length ? Math.round(rows.reduce((a,r)=>a+r._mins,0)/rows.length) : 0;
      $("kSinAt").textContent = avgSinAt;

      const bSin = $("bSinAt");
      if(avgSinAt>=180){ bSin.className="badge text-bg-danger"; bSin.textContent="ALTO"; }
      else if(avgSinAt>=90){ bSin.className="badge text-bg-warning text-dark"; bSin.textContent="MEDIO"; }
      else { bSin.className="badge text-bg-success"; bSin.textContent="OK"; }

      $("kAccPend").textContent = rows.filter(r=>String(r.AccionRequerida||"").trim()!=="").length;
      $("kIncumplidas").textContent = rows.filter(r=>String(r.Estado).toLowerCase().includes("incumplida")).length;
    }

    function renderTable(rows){
      const sorted = [...rows].sort((a,b)=>{
        if(a._prio !== b._prio) return a._prio - b._prio;
        if(a._mins !== b._mins) return b._mins - a._mins;
        return a._pct - b._pct;
      });

      const prioBadge = (p)=> p===1
        ? `<span class="badge text-bg-danger">ALTA</span>`
        : p===2
          ? `<span class="badge text-bg-warning text-dark">MEDIA</span>`
          : `<span class="badge text-bg-success">NORMAL</span>`;

      const ynBadge = (v)=> v
        ? `<span class="badge text-bg-danger">Sí</span>`
        : `<span class="badge text-bg-success">No</span>`;

      const chkBadge = (ok)=> ok
        ? `<span class="badge text-bg-success">OK</span>`
        : `<span class="badge text-bg-warning text-dark">INCOMPLETO</span>`;

      $("tbody").innerHTML = sorted.map(r=>`
        <tr>
          <td>${prioBadge(r._prio)}</td>
          <td class="mono">${r.Operacion}</td>
          <td>${r.Estado}</td>
          <td><span class="badge badge-soft text-secondary">${r.AccionRequerida}</span></td>
          <td class="text-end mono">${r._mins}</td>
          <td class="text-end mono">${r._pct}%</td>

          <td>
            <span class="d-inline-flex align-items-center gap-2">
              ${canalIcon(r._canal)}
              <span class="badge badge-soft text-secondary">${r._canal}</span>
            </span>
          </td>

          <td>${ynBadge(r._no)}</td>
          <td>${chkBadge(r._chk)}</td>

          <td class="text-end">
            <span class="d-inline-flex align-items-center gap-2 justify-content-end">
              <span class="ch-icon wa"><i class="bi bi-whatsapp"></i></span>
              <span class="num-pill mono">${r.WhatsApp}</span>
            </span>
          </td>

          <td class="text-end">
            <span class="d-inline-flex align-items-center gap-2 justify-content-end">
              <span class="ch-icon sms"><i class="bi bi-chat-dots"></i></span>
              <span class="num-pill mono">${r.SMS}</span>
            </span>
          </td>

          <td class="text-end">
            <span class="d-inline-flex align-items-center gap-2 justify-content-end">
              <span class="ch-icon mail"><i class="bi bi-envelope"></i></span>
              <span class="num-pill mono">${r.Correo}</span>
            </span>
          </td>

          <td class="text-end">
            <span class="d-inline-flex align-items-center gap-2 justify-content-end">
              <span class="ch-icon call"><i class="bi bi-telephone"></i></span>
              <span class="num-pill mono">${r.Llamada}</span>
            </span>
          </td>
        </tr>
      `).join("");
    }

    function render(){
      const rows = filtered();
      renderKPIs(rows);
      renderTable(applyReview(rows));
    }

    function fillFilters(){
      $("fGestor").innerHTML = `<option value="">Todos</option>` + unique(raw, r=>r.Gestor).map(g=>`<option>${g}</option>`).join("");
      $("fEstado").innerHTML = `<option value="">Todos</option>` + unique(raw, r=>r.Estado).map(e=>`<option>${e}</option>`).join("");
      $("fAccion").innerHTML = `<option value="">Todas</option>` + unique(raw, r=>r.AccionRequerida).map(a=>`<option>${a}</option>`).join("");

      const dates = unique(raw, r=>toISODate(parseCRDateTime(r.FechaUltimaAccion)));
      const preferred = dates.includes("2026-01-30") ? "2026-01-30" : (dates[dates.length-1] || toISODate(new Date()));
      $("fDate").value = preferred;
    }

    function applyFromUI(){
      state.date = $("fDate").value;
      state.gestor = $("fGestor").value;
      state.estado = $("fEstado").value;
      state.accion = $("fAccion").value;
      state.canal = $("fCanal").value;
      render();
    }

    function resetUI(){
      setReviewMode("");
      $("fGestor").value = "";
      $("fEstado").value = "";
      $("fAccion").value = "";
      $("fCanal").value = "";
      fillFilters();
      applyFromUI();
    }

    function toggleTheme(){
      const html = document.documentElement;
      const current = html.getAttribute("data-bs-theme") || "dark";
      html.setAttribute("data-bs-theme", current === "dark" ? "light" : "dark");
    }

    (function init(){
      $("now").textContent = new Date().toLocaleString("es-CR", { dateStyle:"full", timeStyle:"short" });

      fillFilters();

      // Default David
      const david = raw.find(r=>String(r.Gestor).includes("David"));
      if(david) $("fGestor").value = david.Gestor;

      $("btnApply").addEventListener("click", applyFromUI);
      $("btnReset").addEventListener("click", resetUI);
      $("btnClearReview").addEventListener("click", ()=>setReviewMode(""));

      // KPI click drilldown
      document.querySelectorAll(".kpi-card").forEach(el=>{
        el.addEventListener("click", ()=>{
          const mode = el.getAttribute("data-review");
          setReviewMode(mode);
        });
      });

      // Theme toggle
      $("btnTheme").addEventListener("click", toggleTheme);

      applyFromUI();
    })();
  </script>
</body>
</html>
