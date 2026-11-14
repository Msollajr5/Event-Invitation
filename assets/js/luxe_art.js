/**
 * LEULA EVENTS — LUXE ART ENGINE
 * Original procedural vector illustration library (100% original code-generated SVG).
 * No third-party artwork is used. Every flower, leaf, frame and pattern is drawn
 * from mathematical primitives with a seeded random generator so that each
 * template renders identically every time.
 *
 * Licence: part of the Leula Events codebase (proprietary, original work).
 */
(function (root) {
  'use strict';

  // ------------------------------------------------------------------
  // Utilities
  // ------------------------------------------------------------------
  const f = (n) => Math.round(n * 10) / 10;
  const rad = (d) => (d * Math.PI) / 180;
  const pol = (r, deg) => [r * Math.cos(rad(deg)), r * Math.sin(rad(deg))];

  function hashStr(s) {
    let h = 2166136261;
    for (let i = 0; i < s.length; i++) h = Math.imul(h ^ s.charCodeAt(i), 16777619);
    return h >>> 0;
  }
  function rng(seed) {
    let a = seed >>> 0;
    return function () {
      a |= 0; a = (a + 0x6d2b79f5) | 0;
      let t = Math.imul(a ^ (a >>> 15), 1 | a);
      t = (t + Math.imul(t ^ (t >>> 7), 61 | t)) ^ t;
      return ((t ^ (t >>> 14)) >>> 0) / 4294967296;
    };
  }
  function hex2rgb(h) {
    h = String(h).replace('#', '');
    if (h.length === 3) h = h.split('').map((c) => c + c).join('');
    const n = parseInt(h, 16);
    return [(n >> 16) & 255, (n >> 8) & 255, n & 255];
  }
  function rgb2hex(r, g, b) {
    return '#' + [r, g, b].map((v) => Math.max(0, Math.min(255, Math.round(v))).toString(16).padStart(2, '0')).join('');
  }
  function mix(a, b, t) {
    const A = hex2rgb(a), B = hex2rgb(b);
    return rgb2hex(A[0] + (B[0] - A[0]) * t, A[1] + (B[1] - A[1]) * t, A[2] + (B[2] - A[2]) * t);
  }
  const lighten = (c, t) => mix(c, '#FFFFFF', t);
  const darken = (c, t) => mix(c, '#000000', t);

  function attrs(o) {
    return Object.keys(o || {})
      .filter((k) => o[k] !== undefined && o[k] !== null && o[k] !== false)
      .map((k) => `${k}="${o[k]}"`).join(' ');
  }

  // Drawing context: gradient registry + seeded random
  class Ctx {
    constructor(prefix, seed) {
      this.prefix = prefix.replace(/[^a-zA-Z0-9_]/g, '_');
      this.defs = [];
      this.cache = {};
      this.n = 0;
      this.r = rng(seed || hashStr(prefix));
    }
    rand(a, b) { return a + (b - a) * this.r(); }
    pick(arr) { return arr[Math.floor(this.r() * arr.length) % arr.length]; }
    _id() { return `${this.prefix}_g${this.n++}`; }
    radial(stops, o = {}) {
      const key = 'R' + JSON.stringify([stops, o]);
      if (this.cache[key]) return this.cache[key];
      const id = this._id();
      this.defs.push(`<radialGradient id="${id}" cx="${o.cx ?? 0.5}" cy="${o.cy ?? 0.5}" r="${o.r ?? 0.5}" fx="${o.fx ?? o.cx ?? 0.5}" fy="${o.fy ?? o.cy ?? 0.5}">` +
        stops.map((s) => `<stop offset="${s[0]}" stop-color="${s[1]}" stop-opacity="${s[2] ?? 1}"/>`).join('') + `</radialGradient>`);
      return (this.cache[key] = `url(#${id})`);
    }
    linear(stops, o = {}) {
      const key = 'L' + JSON.stringify([stops, o]);
      if (this.cache[key]) return this.cache[key];
      const id = this._id();
      this.defs.push(`<linearGradient id="${id}" x1="${o.x1 ?? 0}" y1="${o.y1 ?? 0}" x2="${o.x2 ?? 1}" y2="${o.y2 ?? 1}">` +
        stops.map((s) => `<stop offset="${s[0]}" stop-color="${s[1]}" stop-opacity="${s[2] ?? 1}"/>`).join('') + `</linearGradient>`);
      return (this.cache[key] = `url(#${id})`);
    }
    gold(dir) {
      const o = dir === 'v' ? { x1: 0, y1: 0, x2: 0, y2: 1 } : { x1: 0, y1: 0, x2: 1, y2: 1 };
      return this.linear([[0, '#B8862F'], [0.3, '#F3DC92'], [0.55, '#C99A3E'], [0.8, '#EED38A'], [1, '#A7772A']], o);
    }
    defsMarkup() { return this.defs.length ? `<defs>${this.defs.join('')}</defs>` : ''; }
  }

  // ------------------------------------------------------------------
  // Path geometry helpers
  // ------------------------------------------------------------------
  function smoothPath(pts, closed) {
    if (pts.length < 2) return '';
    const P = closed ? [pts[pts.length - 1], ...pts, pts[0], pts[1]] : [pts[0], ...pts, pts[pts.length - 1]];
    let d = `M${f(P[1][0])} ${f(P[1][1])}`;
    for (let i = 1; i < P.length - 2; i++) {
      const p0 = P[i - 1], p1 = P[i], p2 = P[i + 1], p3 = P[i + 2];
      const c1 = [p1[0] + (p2[0] - p0[0]) / 6, p1[1] + (p2[1] - p0[1]) / 6];
      const c2 = [p2[0] - (p3[0] - p1[0]) / 6, p2[1] - (p3[1] - p1[1]) / 6];
      d += ` C${f(c1[0])} ${f(c1[1])} ${f(c2[0])} ${f(c2[1])} ${f(p2[0])} ${f(p2[1])}`;
    }
    return closed ? d + 'Z' : d;
  }
  // Dense sampling of the same Catmull-Rom curve, returning evenly spaced points with tangent angle
  function samplePath(pts, count) {
    const P = [pts[0], ...pts, pts[pts.length - 1]];
    const dense = [];
    for (let i = 1; i < P.length - 2; i++) {
      const p0 = P[i - 1], p1 = P[i], p2 = P[i + 1], p3 = P[i + 2];
      const c1 = [p1[0] + (p2[0] - p0[0]) / 6, p1[1] + (p2[1] - p0[1]) / 6];
      const c2 = [p2[0] - (p3[0] - p1[0]) / 6, p2[1] - (p3[1] - p1[1]) / 6];
      for (let s = 0; s < 24; s++) {
        const t = s / 24, u = 1 - t;
        dense.push([
          u * u * u * p1[0] + 3 * u * u * t * c1[0] + 3 * u * t * t * c2[0] + t * t * t * p2[0],
          u * u * u * p1[1] + 3 * u * u * t * c1[1] + 3 * u * t * t * c2[1] + t * t * t * p2[1],
        ]);
      }
    }
    dense.push(pts[pts.length - 1]);
    const L = [0];
    for (let i = 1; i < dense.length; i++) L.push(L[i - 1] + Math.hypot(dense[i][0] - dense[i - 1][0], dense[i][1] - dense[i - 1][1]));
    const total = L[L.length - 1] || 1;
    return function at(t) {
      const target = Math.max(0, Math.min(1, t)) * total;
      let i = 1; while (i < L.length - 1 && L[i] < target) i++;
      const a = dense[i - 1], b = dense[i];
      const seg = (L[i] - L[i - 1]) || 1, k = (target - L[i - 1]) / seg;
      return { x: a[0] + (b[0] - a[0]) * k, y: a[1] + (b[1] - a[1]) * k, ang: Math.atan2(b[1] - a[1], b[0] - a[0]) * 180 / Math.PI, len: total };
    };
  }
  // Points for a gently curved stem
  function stemPts(x, y, len, ang, bend = 0.15, n = 4) {
    const d = pol(1, ang), nrm = [-d[1], d[0]], pts = [];
    for (let i = 0; i < n; i++) {
      const t = i / (n - 1), b = Math.sin(Math.PI * t) * bend * len;
      pts.push([x + d[0] * len * t + nrm[0] * b, y + d[1] * len * t + nrm[1] * b]);
    }
    return pts;
  }
  function arcPts(cx, cy, r, a0, a1, n = 8) {
    const pts = [];
    for (let i = 0; i < n; i++) { const a = a0 + (a1 - a0) * i / (n - 1); pts.push([cx + r * Math.cos(rad(a)), cy + r * Math.sin(rad(a))]); }
    return pts;
  }

  // Petal / leaf outlines (base at 0,0 — tip at 0,-len)
  function petalPath(len, w, tip, c) {
    const j = (k) => (c ? (c.r() - 0.5) * k : 0);
    if (tip === 'point') {
      return `M0 0 C${f(w * 0.95 + j(w * 0.2))} ${f(-len * 0.22)} ${f(w * 0.55)} ${f(-len * 0.78)} 0 ${f(-len)} C${f(-w * 0.55)} ${f(-len * 0.78)} ${f(-w * 0.95 + j(w * 0.2))} ${f(-len * 0.22)} 0 0Z`;
    }
    if (tip === 'ruffle') {
      return `M0 0 C${f(w)} ${f(-len * 0.2)} ${f(w * 1.08)} ${f(-len * 0.72)} ${f(w * 0.6)} ${f(-len * 0.9)} Q${f(w * 0.36)} ${f(-len * 1.03)} ${f(w * 0.16)} ${f(-len * 0.9)} Q${f(j(w * 0.2))} ${f(-len * 1.06)} ${f(-w * 0.16)} ${f(-len * 0.91)} Q${f(-w * 0.38)} ${f(-len * 1.02)} ${f(-w * 0.62)} ${f(-len * 0.88)} C${f(-w * 1.08)} ${f(-len * 0.7)} ${f(-w)} ${f(-len * 0.2)} 0 0Z`;
    }
    if (tip === 'narrow') {
      return `M0 0 C${f(w * 0.7)} ${f(-len * 0.3)} ${f(w * 0.45)} ${f(-len * 0.85)} 0 ${f(-len)} C${f(-w * 0.45)} ${f(-len * 0.85)} ${f(-w * 0.7)} ${f(-len * 0.3)} 0 0Z`;
    }
    if (tip === 'round-leaf') {
      return `M0 0 C${f(w * 1.05)} ${f(-len * 0.02)} ${f(w * 1.05)} ${f(-len * 0.98)} 0 ${f(-len)} C${f(-w * 1.05)} ${f(-len * 0.98)} ${f(-w * 1.05)} ${f(-len * 0.02)} 0 0Z`;
    }
    // round petal
    return `M0 0 C${f(w * 1.12 + j(w * 0.15))} ${f(-len * 0.14)} ${f(w * 0.98)} ${f(-len * 0.96)} 0 ${f(-len)} C${f(-w * 0.98)} ${f(-len * 0.96)} ${f(-w * 1.12 + j(w * 0.15))} ${f(-len * 0.14)} 0 0Z`;
  }

  // ------------------------------------------------------------------
  // Watercolour washes & texture
  // ------------------------------------------------------------------
  function wash(c, cx, cy, rx, ry, color, op = 0.35) {
    const pts = [], n = 13;
    for (let i = 0; i < n; i++) {
      const a = (i / n) * 360, k = c.rand(0.72, 1.12);
      pts.push([cx + Math.cos(rad(a)) * rx * k, cy + Math.sin(rad(a)) * ry * k]);
    }
    const fill = c.radial([[0, color, op], [0.65, color, op * 0.55], [1, color, 0]], { r: 0.55 });
    return `<path d="${smoothPath(pts, true)}" fill="${fill}"/>`;
  }
  function splatter(c, cx, cy, spread, n, color, op = 0.45) {
    let s = '';
    for (let i = 0; i < n; i++) {
      const a = c.rand(0, 360), d = Math.pow(c.r(), 0.7) * spread;
      s += `<circle cx="${f(cx + Math.cos(rad(a)) * d)}" cy="${f(cy + Math.sin(rad(a)) * d)}" r="${f(c.rand(0.5, 2.2))}" fill="${color}" fill-opacity="${f(op * c.rand(0.5, 1) * 100) / 100}"/>`;
    }
    return s;
  }
  function goldFlakes(c, x, y, w, h, n) {
    let s = '';
    const g = c.gold();
    for (let i = 0; i < n; i++) {
      const cx = x + c.rand(0, w), cy = y + c.rand(0, h), r = c.rand(1, 4.5), pts = [];
      for (let k = 0; k < 5; k++) pts.push(`${f(cx + Math.cos(rad(k * 72 + c.rand(-20, 20))) * r * c.rand(0.5, 1.2))},${f(cy + Math.sin(rad(k * 72 + c.rand(-20, 20))) * r * c.rand(0.5, 1.2))}`);
      s += `<polygon points="${pts.join(' ')}" fill="${g}" fill-opacity="${f(c.rand(0.55, 0.95) * 100) / 100}"/>`;
    }
    return s;
  }
  function marble(c, W, H, o = {}) {
    let s = '';
    const vein = o.vein || '#9E9A93', n = o.count || 9;
    for (let i = 0; i < n; i++) {
      const pts = []; let x = c.rand(-40, W * 0.6), y = c.rand(-40, H * 0.3);
      const ang = c.rand(35, 70);
      for (let k = 0; k < 7; k++) { pts.push([x, y]); x += Math.cos(rad(ang + c.rand(-28, 28))) * c.rand(70, 140); y += Math.sin(rad(ang + c.rand(-28, 28))) * c.rand(70, 140); }
      s += `<path d="${smoothPath(pts)}" fill="none" stroke="${vein}" stroke-width="${f(c.rand(0.5, 2.2))}" stroke-opacity="${f(c.rand(0.12, 0.32) * 100) / 100}" stroke-linecap="round"/>`;
      if (c.r() > 0.5) s += `<path d="${smoothPath(pts.map((p) => [p[0] + c.rand(4, 10), p[1] + c.rand(-4, 4)]))}" fill="none" stroke="${vein}" stroke-width="0.5" stroke-opacity="0.12"/>`;
    }
    if (o.gold) {
      for (let i = 0; i < (o.goldCount || 2); i++) {
        const pts = []; let x = c.rand(0, W * 0.5), y = c.rand(H * 0.2, H * 0.8);
        for (let k = 0; k < 6; k++) { pts.push([x, y]); x += c.rand(60, 120); y += c.rand(-60, 60); }
        s += `<path d="${smoothPath(pts)}" fill="none" stroke="${c.gold()}" stroke-width="${f(c.rand(0.8, 1.6))}" stroke-opacity="0.85"/>`;
      }
    }
    return s;
  }

  // ------------------------------------------------------------------
  // Flowers
  // ------------------------------------------------------------------
  function bloom(c, cx, cy, r, o = {}) {
    const L = o.layers || 3, petals = o.petals || [8, 7, 6, 5];
    const col = Object.assign({ edge: '#FFFFFF', base: '#EADBC8', deep: null, center: '#C8A46A' }, o.colors || {});
    const deep = col.deep || darken(col.base, 0.12);
    const tip = o.tip || 'round', wk = o.width || 0.55;
    const stroke = col.stroke || mix(col.base, '#5A4630', 0.3);
    let s = '';
    for (let l = 0; l < L; l++) {
      const n = petals[l] || petals[petals.length - 1];
      const len = r * (1 - l * (o.step || 0.22));
      const off = (o.rot || 0) + l * (180 / n) + c.rand(-8, 8);
      const t = L > 1 ? l / (L - 1) : 0;
      const base = mix(col.base, deep, t * 0.7);
      const edge = mix(col.edge, col.base, t * 0.4);
      const fill = c.radial([[0, base, 0.96], [0.55, mix(base, edge, 0.62), 0.93], [1, edge, 0.9]], { cx: 0.5, cy: 1, r: 1 });
      for (let i = 0; i < n; i++) {
        const a = off + i * 360 / n + c.rand(-7, 7);
        const pl = len * c.rand(0.88, 1.06), pw = pl * wk * c.rand(0.88, 1.1);
        s += `<path transform="rotate(${f(a)})" d="${petalPath(pl, pw, tip, c)}" fill="${fill}" stroke="${stroke}" stroke-opacity="0.3" stroke-width="0.6"/>`;
        if (o.veins !== false && l === 0) s += `<path transform="rotate(${f(a)})" d="M0 ${f(-pl * 0.14)} Q${f(pw * 0.1)} ${f(-pl * 0.5)} 0 ${f(-pl * 0.8)}" fill="none" stroke="${stroke}" stroke-opacity="0.2" stroke-width="0.5"/>`;
      }
    }
    if (o.center !== false) {
      const cr = r * (o.centerSize || 0.15);
      s += `<circle cx="0" cy="0" r="${f(cr)}" fill="${c.radial([[0, lighten(col.center, 0.35)], [1, col.center]])}"/>`;
      if (o.stamens !== false) {
        const ns = o.stamenCount || 16, sc = darken(col.center, 0.3);
        for (let i = 0; i < ns; i++) {
          const a = i * 360 / ns + c.rand(-6, 6), rl = cr * c.rand(1.4, 2.2), p0 = pol(cr * 0.5, a), p1 = pol(rl, a);
          s += `<line x1="${f(p0[0])}" y1="${f(p0[1])}" x2="${f(p1[0])}" y2="${f(p1[1])}" stroke="${sc}" stroke-width="0.6" stroke-opacity="0.7"/><circle cx="${f(p1[0])}" cy="${f(p1[1])}" r="1.2" fill="${sc}"/>`;
        }
      }
    }
    return `<g transform="translate(${f(cx)} ${f(cy)}) rotate(${f(o.tilt || 0)}) scale(1 ${o.squash || 1})">${s}</g>`;
  }

  function rose(c, cx, cy, r, o = {}) {
    const col = Object.assign({ edge: '#FBE9E7', base: '#E8B4B0', deep: '#C97B7A' }, o.colors || {});
    let s = bloom(c, 0, 0, r, { layers: 2, petals: [5, 5], width: 0.82, step: 0.2, colors: { edge: col.edge, base: col.base, deep: col.deep, center: col.deep }, center: false, veins: false });
    for (let i = 0; i < 6; i++) {
      const ri = r * (0.6 - i * 0.088), a0 = i * 67 + c.rand(-10, 10), a1 = a0 + 215;
      const p1 = pol(ri, a0), p2 = pol(ri, a1);
      const fill = c.radial([[0, mix(col.base, col.deep, 0.3 + i * 0.1)], [1, mix(col.edge, col.base, 0.4 + i * 0.08)]], { cx: 0.5, cy: 0.3, r: 0.8 });
      s += `<path d="M${f(p1[0])} ${f(p1[1])} A${f(ri)} ${f(ri)} 0 1 1 ${f(p2[0])} ${f(p2[1])} A${f(ri * 0.82)} ${f(ri * 0.64)} ${f(a0)} 0 0 ${f(p1[0])} ${f(p1[1])}Z" fill="${fill}" stroke="${darken(col.deep, 0.15)}" stroke-opacity="0.35" stroke-width="0.6"/>`;
    }
    s += `<circle cx="0" cy="0" r="${f(r * 0.07)}" fill="${darken(col.deep, 0.1)}" fill-opacity="0.8"/>`;
    return `<g transform="translate(${f(cx)} ${f(cy)}) rotate(${f(o.tilt || 0)}) scale(1 ${o.squash || 1})">${s}</g>`;
  }

  function peony(c, cx, cy, r, o = {}) {
    const col = Object.assign({ edge: '#FDF1F0', base: '#F2C4C3', deep: '#D98E92', center: '#E7C47A' }, o.colors || {});
    let s = bloom(c, 0, 0, r, { layers: 4, petals: [10, 9, 8, 7], tip: 'ruffle', width: 0.62, step: 0.19, colors: col, center: false, veins: true });
    s += bloom(c, 0, 0, r * 0.32, { layers: 2, petals: [6, 5], tip: 'ruffle', width: 0.7, colors: { edge: col.base, base: col.deep, center: col.center }, centerSize: 0.3, stamenCount: 10 });
    return `<g transform="translate(${f(cx)} ${f(cy)}) rotate(${f(o.tilt || 0)}) scale(1 ${o.squash || 1})">${s}</g>`;
  }

  function magnolia(c, cx, cy, r, o = {}) {
    const col = Object.assign({ edge: '#FFFFFF', base: '#F6E7E1', deep: '#E2B6B0' }, o.colors || {});
    const back = c.radial([[0, col.deep, 0.9], [0.6, col.base, 0.95], [1, col.edge, 0.95]], { cx: 0.5, cy: 1, r: 1 });
    const front = c.radial([[0, col.deep], [0.45, mix(col.base, col.deep, 0.3)], [1, col.edge]], { cx: 0.5, cy: 1, r: 1 });
    const st = darken(col.deep, 0.25);
    let s = '';
    [-62, -34, 34, 62].forEach((a) => { s += `<path transform="rotate(${a + c.rand(-5, 5)})" d="${petalPath(r * 0.92, r * 0.36, 'point', c)}" fill="${back}" stroke="${st}" stroke-opacity="0.3" stroke-width="0.6"/>`; });
    s += `<path transform="rotate(${c.rand(-4, 4)})" d="${petalPath(r, r * 0.4, 'point', c)}" fill="${back}" stroke="${st}" stroke-opacity="0.3" stroke-width="0.6"/>`;
    [-20, 18].forEach((a) => { s += `<path transform="rotate(${a})" d="${petalPath(r * 0.86, r * 0.42, 'point', c)}" fill="${front}" stroke="${st}" stroke-opacity="0.35" stroke-width="0.6"/>`; });
    s += `<path d="M${f(-r * 0.28)} ${f(r * 0.04)} Q0 ${f(r * 0.26)} ${f(r * 0.28)} ${f(r * 0.04)} Q0 ${f(r * 0.12)} ${f(-r * 0.28)} ${f(r * 0.04)}Z" fill="#7B5B45" fill-opacity="0.85"/>`;
    return `<g transform="translate(${f(cx)} ${f(cy)}) rotate(${f(o.tilt || 0)}) scale(${o.scale || 1})">${s}</g>`;
  }

  function orchid(c, cx, cy, r, o = {}) {
    const col = Object.assign({ edge: '#FFFFFF', base: '#F4EEF2', lip: '#C98AA6', center: '#F1D27A' }, o.colors || {});
    const fill = c.radial([[0, col.base], [1, col.edge]], { cx: 0.5, cy: 1, r: 1 });
    const st = mix(col.lip, '#5a4630', 0.3);
    let s = '';
    [0, 128, -128].forEach((a) => { s += `<path transform="rotate(${a + c.rand(-5, 5)})" d="${petalPath(r * 0.95, r * 0.26, 'point', c)}" fill="${fill}" stroke="${st}" stroke-opacity="0.25" stroke-width="0.6"/>`; });
    [-68, 68].forEach((a) => { s += `<path transform="rotate(${a + c.rand(-4, 4)})" d="${petalPath(r * 0.85, r * 0.62, 'round', c)}" fill="${fill}" stroke="${st}" stroke-opacity="0.25" stroke-width="0.6"/>`; });
    const lip = c.radial([[0, darken(col.lip, 0.15)], [1, lighten(col.lip, 0.35)]], { cx: 0.5, cy: 1, r: 1 });
    s += `<path transform="rotate(180)" d="${petalPath(r * 0.62, r * 0.42, 'ruffle', c)}" fill="${lip}" stroke="${st}" stroke-opacity="0.3" stroke-width="0.6"/>`;
    for (let i = 0; i < 7; i++) s += `<circle cx="${f(c.rand(-r * 0.15, r * 0.15))}" cy="${f(c.rand(r * 0.12, r * 0.4))}" r="${f(c.rand(0.6, 1.5))}" fill="${darken(col.lip, 0.35)}" fill-opacity="0.6"/>`;
    s += `<ellipse cx="0" cy="${f(r * 0.02)}" rx="${f(r * 0.1)}" ry="${f(r * 0.14)}" fill="${col.center}"/>`;
    return `<g transform="translate(${f(cx)} ${f(cy)}) rotate(${f(o.tilt || 0)})">${s}</g>`;
  }

  function daisy(c, cx, cy, r, o = {}) {
    return bloom(c, cx, cy, r, { layers: 1, petals: [o.petals || 13], width: o.width || 0.22, colors: Object.assign({ edge: '#FFFFFF', base: '#F3EEE6', center: '#E2B64A' }, o.colors || {}), centerSize: 0.26, stamenCount: 10, tilt: o.tilt, squash: o.squash, veins: false });
  }
  function bud(c, x, y, s, ang, color) {
    const fill = c.radial([[0, darken(color, 0.1)], [1, lighten(color, 0.3)]], { cx: 0.5, cy: 1, r: 1 });
    return `<g transform="translate(${f(x)} ${f(y)}) rotate(${f(ang + 90)})"><path d="${petalPath(s, s * 0.42, 'point', c)}" fill="${fill}" stroke="${darken(color, 0.3)}" stroke-opacity="0.3" stroke-width="0.5"/><path d="M0 0 L${f(-s * 0.25)} ${f(-s * 0.35)} M0 0 L${f(s * 0.25)} ${f(-s * 0.35)}" stroke="#7D8C5A" stroke-width="1" fill="none"/></g>`;
  }

  function protea(c, cx, cy, r, o = {}) {
    const col = Object.assign({ edge: '#F8D6DA', base: '#E59AA6', deep: '#B85C6E', center: '#F5EBDD' }, o.colors || {});
    let s = '';
    for (let l = 0; l < 3; l++) {
      const n = 9 - l, len = r * (1 - l * 0.18);
      const fill = c.radial([[0, mix(col.deep, col.base, l * 0.3)], [1, col.edge]], { cx: 0.5, cy: 1, r: 1 });
      for (let i = 0; i < n; i++) {
        const a = -90 + (i - (n - 1) / 2) * (150 / n) + c.rand(-4, 4);
        s += `<path transform="rotate(${f(a + 90)})" d="${petalPath(len, len * 0.24, 'point', c)}" fill="${fill}" stroke="${col.deep}" stroke-opacity="0.35" stroke-width="0.6"/>`;
      }
    }
    s += `<ellipse cx="0" cy="${f(-r * 0.32)}" rx="${f(r * 0.36)}" ry="${f(r * 0.42)}" fill="${c.radial([[0, '#FFFFFF'], [1, col.center]])}"/>`;
    for (let i = 0; i < 40; i++) {
      const a = c.rand(-170, -10), rr = c.rand(r * 0.05, r * 0.38), p = pol(rr, a);
      s += `<line x1="${f(p[0] * 0.6)}" y1="${f(-r * 0.32 + p[1] * 0.6)}" x2="${f(p[0])}" y2="${f(-r * 0.32 + p[1])}" stroke="#D9C4AE" stroke-width="0.5"/>`;
    }
    return `<g transform="translate(${f(cx)} ${f(cy)}) rotate(${f(o.tilt || 0)})">${s}</g>`;
  }

  function strelitzia(c, x, y, s, o = {}) {
    const g = c.linear([[0, '#3E6B4A'], [0.6, '#5E8A5A'], [1, '#8E4E6A']], { x1: 0, y1: 0, x2: 1, y2: 0 });
    let out = `<path d="M0 0 C${f(s * 0.3)} ${f(-s * 0.12)} ${f(s * 0.8)} ${f(-s * 0.12)} ${f(s * 1.1)} ${f(-s * 0.02)} C${f(s * 0.8)} ${f(s * 0.06)} ${f(s * 0.3)} ${f(s * 0.08)} 0 0Z" fill="${g}"/>`;
    const orange = c.linear([[0, '#E8892F'], [1, '#F7C35A']], { x1: 0, y1: 1, x2: 0, y2: 0 });
    [-75, -58, -42, -28].forEach((a, i) => { out += `<path transform="translate(${f(s * 0.42)} ${f(-s * 0.08)}) rotate(${a + 90})" d="${petalPath(s * (0.75 - i * 0.07), s * 0.11, 'point', c)}" fill="${orange}" stroke="#B9651E" stroke-opacity="0.35" stroke-width="0.5"/>`; });
    out += `<path transform="translate(${f(s * 0.45)} ${f(-s * 0.08)}) rotate(${-50 + 90})" d="${petalPath(s * 0.55, s * 0.06, 'point', c)}" fill="#3F5FA8"/>`;
    out += `<path d="M0 0 C${f(-s * 0.1)} ${f(s * 0.3)} ${f(-s * 0.15)} ${f(s * 0.7)} ${f(-s * 0.2)} ${f(s * 1.2)}" stroke="#4E7A50" stroke-width="2.4" fill="none" stroke-linecap="round"/>`;
    return `<g transform="translate(${f(x)} ${f(y)}) rotate(${f(o.tilt || 0)}) scale(${o.flip ? -1 : 1} 1)">${out}</g>`;
  }

  // ------------------------------------------------------------------
  // Foliage
  // ------------------------------------------------------------------
  function leaf(c, x, y, len, w, ang, color, o = {}) {
    const type = o.type || 'point';
    const fill = o.flat ? color : c.linear([[0, darken(color, 0.12), 0.95], [1, lighten(color, 0.18), 0.9]], { x1: 0, y1: 1, x2: 0, y2: 0 });
    let s = `<path d="${petalPath(len, w, type === 'round' ? 'round-leaf' : type, null)}" fill="${fill}"${o.stroke ? ` stroke="${o.stroke}" stroke-width="${o.strokeW || 0.8}"` : ''}/>`;
    if (o.vein !== false) s += `<path d="M0 ${f(-len * 0.05)} Q${f(w * 0.12)} ${f(-len * 0.5)} 0 ${f(-len * 0.92)}" fill="none" stroke="${o.veinColor || lighten(color, 0.35)}" stroke-opacity="0.55" stroke-width="0.6"/>`;
    return `<g transform="translate(${f(x)} ${f(y)}) rotate(${f(ang + 90)})">${s}</g>`;
  }

  /** Generic branch: stem + leaves placed along a smooth curve */
  function branch(c, pts, o = {}) {
    const stem = o.stem || '#7A8B6F', at = samplePath(pts, 0);
    const colors = o.colors || ['#7F9A7A', '#93AD8C', '#A9BFA2'];
    const count = o.count || 12, spread = o.angle ?? 48, type = o.type || 'point';
    const L0 = o.leafLen || 26, W0 = o.leafW || L0 * 0.38;
    let leaves = '';
    for (let i = 0; i < count; i++) {
      const t = (o.start ?? 0.06) + (i / Math.max(1, count - 1)) * ((o.end ?? 0.94) - (o.start ?? 0.06));
      const p = at(t), sc = o.taper === false ? 1 : 1 - t * 0.45;
      const sides = o.pairs ? [1, -1] : [i % 2 ? 1 : -1];
      sides.forEach((sd) => {
        const a = p.ang + sd * (spread + c.rand(-10, 10));
        const len = L0 * sc * c.rand(0.85, 1.12);
        leaves += leaf(c, p.x, p.y, len, (o.round ? len * 0.48 : W0 * sc), a, c.pick(colors), { type: o.round ? 'round' : type, vein: o.vein, flat: o.flat, stroke: o.leafStroke, strokeW: o.leafStrokeW });
      });
    }
    if (o.tipLeaf !== false) { const p = at(1); leaves += leaf(c, p.x, p.y, L0 * 0.7, (o.round ? L0 * 0.33 : W0 * 0.6), p.ang, c.pick(colors), { type: o.round ? 'round' : type, vein: o.vein, flat: o.flat, stroke: o.leafStroke }); }
    const stemSvg = `<path d="${smoothPath(pts)}" fill="none" stroke="${stem}" stroke-width="${o.stemW || 1.6}" stroke-linecap="round"/>`;
    let berries = '';
    if (o.berries) {
      for (let i = 0; i < o.berries; i++) {
        const p = at(c.rand(0.2, 0.95)), off = pol(c.rand(6, 12), p.ang + (c.r() > 0.5 ? 70 : -70));
        berries += `<ellipse cx="${f(p.x + off[0])}" cy="${f(p.y + off[1])}" rx="${f(o.berryR || 3.4)}" ry="${f((o.berryR || 3.4) * 1.3)}" transform="rotate(${f(p.ang)} ${f(p.x + off[0])} ${f(p.y + off[1])})" fill="${c.radial([[0, lighten(o.berryColor || '#4B4A3A', 0.35)], [1, o.berryColor || '#4B4A3A']], { cx: 0.35, cy: 0.35, r: 0.7 })}"/>`;
      }
    }
    return o.stemOnTop ? leaves + stemSvg + berries : stemSvg + leaves + berries;
  }

  const eucalyptus = (c, pts, o = {}) => branch(c, pts, Object.assign({ round: true, pairs: true, angle: 62, count: 9, leafLen: 24, stem: '#8C9A86', colors: ['#9DB4A0', '#8AA393', '#B6C8B3', '#A3B8A8'], vein: false }, o));
  const olive = (c, pts, o = {}) => branch(c, pts, Object.assign({ type: 'narrow', angle: 34, count: 16, leafLen: 30, leafW: 6, stem: '#6F7B55', colors: ['#7D8C5A', '#93A26E', '#A9B48A', '#6E7D4F'], berries: 3, berryColor: '#3E4430' }, o));
  const fern = (c, pts, o = {}) => branch(c, pts, Object.assign({ type: 'narrow', pairs: true, angle: 64, count: 18, leafLen: 22, leafW: 5, stem: '#5E7652', colors: ['#6E8B5E', '#7E9A6C'], vein: false, flat: true }, o));

  function lavender(c, x, y, len, ang, o = {}) {
    const pts = stemPts(x, y, len, ang, o.bend ?? 0.06), at = samplePath(pts, 0);
    const purples = o.colors || ['#9B86C2', '#8A72B5', '#B3A2D4', '#7E66A8'];
    let s = `<path d="${smoothPath(pts)}" fill="none" stroke="#7F8F6E" stroke-width="1.3" stroke-linecap="round"/>`;
    s += leaf(c, at(0.15).x, at(0.15).y, len * 0.28, 3, at(0.15).ang - 25, '#8FA07C', { type: 'narrow', vein: false });
    s += leaf(c, at(0.22).x, at(0.22).y, len * 0.24, 3, at(0.22).ang + 25, '#8FA07C', { type: 'narrow', vein: false });
    for (let t = 0.45; t <= 1.0001; t += 0.045) {
      const p = at(t), k = 1 - (t - 0.45) * 0.7;
      for (let j = 0; j < 4; j++) {
        const off = pol(c.rand(1.5, 4) * k, p.ang + (j % 2 ? 90 : -90));
        s += `<ellipse cx="${f(p.x + off[0])}" cy="${f(p.y + off[1])}" rx="${f(2.1 * k + 0.6)}" ry="${f(3.3 * k + 0.8)}" transform="rotate(${f(p.ang + 90 + c.rand(-30, 30))} ${f(p.x + off[0])} ${f(p.y + off[1])})" fill="${c.pick(purples)}" fill-opacity="0.9"/>`;
      }
    }
    return s;
  }

  function pampas(c, pts, o = {}) {
    const at = samplePath(pts, 0), cols = o.colors || ['#E6D3B3', '#D9C29E', '#EFE2CB', '#CDB38A'];
    let s = `<path d="${smoothPath(pts)}" fill="none" stroke="${o.stem || '#BFA37A'}" stroke-width="1.2" stroke-linecap="round"/>`;
    const n = o.density || 70;
    for (let i = 0; i < n; i++) {
      const t = 0.38 + (i / n) * 0.62, p = at(t), k = Math.sin(Math.PI * Math.min(1, (t - 0.38) / 0.62 * 0.9 + 0.1));
      for (let j = 0; j < 4; j++) {
        const sd = j % 2 ? 1 : -1, a = p.ang + sd * c.rand(18, 52), l = c.rand(12, 34) * (0.45 + k * 0.6);
        const e = pol(l, a), m = pol(l * 0.55, a - sd * 12);
        s += `<path d="M${f(p.x)} ${f(p.y)} Q${f(p.x + m[0])} ${f(p.y + m[1])} ${f(p.x + e[0])} ${f(p.y + e[1])}" fill="none" stroke="${c.pick(cols)}" stroke-width="${f(c.rand(0.6, 1.4))}" stroke-opacity="${f(c.rand(0.5, 0.85) * 100) / 100}" stroke-linecap="round"/>`;
      }
    }
    return s;
  }

  function palmFrond(c, pts, o = {}) {
    return branch(c, pts, Object.assign({ type: 'narrow', pairs: true, angle: 52, count: 20, leafLen: o.size || 62, leafW: (o.size || 62) * 0.12, stem: '#2F5D45', colors: o.colors || ['#2E6B4E', '#3B7D5B', '#255C42', '#4C8B67'], vein: true, taper: true, tipLeaf: true }, o));
  }
  function monstera(c, x, y, size, ang, o = {}) {
    const colors = o.colors || ['#24573F', '#2D6A4C', '#1F4D37'];
    const pts = stemPts(0, 0, size, -90, 0.05), at = samplePath(pts, 0);
    let s = '';
    for (let k = 0; k < 6; k++) {
      const p = at(0.1 + k * 0.15);
      [1, -1].forEach((sd) => { s += leaf(c, p.x, p.y, size * (0.62 - k * 0.07), size * 0.13, -90 + sd * (78 - k * 9), c.pick(colors), { veinColor: '#6FA184' }); });
    }
    s += leaf(c, at(0.95).x, at(0.95).y, size * 0.22, size * 0.1, -90, colors[0], {});
    s += `<path d="${smoothPath(pts)}" fill="none" stroke="#5E9A79" stroke-width="1.4"/>`;
    s += `<path d="M0 0 L0 ${f(size * 0.45)}" stroke="#2F5D45" stroke-width="2.2"/>`;
    return `<g transform="translate(${f(x)} ${f(y)}) rotate(${f(ang + 90)})">${s}</g>`;
  }
  function mapleLeaf(c, x, y, s, ang, color) {
    const pts = [];
    for (let a = 0; a < 360; a += 7) {
      const k = 0.42 + 0.58 * Math.pow(Math.abs(Math.cos(rad(a) * 2.5)), 0.9);
      const rr = s * k * (a > 150 && a < 210 ? 0.55 : 1);
      pts.push([Math.sin(rad(a)) * rr, -Math.cos(rad(a)) * rr]);
    }
    const fill = c.radial([[0, lighten(color, 0.15)], [1, darken(color, 0.12)]]);
    let o = `<path d="${smoothPath(pts, true)}" fill="${fill}" fill-opacity="0.92"/>`;
    [0, 72, -72, 144, -144].forEach((a) => { const p = pol(s * 0.85, a - 90); o += `<line x1="0" y1="0" x2="${f(p[0])}" y2="${f(p[1])}" stroke="${lighten(color, 0.35)}" stroke-width="0.6" stroke-opacity="0.6"/>`; });
    o += `<line x1="0" y1="0" x2="0" y2="${f(s * 0.7)}" stroke="${darken(color, 0.3)}" stroke-width="1.2"/>`;
    return `<g transform="translate(${f(x)} ${f(y)}) rotate(${f(ang)})">${o}</g>`;
  }
  function oakLeaf(c, x, y, len, ang, color) {
    const pts = [];
    for (let i = 0; i <= 20; i++) { const t = i / 20; const w = Math.sin(Math.PI * t) * len * 0.26 * (0.75 + 0.25 * Math.cos(t * Math.PI * 9)); pts.push([w, -len * t]); }
    for (let i = 19; i >= 1; i--) { const t = i / 20; const w = Math.sin(Math.PI * t) * len * 0.26 * (0.75 + 0.25 * Math.cos(t * Math.PI * 9 + 0.6)); pts.push([-w, -len * t]); }
    const fill = c.linear([[0, darken(color, 0.15)], [1, lighten(color, 0.2)]], { x1: 0, y1: 1, x2: 0, y2: 0 });
    return `<g transform="translate(${f(x)} ${f(y)}) rotate(${f(ang + 90)})"><path d="${smoothPath([[0, 0], ...pts], true)}" fill="${fill}" fill-opacity="0.92"/><path d="M0 0 L0 ${f(-len * 0.92)}" stroke="${lighten(color, 0.35)}" stroke-width="0.7" stroke-opacity="0.6"/></g>`;
  }

  // ------------------------------------------------------------------
  // Frames, ornaments, dividers
  // ------------------------------------------------------------------
  function frameRect(c, x, y, w, h, o = {}) {
    const st = o.stroke || '#C9A96E', sw = o.width || 1, rx = o.radius || 0;
    let s = `<rect x="${f(x)}" y="${f(y)}" width="${f(w)}" height="${f(h)}" rx="${rx}" fill="none" stroke="${st}" stroke-width="${sw}"${o.dash ? ` stroke-dasharray="${o.dash}"` : ''}/>`;
    if (o.double) { const g = o.double; s += `<rect x="${f(x + g)}" y="${f(y + g)}" width="${f(w - 2 * g)}" height="${f(h - 2 * g)}" rx="${Math.max(0, rx - g)}" fill="none" stroke="${st}" stroke-width="${o.innerWidth || sw * 0.5}"/>`; }
    return s;
  }
  function archPath(x, y, w, h) {
    const r = w / 2;
    return `M${f(x)} ${f(y + h)} L${f(x)} ${f(y + r)} A${f(r)} ${f(r)} 0 0 1 ${f(x + w)} ${f(y + r)} L${f(x + w)} ${f(y + h)} Z`;
  }
  function arch(c, x, y, w, h, o = {}) {
    let s = `<path d="${archPath(x, y, w, h)}" fill="${o.fill || 'none'}"${o.fillOpacity ? ` fill-opacity="${o.fillOpacity}"` : ''} stroke="${o.stroke || 'none'}" stroke-width="${o.width || 1}"/>`;
    if (o.inner) s += `<path d="${archPath(x + o.inner, y + o.inner, w - 2 * o.inner, h - 2 * o.inner)}" fill="none" stroke="${o.stroke}" stroke-width="${(o.width || 1) * 0.5}"/>`;
    return s;
  }
  function cornerFlourish(c, x, y, s, o = {}) {
    const st = o.color || '#B8955A', w = o.width || 1.1;
    let p = '';
    p += `<path d="M0 ${f(s)} C0 ${f(s * 0.35)} ${f(s * 0.35)} 0 ${f(s)} 0" fill="none" stroke="${st}" stroke-width="${w}" stroke-linecap="round"/>`;
    p += `<path d="M${f(s)} 0 c${f(s * 0.14)} 0 ${f(s * 0.17)} ${f(s * 0.14)} ${f(s * 0.06)} ${f(s * 0.15)} c${f(-s * 0.07)} 0 ${f(-s * 0.08)} ${f(-s * 0.08)} ${f(-s * 0.02)} ${f(-s * 0.09)}" fill="none" stroke="${st}" stroke-width="${w}" stroke-linecap="round"/>`;
    p += `<path d="M0 ${f(s)} c0 ${f(s * 0.14)} ${f(s * 0.14)} ${f(s * 0.17)} ${f(s * 0.15)} ${f(s * 0.06)} c0 ${f(-s * 0.07)} ${f(-s * 0.08)} ${f(-s * 0.08)} ${f(-s * 0.09)} ${f(-s * 0.02)}" fill="none" stroke="${st}" stroke-width="${w}" stroke-linecap="round"/>`;
    p += `<path d="M${f(s * 0.14)} ${f(s * 0.62)} C${f(s * 0.14)} ${f(s * 0.3)} ${f(s * 0.3)} ${f(s * 0.14)} ${f(s * 0.62)} ${f(s * 0.14)}" fill="none" stroke="${st}" stroke-width="${f(w * 0.7)}"/>`;
    p += `<path d="M${f(s * 0.2)} ${f(s * 0.2)} q${f(s * 0.12)} ${f(-s * 0.02)} ${f(s * 0.2)} ${f(s * 0.08)} q${f(-s * 0.12)} ${f(s * 0.04)} ${f(-s * 0.2)} ${f(-s * 0.08)}Z" fill="${st}" fill-opacity="0.85"/>`;
    p += `<path d="M${f(s * 0.2)} ${f(s * 0.2)} q${f(-s * 0.02)} ${f(s * 0.12)} ${f(s * 0.08)} ${f(s * 0.2)} q${f(s * 0.04)} ${f(-s * 0.12)} ${f(-s * 0.08)} ${f(-s * 0.2)}Z" fill="${st}" fill-opacity="0.85"/>`;
    p += `<circle cx="${f(s * 0.08)}" cy="${f(s * 0.08)}" r="${f(Math.max(1.2, s * 0.025))}" fill="${st}"/>`;
    [[0.45, 0.06], [0.06, 0.45], [0.75, 0.04], [0.04, 0.75]].forEach(([a, b]) => { p += `<circle cx="${f(s * a)}" cy="${f(s * b)}" r="${f(Math.max(0.9, s * 0.014))}" fill="${st}"/>`; });
    const sx = o.flipX ? -1 : 1, sy = o.flipY ? -1 : 1;
    return `<g transform="translate(${f(x)} ${f(y)}) scale(${sx} ${sy})">${p}</g>`;
  }
  function fourCorners(c, x, y, w, h, s, o = {}) {
    return cornerFlourish(c, x, y, s, o) + cornerFlourish(c, x + w, y, s, Object.assign({}, o, { flipX: true })) +
      cornerFlourish(c, x, y + h, s, Object.assign({}, o, { flipY: true })) + cornerFlourish(c, x + w, y + h, s, Object.assign({}, o, { flipX: true, flipY: true }));
  }
  function divider(c, cx, y, w, o = {}) {
    const st = o.color || '#B8955A', style = o.style || 'diamond', sw = o.width || 0.9, h = w / 2;
    if (style === 'diamond') {
      return `<line x1="${f(cx - h)}" y1="${f(y)}" x2="${f(cx - 8)}" y2="${f(y)}" stroke="${st}" stroke-width="${sw}"/><line x1="${f(cx + 8)}" y1="${f(y)}" x2="${f(cx + h)}" y2="${f(y)}" stroke="${st}" stroke-width="${sw}"/><path d="M${f(cx)} ${f(y - 4.5)} L${f(cx + 4.5)} ${f(y)} L${f(cx)} ${f(y + 4.5)} L${f(cx - 4.5)} ${f(y)}Z" fill="${st}"/><circle cx="${f(cx - h)}" cy="${f(y)}" r="1.4" fill="${st}"/><circle cx="${f(cx + h)}" cy="${f(y)}" r="1.4" fill="${st}"/>`;
    }
    if (style === 'dots') {
      let s = ''; for (let i = -3; i <= 3; i++) s += `<circle cx="${f(cx + i * 9)}" cy="${f(y)}" r="${i === 0 ? 2.2 : 1.2}" fill="${st}"/>`;
      return s;
    }
    if (style === 'line') return `<line x1="${f(cx - h)}" y1="${f(y)}" x2="${f(cx + h)}" y2="${f(y)}" stroke="${st}" stroke-width="${sw}"/>`;
    // flourish
    const side = (dir) => `<path d="M${f(cx + dir * 6)} ${f(y)} C${f(cx + dir * h * 0.3)} ${f(y - 9)} ${f(cx + dir * h * 0.55)} ${f(y + 9)} ${f(cx + dir * h)} ${f(y)}" fill="none" stroke="${st}" stroke-width="${sw}" stroke-linecap="round"/><path d="M${f(cx + dir * h * 0.42)} ${f(y)} c${f(dir * 5)} -6 ${f(dir * 11)} -6 ${f(dir * 14)} -2 c${f(-dir * 5)} 3 ${f(-dir * 10)} 3 ${f(-dir * 14)} 2Z" fill="${st}" fill-opacity="0.85"/>`;
    return side(-1) + side(1) + `<circle cx="${f(cx)}" cy="${f(y)}" r="2.6" fill="${st}"/>`;
  }
  function laurel(c, cx, cy, r, o = {}) {
    const colors = o.colors || [o.color || '#B8955A'];
    const gap = o.gap ?? 30;
    const left = arcPts(cx, cy, r, 90 + 8, 270 - gap, 8);
    const right = arcPts(cx, cy, r, 90 - 8, -90 + gap, 8);
    const opt = { type: 'point', pairs: true, angle: 38, count: o.count || 11, leafLen: o.leafLen || r * 0.24, leafW: (o.leafLen || r * 0.24) * 0.36, stem: o.stem || colors[0], colors, vein: false, flat: !!o.flat, taper: true, stemW: 1.1 };
    let s = branch(c, left, opt) + branch(c, right, opt);
    if (o.ribbon) s += `<path d="M${f(cx - 10)} ${f(cy + r + 2)} Q${f(cx)} ${f(cy + r - 6)} ${f(cx + 10)} ${f(cy + r + 2)} L${f(cx + 16)} ${f(cy + r + 16)} L${f(cx + 6)} ${f(cy + r + 8)} L${f(cx)} ${f(cy + r + 18)} L${f(cx - 6)} ${f(cy + r + 8)} L${f(cx - 16)} ${f(cy + r + 16)}Z" fill="${o.ribbon}"/>`;
    return s;
  }
  function monogramRing(c, cx, cy, r, o = {}) {
    const st = o.color || '#B8955A';
    let s = `<circle cx="${f(cx)}" cy="${f(cy)}" r="${f(r)}" fill="none" stroke="${st}" stroke-width="${o.width || 1.2}"/><circle cx="${f(cx)}" cy="${f(cy)}" r="${f(r - 6)}" fill="none" stroke="${st}" stroke-width="0.6"/>`;
    for (let i = 0; i < 60; i++) { const p = pol(r + 6, i * 6); s += `<circle cx="${f(cx + p[0])}" cy="${f(cy + p[1])}" r="0.9" fill="${st}"/>`; }
    [[-90], [90]].forEach(([a]) => { const p = pol(r, a); s += `<path d="M${f(cx + p[0])} ${f(cy + p[1] - 6)} L${f(cx + p[0] + 5)} ${f(cy + p[1])} L${f(cx + p[0])} ${f(cy + p[1] + 6)} L${f(cx + p[0] - 5)} ${f(cy + p[1])}Z" fill="${o.bg || '#FFFFFF'}" stroke="${st}" stroke-width="1"/>`; });
    return s;
  }
  function crest(c, cx, cy, s, o = {}) {
    const st = o.color || '#B8955A', fill = o.fill || 'none';
    const shield = `M${f(cx - s * 0.5)} ${f(cy - s * 0.55)} L${f(cx + s * 0.5)} ${f(cy - s * 0.55)} L${f(cx + s * 0.5)} ${f(cy)} C${f(cx + s * 0.5)} ${f(cy + s * 0.4)} ${f(cx + s * 0.15)} ${f(cy + s * 0.6)} ${f(cx)} ${f(cy + s * 0.72)} C${f(cx - s * 0.15)} ${f(cy + s * 0.6)} ${f(cx - s * 0.5)} ${f(cy + s * 0.4)} ${f(cx - s * 0.5)} ${f(cy)}Z`;
    let out = `<path d="${shield}" fill="${fill}" stroke="${st}" stroke-width="1.6"/>`;
    out += `<path d="${shield}" transform="translate(${f(cx * 0.12)} ${f(cy * 0.12)}) scale(0.88)" fill="none" stroke="${st}" stroke-width="0.7"/>`;
    const cy0 = cy - s * 0.62;
    out += `<path d="M${f(cx - s * 0.3)} ${f(cy0)} L${f(cx - s * 0.34)} ${f(cy0 - s * 0.24)} L${f(cx - s * 0.16)} ${f(cy0 - s * 0.1)} L${f(cx)} ${f(cy0 - s * 0.3)} L${f(cx + s * 0.16)} ${f(cy0 - s * 0.1)} L${f(cx + s * 0.34)} ${f(cy0 - s * 0.24)} L${f(cx + s * 0.3)} ${f(cy0)}Z" fill="${o.crownFill || st}" stroke="${st}" stroke-width="0.8"/>`;
    [[-0.34, -0.24], [0, -0.3], [0.34, -0.24]].forEach(([a, b]) => { out += `<circle cx="${f(cx + s * a)}" cy="${f(cy0 + s * b - 3)}" r="2.4" fill="${st}"/>`; });
    out += laurel(c, cx, cy + s * 0.05, s * 0.78, { color: st, gap: 60, count: 8, leafLen: s * 0.17, flat: true });
    return out;
  }
  function sunburst(c, cx, cy, r1, r2, n, a0, a1, o = {}) {
    const st = o.color || '#C9A24A';
    let s = '';
    for (let i = 0; i < n; i++) {
      const a = a0 + (a1 - a0) * i / (n - 1), p1 = pol(r1, a), p2 = pol(i % 2 ? r2 * 0.82 : r2, a);
      s += `<line x1="${f(cx + p1[0])}" y1="${f(cy + p1[1])}" x2="${f(cx + p2[0])}" y2="${f(cy + p2[1])}" stroke="${st}" stroke-width="${o.width || 0.8}" stroke-opacity="${o.opacity || 0.9}"/>`;
    }
    if (o.arcs) for (let k = 1; k <= o.arcs; k++) { const rr = r1 * (0.4 + k * 0.2), p1 = pol(rr, a0), p2 = pol(rr, a1); s += `<path d="M${f(cx + p1[0])} ${f(cy + p1[1])} A${f(rr)} ${f(rr)} 0 0 1 ${f(cx + p2[0])} ${f(cy + p2[1])}" fill="none" stroke="${st}" stroke-width="0.8"/>`; }
    return s;
  }
  function decoCorner(c, x, y, s, o = {}) {
    const st = o.color || '#C9A24A', sx = o.flipX ? -1 : 1, sy = o.flipY ? -1 : 1;
    let p = '';
    for (let i = 0; i < 4; i++) { const k = s - i * s * 0.18; p += `<path d="M0 ${f(k)} L0 ${f(i * 6)} L${f(i * 6)} 0 L${f(k)} 0" transform="translate(${i * 5} ${i * 5})" fill="none" stroke="${st}" stroke-width="${i === 0 ? 1.4 : 0.7}"/>`; }
    p += `<path d="M${f(s * 0.32)} ${f(s * 0.32)} L${f(s * 0.42)} ${f(s * 0.22)} L${f(s * 0.52)} ${f(s * 0.32)} L${f(s * 0.42)} ${f(s * 0.42)}Z" fill="${st}"/>`;
    return `<g transform="translate(${f(x)} ${f(y)}) scale(${sx} ${sy})">${p}</g>`;
  }
  function pearlSwag(c, pts, r, o = {}) {
    const at = samplePath(pts, 0), L = at(1).len, n = Math.floor(L / (r * 2.1));
    const fill = c.radial([[0, '#FFFFFF'], [0.55, o.mid || '#F2EEF0'], [1, o.edge || '#C9C1CC']], { cx: 0.35, cy: 0.32, r: 0.75 });
    let s = '';
    for (let i = 0; i <= n; i++) { const p = at(i / n); s += `<circle cx="${f(p.x)}" cy="${f(p.y)}" r="${f(r)}" fill="${fill}" stroke="#BDB4BF" stroke-opacity="0.4" stroke-width="0.4"/>`; }
    return s;
  }
  function star8(c, cx, cy, r, o = {}) {
    const st = o.color || '#C9A24A', fill = o.fill || 'none';
    const sq = (k, a) => { const pts = []; for (let i = 0; i < 4; i++) { const p = pol(r * k, a + i * 90); pts.push(`${f(cx + p[0])},${f(cy + p[1])}`); } return pts.join(' '); };
    let s = `<polygon points="${sq(1, 0)}" fill="${fill}" stroke="${st}" stroke-width="1.2"/><polygon points="${sq(1, 45)}" fill="${fill}" stroke="${st}" stroke-width="1.2"/>`;
    s += `<circle cx="${f(cx)}" cy="${f(cy)}" r="${f(r * 0.5)}" fill="none" stroke="${st}" stroke-width="0.9"/><circle cx="${f(cx)}" cy="${f(cy)}" r="${f(r * 0.38)}" fill="none" stroke="${st}" stroke-width="0.6"/>`;
    for (let i = 0; i < 16; i++) { const p = pol(r * 0.44, i * 22.5); s += `<circle cx="${f(cx + p[0])}" cy="${f(cy + p[1])}" r="1.1" fill="${st}"/>`; }
    return s;
  }

  // ------------------------------------------------------------------
  // Cultural & thematic pattern bands (original geometric compositions)
  // ------------------------------------------------------------------
  function kitengeBand(c, x, y, w, h, o = {}) {
    const [bg, c1, c2, c3] = o.colors || ['#1F3A5F', '#E0A030', '#C4552D', '#F4E9D8'];
    let s = `<rect x="${f(x)}" y="${f(y)}" width="${f(w)}" height="${f(h)}" fill="${bg}"/>`;
    const n = Math.max(2, Math.round(w / h)), tw = w / n;
    for (let i = 0; i < n; i++) {
      const cx = x + tw * (i + 0.5), cy = y + h / 2, r = Math.min(tw, h) * 0.38;
      if (i % 2 === 0) {
        s += `<circle cx="${f(cx)}" cy="${f(cy)}" r="${f(r)}" fill="${c1}"/><circle cx="${f(cx)}" cy="${f(cy)}" r="${f(r * 0.68)}" fill="${bg}"/><circle cx="${f(cx)}" cy="${f(cy)}" r="${f(r * 0.42)}" fill="${c2}"/><circle cx="${f(cx)}" cy="${f(cy)}" r="${f(r * 0.16)}" fill="${c3}"/>`;
      } else {
        s += `<path d="M${f(cx)} ${f(cy - r)} L${f(cx + r)} ${f(cy)} L${f(cx)} ${f(cy + r)} L${f(cx - r)} ${f(cy)}Z" fill="${c2}"/><path d="M${f(cx)} ${f(cy - r * 0.55)} L${f(cx + r * 0.55)} ${f(cy)} L${f(cx)} ${f(cy + r * 0.55)} L${f(cx - r * 0.55)} ${f(cy)}Z" fill="${c3}"/><circle cx="${f(cx)}" cy="${f(cy)}" r="${f(r * 0.18)}" fill="${c1}"/>`;
      }
    }
    const zig = (yy, dir) => { let d = `M${f(x)} ${f(yy)}`; const st = h * 0.14; for (let k = 0; k <= w / st; k++) d += ` L${f(x + k * st)} ${f(yy + (k % 2 ? dir * st * 0.7 : 0))}`; return `<path d="${d}" fill="none" stroke="${c3}" stroke-width="1.1"/>`; };
    s += zig(y + 3, 1) + zig(y + h - 3, -1);
    return s;
  }
  function chevronBand(c, x, y, w, h, o = {}) {
    const [a, b] = o.colors || ['#0E0E0E', '#C9A24A'];
    let s = `<rect x="${f(x)}" y="${f(y)}" width="${f(w)}" height="${f(h)}" fill="${a}"/>`;
    const n = Math.round(w / h) * 2, tw = w / n;
    for (let i = 0; i < n; i++) {
      const xx = x + i * tw;
      s += i % 2 ? `<polygon points="${f(xx)},${f(y + h)} ${f(xx + tw / 2)},${f(y + 2)} ${f(xx + tw)},${f(y + h)}" fill="${b}"/>` : `<polygon points="${f(xx)},${f(y)} ${f(xx + tw / 2)},${f(y + h - 2)} ${f(xx + tw)},${f(y)}" fill="${b}" fill-opacity="0.55"/>`;
    }
    return s;
  }
  function mudcloth(c, x, y, w, h, o = {}) {
    const st = o.color || '#E8D9BF', rows = o.rows || 6, rh = h / rows;
    let s = '';
    for (let r = 0; r < rows; r++) {
      const yy = y + r * rh + rh / 2, kind = r % 4;
      if (r > 0) s += `<line x1="${f(x)}" y1="${f(y + r * rh)}" x2="${f(x + w)}" y2="${f(y + r * rh)}" stroke="${st}" stroke-width="0.8" stroke-opacity="0.7"/>`;
      for (let xx = x + rh * 0.5; xx < x + w; xx += rh * 0.9) {
        if (kind === 0) s += `<line x1="${f(xx - rh * 0.22)}" y1="${f(yy)}" x2="${f(xx + rh * 0.22)}" y2="${f(yy)}" stroke="${st}" stroke-width="2" stroke-linecap="round"/>`;
        else if (kind === 1) s += `<path d="M${f(xx - 4)} ${f(yy - 4)} L${f(xx + 4)} ${f(yy + 4)} M${f(xx + 4)} ${f(yy - 4)} L${f(xx - 4)} ${f(yy + 4)}" stroke="${st}" stroke-width="1.4" stroke-linecap="round"/>`;
        else if (kind === 2) s += `<circle cx="${f(xx)}" cy="${f(yy)}" r="2" fill="${st}"/><circle cx="${f(xx + rh * 0.45)}" cy="${f(yy)}" r="1" fill="${st}"/>`;
        else s += `<path d="M${f(xx)} ${f(yy - rh * 0.28)} L${f(xx + rh * 0.28)} ${f(yy)} L${f(xx)} ${f(yy + rh * 0.28)} L${f(xx - rh * 0.28)} ${f(yy)}Z" fill="none" stroke="${st}" stroke-width="1.1"/>`;
      }
    }
    return s;
  }
  function kangaFrame(c, x, y, w, h, band, o = {}) {
    const [bg, c1, c2] = o.colors || ['#7A1F3D', '#F2C14E', '#FFFFFF'];
    let s = `<path d="M${f(x)} ${f(y)} H${f(x + w)} V${f(y + h)} H${f(x)}Z M${f(x + band)} ${f(y + band)} V${f(y + h - band)} H${f(x + w - band)} V${f(y + band)}Z" fill="${bg}" fill-rule="evenodd"/>`;
    const drop = (cx, cy, ang, sz) => `<g transform="translate(${f(cx)} ${f(cy)}) rotate(${f(ang)})"><path d="M0 ${f(-sz)} C${f(sz * 0.75)} ${f(-sz * 0.3)} ${f(sz * 0.6)} ${f(sz * 0.6)} 0 ${f(sz * 0.55)} C${f(-sz * 0.75)} ${f(sz * 0.4)} ${f(-sz * 0.35)} ${f(-sz * 0.2)} ${f(sz * 0.1)} ${f(-sz * 0.25)}" fill="${c1}"/><circle cx="0" cy="${f(sz * 0.15)}" r="${f(sz * 0.18)}" fill="${c2}"/></g>`;
    const step = band * 0.9;
    for (let xx = x + band * 0.5; xx < x + w; xx += step) { s += drop(xx, y + band / 2, 30, band * 0.3) + drop(xx, y + h - band / 2, 210, band * 0.3); }
    for (let yy = y + band * 1.4; yy < y + h - band; yy += step) { s += drop(x + band / 2, yy, -60, band * 0.3) + drop(x + w - band / 2, yy, 120, band * 0.3); }
    s += `<rect x="${f(x + band + 4)}" y="${f(y + band + 4)}" width="${f(w - 2 * band - 8)}" height="${f(h - 2 * band - 8)}" fill="none" stroke="${c1}" stroke-width="1.2" stroke-dasharray="2 3"/>`;
    return s;
  }
  function swahiliDoor(c, x, y, w, h, o = {}) {
    const wood = o.wood || '#6B4A2F', panel = o.panel || '#FBF6EC', brass = c.gold('v'), b = o.border || 22;
    let s = `<path d="${archPath(x, y, w, h)}" fill="${wood}"/><path d="${archPath(x + b, y + b, w - 2 * b, h - b)}" fill="${panel}"/>`;
    const inner = arcPts(x + w / 2, y + w / 2, w / 2 - b / 2, 180, 360, 40);
    inner.forEach((p, i) => { s += `<ellipse cx="${f(p[0])}" cy="${f(p[1])}" rx="2.4" ry="4.2" transform="rotate(${f(180 + i * 4.6 + 90 + (i % 2 ? 25 : -25))} ${f(p[0])} ${f(p[1])})" fill="${lighten(wood, 0.35)}"/>`; });
    for (let yy = y + w / 2 + 8; yy < y + h - 6; yy += 9) { [x + b / 2, x + w - b / 2].forEach((xx, k) => { s += `<ellipse cx="${f(xx)}" cy="${f(yy)}" rx="2.4" ry="4.2" transform="rotate(${(Math.round(yy) % 2 ? 25 : -25) * (k ? -1 : 1)} ${f(xx)} ${f(yy)})" fill="${lighten(wood, 0.35)}"/>`; }); }
    const kx = x + w / 2, ky = y + 2;
    for (let i = 0; i < 9; i++) { const a = 180 + i * 22.5; s += `<path transform="translate(${f(kx)} ${f(ky + b * 0.9)}) rotate(${f(a + 90)})" d="${petalPath(b * 0.85, b * 0.22, 'point', null)}" fill="${brass}" fill-opacity="0.9"/>`; }
    s += `<circle cx="${f(kx)}" cy="${f(ky + b * 0.9)}" r="${f(b * 0.22)}" fill="${brass}"/>`;
    for (let yy = y + w / 2 + 30; yy < y + h - 10; yy += 46) { [x + b / 2, x + w - b / 2].forEach((xx) => { s += `<circle cx="${f(xx)}" cy="${f(yy)}" r="4.2" fill="${brass}"/>`; }); }
    return s;
  }
  function waves(c, x, y, w, rows, o = {}) {
    const cols = o.colors || ['#2E6E9E', '#5D9CC7', '#A9CDE5'], amp = o.amp || 6, len = o.len || 26;
    let s = '';
    for (let r = 0; r < rows; r++) {
      let d = `M${f(x)} ${f(y + r * amp * 2.2)}`;
      for (let xx = 0; xx < w; xx += len) d += ` q${f(len / 4)} ${f(-amp)} ${f(len / 2)} 0 q${f(len / 4)} ${f(amp)} ${f(len / 2)} 0`;
      s += `<path d="${d}" fill="none" stroke="${cols[r % cols.length]}" stroke-width="${o.width || 1.3}" stroke-linecap="round" stroke-opacity="${o.opacity || 0.85}"/>`;
    }
    return s;
  }
  function dhow(c, x, y, s, o = {}) {
    const hull = o.hull || '#5C3D2E', sail = o.sail || '#F5EEDC', st = o.stroke || '#8B6F4E';
    return `<g transform="translate(${f(x)} ${f(y)})"><path d="M${f(-s * 0.6)} 0 Q0 ${f(s * 0.22)} ${f(s * 0.62)} ${f(-s * 0.06)} L${f(s * 0.5)} ${f(s * 0.06)} Q0 ${f(s * 0.26)} ${f(-s * 0.48)} ${f(s * 0.08)}Z" fill="${hull}"/><line x1="0" y1="${f(s * 0.05)}" x2="${f(s * 0.04)}" y2="${f(-s * 0.95)}" stroke="${st}" stroke-width="1.4"/><path d="M${f(-s * 0.55)} ${f(-s * 0.15)} Q${f(-s * 0.05)} ${f(-s * 0.55)} ${f(s * 0.5)} ${f(-s * 1.0)} Q${f(s * 0.3)} ${f(-s * 0.45)} ${f(s * 0.04)} ${f(-s * 0.05)}Z" fill="${sail}" stroke="${st}" stroke-width="0.8"/></g>`;
  }

  // ------------------------------------------------------------------
  // Celebration icons
  // ------------------------------------------------------------------
  function balloon(c, x, y, r, color, o = {}) {
    const fill = c.radial([[0, lighten(color, 0.55)], [0.45, color], [1, darken(color, 0.22)]], { cx: 0.36, cy: 0.32, r: 0.75 });
    const sx = o.stringX ?? c.rand(-20, 20);
    return `<path d="M${f(x)} ${f(y + r * 1.18)} C${f(x + 8)} ${f(y + r * 1.8)} ${f(x - 8 + sx * 0.3)} ${f(y + r * 2.4)} ${f(x + sx)} ${f(y + r * 3.2)}" fill="none" stroke="${o.string || '#B8955A'}" stroke-width="0.8"/>` +
      `<ellipse cx="${f(x)}" cy="${f(y)}" rx="${f(r)}" ry="${f(r * 1.16)}" fill="${fill}"/><path d="M${f(x - 3.5)} ${f(y + r * 1.22)} L${f(x + 3.5)} ${f(y + r * 1.22)} L${f(x)} ${f(y + r * 1.12)}Z" fill="${darken(color, 0.2)}"/>` +
      `<ellipse cx="${f(x - r * 0.38)}" cy="${f(y - r * 0.45)}" rx="${f(r * 0.14)}" ry="${f(r * 0.24)}" transform="rotate(30 ${f(x - r * 0.38)} ${f(y - r * 0.45)})" fill="#FFFFFF" fill-opacity="0.55"/>`;
  }
  function confetti(c, x, y, w, h, n, colors) {
    let s = '';
    for (let i = 0; i < n; i++) {
      const cx = x + c.rand(0, w), cy = y + c.rand(0, h), col = c.pick(colors);
      s += c.r() > 0.45
        ? `<rect x="${f(cx)}" y="${f(cy)}" width="${f(c.rand(3, 7))}" height="${f(c.rand(1.5, 3))}" transform="rotate(${f(c.rand(0, 180))} ${f(cx)} ${f(cy)})" fill="${col}" fill-opacity="0.9"/>`
        : `<circle cx="${f(cx)}" cy="${f(cy)}" r="${f(c.rand(1.2, 3))}" fill="${col}" fill-opacity="0.85"/>`;
    }
    return s;
  }
  function mortarboard(c, cx, cy, s, o = {}) {
    const col = o.color || '#1B1B1F', gold = o.tassel || c.gold();
    return `<path d="M${f(cx)} ${f(cy - s * 0.3)} L${f(cx + s * 0.7)} ${f(cy)} L${f(cx)} ${f(cy + s * 0.3)} L${f(cx - s * 0.7)} ${f(cy)}Z" fill="${col}"/>` +
      `<path d="M${f(cx - s * 0.38)} ${f(cy + s * 0.14)} L${f(cx - s * 0.38)} ${f(cy + s * 0.42)} Q${f(cx)} ${f(cy + s * 0.62)} ${f(cx + s * 0.38)} ${f(cy + s * 0.42)} L${f(cx + s * 0.38)} ${f(cy + s * 0.14)} L${f(cx)} ${f(cy + s * 0.3)}Z" fill="${lighten(col, 0.08)}"/>` +
      `<path d="M${f(cx)} ${f(cy)} L${f(cx + s * 0.55)} ${f(cy + s * 0.08)} L${f(cx + s * 0.55)} ${f(cy + s * 0.5)}" fill="none" stroke="${gold}" stroke-width="1.6"/><path d="M${f(cx + s * 0.5)} ${f(cy + s * 0.5)} L${f(cx + s * 0.6)} ${f(cy + s * 0.5)} L${f(cx + s * 0.63)} ${f(cy + s * 0.72)} L${f(cx + s * 0.47)} ${f(cy + s * 0.72)}Z" fill="${gold}"/><circle cx="${f(cx)}" cy="${f(cy)}" r="2" fill="${gold}"/>`;
  }
  function rings(c, cx, cy, s, o = {}) {
    const st = o.color || c.gold();
    return `<circle cx="${f(cx - s * 0.22)}" cy="${f(cy)}" r="${f(s * 0.36)}" fill="none" stroke="${st}" stroke-width="${f(s * 0.06)}"/><circle cx="${f(cx + s * 0.22)}" cy="${f(cy + s * 0.04)}" r="${f(s * 0.36)}" fill="none" stroke="${st}" stroke-width="${f(s * 0.06)}"/>` +
      `<path d="M${f(cx - s * 0.36)} ${f(cy - s * 0.46)} L${f(cx - s * 0.3)} ${f(cy - s * 0.54)} L${f(cx - s * 0.14)} ${f(cy - s * 0.54)} L${f(cx - s * 0.08)} ${f(cy - s * 0.46)} L${f(cx - s * 0.22)} ${f(cy - s * 0.32)}Z" fill="#EAF2F8" stroke="${st}" stroke-width="0.8"/>`;
  }
  function geoLines(c, x, y, w, h, o = {}) {
    const st = o.color || '#C9A24A';
    let s = '';
    for (let i = 0; i < (o.count || 5); i++) {
      const a = o.angle ?? -35, off = i * (o.gap || 14);
      const p1 = [x + off, y + h], p2 = [x + off + Math.cos(rad(a)) * w * 1.6, y + h + Math.sin(rad(a)) * w * 1.6];
      s += `<line x1="${f(p1[0])}" y1="${f(p1[1])}" x2="${f(p2[0])}" y2="${f(p2[1])}" stroke="${st}" stroke-width="${i === 0 ? 1.4 : 0.6}" stroke-opacity="${1 - i * 0.12}"/>`;
    }
    return s;
  }

  // ------------------------------------------------------------------
  // Dress Code Vector Icons (10 bespoke couture/event icons)
  // ------------------------------------------------------------------
  function dressCodeIcon(type, color = '#B8955A') {
    const col = color || '#B8955A';
    switch (type) {
      case 'tuxedo':
        return `<svg viewBox="0 0 28 28" width="16" height="16" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M7 4L14 13L21 4L25 8V24H3V8L7 4Z" stroke="${col}" stroke-width="1.6" stroke-linejoin="round"/><path d="M10.5 8.5L14 10.5L17.5 8.5V11.5L14 9.5L10.5 11.5V8.5Z" fill="${col}"/><circle cx="14" cy="15.5" r="1" fill="${col}"/><circle cx="14" cy="19" r="1" fill="${col}"/></svg>`;
      case 'gown':
        return `<svg viewBox="0 0 28 28" width="16" height="16" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M10 4C11.5 6 16.5 6 18 4L16.5 11L23 24H5L11.5 11L10 4Z" stroke="${col}" stroke-width="1.6" stroke-linejoin="round"/><path d="M11.5 11H16.5" stroke="${col}" stroke-width="1.5"/></svg>`;
      case 'bowtie':
        return `<svg viewBox="0 0 28 28" width="16" height="16" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M3 8L12.5 14L3 20V8Z" fill="${col}" fill-opacity="0.2" stroke="${col}" stroke-width="1.6" stroke-linejoin="round"/><path d="M25 8L15.5 14L25 20V8Z" fill="${col}" fill-opacity="0.2" stroke="${col}" stroke-width="1.6" stroke-linejoin="round"/><rect x="12" y="11.5" width="4" height="5" rx="1" fill="${col}"/></svg>`;
      case 'crown':
        return `<svg viewBox="0 0 28 28" width="16" height="16" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4 20L6 9L11 14L14 7L17 14L22 9L24 20H4Z" fill="${col}" fill-opacity="0.18" stroke="${col}" stroke-width="1.6" stroke-linejoin="round"/><line x1="4" y1="23" x2="24" y2="23" stroke="${col}" stroke-width="1.6" stroke-linecap="round"/></svg>`;
      case 'floral':
        return `<svg viewBox="0 0 28 28" width="16" height="16" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4 22C10 18 16 12 24 6" stroke="${col}" stroke-width="1.5" stroke-linecap="round"/><path d="M11 15C9 11 11 7 15 8C14 12 13 14 11 15Z" fill="${col}" fill-opacity="0.28" stroke="${col}" stroke-width="1.2"/><path d="M17 12C21 11 23 13 21 17C18 16 17 14 17 12Z" fill="${col}" fill-opacity="0.28" stroke="${col}" stroke-width="1.2"/><circle cx="14" cy="14" r="2" fill="${col}"/></svg>`;
      case 'african_shield':
        return `<svg viewBox="0 0 28 28" width="16" height="16" fill="none" xmlns="http://www.w3.org/2000/svg"><polygon points="14,3 24,14 14,25 4,14" fill="${col}" fill-opacity="0.18" stroke="${col}" stroke-width="1.6"/><polygon points="14,8 19,14 14,20 9,14" stroke="${col}" stroke-width="1.2"/><circle cx="14" cy="14" r="2" fill="${col}"/></svg>`;
      case 'sparkles':
        return `<svg viewBox="0 0 28 28" width="16" height="16" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M14 3L16.2 11.8L25 14L16.2 16.2L14 25L11.8 16.2L3 14L11.8 11.8L14 3Z" fill="${col}" fill-opacity="0.22" stroke="${col}" stroke-width="1.5" stroke-linejoin="round"/></svg>`;
      case 'hanger':
        return `<svg viewBox="0 0 28 28" width="16" height="16" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M14 9C14 7.5 15.5 6.5 16.5 5.5C17.5 4.5 16.8 3 15 3C13.2 3 12.5 4.2 12.5 5" stroke="${col}" stroke-width="1.6" stroke-linecap="round"/><path d="M3 20L14 11L25 20H3Z" stroke="${col}" stroke-width="1.6" stroke-linejoin="round"/></svg>`;
      case 'diamond':
        return `<svg viewBox="0 0 28 28" width="16" height="16" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M8 5H20L25 11L14 24L3 11L8 5Z" fill="${col}" fill-opacity="0.16" stroke="${col}" stroke-width="1.5" stroke-linejoin="round"/><path d="M3 11H25" stroke="${col}" stroke-width="1.2"/></svg>`;
      default:
        return '';
    }
  }

  // ------------------------------------------------------------------
  // Independently Editable Standalone Decorative Vector Motifs
  // ------------------------------------------------------------------
  function standaloneMotifSvg(motifType, primary = '#B8955A', secondary = '#F5ECE0') {
    const c = new Ctx(motifType + primary, 140, 140);
    let body = '';
    switch (motifType) {
      case 'ivory_bloom_cluster':
        body += branch(c, [[20, 120], [70, 70], [120, 25]], { count: 7, leafLen: 20, colors: ['#95A78D', '#A7B89F'], stem: '#7A8972' });
        body += bloom(c, 68, 70, 38, { layers: 3, petals: [8, 7, 6], colors: { edge: '#FFFFFF', base: secondary, deep: '#DECBB4', center: primary } });
        body += bud(c, 108, 35, 14, -45, secondary);
        break;
      case 'gold_olive_sprig':
        body += olive(c, [[18, 118], [58, 76], [96, 42], [124, 18]], { count: 10, leafLen: 20, colors: [primary, lighten(primary, 0.25)], berries: 3, berryColor: secondary, stem: primary });
        break;
      case 'blush_rose_duo':
        body += branch(c, [[16, 110], [70, 70], [124, 30]], { count: 6, leafLen: 18, colors: ['#8EA48B', '#A4B8A0'], stem: '#6F846C' });
        body += peony(c, 58, 74, 34, { colors: { edge: '#FFF7F8', base: '#F4C2C9', deep: '#D48793', center: primary } });
        body += rose(c, 96, 48, 24, { colors: { edge: '#FFF5F6', base: '#EEB4BC', deep: '#C97A86' } });
        break;
      case 'eucalyptus_stem':
        body += eucalyptus(c, [[24, 124], [56, 82], [88, 46], [116, 16]], { count: 9, leafLen: 22, colors: ['#7D9B84', '#98B49E', '#64826B'], stem: '#5C7462' });
        break;
      case 'royal_crest_emblem':
        body += crest(c, 70, 68, 52, { color: primary, crownFill: primary });
        break;
      case 'african_diamond_seal':
        body += star8(c, 70, 70, 48, { color: primary, fill: secondary + '33' });
        break;
      case 'baroque_corner':
        body += cornerFlourish(c, 16, 16, 105, { color: primary, width: 1.5 });
        break;
      case 'pampas_plume':
        body += pampas(c, [[26, 126], [68, 74], [112, 22]], { strands: 38, len: 26, colors: ['#DFCBB2', '#CBB292', '#EFE3D2'] });
        break;
      case 'orchid_blossom':
        body += orchid(c, 70, 70, 44, { colors: { edge: '#FFFFFF', base: secondary, lip: primary, center: primary } });
        break;
      case 'diamond_rings':
        body += rings(c, 70, 76, 56, { color: primary });
        break;
      case 'sunburst_medallion':
        body += sunburst(c, 70, 70, 14, 58, 24, 0, 360, { color: primary, width: 1.0 });
        break;
      default:
        body += divider(c, 70, 70, 115, { color: primary, style: 'flourish', width: 1.3 });
        break;
    }
    return `<svg viewBox="0 0 140 140" width="100%" height="100%" xmlns="http://www.w3.org/2000/svg" style="display:block;overflow:visible;">${c.defsMarkup()}${body}</svg>`;
  }

  // ------------------------------------------------------------------
  // Sample Studio Portrait Generator (Replaceable Photo Placeholder)
  // Generates a high-fashion editorial portrait illustration data-URL
  // that users can replace with their own uploaded JPG/PNG/WebP photo.
  // ------------------------------------------------------------------
  function samplePortraitDataUrl(style = 'couple_floral', accent = '#B8955A', bg = '#F4EDE2') {
    const dark = darken(accent, 0.38);
    const soft = lighten(accent, 0.42);
    let art = '';
    if (style === 'corporate_speaker') {
      art = `
        <rect width="300" height="360" fill="${bg}"/>
        <circle cx="150" cy="145" r="110" fill="${soft}" fill-opacity="0.35"/>
        <circle cx="150" cy="122" r="44" fill="#D8B89A"/>
        <path d="M114 108 C118 76 182 76 186 108 C175 94 125 94 114 108Z" fill="#2A2421"/>
        <path d="M72 320 C76 226 112 192 150 192 C188 192 224 226 228 320Z" fill="#1E293B"/>
        <polygon points="150,192 132,258 150,285 168,258" fill="#F8FAFC"/>
        <polygon points="150,206 143,262 150,278 157,262" fill="${accent}"/>
      `;
    } else if (style === 'african_royalty') {
      art = `
        <rect width="300" height="360" fill="${bg}"/>
        <circle cx="150" cy="165" r="118" fill="${accent}" fill-opacity="0.18"/>
        <!-- Regal Gele / Headwrap & Agbada Silhouette -->
        <path d="M78 118 C82 54 218 54 222 118 C200 142 100 142 78 118Z" fill="${accent}"/>
        <path d="M92 112 C115 74 185 74 208 112" fill="none" stroke="#FFF8E7" stroke-width="2.5"/>
        <circle cx="150" cy="144" r="40" fill="#6E4730"/>
        <circle cx="106" cy="152" r="6" fill="#D4AF37"/>
        <circle cx="194" cy="152" r="6" fill="#D4AF37"/>
        <path d="M64 335 C74 232 108 202 150 202 C192 202 226 232 236 335Z" fill="${dark}"/>
        <path d="M116 205 Q150 245 184 205" fill="none" stroke="#D4AF37" stroke-width="5"/>
      `;
    } else if (style === 'celebrant_portrait') {
      art = `
        <rect width="300" height="360" fill="${bg}"/>
        <circle cx="150" cy="155" r="115" fill="${soft}" fill-opacity="0.35"/>
        <path d="M116 90 L126 68 L140 82 L150 62 L160 82 L174 68 L184 90Z" fill="${accent}"/>
        <circle cx="150" cy="134" r="42" fill="#C69472"/>
        <path d="M76 330 C84 228 114 196 150 196 C186 196 216 228 224 330Z" fill="${accent}"/>
        <path d="M112 200 Q150 238 188 200" fill="none" stroke="#FFFFFF" stroke-width="3"/>
      `;
    } else {
      // Romantic Couple Editorial Portrait
      art = `
        <rect width="300" height="360" fill="${bg}"/>
        <circle cx="150" cy="165" r="122" fill="${soft}" fill-opacity="0.32"/>
        <!-- Groom Silhouette -->
        <circle cx="122" cy="126" r="34" fill="#C89F81"/>
        <path d="M94 114 C98 86 146 86 150 114 C138 100 106 100 94 114Z" fill="#2C221E"/>
        <path d="M56 335 C62 232 90 186 126 186 C158 186 180 225 186 335Z" fill="#232020"/>
        <polygon points="124,186 112,238 124,256 136,238" fill="#FFFFFF"/>
        <!-- Bride Silhouette -->
        <circle cx="178" cy="142" r="32" fill="#D7B196"/>
        <path d="M150 132 C154 102 204 102 208 138 C196 120 162 120 150 132Z" fill="#3B2B23"/>
        <path d="M188 122 Q232 165 244 265" fill="none" stroke="#FFFFFF" stroke-width="14" stroke-opacity="0.55" stroke-linecap="round"/>
        <path d="M118 335 C124 242 150 202 180 202 C210 202 236 242 244 335Z" fill="#FAF6F0" stroke="${accent}" stroke-width="1.2"/>
        <!-- Mini floral bouquet -->
        <circle cx="164" cy="268" r="16" fill="${soft}"/>
        <circle cx="182" cy="274" r="14" fill="#FFFFFF"/>
        <circle cx="172" cy="258" r="12" fill="${accent}" fill-opacity="0.65"/>
      `;
    }
    const svg = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 300 360" width="300" height="360">${art}</svg>`;
    return 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(svg);
  }

  const LuxeArt = {
    Ctx, f, pol, rad, mix, lighten, darken, hashStr, rng, smoothPath, samplePath, stemPts, arcPts, petalPath, archPath,
    wash, splatter, goldFlakes, marble,
    bloom, rose, peony, magnolia, orchid, daisy, bud, protea, strelitzia,
    leaf, branch, eucalyptus, olive, fern, lavender, pampas, palmFrond, monstera, mapleLeaf, oakLeaf,
    frameRect, arch, cornerFlourish, fourCorners, divider, laurel, monogramRing, crest, sunburst, decoCorner, pearlSwag, star8,
    kitengeBand, chevronBand, mudcloth, kangaFrame, swahiliDoor, waves, dhow,
    balloon, confetti, mortarboard, rings, geoLines,
    dressCodeIcon, standaloneMotifSvg, samplePortraitDataUrl,
  };
  if (typeof module !== 'undefined' && module.exports) module.exports = LuxeArt;
  else root.LuxeArt = LuxeArt;
})(typeof window !== 'undefined' ? window : globalThis);
