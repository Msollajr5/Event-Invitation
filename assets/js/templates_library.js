/**
 * LEULA EVENTS — 50 PREMIUM LUXURY INVITATION TEMPLATES LIBRARY
 * ==============================================================
 * Inspired by haute-couture minimalist floral & editorial stationery:
 * - Warm ivory, champagne, blush, sage, marble, velvet burgundy & obsidian palettes
 * - Delicate multi-layered watercolour flowers & botanical branches (via LuxeArt SVG engine)
 * - Pointed-pen calligraphy paired with high-contrast editorial serifs
 * - Asymmetrical floral compositions, architectural arches, crests & African luxury motifs
 *
 * All 50 templates produce 100% original, multi-element vector SVG artwork
 * that renders live in the Invitation Card Designer and on public Guest Passes.
 */

const TEMPLATE_CATEGORIES = [
  { id: 'all',          name: 'All 50 Templates',        icon: '✨', count: 50 },
  { id: 'wedding',      name: 'Luxury Floral Weddings',  icon: '💍', count: 10 },
  { id: 'minimalist',   name: 'Minimalist & Editorial',  icon: '🏛️', count: 10 },
  { id: 'nature',       name: 'Botanical & Nature',      icon: '🌿', count: 10 },
  { id: 'classic',      name: 'Luxury & Classic',        icon: '👑', count: 8  },
  { id: 'african',      name: 'African Luxury',          icon: '🌍', count: 7  },
  { id: 'celebrations', name: 'Modern Celebrations',     icon: '🥂', count: 5  },
  { id: 'recent',       name: 'Recently Used',           icon: '🕒', count: 0  },
  { id: 'favorites',    name: 'Favourites',              icon: '⭐', count: 0  }
];

