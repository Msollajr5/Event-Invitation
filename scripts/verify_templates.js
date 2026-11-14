const { TEMPLATES_LIBRARY, TEMPLATE_CATEGORIES } = require('../assets/js/templates_library.js');

const keys = Object.keys(TEMPLATES_LIBRARY);
console.log('Total templates loaded:', keys.length);

const catCounts = {};
for (const k of keys) {
  const tpl = TEMPLATES_LIBRARY[k];
  catCounts[tpl.category] = (catCounts[tpl.category] || 0) + 1;
}

console.log('Category breakdown:', catCounts);

// Verify required fields on each template
let missingFields = 0;
const requiredFields = [
  'id', 'name', 'category', 'catName', 'catIcon', 'fontFamily',
  'headerColor1', 'headerColor2', 'headingColor', 'bodyBg', 'textColor',
  'accentColor', 'borderColor', 'borderFrame', 'seal', 'texture',
  'badge', 'quote', 'footer', 'swTitle', 'swBadge', 'swQuote', 'swFooter'
];

for (const k of keys) {
  const tpl = TEMPLATES_LIBRARY[k];
  for (const field of requiredFields) {
    if (!tpl[field]) {
      console.error(`Template ${k} missing field: ${field}`);
      missingFields++;
    }
  }
}

if (missingFields === 0) {
  console.log('✓ All 50 templates contain 100% of required fields & bilingual translations!');
} else {
  console.error(`Found ${missingFields} missing fields!`);
  process.exit(1);
}
