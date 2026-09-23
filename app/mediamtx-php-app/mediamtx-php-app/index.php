<?php
declare(strict_types=1);
session_start();
require __DIR__ . '/config.php';

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

$fileExists = file_exists(MEDIAMTX_YAML_PATH);
$fileModified = $fileExists ? date('d/m/Y H:i:s', filemtime(MEDIAMTX_YAML_PATH)) : null;
$fileSizeKb   = $fileExists ? round(filesize(MEDIAMTX_YAML_PATH) / 1024, 1) : null;
$writable     = is_writable(dirname(MEDIAMTX_YAML_PATH)) && (!$fileExists || is_writable(MEDIAMTX_YAML_PATH));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>MediaMTX · Painel de Configuração</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=IBM+Plex+Mono:wght@400;500;600&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<style>
:root{
  --bg:#0E1116;
  --panel:#161A21;
  --panel-2:#1C212A;
  --line:#262C37;
  --line-soft:#20252E;
  --ink:#E7EAF0;
  --ink-dim:#9AA3B2;
  --ink-faint:#5C6577;
  --signal:#FFB454;      /* tally-lamp amber */
  --signal-dim:#5B4626;
  --wire:#39D6C4;        /* signal-path teal */
  --wire-dim:#173934;
  --danger:#E5654F;
  --ok:#5FD98A;
  --radius:10px;
  --mono:'IBM Plex Mono', ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
  --sans:'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
  --disp:'Space Grotesk', var(--sans);
  box-sizing:border-box;
}
:root:not([data-theme="light"]){ color-scheme: dark; }
@media (prefers-color-scheme: light){
  :root:not([data-theme="dark"]){
    --bg:#F3F2EE; --panel:#FFFFFF; --panel-2:#F7F6F2; --line:#DEDCD4; --line-soft:#E7E5DD;
    --ink:#191A16; --ink-dim:#5B5D54; --ink-faint:#8B8D82;
    --signal-dim:#FCE7C4; --wire-dim:#D7F5EF;
  }
}
:root[data-theme="dark"]{
  --bg:#0E1116; --panel:#161A21; --panel-2:#1C212A; --line:#262C37; --line-soft:#20252E;
  --ink:#E7EAF0; --ink-dim:#9AA3B2; --ink-faint:#5C6577;
}
*{box-sizing:border-box;}
html,body{height:100%;}
body{
  margin:0; background:var(--bg); color:var(--ink); font-family:var(--sans);
  padding-top:env(safe-area-inset-top,0px); padding-bottom:env(safe-area-inset-bottom,0px);
  -webkit-font-smoothing:antialiased;
}
html{scroll-padding-top:env(safe-area-inset-top,0px);}

/* ---------- top bar ---------- */
.topbar{
  position:sticky; top:0; z-index:40;
  padding-top:calc(14px + env(safe-area-inset-top,0px));
  padding:calc(14px + env(safe-area-inset-top,0px)) 22px 14px;
  display:flex; align-items:center; gap:14px; justify-content:space-between;
  background:linear-gradient(var(--bg) 78%, transparent); border-bottom:1px solid var(--line-soft);
  flex-wrap:wrap;
}
.brand{display:flex; align-items:center; gap:11px; min-width:0;}
.brand .lamp{
  width:11px; height:11px; border-radius:50%; background:var(--signal);
  box-shadow:0 0 0 4px var(--signal-dim), 0 0 14px 1px var(--signal);
  flex:none;
}
.brand h1{
  font-family:var(--disp); font-size:17px; font-weight:600; margin:0; letter-spacing:.2px;
  white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
}
.brand .sub{font-family:var(--mono); font-size:11.5px; color:var(--ink-faint); white-space:nowrap;}
.top-actions{display:flex; gap:8px; flex-wrap:wrap;}