// ============================================================
// BESPOKE SVG ARTWORK COMPOSITIONS FOR ALL 50 TEMPLATES
// Each function uses LuxeArt primitives to compose a distinct
// 420x640 vector illustration tailored to the template.
// ============================================================
const ARTWORK_BUILDERS = {

  // ----------------------------------------------------------
  // COLLECTION A: LUXURY FLORAL WEDDINGS (1 - 10)
  // ----------------------------------------------------------

  // 1. Ivory Bloom — cream background with large delicate white flowers on the left
  wedding_ivory_bloom(L, c, W, H, p) {
    let s = '';
    s += L.wash(c, 75, 195, 125, 175, '#EDE1CF', 0.50);
    s += L.wash(c, 95, 420, 115, 155, '#E5D5BE', 0.42);
    s += L.wash(c, 355, 540, 95, 110, '#EFE5D6', 0.35);
    s += L.splatter(c, 110, 260, 130, 28, '#C8A97E', 0.35);
    // Sweeping botanical stems along left border
    s += L.eucalyptus(c, [[18, 560], [36, 430], [52, 290], [38, 140], [72, 48]], {
      count: 11, leafLen: 26, colors: ['#95A78D', '#A7B89F', '#7F9177', '#B8C6B0'], stem: '#7A8972'
    });
    s += L.olive(c, [[10, 390], [64, 320], [96, 235], [82, 125]], {
      count: 12, leafLen: 24, colors: ['#879678', '#9BA98C', '#748366'], berries: 4, berryColor: '#C5A572'
    });
    s += L.branch(c, [[30, 595], [80, 515], [135, 465]], {
      count: 8, leafLen: 22, colors: ['#A3B19B', '#8E9E85'], stem: '#847764'
    });
    // Large delicate white & ivory blooms cascading on the left
    s += L.bloom(c, 52, 185, 58, {
      layers: 4, petals: [9, 8, 7, 6], width: 0.58,
      colors: { edge: '#FFFFFF', base: '#F5ECE0', deep: '#DECBB4', center: '#C89D54' }, tilt: -15
    });
    s += L.bloom(c, 84, 305, 66, {
      layers: 4, petals: [10, 8, 7, 6], tip: 'ruffle', width: 0.62,
      colors: { edge: '#FFFFFF', base: '#F7EFE4', deep: '#DFCBB2', center: '#B88E4A' }, tilt: 18
    });
    s += L.peony(c, 44, 435, 52, {
      colors: { edge: '#FFFFFF', base: '#F3E8D8', deep: '#D7C1A6', center: '#C99E5A' }, tilt: -8
    });
    s += L.rose(c, 112, 225, 34, {
      colors: { edge: '#FFFFFF', base: '#EFE2D0', deep: '#CFB89E' }, tilt: 22
    });
    s += L.rose(c, 96, 390, 32, {
      colors: { edge: '#FFFFFF', base: '#F4E9DA', deep: '#D5BFA4' }, tilt: -20
    });
    s += L.bud(c, 88, 92, 20, -65, '#F5ECE0');
    s += L.bud(c, 128, 148, 17, -40, '#EFE3D3');
    s += L.bud(c, 118, 495, 19, 45, '#F5ECE0');
    // Subtle top-right & bottom-right echoes
    s += L.branch(c, [[402, 42], [358, 88], [322, 118]], {
      count: 6, leafLen: 18, colors: ['#A3B19B', '#B6C2AE'], stem: '#8A9682'
    });
    s += L.rose(c, 378, 66, 26, {
      colors: { edge: '#FFFFFF', base: '#F3E7D7', deep: '#D6C0A5' }
    });
    s += L.frameRect(c, 22, 22, W - 44, H - 44, { stroke: p.accent, width: 0.7 });
    return s;
  },

  // 2. Champagne Romance — champagne and beige watercolor flowers
  wedding_champagne_romance(L, c, W, H, p) {
    let s = '';
    s += L.wash(c, 95, 115, 145, 120, '#E8D5B7', 0.48);
    s += L.wash(c, 330, 525, 150, 125, '#E4CEAC', 0.46);
    s += L.goldFlakes(c, 25, 25, 180, 180, 22);
    s += L.goldFlakes(c, 220, 440, 175, 175, 22);
    // Upper-left champagne cascade
    s += L.eucalyptus(c, [[15, 175], [75, 110], [160, 62], [245, 36]], {
      count: 10, leafLen: 23, colors: ['#B5A48B', '#C8B89E', '#9E8D74'], stem: '#8C7A60'
    });
    s += L.peony(c, 68, 82, 56, {
      colors: { edge: '#FFFDF9', base: '#EDD9BE', deep: '#C8A87E', center: '#B8893D' }
    });
    s += L.rose(c, 138, 62, 38, {
      colors: { edge: '#FFF9F0', base: '#E5CCA8', deep: '#BE9B6E' }
    });
    s += L.bloom(c, 46, 154, 38, {
      colors: { edge: '#FFFFFF', base: '#F1E2CC', deep: '#CFB089', center: '#B68B42' }
    });
    // Lower-right champagne cascade
    s += L.eucalyptus(c, [[405, 460], [345, 530], [260, 578], [175, 604]], {
      count: 10, leafLen: 23, colors: ['#B5A48B', '#C8B89E', '#9E8D74'], stem: '#8C7A60'
    });
    s += L.peony(c, 352, 556, 58, {
      colors: { edge: '#FFFDF9', base: '#EDD9BE', deep: '#C8A87E', center: '#B8893D' }
    });
    s += L.rose(c, 282, 578, 38, {
      colors: { edge: '#FFF9F0', base: '#E5CCA8', deep: '#BE9B6E' }
    });
    s += L.bloom(c, 374, 484, 36, {
      colors: { edge: '#FFFFFF', base: '#F1E2CC', deep: '#CFB089', center: '#B68B42' }
    });
    s += L.frameRect(c, 26, 26, W - 52, H - 52, { stroke: c.gold(), width: 1.0, double: 5 });
    return s;
  },

  // 3. Blush Petals — soft blush pink flowers with elegant calligraphy
  wedding_blush_petals(L, c, W, H, p) {
    let s = '';
    s += L.wash(c, 210, 95, 175, 95, '#F7D9DC', 0.48);
    s += L.wash(c, 210, 555, 165, 90, '#F5D0D6', 0.45);
    s += L.splatter(c, 210, 105, 140, 24, '#D99CA5', 0.38);
    // Top floral garland
    s += L.branch(c, [[35, 118], [120, 74], [210, 58], [300, 74], [385, 118]], {
      count: 14, leafLen: 22, colors: ['#8EA48B', '#A4B8A0', '#7B9178'], stem: '#6F846C'
    });
    s += L.peony(c, 210, 68, 52, {
      colors: { edge: '#FFF7F8', base: '#F4C2C9', deep: '#D48793', center: '#D4A857' }
    });
    s += L.rose(c, 134, 78, 36, {
      colors: { edge: '#FFF5F6', base: '#EEB4BC', deep: '#C97A86' }
    });
    s += L.rose(c, 286, 78, 36, {
      colors: { edge: '#FFF5F6', base: '#EEB4BC', deep: '#C97A86' }
    });
    s += L.bud(c, 82, 96, 18, -145, '#F2BAC2');
    s += L.bud(c, 338, 96, 18, -35, '#F2BAC2');
    // Bottom floral garland
    s += L.branch(c, [[55, 540], [140, 578], [210, 590], [280, 578], [365, 540]], {
      count: 12, leafLen: 20, colors: ['#8EA48B', '#A4B8A0'], stem: '#6F846C'
    });
    s += L.peony(c, 210, 580, 44, {
      colors: { edge: '#FFF7F8', base: '#F4C2C9', deep: '#D48793', center: '#D4A857' }
    });
    s += L.rose(c, 148, 572, 28, { colors: { edge: '#FFF5F6', base: '#EEB4BC', deep: '#C97A86' } });
    s += L.rose(c, 272, 572, 28, { colors: { edge: '#FFF5F6', base: '#EEB4BC', deep: '#C97A86' } });
    s += L.frameRect(c, 20, 20, W - 40, H - 40, { stroke: '#DCA5AD', width: 0.8 });
    return s;
  },

  // 4. White Orchid — minimalist white orchids and fine typography
  wedding_white_orchid(L, c, W, H, p) {
    let s = '';
    s += L.wash(c, 335, 210, 110, 165, '#EAE4DC', 0.42);
    // Graceful arching phalaenopsis orchid stem from top-right
    const stem = [[395, 28], [365, 115], [335, 215], [352, 325], [325, 420]];
    s += `<path d="${L.smoothPath(stem)}" fill="none" stroke="#6E7C64" stroke-width="2.0" stroke-linecap="round"/>`;
    // Basal orchid leaves
    s += L.leaf(c, 392, 42, 78, 22, 145, '#5E7254', { type: 'round' });
    s += L.leaf(c, 398, 56, 64, 18, 120, '#728668', { type: 'round' });
    // Cascading white orchids
    s += L.orchid(c, 355, 108, 42, {
      colors: { edge: '#FFFFFF', base: '#F7F3EE', lip: '#C99B6B', center: '#E5BE67' }, tilt: -22
    });
    s += L.orchid(c, 334, 195, 46, {
      colors: { edge: '#FFFFFF', base: '#F5F0E8', lip: '#C49260', center: '#E5BE67' }, tilt: 14
    });
    s += L.orchid(c, 352, 286, 40, {
      colors: { edge: '#FFFFFF', base: '#F7F3EE', lip: '#C99B6B', center: '#E5BE67' }, tilt: -16
    });
    s += L.orchid(c, 332, 368, 34, {
      colors: { edge: '#FFFFFF', base: '#F7F3EE', lip: '#C99B6B', center: '#E5BE67' }, tilt: 18
    });
    s += L.bud(c, 322, 422, 16, 110, '#F5F0E8');
    s += L.bud(c, 316, 444, 12, 125, '#EAE2D6');
    // Fine minimalist editorial frame offset left
    s += L.frameRect(c, 24, 24, W - 48, H - 48, { stroke: '#C8BFA8', width: 0.75 });
    s += `<line x1="42" y1="60" x2="42" y2="${H - 60}" stroke="#C8BFA8" stroke-width="0.6"/>`;
    return s;
  },

  // 5. Golden Botanical — ivory background with subtle gold botanical decorations
  wedding_golden_botanical(L, c, W, H, p) {
    let s = '';
    const g = c.gold();
    s += L.wash(c, 210, 320, 175, 230, '#F3E7D1', 0.35);
    s += L.goldFlakes(c, 30, 30, W - 60, H - 60, 32);
    s += L.frameRect(c, 22, 22, W - 44, H - 44, { stroke: g, width: 1.2, double: 6 });
    // Metallic gold foliage boughs
    s += L.branch(c, [[24, 210], [52, 120], [118, 52], [195, 28]], {
      count: 13, leafLen: 25, colors: ['#D4AF37', '#E6C86E', '#B8892E'], stem: '#B8892E', pairs: true
    });
    s += L.branch(c, [[W - 24, 210], [W - 52, 120], [W - 118, 52], [W - 195, 28]], {
      count: 13, leafLen: 25, colors: ['#D4AF37', '#E6C86E', '#B8892E'], stem: '#B8892E', pairs: true
    });
    s += L.branch(c, [[24, H - 210], [52, H - 120], [118, H - 52], [195, H - 28]], {
      count: 11, leafLen: 23, colors: ['#D4AF37', '#E6C86E', '#B8892E'], stem: '#B8892E', pairs: true
    });
    s += L.branch(c, [[W - 24, H - 210], [W - 52, H - 120], [W - 118, H - 52], [W - 195, H - 28]], {
      count: 11, leafLen: 23, colors: ['#D4AF37', '#E6C86E', '#B8892E'], stem: '#B8892E', pairs: true
    });
    s += L.divider(c, W / 2, 46, 120, { color: g, style: 'diamond' });
    s += L.divider(c, W / 2, H - 46, 120, { color: g, style: 'diamond' });
    return s;
  },

  // 6. Beige Elegance — neutral beige and cream floral composition
  wedding_beige_elegance(L, c, W, H, p) {
    let s = '';
    s += L.wash(c, 80, 120, 130, 130, '#DFCBB2', 0.45);
    s += L.wash(c, 345, 515, 135, 135, '#DFCBB2', 0.45);
    s += L.arch(c, 34, 34, W - 68, H - 68, { fill: '#FAF5EE', fillOpacity: 0.65, stroke: '#BFA78A', width: 1.0, inner: 6 });
    // Dried neutral florals + pampas + cream roses
    s += L.pampas(c, [[22, 230], [54, 145], [105, 65]], { colors: ['#D8C3A8', '#C7AF91', '#E6D6C1'], density: 55 });
    s += L.bloom(c, 68, 108, 48, {
      colors: { edge: '#FAF4EC', base: '#E2CEB5', deep: '#BFA382', center: '#9E7F5B' }
    });
    s += L.rose(c, 122, 74, 32, {
      colors: { edge: '#FFFDF9', base: '#EADAC5', deep: '#C6AC8D' }
    });
    s += L.pampas(c, [[398, 420], [366, 505], [315, 580]], { colors: ['#D8C3A8', '#C7AF91', '#E6D6C1'], density: 55 });
    s += L.bloom(c, 352, 535, 48, {
      colors: { edge: '#FAF4EC', base: '#E2CEB5', deep: '#BFA382', center: '#9E7F5B' }
    });
    s += L.rose(c, 298, 568, 32, {
      colors: { edge: '#FFFDF9', base: '#EADAC5', deep: '#C6AC8D' }
    });
    return s;
  },

  // 7. Rose Garden — soft pink roses and elegant serif lettering
  wedding_rose_garden(L, c, W, H, p) {
    let s = '';
    s += L.wash(c, 70, 90, 120, 110, '#F5D5D8', 0.45);
    s += L.wash(c, 350, 90, 120, 110, '#F5D5D8', 0.45);
    s += L.wash(c, 70, 550, 120, 110, '#F5D5D8', 0.45);
    s += L.wash(c, 350, 550, 120, 110, '#F5D5D8', 0.45);
    s += L.frameRect(c, 26, 26, W - 52, H - 52, { stroke: '#C89B7B', width: 0.9, double: 5 });
    // Four corners climbing garden roses
    const corners = [
      { x: 56, y: 62, bx: [[26, 175], [48, 95], [135, 34]] },
      { x: W - 56, y: 62, bx: [[W - 26, 175], [W - 48, 95], [W - 135, 34]] },
      { x: 56, y: H - 62, bx: [[26, H - 175], [48, H - 95], [135, H - 34]] },
      { x: W - 56, y: H - 62, bx: [[W - 26, H - 175], [W - 48, H - 95], [W - 135, H - 34]] }
    ];
    corners.forEach((cn, idx) => {
      s += L.branch(c, cn.bx, { count: 9, leafLen: 20, colors: ['#6E8B6B', '#88A384', '#5A7557'], stem: '#5A7557' });
      s += L.rose(c, cn.x, cn.y, 38, {
        colors: { edge: '#FFF4F5', base: '#F0B6BE', deep: '#C87380' }, tilt: idx * 35
      });
      s += L.rose(c, cn.x + (idx % 2 ? -42 : 42), cn.y + (idx < 2 ? -14 : 14), 24, {
        colors: { edge: '#FFF9FA', base: '#F6CED3', deep: '#D88E99' }
      });
    });
    return s;
  },

  // 8. Magnolia Dreams — white magnolias with delicate branches
  wedding_magnolia_dreams(L, c, W, H, p) {
    let s = '';
    s += L.wash(c, 130, 115, 155, 115, '#EFE2D6', 0.46);
    s += L.wash(c, 310, 535, 140, 105, '#EFE2D6', 0.42);
    // Woody magnolia bough from top-left
    const bough1 = [[0, 155], [68, 122], [148, 86], [235, 68], [305, 42]];
    s += `<path d="${L.smoothPath(bough1)}" fill="none" stroke="#6B4F3B" stroke-width="3.2" stroke-linecap="round"/>`;
    s += `<path d="${L.smoothPath([[110, 102], [155, 132], [202, 138]])}" fill="none" stroke="#6B4F3B" stroke-width="1.8" stroke-linecap="round"/>`;
    s += L.leaf(c, 55, 128, 36, 16, 45, '#5E7352', { veinColor: '#98AC8C' });
    s += L.leaf(c, 185, 78, 34, 15, -35, '#6B825E', { veinColor: '#98AC8C' });
    s += L.magnolia(c, 92, 105, 54, { tilt: 12 });
    s += L.magnolia(c, 168, 82, 44, { tilt: -18 });
    s += L.bud(c, 248, 62, 22, -15, '#F5E4DC');
    // Lower-right woody magnolia branch
    const bough2 = [[W, 515], [345, 545], [268, 575], [190, 595]];
    s += `<path d="${L.smoothPath(bough2)}" fill="none" stroke="#6B4F3B" stroke-width="2.8" stroke-linecap="round"/>`;
    s += L.leaf(c, 360, 538, 34, 15, -135, '#5E7352', { veinColor: '#98AC8C' });
    s += L.magnolia(c, 322, 552, 50, { tilt: -14 });
    s += L.bud(c, 245, 582, 20, -165, '#F5E4DC');
    s += L.frameRect(c, 22, 22, W - 44, H - 44, { stroke: '#C6B099', width: 0.75 });
    return s;
  },

  // 9. Vintage Floral — antique floral illustrations and classic typography
  wedding_vintage_floral(L, c, W, H, p) {
    let s = '';
    s += L.wash(c, 210, 320, 190, 260, '#E8DAC3', 0.38);
    s += L.frameRect(c, 20, 20, W - 40, H - 40, { stroke: '#9E7E56', width: 1.1, double: 5 });
    s += L.fourCorners(c, 28, 28, W - 56, H - 56, 44, { color: '#9E7E56', width: 1.0 });
    // Antique muted mauve & dusty ochre roses top & bottom
    s += L.branch(c, [[95, 68], [165, 52], [255, 52], [325, 68]], {
      count: 10, leafLen: 18, colors: ['#7A8468', '#8F987D'], stem: '#697258'
    });
    s += L.rose(c, 210, 56, 34, {
      colors: { edge: '#F5E6DF', base: '#CFA39A', deep: '#9E6B62' }
    });
    s += L.daisy(c, 158, 60, 22, { colors: { edge: '#FAF0E1', base: '#E2CEB2', center: '#B5894C' } });
    s += L.daisy(c, 262, 60, 22, { colors: { edge: '#FAF0E1', base: '#E2CEB2', center: '#B5894C' } });
    s += L.branch(c, [[115, H - 62], [210, H - 48], [305, H - 62]], {
      count: 8, leafLen: 17, colors: ['#7A8468', '#8F987D'], stem: '#697258'
    });
    s += L.rose(c, 210, H - 54, 30, {
      colors: { edge: '#F5E6DF', base: '#CFA39A', deep: '#9E6B62' }
    });
    return s;
  },

  // 10. Soft Peony — watercolor peonies with romantic styling
  wedding_soft_peony(L, c, W, H, p) {
    let s = '';
    s += L.wash(c, 85, 520, 145, 130, '#F7D6DA', 0.50);
    s += L.wash(c, 340, 110, 130, 115, '#F5DCE0', 0.45);
    s += L.splatter(c, 105, 500, 130, 26, '#DF9AA4', 0.40);
    // Lush bottom-left peony bouquet
    s += L.eucalyptus(c, [[12, 410], [48, 495], [115, 565], [215, 605]], {
      count: 10, leafLen: 24, colors: ['#8FA893', '#A3B9A6', '#79937E'], stem: '#6E8572'
    });
    s += L.peony(c, 74, 540, 64, {
      colors: { edge: '#FFF8F9', base: '#F6C4CB', deep: '#D98693', center: '#E5B968' }
    });
    s += L.peony(c, 148, 578, 46, {
      colors: { edge: '#FFFFFF', base: '#FADCE0', deep: '#E09CA7', center: '#E5B968' }
    });
    s += L.rose(c, 44, 468, 34, {
      colors: { edge: '#FFF6F7', base: '#F2B8C0', deep: '#CC7B87' }
    });
    // Top-right romantic peony accent
    s += L.eucalyptus(c, [[406, 200], [372, 125], [305, 60], [235, 32]], {
      count: 8, leafLen: 22, colors: ['#8FA893', '#A3B9A6'], stem: '#6E8572'
    });
    s += L.peony(c, 354, 88, 54, {
      colors: { edge: '#FFF8F9', base: '#F6C4CB', deep: '#D98693', center: '#E5B968' }
    });
    s += L.bud(c, 290, 52, 20, -140, '#F6C4CB');
    s += L.frameRect(c, 24, 24, W - 48, H - 48, { stroke: '#DFB3B9', width: 0.8 });
    return s;
  },

  // ----------------------------------------------------------
  // COLLECTION B: MINIMALIST AND EDITORIAL (11 - 20)
  // ----------------------------------------------------------

  // 11. Modern Ivory — clean ivory background and sophisticated typography
  minimal_modern_ivory(L, c, W, H, p) {
    let s = '';
    s += L.frameRect(c, 28, 28, W - 56, H - 56, { stroke: '#C5B49A', width: 0.9 });
    s += L.frameRect(c, 36, 36, W - 72, H - 72, { stroke: '#DCD0BE', width: 0.5 });
    s += L.divider(c, W / 2, 72, 90, { color: '#B69D78', style: 'diamond' });
    s += L.divider(c, W / 2, H - 64, 90, { color: '#B69D78', style: 'diamond' });
    // Single delicate gold-stem olive twig at top center
    s += L.olive(c, [[175, 54], [210, 48], [245, 54]], {
      count: 6, leafLen: 14, leafW: 4, colors: ['#B69D78', '#CDB693'], stem: '#A68B64', berries: 0
    });
    return s;
  },

  // 12. The Editorial — magazine-inspired invitation layout
  minimal_the_editorial(L, c, W, H, p) {
    let s = '';
    // Fashion magazine ruled grid lines & Roman numeral watermark
    s += `<rect x="20" y="20" width="${W - 40}" height="${H - 40}" fill="none" stroke="#18181B" stroke-width="1.4"/>`;
    s += `<line x1="20" y1="88" x2="${W - 20}" y2="88" stroke="#18181B" stroke-width="0.7"/>`;
    s += `<line x1="20" y1="${H - 72}" x2="${W - 20}" y2="${H - 72}" stroke="#18181B" stroke-width="0.7"/>`;
    s += `<line x1="68" y1="20" x2="68" y2="88" stroke="#18181B" stroke-width="0.7"/>`;
    s += `<line x1="${W - 68}" y1="20" x2="${W - 68}" y2="88" stroke="#18181B" stroke-width="0.7"/>`;
    s += `<text x="44" y="59" text-anchor="middle" font-family="'Cinzel', serif" font-size="11" letter-spacing="2" fill="#18181B" opacity="0.75">N°1</text>`;
    s += `<text x="${W - 44}" y="59" text-anchor="middle" font-family="'Cinzel', serif" font-size="11" letter-spacing="2" fill="#18181B" opacity="0.75">XXVI</text>`;
    s += `<text x="${W / 2}" y="355" text-anchor="middle" font-family="'Playfair Display', serif" font-size="170" font-style="italic" fill="#18181B" opacity="0.035">&amp;</text>`;
    return s;
  },

  // 13. Minimal Black — white background with elegant black typography
  minimal_minimal_black(L, c, W, H, p) {
    let s = '';
    s += L.frameRect(c, 22, 22, W - 44, H - 44, { stroke: '#111111', width: 1.8 });
    s += L.frameRect(c, 29, 29, W - 58, H - 58, { stroke: '#111111', width: 0.5 });
    // Crisp architectural corner squares
    [[22, 22], [W - 34, 22], [22, H - 34], [W - 34, H - 34]].forEach(([x, y]) => {
      s += `<rect x="${x}" y="${y}" width="12" height="12" fill="none" stroke="#111111" stroke-width="1.2"/>`;
    });
    s += L.divider(c, W / 2, 58, 110, { color: '#111111', style: 'diamond', width: 0.8 });
    s += L.divider(c, W / 2, H - 50, 110, { color: '#111111', style: 'diamond', width: 0.8 });
    return s;
  },

  // 14. Soft Typography — large calligraphy names with small serif details
  minimal_soft_typography(L, c, W, H, p) {
    let s = '';
    s += L.wash(c, 210, 185, 165, 110, '#EFE4D6', 0.45);
    s += L.wash(c, 210, 480, 155, 100, '#EADBC8', 0.35);
    // Flowing calligraphic flourish ribbon across top & bottom
    s += `<path d="M35 76 C115 32, 305 112, 385 62" fill="none" stroke="#C5A47E" stroke-width="1.0" stroke-opacity="0.7"/>`;
    s += `<path d="M55 84 C135 44, 285 118, 365 72" fill="none" stroke="#C5A47E" stroke-width="0.5" stroke-opacity="0.5"/>`;
    s += `<path d="M35 ${H - 62} C115 ${H - 108}, 305 ${H - 28}, 385 ${H - 76}" fill="none" stroke="#C5A47E" stroke-width="1.0" stroke-opacity="0.7"/>`;
    s += L.divider(c, W / 2, 68, 70, { color: '#B5926B', style: 'dots' });
    s += L.frameRect(c, 24, 24, W - 48, H - 48, { stroke: '#DFD2C0', width: 0.7 });
    return s;
  },

  // 15. Modern Arch — architectural arch shapes and neutral colours
  minimal_modern_arch(L, c, W, H, p) {
    let s = '';
    // Terracotta/sand double offset arches + minimalist botanical sprig
    s += L.arch(c, 48, 46, W - 84, H - 78, { fill: '#E7D7C5', fillOpacity: 0.55 });
    s += L.arch(c, 36, 34, W - 72, H - 68, { fill: '#FBF7F2', fillOpacity: 0.92, stroke: '#B89A7A', width: 1.2, inner: 7 });
    // Sun disc + minimalist olive branch on the arch shoulder
    s += `<circle cx="96" cy="145" r="28" fill="#DFC3A4" fill-opacity="0.55"/>`;
    s += L.olive(c, [[52, 235], [74, 165], [118, 105]], {
      count: 10, leafLen: 22, colors: ['#8C7A65', '#A6927C', '#73624F'], stem: '#6E5D4B', berries: 2, berryColor: '#B89A7A'
    });
    return s;
  },

  // 16. The Monogram — personalized initials with an elegant frame
  minimal_the_monogram(L, c, W, H, p) {
    let s = '';
    const g = '#B8955A';
    s += L.frameRect(c, 24, 24, W - 48, H - 48, { stroke: g, width: 1.1, double: 6 });
    s += L.fourCorners(c, 30, 30, W - 60, H - 60, 36, { color: g, width: 0.9 });
    // Ornate monogram medallion at top center
    s += L.monogramRing(c, W / 2, 82, 34, { color: g, bg: p.bg || '#FAF7F2', width: 1.2 });
    s += L.laurel(c, W / 2, 82, 46, { color: g, count: 9, leafLen: 11, gap: 38, flat: true });
    s += `<text x="${W / 2}" y="89" text-anchor="middle" font-family="'Cormorant Garamond', serif" font-size="22" font-weight="600" letter-spacing="2" fill="#5A4630">L &amp; E</text>`;
    return s;
  },

  // 17. Minimal Beige — warm beige and simple typography
  minimal_minimal_beige(L, c, W, H, p) {
    let s = '';
    s += `<rect x="26" y="26" width="${W - 52}" height="${H - 52}" rx="14" fill="#F9F4EC" fill-opacity="0.78" stroke="#C4AF94" stroke-width="0.9"/>`;
    s += L.wash(c, 85, 85, 95, 95, '#DFCCB4', 0.40);
    s += L.wash(c, W - 85, H - 85, 95, 95, '#DFCCB4', 0.40);
    s += L.divider(c, W / 2, 56, 80, { color: '#A38B6D', style: 'dots' });
    s += L.divider(c, W / 2, H - 52, 80, { color: '#A38B6D', style: 'dots' });
    return s;
  },

  // 18. Classic White — pure white with fine ornamental details
  minimal_classic_white(L, c, W, H, p) {
    let s = '';
    const st = '#B8A992';
    s += L.frameRect(c, 20, 20, W - 40, H - 40, { stroke: st, width: 0.8, double: 5 });
    s += L.fourCorners(c, 26, 26, W - 52, H - 52, 48, { color: st, width: 0.9 });
    s += L.divider(c, W / 2, 56, 130, { color: st, style: 'flourish', width: 0.85 });
    s += L.divider(c, W / 2, H - 52, 130, { color: st, style: 'flourish', width: 0.85 });
    return s;
  },

  // 19. Contemporary Line — minimalist geometric lines
  minimal_contemporary_line(L, c, W, H, p) {
    let s = '';
    const st = '#B58E65';
    s += L.geoLines(c, 16, 16, 140, 140, { color: st, count: 6, gap: 13, angle: -38 });
    s += L.geoLines(c, W - 156, H - 156, 140, 140, { color: st, count: 6, gap: 13, angle: -38 });
    // Asymmetric intersecting geometric frames
    s += `<rect x="24" y="34" width="${W - 60}" height="${H - 58}" fill="none" stroke="#2B2623" stroke-width="0.9"/>`;
    s += `<rect x="36" y="22" width="${W - 60}" height="${H - 58}" fill="none" stroke="${st}" stroke-width="0.8"/>`;
    s += `<circle cx="${W - 54}" cy="68" r="18" fill="none" stroke="${st}" stroke-width="0.9"/>`;
    s += `<circle cx="54" cy="${H - 68}" r="18" fill="none" stroke="${st}" stroke-width="0.9"/>`;
    return s;
  },

  // 20. Timeless Elegance — classic serif typography and subtle decoration
  minimal_timeless_elegance(L, c, W, H, p) {
    let s = '';
    const st = '#A98E67';
    // Beveled heritage passe-partout frame
    s += L.frameRect(c, 22, 22, W - 44, H - 44, { stroke: st, width: 1.2 });
    s += L.frameRect(c, 30, 30, W - 60, H - 60, { stroke: st, width: 0.6 });
    [[22, 22, 30, 30], [W - 22, 22, W - 30, 30], [22, H - 22, 30, H - 30], [W - 22, H - 22, W - 30, H - 30]].forEach(([x1, y1, x2, y2]) => {
      s += `<line x1="${x1}" y1="${y1}" x2="${x2}" y2="${y2}" stroke="${st}" stroke-width="0.9"/>`;
    });
    s += L.divider(c, W / 2, 54, 140, { color: st, style: 'flourish', width: 0.9 });
    s += L.divider(c, W / 2, H - 50, 140, { color: st, style: 'flourish', width: 0.9 });
    return s;
  },

  // ----------------------------------------------------------
  // COLLECTION C: BOTANICAL AND NATURE (21 - 30)
  // ----------------------------------------------------------

  // 21. Eucalyptus Garden — sage green eucalyptus branches
  nature_eucalyptus_garden(L, c, W, H, p) {
    let s = '';
    s += L.wash(c, 100, 110, 150, 115, '#D5E2D4', 0.48);
    s += L.wash(c, 320, 535, 150, 115, '#D5E2D4', 0.45);
    // Silver-dollar eucalyptus boughs draping from top corners
    s += L.eucalyptus(c, [[12, 210], [45, 125], [115, 62], [205, 34]], {
      count: 11, leafLen: 26, colors: ['#7E9B84', '#95B09A', '#6A8770', '#AEC4B2'], stem: '#5D7562'
    });
    s += L.eucalyptus(c, [[W - 12, 195], [W - 48, 118], [W - 122, 58], [W - 200, 36]], {
      count: 10, leafLen: 24, colors: ['#7E9B84', '#95B09A', '#6A8770', '#AEC4B2'], stem: '#5D7562'
    });
    // Bottom eucalyptus sprigs
    s += L.eucalyptus(c, [[18, H - 150], [72, H - 75], [168, H - 36]], {
      count: 8, leafLen: 22, colors: ['#7E9B84', '#95B09A', '#AEC4B2'], stem: '#5D7562'
    });
    s += L.eucalyptus(c, [[W - 18, H - 150], [W - 72, H - 75], [W - 168, H - 36]], {
      count: 8, leafLen: 22, colors: ['#7E9B84', '#95B09A', '#AEC4B2'], stem: '#5D7562'
    });
    s += L.frameRect(c, 24, 24, W - 48, H - 48, { stroke: '#8BA38F', width: 0.85 });
    return s;
  },

  // 22. Olive Branch — minimalist olive leaves
  nature_olive_branch(L, c, W, H, p) {
    let s = '';
    s += L.wash(c, 210, 95, 160, 85, '#E4E8D8', 0.45);
    // Crossed Mediterranean olive branches at top + bottom
    s += L.olive(c, [[55, 115], [130, 74], [220, 52], [315, 44]], {
      count: 16, leafLen: 26, leafW: 6, colors: ['#6B7D4E', '#829464', '#58693E', '#9AAA7E'], berries: 5, berryColor: '#3B4229'
    });
    s += L.olive(c, [[365, 115], [290, 74], [200, 52], [105, 44]], {
      count: 14, leafLen: 24, leafW: 5.5, colors: ['#6B7D4E', '#829464', '#9AAA7E'], berries: 4, berryColor: '#4A3B32'
    });
    s += L.olive(c, [[85, H - 62], [210, H - 44], [335, H - 62]], {
      count: 14, leafLen: 22, leafW: 5, colors: ['#6B7D4E', '#829464', '#9AAA7E'], berries: 3, berryColor: '#3B4229'
    });
    s += L.frameRect(c, 22, 22, W - 44, H - 44, { stroke: '#8A9673', width: 0.8 });
    return s;
  },

  // 23. Wildflower Story — delicate wildflower illustrations
  nature_wildflower_story(L, c, W, H, p) {
    let s = '';
    s += L.wash(c, 210, 565, 195, 85, '#EAE3D2', 0.55);
    // Meadow wildflowers rising along the bottom and lower sides
    const stems = [
      { x: 38, h: 155, a: -82, type: 'daisy', col: '#FFFFFF' },
      { x: 74, h: 185, a: -86, type: 'lavender' },
      { x: 112, h: 135, a: -92, type: 'bloom', col: '#F2C6B6' },
      { x: 152, h: 115, a: -88, type: 'daisy', col: '#FFF8E7' },
      { x: 268, h: 120, a: -92, type: 'daisy', col: '#FFFFFF' },
      { x: 308, h: 145, a: -86, type: 'bloom', col: '#D6DFEF' },
      { x: 346, h: 180, a: -94, type: 'lavender' },
      { x: 382, h: 150, a: -98, type: 'daisy', col: '#F9E3B4' }
    ];
    stems.forEach((st) => {
      if (st.type === 'lavender') {
        s += L.lavender(c, st.x, H - 18, st.h, st.a);
      } else {
        const pts = L.stemPts(st.x, H - 18, st.h, st.a, 0.08);
        s += L.branch(c, pts, { count: 5, leafLen: 16, colors: ['#789268', '#8FA87F'], stem: '#69825A', tipLeaf: false });
        const tip = pts[pts.length - 1];
        if (st.type === 'daisy') {
          s += L.daisy(c, tip[0], tip[1], 19, { colors: { edge: st.col, base: st.col, center: '#DDA842' } });
        } else {
          s += L.bloom(c, tip[0], tip[1], 22, { colors: { edge: '#FFFFFF', base: st.col, center: '#D9A441' } });
        }
      }
    });
    // Delicate top wildflower sprigs
    s += L.daisy(c, 64, 58, 20, { colors: { edge: '#FFFFFF', base: '#F7EFE2', center: '#DDA842' } });
    s += L.daisy(c, W - 64, 58, 20, { colors: { edge: '#FFFFFF', base: '#F7EFE2', center: '#DDA842' } });
    s += L.frameRect(c, 20, 20, W - 40, H - 40, { stroke: '#B8B09C', width: 0.75 });
    return s;
  },

  // 24. Pampas Romance — dried pampas grass and warm neutral colours
  nature_pampas_romance(L, c, W, H, p) {
    let s = '';
    s += L.wash(c, 75, 165, 130, 155, '#E8D5BC', 0.50);
    s += L.wash(c, 345, 485, 130, 155, '#E4CEB2', 0.50);
    // Feathery pampas plumes left & right
    s += L.pampas(c, [[14, 310], [42, 195], [88, 82], [135, 28]], {
      colors: ['#E5D0B5', '#D4BC9C', '#F0E2CE', '#C4A884'], density: 75
    });
    s += L.pampas(c, [[28, 350], [68, 240], [122, 135]], {
      colors: ['#DEC6A8', '#CDB18E', '#EFE0CA'], density: 60
    });
    s += L.rose(c, 68, 225, 36, {
      colors: { edge: '#FFFDF9', base: '#EBD6BE', deep: '#C6A684' }
    });
    s += L.pampas(c, [[W - 14, 340], [W - 42, 455], [W - 88, 562], [W - 135, 614]], {
      colors: ['#E5D0B5', '#D4BC9C', '#F0E2CE', '#C4A884'], density: 75
    });
    s += L.rose(c, W - 68, 425, 36, {
      colors: { edge: '#FFFDF9', base: '#EBD6BE', deep: '#C6A684' }
    });
    s += L.frameRect(c, 24, 24, W - 48, H - 48, { stroke: '#C4A884', width: 0.85 });
    return s;
  },

  // 25. Garden Whisper — soft botanical arrangements
  nature_garden_whisper(L, c, W, H, p) {
    let s = '';
    s += L.wash(c, 210, 105, 155, 100, '#DCE8D8', 0.45);
    s += L.wash(c, 210, 545, 155, 95, '#DCE8D8', 0.42);
    // Airy fern + eucalyptus + white garden blooms
    s += L.fern(c, [[42, 128], [118, 78], [205, 48]], { colors: ['#7A9A76', '#8FAD8B'] });
    s += L.fern(c, [[W - 42, 128], [W - 118, 78], [W - 205, 48]], { colors: ['#7A9A76', '#8FAD8B'] });
    s += L.bloom(c, 210, 62, 42, {
      colors: { edge: '#FFFFFF', base: '#F2EFE6', deep: '#D4CFC0', center: '#C9A45C' }
    });
    s += L.daisy(c, 142, 76, 24, { colors: { edge: '#FFFFFF', base: '#F5F2EA', center: '#D4A84E' } });
    s += L.daisy(c, 278, 76, 24, { colors: { edge: '#FFFFFF', base: '#F5F2EA', center: '#D4A84E' } });
    s += L.eucalyptus(c, [[75, H - 62], [210, H - 42], [345, H - 62]], {
      count: 10, leafLen: 20, colors: ['#88A389', '#9EB69E'], stem: '#6D856E'
    });
    s += L.frameRect(c, 22, 22, W - 44, H - 44, { stroke: '#9BB09A', width: 0.8 });
    return s;
  },

  // 26. Tropical Leaves — sophisticated tropical greenery
  nature_tropical_leaves(L, c, W, H, p) {
    let s = '';
    s += L.wash(c, 85, 125, 140, 130, '#CFE2D4', 0.45);
    s += L.wash(c, 340, 520, 140, 130, '#CFE2D4', 0.45);
    // Lush palm fronds & monstera leaves
    s += L.palmFrond(c, [[10, 220], [65, 125], [155, 42]], {
      size: 52, colors: ['#2D6349', '#3B785B', '#23503A']
    });
    s += L.monstera(c, 58, 86, 78, 38, { colors: ['#25573F', '#316D50', '#1E4733'] });
    s += L.palmFrond(c, [[W - 10, 430], [W - 65, 520], [W - 155, 600]], {
      size: 52, colors: ['#2D6349', '#3B785B', '#23503A']
    });
    s += L.monstera(c, W - 58, H - 86, 78, -142, { colors: ['#25573F', '#316D50', '#1E4733'] });
    s += L.frameRect(c, 26, 26, W - 52, H - 52, { stroke: c.gold(), width: 1.1, double: 5 });
    return s;
  },

  // 27. Greenhouse — botanical frame with elegant typography
  nature_greenhouse(L, c, W, H, p) {
    let s = '';
    // Geometric glasshouse octagon-arched conservatory frame wrapped in climbing vines
    s += L.frameRect(c, 28, 28, W - 56, H - 56, { stroke: '#4A634E', width: 1.3 });
    s += L.arch(c, 38, 38, W - 76, H - 76, { stroke: '#6E8772', width: 0.9 });
    // Climbing botanical vines along left and right conservatory pillars
    s += L.branch(c, [[38, 520], [32, 380], [44, 240], [38, 110]], {
      count: 16, leafLen: 22, colors: ['#58765C', '#719175', '#88A68C'], stem: '#4A634E'
    });
    s += L.branch(c, [[W - 38, 520], [W - 32, 380], [W - 44, 240], [W - 38, 110]], {
      count: 16, leafLen: 22, colors: ['#58765C', '#719175', '#88A68C'], stem: '#4A634E'
    });
    s += L.bloom(c, 38, 240, 28, { colors: { edge: '#FFFFFF', base: '#EAE6DC', center: '#C9A24D' } });
    s += L.bloom(c, W - 38, 240, 28, { colors: { edge: '#FFFFFF', base: '#EAE6DC', center: '#C9A24D' } });
    return s;
  },

  // 28. Lavender Dreams — lavender illustrations and soft purple tones
  nature_lavender_dreams(L, c, W, H, p) {
    let s = '';
    s += L.wash(c, 85, 290, 110, 180, '#E3D9F0', 0.46);
    s += L.wash(c, 335, 290, 110, 180, '#E3D9F0', 0.46);
    // Fragrant lavender bundles on left and right margins + top arch
    [-12, -4, 5, 14].forEach((off, i) => {
      s += L.lavender(c, 42 + i * 7, 460, 175, -82 + off);
      s += L.lavender(c, W - 42 - i * 7, 460, 175, -98 - off);
    });
    // Tied ribbon bow on left & right bundles
    s += `<path d="M34 395 Q52 402 70 395" fill="none" stroke="#B89B72" stroke-width="2.2"/>`;
    s += `<path d="M${W - 70} 395 Q${W - 52} 402 ${W - 34} 395" fill="none" stroke="#B89B72" stroke-width="2.2"/>`;
    s += L.lavender(c, 135, 72, 95, -22);
    s += L.lavender(c, W - 135, 72, 95, -158);
    s += L.frameRect(c, 22, 22, W - 44, H - 44, { stroke: '#B3A2CC', width: 0.85 });
    return s;
  },

  // 29. Autumn Botanicals — earthy brown and terracotta leaves
  nature_autumn_botanicals(L, c, W, H, p) {
    let s = '';
    s += L.wash(c, 95, 115, 145, 120, '#EED6C4', 0.48);
    s += L.wash(c, 325, 525, 145, 120, '#E9CBB5', 0.48);
    // Terracotta, amber, rust & copper botanical branches + oak/maple foliage
    s += L.branch(c, [[16, 215], [62, 125], [142, 58], [235, 32]], {
      count: 12, leafLen: 26, colors: ['#C26D4A', '#D48C5C', '#9E5236', '#DDA56C'], stem: '#7A432D'
    });
    s += L.mapleLeaf(c, 68, 92, 28, 25, '#C25E3A');
    s += L.oakLeaf(c, 128, 72, 42, -35, '#B8733E');
    s += L.bloom(c, 48, 148, 36, {
      colors: { edge: '#FDF3EB', base: '#E8B598', deep: '#B86B4B', center: '#8C4327' }
    });
    s += L.branch(c, [[W - 16, 430], [W - 62, 515], [W - 142, 582], [W - 235, 608]], {
      count: 12, leafLen: 26, colors: ['#C26D4A', '#D48C5C', '#9E5236', '#DDA56C'], stem: '#7A432D'
    });
    s += L.mapleLeaf(c, W - 68, H - 92, 28, -155, '#C25E3A');
    s += L.oakLeaf(c, W - 128, H - 72, 42, 145, '#B8733E');
    s += L.frameRect(c, 24, 24, W - 48, H - 48, { stroke: '#C27A56', width: 0.9 });
    return s;
  },

  // 30. Forest Elegance — deep green botanical details
  nature_forest_elegance(L, c, W, H, p) {
    let s = '';
    const g = c.gold();
    s += L.wash(c, 210, 320, 185, 250, '#1B3B2F', 0.35);
    s += L.goldFlakes(c, 24, 24, W - 48, H - 48, 28);
    // Deep emerald woodland ferns & golden pine/botanical boughs on dark forest card
    s += L.fern(c, [[18, 220], [58, 125], [135, 52], [215, 28]], {
      colors: ['#2E5E4A', '#3D755E', '#4F8C72'], stem: '#8C7348'
    });
    s += L.fern(c, [[W - 18, 220], [W - 58, 125], [W - 135, 52], [W - 215, 28]], {
      colors: ['#2E5E4A', '#3D755E', '#4F8C72'], stem: '#8C7348'
    });
    s += L.branch(c, [[28, 165], [82, 88], [162, 42]], {
      count: 8, leafLen: 20, colors: ['#D4AF37', '#E6C767'], stem: '#B8902E'
    });
    s += L.branch(c, [[W - 28, H - 165], [W - 82, H - 88], [W - 162, H - 42]], {
      count: 8, leafLen: 20, colors: ['#D4AF37', '#E6C767'], stem: '#B8902E'
    });
    s += L.frameRect(c, 22, 22, W - 44, H - 44, { stroke: g, width: 1.2, double: 6 });
    return s;
  },

  // ----------------------------------------------------------
  // COLLECTION D: LUXURY AND CLASSIC (31 - 38)
  // ----------------------------------------------------------

  // 31. Black Gold Royale — black background and gold accents
  classic_black_gold_royale(L, c, W, H, p) {
    let s = '';
    const g = c.gold();
    s += L.goldFlakes(c, 20, 20, W - 40, H - 40, 36);
    s += L.frameRect(c, 18, 18, W - 36, H - 36, { stroke: g, width: 1.5, double: 7 });
    // Art-Deco sunbursts top & bottom + stepped corners
    s += L.sunburst(c, W / 2, 25, 18, 92, 23, 18, 162, { color: g, width: 0.9, arcs: 2 });
    s += L.sunburst(c, W / 2, H - 25, 18, 92, 23, -162, -18, { color: g, width: 0.9, arcs: 2 });
    s += L.decoCorner(c, 25, 25, 52, { color: g });
    s += L.decoCorner(c, W - 25, 25, 52, { color: g, flipX: true });
    s += L.decoCorner(c, 25, H - 25, 52, { color: g, flipY: true });
    s += L.decoCorner(c, W - 25, H - 25, 52, { color: g, flipX: true, flipY: true });
    return s;
  },

  // 32. Royal Champagne — champagne and metallic gold styling
  classic_royal_champagne(L, c, W, H, p) {
    let s = '';
    const g = c.gold();
    s += L.wash(c, 210, 120, 175, 110, '#EFE0C4', 0.55);
    s += L.wash(c, 210, 520, 175, 110, '#EFE0C4', 0.55);
    s += L.goldFlakes(c, 25, 25, W - 50, H - 50, 40);
    s += L.arch(c, 28, 28, W - 56, H - 56, { stroke: g, width: 1.4, inner: 6 });
    s += L.fourCorners(c, 24, 24, W - 48, H - 48, 44, { color: g, width: 1.1 });
    s += L.divider(c, W / 2, 62, 130, { color: g, style: 'flourish' });
    s += L.divider(c, W / 2, H - 52, 130, { color: g, style: 'flourish' });
    return s;
  },

  // 33. Marble Luxury — subtle marble texture and elegant lettering
  classic_marble_luxury(L, c, W, H, p) {
    let s = '';
    const g = c.gold();
    // Carrara grey & kintsugi gold veins across the card
    s += L.marble(c, W, H, { vein: '#B4B0A8', count: 12, gold: true, goldCount: 4 });
    // Geometric gold hexagon/octagon frame
    const m = 26, k = 38;
    const poly = `${m + k},${m} ${W - m - k},${m} ${W - m},${m + k} ${W - m},${H - m - k} ${W - m - k},${H - m} ${m + k},${H - m} ${m},${H - m - k} ${m},${m + k}`;
    s += `<polygon points="${poly}" fill="none" stroke="${g}" stroke-width="1.4"/>`;
    s += L.frameRect(c, 34, 34, W - 68, H - 68, { stroke: g, width: 0.6 });
    return s;
  },

  // 34. Ornamental Heritage — sophisticated ornamental borders
  classic_ornamental_heritage(L, c, W, H, p) {
    let s = '';
    const g = '#B08A48';
    s += L.frameRect(c, 18, 18, W - 36, H - 36, { stroke: g, width: 1.4, double: 6 });
    s += L.fourCorners(c, 26, 26, W - 52, H - 52, 64, { color: g, width: 1.2 });
    s += L.star8(c, W / 2, 54, 18, { color: g });
    s += L.star8(c, W / 2, H - 54, 18, { color: g });
    s += L.divider(c, W / 2, 82, 150, { color: g, style: 'flourish' });
    s += L.divider(c, W / 2, H - 82, 150, { color: g, style: 'flourish' });
    return s;
  },

  // 35. Velvet Burgundy — deep burgundy and champagne
  classic_velvet_burgundy(L, c, W, H, p) {
    let s = '';
    const g = c.gold();
    s += L.wash(c, 85, 110, 130, 120, '#6B152B', 0.55);
    s += L.wash(c, 335, 530, 130, 120, '#6B152B', 0.55);
    s += L.frameRect(c, 22, 22, W - 44, H - 44, { stroke: g, width: 1.3, double: 6 });
    // Deep crimson/burgundy velvet roses with metallic gold leaves
    s += L.branch(c, [[24, 195], [64, 108], [148, 44]], {
      count: 9, leafLen: 22, colors: ['#D4AF37', '#E6C86E', '#AA8229'], stem: '#C99E3A'
    });
    s += L.peony(c, 68, 86, 52, {
      colors: { edge: '#F2C6CE', base: '#9E1F3F', deep: '#4E091B', center: '#E6C365' }
    });
    s += L.rose(c, 128, 58, 32, {
      colors: { edge: '#F9DCE1', base: '#C44563', deep: '#7A132C' }
    });
    s += L.branch(c, [[W - 24, H - 195], [W - 64, H - 108], [W - 148, H - 44]], {
      count: 9, leafLen: 22, colors: ['#D4AF37', '#E6C86E', '#AA8229'], stem: '#C99E3A'
    });
    s += L.peony(c, W - 68, H - 86, 52, {
      colors: { edge: '#F2C6CE', base: '#9E1F3F', deep: '#4E091B', center: '#E6C365' }
    });
    s += L.rose(c, W - 128, H - 58, 32, {
      colors: { edge: '#F9DCE1', base: '#C44563', deep: '#7A132C' }
    });
    return s;
  },

  // 36. Pearl Elegance — pearl white with delicate decorative details
  classic_pearl_elegance(L, c, W, H, p) {
    let s = '';
    s += L.wash(c, 210, 110, 175, 95, '#EEE9F0', 0.50);
    // Draped iridescent pearl swags along top and bottom
    s += L.pearlSwag(c, [[26, 42], [115, 88], [210, 38]], 3.6);
    s += L.pearlSwag(c, [[210, 38], [305, 88], [W - 26, 42]], 3.6);
    s += L.pearlSwag(c, [[52, 42], [130, 68], [210, 38]], 2.6);
    s += L.pearlSwag(c, [[210, 38], [290, 68], [W - 52, 42]], 2.6);
    s += L.pearlSwag(c, [[36, H - 42], [210, H - 78], [W - 36, H - 42]], 3.2);
    s += L.frameRect(c, 24, 24, W - 48, H - 48, { stroke: '#C5BDC9', width: 0.9, double: 5 });
    return s;
  },

  // 37. Golden Frame — fine gold framing and serif typography
  classic_golden_frame(L, c, W, H, p) {
    let s = '';
    const g = c.gold();
    s += L.goldFlakes(c, 20, 20, W - 40, H - 40, 24);
    // Triple geometric gold gallery frames (rectangle + diamond corners + inner arch)
    s += L.frameRect(c, 20, 20, W - 40, H - 40, { stroke: g, width: 1.5 });
    s += L.frameRect(c, 28, 28, W - 56, H - 56, { stroke: g, width: 0.7 });
    s += L.arch(c, 38, 38, W - 76, H - 76, { stroke: g, width: 1.0 });
    s += L.fourCorners(c, 24, 24, W - 48, H - 48, 42, { color: g, width: 1.0 });
    return s;
  },

  // 38. Classic Crest — elegant crest and monogram composition
  classic_classic_crest(L, c, W, H, p) {
    let s = '';
    const g = c.gold();
    s += L.frameRect(c, 22, 22, W - 44, H - 44, { stroke: g, width: 1.2, double: 6 });
    s += L.fourCorners(c, 30, 30, W - 60, H - 60, 40, { color: g, width: 1.0 });
    // Heraldic royal shield, crown & laurel wreath at top center
    s += L.crest(c, W / 2, 74, 48, { color: g, fill: '#FAF4E8', crownFill: g });
    s += L.divider(c, W / 2, H - 50, 140, { color: g, style: 'diamond' });
    return s;
  },

  // ----------------------------------------------------------
  // COLLECTION E: AFRICAN LUXURY (39 - 45)
  // ----------------------------------------------------------

  // 39. African Royalty — elegant African-inspired ornamental patterns
  african_african_royalty(L, c, W, H, p) {
    let s = '';
    const g = c.gold();
    // Royal Ashanti/Serengeti gold chevron friezes top and bottom
    s += L.chevronBand(c, 20, 20, W - 40, 26, { colors: ['#17110B', '#D4AF37'] });
    s += L.chevronBand(c, 20, H - 46, W - 40, 26, { colors: ['#17110B', '#D4AF37'] });
    s += L.frameRect(c, 20, 20, W - 40, H - 40, { stroke: g, width: 1.5, double: 6 });
    s += L.star8(c, W / 2, 76, 22, { color: g, fill: '#17110B' });
    s += L.decoCorner(c, 28, 52, 38, { color: g });
    s += L.decoCorner(c, W - 28, 52, 38, { color: g, flipX: true });
    s += L.decoCorner(c, 28, H - 52, 38, { color: g, flipY: true });
    s += L.decoCorner(c, W - 28, H - 52, 38, { color: g, flipX: true, flipY: true });
    return s;
  },

  // 40. Kitenge Elegance — sophisticated kitenge-inspired decorative motifs
  african_kitenge_elegance(L, c, W, H, p) {
    let s = '';
    // Authentic wax-print Kitenge geometric bands at top and bottom
    s += L.kitengeBand(c, 18, 18, W - 36, 36, {
      colors: ['#162B4C', '#E59F28', '#C2492A', '#FAF1E2']
    });
    s += L.kitengeBand(c, 18, H - 54, W - 36, 36, {
      colors: ['#162B4C', '#E59F28', '#C2492A', '#FAF1E2']
    });
    s += L.frameRect(c, 18, 18, W - 36, H - 36, { stroke: '#C8922A', width: 1.4 });
    s += L.frameRect(c, 26, 60, W - 52, H - 120, { stroke: '#C8922A', width: 0.7 });
    return s;
  },

  // 41. Swahili Heritage — refined Swahili cultural design elements
  african_swahili_heritage(L, c, W, H, p) {
    let s = '';
    const g = c.gold();
    // Carved Zanzibar Stone Town brass-studded arched doorway + 8-point stars
    s += L.swahiliDoor(c, 24, 24, W - 48, H - 48, {
      wood: '#5E3D24', panel: p.bg || '#FBF6EC', border: 20
    });
    s += L.star8(c, 68, 72, 16, { color: g });
    s += L.star8(c, W - 68, 72, 16, { color: g });
    s += L.divider(c, W / 2, H - 48, 130, { color: g, style: 'diamond' });
    return s;
  },

  // 42. African Botanical — tropical African flowers and leaves
  african_african_botanical(L, c, W, H, p) {
    let s = '';
    s += L.wash(c, 85, 130, 135, 125, '#F2D8CE', 0.48);
    s += L.wash(c, 335, 515, 135, 125, '#E5DEC9', 0.48);
    // King Protea + Bird of Paradise (Strelitzia) + tropical foliage
    s += L.palmFrond(c, [[14, 230], [64, 130], [148, 48]], {
      size: 46, colors: ['#3B5E45', '#4E7559', '#2B4733']
    });
    s += L.protea(c, 74, 112, 54, {
      colors: { edge: '#FAD9DE', base: '#E08C9B', deep: '#A84658', center: '#F7EFE2' }, tilt: 18
    });
    s += L.strelitzia(c, 135, 76, 56, { tilt: -15 });
    // Bottom-right protea & strelitzia
    s += L.palmFrond(c, [[W - 14, 420], [W - 64, 515], [W - 148, 592]], {
      size: 46, colors: ['#3B5E45', '#4E7559', '#2B4733']
    });
    s += L.protea(c, W - 74, H - 102, 52, {
      colors: { edge: '#FAD9DE', base: '#E08C9B', deep: '#A84658', center: '#F7EFE2' }, tilt: -162
    });
    s += L.strelitzia(c, W - 135, H - 76, 52, { tilt: 15, flip: true });
    s += L.frameRect(c, 22, 22, W - 44, H - 44, { stroke: '#B87B56', width: 0.9 });
    return s;
  },

  // 43. Zanzibar Romance — coastal-inspired ivory and blue design
  african_zanzibar_romance(L, c, W, H, p) {
    let s = '';
    s += L.wash(c, 210, 545, 195, 90, '#CFE4F2', 0.55);
    // Omani/Swahili coastal arch + Indian Ocean waves + sailing dhow
    s += L.arch(c, 28, 28, W - 56, H - 56, { stroke: '#1E5B88', width: 1.3, inner: 6 });
    s += L.star8(c, 64, 68, 16, { color: '#C89B4B' });
    s += L.star8(c, W - 64, 68, 16, { color: '#C89B4B' });
    s += L.waves(c, 42, H - 74, W - 84, 4, {
      colors: ['#1E5B88', '#3F82B0', '#7AB0D4'], amp: 5, len: 28
    });
    s += L.dhow(c, W - 92, H - 82, 42, { hull: '#5C3D2E', sail: '#FFFDF8', stroke: '#1E5B88' });
    return s;
  },

  // 44. African Gold — warm gold and earthy African-inspired patterns
  african_african_gold(L, c, W, H, p) {
    let s = '';
    const g = c.gold();
    // Bogolanfini / Mali mudcloth motifs in warm gold at top and bottom
    s += L.mudcloth(c, 26, 26, W - 52, 68, { color: '#C8963E', rows: 3 });
    s += L.mudcloth(c, 26, H - 94, W - 52, 68, { color: '#C8963E', rows: 3 });
    s += L.frameRect(c, 20, 20, W - 40, H - 40, { stroke: g, width: 1.4, double: 6 });
    return s;
  },

  // 45. Kanga Celebration — tasteful kanga-inspired borders and patterns
  african_kanga_celebration(L, c, W, H, p) {
    let s = '';
    // Authentic East African Kanga border (pindo) with cashew/paisley korosho motifs
    s += L.kangaFrame(c, 14, 14, W - 28, H - 28, 26, {
      colors: ['#7D1935', '#F2C14E', '#FFF8EB']
    });
    return s;
  },

  // ----------------------------------------------------------
  // COLLECTION F: MODERN CELEBRATIONS (46 - 50)
  // ----------------------------------------------------------

  // 46. Birthday Luxe — elegant birthday invitation
  modern_birthday_luxe(L, c, W, H, p) {
    let s = '';
    const g = c.gold();
    s += L.goldFlakes(c, 20, 20, W - 40, 220, 38);
    s += L.confetti(c, 24, 24, W - 48, 190, 34, ['#D4AF37', '#F3D689', '#E6B8AF', '#C59B38']);
    // Champagne & rose-gold luxury balloons on left and right
    s += L.balloon(c, 58, 92, 26, '#E5C17B');
    s += L.balloon(c, 88, 68, 22, '#E8B8B0');
    s += L.balloon(c, 42, 132, 20, '#F3DFB2');
    s += L.balloon(c, W - 58, 92, 26, '#E5C17B');
    s += L.balloon(c, W - 88, 68, 22, '#E8B8B0');
    s += L.balloon(c, W - 42, 132, 20, '#F3DFB2');
    s += L.frameRect(c, 22, 22, W - 44, H - 44, { stroke: g, width: 1.2, double: 5 });
    return s;
  },

  // 47. Graduation Prestige — sophisticated graduation invitation
  modern_graduation_prestige(L, c, W, H, p) {
    let s = '';
    const g = c.gold();
    s += L.frameRect(c, 22, 22, W - 44, H - 44, { stroke: g, width: 1.3, double: 6 });
    s += L.fourCorners(c, 28, 28, W - 56, H - 56, 40, { color: g });
    // Golden laurel wreath + academic mortarboard cap at top center
    s += L.laurel(c, W / 2, 74, 42, { color: g, count: 10, leafLen: 11, gap: 35, ribbon: '#C99A3E' });
    s += L.mortarboard(c, W / 2, 70, 34, { color: '#142238', tassel: g });
    s += L.divider(c, W / 2, H - 48, 130, { color: g, style: 'diamond' });
    return s;
  },

  // 48. Corporate Prestige — premium business event invitation
  modern_corporate_prestige(L, c, W, H, p) {
    let s = '';
    const g = '#C59B38';
    // Executive architectural diagonal lines + precision frame
    s += L.geoLines(c, 18, 18, 160, 130, { color: g, count: 7, gap: 12, angle: -32 });
    s += L.geoLines(c, W - 178, H - 148, 160, 130, { color: g, count: 7, gap: 12, angle: -32 });
    s += L.frameRect(c, 24, 24, W - 48, H - 48, { stroke: g, width: 1.2 });
    s += `<rect x="32" y="32" width="${W - 64}" height="${H - 64}" fill="none" stroke="#1E3A5F" stroke-width="0.6"/>`;
    s += L.divider(c, W / 2, 58, 120, { color: g, style: 'diamond' });
    return s;
  },

  // 49. Gala Night — elegant black, gold and champagne event invitation
  modern_gala_night(L, c, W, H, p) {
    let s = '';
    const g = c.gold();
    // Starlight champagne bubbles & Art-Deco gala chandelier rays
    s += L.goldFlakes(c, 20, 20, W - 40, H - 40, 48);
    s += L.sunburst(c, W / 2, 22, 12, 115, 29, 12, 168, { color: g, width: 0.85, arcs: 3 });
    s += L.arch(c, 24, 24, W - 48, H - 48, { stroke: g, width: 1.4, inner: 6 });
    s += L.decoCorner(c, 24, H - 24, 48, { color: g, flipY: true });
    s += L.decoCorner(c, W - 24, H - 24, 48, { color: g, flipX: true, flipY: true });
    return s;
  },

  // 50. Engagement Romance — romantic floral engagement invitation
  modern_engagement_romance(L, c, W, H, p) {
    let s = '';
    const g = c.gold();
    s += L.wash(c, 85, 110, 130, 115, '#F7DCE0', 0.48);
    s += L.wash(c, 335, 530, 130, 115, '#F7DCE0', 0.48);
    // Interlocked diamond engagement rings at top center + romantic blush roses
    s += L.rings(c, W / 2, 68, 38, { color: g });
    s += L.eucalyptus(c, [[18, 185], [64, 105], [142, 48]], {
      count: 9, leafLen: 22, colors: ['#8FA691', '#A4B9A6'], stem: '#728A74'
    });
    s += L.peony(c, 64, 92, 46, {
      colors: { edge: '#FFF8F9', base: '#F5C2C9', deep: '#D4838F', center: '#E3B862' }
    });
    s += L.rose(c, 118, 58, 28, {
      colors: { edge: '#FFF6F7', base: '#EEB4BC', deep: '#C97884' }
    });
    s += L.eucalyptus(c, [[W - 18, H - 185], [W - 64, H - 105], [W - 142, H - 48]], {
      count: 9, leafLen: 22, colors: ['#8FA691', '#A4B9A6'], stem: '#728A74'
    });
    s += L.peony(c, W - 64, H - 92, 46, {
      colors: { edge: '#FFF8F9', base: '#F5C2C9', deep: '#D4838F', center: '#E3B862' }
    });
    s += L.rose(c, W - 118, H - 58, 28, {
      colors: { edge: '#FFF6F7', base: '#EEB4BC', deep: '#C97884' }
    });
    s += L.frameRect(c, 22, 22, W - 44, H - 44, { stroke: '#DFB3B9', width: 0.85 });
    return s;
  }
};

