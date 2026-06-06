/**
 * Multilingual Comment System - Production Ready
 * Features: Translate, Like/Dislike, City Display, Auto-moderation, Special Char Blocking
 */

const API_BASE = window.location.hostname.includes('github.io') ? 'https://alokcomments.fwh.is/api' : 'api';
const LANGUAGES = {
  en:'English', hi:'Hindi', es:'Spanish', fr:'French', de:'German',
  ar:'Arabic', zh:'Chinese', ja:'Japanese', ko:'Korean', pt:'Portuguese',
  ru:'Russian', it:'Italian', tr:'Turkish', nl:'Dutch', sv:'Swedish',
  pl:'Polish', th:'Thai', vi:'Vietnamese', id:'Indonesian', ms:'Malay'
};
const AVATAR_COLORS = ['#6C63FF','#FF6B6B','#4ECDC4','#FFE66D','#95E1D3','#F38181','#AA96DA','#FCBAD3','#A8D8EA','#DCD6F7','#FF9A9E','#A18CD1'];

class CommentApp {
  constructor() {
    this.user = this.loadUser();
    this.comments = [];
    this.currentPage = 1;
    this.totalPages = 1;
    this.translationLang = 'en';
    this.init();
  }

  loadUser() {
    const saved = localStorage.getItem('commentUser');
    return saved ? JSON.parse(saved) : null;
  }

  saveUser(user) {
    this.user = user;
    localStorage.setItem('commentUser', JSON.stringify(user));
  }

  async init() {
    this.createToastContainer();
    if (!this.user) {
      this.showUserSetup();
    } else {
      this.translationLang = this.user.language_code || 'en';
      this.renderApp();
      await this.fetchComments();
    }
  }

  // ===== USER SETUP MODAL =====
  showUserSetup() {
    document.getElementById('app').innerHTML = '';
    const overlay = document.createElement('div');
    overlay.className = 'modal-overlay';
    overlay.id = 'setupModal';

    const colorDots = AVATAR_COLORS.map((c, i) =>
      `<div class="color-dot ${i===0?'active':''}" data-color="${c}" style="background:${c}" onclick="app.selectColor(this)"></div>`
    ).join('');

    const langOpts = Object.entries(LANGUAGES).map(([code, name]) =>
      `<option value="${code}">${name}</option>`
    ).join('');

    overlay.innerHTML = `
      <div class="modal">
        <h2>👋 Welcome!</h2>
        <p>Set up your profile to start commenting in any language.</p>
        <div class="form-group">
          <label>Your Name</label>
          <input type="text" id="setupName" placeholder="Enter your name" maxlength="50" autocomplete="name">
        </div>
        <div class="form-group">
          <label>Your City</label>
          <input type="text" id="setupCity" placeholder="Detecting your city..." maxlength="50">
        </div>
        <div class="form-group">
          <label>Country</label>
          <input type="text" id="setupCountry" placeholder="Detecting..." maxlength="50">
        </div>
        <div class="form-group">
          <label>Your Language</label>
          <select id="setupLang">${langOpts}</select>
        </div>
        <div class="form-group">
          <label>Avatar Color</label>
          <div class="color-picker-row">${colorDots}</div>
        </div>
        <button class="btn-save" onclick="app.completeSetup()">Start Commenting →</button>
      </div>`;
    document.body.appendChild(overlay);
    this.detectLocation();
  }

  selectColor(el) {
    document.querySelectorAll('.color-dot').forEach(d => d.classList.remove('active'));
    el.classList.add('active');
  }

  async detectLocation() {
    try {
      const res = await fetch(`${API_BASE}/location.php`);
      const data = await res.json();
      if (data.success && data.data) {
        const cityEl = document.getElementById('setupCity');
        const countryEl = document.getElementById('setupCountry');
        if (cityEl && !cityEl.value && data.data.city !== 'Unknown') cityEl.value = data.data.city;
        if (countryEl && !countryEl.value && data.data.country !== 'Unknown') countryEl.value = data.data.country;
        if (cityEl) cityEl.placeholder = 'Enter your city';
        if (countryEl) countryEl.placeholder = 'Enter your country';
      }
    } catch (e) {
      const cityEl = document.getElementById('setupCity');
      if (cityEl) cityEl.placeholder = 'Enter your city';
    }
  }