button{font-family:var(--sans); cursor:pointer;}
.btn{
  border:1px solid var(--line); background:var(--panel-2); color:var(--ink);
  padding:9px 14px; border-radius:8px; font-size:13px; font-weight:500;
  display:inline-flex; align-items:center; gap:7px; transition:border-color .15s, background .15s, transform .1s;
}
.btn:hover{border-color:var(--ink-faint);}
.btn:active{transform:translateY(1px);}
.btn.primary{background:var(--wire); border-color:var(--wire); color:#06231F; font-weight:600;}
.btn.primary:hover{filter:brightness(1.07);}
.btn.ghost{background:transparent;}
.btn svg{width:14px; height:14px; flex:none;}

/* ---------- layout ---------- */
.shell{
  display:grid; grid-template-columns:220px minmax(0,1fr) minmax(340px,460px);
  gap:0; align-items:start; min-height:calc(100vh - 64px);
}
@media (max-width:1180px){
  .shell{grid-template-columns:190px minmax(0,1fr);}
  .yamlcol{display:none;}
  .yamlcol.open{
    display:block; position:fixed; inset:0; z-index:60; background:var(--bg);
    padding-top:env(safe-area-inset-top,0px);
  }
}
@media (max-width:800px){
  .shell{grid-template-columns:1fr;}
  .navcol{display:none;}
  .navcol.open{
    display:block; position:fixed; inset:0; z-index:55; background:var(--bg); overflow:auto;
    padding-top:env(safe-area-inset-top,0px);
  }
}

/* ---------- nav ---------- */
.navcol{border-right:1px solid var(--line-soft); padding:18px 10px 40px; position:sticky; top:64px; align-self:start; max-height:calc(100vh - 64px); overflow-y:auto;}
.nav-close{display:none;}
@media (max-width:800px){
  .nav-close{display:flex; margin:0 6px 12px; width:calc(100% - 12px);}
}
.navgroup{margin-bottom:4px;}
.navitem{
  width:100%; text-align:left; background:transparent; border:none; color:var(--ink-dim);
  padding:9px 12px; border-radius:8px; font-size:13px; display:flex; align-items:center; gap:10px;
  font-family:var(--sans); font-weight:500;
}
.navitem .n{font-family:var(--mono); font-size:10.5px; color:var(--ink-faint); width:16px; flex:none;}
.navitem:hover{background:var(--panel-2); color:var(--ink);}
.navitem.active{background:var(--panel-2); color:var(--ink);}
.navitem.active .n{color:var(--wire);}
.navitem .dot{width:6px; height:6px; border-radius:50%; margin-left:auto; flex:none;}
.dot.on{background:var(--ok);}
.dot.off{background:var(--ink-faint); opacity:.5;}

/* ---------- main form column ---------- */
.formcol{padding:26px 26px 120px; min-width:0; max-width:760px;}
.crumb{font-family:var(--mono); font-size:11px; color:var(--ink-faint); margin-bottom:6px;}
.section-head{margin-bottom:22px;}
.section-head h2{font-family:var(--disp); font-size:22px; margin:0 0 6px; font-weight:600;}
.section-head p{margin:0; color:var(--ink-dim); font-size:13.5px; line-height:1.55; max-width:58ch;}

.card{
  background:var(--panel); border:1px solid var(--line-soft); border-radius:var(--radius);
  padding:20px 20px 6px; margin-bottom:16px;
}
.card + .card{margin-top:16px;}
.card h3{
  font-family:var(--sans); font-size:12.5px; font-weight:600; text-transform:none;
  color:var(--ink-dim); margin:0 0 14px; padding-bottom:10px; border-bottom:1px solid var(--line-soft);
  display:flex; align-items:center; justify-content:space-between; gap:10px;
}
.card h3 .tag{font-family:var(--mono); font-size:10.5px; color:var(--ink-faint); font-weight:400;}

.field{margin-bottom:16px;}
.field label{display:block; font-size:13px; font-weight:500; margin-bottom:5px; color:var(--ink);}
.field .help{font-size:12px; color:var(--ink-faint); margin-top:5px; line-height:1.5;}
.row2{display:grid; grid-template-columns:1fr 1fr; gap:14px;}
.row3{display:grid; grid-template-columns:1fr 1fr 1fr; gap:14px;}
@media (max-width:560px){.row2,.row3{grid-template-columns:1fr;}}

input[type=text], input[type=number], input[type=password], select, textarea{
  width:100%; background:var(--panel-2); border:1px solid var(--line); color:var(--ink);
  padding:9px 11px; border-radius:7px; font-size:13.5px; font-family:var(--mono);
  transition:border-color .15s, box-shadow .15s;
}
select{font-family:var(--sans); appearance:none; background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6'%3E%3Cpath d='M0 0l5 6 5-6z' fill='%239AA3B2'/%3E%3C/svg%3E"); background-repeat:no-repeat; background-position:right 12px center; padding-right:30px;}
textarea{font-family:var(--mono); resize:vertical; min-height:72px; line-height:1.5;}
input:focus, select:focus, textarea:focus{outline:none; border-color:var(--wire); box-shadow:0 0 0 3px var(--wire-dim);}
input::placeholder{color:var(--ink-faint);}

.switchrow{display:flex; align-items:center; justify-content:space-between; gap:14px; padding:4px 0;}
.switchrow .stxt label{margin-bottom:3px;}
.switch{position:relative; width:40px; height:23px; flex:none;}
.switch input{position:absolute; opacity:0; width:100%; height:100%; margin:0; cursor:pointer;}
.switch .track{position:absolute; inset:0; background:var(--line); border-radius:99px; transition:background .15s;}
.switch .knob{position:absolute; top:3px; left:3px; width:17px; height:17px; border-radius:50%; background:var(--ink-dim); transition:transform .15s, background .15s;}
.switch input:checked + .track{background:var(--wire-dim); border:1px solid var(--wire);}
.switch input:checked ~ .knob{transform:translateX(17px); background:var(--wire);}

.chiplist{display:flex; flex-wrap:wrap; gap:7px;}
.chip{
  border:1px solid var(--line); background:var(--panel-2); color:var(--ink-dim);
  padding:6px 12px; border-radius:99px; font-size:12.5px; font-family:var(--mono); user-select:none;
}
.chip.on{border-color:var(--wire); color:var(--wire); background:var(--wire-dim);}

.subtle-toggle{
  display:flex; align-items:center; gap:8px; font-size:12.5px; color:var(--ink-faint);
  cursor:pointer; margin:2px 0 14px; width:fit-content;
}
.subtle-toggle svg{width:12px; height:12px; transition:transform .15s;}
.subtle-toggle.open svg{transform:rotate(90deg);}
.collapse{display:none;}
.collapse.open{display:block;}

.arr-item{border:1px solid var(--line-soft); border-radius:9px; padding:14px 14px 4px; margin-bottom:12px; background:var(--panel-2);}
.arr-item-head{display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;}
.arr-item-head .lbl{font-family:var(--mono); font-size:11.5px; color:var(--ink-faint);}
.iconbtn{
  border:1px solid var(--line); background:transparent; color:var(--ink-faint); width:26px; height:26px;
  border-radius:6px; display:inline-flex; align-items:center; justify-content:center; flex:none;
}
.iconbtn:hover{color:var(--danger); border-color:var(--danger);}
.iconbtn svg{width:13px; height:13px;}
.addbtn{
  width:100%; border:1px dashed var(--line); background:transparent; color:var(--ink-dim);
  padding:11px; border-radius:8px; font-size:13px; margin:6px 0 18px; display:flex; align-items:center; justify-content:center; gap:7px;
}
.addbtn:hover{border-color:var(--wire); color:var(--wire);}
.addbtn svg{width:14px; height:14px;}

.notebox{
  border:1px solid var(--line-soft); background:var(--panel-2); border-radius:9px; padding:13px 15px;
  font-size:12.5px; color:var(--ink-dim); line-height:1.6; margin:4px 0 18px; display:flex; gap:10px;
}
.notebox svg{width:15px; height:15px; flex:none; margin-top:1px; color:var(--signal);}

.taglist-input{display:flex; gap:8px;}
.taglist-input input{flex:1;}
.taglist{display:flex; flex-wrap:wrap; gap:6px; margin-top:8px;}
.tagitem{
  display:flex; align-items:center; gap:7px; background:var(--panel-2); border:1px solid var(--line);
  padding:5px 6px 5px 11px; border-radius:99px; font-family:var(--mono); font-size:12px; color:var(--ink);
}
.tagitem button{background:none; border:none; color:var(--ink-faint); width:16px; height:16px; border-radius:50%; display:flex; align-items:center; justify-content:center;}
.tagitem button:hover{color:var(--danger); background:var(--line-soft);}

.foot-nav{display:flex; justify-content:space-between; gap:12px; margin-top:30px; padding-top:20px; border-top:1px solid var(--line-soft);}
.foot-nav .btn{flex:1; justify-content:center;}

/* ---------- yaml column ---------- */
.yamlcol{position:sticky; top:64px; align-self:start; height:calc(100vh - 64px); border-left:1px solid var(--line-soft); display:flex; flex-direction:column;}
.yaml-head{padding:16px 18px 12px; border-bottom:1px solid var(--line-soft); display:flex; align-items:center; justify-content:space-between; gap:10px; flex:none;}
.yaml-head .t{display:flex; align-items:center; gap:9px;}
.yaml-head .t svg{width:15px; height:15px; color:var(--wire);}
.yaml-head h4{margin:0; font-family:var(--disp); font-size:14.5px; font-weight:600;}
.yaml-actions{display:flex; gap:7px;}
.yaml-actions .btn{padding:7px 10px; font-size:12px;}
.yaml-body{flex:1; overflow:auto; padding:16px 18px 40px;}
#yamlOut{
  margin:0; font-family:var(--mono); font-size:12.2px; line-height:1.62; white-space:pre; color:var(--ink-dim);
  tab-size:2;
}
#yamlOut .c{color:var(--ink-faint);}
#yamlOut .h{color:var(--signal); font-weight:600;}
#yamlOut .k{color:var(--wire);}
#yamlOut .v{color:var(--ink);}
.yaml-mobile-toggle{display:none;}
@media (max-width:1180px){
  .yaml-mobile-toggle{display:flex; position:fixed; right:16px; bottom:calc(16px + env(safe-area-inset-bottom,0px)); z-index:50; border-radius:99px; padding:13px 18px; box-shadow:0 8px 24px rgba(0,0,0,.35);}
  .yamlcol .yaml-head{padding-top:calc(16px + env(safe-area-inset-top,0px));}
}
.nav-mobile-toggle{display:none;}
@media (max-width:800px){
  .nav-mobile-toggle{display:inline-flex;}
}
.copied-flash{color:var(--ok) !important; border-color:var(--ok) !important;}

::-webkit-scrollbar{width:10px; height:10px;}
::-webkit-scrollbar-thumb{background:var(--line); border-radius:99px; border:2px solid var(--bg);}
::-webkit-scrollbar-track{background:transparent;}
</style>
</head>
<body>

<div class="topbar">
  <div class="brand">
    <span class="lamp" id="statusLamp"></span>
    <div style="min-width:0;">
      <h1>MediaMTX · Painel de Configuração</h1>
      <div class="sub" id="crumbTop">
        <?php if ($fileExists): ?>
          <?php echo htmlspecialchars(MEDIAMTX_YAML_PATH, ENT_QUOTES); ?> · gravado em <?php echo $fileModified; ?> (<?php echo $fileSizeKb; ?> KB)
        <?php else: ?>
          <?php echo htmlspecialchars(MEDIAMTX_YAML_PATH, ENT_QUOTES); ?> · ainda não salvo neste servidor
        <?php endif; ?>
      </div>
    </div>
  </div>
  <div class="top-actions">
    <button class="btn ghost nav-mobile-toggle" id="navMobileToggle">☰ Seções</button>
    <button class="btn ghost" id="resetBtn">Restaurar padrões</button>
    <?php if ($fileExists): ?>
      <a class="btn ghost" href="download.php">⬇ Baixar do servidor</a>
    <?php endif; ?>
    <button class="btn ghost" id="downloadBtnTop">⬇ Baixar mediamtx.yml</button>
    <button class="btn primary" id="saveServerBtn" <?php echo $writable ? '' : 'disabled title="Pasta sem permissão de escrita para o usuário do Apache"'; ?>>💾 Salvar no servidor</button>
  </div>
</div>
<?php if (!$writable): ?>
<div class="notebox" style="margin:0 22px 0; border-radius:0 0 10px 10px;">
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
  <div>O usuário do Apache não tem permissão de escrita em <?php echo htmlspecialchars(dirname(MEDIAMTX_YAML_PATH), ENT_QUOTES); ?>. Ajuste com <code>chown</code>/<code>chmod</code> para salvar diretamente no servidor — o botão "Baixar" continua funcionando normalmente.</div>
</div>
<?php endif; ?>

<div class="shell">
  <!-- NAV -->
  <nav class="navcol" id="navCol">
    <button class="btn ghost nav-close" id="navCloseBtn">Fechar ✕</button>
    <div class="navgroup" id="navGroup"></div>
  </nav>

  <!-- FORM -->
  <main class="formcol" id="formCol"></main>

  <!-- YAML PREVIEW -->
  <aside class="yamlcol" id="yamlCol">
    <div class="yaml-head">
      <div class="t">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
        <h4>mediamtx.yml — pré-visualização</h4>
      </div>
      <div class="yaml-actions">
        <button class="btn" id="copyBtn">Copiar</button>
        <button class="btn primary" id="downloadBtn">Baixar</button>
        <button class="btn ghost" id="yamlCloseBtn" style="display:none;">✕</button>
      </div>
    </div>
    <div class="yaml-body"><pre id="yamlOut"></pre></div>
  </aside>
</div>

<button class="btn primary yaml-mobile-toggle" id="yamlMobileToggle">
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
  YAML
</button>

<script>
const CSRF = <?php echo json_encode($_SESSION['csrf']); ?>;
const SERVER_YAML_PATH = <?php echo json_encode(MEDIAMTX_YAML_PATH); ?>;

/* =========================================================================
   ESTADO — pré-carregado a partir do mediamtx.yml enviado
   ========================================================================= */
const state = {
  general:{
    logLevel:'info', logDestinations:['stdout'], logStructured:false, logFile:'mediamtx.log',
    sysLogPrefix:'mediamtx', dumpPackets:false, readTimeout:'10s', writeTimeout:'10s',
    writeQueueSize:512, udpMaxPayloadSize:1452, udpReadBufferSize:0,
    runOnConnect:'', runOnConnectRestart:false, runOnDisconnect:''
  },
  auth:{
    authMethod:'internal',
    internalUsers:[
      {user:'user01', pass:'pref01', ips:[], permissions:[{action:'read', path:''}]},
      {user:'user02', pass:'pref02', ips:[], permissions:[{action:'read', path:''}]},
      {user:'any', pass:'', ips:['127.0.0.1','::1'], permissions:[{action:'api', path:''},{action:'metrics', path:''},{action:'pprof', path:''}]}
    ],
    httpAddress:'', httpFingerprint:'', httpExclude:[],
    jwtJWKS:'', jwtFingerprint:'', jwtClaimKey:'mediamtx_permissions', jwtExclude:[], jwtIssuer:'', jwtAudience:''
  },
  api:{enabled:false, address:':9997', encryption:false, key:'server.key', cert:'server.crt', origins:['*'], proxies:[]},
  metrics:{enabled:false, address:':9998', encryption:false, key:'server.key', cert:'server.crt', origins:['*'], proxies:[]},
  pprof:{enabled:false, address:':9999', encryption:false, key:'server.key', cert:'server.crt', origins:['*'], proxies:[]},
  playback:{enabled:false, address:':9996', encryption:false, key:'server.key', cert:'server.crt', origins:['*'], proxies:[]},
  rtsp:{
    enabled:true, transports:['tcp'], encryption:'no', address:':8554', tlsAddress:':8322',
    rtpAddress:':8000', rtcpAddress:':8001', multicastRange:'224.1.0.0/16', multicastRTP:8002, multicastRTCP:8003,
    srtpAddress:':8004', srtcpAddress:':8005', multicastSRTP:8006, multicastSRTCP:8007,
    key:'server.key', cert:'server.crt', authMethods:['basic'], proxies:[]
  },
  rtmp:{enabled:false, encryption:'no', address:':1935', tlsAddress:':1936', key:'server.key', cert:'server.crt', proxies:[]},
  hls:{
    enabled:true, address:':8888', encryption:true,
    key:'C:\\Users\\04238468686\\Desktop\\mediamtx_v1.20.1_windows_amd64\\server.key',
    cert:'C:\\Users\\04238468686\\Desktop\\mediamtx_v1.20.1_windows_amd64\\server.crt',
    origins:['*'], proxies:[], alwaysRemux:false, variant:'lowLatency', segmentCount:7,
    segmentDuration:'1s', partDuration:'200ms', segmentMaxSize:'50M', directory:'', muxerCloseAfter:'60s', cdnSecret:''
  },
  webrtc:{
    enabled:true, address:':8889', encryption:true,
    key:'C:\\Users\\04238468686\\Desktop\\mediamtx_v1.20.1_windows_amd64\\server.key',
    cert:'C:\\Users\\04238468686\\Desktop\\mediamtx_v1.20.1_windows_amd64\\server.crt',
    origins:['*'], proxies:[], localUDP:':8189', localTCP:'', ipsFromInterfaces:true, ipsFromInterfacesList:[],
    additionalHosts:[], iceServers:[], stunTimeout:'5s', handshakeTimeout:'10s', trackTimeout:'2s'
  },
  srt:{enabled:true, address:':8890'},
  moq:{enabled:true, http2:':8892', http3:':8892', quic:':8893', key:'auto.key', cert:'auto.crt', origins:['*'], proxies:[]},
  pathDefaults:{
    source:'publisher', sourceFingerprint:'', sourceOnDemand:false, sourceOnDemandStartTimeout:'10s',
    sourceOnDemandCloseAfter:'10s', maxReaders:0, srtReadPassphrase:'', useAbsoluteTimestamp:false,
    alwaysAvailable:false, alwaysAvailableFile:'',
    record:false, recordPath:'./recordings/%path/%Y-%m-%d_%H-%M-%S-%f', recordFormat:'fmp4',
    recordPartDuration:'1s', recordMaxPartSize:'50M', recordSegmentDuration:'1h', recordDeleteAfter:'1d',
    overridePublisher:true, srtPublishPassphrase:'', rtspDemuxMpegts:false,
    rtspTransport:'automatic', rtspAnyPort:false, rtspRangeType:'', rtspRangeStart:'', rtspScale:'', rtspUDPRange:'32768, 60999',
    rtpSDP:'', moqTransport:'quic',
    whepBearerToken:'', whepSTUNTimeout:'5s', whepHandshakeTimeout:'10s', whepTrackTimeout:'2s',
    sourceRedirect:'',
    hooks:{runOnInit:'', runOnInitRestart:false, runOnDemand:'', runOnDemandRestart:false, runOnDemandStartTimeout:'10s',
      runOnDemandCloseAfter:'10s', runOnUnDemand:'', runOnAvailable:'', runOnAvailableRestart:false, runOnUnavailable:'',
      runOnOnline:'', runOnOnlineRestart:false, runOnOffline:'', runOnRead:'', runOnReadRestart:false, runOnUnread:'',
      runOnRecordSegmentCreate:'', runOnRecordSegmentComplete:''},
    extraYaml:''
  },
  paths:[
    {name:'camera1', source:'rtsp://admin:infra%40GCM@10.0.4.97:554/Streaming/Channels/102', rtspTransport:'tcp', record:false, extraYaml:''},
    {name:'camera2', source:'rtsp://admin:infra%40GCM@10.0.4.97:554/Streaming/Channels/202', rtspTransport:'tcp', record:false, extraYaml:''}
  ]
};
const defaultStateJSON = JSON.stringify(state);

/* =========================================================================
   SEÇÕES DE NAVEGAÇÃO
   ========================================================================= */
const SECTIONS = [
  {id:'general', n:'01', label:'Geral & Logs'},
  {id:'auth', n:'02', label:'Autenticação'},
  {id:'servers', n:'03', label:'API · Métricas · Debug'},
  {id:'rtsp', n:'04', label:'Servidor RTSP'},
  {id:'rtmp', n:'05', label:'Servidor RTMP'},
  {id:'hls', n:'06', label:'Servidor HLS'},
  {id:'webrtc', n:'07', label:'Servidor WebRTC'},
  {id:'srt', n:'08', label:'Servidor SRT'},
  {id:'moq', n:'09', label:'Servidor MoQ'},
  {id:'pathdefaults', n:'10', label:'Padrões de Path'},
  {id:'paths', n:'11', label:'Paths (câmeras/streams)'},
];
let current = 'general';

/* helpers de UI ---------------------------------------------------------- */
const $ = (s,el=document)=>el.querySelector(s);
const $$ = (s,el=document)=>Array.from(el.querySelectorAll(s));
function el(tag, attrs={}, ...kids){
  const e = document.createElement(tag);
  for(const k in attrs){
    if(k==='class') e.className = attrs[k];
    else if(k==='html') e.innerHTML = attrs[k];
    else if(k.startsWith('on')) e.addEventListener(k.slice(2), attrs[k]);
    else e.setAttribute(k, attrs[k]);
  }
  kids.flat().forEach(k=>{ if(k==null) return; e.appendChild(typeof k==='string'?document.createTextNode(k):k); });
  return e;
}
function iconTrash(){return el('svg',{viewBox:'0 0 24 24',fill:'none',stroke:'currentColor','stroke-width':'2','stroke-linecap':'round'},
  el('polyline',{points:'3 6 5 6 21 6'}), el('path',{d:'M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6'}), el('path',{d:'M10 11v6'}), el('path',{d:'M14 11v6'}), el('path',{d:'M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2'}));}
function iconPlus(){return el('svg',{viewBox:'0 0 24 24',fill:'none',stroke:'currentColor','stroke-width':'2','stroke-linecap':'round'}, el('line',{x1:12,y1:5,x2:12,y2:19}), el('line',{x1:5,y1:12,x2:19,y2:12}));}
function iconInfo(){return el('svg',{viewBox:'0 0 24 24',fill:'none',stroke:'currentColor','stroke-width':'2'}, el('circle',{cx:12,cy:12,r:10}), el('line',{x1:12,y1:16,x2:12,y2:12}), el('line',{x1:12,y1:8,x2:12.01,y2:8}));}

function field(labelText, inputEl, help){
  const wrap = el('div',{class:'field'});
  wrap.appendChild(el('label',{},labelText));
  wrap.appendChild(inputEl);
  if(help) wrap.appendChild(el('div',{class:'help'},help));
  return wrap;
}
function textInput(value, onChange, placeholder=''){
  const i = el('input',{type:'text', placeholder});
  i.value = value ?? '';
  i.addEventListener('input', ()=>onChange(i.value));
  return i;
}
function numInput(value, onChange){
  const i = el('input',{type:'number'});
  i.value = value ?? 0;
  i.addEventListener('input', ()=>onChange(i.value===''?0:Number(i.value)));
  return i;
}
function selectInput(value, options, onChange){
  const s = el('select',{});
  options.forEach(o=>{
    const [val,lab] = Array.isArray(o)?o:[o,o];
    const opt = el('option',{value:val}, lab);
    if(val===value) opt.selected = true;
    s.appendChild(opt);
  });
  s.addEventListener('change', ()=>onChange(s.value));
  return s;
}
function switchInput(checked, onChange, labelText, helpText){
  const row = el('div',{class:'switchrow'});
  const stxt = el('div',{class:'stxt'}, el('label',{},labelText), helpText?el('div',{class:'help'},helpText):null);
  const sw = el('label',{class:'switch'});
  const input = el('input',{type:'checkbox'});
  input.checked = !!checked;
  input.addEventListener('change', ()=>{ onChange(input.checked); render(); });
  sw.appendChild(input); sw.appendChild(el('span',{class:'track'})); sw.appendChild(el('span',{class:'knob'}));
  row.appendChild(stxt); row.appendChild(sw);
  return row;
}
function chipsInput(values, allOptions, onChange){
  const wrap = el('div',{class:'chiplist'});
  allOptions.forEach(opt=>{
    const on = values.includes(opt);
    const c = el('span',{class:'chip'+(on?' on':'')}, opt);
    c.addEventListener('click', ()=>{
      const idx = values.indexOf(opt);
      if(idx>-1) values.splice(idx,1); else values.push(opt);
      onChange(values); render();
    });
    wrap.appendChild(c);
  });
  return wrap;
}
function tagListInput(values, onChange, placeholder){
  const wrap = el('div',{});
  const inputRow = el('div',{class:'taglist-input'});
  const inp = el('input',{type:'text', placeholder});
  const addBtn = el('button',{class:'btn', type:'button'}, '+');
  function add(){
    const v = inp.value.trim();
    if(v){ values.push(v); inp.value=''; onChange(values); render(); }
  }
  addBtn.addEventListener('click', add);
  inp.addEventListener('keydown', e=>{ if(e.key==='Enter'){ e.preventDefault(); add(); } });
  inputRow.appendChild(inp); inputRow.appendChild(addBtn);
  wrap.appendChild(inputRow);
  const list = el('div',{class:'taglist'});
  values.forEach((v,i)=>{
    const t = el('span',{class:'tagitem'}, v);
    const rm = el('button',{type:'button'}, '✕');
    rm.addEventListener('click', ()=>{ values.splice(i,1); onChange(values); render(); });
    t.appendChild(rm);
    list.appendChild(t);
  });
  wrap.appendChild(list);
  return wrap;
}
function note(text){
  return el('div',{class:'notebox'}, iconInfo(), el('div',{},text));
}
function collapsible(title, buildInner){
  const wrapBtn = el('div',{class:'subtle-toggle'},
    el('svg',{viewBox:'0 0 24 24',fill:'none',stroke:'currentColor','stroke-width':'2'}, el('polyline',{points:'9 18 15 12 9 6'})),
    title
  );
  const body = el('div',{class:'collapse'});
  body.appendChild(buildInner());
  wrapBtn.addEventListener('click', ()=>{
    wrapBtn.classList.toggle('open');
    body.classList.toggle('open');
  });
  const holder = el('div',{});
  holder.appendChild(wrapBtn); holder.appendChild(body);
  return holder;
}

/* =========================================================================
   BUILDERS — servidor genérico (api/metrics/pprof/playback/rtmp/moq base)
   ========================================================================= */
function serverBasicsCard(title, tag, s, onCert=true){
  const c = el('div',{class:'card'});
  c.appendChild(el('h3',{}, title, el('span',{class:'tag'}, tag)));
  c.appendChild(switchInput(s.enabled, v=>s.enabled=v, 'Ativar servidor', 'Liga ou desliga este serviço do MediaMTX.'));
  if(s.enabled){
    const grid = el('div',{class:'row2'});
    grid.appendChild(field('Endereço TCP/HTTP', textInput(s.address, v=>s.address=v,':porta')));
    if('encryption' in s) grid.appendChild(field('HTTPS', selectBool(s.encryption, v=>s.encryption=v)));
    c.appendChild(grid);
    if(s.encryption){
      const g2 = el('div',{class:'row2'});
      g2.appendChild(field('Chave do servidor (key)', textInput(s.key, v=>s.key=v)));
      g2.appendChild(field('Certificado do servidor (cert)', textInput(s.cert, v=>s.cert=v)));
      c.appendChild(g2);
    }
    c.appendChild(field('Origens CORS permitidas', tagListInput(s.origins, v=>s.origins=v, 'ex: http://*.exemplo.com'),
      'Domínios que podem acessar este servidor via navegador. "*" libera geral.'));
    c.appendChild(field('Proxies confiáveis (IP/CIDR)', tagListInput(s.proxies, v=>s.proxies=v, 'ex: 10.0.0.1/32'),
      'IPs de proxies na frente deste servidor, usados para ler o IP real do cliente via X-Forwarded-For.'));
  }
  return c;
}
function selectBool(value, onChange){
  return selectInput(value?'yes':'no', [['yes','Sim'],['no','Não']], v=>onChange(v==='yes'));
}

/* =========================================================================
   RENDERERS POR SEÇÃO
   ========================================================================= */
const RENDERERS = {

general(root){
  const s = state.general;
  root.appendChild(sectionHead('Geral & Logs','Comportamento global do servidor: nível de log, destino dos logs, timeouts de leitura/escrita e comandos disparados em conexões.'));

  const c1 = el('div',{class:'card'});
  c1.appendChild(el('h3',{},'Logging'));
  const g1 = el('div',{class:'row2'});
  g1.appendChild(field('Nível de verbosidade', selectInput(s.logLevel, ['error','warn','info','debug'], v=>s.logLevel=v)));
  g1.appendChild(field('Log estruturado (JSONL)', selectBool(s.logStructured, v=>s.logStructured=v)));
  c1.appendChild(g1);
  c1.appendChild(field('Destinos do log', chipsInput(s.logDestinations, ['stdout','file','syslog'], v=>s.logDestinations=v),
    'Onde as mensagens de log serão escritas — pode combinar mais de um.'));
  if(s.logDestinations.includes('file')) c1.appendChild(field('Arquivo de log', textInput(s.logFile, v=>s.logFile=v)));
  if(s.logDestinations.includes('syslog')) c1.appendChild(field('Prefixo do syslog', textInput(s.sysLogPrefix, v=>s.sysLogPrefix=v)));
  c1.appendChild(switchInput(s.dumpPackets, v=>s.dumpPackets=v, 'Despejar pacotes em disco', 'Só para depuração — gera muitos dados.'));
  root.appendChild(c1);

  const c2 = el('div',{class:'card'});
  c2.appendChild(el('h3',{},'Timeouts & Fila'));
  const g2 = el('div',{class:'row3'});
  g2.appendChild(field('Timeout de leitura', textInput(s.readTimeout, v=>s.readTimeout=v)));
  g2.appendChild(field('Timeout de escrita', textInput(s.writeTimeout, v=>s.writeTimeout=v)));
  g2.appendChild(field('Tamanho da fila de saída', numInput(s.writeQueueSize, v=>s.writeQueueSize=v),'Maior = mais throughput; menor = economiza RAM.'));
  c2.appendChild(g2);
  const g3 = el('div',{class:'row2'});
  g3.appendChild(field('Payload UDP máximo (bytes)', numInput(s.udpMaxPayloadSize, v=>s.udpMaxPayloadSize=v)));
  g3.appendChild(field('Buffer de leitura UDP (bytes)', numInput(s.udpReadBufferSize, v=>s.udpReadBufferSize=v), '0 = usar o padrão do sistema operacional.'));
  c2.appendChild(g3);
  root.appendChild(c2);

  const c3 = el('div',{class:'card'});
  c3.appendChild(el('h3',{},'Hooks de conexão'));
  c3.appendChild(field('Comando ao conectar (runOnConnect)', textInput(s.runOnConnect, v=>s.runOnConnect=v,'ex: bash script.sh')));
  c3.appendChild(switchInput(s.runOnConnectRestart, v=>s.runOnConnectRestart=v, 'Reiniciar comando se ele sair'));
  c3.appendChild(field('Comando ao desconectar (runOnDisconnect)', textInput(s.runOnDisconnect, v=>s.runOnDisconnect=v)));
  root.appendChild(c3);
},

auth(root){
  const s = state.auth;
  root.appendChild(sectionHead('Autenticação','Como os clientes se autenticam para publicar, ler ou controlar o servidor: banco interno, HTTP externo ou JWT.'));

  const cMethod = el('div',{class:'card'});
  cMethod.appendChild(el('h3',{},'Método de autenticação'));
  cMethod.appendChild(field('authMethod', selectInput(s.authMethod, [['internal','Banco interno (usuários no YAML)'],['http','Servidor HTTP externo'],['jwt','Provedor JWT externo']], v=>{s.authMethod=v; render();})));
  root.appendChild(cMethod);

  if(s.authMethod==='internal'){
    const c = el('div',{class:'card'});
    c.appendChild(el('h3',{},'Usuários internos', el('span',{class:'tag'},'authInternalUsers')));
    s.internalUsers.forEach((u,i)=>{
      const item = el('div',{class:'arr-item'});
      const head = el('div',{class:'arr-item-head'}, el('span',{class:'lbl'}, `usuário ${i+1}`));
      const rm = el('button',{class:'iconbtn', type:'button'}, iconTrash());
      rm.addEventListener('click', ()=>{ s.internalUsers.splice(i,1); render(); });
      head.appendChild(rm);
      item.appendChild(head);
      const g = el('div',{class:'row2'});
      g.appendChild(field('Usuário', textInput(u.user, v=>u.user=v)));
      g.appendChild(field('Senha', textInput(u.pass, v=>u.pass=v,'em branco = qualquer senha')));
      item.appendChild(g);
      item.appendChild(field('IPs permitidos', tagListInput(u.ips, v=>u.ips=v,'ex: 127.0.0.1'), 'Vazio = qualquer IP.'));
      item.appendChild(el('div',{class:'help', style:'margin-bottom:8px;font-weight:500;color:var(--ink-dim)'}, 'Permissões'));
      u.permissions.forEach((p,pi)=>{
        const prow = el('div',{class:'row2', style:'align-items:end;'});
        prow.appendChild(field('Ação', selectInput(p.action, ['read','publish','playback','api','metrics','pprof'], v=>p.action=v)));
        const pf = field('Caminho (vazio = todos)', textInput(p.path, v=>p.path=v,'ex: cameras/~'));
        const delBtn = el('button',{class:'iconbtn', type:'button', style:'margin-left:8px;'}, iconTrash());
        delBtn.addEventListener('click', ()=>{ u.permissions.splice(pi,1); render(); });
        const line = el('div',{style:'display:flex; gap:10px; align-items:end;'}, prow, delBtn);
        item.appendChild(line);
      });
      const addPerm = el('button',{class:'addbtn', type:'button'}, iconPlus(), 'Adicionar permissão');
      addPerm.addEventListener('click', ()=>{ u.permissions.push({action:'read', path:''}); render(); });
      item.appendChild(addPerm);
      c.appendChild(item);
    });
    const addUser = el('button',{class:'addbtn', type:'button'}, iconPlus(), 'Adicionar usuário');
    addUser.addEventListener('click', ()=>{ s.internalUsers.push({user:'', pass:'', ips:[], permissions:[{action:'read', path:''}]}); render(); });
    c.appendChild(addUser);
    root.appendChild(c);
  }

  if(s.authMethod==='http'){
    const c = el('div',{class:'card'});
    c.appendChild(el('h3',{},'Servidor HTTP externo'));
    c.appendChild(field('URL de autenticação', textInput(s.httpAddress, v=>s.httpAddress=v,'https://auth.exemplo.com/check')));
    c.appendChild(field('Fingerprint do certificado (opcional)', textInput(s.httpFingerprint, v=>s.httpFingerprint=v)));
    root.appendChild(c);
  }

  if(s.authMethod==='jwt'){
    const c = el('div',{class:'card'});
    c.appendChild(el('h3',{},'Provedor JWT'));
    c.appendChild(field('URL do JWKS', textInput(s.jwtJWKS, v=>s.jwtJWKS=v)));
    const g = el('div',{class:'row2'});
    g.appendChild(field('Fingerprint do certificado', textInput(s.jwtFingerprint, v=>s.jwtFingerprint=v)));
    g.appendChild(field('Nome da claim de permissões', textInput(s.jwtClaimKey, v=>s.jwtClaimKey=v)));
    c.appendChild(g);
    const g2 = el('div',{class:'row2'});
    g2.appendChild(field('Issuer esperado', textInput(s.jwtIssuer, v=>s.jwtIssuer=v)));
    g2.appendChild(field('Audience esperado', textInput(s.jwtAudience, v=>s.jwtAudience=v)));
    c.appendChild(g2);
    root.appendChild(c);
  }
},

servers(root){
  root.appendChild(sectionHead('API · Métricas · Debug · Playback','Servidores auxiliares de controle e observabilidade. Ficam desligados por padrão por segurança.'));
  root.appendChild(serverBasicsCard('Control API', 'api', state.api));
  root.appendChild(serverBasicsCard('Métricas (Prometheus)', 'metrics', state.metrics));
  root.appendChild(serverBasicsCard('PPROF (performance)', 'pprof', state.pprof));
  root.appendChild(serverBasicsCard('Playback (download de gravações)', 'playback', state.playback));
},

rtsp(root){
  const s = state.rtsp;
  root.appendChild(sectionHead('Servidor RTSP','Publicação e leitura via RTSP/RTSPS — o protocolo mais usado por câmeras IP.'));
  const c = el('div',{class:'card'});
  c.appendChild(el('h3',{},'RTSP', el('span',{class:'tag'},'rtsp')));
  c.appendChild(switchInput(s.enabled, v=>s.enabled=v, 'Ativar servidor RTSP'));
  if(s.enabled){
    c.appendChild(field('Transportes habilitados', chipsInput(s.transports, ['tcp','udp','multicast'], v=>s.transports=v), 'O handshake é sempre feito por TCP.'));
    c.appendChild(field('Criptografia (RTSPS)', selectInput(s.encryption, [['no','Desligada'],['strict','Obrigatória (RTSPS)'],['optional','Opcional (ambas)']], v=>{s.encryption=v; render();})));
    const g = el('div',{class:'row2'});
    if(s.encryption!=='strict') g.appendChild(field('Endereço RTSP', textInput(s.address, v=>s.address=v)));
    if(s.encryption!=='no') g.appendChild(field('Endereço RTSPS', textInput(s.tlsAddress, v=>s.tlsAddress=v)));
    c.appendChild(g);
    if(s.transports.includes('udp')){
      const g2 = el('div',{class:'row2'});
      g2.appendChild(field('Porta UDP/RTP', textInput(s.rtpAddress, v=>s.rtpAddress=v)));
      g2.appendChild(field('Porta UDP/RTCP', textInput(s.rtcpAddress, v=>s.rtcpAddress=v)));
      c.appendChild(g2);
    }
    if(s.transports.includes('multicast')){
      const g3 = el('div',{class:'row3'});
      g3.appendChild(field('Faixa de IP multicast', textInput(s.multicastRange, v=>s.multicastRange=v)));
      g3.appendChild(field('Porta multicast RTP', numInput(s.multicastRTP, v=>s.multicastRTP=v)));
      g3.appendChild(field('Porta multicast RTCP', numInput(s.multicastRTCP, v=>s.multicastRTCP=v)));
      c.appendChild(g3);
    }
    if(s.encryption!=='no'){
      const g4 = el('div',{class:'row2'});
      g4.appendChild(field('Chave do servidor', textInput(s.key, v=>s.key=v)));
      g4.appendChild(field('Certificado do servidor', textInput(s.cert, v=>s.cert=v)));
      c.appendChild(g4);
    }
    c.appendChild(field('Métodos de autenticação', chipsInput(s.authMethods, ['basic','digest'], v=>s.authMethods=v)));
    c.appendChild(field('Proxies confiáveis', tagListInput(s.proxies, v=>s.proxies=v)));
  }
  root.appendChild(c);
},

rtmp(root){
  const s = state.rtmp;
  root.appendChild(sectionHead('Servidor RTMP','Publicação e leitura via RTMP/RTMPS — usado por OBS e encoders legados.'));
  const c = el('div',{class:'card'});
  c.appendChild(el('h3',{},'RTMP', el('span',{class:'tag'},'rtmp')));
  c.appendChild(switchInput(s.enabled, v=>s.enabled=v, 'Ativar servidor RTMP'));
  if(s.enabled){
    c.appendChild(field('Criptografia (RTMPS)', selectInput(s.encryption, [['no','Desligada'],['strict','Obrigatória (RTMPS)'],['optional','Opcional (ambas)']], v=>{s.encryption=v; render();})));
    const g = el('div',{class:'row2'});
    if(s.encryption!=='strict') g.appendChild(field('Endereço RTMP', textInput(s.address, v=>s.address=v)));
    if(s.encryption!=='no') g.appendChild(field('Endereço RTMPS', textInput(s.tlsAddress, v=>s.tlsAddress=v)));
    c.appendChild(g);
    if(s.encryption!=='no'){
      const g2 = el('div',{class:'row2'});
      g2.appendChild(field('Chave do servidor', textInput(s.key, v=>s.key=v)));
      g2.appendChild(field('Certificado do servidor', textInput(s.cert, v=>s.cert=v)));
      c.appendChild(g2);
    }
    c.appendChild(field('Proxies confiáveis', tagListInput(s.proxies, v=>s.proxies=v)));
  }
  root.appendChild(c);
},

hls(root){
  const s = state.hls;
  root.appendChild(sectionHead('Servidor HLS','Leitura via HLS — compatível com qualquer navegador ou player, com maior latência que WebRTC.'));
  const c = el('div',{class:'card'});
  c.appendChild(el('h3',{},'HLS', el('span',{class:'tag'},'hls')));
  c.appendChild(switchInput(s.enabled, v=>s.enabled=v, 'Ativar servidor HLS'));
  if(s.enabled){
    const g = el('div',{class:'row2'});
    g.appendChild(field('Endereço', textInput(s.address, v=>s.address=v)));
    g.appendChild(field('HTTPS', selectBool(s.encryption, v=>{s.encryption=v; render();}), 'Obrigatório para Low-Latency HLS em dispositivos Apple.'));
    c.appendChild(g);
    if(s.encryption){
      const g2 = el('div',{class:'row2'});
      g2.appendChild(field('Chave do servidor', textInput(s.key, v=>s.key=v)));
      g2.appendChild(field('Certificado do servidor', textInput(s.cert, v=>s.cert=v)));
      c.appendChild(g2);
    }
    const g3 = el('div',{class:'row2'});
    g3.appendChild(field('Variante do protocolo', selectInput(s.variant, [['mpegts','MPEG-TS (compatibilidade máxima)'],['fmp4','fMP4 (mais eficiente)'],['lowLatency','Low-Latency HLS']], v=>s.variant=v)));
    g3.appendChild(field('Sempre remuxar (hlsAlwaysRemux)', selectBool(s.alwaysRemux, v=>s.alwaysRemux=v), 'Evita atraso na 1ª requisição, mas gasta recursos continuamente.'));
    c.appendChild(g3);
    const g4 = el('div',{class:'row3'});
    g4.appendChild(field('Segmentos mantidos', numInput(s.segmentCount, v=>s.segmentCount=v)));
    g4.appendChild(field('Duração mínima do segmento', textInput(s.segmentDuration, v=>s.segmentDuration=v)));
    g4.appendChild(field('Duração mínima da parte', textInput(s.partDuration, v=>s.partDuration=v)));
    c.appendChild(g4);
    const g5 = el('div',{class:'row3'});
    g5.appendChild(field('Tamanho máx. do segmento', textInput(s.segmentMaxSize, v=>s.segmentMaxSize=v)));
    g5.appendChild(field('Fechar muxer após', textInput(s.muxerCloseAfter, v=>s.muxerCloseAfter=v)));
    g5.appendChild(field('Diretório dos segmentos', textInput(s.directory, v=>s.directory=v,'vazio = memória')));
    c.appendChild(g5);
    c.appendChild(field('Origens CORS', tagListInput(s.origins, v=>s.origins=v)));
    c.appendChild(collapsible('Avançado: proxies confiáveis e segredo de CDN', ()=>{
      const box = el('div',{});
      box.appendChild(field('Proxies confiáveis', tagListInput(s.proxies, v=>s.proxies=v)));
      box.appendChild(field('Segredo do CDN (Bearer)', textInput(s.cdnSecret, v=>s.cdnSecret=v)));
      return box;
    }));
  }
  root.appendChild(c);
},

webrtc(root){
  const s = state.webrtc;
  root.appendChild(sectionHead('Servidor WebRTC','Leitura e publicação de baixíssima latência via WebRTC (WHIP/WHEP).'));
  const c = el('div',{class:'card'});
  c.appendChild(el('h3',{},'WebRTC', el('span',{class:'tag'},'webrtc')));
  c.appendChild(switchInput(s.enabled, v=>s.enabled=v, 'Ativar servidor WebRTC'));
  if(s.enabled){
    const g = el('div',{class:'row2'});
    g.appendChild(field('Endereço TCP/HTTP', textInput(s.address, v=>s.address=v)));
    g.appendChild(field('HTTPS (handshake)', selectBool(s.encryption, v=>{s.encryption=v; render();})));
    c.appendChild(g);
    if(s.encryption){
      const g2 = el('div',{class:'row2'});
      g2.appendChild(field('Chave do servidor', textInput(s.key, v=>s.key=v)));
      g2.appendChild(field('Certificado do servidor', textInput(s.cert, v=>s.cert=v)));
      c.appendChild(g2);
    }
    const g3 = el('div',{class:'row2'});
    g3.appendChild(field('Listener local UDP/ICE', textInput(s.localUDP, v=>s.localUDP=v)));
    g3.appendChild(field('Listener local TCP/ICE (opcional)', textInput(s.localTCP, v=>s.localTCP=v,'vazio = desativado')));
    c.appendChild(g3);
    c.appendChild(switchInput(s.ipsFromInterfaces, v=>s.ipsFromInterfaces=v, 'Detectar IPs pelas interfaces de rede', 'Envia automaticamente os IPs das interfaces locais aos clientes.'));
    if(s.ipsFromInterfaces) c.appendChild(field('Interfaces específicas (opcional)', tagListInput(s.ipsFromInterfacesList, v=>s.ipsFromInterfacesList=v,'ex: eth0'),'Vazio = todas as interfaces.'));
    c.appendChild(field('Hosts/IPs adicionais', tagListInput(s.additionalHosts, v=>s.additionalHosts=v)));
    c.appendChild(field('Origens CORS', tagListInput(s.origins, v=>s.origins=v)));

    c.appendChild(collapsible('Avançado: servidores ICE, timeouts e proxies', ()=>{
      const box = el('div',{});
      box.appendChild(el('div',{class:'help', style:'font-weight:500;color:var(--ink-dim);margin-bottom:8px;'},'Servidores ICE (STUN/TURN) — necessários quando os listeners locais não são alcançáveis pelos clientes.'));
      s.iceServers.forEach((ice,i)=>{
        const item = el('div',{class:'arr-item'});
        const head = el('div',{class:'arr-item-head'}, el('span',{class:'lbl'},`servidor ICE ${i+1}`));
        const rm = el('button',{class:'iconbtn', type:'button'}, iconTrash());
        rm.addEventListener('click', ()=>{ s.iceServers.splice(i,1); render(); });
        head.appendChild(rm); item.appendChild(head);
        item.appendChild(field('URL', textInput(ice.url, v=>ice.url=v,'stun:stun.l.google.com:19302')));
        const g = el('div',{class:'row2'});
        g.appendChild(field('Usuário', textInput(ice.username, v=>ice.username=v,'ou AUTH_SECRET')));
        g.appendChild(field('Senha', textInput(ice.password, v=>ice.password=v)));
        item.appendChild(g);
        item.appendChild(switchInput(ice.clientOnly, v=>ice.clientOnly=v, 'Somente para clientes (clientOnly)'));
        box.appendChild(item);
      });
      const add = el('button',{class:'addbtn', type:'button'}, iconPlus(), 'Adicionar servidor ICE');
      add.addEventListener('click', ()=>{ s.iceServers.push({url:'', username:'', password:'', clientOnly:false}); render(); });
      box.appendChild(add);
      const g3 = el('div',{class:'row3'});
      g3.appendChild(field('Timeout de coleta STUN', textInput(s.stunTimeout, v=>s.stunTimeout=v)));
      g3.appendChild(field('Timeout de handshake', textInput(s.handshakeTimeout, v=>s.handshakeTimeout=v)));
      g3.appendChild(field('Timeout de coleta de tracks', textInput(s.trackTimeout, v=>s.trackTimeout=v)));
      box.appendChild(g3);
      box.appendChild(field('Proxies confiáveis', tagListInput(s.proxies, v=>s.proxies=v)));
      return box;
    }));
  }
  root.appendChild(c);
},

srt(root){
  const s = state.srt;
  root.appendChild(sectionHead('Servidor SRT','Publicação e leitura via SRT — protocolo de baixa latência com boa resiliência a redes instáveis.'));
  const c = el('div',{class:'card'});
  c.appendChild(el('h3',{},'SRT', el('span',{class:'tag'},'srt')));
  c.appendChild(switchInput(s.enabled, v=>s.enabled=v, 'Ativar servidor SRT'));
  if(s.enabled) c.appendChild(field('Endereço UDP/SRT', textInput(s.address, v=>s.address=v)));
  root.appendChild(c);
},

moq(root){
  const s = state.moq;
  root.appendChild(sectionHead('Servidor MoQ','Media over QUIC — protocolo experimental de nova geração, expõe também um cliente web.'));
  const c = el('div',{class:'card'});
  c.appendChild(el('h3',{},'MoQ', el('span',{class:'tag'},'moq')));
  c.appendChild(switchInput(s.enabled, v=>s.enabled=v, 'Ativar servidor MoQ'));
  if(s.enabled){
    const g = el('div',{class:'row3'});
    g.appendChild(field('HTTP/2 (cliente web)', textInput(s.http2, v=>s.http2=v)));
    g.appendChild(field('HTTP/3 (WebTransport)', textInput(s.http3, v=>s.http3=v)));
    g.appendChild(field('QUIC nativo', textInput(s.quic, v=>s.quic=v)));
    c.appendChild(g);
    const g2 = el('div',{class:'row2'});
    g2.appendChild(field('Chave do servidor', textInput(s.key, v=>s.key=v),'"auto.key" gera automaticamente.'));
    g2.appendChild(field('Certificado do servidor', textInput(s.cert, v=>s.cert=v)));
    c.appendChild(g2);
    c.appendChild(field('Origens CORS', tagListInput(s.origins, v=>s.origins=v)));
    c.appendChild(field('Proxies confiáveis', tagListInput(s.proxies, v=>s.proxies=v)));
  }
  root.appendChild(c);
},

pathdefaults(root){
  const s = state.pathDefaults;
  root.appendChild(sectionHead('Padrões de Path','Configurações aplicadas a todo path que não as sobrescrever individualmente — origem do stream, gravação, hooks.'));

  const c1 = el('div',{class:'card'});
  c1.appendChild(el('h3',{},'Origem padrão do stream'));
  c1.appendChild(field('source', selectInput(s.source, [['publisher','publisher — recebido de um cliente RTSP/RTMP/WebRTC/SRT'],['rtsp://...','Pull de outra câmera/servidor RTSP'],['redirect','Redirecionar para outro path/servidor'],['rpiCamera','Raspberry Pi Camera']], v=>s.source=v),
    'Cada path individual pode sobrescrever este valor com sua própria URL de origem.'));
  const g1 = el('div',{class:'row2'});
  g1.appendChild(switchInput(s.sourceOnDemand, v=>s.sourceOnDemand=v, 'Puxar sob demanda (sourceOnDemand)', 'Só conecta à origem quando houver leitor conectado.'));
  g1.appendChild(field('Máx. de leitores (0 = sem limite)', numInput(s.maxReaders, v=>s.maxReaders=v)));
  c1.appendChild(g1);
  if(s.sourceOnDemand){
    const g2 = el('div',{class:'row2'});
    g2.appendChild(field('Timeout inicial de conexão', textInput(s.sourceOnDemandStartTimeout, v=>s.sourceOnDemandStartTimeout=v)));
    g2.appendChild(field('Fechar após sem leitores', textInput(s.sourceOnDemandCloseAfter, v=>s.sourceOnDemandCloseAfter=v)));
    c1.appendChild(g2);
  }
  root.appendChild(c1);

  const c2 = el('div',{class:'card'});
  c2.appendChild(el('h3',{},'Gravação (record)'));
  c2.appendChild(switchInput(s.record, v=>s.record=v, 'Gravar streams em disco'));
  if(s.record){
    c2.appendChild(field('Caminho dos segmentos', textInput(s.recordPath, v=>s.recordPath=v),'Variáveis: %path %Y %m %d %H %M %S %f %z %s'));
    const g = el('div',{class:'row2'});
    g.appendChild(field('Formato', selectInput(s.recordFormat, [['fmp4','fMP4'],['mpegts','MPEG-TS']], v=>s.recordFormat=v)));
    g.appendChild(field('Duração mínima do segmento', textInput(s.recordSegmentDuration, v=>s.recordSegmentDuration=v)));
    c2.appendChild(g);
    const g2 = el('div',{class:'row2'});
    g2.appendChild(field('Duração da parte', textInput(s.recordPartDuration, v=>s.recordPartDuration=v),'Equivale ao RPO em caso de falha.'));
    g2.appendChild(field('Tamanho máx. da parte', textInput(s.recordMaxPartSize, v=>s.recordMaxPartSize=v)));
    c2.appendChild(g2);
    c2.appendChild(field('Apagar gravações após', textInput(s.recordDeleteAfter, v=>s.recordDeleteAfter=v),'0s desativa a exclusão automática.'));
  }
  root.appendChild(c2);

  const c3 = el('div',{class:'card'});
  c3.appendChild(el('h3',{},'Publicação & fontes específicas'));
  c3.appendChild(switchInput(s.overridePublisher, v=>s.overridePublisher=v, 'Permitir substituir publicador atual', 'Um novo cliente pode desconectar o publicador em curso e assumir o path.'));
  c3.appendChild(collapsible('Avançado: RTSP pull, SRT, MoQ e WHEP como origem', ()=>{
    const box = el('div',{});
    box.appendChild(el('div',{class:'help', style:'font-weight:500;color:var(--ink-dim);margin:2px 0 10px;'},'Usado quando o "source" acima é uma URL rtsp://, srt://, moqt:// ou whep://.'));
    const g = el('div',{class:'row2'});
    g.appendChild(field('Transporte RTSP', selectInput(s.rtspTransport, ['automatic','udp','multicast','tcp'], v=>s.rtspTransport=v)));
    g.appendChild(field('Faixa de portas UDP de origem', textInput(s.rtspUDPRange, v=>s.rtspUDPRange=v)));
    box.appendChild(g);
    box.appendChild(switchInput(s.rtspAnyPort, v=>s.rtspAnyPort=v, 'Aceitar portas de origem quaisquer', '⚠ Reduz segurança — use só se a origem exigir.'));
    box.appendChild(switchInput(s.rtspDemuxMpegts, v=>s.rtspDemuxMpegts=v, 'Demuxar MPEG-TS sobre RTSP', 'Expõe H.264/H.265/AAC como tracks nativas para HLS/WebRTC.'));
    box.appendChild(field('Senha de leitura SRT', textInput(s.srtReadPassphrase, v=>s.srtReadPassphrase=v)));
    box.appendChild(field('Senha de publicação SRT', textInput(s.srtPublishPassphrase, v=>s.srtPublishPassphrase=v)));
    box.appendChild(field('Transporte MoQ', selectInput(s.moqTransport, ['quic','webtransport'], v=>s.moqTransport=v)));
    box.appendChild(field('Bearer token WHEP', textInput(s.whepBearerToken, v=>s.whepBearerToken=v)));
    box.appendChild(field('Path de redirecionamento (source: redirect)', textInput(s.sourceRedirect, v=>s.sourceRedirect=v,'/outroPath ou rtsp://...')));
    return box;
  }));
  root.appendChild(c3);

  const c4 = el('div',{class:'card'});
  c4.appendChild(el('h3',{},'Hooks (comandos automáticos)'));
  const h = s.hooks;
  [
    ['runOnInit','Ao inicializar o path'], ['runOnDemand','Ao ser solicitado sem publicador (on-demand)'],
    ['runOnAvailable','Quando o stream fica disponível'], ['runOnUnavailable','Quando o stream fica indisponível'],
    ['runOnRead','Quando um cliente começa a ler'], ['runOnUnread','Quando um cliente para de ler'],
    ['runOnRecordSegmentCreate','Ao criar um segmento de gravação'], ['runOnRecordSegmentComplete','Ao completar um segmento de gravação'],
  ].forEach(([key,label])=>{
    c4.appendChild(field(label, textInput(h[key], v=>h[key]=v, 'comando de shell')));
  });
  root.appendChild(c4);

  const c5 = el('div',{class:'card'});
  c5.appendChild(el('h3',{},'YAML extra em pathDefaults', el('span',{class:'tag'},'opcional')));
  c5.appendChild(note('Cole aqui chaves adicionais não cobertas pela interface (ex: alwaysAvailableTracks, forward, ajustes finos de rpiCamera). O conteúdo é inserido indentado dentro de pathDefaults, sem validação.'));
  c5.appendChild(field('', (()=>{ const t = el('textarea',{placeholder:'forward:\\n  - dest: rtsp://outro-servidor:8554/path'}); t.value = s.extraYaml; t.addEventListener('input', ()=>s.extraYaml=t.value); return t; })()));
  root.appendChild(c5);
},

paths(root){
  root.appendChild(sectionHead('Paths','Streams individuais — cada um herda os Padrões de Path e pode sobrescrever a origem, o transporte e a gravação.'));
  const list = state.paths;
  const wrap = el('div',{});
  list.forEach((p,i)=>{
    const item = el('div',{class:'card'});
    item.appendChild(el('h3',{}, p.name || `path ${i+1}`, (()=>{ const rm=el('button',{class:'iconbtn', type:'button'}, iconTrash()); rm.addEventListener('click', ()=>{ list.splice(i,1); render(); }); return rm; })()));
    const g = el('div',{class:'row2'});
    g.appendChild(field('Nome do path', textInput(p.name, v=>{p.name=v; renderCrumb();}, 'ex: camera1')));
    g.appendChild(field('Transporte RTSP (se origem for RTSP)', selectInput(p.rtspTransport, ['','automatic','udp','multicast','tcp'], v=>p.rtspTransport=v)));
    item.appendChild(g);
    item.appendChild(field('Origem (source)', textInput(p.source, v=>p.source=v,'rtsp://usuario:senha@ip:554/caminho'),
      'URL da câmera/stream de origem, ou "publisher" para receber de um cliente. Use %40 para @ dentro da senha.'));
    item.appendChild(switchInput(p.record, v=>p.record=v, 'Sobrescrever gravação para este path', 'Se desligado, usa o que estiver definido em Padrões de Path.'));
    item.appendChild(field('YAML extra deste path (opcional)', (()=>{ const t = el('textarea',{placeholder:'runOnReady: bash notify.sh'}); t.value=p.extraYaml; t.addEventListener('input', ()=>p.extraYaml=t.value); return t; })()));
    wrap.appendChild(item);
  });
  root.appendChild(wrap);
  const add = el('button',{class:'addbtn', type:'button'}, iconPlus(), 'Adicionar path');
  add.addEventListener('click', ()=>{ list.push({name:`camera${list.length+1}`, source:'', rtspTransport:'tcp', record:false, extraYaml:''}); render(); });
  root.appendChild(add);
  if(list.length===0) root.appendChild(note('Nenhum path configurado ainda — sem paths definidos, o servidor sobe mas não expõe nenhum stream nomeado.'));
},

};

function sectionHead(title, desc){
  return el('div',{class:'section-head'}, el('h2',{},title), el('p',{},desc));
}

/* =========================================================================
   RENDER PRINCIPAL
   ========================================================================= */
function renderNav(){
  const group = $('#navGroup');
  group.innerHTML = '';
  SECTIONS.forEach(sec=>{
    const active = sec.id===current;
    const on = sectionIsOn(sec.id);
    const btn = el('button',{class:'navitem'+(active?' active':'')},
      el('span',{class:'n'}, sec.n), el('span',{}, sec.label),
      el('span',{class:'dot '+(on?'on':'off')})
    );
    btn.addEventListener('click', ()=>{ current = sec.id; render(); closeNavMobile(); document.getElementById('formCol').scrollTop=0; window.scrollTo(0,0); });
    group.appendChild(btn);
  });
}
function sectionIsOn(id){
  const map = {rtsp:state.rtsp.enabled, rtmp:state.rtmp.enabled, hls:state.hls.enabled, webrtc:state.webrtc.enabled, srt:state.srt.enabled, moq:state.moq.enabled};
  if(id in map) return map[id];
  if(id==='paths') return state.paths.length>0;
  return null;
}
function renderCrumb(){
  const sec = SECTIONS.find(s=>s.id===current);
  $('#crumbTop').textContent = 'mediamtx.yml › '+(sec?sec.label:'');
}
function renderForm(){
  const root = $('#formCol');
  root.innerHTML = '';
  const idx = SECTIONS.findIndex(s=>s.id===current);
  root.appendChild(el('div',{class:'crumb'}, `Seção ${idx+1} de ${SECTIONS.length}`));
  RENDERERS[current](root);
  const foot = el('div',{class:'foot-nav'});
  if(idx>0){
    const prev = el('button',{class:'btn'}, '← '+SECTIONS[idx-1].label);
    prev.addEventListener('click', ()=>{ current=SECTIONS[idx-1].id; render(); window.scrollTo(0,0); });
    foot.appendChild(prev);
  } else foot.appendChild(el('span',{}));
  if(idx<SECTIONS.length-1){
    const next = el('button',{class:'btn primary'}, SECTIONS[idx+1].label+' →');
    next.addEventListener('click', ()=>{ current=SECTIONS[idx+1].id; render(); window.scrollTo(0,0); });
    foot.appendChild(next);
  } else foot.appendChild(el('span',{}));
  root.appendChild(foot);
}

/* =========================================================================
   GERADOR DE YAML
   ========================================================================= */
function y(v){
  if(v===''||v===null||v===undefined) return '';
  if(typeof v==='boolean') return v?'true':'false';
  if(typeof v==='number') return String(v);
  const s = String(v);
  if(/^(true|false|null|~|yes|no)$/i.test(s) || /[:#\[\]{}]/.test(s) && !s.startsWith('[')) return JSON.stringify(s);
  return s;
}
function listYaml(arr){
  if(!arr || arr.length===0) return '[]';
  return '['+arr.map(v=>JSON.stringify(v)).join(', ')+']';
}
function line(indent, key, value, comment){
  const pad = '  '.repeat(indent);
  const v = value===''||value===undefined ? '' : ' '+y(value);
  return pad+key+':'+v;
}

function buildYaml(){
  const out = [];
  const g = state.general, a = state.auth;
  out.push('###############################################');
  out.push('# Configurações Gerais');
  out.push('###############################################');
  out.push('');
  out.push(line(0,'logLevel',g.logLevel));
  out.push(line(0,'logDestinations',listYaml(g.logDestinations)));
  out.push(line(0,'logStructured',g.logStructured));
  if(g.logDestinations.includes('file')) out.push(line(0,'logFile',g.logFile));
  if(g.logDestinations.includes('syslog')) out.push(line(0,'sysLogPrefix',g.sysLogPrefix));
  out.push(line(0,'dumpPackets',g.dumpPackets));
  out.push('');
  out.push(line(0,'readTimeout',g.readTimeout));
  out.push(line(0,'writeTimeout',g.writeTimeout));
  out.push(line(0,'writeQueueSize',g.writeQueueSize));
  out.push(line(0,'udpMaxPayloadSize',g.udpMaxPayloadSize));
  out.push(line(0,'udpReadBufferSize',g.udpReadBufferSize));
  out.push('');
  if(g.runOnConnect) out.push(line(0,'runOnConnect',g.runOnConnect));
  out.push(line(0,'runOnConnectRestart',g.runOnConnectRestart));
  if(g.runOnDisconnect) out.push(line(0,'runOnDisconnect',g.runOnDisconnect));
  out.push('');

  out.push('###############################################');
  out.push('# Autenticação');
  out.push('###############################################');
  out.push('');
  out.push(line(0,'authMethod',a.authMethod));
  out.push('');
  if(a.authMethod==='internal'){
    out.push('authInternalUsers:');
    a.internalUsers.forEach(u=>{
      out.push(`  - user: ${u.user}`);
      out.push(`    pass: ${u.pass}`);
      out.push(`    ips: ${listYaml(u.ips)}`);
      out.push('    permissions:');
      u.permissions.forEach(p=>{
        out.push(`      - action: ${p.action}`);
        out.push(`        path: ${p.path||''}`);
      });
      out.push('');
    });
  } else {
    out.push('authInternalUsers: []');
    out.push('');
  }
  if(a.authMethod==='http'){
    out.push(line(0,'authHTTPAddress',a.httpAddress));
    if(a.httpFingerprint) out.push(line(0,'authHTTPFingerprint',a.httpFingerprint));
    out.push(line(0,'authHTTPExclude','[]'));
    out.push('');
  }
  if(a.authMethod==='jwt'){
    out.push(line(0,'authJWTJWKS',a.jwtJWKS));
    if(a.jwtFingerprint) out.push(line(0,'authJWTJWKSFingerprint',a.jwtFingerprint));
    out.push(line(0,'authJWTClaimKey',a.jwtClaimKey));
    out.push(line(0,'authJWTExclude','[]'));
    if(a.jwtIssuer) out.push(line(0,'authJWTIssuer',a.jwtIssuer));
    if(a.jwtAudience) out.push(line(0,'authJWTAudience',a.jwtAudience));
    out.push('');
  }

  function serverBlock(title, prefix, s, hasEncKeyword){
    out.push('###############################################');
    out.push('# '+title);
    out.push('###############################################');
    out.push('');
    out.push(line(0,prefix,s.enabled));
    out.push(line(0,prefix+'Address',s.address));
    if('encryption' in s) out.push(line(0,prefix+'Encryption',s.encryption));
    if('key' in s) out.push(line(0,prefix+'ServerKey',s.key));
    if('cert' in s) out.push(line(0,prefix+'ServerCert',s.cert));
    if('origins' in s) out.push(line(0,prefix+'AllowOrigins',listYaml(s.origins)));
    if('proxies' in s) out.push(line(0,prefix+'TrustedProxies',listYaml(s.proxies)));
    out.push('');
  }
  serverBlock('Control API','api',state.api);
  serverBlock('Métricas','metrics',state.metrics);
  serverBlock('PPROF','pprof',state.pprof);
  serverBlock('Playback','playback',state.playback);

  const r = state.rtsp;
  out.push('###############################################');
  out.push('# Servidor RTSP');
  out.push('###############################################');
  out.push('');
  out.push(line(0,'rtsp',r.enabled));
  out.push(line(0,'rtspTransports',listYaml(r.transports)));
  out.push(line(0,'rtspEncryption',r.encryption));
  if(r.encryption!=='strict') out.push(line(0,'rtspAddress',r.address));
  if(r.encryption!=='no') out.push(line(0,'rtspsAddress',r.tlsAddress));
  if(r.transports.includes('udp')){
    out.push(line(0,'rtpAddress',r.rtpAddress));
    out.push(line(0,'rtcpAddress',r.rtcpAddress));
  }
  if(r.transports.includes('multicast')){
    out.push(line(0,'multicastIPRange',r.multicastRange));
    out.push(line(0,'multicastRTPPort',r.multicastRTP));
    out.push(line(0,'multicastRTCPPort',r.multicastRTCP));
  }
  if(r.encryption!=='no'){
    out.push(line(0,'rtspServerKey',r.key));
    out.push(line(0,'rtspServerCert',r.cert));
  }
  out.push(line(0,'rtspAuthMethods',listYaml(r.authMethods)));
  out.push(line(0,'rtspTrustedProxies',listYaml(r.proxies)));
  out.push('');

  const rt = state.rtmp;
  out.push('###############################################');
  out.push('# Servidor RTMP');
  out.push('###############################################');
  out.push('');
  out.push(line(0,'rtmp',rt.enabled));
  out.push(line(0,'rtmpEncryption',rt.encryption));
  if(rt.encryption!=='strict') out.push(line(0,'rtmpAddress',rt.address));
  if(rt.encryption!=='no'){
    out.push(line(0,'rtmpsAddress',rt.tlsAddress));
    out.push(line(0,'rtmpServerKey',rt.key));
    out.push(line(0,'rtmpServerCert',rt.cert));
  }
  out.push(line(0,'rtmpTrustedProxies',listYaml(rt.proxies)));
  out.push('');

  const h = state.hls;
  out.push('###############################################');
  out.push('# Servidor HLS');
  out.push('###############################################');
  out.push('');
  out.push(line(0,'hls',h.enabled));
  out.push(line(0,'hlsAddress',h.address));
  out.push(line(0,'hlsEncryption',h.encryption));
  if(h.encryption){
    out.push(line(0,'hlsServerKey',h.key));
    out.push(line(0,'hlsServerCert',h.cert));
  }
  out.push(line(0,'hlsAllowOrigins',listYaml(h.origins)));
  out.push(line(0,'hlsTrustedProxies',listYaml(h.proxies)));
  out.push(line(0,'hlsAlwaysRemux',h.alwaysRemux));
  out.push(line(0,'hlsVariant',h.variant));
  out.push(line(0,'hlsSegmentCount',h.segmentCount));
  out.push(line(0,'hlsSegmentDuration',h.segmentDuration));
  out.push(line(0,'hlsPartDuration',h.partDuration));
  out.push(line(0,'hlsSegmentMaxSize',h.segmentMaxSize));
  out.push(line(0,'hlsDirectory',h.directory));
  out.push(line(0,'hlsMuxerCloseAfter',h.muxerCloseAfter));
  if(h.cdnSecret) out.push(line(0,'hlsCDNSecret',h.cdnSecret));
  out.push('');

  const w = state.webrtc;
  out.push('###############################################');
  out.push('# Servidor WebRTC');
  out.push('###############################################');
  out.push('');
  out.push(line(0,'webrtc',w.enabled));
  out.push(line(0,'webrtcAddress',w.address));
  out.push(line(0,'webrtcEncryption',w.encryption));
  if(w.encryption){
    out.push(line(0,'webrtcServerKey',w.key));
    out.push(line(0,'webrtcServerCert',w.cert));
  }
  out.push(line(0,'webrtcAllowOrigins',listYaml(w.origins)));
  out.push(line(0,'webrtcTrustedProxies',listYaml(w.proxies)));
  out.push(line(0,'webrtcLocalUDPAddress',w.localUDP));
  out.push(line(0,'webrtcLocalTCPAddress',w.localTCP));
  out.push(line(0,'webrtcIPsFromInterfaces',w.ipsFromInterfaces));
  out.push(line(0,'webrtcIPsFromInterfacesList',listYaml(w.ipsFromInterfacesList)));
  out.push(line(0,'webrtcAdditionalHosts',listYaml(w.additionalHosts)));
  if(w.iceServers.length){
    out.push('webrtcICEServers2:');
    w.iceServers.forEach(ice=>{
      out.push(`  - url: ${ice.url}`);
      if(ice.username) out.push(`    username: '${ice.username}'`);
      if(ice.password) out.push(`    password: '${ice.password}'`);
      out.push(`    clientOnly: ${ice.clientOnly}`);
    });
  } else out.push('webrtcICEServers2: []');
  out.push(line(0,'webrtcSTUNGatherTimeout',w.stunTimeout));
  out.push(line(0,'webrtcHandshakeTimeout',w.handshakeTimeout));
  out.push(line(0,'webrtcTrackGatherTimeout',w.trackTimeout));
  out.push('');

  const srt = state.srt;
  out.push('###############################################');
  out.push('# Servidor SRT');
  out.push('###############################################');
  out.push('');
  out.push(line(0,'srt',srt.enabled));
  out.push(line(0,'srtAddress',srt.address));
  out.push('');

  const m = state.moq;
  out.push('###############################################');
  out.push('# Servidor MoQ');
  out.push('###############################################');
  out.push('');
  out.push(line(0,'moq',m.enabled));
  out.push(line(0,'moqHTTP2Address',m.http2));
  out.push(line(0,'moqHTTP3Address',m.http3));
  out.push(line(0,'moqQUICAddress',m.quic));
  out.push(line(0,'moqServerKey',m.key));
  out.push(line(0,'moqServerCert',m.cert));
  out.push(line(0,'moqAllowOrigins',listYaml(m.origins)));
  out.push(line(0,'moqTrustedProxies',listYaml(m.proxies)));
  out.push('');

  const pd = state.pathDefaults;
  out.push('###############################################');
  out.push('# Padrões de Path (pathDefaults)');
  out.push('###############################################');
  out.push('');
  out.push('pathDefaults:');
  out.push(`  source: ${pd.source}`);
  if(pd.sourceFingerprint) out.push(`  sourceFingerprint: ${pd.sourceFingerprint}`);
  out.push(`  sourceOnDemand: ${pd.sourceOnDemand}`);
  out.push(`  sourceOnDemandStartTimeout: ${pd.sourceOnDemandStartTimeout}`);
  out.push(`  sourceOnDemandCloseAfter: ${pd.sourceOnDemandCloseAfter}`);
  out.push(`  maxReaders: ${pd.maxReaders}`);
  if(pd.srtReadPassphrase) out.push(`  srtReadPassphrase: ${pd.srtReadPassphrase}`);
  out.push(`  useAbsoluteTimestamp: ${pd.useAbsoluteTimestamp}`);
  out.push('');
  out.push(`  alwaysAvailable: ${pd.alwaysAvailable}`);
  if(pd.alwaysAvailableFile) out.push(`  alwaysAvailableFile: "${pd.alwaysAvailableFile}"`);
  out.push('');
  out.push(`  record: ${pd.record}`);
  out.push(`  recordPath: ${pd.recordPath}`);
  out.push(`  recordFormat: ${pd.recordFormat}`);
  out.push(`  recordPartDuration: ${pd.recordPartDuration}`);
  out.push(`  recordMaxPartSize: ${pd.recordMaxPartSize}`);
  out.push(`  recordSegmentDuration: ${pd.recordSegmentDuration}`);
  out.push(`  recordDeleteAfter: ${pd.recordDeleteAfter}`);
  out.push('');
  out.push(`  overridePublisher: ${pd.overridePublisher}`);
  if(pd.srtPublishPassphrase) out.push(`  srtPublishPassphrase: ${pd.srtPublishPassphrase}`);
  out.push(`  rtspDemuxMpegts: ${pd.rtspDemuxMpegts}`);
  out.push('');
  out.push(`  rtspTransport: ${pd.rtspTransport}`);
  out.push(`  rtspAnyPort: ${pd.rtspAnyPort}`);
  if(pd.rtspRangeType) out.push(`  rtspRangeType: ${pd.rtspRangeType}`);
  if(pd.rtspRangeStart) out.push(`  rtspRangeStart: ${pd.rtspRangeStart}`);
  if(pd.rtspScale) out.push(`  rtspScale: ${pd.rtspScale}`);
  out.push(`  rtspUDPSourcePortRange: [${pd.rtspUDPRange}]`);
  out.push('');
  if(pd.rtpSDP) out.push(`  rtpSDP: ${pd.rtpSDP}`);
  out.push(`  moqTransport: ${pd.moqTransport}`);
  out.push('');
  if(pd.whepBearerToken) out.push(`  whepBearerToken: "${pd.whepBearerToken}"`);
  out.push(`  whepSTUNGatherTimeout: ${pd.whepSTUNTimeout}`);
  out.push(`  whepHandshakeTimeout: ${pd.whepHandshakeTimeout}`);
  out.push(`  whepTrackGatherTimeout: ${pd.whepTrackTimeout}`);
  out.push('');
  if(pd.sourceRedirect) out.push(`  sourceRedirect: ${pd.sourceRedirect}`);
  out.push('');
  const hk = pd.hooks;
  Object.entries({
    runOnInit:hk.runOnInit, runOnInitRestart:hk.runOnInitRestart,
    runOnDemand:hk.runOnDemand, runOnDemandRestart:hk.runOnDemandRestart,
    runOnDemandStartTimeout:hk.runOnDemandStartTimeout, runOnDemandCloseAfter:hk.runOnDemandCloseAfter,
    runOnUnDemand:hk.runOnUnDemand, runOnAvailable:hk.runOnAvailable, runOnAvailableRestart:hk.runOnAvailableRestart,
    runOnUnavailable:hk.runOnUnavailable, runOnOnline:hk.runOnOnline, runOnOnlineRestart:hk.runOnOnlineRestart,
    runOnOffline:hk.runOnOffline, runOnRead:hk.runOnRead, runOnReadRestart:hk.runOnReadRestart, runOnUnread:hk.runOnUnread,
    runOnRecordSegmentCreate:hk.runOnRecordSegmentCreate, runOnRecordSegmentComplete:hk.runOnRecordSegmentComplete
  }).forEach(([k,v])=>{
    if(typeof v==='boolean') out.push(`  ${k}: ${v}`);
    else if(v) out.push(`  ${k}: ${v}`);
  });
  if(pd.extraYaml.trim()){
    out.push('');
    pd.extraYaml.split('\n').forEach(l=>out.push('  '+l));
  }
  out.push('');

  out.push('###############################################');
  out.push('# Paths');
  out.push('###############################################');
  out.push('');
  if(state.paths.length===0){
    out.push('paths: {}');
  } else {
    out.push('paths:');
    state.paths.forEach(p=>{
      out.push(`  ${p.name||'unnamed'}:`);
      if(p.source) out.push(`    source: ${p.source}`);
      if(p.rtspTransport) out.push(`    rtspTransport: ${p.rtspTransport}`);
      if(p.record) out.push(`    record: true`);
      if(p.extraYaml.trim()) p.extraYaml.split('\n').forEach(l=>out.push('    '+l));
      out.push('');
    });
  }
  return out.join('\n');
}

function highlightYaml(text){
  return text.split('\n').map(l=>{
    if(/^\s*#/.test(l)) return `<span class="c">${escapeHtml(l)}</span>`;
    const m = l.match(/^(\s*(?:- )?)([A-Za-z][A-Za-z0-9_]*)(:)(.*)$/);
    if(m){
      return `${m[1]}<span class="k">${m[2]}</span><span class="c">:</span><span class="v">${escapeHtml(m[4])}</span>`;
    }
    return escapeHtml(l);
  }).join('\n');
}
function escapeHtml(s){ return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

function renderYaml(){
  const yamlStr = buildYaml();
  $('#yamlOut').innerHTML = highlightYaml(yamlStr);
  window.__yamlStr = yamlStr;
}

function updateLamp(){
  const anyOn = state.rtsp.enabled || state.rtmp.enabled || state.hls.enabled || state.webrtc.enabled || state.srt.enabled || state.moq.enabled;
  $('#statusLamp').style.background = anyOn ? 'var(--signal)' : 'var(--ink-faint)';
}

function render(){
  renderNav();
  renderForm();
  renderCrumb();
  renderYaml();
  updateLamp();
}

/* =========================================================================
   AÇÕES GLOBAIS
   ========================================================================= */
function downloadYaml(){
  const blob = new Blob([window.__yamlStr], {type:'text/yaml'});
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url; a.download = 'mediamtx.yml';
  document.body.appendChild(a); a.click(); a.remove();
  URL.revokeObjectURL(url);
}
function copyYaml(btn){
  navigator.clipboard.writeText(window.__yamlStr).then(()=>{
    const orig = btn.textContent;
    btn.textContent = 'Copiado ✓';
    btn.classList.add('copied-flash');
    setTimeout(()=>{ btn.textContent = orig; btn.classList.remove('copied-flash'); }, 1500);
  }).catch(()=>{
    const ta = document.createElement('textarea');
    ta.value = window.__yamlStr; document.body.appendChild(ta); ta.select();
    document.execCommand('copy'); ta.remove();
  });
}

function toast(msg, kind){
  let t = document.getElementById('toastBox');
  if(!t){
    t = document.createElement('div');
    t.id = 'toastBox';
    t.style.cssText = 'position:fixed;left:50%;top:calc(14px + env(safe-area-inset-top,0px));transform:translateX(-50%);z-index:99;padding:11px 18px;border-radius:9px;font-size:13px;font-family:var(--sans);box-shadow:0 8px 24px rgba(0,0,0,.35);max-width:min(90vw,480px);text-align:center;';
    document.body.appendChild(t);
  }
  t.textContent = msg;
  t.style.background = kind==='error' ? 'var(--danger)' : 'var(--ok)';
  t.style.color = '#0B0F0C';
  t.style.display = 'block';
  clearTimeout(t.__hideTimer);
  t.__hideTimer = setTimeout(()=>{ t.style.display='none'; }, 4200);
}

function saveToServer(){
  const btn = $('#saveServerBtn');
  const orig = btn.textContent;
  btn.disabled = true;
  btn.textContent = 'Salvando…';
  fetch('save.php', {
    method:'POST',
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body: new URLSearchParams({csrf:CSRF, yaml: window.__yamlStr})
  })
  .then(r=>r.json().catch(()=>({ok:false, error:'Resposta inválida do servidor.'})))
  .then(data=>{
    if(data.ok){
      toast(`Salvo em ${data.path} às ${data.savedAt}`, 'ok');
      $('#crumbTop').textContent = `${data.path} · gravado em ${data.savedAt} (${(data.bytes/1024).toFixed(1)} KB)`;
    } else {
      toast(data.error || 'Falha ao salvar no servidor.', 'error');
    }
  })
  .catch(()=>toast('Falha de rede ao tentar salvar no servidor.', 'error'))
  .finally(()=>{ btn.disabled=false; btn.textContent=orig; });
}

$('#downloadBtn').addEventListener('click', downloadYaml);
$('#downloadBtnTop').addEventListener('click', downloadYaml);
$('#copyBtn').addEventListener('click', (e)=>copyYaml(e.currentTarget));
$('#saveServerBtn').addEventListener('click', saveToServer);
$('#resetBtn').addEventListener('click', ()=>{
  if(confirm('Restaurar todos os campos para os valores originais do mediamtx.yml enviado?')){
    Object.assign(state, JSON.parse(defaultStateJSON));
    render();
  }
});

/* mobile toggles */
function openNavMobile(){ $('#navCol').classList.add('open'); }
function closeNavMobile(){ $('#navCol').classList.remove('open'); }
$('#navMobileToggle').addEventListener('click', openNavMobile);
$('#navCloseBtn').addEventListener('click', closeNavMobile);
$('#yamlMobileToggle').addEventListener('click', ()=>{ $('#yamlCol').classList.add('open'); $('#yamlCloseBtn').style.display='inline-flex'; });
$('#yamlCloseBtn').addEventListener('click', ()=>{ $('#yamlCol').classList.remove('open'); });

render();
</script>
</body>
</html>