/**
 * Generates the SVG artwork string for a given template ID and optional custom accent color.
 */
function generateTemplateArtworkSvg(tplId, customAccent) {
  const L = (typeof LuxeArt !== 'undefined') ? LuxeArt : null;
  if (!L) return '';
  const builder = ARTWORK_BUILDERS[tplId] || ARTWORK_BUILDERS.wedding_ivory_bloom;
  const W = 420, H = 640;
  const c = new L.Ctx(tplId);
  const tpl = (typeof TEMPLATES_LIBRARY !== 'undefined' && TEMPLATES_LIBRARY[tplId]) ? TEMPLATES_LIBRARY[tplId] : {};
  const palette = {
    accent: customAccent || tpl.accentColor || '#B8955A',
    bg: tpl.bodyBg || '#FAF6F0',
    text: tpl.textColor || '#2C221E'
  };
  const body = builder(L, c, W, H, palette);
  return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ${W} ${H}" preserveAspectRatio="none" class="luxe-card-svg" style="width:100%;height:100%;display:block;pointer-events:none;">${c.defsMarkup()}${body}</svg>`;
}

const TEMPLATES_LIBRARY = {
  wedding_ivory_bloom: {
    id: 'wedding_ivory_bloom',
    number: 1,
    name: 'Ivory Bloom',
    category: 'wedding',
    catName: 'Luxury Floral Weddings',
    catIcon: '💍',
    tagline: 'Cream background with large delicate white flowers on the left',
    orientation: 'portrait',
    headerStyle: 'unified',
    showArtwork: true,
    artworkOpacity: 100,
    fontFamily: "'Cormorant Garamond', serif",
    titleFont: "'Cormorant Garamond', serif",
    scriptFont: "'Great Vibes', cursive",
    bodyFont: "'Montserrat', sans-serif",
    headerColor1: '#FAF6F0',
    headerColor2: '#F5EFE6',
    headingColor: '#3A2E25',
    bodyBg: '#FAF6F0',
    textColor: '#3A2E25',
    accentColor: '#B8955A',
    borderColor: '#DFD3C3',
    borderFrame: 'none',
    seal: 'crown',
    texture: 'none',
    qrDark: '#18181B',
    canvaQrLight: '#FFFFFF',
    badge: 'THE WEDDING OF',
    sampleCouple: 'Amelia & Sebastian',
    quote: 'Together with their families, joyfully invite you to share in their celebration of love',
    footer: 'Formal Evening Attire • Reception to Follow',
    swTitle: 'Harusi ya Kifahari',
    swBadge: 'MWALIKO WA HARUSI',
    swQuote: 'Familia zetu zina furaha kukualika katika sherehe ya kufunga ndoa takatifu',
    swFooter: 'Mavazi Rasmi ya Jioni • Karamu Itafuata'
  },
  wedding_champagne_romance: {
    id: 'wedding_champagne_romance',
    number: 2,
    name: 'Champagne Romance',
    category: 'wedding',
    catName: 'Luxury Floral Weddings',
    catIcon: '💍',
    tagline: 'Champagne and beige watercolor flowers with gold leaf accents',
    orientation: 'portrait',
    headerStyle: 'unified',
    showArtwork: true,
    artworkOpacity: 100,
    fontFamily: "'Playfair Display', serif",
    titleFont: "'Playfair Display', serif",
    scriptFont: "'Allura', cursive",
    bodyFont: "'Lato', sans-serif",
    headerColor1: '#FBF7F0',
    headerColor2: '#F4EBDC',
    headingColor: '#3E2F20',
    bodyBg: '#FBF7F0',
    textColor: '#3E2F20',
    accentColor: '#C49A52',
    borderColor: '#DFC9A6',
    borderFrame: 'none',
    seal: 'crown',
    texture: 'none',
    qrDark: '#18181B',
    canvaQrLight: '#FFFFFF',
    badge: 'HOLY MATRIMONY',
    sampleCouple: 'Genevieve & Alexander',
    quote: 'Request the honour of your presence as we exchange vows and begin our forever',
    footer: 'Black Tie Optional • Champagne Reception',
    swTitle: 'Ndoa ya Champagne',
    swBadge: 'MWALIKO WA NDOA',
    swQuote: 'Tuna heshima kubwa kukualika kushuhudia kiapo chetu cha upendo wa milele',
    swFooter: 'Mavazi ya Kifahari • Karibuni Sana'
  },
  wedding_blush_petals: {
    id: 'wedding_blush_petals',
    number: 3,
    name: 'Blush Petals',
    category: 'wedding',
    catName: 'Luxury Floral Weddings',
    catIcon: '💍',
    tagline: 'Soft blush pink flowers with elegant calligraphy',
    orientation: 'portrait',
    headerStyle: 'unified',
    showArtwork: true,
    artworkOpacity: 100,
    fontFamily: "'Cormorant Garamond', serif",
    titleFont: "'Cormorant Garamond', serif",
    scriptFont: "'Great Vibes', cursive",
    bodyFont: "'Montserrat', sans-serif",
    headerColor1: '#FFF9FA',
    headerColor2: '#FDF0F2',
    headingColor: '#4A2B30',
    bodyBg: '#FFF9FA',
    textColor: '#4A2B30',
    accentColor: '#C97B86',
    borderColor: '#EBC8CE',
    borderFrame: 'none',
    seal: 'crown',
    texture: 'none',
    qrDark: '#18181B',
    canvaQrLight: '#FFFFFF',
    badge: 'CELEBRATION OF LOVE',
    sampleCouple: 'Isabella & Nathaniel',
    quote: 'Invite you to celebrate the joy of their marriage and romantic garden reception',
    footer: 'Garden Formal Attire • Dinner & Dancing',
    swTitle: 'Harusi ya Waridi',
    swBadge: 'SHEREHE YA UPENDO',
    swQuote: 'Tunakualika kusherehekea furaha ya ndoa yetu katika bustani ya waridi',
    swFooter: 'Mavazi ya Bustani • Chakula na Muziki'
  },
  wedding_white_orchid: {
    id: 'wedding_white_orchid',
    number: 4,
    name: 'White Orchid',
    category: 'wedding',
    catName: 'Luxury Floral Weddings',
    catIcon: '💍',
    tagline: 'Minimalist white orchids and fine editorial typography',
    orientation: 'portrait',
    headerStyle: 'unified',
    showArtwork: true,
    artworkOpacity: 100,
    fontFamily: "'Playfair Display', serif",
    titleFont: "'Playfair Display', serif",
    scriptFont: "'Parisienne', cursive",
    bodyFont: "'Inter', sans-serif",
    headerColor1: '#FAF8F5',
    headerColor2: '#F3EFE9',
    headingColor: '#2B2622',
    bodyBg: '#FAF8F5',
    textColor: '#2B2622',
    accentColor: '#A68863',
    borderColor: '#DCD4C8',
    borderFrame: 'none',
    seal: 'crown',
    texture: 'none',
    qrDark: '#18181B',
    canvaQrLight: '#FFFFFF',
    badge: 'WEDDING INVITATION',
    sampleCouple: 'Clara & Julian',
    quote: 'With joyful hearts, we invite you to celebrate our union and intimate dinner',
    footer: 'Cocktail Couture • Private Conservatory',
    swTitle: 'Harusi ya Okidi Nyeupe',
    swBadge: 'MWALIKO RASMI',
    swQuote: 'Kwa mioyo yenye furaha, tunakualika katika sherehe ya ndoa yetu',
    swFooter: 'Mavazi ya Kifahari • Ukumbi Maalum'
  },
  wedding_golden_botanical: {
    id: 'wedding_golden_botanical',
    number: 5,
    name: 'Golden Botanical',
    category: 'wedding',
    catName: 'Luxury Floral Weddings',
    catIcon: '💍',
    tagline: 'Ivory background with subtle gold botanical decorations',
    orientation: 'portrait',
    headerStyle: 'unified',
    showArtwork: true,
    artworkOpacity: 100,
    fontFamily: "'Cinzel', serif",
    titleFont: "'Cinzel', serif",
    scriptFont: "'Great Vibes', cursive",
    bodyFont: "'Lato', sans-serif",
    headerColor1: '#FBF8F1',
    headerColor2: '#F4ECDC',
    headingColor: '#33281B',
    bodyBg: '#FBF8F1',
    textColor: '#33281B',
    accentColor: '#C99B3B',
    borderColor: '#DFC68C',
    borderFrame: 'none',
    seal: 'crown',
    texture: 'none',
    qrDark: '#18181B',
    canvaQrLight: '#FFFFFF',
    badge: 'ROYAL NUPTIAL INVITATION',
    sampleCouple: 'Victoria & Christian',
    quote: 'Cordially request the pleasure of your company on our wedding day',
    footer: 'Strict Black Tie • Grand Ballroom',
    swTitle: 'Harusi ya Dhahabu',
    swBadge: 'MWALIKO WA KIFALME',
    swQuote: 'Tunakuomba ushiriki nasi katika siku yetu kuu ya kufunga ndoa',
    swFooter: 'Mavazi Rasmi ya Kifahari • Ukumbi Mkuu'
  },
  wedding_beige_elegance: {
    id: 'wedding_beige_elegance',
    number: 6,
    name: 'Beige Elegance',
    category: 'wedding',
    catName: 'Luxury Floral Weddings',
    catIcon: '💍',
    tagline: 'Neutral beige and cream floral composition inside an arch',
    orientation: 'portrait',
    headerStyle: 'unified',
    showArtwork: true,
    artworkOpacity: 100,
    fontFamily: "'Cormorant Garamond', serif",
    titleFont: "'Cormorant Garamond', serif",
    scriptFont: "'Allura', cursive",
    bodyFont: "'Montserrat', sans-serif",
    headerColor1: '#F5EDE2',
    headerColor2: '#EDE1D1',
    headingColor: '#3C3026',
    bodyBg: '#F5EDE2',
    textColor: '#3C3026',
    accentColor: '#A6896A',
    borderColor: '#D5C3AE',
    borderFrame: 'none',
    seal: 'crown',
    texture: 'none',
    qrDark: '#18181B',
    canvaQrLight: '#FFFFFF',
    badge: 'TOGETHER FOREVER',
    sampleCouple: 'Eleanor & Gabriel',
    quote: 'Join us as we celebrate love, laughter, and the beginning of our new life',
    footer: 'Warm Neutral Palette Attire • Sunset Soirée',
    swTitle: 'Uzuri wa Beige',
    swBadge: 'PAMOJA MILELE',
    swQuote: 'Ungana nasi kusherehekea upendo na mwanzo wa maisha yetu mapya',
    swFooter: 'Mavazi ya Rangi Tulivu • Sherehe ya Jioni'
  },
  wedding_rose_garden: {
    id: 'wedding_rose_garden',
    number: 7,
    name: 'Rose Garden',
    category: 'wedding',
    catName: 'Luxury Floral Weddings',
    catIcon: '💍',
    tagline: 'Soft pink roses and elegant serif lettering',
    orientation: 'portrait',
    headerStyle: 'unified',
    showArtwork: true,
    artworkOpacity: 100,
    fontFamily: "'Libre Baskerville', serif",
    titleFont: "'Libre Baskerville', serif",
    scriptFont: "'Alex Brush', cursive",
    bodyFont: "'Lato', sans-serif",
    headerColor1: '#FDF8F7',
    headerColor2: '#F9ECEB',
    headingColor: '#3F272A',
    bodyBg: '#FDF8F7',
    textColor: '#3F272A',
    accentColor: '#C2747F',
    borderColor: '#E6C3C7',
    borderFrame: 'none',
    seal: 'crown',
    texture: 'none',
    qrDark: '#18181B',
    canvaQrLight: '#FFFFFF',
    badge: 'GARDEN MATRIMONY',
    sampleCouple: 'Charlotte & William',
    quote: 'Request the honour of your presence at their Rose Garden wedding ceremony',
    footer: 'Romantic Garden Attire • Al Fresco Banquet',
    swTitle: 'Bustani ya Waridi',
    swBadge: 'NDOA YA BUSTANI',
    swQuote: 'Tunakualika kushuhudia ndoa yetu katika bustani yenye maua ya waridi',
    swFooter: 'Mavazi ya Heshima • Karamu ya Nje'
  },
  wedding_magnolia_dreams: {
    id: 'wedding_magnolia_dreams',
    number: 8,
    name: 'Magnolia Dreams',
    category: 'wedding',
    catName: 'Luxury Floral Weddings',
    catIcon: '💍',
    tagline: 'White magnolias with delicate woody branches',
    orientation: 'portrait',
    headerStyle: 'unified',
    showArtwork: true,
    artworkOpacity: 100,
    fontFamily: "'Cormorant Garamond', serif",
    titleFont: "'Cormorant Garamond', serif",
    scriptFont: "'Great Vibes', cursive",
    bodyFont: "'Poppins', sans-serif",
    headerColor1: '#FAF5EF',
    headerColor2: '#F3EAE0',
    headingColor: '#382B22',
    bodyBg: '#FAF5EF',
    textColor: '#382B22',
    accentColor: '#9E755B',
    borderColor: '#DBCABC',
    borderFrame: 'none',
    seal: 'crown',
    texture: 'none',
    qrDark: '#18181B',
    canvaQrLight: '#FFFFFF',
    badge: 'THE MARRIAGE OF',
    sampleCouple: 'Madeline & Theodore',
    quote: 'Beneath the blooming magnolias, we invite you to witness our sacred vows',
    footer: 'Formal Evening Wear • Courtyard Reception',
    swTitle: 'Ndoto ya Magnolia',
    swBadge: 'SHEREHE YA NDOA',
    swQuote: 'Chini ya miti ya magnolia, tunakualika kushuhudia viapo vyetu',
    swFooter: 'Mavazi Rasmi ya Jioni • Karibuni Sana'
  },
  wedding_vintage_floral: {
    id: 'wedding_vintage_floral',
    number: 9,
    name: 'Vintage Floral',
    category: 'wedding',
    catName: 'Luxury Floral Weddings',
    catIcon: '💍',
    tagline: 'Antique floral illustrations and classic heirloom typography',
    orientation: 'portrait',
    headerStyle: 'unified',
    showArtwork: true,
    artworkOpacity: 100,
    fontFamily: "'Playfair Display', serif",
    titleFont: "'Playfair Display', serif",
    scriptFont: "'Tangerine', cursive",
    bodyFont: "'Libre Baskerville', serif",
    headerColor1: '#F7F0E4',
    headerColor2: '#EFE5D4',
    headingColor: '#3B2D22',
    bodyBg: '#F7F0E4',
    textColor: '#3B2D22',
    accentColor: '#9E7458',
    borderColor: '#CBB89D',
    borderFrame: 'none',
    seal: 'crown',
    texture: 'none',
    qrDark: '#18181B',
    canvaQrLight: '#FFFFFF',
    badge: 'HEIRLOOM UNION',
    sampleCouple: 'Beatrice & Arthur',
    quote: 'Honouring timeless tradition, we invite you to the celebration of our marriage',
    footer: 'Classic Formal Attire • Heritage Manor',
    swTitle: 'Harusi ya Kale ya Kifahari',
    swBadge: 'MWALIKO WA HESHIMA',
    swQuote: 'Kwa kuheshimu mila na upendo, tunakualika kwenye sherehe ya ndoa yetu',
    swFooter: 'Mavazi Rasmi ya Kawaida • Ukumbi wa Urithi'
  },
  wedding_soft_peony: {
    id: 'wedding_soft_peony',
    number: 10,
    name: 'Soft Peony',
    category: 'wedding',
    catName: 'Luxury Floral Weddings',
    catIcon: '💍',
    tagline: 'Watercolor peonies with romantic styling',
    orientation: 'portrait',
    headerStyle: 'unified',
    showArtwork: true,
    artworkOpacity: 100,
    fontFamily: "'Cormorant Garamond', serif",
    titleFont: "'Cormorant Garamond', serif",
    scriptFont: "'Great Vibes', cursive",
    bodyFont: "'Montserrat', sans-serif",
    headerColor1: '#FFF9F9',
    headerColor2: '#FDEFF1',
    headingColor: '#45282D',
    bodyBg: '#FFF9F9',
    textColor: '#45282D',
    accentColor: '#CE7E8B',
    borderColor: '#ECC7CD',
    borderFrame: 'none',
    seal: 'crown',
    texture: 'none',
    qrDark: '#18181B',
    canvaQrLight: '#FFFFFF',
    badge: 'WEDDING CELEBRATION',
    sampleCouple: 'Seraphina & Dominic',
    quote: 'Invite you to share in the joy and romance of our wedding day',
    footer: 'Black Tie & Blush Accents • Grand Pavilion',
    swTitle: 'Peony Laini',
    swBadge: 'SHEREHE YA HARUSI',
    swQuote: 'Tunakualika kushiriki furaha na mapenzi ya siku yetu ya harusi',
    swFooter: 'Mavazi ya Kifahari • Banda Kuu'
  },
  minimal_modern_ivory: {
    id: 'minimal_modern_ivory',
    number: 11,
    name: 'Modern Ivory',
    category: 'minimalist',
    catName: 'Minimalist & Editorial',
    catIcon: '🏛️',
    tagline: 'Clean ivory background and sophisticated serif typography',
    orientation: 'portrait',
    headerStyle: 'unified',
    showArtwork: true,
    artworkOpacity: 100,
    fontFamily: "'Cormorant Garamond', serif",
    titleFont: "'Cormorant Garamond', serif",
    scriptFont: "'Allura', cursive",
    bodyFont: "'Montserrat', sans-serif",
    headerColor1: '#FAF7F2',
    headerColor2: '#F3EEE6',
    headingColor: '#2A241E',
    bodyBg: '#FAF7F2',
    textColor: '#2A241E',
    accentColor: '#A8906E',
    borderColor: '#DDD3C3',
    borderFrame: 'none',
    seal: 'crown',
    texture: 'none',
    qrDark: '#18181B',
    canvaQrLight: '#FFFFFF',
    badge: 'SAVE THE DATE',
    sampleCouple: 'Camille & Laurent',
    quote: 'Cordially invite you to an intimate evening celebrating their union',
    footer: 'Minimalist Black Tie • Private Gallery',
    swTitle: 'Pembe ya Ndovu ya Kisasa',
    swBadge: 'HIFADHI TAREHE',
    swQuote: 'Tunakualika katika jioni maalum ya kusherehekea muungano wetu',
    swFooter: 'Mavazi Rasmi • Ukumbi wa Sanaa'
  },
  minimal_the_editorial: {
    id: 'minimal_the_editorial',
    number: 12,
    name: 'The Editorial',
    category: 'minimalist',
    catName: 'Minimalist & Editorial',
    catIcon: '🏛️',
    tagline: 'Vogue magazine-inspired haute couture invitation layout',
    orientation: 'portrait',
    headerStyle: 'unified',
    showArtwork: true,
    artworkOpacity: 100,
    fontFamily: "'Playfair Display', serif",
    titleFont: "'Playfair Display', serif",
    scriptFont: "'Parisienne', cursive",
    bodyFont: "'Inter', sans-serif",
    headerColor1: '#F9F8F6',
    headerColor2: '#F1EFEA',
    headingColor: '#141414',
    bodyBg: '#F9F8F6',
    textColor: '#141414',
    accentColor: '#18181B',
    borderColor: '#27272A',
    borderFrame: 'none',
    seal: 'crown',
    texture: 'none',
    qrDark: '#18181B',
    canvaQrLight: '#FFFFFF',
    badge: 'EDITION N° 01 • PRIVATE INVITATION',
    sampleCouple: 'Margaux & Henri',
    quote: 'An editorial celebration of modern love, architecture, and fine dining',
    footer: 'Haute Couture Black & White • Rooftop Conservatory',
    swTitle: 'Mwaliko wa Kihariri',
    swBadge: 'TOLEO MAALUM',
    swQuote: 'Sherehe ya kisasa ya upendo, usanifu na chakula cha kifahari',
    swFooter: 'Mavazi ya Nyeusi na Nyeupe • Ghorofa ya Juu'
  },
  minimal_minimal_black: {
    id: 'minimal_minimal_black',
    number: 13,
    name: 'Minimal Black',
    category: 'minimalist',
    catName: 'Minimalist & Editorial',
    catIcon: '🏛️',
    tagline: 'Pure white background with architectural black typography',
    orientation: 'portrait',
    headerStyle: 'unified',
    showArtwork: true,
    artworkOpacity: 100,
    fontFamily: "'Cinzel', serif",
    titleFont: "'Cinzel', serif",
    scriptFont: "'Allura', cursive",
    bodyFont: "'Montserrat', sans-serif",
    headerColor1: '#FFFFFF',
    headerColor2: '#F7F7F7',
    headingColor: '#0F0F0F',
    bodyBg: '#FFFFFF',
    textColor: '#0F0F0F',
    accentColor: '#18181B',
    borderColor: '#18181B',
    borderFrame: 'none',
    seal: 'crown',
    texture: 'none',
    qrDark: '#18181B',
    canvaQrLight: '#FFFFFF',
    badge: 'BY INVITATION ONLY',
    sampleCouple: 'Vivienne & Marcus',
    quote: 'Request the pleasure of your company for cocktails, dinner and celebration',
    footer: 'Strict Monochrome Attire • Contemporary Museum',
    swTitle: 'Nyeusi Ndogo ya Kisasa',
    swBadge: 'KWA MWALIKO PEKEE',
    swQuote: 'Tunakuomba ushiriki nasi kwa vinywaji, chakula cha jioni na sherehe',
    swFooter: 'Mavazi Rasmi • Makumbusho ya Kisasa'
  },
  minimal_soft_typography: {
    id: 'minimal_soft_typography',
    number: 14,
    name: 'Soft Typography',
    category: 'minimalist',
    catName: 'Minimalist & Editorial',
    catIcon: '🏛️',
    tagline: 'Large calligraphy names with delicate serif details',
    orientation: 'portrait',
    headerStyle: 'unified',
    showArtwork: true,
    artworkOpacity: 100,
    fontFamily: "'Cormorant Garamond', serif",
    titleFont: "'Cormorant Garamond', serif",
    scriptFont: "'Great Vibes', cursive",
    bodyFont: "'Lato', sans-serif",
    headerColor1: '#FBF7F2',
    headerColor2: '#F4EDE3',
    headingColor: '#362C24',
    bodyBg: '#FBF7F2',
    textColor: '#362C24',
    accentColor: '#B5926B',
    borderColor: '#E2D5C3',
    borderFrame: 'none',
    seal: 'crown',
    texture: 'none',
    qrDark: '#18181B',
    canvaQrLight: '#FFFFFF',
    badge: 'TOGETHER WITH THEIR FAMILIES',
    sampleCouple: 'Celeste & Olivier',
    quote: 'Joyfully invite you to witness the beginning of their shared journey',
    footer: 'Formal Attire • Candlelit Reception',
    swTitle: 'Maandishi Laini',
    swBadge: 'PAMOJA NA FAMILIA',
    swQuote: 'Tunakualika kushuhudia mwanzo wa safari yetu ya pamoja',
    swFooter: 'Mavazi ya Heshima • Karamu ya Mishumaa'
  },
  minimal_modern_arch: {
    id: 'minimal_modern_arch',
    number: 15,
    name: 'Modern Arch',
    category: 'minimalist',
    catName: 'Minimalist & Editorial',
    catIcon: '🏛️',
    tagline: 'Architectural arch shapes and warm neutral colours',
    orientation: 'portrait',
    headerStyle: 'unified',
    showArtwork: true,
    artworkOpacity: 100,
    fontFamily: "'DM Serif Display', serif",
    titleFont: "'DM Serif Display', serif",
    scriptFont: "'Allura', cursive",
    bodyFont: "'Outfit', sans-serif",
    headerColor1: '#F6EFE7',
    headerColor2: '#ECE1D4',
    headingColor: '#382D24',
    bodyBg: '#F6EFE7',
    textColor: '#382D24',
    accentColor: '#B08968',
    borderColor: '#D8C5B0',
    borderFrame: 'none',
    seal: 'crown',
    texture: 'none',
    qrDark: '#18181B',
    canvaQrLight: '#FFFFFF',
    badge: 'ARCHITECTURAL UNION',
    sampleCouple: 'Sienna & Matteo',
    quote: 'Join us beneath the arches for an evening of vows, wine, and celebration',
    footer: 'Earth-Tone Formal • Villa Terrace',
    swTitle: 'Tao la Kisasa',
    swBadge: 'MUUNGANO WA KISASA',
    swQuote: 'Ungana nasi kwa jioni ya viapo, divai na furaha kuu',
    swFooter: 'Mavazi ya Rangi za Asili • Ukumbi wa Villa'
  },
  minimal_the_monogram: {
    id: 'minimal_the_monogram',
    number: 16,
    name: 'The Monogram',
    category: 'minimalist',
    catName: 'Minimalist & Editorial',
    catIcon: '🏛️',
    tagline: 'Personalized crest initials with an elegant double frame',
    orientation: 'portrait',
    headerStyle: 'unified',
    showArtwork: true,
    artworkOpacity: 100,
    fontFamily: "'Cormorant Garamond', serif",
    titleFont: "'Cormorant Garamond', serif",
    scriptFont: "'Alex Brush', cursive",
    bodyFont: "'Montserrat', sans-serif",
    headerColor1: '#FAF7F2',
    headerColor2: '#F2ECE2',
    headingColor: '#2E261E',
    bodyBg: '#FAF7F2',
    textColor: '#2E261E',
    accentColor: '#B8955A',
    borderColor: '#D8C7A8',
    borderFrame: 'none',
    seal: 'crown',
    texture: 'none',
    qrDark: '#18181B',
    canvaQrLight: '#FFFFFF',
    badge: 'MONOGRAM EDITION',
    sampleCouple: 'Lavinia & Edward',
    quote: 'The honour of your presence is requested at our marriage celebration',
    footer: 'Black Tie Invited • Estate Orangery',
    swTitle: 'Nembo ya Herufi',
    swBadge: 'MWALIKO WA MONOGRAM',
    swQuote: 'Uwepo wako unaombwa katika sherehe ya kufunga ndoa yetu',
    swFooter: 'Mavazi Rasmi ya Suti • Bustani ya Kifalme'
  },
  minimal_minimal_beige: {
    id: 'minimal_minimal_beige',
    number: 17,
    name: 'Minimal Beige',
    category: 'minimalist',
    catName: 'Minimalist & Editorial',
    catIcon: '🏛️',
    tagline: 'Warm cashmere beige cardstock and understated typography',
    orientation: 'portrait',
    headerStyle: 'unified',
    showArtwork: true,
    artworkOpacity: 100,
    fontFamily: "'Cormorant Garamond', serif",
    titleFont: "'Cormorant Garamond', serif",
    scriptFont: "'Parisienne', cursive",
    bodyFont: "'Lato', sans-serif",
    headerColor1: '#F3EAE0',
    headerColor2: '#EAE0D3',
    headingColor: '#3D3228',
    bodyBg: '#F3EAE0',
    textColor: '#3D3228',
    accentColor: '#9C8265',
    borderColor: '#CFC0AE',
    borderFrame: 'none',
    seal: 'crown',
    texture: 'none',
    qrDark: '#18181B',
    canvaQrLight: '#FFFFFF',
    badge: 'INTIMATE GATHERING',
    sampleCouple: 'Noemi & Soren',
    quote: 'We warmly invite you to share in a quiet celebration of love and friendship',
    footer: 'Smart Linen & Silk • Coastal Sanctuary',
    swTitle: 'Beige Tulivu',
    swBadge: 'MKUSANYIKO WA KARIBU',
    swQuote: 'Tunakualika kushiriki katika sherehe tulivu ya upendo na urafiki',
    swFooter: 'Mavazi Laini ya Heshima • Ukumbi wa Pwani'
  },
  minimal_classic_white: {
    id: 'minimal_classic_white',
    number: 18,
    name: 'Classic White',
    category: 'minimalist',
    catName: 'Minimalist & Editorial',
    catIcon: '🏛️',
    tagline: 'Pure cotton white with fine ornamental details',
    orientation: 'portrait',
    headerStyle: 'unified',
    showArtwork: true,
    artworkOpacity: 100,
    fontFamily: "'Playfair Display', serif",
    titleFont: "'Playfair Display', serif",
    scriptFont: "'Great Vibes', cursive",
    bodyFont: "'Montserrat', sans-serif",
    headerColor1: '#FFFFFF',
    headerColor2: '#F9F8F6',
    headingColor: '#26231F',
    bodyBg: '#FFFFFF',
    textColor: '#26231F',
    accentColor: '#A89882',
    borderColor: '#DCD5C9',
    borderFrame: 'none',
    seal: 'crown',
    texture: 'none',
    qrDark: '#18181B',
    canvaQrLight: '#FFFFFF',
    badge: 'CORDIAL INVITATION',
    sampleCouple: 'Helena & Charles',
    quote: 'Request the honour of your company at their ceremony and wedding breakfast',
    footer: 'Morning Suit & Formal Daywear • Cathedral Hall',
    swTitle: 'Nyeupe ya Kawaida',
    swBadge: 'MWALIKO WA HESHIMA',
    swQuote: 'Tunaomba uwepo wako katika ibada na karamu ya harusi yetu',
    swFooter: 'Mavazi Rasmi ya Mchana • Ukumbi wa Kanisa Kuu'
  },
  minimal_contemporary_line: {
    id: 'minimal_contemporary_line',
    number: 19,
    name: 'Contemporary Line',
    category: 'minimalist',
    catName: 'Minimalist & Editorial',
    catIcon: '🏛️',
    tagline: 'Minimalist geometric lines and asymmetric frames',
    orientation: 'portrait',
    headerStyle: 'unified',
    showArtwork: true,
    artworkOpacity: 100,
    fontFamily: "'Marcellus', serif",
    titleFont: "'Marcellus', serif",
    scriptFont: "'Allura', cursive",
    bodyFont: "'Sora', sans-serif",
    headerColor1: '#FAF7F3',
    headerColor2: '#F2ECE4',
    headingColor: '#24201D',
    bodyBg: '#FAF7F3',
    textColor: '#24201D',
    accentColor: '#B58E65',
    borderColor: '#D8C7B4',
    borderFrame: 'none',
    seal: 'crown',
    texture: 'none',
    qrDark: '#18181B',
    canvaQrLight: '#FFFFFF',
    badge: 'CONTEMPORARY SOIRÉE',
    sampleCouple: 'Aria & Bastian',
    quote: 'Celebrate with us as we unite our lives in an evening of modern elegance',
    footer: 'Cocktail Chic • Sculpture Garden',
    swTitle: 'Mstari wa Kisasa',
    swBadge: 'HAFLA YA KISASA',
    swQuote: 'Sherehekea nasi tunapounganisha maisha yetu katika usiku wa kifahari',
    swFooter: 'Mavazi ya Kisasa • Bustani ya Sanamu'
  },
  minimal_timeless_elegance: {
    id: 'minimal_timeless_elegance',
    number: 20,
    name: 'Timeless Elegance',
    category: 'minimalist',
    catName: 'Minimalist & Editorial',
    catIcon: '🏛️',
    tagline: 'Classic engraved serif typography and subtle passe-partout frame',
    orientation: 'portrait',
    headerStyle: 'unified',
    showArtwork: true,
    artworkOpacity: 100,
    fontFamily: "'Libre Baskerville', serif",
    titleFont: "'Libre Baskerville', serif",
    scriptFont: "'Great Vibes', cursive",
    bodyFont: "'Lato', sans-serif",
    headerColor1: '#FAF6EF',
    headerColor2: '#F2EBDF',
    headingColor: '#2C251E',
    bodyBg: '#FAF6EF',
    textColor: '#2C251E',
    accentColor: '#A98E67',
    borderColor: '#D6C7B0',
    borderFrame: 'none',
    seal: 'crown',
    texture: 'none',
    qrDark: '#18181B',
    canvaQrLight: '#FFFFFF',
    badge: 'THE HONOUR OF YOUR PRESENCE',
    sampleCouple: 'Evangeline & Henry',
    quote: 'Is requested at the marriage of their children and the gala dinner following',
    footer: 'White Tie or Black Tie • Symphony Hall',
    swTitle: 'Uzuri Usiochuja',
    swBadge: 'HESHIMA YA UWEPO WAKO',
    swQuote: 'Inaombwa katika sherehe ya ndoa na chakula cha jioni cha gala',
    swFooter: 'Mavazi Rasmi ya Heshima Kuu • Ukumbi wa Muziki'
  },
  nature_eucalyptus_garden: {
    id: 'nature_eucalyptus_garden',
    number: 21,
    name: 'Eucalyptus Garden',
    category: 'nature',
    catName: 'Botanical & Nature',
    catIcon: '🌿',
    tagline: 'Sage green silver-dollar eucalyptus branches on warm ivory',
    orientation: 'portrait',
    headerStyle: 'unified',
    showArtwork: true,
    artworkOpacity: 100,
    fontFamily: "'Cormorant Garamond', serif",
    titleFont: "'Cormorant Garamond', serif",
    scriptFont: "'Great Vibes', cursive",
    bodyFont: "'Montserrat', sans-serif",
    headerColor1: '#F8FAF7',
    headerColor2: '#EEF3EC',
    headingColor: '#263328',
    bodyBg: '#F8FAF7',
    textColor: '#263328',
    accentColor: '#6B8770',
    borderColor: '#C4D4C5',
    borderFrame: 'none',
    seal: 'crown',
    texture: 'none',
    qrDark: '#18181B',
    canvaQrLight: '#FFFFFF',
    badge: 'BOTANICAL WEDDING',
    sampleCouple: 'Olivia & Harrison',
    quote: 'Surrounded by eucalyptus and evergreen grace, we invite you to our wedding',
    footer: 'Garden Semi-Formal • Botanical Atrium',
    swTitle: 'Bustani ya Mikaratusi',
    swBadge: 'HARUSI YA KIJANI',
    swQuote: 'Tukizungukwa na uoto wa kijani kibichi, tunakualika kwenye harusi yetu',
    swFooter: 'Mavazi ya Bustani • Ukumbi wa Mimea'
  },
  nature_olive_branch: {
    id: 'nature_olive_branch',
    number: 22,
    name: 'Olive Branch',
    category: 'nature',
    catName: 'Botanical & Nature',
    catIcon: '🌿',
    tagline: 'Minimalist Mediterranean olive leaves and organic typography',
    orientation: 'portrait',
    headerStyle: 'unified',
    showArtwork: true,
    artworkOpacity: 100,
    fontFamily: "'Cormorant Garamond', serif",
    titleFont: "'Cormorant Garamond', serif",
    scriptFont: "'Allura', cursive",
    bodyFont: "'Lato', sans-serif",
    headerColor1: '#FAF9F4',
    headerColor2: '#F2F0E6',
    headingColor: '#2D3322',
    bodyBg: '#FAF9F4',
    textColor: '#2D3322',
    accentColor: '#6E7F4F',
    borderColor: '#CFD6C0',
    borderFrame: 'none',
    seal: 'crown',
    texture: 'none',
    qrDark: '#18181B',
    canvaQrLight: '#FFFFFF',
    badge: 'PEACE & DEVOTION',
    sampleCouple: 'Sofia & Lorenzo',
    quote: 'Under the olive trees, we invite you to feast, toast, and celebrate our marriage',
    footer: 'Tuscan Villa Attire • Olive Grove Courtyard',
    swTitle: 'Tawi la Mzeituni',
    swBadge: 'AMANI NA UPENDO',
    swQuote: 'Chini ya miti ya mizeituni, tunakualika kusherehekea ndoa yetu',
    swFooter: 'Mavazi ya Kifahari • Uwanja wa Mizeituni'
  },
  nature_wildflower_story: {
    id: 'nature_wildflower_story',
    number: 23,
    name: 'Wildflower Story',
    category: 'nature',
    catName: 'Botanical & Nature',
    catIcon: '🌿',
    tagline: 'Delicate meadow wildflower illustrations in soft pastels',
    orientation: 'portrait',
    headerStyle: 'unified',
    showArtwork: true,
    artworkOpacity: 100,
    fontFamily: "'Playfair Display', serif",
    titleFont: "'Playfair Display', serif",
    scriptFont: "'Parisienne', cursive",
    bodyFont: "'Poppins', sans-serif",
    headerColor1: '#FCFBF7',
    headerColor2: '#F5F2E9',
    headingColor: '#332E27',
    bodyBg: '#FCFBF7',
    textColor: '#332E27',
    accentColor: '#80966E',
    borderColor: '#D8D3C4',
    borderFrame: 'none',
    seal: 'crown',
    texture: 'none',
    qrDark: '#18181B',
    canvaQrLight: '#FFFFFF',
    badge: 'MEADOW CELEBRATION',
    sampleCouple: 'Freya & Jasper',
    quote: 'Where wildflowers bloom, our story begins — join us for a joyful celebration',
    footer: 'Pastel Garden Attire • Meadow Pavilion',
    swTitle: 'Hadithi ya Maua ya Kondeni',
    swBadge: 'SHEREHE YA SHAMBANI',
    swQuote: 'Pale maua yanapochanua, hadithi yetu inaanza — karibu tusherehekee',
    swFooter: 'Mavazi ya Rangi Laini • Banda la Bustani'
  },
  nature_pampas_romance: {
    id: 'nature_pampas_romance',
    number: 24,
    name: 'Pampas Romance',
    category: 'nature',
    catName: 'Botanical & Nature',
    catIcon: '🌿',
    tagline: 'Dried pampas grass plumes and warm boho neutral colours',
    orientation: 'portrait',
    headerStyle: 'unified',
    showArtwork: true,
    artworkOpacity: 100,
    fontFamily: "'Cormorant Garamond', serif",
    titleFont: "'Cormorant Garamond', serif",
    scriptFont: "'Alex Brush', cursive",
    bodyFont: "'Montserrat', sans-serif",
    headerColor1: '#FAF4EC',
    headerColor2: '#F2E7D8',
    headingColor: '#3F3124',
    bodyBg: '#FAF4EC',
    textColor: '#3F3124',
    accentColor: '#B89772',
    borderColor: '#DFCBB2',
    borderFrame: 'none',
    seal: 'crown',
    texture: 'none',
    qrDark: '#18181B',
    canvaQrLight: '#FFFFFF',
    badge: 'BOHEMIAN LUXURY',
    sampleCouple: 'Aurelia & Cassian',
    quote: 'With warm desert winds and golden light, we invite you to celebrate our vows',
    footer: 'Warm Desert Formal • Sunset Dune Terrace',
    swTitle: 'Mapenzi ya Pampas',
    swBadge: 'UFAHARI WA ASILI',
    swQuote: 'Katika mwanga wa dhahabu wa jioni, tunakualika kushuhudia viapo vyetu',
    swFooter: 'Mavazi ya Rangi za Joto • Ukumbi wa Machweo'
  },
  nature_garden_whisper: {
    id: 'nature_garden_whisper',
    number: 25,
    name: 'Garden Whisper',
    category: 'nature',
    catName: 'Botanical & Nature',
    catIcon: '🌿',
    tagline: 'Soft airy fern and white garden bloom arrangements',
    orientation: 'portrait',
    headerStyle: 'unified',
    showArtwork: true,
    artworkOpacity: 100,
    fontFamily: "'Cormorant Garamond', serif",
    titleFont: "'Cormorant Garamond', serif",
    scriptFont: "'Great Vibes', cursive",
    bodyFont: "'Lato', sans-serif",
    headerColor1: '#F9FBF8',
    headerColor2: '#EFF4ED',
    headingColor: '#2A362B',
    bodyBg: '#F9FBF8',
    textColor: '#2A362B',
    accentColor: '#769177',
    borderColor: '#C8D8C8',
    borderFrame: 'none',
    seal: 'crown',
    texture: 'none',
    qrDark: '#18181B',
    canvaQrLight: '#FFFFFF',
    badge: 'WHISPERS IN THE GARDEN',
    sampleCouple: 'Rosalie & Felix',
    quote: 'Join us for an enchanted afternoon of vows, acoustic music, and garden dining',
    footer: 'Afternoon Garden Elegance • Walled Garden',
    swTitle: 'Mnongono wa Bustani',
    swBadge: 'ALASIRI YA BUSTANI',
    swQuote: 'Ungana nasi kwa alasiri ya viapo, muziki mwanana na chakula cha bustani',
    swFooter: 'Mavazi ya Alasiri • Bustani Iliyozungushiwa Ukuta'
  },
  nature_tropical_leaves: {
    id: 'nature_tropical_leaves',
    number: 26,
    name: 'Tropical Leaves',
    category: 'nature',
    catName: 'Botanical & Nature',
    catIcon: '🌿',
    tagline: 'Sophisticated tropical palm and monstera greenery with gold frame',
    orientation: 'portrait',
    headerStyle: 'unified',
    showArtwork: true,
    artworkOpacity: 100,
    fontFamily: "'Playfair Display', serif",
    titleFont: "'Playfair Display', serif",
    scriptFont: "'Allura', cursive",
    bodyFont: "'Outfit', sans-serif",
    headerColor1: '#F7FAF7',
    headerColor2: '#EBF2EC',
    headingColor: '#1B3629',
    bodyBg: '#F7FAF7',
    textColor: '#1B3629',
    accentColor: '#2E664A',
    borderColor: '#B8CEBF',
    borderFrame: 'none',
    seal: 'crown',
    texture: 'none',
    qrDark: '#18181B',
    canvaQrLight: '#FFFFFF',
    badge: 'TROPICAL BLACK TIE',
    sampleCouple: 'Paloma & Rafael',
    quote: 'Escape with us to paradise as we celebrate our marriage under the palms',
    footer: 'Tropical Black Tie • Oceanfront Veranda',
    swTitle: 'Majani ya Kitropiki',
    swBadge: 'SHEREHE YA KITROPIKI',
    swQuote: 'Karibu tusherehekee ndoa yetu chini ya mitende ya kifahari',
    swFooter: 'Mavazi Rasmi ya Kitropiki • Ukumbi wa Baharini'
  },
  nature_greenhouse: {
    id: 'nature_greenhouse',
    number: 27,
    name: 'Greenhouse',
    category: 'nature',
    catName: 'Botanical & Nature',
    catIcon: '🌿',
    tagline: 'Architectural conservatory frame wrapped in climbing vines',
    orientation: 'portrait',
    headerStyle: 'unified',
    showArtwork: true,
    artworkOpacity: 100,
    fontFamily: "'DM Serif Display', serif",
    titleFont: "'DM Serif Display', serif",
    scriptFont: "'Parisienne', cursive",
    bodyFont: "'Inter', sans-serif",
    headerColor1: '#F6F8F5',
    headerColor2: '#ECEFE9',
    headingColor: '#233025',
    bodyBg: '#F6F8F5',
    textColor: '#233025',
    accentColor: '#4E6B52',
    borderColor: '#BCC9BD',
    borderFrame: 'none',
    seal: 'crown',
    texture: 'none',
    qrDark: '#18181B',
    canvaQrLight: '#FFFFFF',
    badge: 'CONSERVATORY RECEPTION',
    sampleCouple: 'Daphne & Benedict',
    quote: 'Beneath the glasshouse arches, we invite you to dine and dance the night away',
    footer: 'Formal Evening Wear • Royal Glasshouse',
    swTitle: 'Nyumba ya Kioo ya Mimea',
    swBadge: 'KARAMU YA BUSTANI',
    swQuote: 'Chini ya matao ya kioo na mimea, tunakualika kula na kufurahi nasi',
    swFooter: 'Mavazi Rasmi ya Usiku • Ukumbi wa Kioo'
  },
  nature_lavender_dreams: {
    id: 'nature_lavender_dreams',
    number: 28,
    name: 'Lavender Dreams',
    category: 'nature',
    catName: 'Botanical & Nature',
    catIcon: '🌿',
    tagline: 'Fragrant Provence lavender illustrations and soft purple tones',
    orientation: 'portrait',
    headerStyle: 'unified',
    showArtwork: true,
    artworkOpacity: 100,
    fontFamily: "'Cormorant Garamond', serif",
    titleFont: "'Cormorant Garamond', serif",
    scriptFont: "'Great Vibes', cursive",
    bodyFont: "'Lato', sans-serif",
    headerColor1: '#FAF8FC',
    headerColor2: '#F2EDF8',
    headingColor: '#322840',
    bodyBg: '#FAF8FC',
    textColor: '#322840',
    accentColor: '#836CA6',
    borderColor: '#D4C8E6',
    borderFrame: 'none',
    seal: 'crown',
    texture: 'none',
    qrDark: '#18181B',
    canvaQrLight: '#FFFFFF',
    badge: 'PROVENCE ROMANCE',
    sampleCouple: 'Colette & Maxime',
    quote: 'Amidst the scent of blooming lavender, we invite you to celebrate our love',
    footer: 'Chic Cocktail Attire • Lavender Chateau',
    swTitle: 'Ndoto ya Lavenda',
    swBadge: 'MAPENZI YA LAVENDA',
    swQuote: 'Katika harufu nzuri ya maua ya lavenda, tunakualika kusherehekea upendo wetu',
    swFooter: 'Mavazi ya Kifahari • Kasri la Lavenda'
  },
  nature_autumn_botanicals: {
    id: 'nature_autumn_botanicals',
    number: 29,
    name: 'Autumn Botanicals',
    category: 'nature',
    catName: 'Botanical & Nature',
    catIcon: '🌿',
    tagline: 'Earthy terracotta, copper and amber autumn leaves',
    orientation: 'portrait',
    headerStyle: 'unified',
    showArtwork: true,
    artworkOpacity: 100,
    fontFamily: "'Playfair Display', serif",
    titleFont: "'Playfair Display', serif",
    scriptFont: "'Alex Brush', cursive",
    bodyFont: "'Montserrat', sans-serif",
    headerColor1: '#FDF7F2',
    headerColor2: '#F7EBE1',
    headingColor: '#3D2419',
    bodyBg: '#FDF7F2',
    textColor: '#3D2419',
    accentColor: '#B86242',
    borderColor: '#E2C2B0',
    borderFrame: 'none',
    seal: 'crown',
    texture: 'none',
    qrDark: '#18181B',
    canvaQrLight: '#FFFFFF',
    badge: 'AUTUMN HARVEST UNION',
    sampleCouple: 'Hazel & August',
    quote: 'As the leaves turn to amber and gold, we invite you to celebrate our marriage',
    footer: 'Warm Velvet & Formal Attire • Vineyard Hall',
    swTitle: 'Mimea ya Vuli',
    swBadge: 'SHEREHE YA MAVUNO',
    swQuote: 'Majani yanapobadilika kuwa dhahabu, tunakualika kwenye harusi yetu',
    swFooter: 'Mavazi ya Joto ya Heshima • Ukumbi wa Mizabibu'
  },
  nature_forest_elegance: {
    id: 'nature_forest_elegance',
    number: 30,
    name: 'Forest Elegance',
    category: 'nature',
    catName: 'Botanical & Nature',
    catIcon: '🌿',
    tagline: 'Deep emerald forest background with golden botanical details',
    orientation: 'portrait',
    headerStyle: 'unified',
    showArtwork: true,
    artworkOpacity: 100,
    fontFamily: "'Cinzel', serif",
    titleFont: "'Cinzel', serif",
    scriptFont: "'Great Vibes', cursive",
    bodyFont: "'Lato', sans-serif",
    headerColor1: '#12261E',
    headerColor2: '#0D1C16',
    headingColor: '#F5E6C8',
    bodyBg: '#12261E',
    textColor: '#F3E7D0',
    accentColor: '#D4AF37',
    borderColor: '#C9A23F',
    borderFrame: 'none',
    seal: 'crown',
    texture: 'none',
    qrDark: '#12261E',
    canvaQrLight: '#F5E6C8',
    badge: 'ENCHANTED WOODLAND GALA',
    sampleCouple: 'Rowan & Alistair',
    quote: 'Beneath the ancient canopy and starlight, join us for an unforgettable evening',
    footer: 'Emerald & Gold Black Tie • Forest Lodge',
    swTitle: 'Ufahari wa Msitu',
    swBadge: 'USIKU WA MSITU WA KIFALME',
    swQuote: 'Chini ya nyota na miti mirefu, ungana nasi kwa usiku usiosahaulika',
    swFooter: 'Mavazi ya Kijani na Dhahabu • Ukumbi wa Msitu'
  },
  classic_black_gold_royale: {
    id: 'classic_black_gold_royale',
    number: 31,
    name: 'Black Gold Royale',
    category: 'classic',
    catName: 'Luxury & Classic',
    catIcon: '👑',
    tagline: 'Obsidian black cardstock with metallic Art-Deco gold accents',
    orientation: 'portrait',
    headerStyle: 'unified',
    showArtwork: true,
    artworkOpacity: 100,
    fontFamily: "'Cinzel', serif",
    titleFont: "'Cinzel', serif",
    scriptFont: "'Great Vibes', cursive",
    bodyFont: "'Montserrat', sans-serif",
    headerColor1: '#0D0D0F',
    headerColor2: '#16161A',
    headingColor: '#F5DC90',
    bodyBg: '#0D0D0F',
    textColor: '#F7E7B4',
    accentColor: '#D4AF37',
    borderColor: '#D4AF37',
    borderFrame: 'none',
    seal: 'crown',
    texture: 'none',
    qrDark: '#0D0D0F',
    canvaQrLight: '#F5DC90',
    badge: 'IMPERIAL BLACK TIE INVITATION',
    sampleCouple: 'Anastasia & Sterling',
    quote: 'Request the honour of your presence at an evening of royal splendour and celebration',
    footer: 'Strict Black Tie & Gold • Imperial Ballroom',
    swTitle: 'Ufalme wa Nyeusi na Dhahabu',
    swBadge: 'MWALIKO WA KIFALME',
    swQuote: 'Tunaomba heshima ya uwepo wako katika usiku wa fahari kuu',
    swFooter: 'Suti Nyeusi na Dhahabu • Ukumbi wa Kifalme'
  },
  classic_royal_champagne: {
    id: 'classic_royal_champagne',
    number: 32,
    name: 'Royal Champagne',
    category: 'classic',
    catName: 'Luxury & Classic',
    catIcon: '👑',
    tagline: 'Champagne silk background and metallic gold double arch styling',
    orientation: 'portrait',
    headerStyle: 'unified',
    showArtwork: true,
    artworkOpacity: 100,
    fontFamily: "'Playfair Display', serif",
    titleFont: "'Playfair Display', serif",
    scriptFont: "'Allura', cursive",
    bodyFont: "'Lato', sans-serif",
    headerColor1: '#FBF5E8',
    headerColor2: '#F4E9D2',
    headingColor: '#382B19',
    bodyBg: '#FBF5E8',
    textColor: '#382B19',
    accentColor: '#C49A3E',
    borderColor: '#DFC484',
    borderFrame: 'none',
    seal: 'crown',
    texture: 'none',
    qrDark: '#18181B',
    canvaQrLight: '#FFFFFF',
    badge: 'ROYAL CHAMPAGNE RECEPTION',
    sampleCouple: 'Francesca & Leopold',
    quote: 'Toast with us to a lifetime of love, grandeur, and cherished memories',
    footer: 'Formal Evening Gown & Tuxedo • Palace Salon',
    swTitle: 'Champagne ya Kifalme',
    swBadge: 'KARAMU YA KIFALME',
    swQuote: 'Inua glasi nasi kusherehekea maisha ya upendo na kumbukumbu njema',
    swFooter: 'Gauni la Jioni na Suti • Ukumbi wa Ikulu'
  },
  classic_marble_luxury: {
    id: 'classic_marble_luxury',
    number: 33,
    name: 'Marble Luxury',
    category: 'classic',
    catName: 'Luxury & Classic',
    catIcon: '👑',
    tagline: 'Carrara white marble veining with kintsugi gold geometric frame',
    orientation: 'portrait',
    headerStyle: 'unified',
    showArtwork: true,
    artworkOpacity: 100,
    fontFamily: "'Cinzel', serif",
    titleFont: "'Cinzel', serif",
    scriptFont: "'Parisienne', cursive",
    bodyFont: "'Montserrat', sans-serif",
    headerColor1: '#F9F8F6',
    headerColor2: '#F0EEEB',
    headingColor: '#242220',
    bodyBg: '#F9F8F6',
    textColor: '#242220',
    accentColor: '#C59B38',
    borderColor: '#D6BE7C',
    borderFrame: 'none',
    seal: 'crown',
    texture: 'none',
    qrDark: '#18181B',
    canvaQrLight: '#FFFFFF',
    badge: 'MARBLE & GOLD EDITION',
    sampleCouple: 'Valentina & Roman',
    quote: 'Invite you to celebrate their marriage in an evening of timeless luxury',
    footer: 'Black Tie Optional • Grand Marble Rotunda',
    swTitle: 'Ufahari wa Marumaru',
    swBadge: 'TOLEO LA MARUMARU NA DHAHABU',
    swQuote: 'Tunakualika kusherehekea ndoa yetu katika usiku wa ufahari',
    swFooter: 'Mavazi ya Kifahari • Ukumbi wa Marumaru'
  },
  classic_ornamental_heritage: {
    id: 'classic_ornamental_heritage',
    number: 34,
    name: 'Ornamental Heritage',
    category: 'classic',
    catName: 'Luxury & Classic',
    catIcon: '👑',
    tagline: 'Sophisticated baroque ornamental filigree borders and rosettes',
    orientation: 'portrait',
    headerStyle: 'unified',
    showArtwork: true,
    artworkOpacity: 100,
    fontFamily: "'Cormorant Garamond', serif",
    titleFont: "'Cormorant Garamond', serif",
    scriptFont: "'Great Vibes', cursive",
    bodyFont: "'Libre Baskerville', serif",
    headerColor1: '#FAF5EC',
    headerColor2: '#F2E8D7',
    headingColor: '#332617',
    bodyBg: '#FAF5EC',
    textColor: '#332617',
    accentColor: '#B08A48',
    borderColor: '#D4BA88',
    borderFrame: 'none',
    seal: 'crown',
    texture: 'none',
    qrDark: '#18181B',
    canvaQrLight: '#FFFFFF',
    badge: 'HERITAGE PROCLAMATION',
    sampleCouple: 'Cordelia & Maximilian',
    quote: 'With the blessing of our families, we request the honour of your company',
    footer: 'Traditional Formal Attire • Heritage Grand Hall',
    swTitle: 'Urithi wa Mapambo',
    swBadge: 'TANGAZO LA HESHIMA',
    swQuote: 'Kwa baraka za familia zetu, tunaomba heshima ya uwepo wako',
    swFooter: 'Mavazi Rasmi ya Heshima • Ukumbi wa Urithi'
  },
  classic_velvet_burgundy: {
    id: 'classic_velvet_burgundy',
    number: 35,
    name: 'Velvet Burgundy',
    category: 'classic',
    catName: 'Luxury & Classic',
    catIcon: '👑',
    tagline: 'Deep wine burgundy velvet with metallic champagne gold roses',
    orientation: 'portrait',
    headerStyle: 'unified',
    showArtwork: true,
    artworkOpacity: 100,
    fontFamily: "'Playfair Display', serif",
    titleFont: "'Playfair Display', serif",
    scriptFont: "'Great Vibes', cursive",
    bodyFont: "'Lato', sans-serif",
    headerColor1: '#2E0913',
    headerColor2: '#420D1B',
    headingColor: '#F5DFB8',
    bodyBg: '#2E0913',
    textColor: '#F7E7CE',
    accentColor: '#D4AF37',
    borderColor: '#C9A23F',
    borderFrame: 'none',
    seal: 'crown',
    texture: 'none',
    qrDark: '#2E0913',
    canvaQrLight: '#F5DFB8',
    badge: 'ROYAL BURGUNDY BANQUET',
    sampleCouple: 'Katarina & Nikolai',
    quote: 'Join us for an opulent candlelit ceremony and velvet gala reception',
    footer: 'Black Tie & Jewel Tones • Opera House Ballroom',
    swTitle: 'Velvet ya Zambarau-Damu',
    swBadge: 'KARAMU KUU YA KIFALME',
    swQuote: 'Ungana nasi kwa ibada ya mishumaa na karamu ya kifahari',
    swFooter: 'Mavazi ya Kifahari • Ukumbi wa Opera'
  },
  classic_pearl_elegance: {
    id: 'classic_pearl_elegance',
    number: 36,
    name: 'Pearl Elegance',
    category: 'classic',
    catName: 'Luxury & Classic',
    catIcon: '👑',
    tagline: 'Iridescent pearl white with draped pearl swags and fine framing',
    orientation: 'portrait',
    headerStyle: 'unified',
    showArtwork: true,
    artworkOpacity: 100,
    fontFamily: "'Cormorant Garamond', serif",
    titleFont: "'Cormorant Garamond', serif",
    scriptFont: "'Allura', cursive",
    bodyFont: "'Montserrat', sans-serif",
    headerColor1: '#FBF9FC',
    headerColor2: '#F3EFF6',
    headingColor: '#302A36',
    bodyBg: '#FBF9FC',
    textColor: '#302A36',
    accentColor: '#9B8FA6',
    borderColor: '#D5CEDB',
    borderFrame: 'none',
    seal: 'crown',
    texture: 'none',
    qrDark: '#18181B',
    canvaQrLight: '#FFFFFF',
    badge: 'PEARL JUBILEE & MATRIMONY',
    sampleCouple: 'Gwendolyn & Alistair',
    quote: 'Grace us with your presence at an evening adorned in pearls and white roses',
    footer: 'White & Silver Formal • Crystal Conservatory',
    swTitle: 'Uzuri wa Lulu',
    swBadge: 'SHEREHE YA LULU',
    swQuote: 'Tupambe kwa uwepo wako katika usiku uliopambwa kwa lulu na waridi nyeupe',
    swFooter: 'Mavazi ya Nyeupe na Fedha • Ukumbi wa Kristali'
  },
  classic_golden_frame: {
    id: 'classic_golden_frame',
    number: 37,
    name: 'Golden Frame',
    category: 'classic',
    catName: 'Luxury & Classic',
    catIcon: '👑',
    tagline: 'Triple fine metallic gold gallery framing and serif typography',
    orientation: 'portrait',
    headerStyle: 'unified',
    showArtwork: true,
    artworkOpacity: 100,
    fontFamily: "'Cinzel', serif",
    titleFont: "'Cinzel', serif",
    scriptFont: "'Alex Brush', cursive",
    bodyFont: "'Lato', sans-serif",
    headerColor1: '#FCF9F2',
    headerColor2: '#F5EDE0',
    headingColor: '#2E2418',
    bodyBg: '#FCF9F2',
    textColor: '#2E2418',
    accentColor: '#C59B38',
    borderColor: '#DFC278',
    borderFrame: 'none',
    seal: 'crown',
    texture: 'none',
    qrDark: '#18181B',
    canvaQrLight: '#FFFFFF',
    badge: 'GALLERY OF LOVE',
    sampleCouple: 'Arabella & Constantine',
    quote: 'Have the honour of inviting you to their wedding ceremony and gala banquet',
    footer: 'Formal Black Tie • National Portrait Gallery',
    swTitle: 'Fremu ya Dhahabu',
    swBadge: 'SANAA YA UPENDO',
    swQuote: 'Tuna heshima ya kukualika kwenye ibada ya harusi na karamu yetu',
    swFooter: 'Mavazi Rasmi ya Suti • Ukumbi wa Kitaifa'
  },
  classic_classic_crest: {
    id: 'classic_classic_crest',
    number: 38,
    name: 'Classic Crest',
    category: 'classic',
    catName: 'Luxury & Classic',
    catIcon: '👑',
    tagline: 'Heraldic royal shield, crown and laurel wreath crest composition',
    orientation: 'portrait',
    headerStyle: 'unified',
    showArtwork: true,
    artworkOpacity: 100,
    fontFamily: "'Cormorant Garamond', serif",
    titleFont: "'Cormorant Garamond', serif",
    scriptFont: "'Great Vibes', cursive",
    bodyFont: "'Montserrat', sans-serif",
    headerColor1: '#FAF6EE',
    headerColor2: '#F2EADF',
    headingColor: '#2B2218',
    bodyBg: '#FAF6EE',
    textColor: '#2B2218',
    accentColor: '#B89246',
    borderColor: '#D8C08E',
    borderFrame: 'none',
    seal: 'crown',
    texture: 'none',
    qrDark: '#18181B',
    canvaQrLight: '#FFFFFF',
    badge: 'BY ROYAL CREST & COVENANT',
    sampleCouple: 'Adelaide & Montgomery',
    quote: 'Request the pleasure of your company at the marriage of their children',
    footer: 'Formal Morning Dress or Black Tie • Royal Chapel',
    swTitle: 'Nembo ya Kifalme',
    swBadge: 'AGANO LA KIFALME',
    swQuote: 'Tunaomba uwepo wako katika sherehe ya ndoa ya watoto wetu',
    swFooter: 'Mavazi Rasmi ya Kifalme • Kanisa la Kifalme'
  },
  african_african_royalty: {
    id: 'african_african_royalty',
    number: 39,
    name: 'African Royalty',
    category: 'african',
    catName: 'African Luxury',
    catIcon: '🌍',
    tagline: 'Obsidian and Ashanti gold chevron ornamental patterns',
    orientation: 'portrait',
    headerStyle: 'unified',
    showArtwork: true,
    artworkOpacity: 100,
    fontFamily: "'Cinzel', serif",
    titleFont: "'Cinzel', serif",
    scriptFont: "'Great Vibes', cursive",
    bodyFont: "'Sora', sans-serif",
    headerColor1: '#14100C',
    headerColor2: '#1F1812',
    headingColor: '#F5D884',
    bodyBg: '#14100C',
    textColor: '#F7E6B5',
    accentColor: '#D4AF37',
    borderColor: '#D4AF37',
    borderFrame: 'none',
    seal: 'crown',
    texture: 'none',
    qrDark: '#14100C',
    canvaQrLight: '#F5D884',
    badge: 'ROYAL AFRICAN PROCLAMATION',
    sampleCouple: 'Zuri & Kwame',
    quote: 'Under the blessings of our elders and royal lineage, we invite you to celebrate with us',
    footer: 'Regal African Couture & Gold • Serengeti Grand Hall',
    swTitle: 'Ufalme wa Kiafrika',
    swBadge: 'TANGAZO LA KIFALME LA KIAFRIKA',
    swQuote: 'Chini ya baraka za wazee wetu, tunakualika kusherehekea pamoja nasi',
    swFooter: 'Mavazi ya Kifalme ya Kiafrika • Ukumbi wa Serengeti'
  },
  african_kitenge_elegance: {
    id: 'african_kitenge_elegance',
    number: 40,
    name: 'Kitenge Elegance',
    category: 'african',
    catName: 'African Luxury',
    catIcon: '🌍',
    tagline: 'Sophisticated indigo, marigold and terracotta kitenge motifs',
    orientation: 'portrait',
    headerStyle: 'unified',
    showArtwork: true,
    artworkOpacity: 100,
    fontFamily: "'Playfair Display', serif",
    titleFont: "'Playfair Display', serif",
    scriptFont: "'Allura', cursive",
    bodyFont: "'Outfit', sans-serif",
    headerColor1: '#FBF6EE',
    headerColor2: '#F3E9D8',
    headingColor: '#1A2B48',
    bodyBg: '#FBF6EE',
    textColor: '#1A2B48',
    accentColor: '#C88B28',
    borderColor: '#DFBA78',
    borderFrame: 'none',
    seal: 'crown',
    texture: 'none',
    qrDark: '#18181B',
    canvaQrLight: '#FFFFFF',
    badge: 'KITENGE HAUTE COUTURE',
    sampleCouple: 'Neema & Baraka',
    quote: 'Celebrate vibrant heritage, joyful union, and East African elegance with our families',
    footer: 'Luxury Kitenge & Formal Attire • Dar es Salaam Ballroom',
    swTitle: 'Uzuri wa Kitenge',
    swBadge: 'SHEREHE YA KITENGE CHA KIFAHARI',
    swQuote: 'Sherehekea utamaduni wetu na muungano wenye furaha pamoja na familia zetu',
    swFooter: 'Mavazi ya Kitenge cha Kifahari • Ukumbi wa Dar es Salaam'
  },
  african_swahili_heritage: {
    id: 'african_swahili_heritage',
    number: 41,
    name: 'Swahili Heritage',
    category: 'african',
    catName: 'African Luxury',
    catIcon: '🌍',
    tagline: 'Carved Zanzibar arched doorway and brass rosette cultural elements',
    orientation: 'portrait',
    headerStyle: 'unified',
    showArtwork: true,
    artworkOpacity: 100,
    fontFamily: "'Cormorant Garamond', serif",
    titleFont: "'Cormorant Garamond', serif",
    scriptFont: "'Great Vibes', cursive",
    bodyFont: "'Lato', sans-serif",
    headerColor1: '#FBF6EC',
    headerColor2: '#F2E8D5',
    headingColor: '#3B2616',
    bodyBg: '#FBF6EC',
    textColor: '#3B2616',
    accentColor: '#B8893A',
    borderColor: '#D6B67C',
    borderFrame: 'none',
    seal: 'crown',
    texture: 'none',
    qrDark: '#18181B',
    canvaQrLight: '#FFFFFF',
    badge: 'KARIBU KATIKA SHEREHE • SWAHILI HERITAGE',
    sampleCouple: 'Halima & Tariq',
    quote: 'With coastal grace, spice-scented breezes, and Swahili hospitality, we welcome you',
    footer: 'Swahili Royal Attire • Stone Town Palace Courtyard',
    swTitle: 'Urithi wa Waswahili',
    swBadge: 'KARIBU KATIKA SHEREHE YETU',
    swQuote: 'Kwa ukarimu wa Pwani na heshima ya Kiswahili, tunakukaribisha sana',
    swFooter: 'Mavazi ya Kistaha ya Kiswahili • Uwanja wa Mji Mkongwe'
  },
  african_african_botanical: {
    id: 'african_african_botanical',
    number: 42,
    name: 'African Botanical',
    category: 'african',
    catName: 'African Luxury',
    catIcon: '🌍',
    tagline: 'King Protea and Bird of Paradise tropical African florals',
    orientation: 'portrait',
    headerStyle: 'unified',
    showArtwork: true,
    artworkOpacity: 100,
    fontFamily: "'Playfair Display', serif",
    titleFont: "'Playfair Display', serif",
    scriptFont: "'Alex Brush', cursive",
    bodyFont: "'Montserrat', sans-serif",
    headerColor1: '#FAF5EE',
    headerColor2: '#F2E8DC',
    headingColor: '#36241E',
    bodyBg: '#FAF5EE',
    textColor: '#36241E',
    accentColor: '#B85C4E',
    borderColor: '#DFB8AE',
    borderFrame: 'none',
    seal: 'crown',
    texture: 'none',
    qrDark: '#18181B',
    canvaQrLight: '#FFFFFF',
    badge: 'AFRICAN BOTANICAL LUXURY',
    sampleCouple: 'Amara & Tendai',
    quote: 'Surrounded by blooming King Proteas and Birds of Paradise, join our celebration',
    footer: 'Afro-Modern Formal • Kilimanjaro Garden Pavilion',
    swTitle: 'Mimea ya Kiafrika',
    swBadge: 'UFAHARI WA MAUA YA KIAFRIKA',
    swQuote: 'Tukizungukwa na maua ya Protea na Strelitzia, ungana nasi kusherehekea',
    swFooter: 'Mavazi Rasmi ya Kiafrika • Bustani ya Kilimanjaro'
  },
  african_zanzibar_romance: {
    id: 'african_zanzibar_romance',
    number: 43,
    name: 'Zanzibar Romance',
    category: 'african',
    catName: 'African Luxury',
    catIcon: '🌍',
    tagline: 'Coastal-inspired ivory and sapphire blue design with sailing dhow',
    orientation: 'portrait',
    headerStyle: 'unified',
    showArtwork: true,
    artworkOpacity: 100,
    fontFamily: "'Cormorant Garamond', serif",
    titleFont: "'Cormorant Garamond', serif",
    scriptFont: "'Parisienne', cursive",
    bodyFont: "'Lato', sans-serif",
    headerColor1: '#F8FBFD',
    headerColor2: '#EBF3F9',
    headingColor: '#163A59',
    bodyBg: '#F8FBFD',
    textColor: '#163A59',
    accentColor: '#1E5B88',
    borderColor: '#A9CBE4',
    borderFrame: 'none',
    seal: 'crown',
    texture: 'none',
    qrDark: '#18181B',
    canvaQrLight: '#FFFFFF',
    badge: 'ZANZIBAR ISLAND ROMANCE',
    sampleCouple: 'Leila & Rashid',
    quote: 'As white dhow sails catch the Indian Ocean breeze, join us on the shores of Zanzibar',
    footer: 'Coastal White & Sapphire • Kendwa Beach Resort',
    swTitle: 'Mapenzi ya Zanzibar',
    swBadge: 'SHEREHE YA KISIWANI ZANZIBAR',
    swQuote: 'Jahazi linapopasua mawimbi ya Bahari ya Hindi, ungana nasi ufukweni Zanzibar',
    swFooter: 'Mavazi ya Nyeupe na Bluu • Ufukwe wa Kendwa'
  },
  african_african_gold: {
    id: 'african_african_gold',
    number: 44,
    name: 'African Gold',
    category: 'african',
    catName: 'African Luxury',
    catIcon: '🌍',
    tagline: 'Warm gold and earthy Mali mudcloth geometric patterns',
    orientation: 'portrait',
    headerStyle: 'unified',
    showArtwork: true,
    artworkOpacity: 100,
    fontFamily: "'Cinzel', serif",
    titleFont: "'Cinzel', serif",
    scriptFont: "'Allura', cursive",
    bodyFont: "'Sora', sans-serif",
    headerColor1: '#FAF3E6',
    headerColor2: '#F2E5CE',
    headingColor: '#382614',
    bodyBg: '#FAF3E6',
    textColor: '#382614',
    accentColor: '#C48E32',
    borderColor: '#DFBC7C',
    borderFrame: 'none',
    seal: 'crown',
    texture: 'none',
    qrDark: '#18181B',
    canvaQrLight: '#FFFFFF',
    badge: 'GOLDEN SAVANNAH HERITAGE',
    sampleCouple: 'Nia & Juma',
    quote: 'Woven in threads of African gold and ancestral pride, we invite you to our celebration',
    footer: 'Gold & Earthy Regalia • Ngorongoro Royal Terrace',
    swTitle: 'Dhahabu ya Afrika',
    swBadge: 'URITHI WA DHAHABU YA AFRIKA',
    swQuote: 'Katika uzi wa dhahabu na fahari ya asili yetu, tunakualika kusherehekea',
    swFooter: 'Mavazi ya Dhahabu na Rangi za Ardhi • Ukumbi wa Ngorongoro'
  },
  african_kanga_celebration: {
    id: 'african_kanga_celebration',
    number: 45,
    name: 'Kanga Celebration',
    category: 'african',
    catName: 'African Luxury',
    catIcon: '🌍',
    tagline: 'Tasteful Tanzanian kanga paisley borders and Swahili blessing',
    orientation: 'portrait',
    headerStyle: 'unified',
    showArtwork: true,
    artworkOpacity: 100,
    fontFamily: "'Playfair Display', serif",
    titleFont: "'Playfair Display', serif",
    scriptFont: "'Great Vibes', cursive",
    bodyFont: "'Poppins', sans-serif",
    headerColor1: '#FFF9F2',
    headerColor2: '#F9EFE2',
    headingColor: '#4A1224',
    bodyBg: '#FFF9F2',
    textColor: '#4A1224',
    accentColor: '#8C1D3B',
    borderColor: '#E5B869',
    borderFrame: 'none',
    seal: 'crown',
    texture: 'none',
    qrDark: '#18181B',
    canvaQrLight: '#FFFFFF',
    badge: 'SHEREHE YA KANGA • BLESSED UNION',
    sampleCouple: 'Zawadi & Omari',
    quote: "'Upendo ni tunu ya maisha' — Join us in a joyous Tanzanian celebration of love and unity",
    footer: 'Elegant Kanga / Kitenge Attire • Mlimani City Grand Hall',
    swTitle: 'Sherehe ya Kanga',
    swBadge: 'UPENDO NI TUNU YA MAISHA',
    swQuote: 'Ungana nasi katika sherehe yenye furaha, upendo na mshikamano',
    swFooter: 'Mavazi ya Kanga za Kifahari • Ukumbi wa Mlimani City'
  },
  modern_birthday_luxe: {
    id: 'modern_birthday_luxe',
    number: 46,
    name: 'Birthday Luxe',
    category: 'celebrations',
    catName: 'Modern Celebrations',
    catIcon: '🥂',
    tagline: 'Champagne gold balloons, fine confetti and luxury birthday typography',
    orientation: 'portrait',
    headerStyle: 'unified',
    showArtwork: true,
    artworkOpacity: 100,
    fontFamily: "'Playfair Display', serif",
    titleFont: "'Playfair Display', serif",
    scriptFont: "'Great Vibes', cursive",
    bodyFont: "'Montserrat', sans-serif",
    headerColor1: '#FBF7F0',
    headerColor2: '#F4EBDD',
    headingColor: '#2E2318',
    bodyBg: '#FBF7F0',
    textColor: '#2E2318',
    accentColor: '#C89B3C',
    borderColor: '#E2C88A',
    borderFrame: 'none',
    seal: 'crown',
    texture: 'none',
    qrDark: '#18181B',
    canvaQrLight: '#FFFFFF',
    badge: 'MILESTONE BIRTHDAY SOIRÉE',
    sampleCouple: 'Lady Gabrielle Msuya',
    quote: 'Join us for champagne, live jazz, and an unforgettable milestone birthday celebration',
    footer: 'Cocktail Glamour & Gold • Rooftop Lounge',
    swTitle: 'Sherehe ya Kuzaliwa ya Kifahari',
    swBadge: 'SHEREHE KUU YA KUZALIWA',
    swQuote: 'Ungana nasi kwa vinywaji, muziki na kusherehekea siku maalum ya kuzaliwa',
    swFooter: 'Mavazi ya Kifahari na Dhahabu • Ukumbi wa Juu'
  },
  modern_graduation_prestige: {
    id: 'modern_graduation_prestige',
    number: 47,
    name: 'Graduation Prestige',
    category: 'celebrations',
    catName: 'Modern Celebrations',
    catIcon: '🥂',
    tagline: 'Academic mortarboard cap, golden laurel wreath and prestige framing',
    orientation: 'portrait',
    headerStyle: 'unified',
    showArtwork: true,
    artworkOpacity: 100,
    fontFamily: "'Cinzel', serif",
    titleFont: "'Cinzel', serif",
    scriptFont: "'Allura', cursive",
    bodyFont: "'Lato', sans-serif",
    headerColor1: '#FAF7F0',
    headerColor2: '#F1EADF',
    headingColor: '#16243B',
    bodyBg: '#FAF7F0',
    textColor: '#16243B',
    accentColor: '#C59A38',
    borderColor: '#DCC07C',
    borderFrame: 'none',
    seal: 'crown',
    texture: 'none',
    qrDark: '#18181B',
    canvaQrLight: '#FFFFFF',
    badge: 'ACADEMIC EXCELLENCE & GRADUATION',
    sampleCouple: 'Dr. Kelvin Massawe, MD',
    quote: 'In celebration of dedication, scholarly achievement, and the conferring of honours',
    footer: 'Formal Academic & Evening Attire • Convocation Banquet Hall',
    swTitle: 'Heshima ya Mahafali',
    swBadge: 'SHUKRANI NA USHINDI WA MASOMO',
    swQuote: 'Kusherehekea bidii, mafanikio ya kitaaluma na kutunukiwa shahada',
    swFooter: 'Mavazi Rasmi ya Heshima • Ukumbi wa Mahafali'
  },
  modern_corporate_prestige: {
    id: 'modern_corporate_prestige',
    number: 48,
    name: 'Corporate Prestige',
    category: 'celebrations',
    catName: 'Modern Celebrations',
    catIcon: '🥂',
    tagline: 'Executive architectural lines and authoritative serif/sans pairing',
    orientation: 'portrait',
    headerStyle: 'unified',
    showArtwork: true,
    artworkOpacity: 100,
    fontFamily: "'Marcellus', serif",
    titleFont: "'Marcellus', serif",
    scriptFont: "'Parisienne', cursive",
    bodyFont: "'Inter', sans-serif",
    headerColor1: '#F8FAFC',
    headerColor2: '#EEF2F6',
    headingColor: '#0F2540',
    bodyBg: '#F8FAFC',
    textColor: '#0F2540',
    accentColor: '#C59B38',
    borderColor: '#CBD5E1',
    borderFrame: 'none',
    seal: 'crown',
    texture: 'none',
    qrDark: '#18181B',
    canvaQrLight: '#FFFFFF',
    badge: 'EXECUTIVE SUMMIT & GALA DINNER',
    sampleCouple: 'East Africa Leadership Forum',
    quote: 'The Board of Directors cordially invites you to the Annual Executive Gala & Awards',
    footer: 'Business Formal / Black Tie • Serena Diplomatic Ballroom',
    swTitle: 'Hafla Rasmi ya Kampuni',
    swBadge: 'MKUTANO MKUU NA CHAKULA CHA JIONI',
    swQuote: 'Bodi ya Wakurugenzi inakualika kwenye Hafla ya Mwaka ya Viongozi na Tuzo',
    swFooter: 'Suti Rasmi ya Kazi • Ukumbi wa Serena'
  },
  modern_gala_night: {
    id: 'modern_gala_night',
    number: 49,
    name: 'Gala Night',
    category: 'celebrations',
    catName: 'Modern Celebrations',
    catIcon: '🥂',
    tagline: 'Midnight black, gold sunburst rays and starlight champagne',
    orientation: 'portrait',
    headerStyle: 'unified',
    showArtwork: true,
    artworkOpacity: 100,
    fontFamily: "'Cinzel', serif",
    titleFont: "'Cinzel', serif",
    scriptFont: "'Great Vibes', cursive",
    bodyFont: "'Montserrat', sans-serif",
    headerColor1: '#0B0D13',
    headerColor2: '#131722',
    headingColor: '#F5DC90',
    bodyBg: '#0B0D13',
    textColor: '#F7E7B8',
    accentColor: '#D4AF37',
    borderColor: '#D4AF37',
    borderFrame: 'none',
    seal: 'crown',
    texture: 'none',
    qrDark: '#0B0D13',
    canvaQrLight: '#F5DC90',
    badge: 'STARLIGHT CHARITY GALA NIGHT',
    sampleCouple: 'The Royal Philanthropy Ball',
    quote: 'An enchanting evening of symphony orchestra, fine dining, and black-tie elegance',
    footer: 'Strict Black Tie & Evening Gown • Grand Starlight Ballroom',
    swTitle: 'Usiku wa Gala ya Nyota',
    swBadge: 'USIKU WA GALA NA HISANI',
    swQuote: 'Jioni ya kipekee ya muziki wa okestra, chakula bora na mavazi ya heshima',
    swFooter: 'Suti Rasmi na Gauni la Usiku • Ukumbi wa Nyota'
  },
  modern_engagement_romance: {
    id: 'modern_engagement_romance',
    number: 50,
    name: 'Engagement Romance',
    category: 'celebrations',
    catName: 'Modern Celebrations',
    catIcon: '🥂',
    tagline: 'Interlocked diamond rings and romantic blush floral arrangements',
    orientation: 'portrait',
    headerStyle: 'unified',
    showArtwork: true,
    artworkOpacity: 100,
    fontFamily: "'Cormorant Garamond', serif",
    titleFont: "'Cormorant Garamond', serif",
    scriptFont: "'Great Vibes', cursive",
    bodyFont: "'Montserrat', sans-serif",
    headerColor1: '#FFF9FA',
    headerColor2: '#FDF0F2',
    headingColor: '#42252A',
    bodyBg: '#FFF9FA',
    textColor: '#42252A',
    accentColor: '#C98A5B',
    borderColor: '#E8C5CC',
    borderFrame: 'none',
    seal: 'crown',
    texture: 'none',
    qrDark: '#18181B',
    canvaQrLight: '#FFFFFF',
    badge: 'ENGAGEMENT CELEBRATION',
    sampleCouple: 'Naomi & Emmanuel',
    quote: 'We said yes to forever! Join us for champagne toasts and romantic celebration',
    footer: 'Romantic Cocktail Attire • Rose Terrace Lounge',
    swTitle: 'Sherehe ya Uchumba',
    swBadge: 'TUMECHUMBIANA RASMI',
    swQuote: 'Tumekubali kuwa pamoja milele! Ungana nasi kusherehekea uchumba wetu',
    swFooter: 'Mavazi ya Kifahari ya Jioni • Ukumbi wa Waridi'
  },
};