  completeSetup() {
    const name = document.getElementById('setupName').value.trim();
    const city = document.getElementById('setupCity').value.trim();
    const country = document.getElementById('setupCountry').value.trim();
    const lang = document.getElementById('setupLang').value;
    const color = document.querySelector('.color-dot.active')?.dataset.color || '#6C63FF';

    if (!name) { this.toast('Please enter your name!', 'error'); return; }
    if (name.length < 2) { this.toast('Name must be at least 2 characters', 'error'); return; }

    this.saveUser({
      user_id: 'user_' + Date.now() + '_' + Math.random().toString(36).substr(2,8),
      username: name,
      city: city || 'Unknown',
      country: country || 'Unknown',
      language_code: lang,
      avatar_color: color
    });

    this.translationLang = lang;
    document.getElementById('setupModal')?.remove();
    this.renderApp();
    this.fetchComments();
    this.toast(`Welcome, ${name}! 🎉`, 'success');
  }

  // ===== RENDER MAIN APP =====
  renderApp() {
    const u = this.user;
    const initials = u.username.split(' ').map(w => w[0]).join('').toUpperCase().substring(0,2);
    const locationStr = [u.city, u.country].filter(v => v && v !== 'Unknown').join(', ') || 'Location not set';

    const langOpts = Object.entries(LANGUAGES).map(([code, name]) =>
      `<option value="${code}" ${code===this.translationLang?'selected':''}>${name}</option>`
    ).join('');

    const composerLangOpts = Object.entries(LANGUAGES).map(([code, name]) =>
      `<option value="${code}" ${code===u.language_code?'selected':''}>${name}</option>`
    ).join('');

    document.getElementById('app').innerHTML = `
      <header class="app-header">
        <div class="header-inner">
          <div class="logo">
            <div class="logo-icon">💬</div>
            <span>Comments</span>
          </div>
          <div class="lang-selector">
            <select id="globalLang" onchange="app.changeTranslationLang(this.value)" title="Translate comments to">${langOpts}</select>
          </div>
        </div>
      </header>
      <div class="container">
        <div class="user-bar">
          <div class="avatar" style="background:${u.avatar_color}">${initials}</div>
          <div class="user-info">
            <div class="user-name">${this.esc(u.username)}</div>
            <div class="user-location">📍 ${this.esc(locationStr)}</div>
          </div>
          <button class="edit-btn" onclick="app.editProfile()">Edit</button>
        </div>

        <div class="composer">
          <div class="composer-header">
            <h3>💬 Write a Comment</h3>
            <span class="char-counter" id="charCounter">0 / 500</span>
          </div>
          <textarea id="commentInput" placeholder="Share your thoughts in any language..." maxlength="500" oninput="app.updateCharCount()"></textarea>
          <div class="composer-footer">
            <div class="composer-lang">
              <span>Language:</span>
              <select id="commentLang">${composerLangOpts}</select>
            </div>
            <button class="btn-post" id="postBtn" onclick="app.postComment()">
              <span>Post Comment</span> ✨
            </button>
          </div>
        </div>

        <div class="stats-bar" id="statsBar"></div>
        <div id="commentsList"></div>
        <div id="pagination"></div>
      </div>
      <footer class="app-footer">
        Multilingual Comment System v1.0 · <a href="admin/index.html">Admin Dashboard</a>
      </footer>`;
  }

  createToastContainer() {
    if (document.getElementById('toastContainer')) return;
    const c = document.createElement('div');
    c.className = 'toast-container';
    c.id = 'toastContainer';
    document.body.appendChild(c);
  }

