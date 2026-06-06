/**
 * Admin Dashboard - JavaScript
 */
const API_BASE = window.location.hostname.includes('github.io') ? 'https://alokcomments.fwh.is/api' : '../api';
let currentTab = 'all';
let allComments = [];

async function init() {
  await loadStats();
  await loadComments('all');
  await loadLogs();
}

// ===== STATS =====
async function loadStats() {
  try {
    const res = await fetch(`${API_BASE}/comments.php?stats=1`);
    const data = await res.json();
    if (data.success) {
      const s = data.data;
      document.getElementById('statsGrid').innerHTML = `
        <div class="stat-card"><div class="icon">💬</div><div class="value">${s.total}</div><div class="label">Total Comments</div></div>
        <div class="stat-card"><div class="icon">✅</div><div class="value">${s.active}</div><div class="label">Active</div></div>
        <div class="stat-card"><div class="icon">👁️</div><div class="value">${s.hidden}</div><div class="label">Hidden</div></div>
        <div class="stat-card"><div class="icon">🗑️</div><div class="value">${s.removed}</div><div class="label">Removed</div></div>
        <div class="stat-card"><div class="icon">👍</div><div class="value">${s.totalLikes}</div><div class="label">Total Likes</div></div>
        <div class="stat-card"><div class="icon">👎</div><div class="value">${s.totalDislikes}</div><div class="label">Total Dislikes</div></div>
        <div class="stat-card"><div class="icon">🛡️</div><div class="value">${s.moderationActions}</div><div class="label">Mod Actions</div></div>
        <div class="stat-card"><div class="icon">🌍</div><div class="value">${s.languages}</div><div class="label">Languages</div></div>`;
    }
  } catch (e) {
    document.getElementById('statsGrid').innerHTML = '<p style="color:var(--red);grid-column:1/-1;text-align:center">Failed to load stats</p>';
  }
}

// ===== TAB SWITCHING =====
function switchTab(tab) {
  currentTab = tab;
  document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
  document.querySelector(`[data-tab="${tab}"]`).classList.add('active');
  document.getElementById('commentsSection').style.display = tab === 'logs' ? 'none' : 'block';
  document.getElementById('logsSection').style.display = tab === 'logs' ? 'block' : 'none';
  if (tab !== 'logs') loadComments(tab);
}

// ===== LOAD COMMENTS =====
async function loadComments(filter) {
  const list = document.getElementById('commentsList');
  list.innerHTML = '<div class="loading-admin"><div class="spinner" style="width:30px;height:30px;border:3px solid var(--border);border-top-color:var(--accent);border-radius:50%;animation:spin 0.8s linear infinite;margin:0 auto 12px"></div>Loading...</div>';
  
  try {
    let url = `${API_BASE}/comments.php?include_hidden=true&limit=50`;
    if (filter !== 'all') url = `${API_BASE}/comments.php?status=${filter}`;
    const res = await fetch(url);
    const data = await res.json();
    const comments = filter !== 'all' ? data.data : (data.data.comments || data.data);
    allComments = comments;
    renderComments(comments);
  } catch (e) {
    if (window.location.hostname.includes('github.io')) {
      list.innerHTML = `
        <div class="empty-admin">
          <div class="emoji">⚠️</div>
          <p>Failed to load comments from API.</p>
          <p style="font-size:0.8rem;opacity:0.8;margin-top:10px;">
            GitHub Pages doesn't support PHP. Open live admin:<br>
            <a href="http://alokcomments.fwh.is/admin/index.html" target="_blank" style="color:#06D6A0;text-decoration:underline;font-weight:700">alokcomments.fwh.is/admin/index.html</a>
          </p>
        </div>`;
    } else {
      list.innerHTML = '<div class="empty-admin"><div class="emoji">⚠️</div><p>Failed to load comments</p></div>';
    }
  }
}