// Populate original SVG decorativeArtwork on all 50 templates at initialization
function hydrateAllTemplatesArtwork() {
  Object.keys(TEMPLATES_LIBRARY).forEach((id) => {
    const tpl = TEMPLATES_LIBRARY[id];
    if (tpl && !tpl.decorativeArtwork) {
      tpl.decorativeArtwork = generateTemplateArtworkSvg(id, tpl.accentColor);
    }
  });
}
hydrateAllTemplatesArtwork();

// Load any user-created custom templates saved in localStorage
try {
  const savedCustom = JSON.parse(localStorage.getItem('leula_custom_templates') || '[]');
  if (Array.isArray(savedCustom)) {
    savedCustom.forEach((ct) => {
      if (ct && ct.id && !TEMPLATES_LIBRARY[ct.id]) {
        Object.defineProperty(TEMPLATES_LIBRARY, ct.id, {
          value: ct,
          writable: true,
          configurable: true,
          enumerable: false // Keep main enumeration strictly 50 built-in templates unless queried
        });
      }
    });
  }
} catch (err) {}

// Non-enumerable backward-compatible alias for legacy default template ID
if (!TEMPLATES_LIBRARY.wedding_royal_gold) {
  Object.defineProperty(TEMPLATES_LIBRARY, 'wedding_royal_gold', {
    value: TEMPLATES_LIBRARY.wedding_ivory_bloom,
    writable: false,
    configurable: true,
    enumerable: false
  });
}

