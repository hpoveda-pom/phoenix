<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Phoenix — Supervisión Gestor (Maqueta)</title>
  <style>
    :root{
      --bg:#0b1020; --card:#121a33; --card2:#0f1630; --text:#e8ecff; --muted:#aab3d6;
      --ok:#42d392; --warn:#ffcc66; --bad:#ff6b6b; --line:rgba(255,255,255,.09);
      --chip:#1b2550; --accent:#7aa2ff;
      --radius:14px;
      --mono: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
      --sans: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, "Apple Color Emoji","Segoe UI Emoji";
    }
    *{box-sizing:border-box}
    body{
      margin:0; font-family:var(--sans); background:radial-gradient(900px 480px at 20% 0%, rgba(122,162,255,.18), transparent 60%),
      radial-gradient(900px 480px at 80% 0%, rgba(66,211,146,.12), transparent 60%), var(--bg);
      color:var(--text);
    }
    header{
      padding:22px 22px 10px;
      display:flex; align-items:flex-end; justify-content:space-between; gap:16px; flex-wrap:wrap;
    }
    h1{margin:0; font-size:18px; letter-spacing:.2px}
    .subtitle{color:var(--muted); font-size:12px; margin-top:6px}
    .pill{
      display:inline-flex; align-items:center; gap:8px;
      padding:8px 10px; border:1px solid var(--line); border-radius:999px; background:rgba(255,255,255,.03);
      font-size:12px; color:var(--muted);
    }
    .container{padding:12px 22px 26px; max-width:1320px; margin:0 auto;}
    .grid{display:grid; gap:14px;}
    .filters{grid-template-columns: repeat(12, 1fr);}
    .kpis{grid-template-columns: repeat(12, 1fr);}
    .charts{grid-template-columns: repeat(12, 1fr);}
    .tablegrid{grid-template-columns: repeat(12, 1fr);}
    .card{
      background:linear-gradient(180deg, rgba(255,255,255,.04), rgba(255,255,255,.02));
      border:1px solid var(--line);
      border-radius:var(--radius);
      padding:14px;
      box-shadow: 0 10px 30px rgba(0,0,0,.25);
    }
    .card h2{margin:0 0 10px; font-size:13px; color:var(--muted); font-weight:600}
    .control{
      display:flex; flex-direction:column; gap:6px;
    }
    label{font-size:11px; color:var(--muted)}
    select, input{
      width:100%;
      background:rgba(255,255,255,.04);
      border:1px solid var(--line);
      border-radius:12px;
      padding:10px 10px;
      color:var(--text);
      outline:none;
    }
    input::placeholder{color:rgba(232,236,255,.35)}
    .btnrow{display:flex; gap:10px; align-items:center; justify-content:flex-end; flex-wrap:wrap;}
    button{
      border:1px solid var(--line);
      background:rgba(255,255,255,.04);
      color:var(--text);
      border-radius:12px;
      padding:10px 12px;
      cursor:pointer;
      font-weight:600;
    }
    button.primary{
      background:linear-gradient(180deg, rgba(122,162,255,.35), rgba(122,162,255,.12));
      border-color:rgba(122,162,255,.45);
    }
    button:hover{filter:brightness(1.08)}
    .kpi{
      display:flex; flex-direction:column; gap:10px;
      padding:14px;
      background:linear-gradient(180deg, rgba(255,255,255,.05), rgba(255,255,255,.02));
      border:1px solid var(--line);
      border-radius:var(--radius);
    }
    .kpi .top{display:flex; align-items:flex-start; justify-content:space-between; gap:12px;}
    .kpi .label{font-size:12px; color:var(--muted); font-weight:600}
    .kpi .value{font-size:22px; font-weight:800; letter-spacing:.2px}
    .kpi .hint{font-size:12px; color:var(--muted)}
    .badge{
      font-size:11px; padding:6px 8px; border-radius:999px; border:1px solid var(--line);
      background:rgba(255,255,255,.03); color:var(--muted);
      white-space:nowrap;
    }
    .badge.ok{border-color:rgba(66,211,146,.35); background:rgba(66,211,146,.10); color:var(--ok)}
    .badge.warn{border-color:rgba(255,204,102,.35); background:rgba(255,204,102,.10); color:var(--warn)}
    .badge.bad{border-color:rgba(255,107,107,.35); background:rgba(255,107,107,.10); color:var(--bad)}
    .progress{
      height:10px; border-radius:999px; border:1px solid var(--line); background:rgba(0,0,0,.18);
      overflow:hidden;
    }
    .progress > div{
      height:100%; width:0%;
      background:linear-gradient(90deg, rgba(122,162,255,.85), rgba(66,211,146,.75));
    }
    .spark{
      height:160px; border-radius:12px; border:1px dashed rgba(255,255,255,.14);
      display:flex; align-items:center; justify-content:center;
      color:rgba(232,236,255,.35); font-family:var(--mono); font-size:12px;
      background:rgba(0,0,0,.08);
    }
    table{
      width:100%; border-collapse:collapse; font-size:12px;
    }
    thead th{
      text-align:left; color:var(--muted); font-weight:700; font-size:11px;
      padding:10px 10px; border-bottom:1px solid var(--line);
    }
    tbody td{
      padding:10px 10px; border-bottom:1px solid rgba(255,255,255,.06);
      vertical-align:middle;
    }
    tbody tr:hover{background:rgba(255,255,255,.03)}
    .mono{font-family:var(--mono)}
    .chip{
      display:inline-flex; gap:8px; align-items:center;
      padding:6px 8px; border-radius:999px; background:rgba(255,255,255,.04);
      border:1px solid var(--line); color:var(--muted);
      font-size:11px;
    }
    .right{text-align:right}
    .muted{color:var(--muted)}
    .small{font-size:11px}
    .col-2{grid-column:span 2}
    .col-3{grid-column:span 3}
    .col-4{grid-column:span 4}
    .col-5{grid-column:span 5}
    .col-6{grid-column:span 6}
    .col-7{grid-column:span 7}
    .col-8{grid-column:span 8}
    .col-9{grid-column:span 9}
    .col-12{grid-column:span 12}
    @media (max-width:1100px){
      .col-2,.col-3,.col-4,.col-5,.col-6,.col-7,.col-8,.col-9{grid-column:span 12}
      header{align-items:flex-start}
    }
  </style>
