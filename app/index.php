<?php
// Página de apresentação do ambiente DevOps (Proxmox / Kubernetes)
$ultima_atualizacao = date('d/m/Y \à\s H:i');
$host_cluster = "proxmox-lab-01";
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Ambiente DevOps</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=IBM+Plex+Sans:wght@400;500&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
  :root{
    --bg: #12151b;
    --bg-panel: #181d25;
    --bg-panel-raised: #1d232c;
    --border: #262e39;
    --border-soft: #1f2530;
    --text: #e6e9ee;
    --text-dim: #8a94a3;
    --text-faint: #5c6572;
    --accent: #3fbf9f;
    --accent-dev: #5b8def;
    --accent-test: #e9a23b;
    --accent-prod: #e2574c;
    --font-display: 'Space Grotesk', sans-serif;
    --font-body: 'IBM Plex Sans', sans-serif;
    --font-mono: 'IBM Plex Mono', monospace;
  }

  *{ box-sizing: border-box; }
  html{ scroll-behavior: smooth; }

  body{
    margin: 0;
    background: var(--bg);
    background-image:
      linear-gradient(180deg, rgba(63,191,159,0.05), transparent 420px),
      repeating-linear-gradient(0deg, rgba(255,255,255,0.012) 0px, rgba(255,255,255,0.012) 1px, transparent 1px, transparent 3px);
    color: var(--text);
    font-family: var(--font-body);
    line-height: 1.55;
    -webkit-font-smoothing: antialiased;
  }

  a{ color: inherit; }
  ::selection{ background: rgba(63,191,159,0.35); }

  .wrap{
    max-width: 1080px;
    margin: 0 auto;
    padding: 0 32px;
  }

  /* ---------- header ---------- */
  header{
    padding: 28px 0 0;
    border-bottom: 1px solid var(--border-soft);
  }
  .topbar{
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-bottom: 24px;
  }
  .brand{
    display: flex;
    align-items: baseline;
    gap: 10px;
    font-family: var(--font-display);
    font-weight: 600;
    font-size: 1.05rem;
    letter-spacing: 0.01em;
  }
  .brand .dot{
    width: 8px; height: 8px;
    border-radius: 50%;
    background: var(--accent);
    box-shadow: 0 0 0 3px rgba(63,191,159,0.18);
    display: inline-block;
  }
  .brand .host{
    font-family: var(--font-mono);
    font-size: 0.78rem;
    color: var(--text-faint);
  }
  .status-pill{
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.82rem;
    color: var(--text-dim);
    font-family: var(--font-mono);
    padding: 6px 12px;
    border: 1px solid var(--border);
    border-radius: 999px;
    background: var(--bg-panel);
  }
  .status-pill .pulse{
    width: 6px; height: 6px;
    border-radius: 50%;
    background: var(--accent);
    animation: pulse 2.4s ease-in-out infinite;
  }
  @keyframes pulse{
    0%, 100% { opacity: 1; box-shadow: 0 0 0 0 rgba(63,191,159,0.5); }
    50% { opacity: 0.6; box-shadow: 0 0 0 4px rgba(63,191,159,0); }
  }

  /* ---------- hero ---------- */
  .hero{
    padding: 64px 0 56px;
  }
  .hero h1{
    font-family: var(--font-display);
    font-weight: 700;
    font-size: clamp(2.1rem, 4.4vw, 3.1rem);
    line-height: 1.15;
    letter-spacing: -0.01em;
    margin: 0 0 18px;
    max-width: 620px;
  }
  .hero p.lede{
    color: var(--text-dim);
    font-size: 1.05rem;
    max-width: 540px;
    margin: 0 0 44px;
  }

  .stat-row{
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1px;
    background: var(--border);
    border: 1px solid var(--border);
    border-radius: 10px;
    overflow: hidden;
  }
  .stat{
    background: var(--bg-panel);
    padding: 22px 24px;
  }
  .stat .value{
    font-family: var(--font-mono);
    font-size: 1.7rem;
    color: var(--text);
    font-weight: 500;
  }
  .stat .value small{
    font-size: 0.95rem;
    color: var(--text-faint);
    margin-left: 4px;
  }
  .stat .label{
    margin-top: 6px;
    font-size: 0.82rem;
    color: var(--text-dim);
  }

  /* ---------- topology ---------- */
  section{ padding: 56px 0; border-top: 1px solid var(--border-soft); }
  .section-head{
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    margin-bottom: 30px;
  }
  .section-head h2{
    font-family: var(--font-display);
    font-size: 1.4rem;
    font-weight: 600;
    margin: 0;
  }
  .section-head .note{
    color: var(--text-faint);
    font-size: 0.85rem;
  }

  .topology{
    display: flex;
    align-items: center;
    gap: 0;
    overflow-x: auto;
    padding: 8px 0 4px;
  }
  .topo-node{
    flex: 0 0 auto;
    background: var(--bg-panel);
    border: 1px solid var(--border);
    border-radius: 8px;
    padding: 14px 18px;
    min-width: 130px;
    text-align: center;
  }
  .topo-node .t-label{
    font-size: 0.88rem;
    color: var(--text);
    font-weight: 500;
  }
  .topo-node .t-sub{
    font-family: var(--font-mono);
    font-size: 0.72rem;
    color: var(--text-faint);
    margin-top: 3px;
  }
  .topo-arrow{
    flex: 0 0 auto;
    width: 44px;
    height: 1px;
    background: var(--border);
    position: relative;
    margin: 0 2px;
  }
  .topo-arrow::after{
    content: '';
    position: absolute;
    right: 0; top: -3px;
    width: 6px; height: 6px;
    border-right: 1px solid var(--text-faint);
    border-bottom: 1px solid var(--text-faint);
    transform: rotate(-45deg);
  }
  .topo-branch{
    display: flex;
    flex-direction: column;
    gap: 8px;
  }

  /* ---------- environments ---------- */
  .env-grid{
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
  }
  .env-card{
    background: var(--bg-panel);
    border: 1px solid var(--border);
    border-radius: 10px;
    padding: 22px 22px 20px;
    border-left: 3px solid var(--card-accent);
  }
  .env-card.dev{ --card-accent: var(--accent-dev); }
  .env-card.test{ --card-accent: var(--accent-test); }
  .env-card.prod{ --card-accent: var(--accent-prod); }

  .env-card .env-name{
    font-family: var(--font-display);
    font-size: 1.05rem;
    font-weight: 600;
    margin-bottom: 4px;
  }
  .env-card .env-tag{
    font-family: var(--font-mono);
    font-size: 0.72rem;
    color: var(--card-accent);
    margin-bottom: 14px;
    display: block;
  }
  .env-card p{
    color: var(--text-dim);
    font-size: 0.9rem;
    margin: 0 0 14px;
  }
  .env-meta{
    display: flex;
    flex-direction: column;
    gap: 6px;
    font-size: 0.78rem;
    color: var(--text-faint);
    font-family: var(--font-mono);
    border-top: 1px solid var(--border-soft);
    padding-top: 12px;
  }

  /* ---------- resources ---------- */
  .resource-panel{
    background: var(--bg-panel);
    border: 1px solid var(--border);
    border-radius: 10px;
    padding: 8px 0;
  }
  .resource-row{
    display: grid;
    grid-template-columns: 200px 1fr 90px;
    gap: 20px;
    align-items: center;
    padding: 16px 24px;
    border-bottom: 1px solid var(--border-soft);
  }
  .resource-row:last-child{ border-bottom: none; }
  .resource-row .r-name{ font-size: 0.9rem; color: var(--text); }
  .resource-row .r-name span{
    display: block;
    font-size: 0.74rem;
    color: var(--text-faint);
    margin-top: 2px;
    font-family: var(--font-mono);
  }
  .bar-track{
    height: 5px;
    background: var(--border-soft);
    border-radius: 4px;
    overflow: hidden;
  }
  .bar-fill{
    height: 100%;
    background: var(--accent);
    border-radius: 4px;
  }
  .resource-row .r-val{
    text-align: right;
    font-family: var(--font-mono);
    font-size: 0.85rem;
    color: var(--text-dim);
  }

  /* ---------- notes ---------- */
  .notes{
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
  }
  .note-card{
    border: 1px solid var(--border);
    border-radius: 10px;
    padding: 20px 22px;
    background: var(--bg-panel);
  }
  .note-card h3{
    font-family: var(--font-display);
    font-size: 0.95rem;
    margin: 0 0 8px;
    font-weight: 600;
  }
  .note-card p{
    color: var(--text-dim);
    font-size: 0.88rem;
    margin: 0;
  }

  footer{
    border-top: 1px solid var(--border-soft);
    padding: 26px 0 40px;
    display: flex;
    justify-content: space-between;
    font-size: 0.78rem;
    color: var(--text-faint);
    font-family: var(--font-mono);
  }

  @media (max-width: 760px){
    .stat-row{ grid-template-columns: 1fr; }
    .env-grid{ grid-template-columns: 1fr; }
    .notes{ grid-template-columns: 1fr; }
    .resource-row{ grid-template-columns: 1fr; gap: 8px; }
    .resource-row .r-val{ text-align: left; }
    .topbar{ flex-direction: column; align-items: flex-start; gap: 12px; }
    footer{ flex-direction: column; gap: 6px; }
  }

  @media (prefers-reduced-motion: reduce){
    .status-pill .pulse{ animation: none; }
  }