// ============================================================
// TEMPLATE GALLERY MARKETPLACE ENGINE (LARGE CARD PREVIEWS)
// ============================================================

let currentTplCategory = 'all';
let currentTplSearch = '';
let currentLanguage = 'en';

function getFavoriteTemplates() {
  try {
    return JSON.parse(localStorage.getItem('leula_favorite_templates') || '[]');
  } catch (e) {
    return [];
  }
}

function getRecentTemplates() {
  try {
    return JSON.parse(localStorage.getItem('leula_recent_templates') || '[]');
  } catch (e) {
    return [];
  }
}

function recordRecentTemplate(tplId) {
  try {
    let recents = getRecentTemplates().filter((id) => id !== tplId);
    recents.unshift(tplId);
    localStorage.setItem('leula_recent_templates', JSON.stringify(recents.slice(0, 15)));
  } catch (e) {}
}

function toggleFavoriteTemplate(tplId, e) {
  if (e) e.stopPropagation();
  let favs = getFavoriteTemplates();
  if (favs.includes(tplId)) {
    favs = favs.filter((id) => id !== tplId);
    if (typeof showToast === 'function') showToast('Removed from favourites', 'info');
  } else {
    favs.push(tplId);
    if (typeof showToast === 'function') showToast('Saved to Favourites! ⭐', 'success');
  }
  localStorage.setItem('leula_favorite_templates', JSON.stringify(favs));
  renderTemplatesLibrary(currentTplCategory, currentTplSearch);
}

