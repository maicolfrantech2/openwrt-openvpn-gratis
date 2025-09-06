<?php
session_start();
if (!isset($_SESSION['uid'])) { header('Location: index.php'); exit; } // [18]
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Reporte técnico</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body class="dark">
<header class="topbar">
  <div class="brand">Soporte | Maicol</div>
  <div class="spacer"></div>
  <div class="user">👤 <?php echo htmlspecialchars($_SESSION['username']); ?></div>
  <form action="logout.php" method="post"><button class="btn-ghost">Salir</button></form>
</header>

<main class="container">
  <div class="actions">
    <button id="btn-nuevo" class="btn-accent">Añadir nuevo problema</button>
  </div>
  <div id="lista"></div>
</main>

<!-- Modal -->
<div id="modal" class="modal hidden" aria-hidden="true" role="dialog">
  <div class="modal-card">
    <h2>Nuevo problema</h2>
    <form id="form-nuevo" class="form">
      <label>Cliente</label>
      <input name="cliente" required maxlength="120">
      <label>Problema</label>
      <textarea name="descripcion" required rows="4"></textarea>
      <div class="modal-actions">
        <button type="button" class="btn-ghost" id="btn-cerrar">Cancelar</button>
        <button type="submit" class="btn-primary">Guardar</button>
      </div>
    </form>
  </div>
</div>

<script>
(() => {
  const d = document;
  const $lista = d.getElementById('lista');
  const $modal = d.getElementById('modal');
  const $btnNuevo = d.getElementById('btn-nuevo');
  const $btnCerrar = d.getElementById('btn-cerrar');
  const $formNuevo = d.getElementById('form-nuevo');

  const estados = {
    EN_REVISION: { label: 'En revisión', cls: 'rev' },
    EN_DIALOGO: { label: 'En diálogo', cls: 'dia' },
    RESUELTO: { label: 'Resuelto', cls: 'res' }
  };

  const api = (action, opts = {}) =>
    fetch(`api.php?action=${action}`, opts)
      .then(r => {
        if (!r.ok) throw new Error('Error ' + r.status);
        const ct = r.headers.get('content-type') || '';
        return ct.includes('application/json') ? r.json() : null;
      });

  const badge = (estado) => {
    const e = estados[estado] || estados.EN_REVISION;
    return `<span class="badge ${e.cls}">${e.label}</span>`;
  };

  const estadoSelect = (id, current) => {
    const opts = Object.keys(estados).map(k =>
      `<option value="${k}" ${k===current?'selected':''}>${estados[k].label}</option>`
    ).join('');
    return `<select data-id="${id}" class="estado-select">${opts}</select>`;
  };

  const card = (row) => `
    <div class="card">
      <div style="display:flex;align-items:center;gap:10px;justify-content:space-between;">
        <div style="display:flex;flex-direction:column;gap:4px;max-width:70%;">
          <strong>${escapeHtml(row.cliente)}</strong>
          <small style="color:var(--muted)">${new Date(row.creado_en).toLocaleString()}</small>
        </div>
        <div>${badge(row.estado)}</div>
      </div>
      <div style="color:#cbd5e1">${escapeHtml(row.descripcion)}</div>
      <div style="display:flex;gap:10px;align-items:center;">
        <span style="color:var(--muted);font-size:12px;">Estado:</span>
        ${estadoSelect(row.id, row.estado)}
        <button class="btn-ghost btn-editar" data-id="${row.id}">Editar</button>
        <button class="btn-danger btn-eliminar" data-id="${row.id}">Eliminar</button>
      </div>
    </div>
  `;

  function escapeHtml(s='') {
    return s.replace(/[&<>\"]'/g, (c)=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c]));
  }

  async function cargar() {
    $lista.innerHTML = '<div class="card">Cargando…</div>';
    try {
      const data = await api('list');
      if (!Array.isArray(data) || !data.length) {
        $lista.innerHTML = '<div class="card">Sin registros</div>';
        return;
      }
      $lista.innerHTML = `<div class="list">${data.map(card).join('')}</div>`;
    } catch (e) {
      $lista.innerHTML = `<div class="card">Error al cargar</div>`;
    }
  }

  function abrirModal() {
    $modal.classList.add('show');
    $modal.classList.remove('hidden');
    $modal.setAttribute('aria-hidden', 'false');
    $modal.querySelector('input[name="cliente"]').focus();
  }
  function cerrarModal() {
    $modal.classList.remove('show');
    $modal.classList.add('hidden');
    $modal.setAttribute('aria-hidden', 'true');
    $formNuevo.reset();
  }

  $btnNuevo.addEventListener('click', abrirModal);
  $btnCerrar.addEventListener('click', cerrarModal);
  $modal.addEventListener('click', (e)=> { if (e.target === $modal) cerrarModal(); });

  $formNuevo.addEventListener('submit', async (e) => {
    e.preventDefault();
    const fd = new FormData($formNuevo);
    const cliente = (fd.get('cliente')||'').toString().trim();
    const descripcion = (fd.get('descripcion')||'').toString().trim();
    if (!cliente || !descripcion) return;

    try {
      await api('create', { method:'POST', body: fd });
      cerrarModal();
      cargar();
    } catch (e) {
      alert('Error al guardar');
    }
  });

  d.addEventListener('click', async (e) => {
    const el = e.target;

    if (el.classList.contains('btn-eliminar')) {
      const id = parseInt(el.getAttribute('data-id'), 10);
      if (!confirm('¿Seguro que quieres eliminar este registro?')) return;
      try {
        await api('delete', { method: 'POST', body: new URLSearchParams({ id }) });
        cargar();
      } catch (err) {
        alert('No se pudo eliminar el registro');
      }
    }

    if (el.classList.contains('btn-editar')) {
      const id = parseInt(el.getAttribute('data-id'), 10);
      // Aquí podrías abrir un modal con los datos actuales y permitir la edición
      alert('Función de edición aún no implementada');
    }
  });

  d.addEventListener('change', async (e) => {
    const el = e.target;
    if (el.classList.contains('estado-select')) {
      const id = parseInt(el.getAttribute('data-id'), 10);
      const estado = el.value;
      const fd = new FormData();
      fd.append('id', id);
      fd.append('estado', estado);
      el.disabled = true;
      try {
        await api('update_status', { method:'POST', body: fd });
        // actualizar badge de su card
        const cont = el.closest('.card');
        cont.querySelector('.badge').outerHTML = badge(estado);
      } catch (err) {
        alert('No se pudo actualizar el estado');
      } finally {
        el.disabled = false;
      }
    }
  });

  cargar();
})();
</script>
</body>
</html>