  // ===== FETCH & RENDER COMMENTS =====
  async fetchComments() {
    const list = document.getElementById('commentsList');
    if (!list) return;
    list.innerHTML = '<div class="loading-spinner"><div class="spinner"></div><span>Loading comments...</span></div>';

    try {
      const res = await fetch(`${API_BASE}/comments.php?page=${this.currentPage}&limit=20&user_id=${this.user.user_id}`);
      const data = await res.json();
      if (data.success) {
        this.comments = data.data.comments || [];
        this.totalPages = data.data.totalPages || 1;
        this.renderStats(data.data.total || 0);
        this.renderComments();
        this.renderPagination();
      } else {
        list.innerHTML = '<div class="empty-state"><div class="emoji">⚠️</div><p>Failed to load comments.</p></div>';
      }
    } catch (e) {
      if (window.location.hostname.includes('github.io')) {
        list.innerHTML = `
          <div class="empty-state">
            <div class="emoji">⚠️</div>
            <p>Cannot connect to live backend API.</p>
            <p style="font-size:0.8rem;opacity:0.8;max-width:320px;margin:12px auto;line-height:1.6">
              GitHub Pages does not support PHP backends. Please open the app directly on InfinityFree:<br>
              <a href="http://alokcomments.fwh.is/index.html" target="_blank" style="color:#06D6A0;text-decoration:underline;font-weight:700">alokcomments.fwh.is/index.html</a>
            </p>
          </div>`;
      } else {
        list.innerHTML = '<div class="empty-state"><div class="emoji">⚠️</div><p>Cannot connect to server. Make sure XAMPP is running.</p></div>';
      }
    }
  }

  renderStats(total) {
    const bar = document.getElementById('statsBar');
    if (!bar) return;
    const langs = new Set(this.comments.map(c => c.language_code)).size;
    bar.innerHTML = `
      <div class="stat-pill"><span class="count">${total}</span> Comments</div>
      <div class="stat-pill">🌍 <span class="count">${langs}</span> Languages</div>`;
  }

  renderComments() {
    const list = document.getElementById('commentsList');
    if (!list) return;
    if (!this.comments.length) {
      list.innerHTML = '<div class="empty-state"><div class="emoji">💬</div><p>No comments yet. Be the first to comment!</p></div>';
      return;
    }
    list.innerHTML = this.comments.map((c, i) => this.buildCard(c, i)).join('');
  }

  buildCard(c, idx) {
    const initials = c.username.split(' ').map(w => w[0]).join('').toUpperCase().substring(0,2);
    const loc = [c.city, c.country].filter(v => v && v !== '').join(', ');
    const time = this.timeAgo(c.created_at);
    const liked = c.user_reaction?.liked || false;
    const disliked = c.user_reaction?.disliked || false;
    const lang = LANGUAGES[c.language_code] || c.language_code;

    const langOptions = Object.entries(LANGUAGES).filter(([code]) => code !== c.language_code).map(([code, name]) =>
      `<option value="${code}" ${code===this.translationLang?'selected':''}>${name}</option>`
    ).join('');

    return `
    <div class="comment-card" style="animation-delay:${idx*0.05}s" id="comment-${c.comment_id}">
      <div class="comment-header">
        <div class="comment-avatar" style="background:${c.avatar_color||'#6C63FF'}">${initials}</div>
        <div class="comment-meta">
          <div class="comment-username">${this.esc(c.username)}</div>
          ${loc ? `<div class="comment-location">📍 ${this.esc(loc)}</div>` : ''}
        </div>
        <div class="comment-time">${time}</div>
      </div>
      <div class="comment-body" id="body-${c.comment_id}">${this.esc(c.comment_text)}</div>
      <div class="comment-translation" id="translation-${c.comment_id}">
        <div class="label">Translated</div>
        <div class="text" id="transText-${c.comment_id}"></div>
      </div>
      <div class="translate-picker" id="picker-${c.comment_id}" style="display:none">
        <select id="pickLang-${c.comment_id}" class="translate-select">${langOptions}</select>
        <button class="btn-translate-go" onclick="app.doTranslate(${c.comment_id})">Translate →</button>
      </div>
      <div class="comment-actions">
        <button class="action-btn translate-btn" id="trBtn-${c.comment_id}" onclick="app.toggleTranslatePicker(${c.comment_id})">
          <span class="emoji">🌐</span> Translate
        </button>
        <div class="action-separator"></div>
        <button class="action-btn like-btn ${liked?'active':''}" onclick="app.like(${c.comment_id})">
          <span class="emoji">👍</span> <span id="lc-${c.comment_id}">${c.like_count||0}</span>
        </button>
        <button class="action-btn dislike-btn ${disliked?'active':''}" onclick="app.dislike(${c.comment_id})">
          <span class="emoji">👎</span> <span id="dc-${c.comment_id}">${c.dislike_count||0}</span>
        </button>
        <div class="action-separator"></div>
        <span class="action-btn" style="cursor:default;opacity:0.5;font-size:0.72rem">${lang}</span>
      </div>
    </div>`;
  }