function renderComments(comments) {
  const list = document.getElementById('commentsList');
  if (!comments || !comments.length) {
    list.innerHTML = '<div class="empty-admin"><div class="emoji">📭</div><p>No comments found</p></div>';
    return;
  }

  list.innerHTML = comments.map(c => {
    const initials = c.username.split(' ').map(w=>w[0]).join('').toUpperCase().substring(0,2);
    const loc = [c.city, c.country].filter(Boolean).join(', ');
    const statusClass = c.status || 'active';
    const actions = getActions(c);
    return `
      <div class="admin-comment" id="admin-${c.comment_id}">
        <div class="avatar" style="background:${c.avatar_color||'#6C63FF'}">${initials}</div>
        <div class="content">
          <div class="meta">
            <span class="name">${escapeHtml(c.username)}</span>
            <span class="status-badge ${statusClass}">${statusClass}</span>
            ${loc?`<span class="location">📍 ${escapeHtml(loc)}</span>`:''}
            <span class="time">${new Date(c.created_at).toLocaleString()}</span>
          </div>
          <div class="text">${escapeHtml(c.comment_text)}</div>
          <div class="stats">
            <span>👍 ${c.like_count||0}</span>
            <span>👎 ${c.dislike_count||0}</span>
            <span>🌐 ${c.language_code}</span>
            <span>#${c.comment_id}</span>
          </div>
        </div>
        <div class="admin-actions">${actions}</div>
      </div>`;
  }).join('');
}

function getActions(c) {
  let btns = '';
  if (c.status !== 'removed') btns += `<button class="admin-btn danger" onclick="moderateComment(${c.comment_id},'remove')">Remove</button>`;
  if (c.status === 'active') btns += `<button class="admin-btn" onclick="moderateComment(${c.comment_id},'hide')">Hide</button>`;
  if (c.status !== 'active') btns += `<button class="admin-btn restore" onclick="moderateComment(${c.comment_id},'restore')">Restore</button>`;
  return btns;
}

// ===== MODERATION ACTION =====
async function moderateComment(id, action) {
  try {
    const res = await fetch(`${API_BASE}/moderation.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ comment_id: id, action: action, reason: `Admin ${action} action` })
    });
    const data = await res.json();
    if (data.success) {
      loadStats();
      loadComments(currentTab === 'logs' ? 'all' : currentTab);
      loadLogs();
    }
  } catch (e) { alert('Action failed'); }
}

// ===== LOGS =====
async function loadLogs() {
  const list = document.getElementById('logsList');
  try {
    const res = await fetch(`${API_BASE}/moderation.php?limit=50`);
    const data = await res.json();
    if (data.success && data.data.length) {
      const icons = { hidden: '👁️', removed: '🗑️', restored: '♻️', spam_blocked: '🚫', char_blocked: '⛔' };
      list.innerHTML = data.data.map(log => `
        <div class="log-entry">
          <span class="icon">${icons[log.action]||'📋'}</span>
          <div class="details">
            <div><span class="action-type">${log.action}</span> — Comment #${log.comment_id} ${log.username ? 'by ' + escapeHtml(log.username) : ''}</div>
            <div class="reason">${escapeHtml(log.reason||'')} • by ${log.performed_by}</div>
          </div>
          <span class="timestamp">${new Date(log.created_at).toLocaleString()}</span>
        </div>`).join('');
    } else {
      list.innerHTML = '<div class="empty-admin"><div class="emoji">📋</div><p>No moderation logs yet</p></div>';
    }
  } catch (e) {
    list.innerHTML = '<div class="empty-admin"><div class="emoji">⚠️</div><p>Failed to load logs</p></div>';
  }
}

// ===== SEARCH =====
function searchComments(query) {
  if (!query.trim()) { renderComments(allComments); return; }
  const q = query.toLowerCase();
  const filtered = allComments.filter(c =>
    c.comment_text.toLowerCase().includes(q) ||
    c.username.toLowerCase().includes(q) ||
    (c.city||'').toLowerCase().includes(q)
  );
  renderComments(filtered);
}

function escapeHtml(str) {
  if (!str) return '';
  const d = document.createElement('div');
  d.textContent = str;
  return d.innerHTML;
}

// Init on load
document.addEventListener('DOMContentLoaded', init);