function filterTemplatesByCategory(catId) {
  currentTplCategory = catId;
  document.querySelectorAll('.tpl-filter-pill').forEach((btn) => {
    btn.classList.toggle('active', btn.dataset.cat === catId);
  });
  renderTemplatesLibrary(catId, currentTplSearch);
}

function onTemplateSearchInput(query) {
  currentTplSearch = (query || '').trim().toLowerCase();
  renderTemplatesLibrary(currentTplCategory, currentTplSearch);
}

function renderTemplatesLibrary(filterCat = 'all', searchQuery = '') {
  const container = document.getElementById('templatesMarketplaceGrid');
  if (!container) return;

  hydrateAllTemplatesArtwork();

  const favs = getFavoriteTemplates();
  const recents = getRecentTemplates();
  const baseTemplates = Object.values(TEMPLATES_LIBRARY);

  // Also include user-saved custom templates when viewing 'all' or 'recent'
  let customTemplates = [];
  try {
    customTemplates = JSON.parse(localStorage.getItem('leula_custom_templates') || '[]');
    customTemplates.forEach((ct) => {
      if (ct && ct.id && !TEMPLATES_LIBRARY[ct.id]) {
        Object.defineProperty(TEMPLATES_LIBRARY, ct.id, {
          value: ct, writable: true, configurable: true, enumerable: false
        });
      }
    });
  } catch (e) {}

  const pool = (filterCat === 'all' && customTemplates.length > 0)
    ? [...customTemplates, ...baseTemplates]
    : baseTemplates;

  let filtered = pool.filter((tpl) => {
    if (filterCat === 'favorites') {
      if (!favs.includes(tpl.id)) return false;
    } else if (filterCat === 'recent') {
      if (!recents.includes(tpl.id)) return false;
    } else if (filterCat !== 'all' && tpl.category !== filterCat) {
      return false;
    }
    if (searchQuery) {
      const hay = `${tpl.name} ${tpl.catName} ${tpl.tagline || ''} ${tpl.badge || ''} ${tpl.sampleCouple || ''}`.toLowerCase();
      if (!hay.includes(searchQuery)) return false;
    }
    return true;
  });

  if (filterCat === 'recent') {
    filtered.sort((a, b) => recents.indexOf(a.id) - recents.indexOf(b.id));
  }

  const countEl = document.getElementById('tplResultsCount');
  if (countEl) {
    countEl.textContent = `${filtered.length} of ${baseTemplates.length} luxury templates`;
  }

  if (filtered.length === 0) {
    container.innerHTML = `
      <div style="grid-column: 1 / -1; text-align: center; padding: 40px 20px; background: var(--surface-2); border-radius: var(--radius-sm); border: 1px dashed var(--border);">
        <div style="font-size: 2rem; margin-bottom: 8px;">🔍</div>
        <div style="font-weight: 700; color: var(--text-primary); margin-bottom: 4px;">No matching templates found</div>
        <div style="font-size: 0.78rem; color: var(--text-muted); margin-bottom: 12px;">Try clearing your search or switching collections.</div>
        <button type="button" class="btn btn-secondary btn-sm" onclick="filterTemplatesByCategory('all'); const s=document.getElementById('tplSearchInput'); if(s) s.value=''; currentTplSearch='';">
          Show All 50 Luxury Templates
        </button>
      </div>
    `;
    return;
  }

  container.innerHTML = filtered.map((tpl) => {
    const isFav = favs.includes(tpl.id);
    const isActive = (typeof state !== 'undefined' && state && state.template === tpl.id);
    const svgArt = tpl.decorativeArtwork || generateTemplateArtworkSvg(tpl.id, tpl.accentColor);

    return `
      <div class="tpl-card-item ${isActive ? 'active' : ''}" onclick="customizeTemplate('${tpl.id}', event)" title="Click to customize ${tpl.name}" data-template-id="${tpl.id}">
        <!-- Full Miniature Stationery Card Preview -->
        <div class="tpl-card-swatch tpl-stationery-preview" style="background: ${tpl.bodyBg}; color: ${tpl.textColor}; position: relative; height: 248px; overflow: hidden; display: flex; flex-direction: column; justify-content: center; align-items: center; padding: 22px 18px; text-align: center;">
          <div class="tpl-preview-svg-layer" style="position:absolute; inset:0; width:100%; height:100%; pointer-events:none; z-index:1;">
            ${svgArt}
          </div>
          <div class="tpl-card-category-tag" style="z-index:3; background: rgba(15,23,42,0.72); color:#fff; backdrop-filter: blur(4px);">
            ${tpl.catIcon} ${tpl.number ? '#' + tpl.number : ''} ${tpl.catName}
          </div>
          <button type="button" class="tpl-fav-btn ${isFav ? 'favorited' : ''}" style="z-index:3;" onclick="toggleFavoriteTemplate('${tpl.id}', event)" title="${isFav ? 'Remove from favourites' : 'Save to favourites'}">
            ${isFav ? '⭐' : '☆'}
          </button>

          <!-- Miniature Editorial Stationery Typography Layer -->
          <div style="position:relative; z-index:2; max-width: 88%; display:flex; flex-direction:column; align-items:center; gap: 4px; margin-top: 12px;">
            <div style="font-family: ${tpl.bodyFont || "'Montserrat', sans-serif"}; font-size: 0.52rem; font-weight: 700; letter-spacing: 1.6px; text-transform: uppercase; color: ${tpl.accentColor}; opacity: 0.92;">
              ${tpl.badge}
            </div>
            <div style="font-family: ${tpl.scriptFont || "'Great Vibes', cursive"}; font-size: 1.42rem; line-height: 1.15; color: ${tpl.headingColor}; margin: 2px 0;">
              ${tpl.sampleCouple || tpl.name}
            </div>
            <div style="font-family: ${tpl.titleFont || tpl.fontFamily}; font-size: 0.68rem; font-weight: 600; letter-spacing: 0.6px; color: ${tpl.textColor}; opacity: 0.88;">
              ${tpl.name}
            </div>
            <div style="width: 36px; height: 1px; background: ${tpl.accentColor}; opacity: 0.55; margin: 3px 0;"></div>
            <div style="font-family: ${tpl.bodyFont || "'Montserrat', sans-serif"}; font-size: 0.52rem; letter-spacing: 1px; text-transform: uppercase; color: ${tpl.textColor}; opacity: 0.72;">
              SAT • NOV 21, 2026 • DAR ES SALAAM
            </div>
          </div>
        </div>

        <!-- Meta Footer & Action Buttons -->
        <div class="tpl-card-footer">
          <div class="tpl-card-info">
            <div style="display:flex; align-items:center; justify-content:space-between; gap:4px;">
              <div class="tpl-card-name">${tpl.name}</div>
              <span style="font-size:0.62rem; padding:1px 6px; border-radius:99px; background:var(--surface-2); color:var(--text-muted); font-weight:600;">Portrait</span>
            </div>
            <div class="tpl-card-tagline">${tpl.tagline || ''}</div>
          </div>
          <div class="tpl-card-actions">
            <button type="button" class="btn btn-sm btn-primary tpl-use-btn" onclick="customizeTemplate('${tpl.id}', event)" title="Open in live canvas editor">
              ✨ Customize
            </button>
            <button type="button" class="btn btn-sm btn-secondary tpl-preview-btn" onclick="previewTemplateModal('${tpl.id}', event)" title="Large Full-Card Preview">
              👁️
            </button>
            <button type="button" class="btn btn-sm btn-secondary tpl-dup-btn" onclick="duplicateTemplate('${tpl.id}', event)" title="Duplicate Template">
              📑
            </button>
          </div>
        </div>
      </div>
    `;
  }).join('');
}