</head>
<body>
  <header>
    <div>
      <h1>Phoenix — Dashboard Supervisión del Gestor</h1>
      <div class="subtitle">Meta operativa: <b>65 promesas/día</b> · Vista: Autosupervisión + Supervisión por gestor</div>
    </div>
    <div class="pill">
      <span>Fecha local:</span>
      <b id="now"></b>
    </div>
  </header>

  <div class="container">
    <!-- FILTROS -->
    <div class="grid filters">
      <div class="card col-3">
        <h2>Filtro · Fecha</h2>
        <div class="control">
          <label>Fecha (YYYY-MM-DD)</label>
          <input id="fDate" type="date" />
        </div>
      </div>

      <div class="card col-3">
        <h2>Filtro · Gestor</h2>
        <div class="control">
          <label>Gestor</label>
          <select id="fGestor"></select>
        </div>
      </div>

      <div class="card col-3">
        <h2>Filtro · Estado</h2>
        <div class="control">
          <label>Estado</label>
          <select id="fEstado"></select>
        </div>
      </div>

      <div class="card col-3">
        <h2>Filtro · Acción</h2>
        <div class="control">
          <label>Acción requerida</label>
          <select id="fAccion"></select>
        </div>
      </div>

      <div class="card col-8">
        <h2>Filtro · Canal predominante</h2>
        <div class="control">
          <label>Canal predominante</label>
          <select id="fCanal">
            <option value="">Todos</option>
            <option value="WhatsApp">WhatsApp</option>
            <option value="SMS">SMS</option>
            <option value="Correo">Correo</option>
            <option value="Llamada">Llamada</option>
            <option value="Mixto">Mixto</option>
            <option value="Ninguno">Ninguno</option>
          </select>
        </div>
      </div>

      <div class="card col-4">
        <h2>Acciones</h2>
        <div class="btnrow" style="margin-top:2px;">
          <button id="btnReset">Reset</button>
          <button class="primary" id="btnApply">Aplicar</button>
        </div>
        <div class="muted small" style="margin-top:10px;">
          Nota: “Checklist incompleto” y “No contactado” se calculan desde WhatsApp/SMS/Correo/Llamada + Progreso.
        </div>
      </div>
    </div>

    <!-- KPIs -->
    <div class="grid kpis" style="margin-top:14px;">
      <div class="kpi col-3">
        <div class="top">
          <div>
            <div class="label">Promesas hoy</div>
            <div class="value" id="kPromesas">—</div>
          </div>
          <span class="badge" id="bPromesas">—</span>
        </div>
        <div class="hint">Meta diaria: <b>65</b></div>
        <div class="progress"><div id="pPromesas"></div></div>
      </div>

      <div class="kpi col-3">
        <div class="top">
          <div>
            <div class="label">No contactados</div>
            <div class="value" id="kNoContact">—</div>
          </div>
          <span class="badge bad" id="bNoContact">riesgo</span>
        </div>
        <div class="hint">WhatsApp/SMS/Correo/Llamada = 0</div>
      </div>

      <div class="kpi col-3">
        <div class="top">
          <div>
            <div class="label">Checklist incompleto</div>
            <div class="value" id="kChecklist">—</div>
          </div>
          <span class="badge warn" id="bChecklist">bloquea avance</span>
        </div>
        <div class="hint">Progreso=0 y sin contactos</div>
      </div>

      <div class="kpi col-3">
        <div class="top">
          <div>
            <div class="label">Tiempo prom. sin atención</div>
            <div class="value" id="kSinAt">—</div>
          </div>
          <span class="badge" id="bSinAt">min</span>
        </div>
        <div class="hint">Promedio de “SinAtencion” (min)</div>
      </div>

      <div class="kpi col-3">
        <div class="top">
          <div>
            <div class="label">Promesado (CRC)</div>
            <div class="value" id="kPromCRC">—</div>
          </div>
          <span class="badge" id="bPromCRC">monto</span>
        </div>
        <div class="hint">Suma PromesadoCRC</div>
      </div>

      <div class="kpi col-3">
        <div class="top">
          <div>
            <div class="label">Promesado (USD)</div>
            <div class="value" id="kPromUSD">—</div>
          </div>
          <span class="badge" id="bPromUSD">monto</span>
        </div>
        <div class="hint">Suma PromesadoUSD</div>
      </div>

      <div class="kpi col-3">
        <div class="top">
          <div>
            <div class="label">Recuperado (CRC)</div>
            <div class="value" id="kRecCRC">—</div>
          </div>
          <span class="badge ok" id="bRecCRC">cobrado</span>
        </div>
        <div class="hint">Suma RecuperadoCRC</div>
      </div>

      <div class="kpi col-3">
        <div class="top">
          <div>
            <div class="label">Recuperado (USD)</div>
            <div class="value" id="kRecUSD">—</div>
          </div>
          <span class="badge ok" id="bRecUSD">cobrado</span>
        </div>
        <div class="hint">Suma RecuperadoUSD</div>
      </div>
    </div>

    <!-- CHARTS (placeholders) -->
    <div class="grid charts" style="margin-top:14px;">
      <div class="card col-4">
        <h2>Distribución por Estado</h2>
        <div class="spark" id="cEstados">[placeholder]</div>
      </div>
      <div class="card col-4">
        <h2>Contactos por Canal</h2>
        <div class="spark" id="cCanales">[placeholder]</div>
      </div>
      <div class="card col-4">
        <h2>Backlog por Acción Requerida</h2>
        <div class="spark" id="cAcciones">[placeholder]</div>
      </div>
    </div>

    <!-- TABLA OPERATIVA -->
    <div class="grid tablegrid" style="margin-top:14px;">
      <div class="card col-12">
        <h2>Casos prioritarios (operativo)</h2>
        <div class="muted small" style="margin-bottom:10px;">
          Orden: <b>Incumplida (prioridad)</b> primero, luego mayor <b>SinAtención</b>, luego menor <b>Progreso</b>.
        </div>
        <div style="overflow:auto;">
          <table>
            <thead>
              <tr>
                <th>Prioridad</th>
                <th>Operación</th>
                <th>Estado</th>
                <th>Acción</th>
                <th class="right">SinAt (min)</th>
                <th class="right">Progreso</th>
                <th>Canal</th>
                <th>No contactado</th>
                <th>Checklist</th>
                <th class="right">W</th>
                <th class="right">S</th>
                <th class="right">C</th>
                <th class="right">L</th>
              </tr>
            </thead>
            <tbody id="tbody"></tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