  // ===== POST COMMENT =====
  async postComment() {
    const input = document.getElementById('commentInput');
    const text = input.value.trim();
    const lang = document.getElementById('commentLang').value;
    const btn = document.getElementById('postBtn');

    if (!text) { this.toast('Comment cannot be empty!', 'warning'); return; }
    if (text.length > 500) { this.toast('Comment exceeds 500 character limit', 'error'); return; }

    btn.disabled = true;
    btn.innerHTML = '<div class="spinner" style="width:16px;height:16px;border-width:2px"></div> Posting...';

    try {
      const res = await fetch(`${API_BASE}/comments.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          user_id: this.user.user_id,
          username: this.user.username,
          avatar_color: this.user.avatar_color,
          city: this.user.city,
          country: this.user.country,
          language_code: lang,
          comment_text: text
        })
      });
      const data = await res.json();
      if (data.success) {
        input.value = '';
        this.updateCharCount();
        this.toast('Comment posted successfully! 🎉', 'success');
        this.currentPage = 1;
        await this.fetchComments();
      } else {
        this.toast(data.error || 'Failed to post comment', data.moderated ? 'warning' : 'error');
      }
    } catch (e) {
      this.toast('Network error. Check your server.', 'error');
    }
    btn.disabled = false;
    btn.innerHTML = '<span>Post Comment</span> ✨';
  }

  // ===== TRANSLATE =====
  toggleTranslatePicker(id) {
    const el = document.getElementById(`translation-${id}`);
    const picker = document.getElementById(`picker-${id}`);
    const btn = document.getElementById(`trBtn-${id}`);
    if (!picker || !btn) return;

    // If translation is showing, toggle back to original
    if (el && el.classList.contains('show')) {
      el.classList.remove('show');
      btn.classList.remove('active');
      btn.innerHTML = '<span class="emoji">🌐</span> Translate';
      picker.style.display = 'none';
      return;
    }

    // Toggle the language picker
    const isVisible = picker.style.display !== 'none';
    // Hide all other pickers first
    document.querySelectorAll('.translate-picker').forEach(p => p.style.display = 'none');
    picker.style.display = isVisible ? 'none' : 'flex';
  }

  async doTranslate(id) {
    const picker = document.getElementById(`picker-${id}`);
    const el = document.getElementById(`translation-${id}`);
    const btn = document.getElementById(`trBtn-${id}`);
    const targetLang = document.getElementById(`pickLang-${id}`).value;
    if (!el || !btn) return;

    picker.style.display = 'none';
    btn.innerHTML = '<div class="spinner" style="width:14px;height:14px;border-width:2px"></div> Translating...';
    btn.disabled = true;

    try {
      const res = await fetch(`${API_BASE}/translate.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ comment_id: id, target_lang: targetLang })
      });
      const data = await res.json();
      if (data.success) {
        const targetName = LANGUAGES[data.data.target_lang] || data.data.target_lang;
        document.getElementById(`transText-${id}`).textContent = data.data.translated_text;
        el.querySelector('.label').textContent = `🌐 Translated to ${targetName}`;
        el.classList.add('show');
        btn.classList.add('active');
        btn.innerHTML = '<span class="emoji">🌐</span> Original';
      } else {
        this.toast(data.error || 'Translation failed', 'error');
        btn.innerHTML = '<span class="emoji">🌐</span> Translate';
      }
    } catch (e) {
      this.toast('Translation service unavailable', 'error');
      btn.innerHTML = '<span class="emoji">🌐</span> Translate';
    }
    btn.disabled = false;
  }

  // ===== LIKE =====
  async like(id) {
    try {
      const res = await fetch(`${API_BASE}/like.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ comment_id: id, user_id: this.user.user_id })
      });
      const data = await res.json();
      if (data.success) {
        document.getElementById(`lc-${id}`).textContent = data.data.like_count;
        document.getElementById(`dc-${id}`).textContent = data.data.dislike_count;
        const card = document.getElementById(`comment-${id}`);
        card.querySelector('.like-btn').classList.toggle('active', data.data.liked);
        card.querySelector('.dislike-btn').classList.remove('active');
      }
    } catch (e) { this.toast('Error processing like', 'error'); }
  }

  // ===== DISLIKE =====
  async dislike(id) {
    try {
      const res = await fetch(`${API_BASE}/dislike.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ comment_id: id, user_id: this.user.user_id })
      });
      const data = await res.json();
      if (data.success) {
        document.getElementById(`dc-${id}`).textContent = data.data.dislike_count;
        document.getElementById(`lc-${id}`).textContent = data.data.like_count;
        const card = document.getElementById(`comment-${id}`);
        card.querySelector('.dislike-btn').classList.toggle('active', data.data.disliked);
        card.querySelector('.like-btn').classList.remove('active');

        if (data.data.auto_hidden) {
          this.toast('⚠️ Comment auto-removed: received 2 dislikes', 'warning');
          card.style.transition = 'all 0.5s ease';
          card.style.opacity = '0.3';
          card.style.transform = 'scale(0.95)';
          setTimeout(() => this.fetchComments(), 1500);
        }
      }
    } catch (e) { this.toast('Error processing dislike', 'error'); }
  }

  // ===== PAGINATION =====
  renderPagination() {
    const el = document.getElementById('pagination');
    if (!el || this.totalPages <= 1) { if(el) el.innerHTML=''; return; }
    let btns = '';
    for (let i = 1; i <= this.totalPages; i++) {
      btns += `<button class="action-btn ${i===this.currentPage?'active':''}" style="min-width:36px;justify-content:center" onclick="app.goPage(${i})">${i}</button>`;
    }
    el.innerHTML = `<div style="display:flex;justify-content:center;gap:8px;margin-top:24px">${btns}</div>`;
  }

  goPage(p) { this.currentPage = p; this.fetchComments(); window.scrollTo({top:0,behavior:'smooth'}); }

  // ===== UTILS =====
  changeTranslationLang(lang) {
    this.translationLang = lang;
    document.querySelectorAll('.comment-translation.show').forEach(el => el.classList.remove('show'));
    document.querySelectorAll('.translate-btn.active').forEach(el => {
      el.classList.remove('active');
      el.innerHTML = '<span class="emoji">🌐</span> Translate';
    });
    this.toast(`Translation language set to ${LANGUAGES[lang]}`, 'info');
  }

  updateCharCount() {
    const input = document.getElementById('commentInput');
    const counter = document.getElementById('charCounter');
    if (!input||!counter) return;
    const len = input.value.length;
    counter.textContent = `${len} / 500`;
    counter.className = 'char-counter' + (len>450?' danger':len>350?' warning':'');
  }

  editProfile() {
    localStorage.removeItem('commentUser');
    this.user = null;
    document.getElementById('app').innerHTML = '';
    this.showUserSetup();
  }

  esc(str) {
    if (!str) return '';
    const d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
  }

  timeAgo(dateStr) {
    const diff = Math.floor((new Date() - new Date(dateStr)) / 1000);
    if (diff < 60) return 'just now';
    if (diff < 3600) return `${Math.floor(diff/60)}m ago`;
    if (diff < 86400) return `${Math.floor(diff/3600)}h ago`;
    if (diff < 604800) return `${Math.floor(diff/86400)}d ago`;
    return new Date(dateStr).toLocaleDateString();
  }

  toast(msg, type='info') {
    const container = document.getElementById('toastContainer');
    if (!container) return;
    const icons = {success:'✅', error:'❌', warning:'⚠️', info:'ℹ️'};
    const t = document.createElement('div');
    t.className = `toast ${type}`;
    t.innerHTML = `<span>${icons[type]||''}</span> ${this.esc(msg)}`;
    container.appendChild(t);
    setTimeout(() => { t.style.opacity='0'; t.style.transform='translateX(40px)'; setTimeout(()=>t.remove(),300); }, 3500);
  }
}

const app = new CommentApp();