// Customize Template & Connect Directly to Editing Canvas
function customizeTemplate(tplId, e) {
  if (e) e.stopPropagation();
  const tpl = TEMPLATES_LIBRARY[tplId];
  if (!tpl) return;

  if (window.isDesignDirty && typeof showConfirmDialog === 'function') {
    showConfirmDialog({
      title: 'Load Luxury Template?',
      subtitle: 'Replace current unsaved canvas design',
      message: `Load "${tpl.name}" into the invitation studio? Unsaved changes on the current canvas will be replaced.`,
      confirmText: 'Load Template',
      cancelText: 'Keep Current',
      type: 'warning'
    }).then(function (confirmed) {
      if (confirmed) executeCustomizeTemplate(tplId);
    });
  } else {
    executeCustomizeTemplate(tplId);
  }
}

function executeCustomizeTemplate(tplId) {
  const tpl = TEMPLATES_LIBRARY[tplId];
  if (!tpl) return;

  recordRecentTemplate(tplId);

  if (typeof applyTemplate === 'function') {
    applyTemplate(tplId);
  }

  const banner = document.getElementById('activeTemplateBanner');
  const nameEl = document.getElementById('activeTemplateName');
  if (banner) banner.style.display = 'flex';
  if (nameEl) nameEl.textContent = tpl.name;

  window.isDesignDirty = false;

  if (typeof showToast === 'function') {
    showToast(`Loaded "${tpl.name}" into the luxury studio!`, 'success');
  }
}