<script>
/** ==========
 *  DATA (pegada desde tu ejemplo)
 *  ========== */
const raw = [
  {"Operacion":"PPP_115375","FechaAsignada":"30/1/2026 13:33","FechaUltimaAccion":"30/1/2026 13:33","Gestor":"David Alejandro Rodriguez Bolaños","Estado":"Esperando respuesta","WhatsApp":0,"SMS":0,"Correo":0,"Llamada":0,"Progreso":"0%","SinAtencion":"29 min","PromesadoCRC":0,"PromesadoUSD":0,"RecuperadoCRC":0,"RecuperadoUSD":0,"AccionRequerida":"Llamar"},
  {"Operacion":"PTC_110731000","FechaAsignada":"14/1/2026 16:36","FechaUltimaAccion":"30/1/2026 13:18","Gestor":"David Alejandro Rodriguez Bolaños","Estado":"Esperando respuesta","WhatsApp":1,"SMS":0,"Correo":0,"Llamada":0,"Progreso":"25%","SinAtencion":"44 min","PromesadoCRC":0,"PromesadoUSD":0,"RecuperadoCRC":0,"RecuperadoUSD":0,"AccionRequerida":"Llamar"},
  {"Operacion":"PTC_110703616","FechaAsignada":"13/1/2026 12:46","FechaUltimaAccion":"30/1/2026 13:11","Gestor":"David Alejandro Rodriguez Bolaños","Estado":"Esperando respuesta","WhatsApp":1,"SMS":0,"Correo":1,"Llamada":0,"Progreso":"50%","SinAtencion":"51 min","PromesadoCRC":0,"PromesadoUSD":0,"RecuperadoCRC":0,"RecuperadoUSD":0,"AccionRequerida":"Llamar"},
  {"Operacion":"PTC_111006757","FechaAsignada":"9/12/2025 08:26","FechaUltimaAccion":"30/1/2026 11:46","Gestor":"David Alejandro Rodriguez Bolaños","Estado":"Incumplida (prioridad)","WhatsApp":5,"SMS":0,"Correo":3,"Llamada":1,"Progreso":"75%","SinAtencion":"136 min","PromesadoCRC":0,"PromesadoUSD":0,"RecuperadoCRC":0,"RecuperadoUSD":0,"AccionRequerida":"Enviar SMS"},
  {"Operacion":"PTC_110698133","FechaAsignada":"9/12/2025 08:26","FechaUltimaAccion":"30/1/2026 11:46","Gestor":"David Alejandro Rodriguez Bolaños","Estado":"Incumplida (prioridad)","WhatsApp":3,"SMS":0,"Correo":3,"Llamada":2,"Progreso":"75%","SinAtencion":"136 min","PromesadoCRC":0,"PromesadoUSD":0,"RecuperadoCRC":0,"RecuperadoUSD":0,"AccionRequerida":"Enviar SMS"},
  {"Operacion":"PTC_111095295","FechaAsignada":"13/1/2026 08:06","FechaUltimaAccion":"30/1/2026 11:41","Gestor":"David Alejandro Rodriguez Bolaños","Estado":"Incumplida (prioridad)","WhatsApp":5,"SMS":0,"Correo":1,"Llamada":2,"Progreso":"75%","SinAtencion":"141 min","PromesadoCRC":0,"PromesadoUSD":0,"RecuperadoCRC":0,"RecuperadoUSD":0,"AccionRequerida":"Enviar SMS"},
  {"Operacion":"PTC_1524054343","FechaAsignada":"30/1/2026 11:33","FechaUltimaAccion":"30/1/2026 11:37","Gestor":"David Alejandro Rodriguez Bolaños","Estado":"Incumplida (prioridad)","WhatsApp":1,"SMS":0,"Correo":1,"Llamada":0,"Progreso":"50%","SinAtencion":"145 min","PromesadoCRC":0,"PromesadoUSD":0,"RecuperadoCRC":0,"RecuperadoUSD":0,"AccionRequerida":"Llamar"},
  {"Operacion":"PTC_111045617","FechaAsignada":"29/1/2026 15:53","FechaUltimaAccion":"30/1/2026 10:49","Gestor":"David Alejandro Rodriguez Bolaños","Estado":"Incumplida (prioridad)","WhatsApp":1,"SMS":0,"Correo":1,"Llamada":1,"Progreso":"75%","SinAtencion":"193 min","PromesadoCRC":0,"PromesadoUSD":0,"RecuperadoCRC":0,"RecuperadoUSD":0,"AccionRequerida":"Enviar SMS"},
  {"Operacion":"PTC_110983800","FechaAsignada":"9/1/2026 10:08","FechaUltimaAccion":"30/1/2026 10:42","Gestor":"David Alejandro Rodriguez Bolaños","Estado":"Incumplida (prioridad)","WhatsApp":0,"SMS":0,"Correo":3,"Llamada":0,"Progreso":"25%","SinAtencion":"200 min","PromesadoCRC":0,"PromesadoUSD":0,"RecuperadoCRC":0,"RecuperadoUSD":0,"AccionRequerida":"Llamar"},
  {"Operacion":"PTC_111218596","FechaAsignada":"22/1/2026 16:16","FechaUltimaAccion":"30/1/2026 10:25","Gestor":"David Alejandro Rodriguez Bolaños","Estado":"Incumplida (prioridad)","WhatsApp":1,"SMS":0,"Correo":0,"Llamada":1,"Progreso":"50%","SinAtencion":"217 min","PromesadoCRC":0,"PromesadoUSD":0,"RecuperadoCRC":0,"RecuperadoUSD":0,"AccionRequerida":"Enviar SMS"},
  {"Operacion":"PTC_111227692","FechaAsignada":"19/1/2026 14:03","FechaUltimaAccion":"30/1/2026 10:21","Gestor":"David Alejandro Rodriguez Bolaños","Estado":"Incumplida (prioridad)","WhatsApp":3,"SMS":0,"Correo":0,"Llamada":0,"Progreso":"25%","SinAtencion":"221 min","PromesadoCRC":0,"PromesadoUSD":0,"RecuperadoCRC":0,"RecuperadoUSD":0,"AccionRequerida":"Llamar"},
  {"Operacion":"PTC_111220300","FechaAsignada":"24/1/2026 12:55","FechaUltimaAccion":"30/1/2026 10:11","Gestor":"David Alejandro Rodriguez Bolaños","Estado":"Incumplida (prioridad)","WhatsApp":0,"SMS":0,"Correo":0,"Llamada":0,"Progreso":"0%","SinAtencion":"231 min","PromesadoCRC":0,"PromesadoUSD":0,"RecuperadoCRC":0,"RecuperadoUSD":0,"AccionRequerida":"Llamar"},
  {"Operacion":"PPP_115807","FechaAsignada":"26/1/2026 10:41","FechaUltimaAccion":"30/1/2026 10:04","Gestor":"David Alejandro Rodriguez Bolaños","Estado":"Incumplida (prioridad)","WhatsApp":2,"SMS":0,"Correo":2,"Llamada":0,"Progreso":"50%","SinAtencion":"238 min","PromesadoCRC":0,"PromesadoUSD":0,"RecuperadoCRC":0,"RecuperadoUSD":0,"AccionRequerida":"Llamar"}
];