</style>
</head>
<body>

<div class="wrap">
  <header>
    <div class="topbar">
      <div class="brand">
        <span class="dot"></span>  <span class="host">/ <?php echo htmlspecialchars($host_cluster); ?></span>
      </div>
      <div class="status-pill"><span class="pulse"></span> cluster operacional</div>
    </div>

    <div class="hero">
      <h1>Do bare metal ao pod: um cluster único sustentando dev, teste e produção.</h1>
      <p class="lede">Infraestrutura própria sobre Proxmox, orquestrada com Kubernetes e segmentada por VLAN, pronta para times trabalharem em paralelo sem pisar uns nos outros.</p>

      <div class="stat-row">
        <div class="stat">
          <div class="value">2<small>×</small> Xeon Gold 6230</div>
          <div class="label">80 threads totais @ 2.10GHz</div>
        </div>
        <div class="stat">
          <div class="value">512<small>GB</small></div>
          <div class="label">memória disponível no host</div>
        </div>
        <div class="stat">
          <div class="value">9<small>TB</small></div>
          <div class="label">armazenamento livre para volumes</div>
        </div>
      </div>
    </div>
  </header>

  <section id="topologia">
    <div class="section-head">
      <h2>Topologia</h2>
      <span class="note">rede segmentada via Active Directory</span>
    </div>
    <div class="topology">
      <div class="topo-node">
        <div class="t-label">VLANs / AD</div>
        <div class="t-sub">controle de acesso</div>
      </div>
      <div class="topo-arrow"></div>
      <div class="topo-node">
        <div class="t-label">Proxmox</div>
        <div class="t-sub">hipervisor</div>
      </div>
      <div class="topo-arrow"></div>
      <div class="topo-node">
        <div class="t-label">Kubernetes</div>
        <div class="t-sub">orquestração</div>
      </div>
      <div class="topo-arrow"></div>
      <div class="topo-branch">
        <div class="topo-node" style="border-left:3px solid var(--accent-dev)">
          <div class="t-label">dev</div>
        </div>
        <div class="topo-node" style="border-left:3px solid var(--accent-test)">
          <div class="t-label">teste</div>
        </div>
        <div class="topo-node" style="border-left:3px solid var(--accent-prod)">
          <div class="t-label">produção</div>
        </div>
      </div>
    </div>
  </section>

  <section id="ambientes">
    <div class="section-head">
      <h2>Ambientes</h2>
      <span class="note">isolados por namespace e VLAN</span>
    </div>
    <div class="env-grid">
      <div class="env-card dev">
        <div class="env-name">Desenvolvimento</div>
        <span class="env-tag">namespace: dev</span>
        <p>Espaço de iteração livre para os times. Deploys frequentes, recursos compartilhados entre monólitos, microsserviços e pipelines de dados em construção.</p>
        <div class="env-meta">
          <span>acesso — todos os devs</span>
          <span>estabilidade — baixa, por design</span>
        </div>
      </div>
      <div class="env-card test">
        <div class="env-name">Teste</div>
        <span class="env-tag">namespace: staging</span>
        <p>Réplica próxima da produção para validar integrações, migrações e pipelines antes da promoção. Ponto de checagem antes de qualquer release.</p>
        <div class="env-meta">
          <span>acesso — times + QA</span>
          <span>estabilidade — média</span>
        </div>
      </div>
      <div class="env-card prod">
        <div class="env-name">Produção</div>
        <span class="env-tag">namespace: prod</span>
        <p>Cargas reais: apps monolíticas, microsserviços e pipelines de dados em execução contínua. Alterações passam por teste antes de chegar aqui.</p>
        <div class="env-meta">
          <span>acesso — restrito</span>
          <span>estabilidade — alta, sem exceções</span>
        </div>
      </div>
    </div>
  </section>

  <section id="recursos">
    <div class="section-head">
      <h2>Capacidade do host</h2>
      <span class="note">alocação de referência</span>
    </div>
    <div class="resource-panel">
      <div class="resource-row">
        <div class="r-name">CPU<span>2× Xeon Gold 6230, 2 sockets</span></div>
        <div class="bar-track"><div class="bar-fill" style="width: 38%"></div></div>
        <div class="r-val">80 threads</div>
      </div>
      <div class="resource-row">
        <div class="r-name">Memória<span>alocação entre VMs e nós K8s</span></div>
        <div class="bar-track"><div class="bar-fill" style="width: 52%"></div></div>
        <div class="r-val">512 GB</div>
      </div>
      <div class="resource-row">
        <div class="r-name">Armazenamento<span>volumes persistentes + imagens</span></div>
        <div class="bar-track"><div class="bar-fill" style="width: 24%"></div></div>
        <div class="r-val">9 TB livres</div>
      </div>
    </div>
  </section>

  <section id="notas">
    <div class="section-head">
      <h2>Notas de operação</h2>
    </div>
    <div class="notes">
      <div class="note-card">
        <h3>Uso simultâneo</h3>
        <p>Vários desenvolvedores acessam o cluster ao mesmo tempo — namespaces e cotas de recurso por ambiente evitam que um time consuma a capacidade de outro.</p>
      </div>
      <div class="note-card">
        <h3>Cargas suportadas</h3>
        <p>O ambiente foi desenhado para três perfis de carga: aplicações monolíticas, microsserviços e pipelines de dados, convivendo no mesmo cluster.</p>
      </div>
    </div>
  </section>

  <footer>
    <span>atualizado em <?php echo $ultima_atualizacao; ?></span>
    <span>proxmox → kubernetes → dev / teste / produção</span>
  </footer>
</div>

</body>
</html>