// Duplicate Template Functionality
function duplicateTemplate(tplId, e) {
  if (e) e.stopPropagation();
  const source = TEMPLATES_LIBRARY[tplId];
  if (!source) return;

  const copyId = `${tplId}_copy_${Date.now()}`;
  const copyName = `${source.name} (Copy)`;
  const copyTpl = Object.assign({}, source, {
    id: copyId,
    name: copyName,
    tagline: `Custom edition of ${source.name}`
  });

  Object.defineProperty(TEMPLATES_LIBRARY, copyId, {
    value: copyTpl, writable: true, configurable: true, enumerable: false
  });

  try {
    const customList = JSON.parse(localStorage.getItem('leula_custom_templates') || '[]');
    customList.unshift(copyTpl);
    localStorage.setItem('leula_custom_templates', JSON.stringify(customList.slice(0, 30)));
  } catch (err) {}

  renderTemplatesLibrary(currentTplCategory, currentTplSearch);

  if (typeof showToast === 'function') {
    showToast(`Duplicated "${source.name}"! Opening in studio...`, 'success');
  }
  executeCustomizeTemplate(copyId);
}

// Save Current Canvas as a Custom Template in Library
function saveCurrentDesignAsTemplate() {
  if (typeof state === 'undefined' || !state) return;
  const baseTpl = TEMPLATES_LIBRARY[state.template] || TEMPLATES_LIBRARY.wedding_ivory_bloom;
  const customId = `custom_tpl_${Date.now()}`;
  const customName = (state.customTitle && state.customTitle.trim())
    ? `${state.customTitle.trim()} (Saved)`
    : `${baseTpl.name} (Custom)`;

  const newTpl = Object.assign({}, baseTpl, JSON.parse(JSON.stringify(state)), {
    id: customId,
    name: customName,
    catName: 'Saved Custom',
    catIcon: '💎',
    tagline: 'Saved custom design from Invitation Studio'
  });

  Object.defineProperty(TEMPLATES_LIBRARY, customId, {
    value: newTpl, writable: true, configurable: true, enumerable: false
  });

  try {
    const customList = JSON.parse(localStorage.getItem('leula_custom_templates') || '[]');
    customList.unshift(newTpl);
    localStorage.setItem('leula_custom_templates', JSON.stringify(customList.slice(0, 30)));
  } catch (err) {}

  recordRecentTemplate(customId);
  renderTemplatesLibrary(currentTplCategory, currentTplSearch);

  if (typeof showToast === 'function') {
    showToast(`Saved "${customName}" to your Template Library!`, 'success');
  }
}

// Full-Resolution Luxury Stationery Modal Preview
function previewTemplateModal(tplId, e) {
  if (e) e.stopPropagation();
  const tpl = TEMPLATES_LIBRARY[tplId];
  if (!tpl) return;

  const modal = document.getElementById('templatePreviewModal');
  const body = document.getElementById('modalPreviewContent');
  if (!modal || !body) return;

  const svgArt = tpl.decorativeArtwork || generateTemplateArtworkSvg(tpl.id, tpl.accentColor);

  body.innerHTML = `
    <div style="background: ${tpl.bodyBg}; color: ${tpl.textColor}; border-radius: 18px; overflow: hidden; box-shadow: 0 24px 60px rgba(0,0,0,0.32); border: 1px solid ${tpl.borderColor || '#E4E4E7'}; width: 380px; min-height: 560px; max-width: 100%; margin: 0 auto; text-align: center; position: relative; display: flex; flex-direction: column; justify-content: space-between; padding: 42px 34px 34px;">
      <!-- Full-Card Original Vector Artwork Layer -->
      <div style="position:absolute; inset:0; width:100%; height:100%; pointer-events:none; z-index:1;">
        ${svgArt}
      </div>

      <!-- Top Header Section -->
      <div style="position:relative; z-index:2;">
        <div style="display:inline-block; font-family: ${tpl.bodyFont || "'Montserrat', sans-serif"}; font-size: 0.65rem; font-weight: 700; letter-spacing: 2px; text-transform: uppercase; padding: 4px 12px; border-radius: 99px; color: ${tpl.accentColor}; border: 1px solid ${tpl.accentColor}55; margin-bottom: 12px;">
          ${tpl.badge}
        </div>
        <div style="font-family: ${tpl.scriptFont || "'Great Vibes', cursive"}; font-size: 2.35rem; line-height: 1.15; color: ${tpl.headingColor}; margin-bottom: 6px;">
          ${tpl.sampleCouple || tpl.name}
        </div>
        <div style="font-family: ${tpl.titleFont || tpl.fontFamily}; font-size: 0.95rem; font-weight: 600; letter-spacing: 1.2px; text-transform: uppercase; color: ${tpl.textColor};">
          ${tpl.name}
        </div>
      </div>

      <!-- Middle Editorial Body & QR -->
      <div style="position:relative; z-index:2; my: auto;">
        <div style="font-family: ${tpl.titleFont || tpl.fontFamily}; font-size: 0.84rem; font-style: italic; opacity: 0.88; line-height: 1.5; max-width: 270px; margin: 12px auto 14px;">
          "${tpl.quote}"
        </div>
        <div style="font-family: ${tpl.bodyFont || "'Montserrat', sans-serif"}; font-size: 0.62rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1.8px; color: ${tpl.accentColor}; margin-bottom: 3px;">
          HONOURED GUEST
        </div>
        <div style="font-family: ${tpl.titleFont || tpl.fontFamily}; font-size: 1.18rem; font-weight: 700; margin-bottom: 12px;">
          Amb. Victoria Mwakasege
        </div>

        <div style="width: 104px; height: 104px; margin: 0 auto 10px; background: ${tpl.canvaQrLight || '#FFFFFF'}; border-radius: 12px; padding: 7px; box-shadow: 0 6px 20px rgba(0,0,0,0.10); border: 1px solid ${tpl.accentColor}44; display: flex; align-items: center; justify-content: center;">
          <div id="modalMockQr_${tpl.id}"></div>
        </div>

        <div style="font-family: ${tpl.bodyFont || "'Montserrat', sans-serif"}; font-size: 0.72rem; font-weight: 600; letter-spacing: 0.5px; margin-bottom: 4px;">
          Saturday, November 21, 2026 • 18:00 EAT
        </div>
        <div style="font-family: ${tpl.bodyFont || "'Montserrat', sans-serif"}; font-size: 0.68rem; color: ${tpl.accentColor}; font-weight: 700;">
          The Kilimanjaro Grand Ballroom • Table 01
        </div>
      </div>

      <!-- Footer -->
      <div style="position:relative; z-index:2; font-family: ${tpl.bodyFont || "'Montserrat', sans-serif"}; font-size: 0.62rem; letter-spacing: 0.8px; text-transform: uppercase; opacity: 0.72; padding-top: 8px; border-top: 1px solid ${tpl.accentColor}33;">
        ${tpl.footer}
      </div>
    </div>

    <div style="margin-top: 16px; display: flex; gap: 8px; justify-content: center; flex-wrap: wrap;">
      <button type="button" class="btn btn-primary btn-sm" onclick="customizeTemplate('${tpl.id}'); closeTemplatePreviewModal();" style="padding: 8px 18px; font-weight: 700;">
        ✨ Customize This Template
      </button>
      <button type="button" class="btn btn-secondary btn-sm" onclick="duplicateTemplate('${tpl.id}', event); closeTemplatePreviewModal();" style="padding: 8px 14px;">
        📑 Duplicate
      </button>
      <button type="button" class="btn btn-secondary btn-sm" onclick="closeTemplatePreviewModal()">
        Close
      </button>
    </div>
  `;

  modal.style.display = 'flex';

  setTimeout(() => {
    const qrContainer = document.getElementById(`modalMockQr_${tpl.id}`);
    if (qrContainer && typeof QRCode !== 'undefined') {
      qrContainer.innerHTML = '';
      new QRCode(qrContainer, {
        text: `https://leulaevents.com/pass.php?preview=${tpl.id}`,
        width: 88,
        height: 88,
        colorDark: tpl.qrDark || '#18181B',
        colorLight: tpl.canvaQrLight || '#FFFFFF',
        correctLevel: QRCode.CorrectLevel.M
      });
    }
  }, 40);
}

function closeTemplatePreviewModal() {
  const modal = document.getElementById('templatePreviewModal');
  if (modal) modal.style.display = 'none';
}

// Bilingual English / Swahili Switcher
function setDesignerLanguage(lang) {
  currentLanguage = lang;
  const btnEn = document.getElementById('langToggleEn');
  const btnSw = document.getElementById('langToggleSw');
  if (btnEn) btnEn.className = (lang === 'en' ? 'btn btn-sm btn-primary' : 'btn btn-sm btn-secondary');
  if (btnSw) btnSw.className = (lang === 'sw' ? 'btn btn-sm btn-primary' : 'btn btn-sm btn-secondary');

  const tpl = TEMPLATES_LIBRARY[state.template] || TEMPLATES_LIBRARY.wedding_ivory_bloom;
  if (!tpl) return;

  if (lang === 'sw') {
    state.badge = tpl.swBadge || 'MWALIKO WA HESHIMA';
    state.quote = tpl.swQuote || tpl.quote;
    state.footer = tpl.swFooter || tpl.footer;
    if (typeof showToast === 'function') showToast('Switched invitation wording to Kiswahili!', 'success');
  } else {
    state.badge = tpl.badge || 'WEDDING INVITATION';
    state.quote = tpl.quote || 'Together with their families, joyfully invite you to celebrate';
    state.footer = tpl.footer || 'Formal Attire • Reception to Follow';
    if (typeof showToast === 'function') showToast('Switched invitation wording to English!', 'info');
  }

  if (typeof syncStateToInputs === 'function') syncStateToInputs();
  if (typeof renderCanvas === 'function') renderCanvas();
}

// Global Exports
if (typeof window !== 'undefined') {
  window.TEMPLATES_LIBRARY = TEMPLATES_LIBRARY;
  window.TEMPLATE_CATEGORIES = TEMPLATE_CATEGORIES;
  window.generateTemplateArtworkSvg = generateTemplateArtworkSvg;
  window.saveCurrentDesignAsTemplate = saveCurrentDesignAsTemplate;
}
if (typeof module !== 'undefined' && module.exports) {
  module.exports = { TEMPLATES_LIBRARY, TEMPLATE_CATEGORIES, generateTemplateArtworkSvg };
}
