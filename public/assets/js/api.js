// public/assets/js/api.js

if (!window.Api) {
   window.Api = {
      _getToken() {
         const meta = document.querySelector('meta[name="csrf-token"]');
         if (meta && meta.content) return meta.content;
         const ls = localStorage.getItem('csrf_token');
         return ls || '';
      },
      _mergeHeaders(base, extra) {
         const out = Object.assign({}, base);
         if (extra) {
            for (const k of Object.keys(extra)) {
               if (extra[k] === undefined || extra[k] === null) {
                  delete out[k]; // evita 'Content-Type: undefined'
               } else {
                  out[k] = extra[k];
               }
            }
         }
         return out;
      },
      _headers(extra = {}) {
         const token = this._getToken();
         const base = { 'Accept': 'application/json' };
         if (token) base['X-CSRF-Token'] = token;
         // OJO: solo seteamos Content-Type cuando sea JSON
         if (!('Content-Type' in extra)) {
            base['Content-Type'] = 'application/json';
         }
         return this._mergeHeaders(base, extra);
      },
      async _parse(res) {
         const text = await res.text();
         try { return JSON.parse(text); } catch { return { raw: text }; }
      },
      async _handle(res) {
         const body = await this._parse(res);
         if (!res.ok || body?.ok === false) {
            const err = new Error(body?.error?.message || `HTTP ${res.status}`);
            err.status = res.status;
            err.code = body?.error?.code || null;
            err.payload = body;
            throw err;
         }
         return body;
      },
      async get(url) {
         const res = await fetch(url, {
            method: 'GET',
            credentials: 'same-origin',
            headers: this._headers({ 'Content-Type': undefined })
         });
         return this._handle(res);
      },
      async post(url, body) {
         const isForm = (typeof FormData !== 'undefined') && (body instanceof FormData);
         const headers = isForm ? this._headers({ 'Content-Type': undefined }) : this._headers();
         const res = await fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers,
            body: isForm ? body : JSON.stringify(body ?? {})
         });
         return this._handle(res);
      },
      async put(url, body) {
         const isForm = (typeof FormData !== 'undefined') && (body instanceof FormData);
         const headers = isForm ? this._headers({ 'Content-Type': undefined }) : this._headers();
         const res = await fetch(url, {
            method: 'PUT',
            credentials: 'same-origin',
            headers,
            body: isForm ? body : JSON.stringify(body ?? {})
         });
         return this._handle(res);
      },
      async patch(url, body) {
         const isForm = (typeof FormData !== 'undefined') && (body instanceof FormData);
         const headers = isForm ? this._headers({ 'Content-Type': undefined }) : this._headers();
         const res = await fetch(url, {
            method: 'PATCH',
            credentials: 'same-origin',
            headers,
            body: isForm ? body : JSON.stringify(body ?? {})
         });
         return this._handle(res);
      },
      async del(url) {
         const res = await fetch(url, {
            method: 'DELETE',
            credentials: 'same-origin',
            headers: this._headers({ 'Content-Type': undefined })
         });
         return this._handle(res);
      }
   };
}