/** ==========
 *  Helpers
 *  ========== */
function parseCRDateTime(s){
  // "30/1/2026 13:33"
  const [dpart, tpart] = s.split(" ");
  const [dd, mm, yyyy] = dpart.split("/").map(n=>parseInt(n,10));
  const [HH, Min] = tpart.split(":").map(n=>parseInt(n,10));
  return new Date(yyyy, mm-1, dd, HH, Min, 0);
}
function toISODate(d){ // yyyy-mm-dd
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
function noContactado(r){
  return (r.WhatsApp+r.SMS+r.Correo+r.Llamada)===0;
}
function checklistCompleto(r){
  // versión simple: progreso>0 o al menos un contacto
  return (pctToNum(r.Progreso)>0) || !noContactado(r);
}
function prioridad(r){
  // 1 = más alto
  if(String(r.Estado).toLowerCase().includes("incumplida")) return 1;
  if(noContactado(r)) return 2;
  return 3;
}
function badgeForChecklist(ok){
  return ok ? `<span class="badge ok">OK</span>` : `<span class="badge warn">INCOMPLETO</span>`;
}
function badgeForNoContact(v){
  return v ? `<span class="badge bad">Sí</span>` : `<span class="badge ok">No</span>`;
}

/** ==========
 *  UI state
 *  ========== */
const $ = (id)=>document.getElementById(id);
const state = { date:"", gestor:"", estado:"", accion:"", canal:"" };

function unique(arr, keyFn){
  return Array.from(new Set(arr.map(keyFn))).sort();
}

function fillFilters(){
  // gestores
  const gestores = unique(raw, r=>r.Gestor);
  $("fGestor").innerHTML = `<option value="">Todos</option>` + gestores.map(g=>`<option>${g}</option>`).join("");
  // estados
  const estados = unique(raw, r=>r.Estado);
  $("fEstado").innerHTML = `<option value="">Todos</option>` + estados.map(e=>`<option>${e}</option>`).join("");
  // acciones
  const acciones = unique(raw, r=>r.AccionRequerida);
  $("fAccion").innerHTML = `<option value="">Todas</option>` + acciones.map(a=>`<option>${a}</option>`).join("");

  // fecha default = hoy si existe en data, si no hoy real
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
  $("fGestor").value = "";
  $("fEstado").value = "";
  $("fAccion").value = "";
  $("fCanal").value = "";
  fillFilters();
  applyFromUI();
}

function filtered(){
  return raw
    .map(r=>({
      ...r,
      _dt: parseCRDateTime(r.FechaUltimaAccion),
      _date: toISODate(parseCRDateTime(r.FechaUltimaAccion)),
      _pct: pctToNum(r.Progreso),
      _mins: minsFromSinAt(r.SinAtencion),
      _canal: canalPred(r),
      _no: noContactado(r),
      _chk: checklistCompleto(r),
      _prio: prioridad(r)
    }))
    .filter(r=>{
      if(state.date && r._date !== state.date) return false;
      if(state.gestor && r.Gestor !== state.gestor) return false;
      if(state.estado && r.Estado !== state.estado) return false;
      if(state.accion && r.AccionRequerida !== state.accion) return false;
      if(state.canal && r._canal !== state.canal) return false;
      return true;
    });
}

function renderKPIs(rows){
  // Promesas hoy: placeholder hasta que exista dato de "promesa nueva".
  // Por ahora: asumimos "promesa" como Promesado>0 (si viene en la data real).
  const promesas = rows.filter(r => (r.PromesadoCRC>0 || r.PromesadoUSD>0)).length;
  const meta = 65;
  const pct = Math.min(100, Math.round((promesas/meta)*100));
  $("kPromesas").textContent = promesas;
  $("bPromesas").textContent = `${pct}%`;
  $("pPromesas").style.width = pct + "%";
  $("bPromesas").className = "badge " + (pct>=100 ? "ok" : pct>=60 ? "warn" : "bad");

  const noC = rows.filter(r=>r._no).length;
  $("kNoContact").textContent = noC;

  const chkBad = rows.filter(r=>!r._chk).length;
  $("kChecklist").textContent = chkBad;

  const avgSinAt = rows.length ? Math.round(rows.reduce((a,r)=>a+r._mins,0)/rows.length) : 0;
  $("kSinAt").textContent = avgSinAt;
  $("bSinAt").textContent = "min";

  const sum = (k)=> rows.reduce((a,r)=>a + (Number(r[k])||0), 0);
  $("kPromCRC").textContent = sum("PromesadoCRC").toLocaleString("es-CR");
  $("kPromUSD").textContent = sum("PromesadoUSD").toLocaleString("en-US");
  $("kRecCRC").textContent = sum("RecuperadoCRC").toLocaleString("es-CR");
  $("kRecUSD").textContent = sum("RecuperadoUSD").toLocaleString("en-US");
}

function renderPlaceholders(rows){
  // Estados
  const countBy = (key)=> rows.reduce((acc,r)=>{acc[r[key]]=(acc[r[key]]||0)+1; return acc;}, {});
  const topList = (obj)=> Object.entries(obj).sort((a,b)=>b[1]-a[1]).slice(0,6);
  const estados = topList(countBy("Estado")).map(([k,v])=>`${v} · ${k}`).join("\n");
  $("cEstados").textContent = estados || "[sin datos]";

  // Canales
  const canales = topList(countBy("_canal")).map(([k,v])=>`${v} · ${k}`).join("\n");
  $("cCanales").textContent = canales || "[sin datos]";

  // Acciones
  const acciones = topList(countBy("AccionRequerida")).map(([k,v])=>`${v} · ${k}`).join("\n");
  $("cAcciones").textContent = acciones || "[sin datos]";
}

function renderTable(rows){
  const sorted = [...rows].sort((a,b)=>{
    if(a._prio !== b._prio) return a._prio - b._prio;
    if(a._mins !== b._mins) return b._mins - a._mins;
    return a._pct - b._pct;
  });

  $("tbody").innerHTML = sorted.map(r=>{
    const prioBadge = r._prio===1 ? `<span class="badge bad">ALTA</span>`
                    : r._prio===2 ? `<span class="badge warn">MEDIA</span>`
                    : `<span class="badge ok">NORMAL</span>`;
    const canal = `<span class="chip">${r._canal}</span>`;
    const prog = `<span class="mono">${r._pct}%</span>`;
    return `
      <tr>
        <td>${prioBadge}</td>
        <td class="mono">${r.Operacion}</td>
        <td>${r.Estado}</td>
        <td><span class="chip">${r.AccionRequerida}</span></td>
        <td class="right mono">${r._mins}</td>
        <td class="right">${prog}</td>
        <td>${canal}</td>
        <td>${badgeForNoContact(r._no)}</td>
        <td>${badgeForChecklist(r._chk)}</td>
        <td class="right mono">${r.WhatsApp}</td>
        <td class="right mono">${r.SMS}</td>
        <td class="right mono">${r.Correo}</td>
        <td class="right mono">${r.Llamada}</td>
      </tr>
    `;
  }).join("");
}

function render(){
  const rows = filtered();
  renderKPIs(rows);
  renderPlaceholders(rows);
  renderTable(rows);
}

/** ==========
 *  Init
 *  ========== */
(function init(){
  const now = new Date();
  $("now").textContent = now.toLocaleString("es-CR", { dateStyle:"full", timeStyle:"short" });

  fillFilters();
  $("btnApply").addEventListener("click", applyFromUI);
  $("btnReset").addEventListener("click", resetUI);

  // set default to David si existe
  const david = raw.find(r=>r.Gestor.includes("David"));
  if(david) $("fGestor").value = david.Gestor;

  applyFromUI();
})();
</script>
</body>
</html>
