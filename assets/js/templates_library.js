/**
 * LEULA EVENTS - 50 PREMIUM INVITATION TEMPLATES LIBRARY
 * Highly curated, distinctive, and fully customizable invitation card templates.
 */

const TEMPLATE_CATEGORIES = [
  { id: 'all',          name: 'All Templates',      icon: '✨', count: 50 },
  { id: 'wedding',      name: 'Wedding',            icon: '💍', count: 10 },
  { id: 'birthday',     name: 'Birthday',           icon: '🎂', count: 8 },
  { id: 'corporate',    name: 'Corporate & Business',icon: '💼', count: 6 },
  { id: 'graduation',   name: 'Graduation',         icon: '🎓', count: 5 },
  { id: 'religious',    name: 'Religious & Church', icon: '⛪', count: 5 },
  { id: 'celebration',  name: 'Celebrations',       icon: '🎉', count: 6 },
  { id: 'special',      name: 'Special Events',     icon: '👑', count: 5 },
  { id: 'african',      name: 'African & Swahili',  icon: '🌍', count: 5 }
];

const TEMPLATES_LIBRARY = {
  // ============================================================
  // A. WEDDING INVITATIONS (10 Templates)
  // ============================================================
  wedding_royal_gold: {
    id: 'wedding_royal_gold',
    name: 'Royal Gold Wedding',
    category: 'wedding',
    catName: 'Wedding',
    catIcon: '💍',
    tagline: 'Imperial gold filigree & regal serif',
    fontFamily: "'Cinzel', serif",
    headerColor1: '#1C160C',
    headerColor2: '#0D0A05',
    headingColor: '#F6D580',
    bodyBg: '#0D0A05',
    textColor: '#F3E5AB',
    accentColor: '#D4AF37',
    borderColor: '#D4AF37',
    borderFrame: 'gold_double',
    seal: 'crown',
    texture: 'stardust',
    qrDark: '#0D0A05',
    canvaQrLight: '#F6D580',
    badge: 'ROYAL WEDDING ACCREDITATION',
    quote: 'Together with their families, request the honour of your presence at the holy union',
    footer: 'Strict Black Tie & Evening Gown • Admission by Golden QR Token',
    swTitle: 'Harusi ya Kifalme',
    swBadge: 'UKARIBISHO WA KIFALME',
    swQuote: 'Pamoja na familia zao, wanayo heshima kukualika katika ndoa yao takatifu',
    swFooter: 'Mavazi Rasmi ya Kifahari • Kuingia ni kwa Tokeni ya Dhahabu'
  },
  wedding_luxury_floral: {
    id: 'wedding_luxury_floral',
    name: 'Luxury Floral Wedding',
    category: 'wedding',
    catName: 'Wedding',
    catIcon: '💍',
    tagline: 'Soft blush blossoms & romantic burgundy',
    fontFamily: "'Playfair Display', serif",
    headerColor1: '#4A1525',
    headerColor2: '#782038',
    headingColor: '#FFE4EC',
    bodyBg: '#FFF5F7',
    textColor: '#4A1525',
    accentColor: '#C24168',
    borderColor: '#F4B6C2',
    borderFrame: 'gold_single',
    seal: 'ribbon',
    texture: 'glow',
    qrDark: '#4A1525',
    canvaQrLight: '#FFFFFF',
    badge: 'CELESTIAL BLOSSOM PASS',
    quote: 'Two lives, two hearts, joined together in friendship and united forever in love',
    footer: 'Elegant Garden Cocktail Attire • Digital QR Required at Entrance',
    swTitle: 'Harusi ya Maua ya Kifahari',
    swBadge: 'MUALIKO WA MAUA',
    swQuote: 'Mioyo miwili iliyoungana kwa upendo na ahadi ya milele',
    swFooter: 'Mavazi ya Bustani ya Kifahari • Kadi hii ni ya Mtu Mmoja'
  },
  wedding_minimalist_white: {
    id: 'wedding_minimalist_white',
    name: 'Minimalist White Wedding',
    category: 'wedding',
    catName: 'Wedding',
    catIcon: '💍',
    tagline: 'Clean white background & modern monochrome',
    fontFamily: "'Montserrat', sans-serif",
    headerColor1: '#1E1E24',
    headerColor2: '#121216',
    headingColor: '#FFFFFF',
    bodyBg: '#FFFFFF',
    textColor: '#18181B',
    accentColor: '#18181B',
    borderColor: '#E4E4E7',
    borderFrame: 'none',
    seal: 'verified',
    texture: 'none',
    qrDark: '#000000',
    canvaQrLight: '#FFFFFF',
    badge: 'ADMIT WEDDING GUEST',
    quote: 'Celebrate love, laughter, and happily ever after with us on our special day',
    footer: 'Minimalist Modern Chic • Scan QR at Venue Foyer Kiosk',
    swTitle: 'Harusi ya Kisasa Nyeupe',
    swBadge: 'MGENI WA HARUSI',
    swQuote: 'Ungana nasi kusherehekea upendo na mwanzo mpya wa maisha',
    swFooter: 'Mavazi ya Kisasa • Thibitisha QR Mlangoni'
  },
  wedding_african_trad: {
    id: 'wedding_african_trad',
    name: 'African Traditional Wedding',
    category: 'wedding',
    catName: 'Wedding',
    catIcon: '💍',
    tagline: 'Terracotta, ochre patterns & rich cultural pride',
    fontFamily: "'Sora', sans-serif",
    headerColor1: '#7A2807',
    headerColor2: '#4A1503',
    headingColor: '#FAD7A0',
    bodyBg: '#FFF8F0',
    textColor: '#4A1806',
    accentColor: '#D35400',
    borderColor: '#D35400',
    borderFrame: 'deco_corners',
    seal: 'ticket',
    texture: 'dots',
    qrDark: '#4A1806',
    canvaQrLight: '#FFFFFF',
    badge: 'NUPTIAL CELEBRATION',
    quote: 'Joining two families and celebrating our rich heritage in unity and joy',
    footer: 'Traditional African Attire / Kitenge Glam • Karibuni Sana',
    swTitle: 'Harusi ya Kitamaduni',
    swBadge: 'SHEREHE YA NDIO',
    swQuote: 'Kuunganisha koo mbili na kuadhimisha mila na upendo wa dhati',
    swFooter: 'Mavazi ya Kitamaduni • Karibuni Sana Ndugu na Jamaa'
  },
  wedding_modern_luxury: {
    id: 'wedding_modern_luxury',
    name: 'Modern Luxury Wedding',
    category: 'wedding',
    catName: 'Wedding',
    catIcon: '💍',
    tagline: 'Midnight slate, cyan illumination & high contrast',
    fontFamily: "'Space Grotesk', sans-serif",
    headerColor1: '#0F172A',
    headerColor2: '#020617',
    headingColor: '#38BDF8',
    bodyBg: '#090D16',
    textColor: '#E2E8F0',
    accentColor: '#38BDF8',
    borderColor: '#38BDF8',
    borderFrame: 'glass_glow',
    seal: 'diamond',
    texture: 'glow',
    qrDark: '#090D16',
    canvaQrLight: '#38BDF8',
    badge: 'EXCLUSIVE NUPTIAL GALA',
    quote: 'An evening of love, modern elegance, and unforgettable memories',
    footer: 'Modern Black Tie • Encrypted Dynamic Pass Required',
    swTitle: 'Harusi ya Kisasa ya Kifahari',
    swBadge: 'GALA YA HARUSI',
    swQuote: 'Usiku wa kipekee wa upendo, heshima na furaha isiyo na kifani',
    swFooter: 'Mavazi Rasmi ya Usiku • Kadi hii ina QR Maalum'
  },
  wedding_romantic_rose: {
    id: 'wedding_romantic_rose',
    name: 'Romantic Rose Wedding',
    category: 'wedding',
    catName: 'Wedding',
    catIcon: '💍',
    tagline: 'Vintage rose wine & delicate script aesthetic',
    fontFamily: "'Cormorant Garamond', serif",
    headerColor1: '#581C2F',
    headerColor2: '#832B45',
    headingColor: '#FCD5CE',
    bodyBg: '#FFF9F9',
    textColor: '#441424',
    accentColor: '#B83A5D',
    borderColor: '#E8A598',
    borderFrame: 'gold_single',
    seal: 'ribbon',
    texture: 'marble',
    qrDark: '#441424',
    canvaQrLight: '#FFFFFF',
    badge: 'SWEETHEART NUPTIALS',
    quote: 'Once in awhile, right in the middle of an ordinary life, love gives us a fairytale',
    footer: 'Semi-Formal Pastel Chic • QR Checked at Grand Entry Ballroom',
    swTitle: 'Harusi ya Waridi',
    swBadge: 'NDOA YA MAHARUSI',
    swQuote: 'Upendo uliotukuka unaotuunganisha katika safari ya maisha',
    swFooter: 'Mavazi ya Rangi Tulivu • Uhakiki wa QR Mlangoni'
  },
  wedding_black_gold: {
    id: 'wedding_black_gold',
    name: 'Black and Gold Wedding',
    category: 'wedding',
    catName: 'Wedding',
    catIcon: '💍',
    tagline: 'Deep jet black with gleaming metallic gold borders',
    fontFamily: "'Cinzel', serif",
    headerColor1: '#0A0A0A',
    headerColor2: '#171717',
    headingColor: '#E5B842',
    bodyBg: '#0A0A0A',
    textColor: '#F3E5AB',
    accentColor: '#D4AF37',
    borderColor: '#D4AF37',
    borderFrame: 'gold_double',
    seal: 'crown',
    texture: 'stardust',
    qrDark: '#0A0A0A',
    canvaQrLight: '#E5B842',
    badge: 'BLACK TIE NUPTIALS',
    quote: 'Witness our vows and feast with us in an unforgettable evening of splendour',
    footer: 'Strict Formal Black & Gold Attire • Non-Transferable Digital Pass',
    swTitle: 'Harusi ya Nyeusi na Dhahabu',
    swBadge: 'MGENI WA HESHIMA',
    swQuote: 'Shuhudia viapo vyetu katika usiku mtukufu wa furaha na shukrani',
    swFooter: 'Mavazi ya Nyeusi na Dhahabu • Kuingia ni kwa QR Pekee'
  },
  wedding_botanical: {
    id: 'wedding_botanical',
    name: 'Botanical Wedding',
    category: 'wedding',
    catName: 'Wedding',
    catIcon: '💍',
    tagline: 'Eucalyptus leaves, sage greens & natural foliage',
    fontFamily: "'Cormorant Garamond', serif",
    headerColor1: '#1E3A2F',
    headerColor2: '#2D5A46',
    headingColor: '#D1E7DD',
    bodyBg: '#F9FCFA',
    textColor: '#1A3329',
    accentColor: '#3D7A5E',
    borderColor: '#74A892',
    borderFrame: 'deco_corners',
    seal: 'ribbon',
    texture: 'marble',
    qrDark: '#1A3329',
    canvaQrLight: '#FFFFFF',
    badge: 'BOTANICAL GARDEN UNION',
    quote: 'Surrounded by nature, nurtured by love, united for a lifetime of growth',
    footer: 'Garden Party Attire • Pass Valid for 1 Registered Guest',
    swTitle: 'Harusi ya Bustani Asilia',
    swBadge: 'MGENI WA BUSTANI',
    swQuote: 'Katikati ya uoto wa asili, upendo unachanua kwa baraka tele',
    swFooter: 'Mavazi ya Bustani • Mwaliko wa Mtu Mmoja'
  },
  wedding_beach: {
    id: 'wedding_beach',
    name: 'Beach Wedding',
    category: 'wedding',
    catName: 'Wedding',
    catIcon: '💍',
    tagline: 'Coastal cyan, aqua waves & coastal breeze',
    fontFamily: "'Playfair Display', serif",
    headerColor1: '#085464',
    headerColor2: '#0E7490',
    headingColor: '#E0F2FE',
    bodyBg: '#F0FDF4',
    textColor: '#0E3D48',
    accentColor: '#0891B2',
    borderColor: '#67E8F9',
    borderFrame: 'glass_glow',
    seal: 'verified',
    texture: 'glow',
    qrDark: '#0E3D48',
    canvaQrLight: '#FFFFFF',
    badge: 'COASTAL MATRIMONY',
    quote: 'As eternal as the ocean waves, two souls become one by the shore',
    footer: 'Barefoot Beach Formal / Linen Suits • Gate QR Required',
    swTitle: 'Harusi ya Ufukweni',
    swBadge: 'MUALIKO WA PWANI',
    swQuote: 'Kama mawimbi ya bahari yasiyo na kikomo, mioyo miwili inaungana',
    swFooter: 'Mavazi ya Ufukweni / Pamba Nyeupe • Karibu Baharini'
  },
  wedding_elegant_traditional: {
    id: 'wedding_elegant_traditional',
    name: 'Elegant Traditional Wedding',
    category: 'wedding',
    catName: 'Wedding',
    catIcon: '💍',
    tagline: 'Victorian filigree, royal amethyst & gold accents',
    fontFamily: "'Cinzel', serif",
    headerColor1: '#3B1C32',
    headerColor2: '#542044',
    headingColor: '#FDE047',
    bodyBg: '#FCFBF7',
    textColor: '#38122E',
    accentColor: '#A21CAF',
    borderColor: '#C084FC',
    borderFrame: 'gold_single',
    seal: 'crown',
    texture: 'dots',
    badge: 'CLASSIC NUPTIAL SOIRÉE',
    quote: 'Honor us with your gracious presence at our traditional matrimonial feast',
    footer: 'Traditional Formal Elegance • Scan at Arrival Kiosk',
    swTitle: 'Harusi ya Kitamaduni ya Kifahari',
    swBadge: 'SHEREHE KUU',
    swQuote: 'Tafadhali tuheshimishe kwa uwepo wako katika karamu yetu ya ndoa',
    swFooter: 'Mavazi ya Kitamaduni ya Heshima • Thibitisha Kadi Mlangoni'
  },

  // ============================================================
  // B. BIRTHDAY INVITATIONS (8 Templates)
  // ============================================================
  bday_luxury_celebration: {
    id: 'bday_luxury_celebration',
    name: 'Luxury Birthday Celebration',
    category: 'birthday',
    catName: 'Birthday',
    catIcon: '🎂',
    tagline: 'Dark caviar, champagne gold & milestone prestige',
    fontFamily: "'Playfair Display', serif",
    headerColor1: '#1C1917',
    headerColor2: '#292524',
    headingColor: '#FBBF24',
    bodyBg: '#121110',
    textColor: '#FDE68A',
    accentColor: '#F59E0B',
    borderColor: '#D97706',
    borderFrame: 'gold_single',
    seal: 'crown',
    texture: 'stardust',
    qrDark: '#121110',
    canvaQrLight: '#FBBF24',
    badge: 'VIP JUBILEE PASS',
    quote: 'Cheers to another fabulous year of life, laughter, and soaring achievements',
    footer: 'Cocktail Chic • Exclusive Milestone Birthday Pass',
    swTitle: 'Maadhimisho ya Kifahari ya Kuzaliwa',
    swBadge: 'MGENI WA VIP',
    swQuote: 'Kusherehekea mwaka mwingine wa mafanikio, afya tele na furaha',
    swFooter: 'Mavazi ya Kifahari • Kadi Maalum ya Mwaliko'
  },
  bday_kids_cartoon: {
    id: 'bday_kids_cartoon',
    name: 'Kids Cartoon Birthday',
    category: 'birthday',
    catName: 'Birthday',
    catIcon: '🎂',
    tagline: 'Playful sky blue, bright citrus & balloon fun',
    fontFamily: "'Montserrat', sans-serif",
    headerColor1: '#0284C7',
    headerColor2: '#06B6D4',
    headingColor: '#FEF08A',
    bodyBg: '#F0FDF4',
    textColor: '#0F172A',
    accentColor: '#F97316',
    borderColor: '#38BDF8',
    borderFrame: 'none',
    seal: 'ticket',
    texture: 'dots',
    qrDark: '#0F172A',
    canvaQrLight: '#FFFFFF',
    badge: 'KIDS ADVENTURE TICKET',
    quote: 'Join the fun for games, balloons, birthday cake, and magical surprises!',
    footer: 'Come Ready to Play & Celebrate! • Admit Bearer + Parent',
    swTitle: 'Siku ya Kuzaliwa ya Watoto',
    swBadge: 'TIKETI YA MICHEZO',
    swQuote: 'Njoo ufurahi pamoja nasi kwa michezo, keki na zawadi tele!',
    swFooter: 'Karibuni Watoto na Wazazi • Kuingia Bure kwa Tiketi Hii'
  },
  bday_princess: {
    id: 'bday_princess',
    name: 'Princess Birthday',
    category: 'birthday',
    catName: 'Birthday',
    catIcon: '🎂',
    tagline: 'Fairytale rose, tiara sparkle & script charm',
    fontFamily: "'Great Vibes', cursive",
    headerColor1: '#9D174D',
    headerColor2: '#BE185D',
    headingColor: '#FDF2F8',
    bodyBg: '#FFF1F2',
    textColor: '#831843',
    accentColor: '#EC4899',
    borderColor: '#F472B6',
    borderFrame: 'glass_glow',
    seal: 'crown',
    texture: 'glow',
    qrDark: '#831843',
    canvaQrLight: '#FFFFFF',
    badge: 'ROYAL PRINCESS PASS',
    quote: 'A magical royal ball awaits your highness in honour of our little princess',
    footer: 'Dress Like Royalty • Magical Fairy Entrance via Digital QR',
    swTitle: 'Siku ya Kuzaliwa ya Binti Mfalme',
    swBadge: 'MGENI WA KIFALME',
    swQuote: 'Karamu ya kifalme inakungoja kusherehekea siku ya binti yetu',
    swFooter: 'Vaa Kifalme • Uthibitisho wa Tiketi Mlangoni'
  },
  bday_black_gold: {
    id: 'bday_black_gold',
    name: 'Black and Gold Birthday',
    category: 'birthday',
    catName: 'Birthday',
    catIcon: '🎂',
    tagline: 'Nocturnal onyx, shimmering confetti & VIP vibes',
    fontFamily: "'Cinzel', serif",
    headerColor1: '#09090B',
    headerColor2: '#18181B',
    headingColor: '#F59E0B',
    bodyBg: '#09090B',
    textColor: '#FEF3C7',
    accentColor: '#F59E0B',
    borderColor: '#D97706',
    borderFrame: 'gold_double',
    seal: 'crown',
    texture: 'stardust',
    qrDark: '#09090B',
    canvaQrLight: '#F59E0B',
    badge: 'GOLDEN MILESTONE',
    quote: 'A golden milestone deserves a grand celebration • Join us for a night to remember',
    footer: 'Black & Gold Sophisticated Attire • Private Club Entry',
    swTitle: 'Kuzaliwa: Dhahabu na Nyeusi',
    swBadge: 'USIKU WA MAAJABU',
    swQuote: 'Miaka yenye baraka inahitaji sherehe kuu ya dhahabu na fahari',
    swFooter: 'Mavazi ya Nyeusi na Dhahabu • Kuingia ni kwa QR'
  },
  bday_neon_party: {
    id: 'bday_neon_party',
    name: 'Neon Party Birthday',
    category: 'birthday',
    catName: 'Birthday',
    catIcon: '🎂',
    tagline: 'Electric indigo, vibrant cyan glow & laser aesthetics',
    fontFamily: "'Space Grotesk', sans-serif",
    headerColor1: '#1E1B4B',
    headerColor2: '#4C1D95',
    headingColor: '#22D3EE',
    bodyBg: '#090514',
    textColor: '#E0E7FF',
    accentColor: '#E11D48',
    borderColor: '#06B6D4',
    borderFrame: 'glass_glow',
    seal: 'diamond',
    texture: 'dots',
    qrDark: '#090514',
    canvaQrLight: '#22D3EE',
    badge: 'NEON GLOW PASS',
    quote: 'Glow in the dark, dance all night, and celebrate under the lasers',
    footer: 'Neon / White Attire Encouraged • Fast Gate Pass',
    swTitle: 'Sherehe ya Rangi za Neon',
    swBadge: 'TIKETI YA NEON',
    swQuote: 'Njoo un\'gae gizani, cheza muziki na usherehekee usiku mzima',
    swFooter: 'Mavazi ya Kung\'aa / Nyeupe • QR ya Kuingia Haraka'
  },
  bday_floral: {
    id: 'bday_floral',
    name: 'Floral Birthday',
    category: 'birthday',
    catName: 'Birthday',
    catIcon: '🎂',
    tagline: 'Wild iris purple, lavender silk & watercolor charm',
    fontFamily: "'Playfair Display', serif",
    headerColor1: '#4C1D95',
    headerColor2: '#6D28D9',
    headingColor: '#EDE9FE',
    bodyBg: '#FAF5FF',
    textColor: '#3B0764',
    accentColor: '#8B5CF6',
    borderColor: '#C4B5FD',
    borderFrame: 'deco_corners',
    seal: 'ribbon',
    texture: 'marble',
    qrDark: '#3B0764',
    canvaQrLight: '#FFFFFF',
    badge: 'GARDEN BIRTHDAY BRUNCH',
    quote: 'Blooming into another year with gratitude, joy, and wonderful friends',
    footer: 'Garden Chic Attire • Table Allocation on QR Pass',
    swTitle: 'Kuzaliwa: Maua ya Kifahari',
    swBadge: 'MGENI WA BRUNCH',
    swQuote: 'Kuanza mwaka mpya kwa shukrani na bashasha ya marafiki wema',
    swFooter: 'Mavazi ya Bustani • Namba ya Meza iko kwenye QR'
  },
  bday_minimalist: {
    id: 'bday_minimalist',
    name: 'Minimalist Birthday',
    category: 'birthday',
    catName: 'Birthday',
    catIcon: '🎂',
    tagline: 'Clean typography, pure white slate & orange pop',
    fontFamily: "'Inter', sans-serif",
    headerColor1: '#0F172A',
    headerColor2: '#1E293B',
    headingColor: '#FFFFFF',
    bodyBg: '#FFFFFF',
    textColor: '#0F172A',
    accentColor: '#FF7A00',
    borderColor: '#E2E8F0',
    borderFrame: 'none',
    seal: 'verified',
    texture: 'none',
    qrDark: '#0F172A',
    canvaQrLight: '#FFFFFF',
    badge: 'BIRTHDAY ADMISSION',
    quote: 'Keep it simple, keep it joyful • Join us for drinks, bites and celebrations',
    footer: 'Smart Casual • Scan QR Code at Reception',
    swTitle: 'Siku ya Kuzaliwa ya Kisasa',
    swBadge: 'MGENI WA SIKUKUU',
    swQuote: 'Rahisi na yenye furaha tele • Karibu tufurahi pamoja',
    swFooter: 'Mavazi ya Kawaida ya Kisasa • Scan QR Mlangoni'
  },
  bday_african_celeb: {
    id: 'bday_african_celeb',
    name: 'African Celebration Birthday',
    category: 'birthday',
    catName: 'Birthday',
    catIcon: '🎂',
    tagline: 'Golden amber sunset, African warmth & festive drumming',
    fontFamily: "'Sora', sans-serif",
    headerColor1: '#92400E',
    headerColor2: '#B45309',
    headingColor: '#FEF08A',
    bodyBg: '#FEF9E7',
    textColor: '#713F12',
    accentColor: '#EA580C',
    borderColor: '#F59E0B',
    borderFrame: 'gold_single',
    seal: 'ticket',
    texture: 'dots',
    qrDark: '#713F12',
    canvaQrLight: '#FFFFFF',
    badge: 'SHEREHE YA KUZALIWA',
    quote: 'Tuungane pamoja katika kusherehekea kumbukumbu ya kuzaliwa kwa bashasha',
    footer: 'Mavazi ya Kitenge / Kiafrika • Karibuni Sana',
    swTitle: 'Sherehe ya Kuzaliwa ya Kiafrika',
    swBadge: 'MGENI RASMI',
    swQuote: 'Miaka mingi ya furaha, amani na afya tele • Karibu tusherehekee',
    swFooter: 'Mavazi ya Kiafrika • Tiketi hii ni ya Mtu Mmoja'
  },

  // ============================================================
  // C. CORPORATE AND BUSINESS EVENTS (6 Templates)
  // ============================================================
  corp_conference: {
    id: 'corp_conference',
    name: 'Corporate Conference',
    category: 'corporate',
    catName: 'Corporate',
    catIcon: '💼',
    tagline: 'Executive navy, crisp geometry & summit keynote layout',
    fontFamily: "'Inter', sans-serif",
    headerColor1: '#0369A1',
    headerColor2: '#0284C7',
    headingColor: '#FFFFFF',
    bodyBg: '#F8FAFC',
    textColor: '#0F172A',
    accentColor: '#0284C7',
    borderColor: '#BAE6FD',
    borderFrame: 'none',
    seal: 'verified',
    texture: 'dots',
    qrDark: '#0F172A',
    canvaQrLight: '#FFFFFF',
    badge: 'DELEGATE ACCREDITATION',
    quote: 'Empowering visionary leadership, sustainable growth, and global innovation',
    footer: 'Business Formal • Present Badge at Security Gate for Badge Printing',
    swTitle: 'Kongamano la Kibiashara',
    swBadge: 'MJUMBE WA KONGAMANO',
    swQuote: 'Kukuza uongozi wa maono, uwekezaji endelevu na ubunifu wa kimataifa',
    swFooter: 'Mavazi Rasmi ya Kazi • Onyesha QR Kupata Kitambulisho'
  },
  corp_networking: {
    id: 'corp_networking',
    name: 'Business Networking Event',
    category: 'corporate',
    catName: 'Corporate',
    catIcon: '💼',
    tagline: 'Deep executive teal & structured connection badge',
    fontFamily: "'Montserrat', sans-serif",
    headerColor1: '#0F766E',
    headerColor2: '#0D9488',
    headingColor: '#CCFBF1',
    bodyBg: '#F0FDFA',
    textColor: '#115E59',
    accentColor: '#0F766E',
    borderColor: '#99F6E4',
    borderFrame: 'glass_glow',
    seal: 'diamond',
    texture: 'glow',
    qrDark: '#115E59',
    canvaQrLight: '#FFFFFF',
    badge: 'NETWORKING PASS',
    quote: 'Connect with key industry leaders, institutional investors, and founders',
    footer: 'Business Casual / Smart Suits • Digital Pass for Fast Entry',
    swTitle: 'Mtandao wa Kibiashara',
    swBadge: 'TIKETI YA MTANDAO',
    swQuote: 'Kutana na wajasiriamali, wawekezaji na viongozi wa sekta mbalimbali',
    swFooter: 'Mavazi Nadhifu ya Kazi • Uthibitisho wa Haraka wa QR'
  },
  corp_anniversary: {
    id: 'corp_anniversary',
    name: 'Company Anniversary',
    category: 'corporate',
    catName: 'Corporate',
    catIcon: '💼',
    tagline: 'Platinum slate navy, milestone wreath & prestige',
    fontFamily: "'Cinzel', serif",
    headerColor1: '#1E293B',
    headerColor2: '#0F172A',
    headingColor: '#E2E8F0',
    bodyBg: '#0F172A',
    textColor: '#E2E8F0',
    accentColor: '#38BDF8',
    borderColor: '#38BDF8',
    borderFrame: 'gold_single',
    seal: 'crown',
    texture: 'stardust',
    qrDark: '#0F172A',
    canvaQrLight: '#38BDF8',
    badge: 'JUBILEE CELEBRATION',
    quote: 'Honoring years of excellence, trusted partnerships, and transformative milestones',
    footer: 'Formal Corporate Attire • VIP Entry via Secure QR Token',
    swTitle: 'Maadhimisho ya Kampuni',
    swBadge: 'SHEREHE YA JUBILEI',
    swQuote: 'Kuadhimisha miaka ya ubora, ushirikiano imara na mafanikio makubwa',
    swFooter: 'Mavazi Rasmi ya Kampuni • Kuingia ni kwa QR ya Usalama'
  },
  corp_product_launch: {
    id: 'corp_product_launch',
    name: 'Product Launch',
    category: 'corporate',
    catName: 'Corporate',
    catIcon: '💼',
    tagline: 'Ultra tech dark obsidian with luminous purple edge',
    fontFamily: "'Space Grotesk', sans-serif",
    headerColor1: '#18181B',
    headerColor2: '#27272A',
    headingColor: '#A855F7',
    bodyBg: '#09090B',
    textColor: '#E4E4E7',
    accentColor: '#A855F7',
    borderColor: '#A855F7',
    borderFrame: 'glass_glow',
    seal: 'diamond',
    texture: 'dots',
    qrDark: '#09090B',
    canvaQrLight: '#A855F7',
    badge: 'INNOVATION KEYNOTE',
    quote: 'The future begins tonight • Be the first to witness our breakthrough announcement',
    footer: 'Strictly Embargoed • Confidential Delegate Pass',
    swTitle: 'Uzinduzi wa Bidhaa Mpya',
    swBadge: 'MJUMBE WA UZINDUZI',
    swQuote: 'Ubunifu unaobadilisha soko • Shuhudia uzinduzi wa teknolojia mpya',
    swFooter: 'Taarifa Rasmi ya Siri • Mualiko Maalum wa Mjumbe'
  },
  corp_executive_dinner: {
    id: 'corp_executive_dinner',
    name: 'Executive Dinner',
    category: 'corporate',
    catName: 'Corporate',
    catIcon: '💼',
    tagline: 'Imperial indigo, gold foil & boardroom exclusivity',
    fontFamily: "'Playfair Display', serif",
    headerColor1: '#312E81',
    headerColor2: '#1E1B4B',
    headingColor: '#FEF08A',
    bodyBg: '#0F172A',
    textColor: '#E2E8F0',
    accentColor: '#EAB308',
    borderColor: '#CA8A04',
    borderFrame: 'gold_double',
    seal: 'crown',
    texture: 'stardust',
    qrDark: '#0F172A',
    canvaQrLight: '#FEF08A',
    badge: 'BOARDROOM DINING PASS',
    quote: 'An exclusive fireside dinner discussing macroeconomic trends and strategic vision',
    footer: 'Black Tie or Dark Lounge Suit • Private RSVP Allocation',
    swTitle: 'Chakula cha Jioni cha Wakurugenzi',
    swBadge: 'MGENI WA BODI',
    swQuote: 'Mjadala wa kimkakati na fursa za uwekezaji katika ngazi ya juu',
    swFooter: 'Suti Rasmi ya Kazi • Nafasi ya Mtu Mmoja Pekee'
  },
  corp_seminar: {
    id: 'corp_seminar',
    name: 'Professional Seminar',
    category: 'corporate',
    catName: 'Corporate',
    catIcon: '💼',
    tagline: 'Energetic Leula orange, radiant clarity & training focus',
    fontFamily: "'Sora', sans-serif",
    headerColor1: '#FF7A00',
    headerColor2: '#EA580C',
    headingColor: '#FFFFFF',
    bodyBg: '#FFFFFF',
    textColor: '#18181B',
    accentColor: '#FF7A00',
    borderColor: '#FF7A00',
    borderFrame: 'glass_glow',
    seal: 'verified',
    texture: 'glow',
    qrDark: '#18181B',
    canvaQrLight: '#FFFFFF',
    badge: 'SEMINAR ATTENDEE',
    quote: 'Master advanced competencies, actionable insights, and cutting-edge best practices',
    footer: 'Accredited Professional Development Hours • Fast Pass Verification',
    swTitle: 'Semina ya Kitaalamu',
    swBadge: 'MSHIRIKI WA SEMINA',
    swQuote: 'Kujenga uwezo wa kitaaluma, stadi za kisasa na maarifa ya kiutendaji',
    swFooter: 'Hati ya Ushiriki Itatolewa • Uhakiki wa QR Mlangoni'
  },

  // ============================================================
  // D. GRADUATION INVITATIONS (5 Templates)
  // ============================================================
  grad_university: {
    id: 'grad_university',
    name: 'University Graduation',
    category: 'graduation',
    catName: 'Graduation',
    catIcon: '🎓',
    tagline: 'Oxford collegiate navy, golden laurel & academic prestige',
    fontFamily: "'Cinzel', serif",
    headerColor1: '#1E3A8A',
    headerColor2: '#172554',
    headingColor: '#FDE047',
    bodyBg: '#F8FAFC',
    textColor: '#1E293B',
    accentColor: '#2563EB',
    borderColor: '#D4AF37',
    borderFrame: 'gold_single',
    seal: 'crown',
    texture: 'stardust',
    qrDark: '#1E293B',
    canvaQrLight: '#FFFFFF',
    badge: 'CONVOCATION PASS',
    quote: 'Honoring years of dedication, scholarly rigor, and the conferral of academic degrees',
    footer: 'Academic Regalia or Formal Attire • Official Guest Seat Allocation',
    swTitle: 'Mahafali ya Chuo Kikuu',
    swBadge: 'MGENI WA MAHAFALI',
    swQuote: 'Kutunuku shahada na kusherehekea bidii na mafanikio ya kielimu',
    swFooter: 'Mavazi Rasmi ya Mahafali • Nafasi ya Mgeni Aliyesajiliwa'
  },
  grad_luxury: {
    id: 'grad_luxury',
    name: 'Luxury Graduation Celebration',
    category: 'graduation',
    catName: 'Graduation',
    catIcon: '🎓',
    tagline: 'Emerald honor, golden ribbons & lavish commencement gala',
    fontFamily: "'Playfair Display', serif",
    headerColor1: '#064E3B',
    headerColor2: '#022C22',
    headingColor: '#FCD34D',
    bodyBg: '#06281E',
    textColor: '#FDE68A',
    accentColor: '#10B981',
    borderColor: '#F59E0B',
    borderFrame: 'gold_double',
    seal: 'crown',
    texture: 'stardust',
    qrDark: '#06281E',
    canvaQrLight: '#FCD34D',
    badge: 'HONORARY GRADUATE GALA',
    quote: 'The cap has been tossed, the tassel turned, and the journey to greatness has begun',
    footer: 'Formal Gala Attire • VIP Reserved Table Allocation',
    swTitle: 'Gala ya Kifahari ya Mahafali',
    swBadge: 'MGENI WA HESHIMA',
    swQuote: 'Kofia imerushwa angani, safari ya mafanikio makubwa ndiyo inaanza',
    swFooter: 'Mavazi ya Kifahari • Meza ya VIP Imetengwa'
  },
  grad_minimalist: {
    id: 'grad_minimalist',
    name: 'Minimalist Graduation',
    category: 'graduation',
    catName: 'Graduation',
    catIcon: '🎓',
    tagline: 'Monochrome class statement, clean borders & contemporary font',
    fontFamily: "'Montserrat', sans-serif",
    headerColor1: '#18181B',
    headerColor2: '#09090B',
    headingColor: '#FFFFFF',
    bodyBg: '#FFFFFF',
    textColor: '#18181B',
    accentColor: '#0284C7',
    borderColor: '#E4E4E7',
    borderFrame: 'none',
    seal: 'verified',
    texture: 'none',
    qrDark: '#18181B',
    canvaQrLight: '#FFFFFF',
    badge: 'COMMENCEMENT GUEST',
    quote: 'Class of 2026 • Celebrating academic triumphs and exciting new beginnings',
    footer: 'Present QR Code at Main Auditorium Hall Entry Gate',
    swTitle: 'Mahafali ya Kisasa',
    swBadge: 'MGENI WA MAHAFALI',
    swQuote: 'Wahitimu 2026 • Kusherehekea ushindi wa masomo na kuanza ukurasa mpya',
    swFooter: 'Onyesha QR Mlangoni mwa Ukumbi Mkuu'
  },
  grad_black_gold: {
    id: 'grad_black_gold',
    name: 'Black and Gold Graduation',
    category: 'graduation',
    catName: 'Graduation',
    catIcon: '🎓',
    tagline: 'Formal commencement party with golden diploma accents',
    fontFamily: "'Cinzel', serif",
    headerColor1: '#0F0F10',
    headerColor2: '#1C1917',
    headingColor: '#FBBF24',
    bodyBg: '#0C0A09',
    textColor: '#FEF3C7',
    accentColor: '#F59E0B',
    borderColor: '#D4AF37',
    borderFrame: 'gold_double',
    seal: 'crown',
    texture: 'stardust',
    qrDark: '#0C0A09',
    canvaQrLight: '#FBBF24',
    badge: 'DISTINGUISHED GRADUATE',
    quote: 'Celebration of academic excellence, leadership, and prestigious honours',
    footer: 'Strict Formal Black Tie • Golden Gate Verification',
    swTitle: 'Mahafali ya Dhahabu na Nyeusi',
    swBadge: 'MHITIMU WA SIFA',
    swQuote: 'Kutambua bidii ya kiwango cha juu, uongozi na mafanikio bora',
    swFooter: 'Suti Rasmi / Gauni la Heshima • Uhakiki wa Lango Kuu'
  },
  grad_academic: {
    id: 'grad_academic',
    name: 'Academic Achievement Celebration',
    category: 'graduation',
    catName: 'Graduation',
    catIcon: '🎓',
    tagline: 'Crimson dean honor roll & vintage parchment texture',
    fontFamily: "'Cormorant Garamond', serif",
    headerColor1: '#7F1D1D',
    headerColor2: '#991B1B',
    headingColor: '#FEF9C3',
    bodyBg: '#FFFBEB',
    textColor: '#450A0A',
    accentColor: '#DC2626',
    borderColor: '#FCD34D',
    borderFrame: 'gold_single',
    seal: 'verified',
    texture: 'marble',
    qrDark: '#450A0A',
    canvaQrLight: '#FFFFFF',
    badge: "DEAN'S HONORS GUEST",
    quote: 'Rewarding scholarly brilliance, relentless perseverance, and visionary potential',
    footer: 'Academic Hall Pass • Non-Transferable Invitation Badge',
    swTitle: 'Tuzo za Mafanikio ya Kitaaluma',
    swBadge: 'MGENI WA DEAN',
    swQuote: 'Kutambua vipaji adimu, bidii isiyokoma na maono ya uongozi',
    swFooter: 'Tiketi Rasmi ya Ukumbi • Hairuhusiwi Kuhamishwa'
  },

  // ============================================================
  // E. RELIGIOUS AND CHURCH EVENTS (5 Templates)
  // ============================================================
  rel_thanksgiving: {
    id: 'rel_thanksgiving',
    name: 'Church Thanksgiving',
    category: 'religious',
    catName: 'Religious',
    catIcon: '⛪',
    tagline: 'Divine gold rays, scripture blessings & praise feast',
    fontFamily: "'Playfair Display', serif",
    headerColor1: '#854D0E',
    headerColor2: '#A16207',
    headingColor: '#FEF9C3',
    bodyBg: '#FEFCE8',
    textColor: '#713F12',
    accentColor: '#CA8A04',
    borderColor: '#EAB308',
    borderFrame: 'gold_single',
    seal: 'crown',
    texture: 'glow',
    qrDark: '#713F12',
    canvaQrLight: '#FFFFFF',
    badge: 'IBADA YA SHUKRANI',
    quote: "'Give thanks to the Lord, for He is good; His love endures forever' • Psalm 107:1",
    footer: 'Worship & Thanksgiving Service • Free & Blessed Admission',
    swTitle: 'Ibada Kuu ya Shukrani',
    swBadge: 'SHUKRANI KWA BWANA',
    swQuote: "'Mshukuruni Bwana kwa kuwa ni mwema, kwa maana fadhili zake ni za milele' • Zaburi 107:1",
    swFooter: 'Ibada ya Sifa na Shukrani • Karibuni Wote Nyumbani mwa Bwana'
  },
  rel_gospel: {
    id: 'rel_gospel',
    name: 'Gospel Celebration',
    category: 'religious',
    catName: 'Religious',
    catIcon: '⛪',
    tagline: 'Sacred majesty purple, golden praise & choir celebration',
    fontFamily: "'Cinzel', serif",
    headerColor1: '#4C1D95',
    headerColor2: '#581C87',
    headingColor: '#FDE047',
    bodyBg: '#1E1035',
    textColor: '#FAF5FF',
    accentColor: '#A855F7',
    borderColor: '#EAB308',
    borderFrame: 'gold_double',
    seal: 'crown',
    texture: 'stardust',
    qrDark: '#1E1035',
    canvaQrLight: '#FDE047',
    badge: 'GOSPEL PRAISE GUEST',
    quote: 'An evening of uplifting choir symphony, powerful worship, and divine grace',
    footer: 'Sanctuary Access via QR Token • Karibu Katika Ibada',
    swTitle: 'Tamasha la Nyimbo za Injili',
    swBadge: 'MGENI WA SIFA',
    swQuote: 'Usiku wa kumsifu Mungu kwa nyimbo, zaburi na shangwe kuu',
    swFooter: 'Kuingia Kanisani kwa Tiketi ya QR • Barikiwa Sana'
  },
  rel_conference: {
    id: 'rel_conference',
    name: 'Church Conference',
    category: 'religious',
    catName: 'Religious',
    catIcon: '⛪',
    tagline: 'Kingdom blue, sacred cross motif & conference empowerment',
    fontFamily: "'Inter', sans-serif",
    headerColor1: '#1E3A8A',
    headerColor2: '#1D4ED8',
    headingColor: '#FFFFFF',
    bodyBg: '#EFF6FF',
    textColor: '#172554',
    accentColor: '#2563EB',
    borderColor: '#60A5FA',
    borderFrame: 'glass_glow',
    seal: 'verified',
    texture: 'dots',
    qrDark: '#172554',
    canvaQrLight: '#FFFFFF',
    badge: 'CONFERENCE DELEGATE',
    quote: 'Renewing minds, equipping believers, and igniting spiritual leadership across nations',
    footer: 'Official Delegate Badge • Registered Attendee Identification',
    swTitle: 'Kongamano la Kiroho',
    swBadge: 'MJUMBE WA KONGAMANO',
    swQuote: 'Kujengwa kiroho, kufundishwa neno na kuwezeshwa kwa huduma',
    swFooter: 'Kitambulisho Rasmi cha Mjumbe • Mkutano Mkuu wa Mwaka'
  },
  rel_wedding: {
    id: 'rel_wedding',
    name: 'Religious Wedding',
    category: 'religious',
    catName: 'Religious',
    catIcon: '⛪',
    tagline: 'Sacred covenant mahogany, golden rings & biblical blessings',
    fontFamily: "'Cormorant Garamond', serif",
    headerColor1: '#431407',
    headerColor2: '#7C2D12',
    headingColor: '#FEF3C7',
    bodyBg: '#FFFDF5',
    textColor: '#451A03',
    accentColor: '#B45309',
    borderColor: '#D4AF37',
    borderFrame: 'gold_single',
    seal: 'crown',
    texture: 'marble',
    qrDark: '#451A03',
    canvaQrLight: '#FFFFFF',
    badge: 'HOLY MATRIMONY',
    quote: "'What therefore God has joined together, let not man put asunder' • Mark 10:9",
    footer: 'Church Ceremony & Reception Feast • Karibuni Sana',
    swTitle: 'Ndoa Takatifu ya Kikristo',
    swBadge: 'MGENI WA NDOA',
    swQuote: "'Aliowaunganisha Mungu, mwanadamu asiwatenganishe' • Marko 10:9",
    swFooter: 'Ibada ya Ndoa na Karamu ya Harusi • Karibuni Sana'
  },
  rel_prayer: {
    id: 'rel_prayer',
    name: 'Prayer and Worship Event',
    category: 'religious',
    catName: 'Religious',
    catIcon: '⛪',
    tagline: 'Night vigil twilight, candlelight amber & reverent aura',
    fontFamily: "'Playfair Display', serif",
    headerColor1: '#0F172A',
    headerColor2: '#1E1B4B',
    headingColor: '#FDE68A',
    bodyBg: '#090D1A',
    textColor: '#F8FAFC',
    accentColor: '#F59E0B',
    borderColor: '#F59E0B',
    borderFrame: 'glass_glow',
    seal: 'crown',
    texture: 'glow',
    qrDark: '#090D1A',
    canvaQrLight: '#FDE68A',
    badge: 'NIGHT OF GLORY PASS',
    quote: 'Seeking the presence of God in prayer, intercession, and holy spiritual revival',
    footer: 'All Are Welcomed in Faith • QR Pass for Sanctuary Reserved Seating',
    swTitle: 'Mkesha Mkuu wa Maombi',
    swBadge: 'TIKETI YA MKESHA',
    swQuote: 'Kutafuta uso wa Bwana katika maombi, toba na uamsho mkuu',
    swFooter: 'Wote Mnakaribishwa • QR ya Nafasi Iliyotengwa'
  },

  // ============================================================
  // F. GENERAL CELEBRATIONS (6 Templates)
  // ============================================================
  celeb_engagement: {
    id: 'celeb_engagement',
    name: 'Engagement Party',
    category: 'celebration',
    catName: 'Celebrations',
    catIcon: '🎉',
    tagline: 'Champagne berry, diamond sparkle & joyful love toast',
    fontFamily: "'Playfair Display', serif",
    headerColor1: '#831843',
    headerColor2: '#9D174D',
    headingColor: '#FCE7F3',
    bodyBg: '#FDF2F8',
    textColor: '#500724',
    accentColor: '#DB2777',
    borderColor: '#F472B6',
    borderFrame: 'gold_single',
    seal: 'ribbon',
    texture: 'glow',
    qrDark: '#500724',
    canvaQrLight: '#FFFFFF',
    badge: 'SHE SAID YES!',
    quote: 'Pop the champagne! Celebrate the beginning of our forever together',
    footer: 'Cocktail Evening Wear • RSVP Confirmed Pass Required',
    swTitle: 'Sherehe ya Uchumba',
    swBadge: 'AMEKUBALI!',
    swQuote: 'Mwanzo wa safari yetu ya milele • Karibu tusherehekee uchumba',
    swFooter: 'Mavazi ya Kifahari • Thibitisha Ushiriki wako'
  },
  celeb_baby_shower: {
    id: 'celeb_baby_shower',
    name: 'Baby Shower',
    category: 'celebration',
    catName: 'Celebrations',
    catIcon: '🎉',
    tagline: 'Soft mint whisper, gentle clouds & loving welcome',
    fontFamily: "'Sora', sans-serif",
    headerColor1: '#0D9488',
    headerColor2: '#14B8A6',
    headingColor: '#FEF9C3',
    bodyBg: '#F0FDFA',
    textColor: '#134E4A',
    accentColor: '#0D9488',
    borderColor: '#5EEAD4',
    borderFrame: 'none',
    seal: 'ticket',
    texture: 'dots',
    qrDark: '#134E4A',
    canvaQrLight: '#FFFFFF',
    badge: 'SWEET BUNDLE OF JOY',
    quote: 'A tiny miracle is on the way! Shower the parents-to-be with love and blessings',
    footer: 'Afternoon Tea & Games • Baby Shower Admission Pass',
    swTitle: 'Sherehe ya Mtoto Mtarajiwa',
    swBadge: 'BARAKA YA MTOTO',
    swQuote: 'Kukaribisha kiumbe kipya kwa upendo, dua na baraka tele',
    swFooter: 'Michezo na Sherehe ya Mchana • Karibuni Sana'
  },
  celeb_bridal_shower: {
    id: 'celeb_bridal_shower',
    name: 'Bridal Shower',
    category: 'celebration',
    catName: 'Celebrations',
    catIcon: '🎉',
    tagline: 'Rosé sparkle, champagne flutes & stylish ladies gathering',
    fontFamily: "'Great Vibes', cursive",
    headerColor1: '#BE185D',
    headerColor2: '#E11D48',
    headingColor: '#FFF1F2',
    bodyBg: '#FFF5F7',
    textColor: '#881337',
    accentColor: '#E11D48',
    borderColor: '#FDA4AF',
    borderFrame: 'glass_glow',
    seal: 'ribbon',
    texture: 'glow',
    qrDark: '#881337',
    canvaQrLight: '#FFFFFF',
    badge: 'BRIDE-TO-BE SOIRÉE',
    quote: "Before she says 'I Do', let's celebrate our beautiful bride with bubbles and joy",
    footer: 'Glamorous Cocktail Dresses • Ladies Only Celebration',
    swTitle: 'Bridal Shower ya Maharusi',
    swBadge: 'BIBI HARUSI MTARAJIWA',
    swQuote: 'Kabla hajatamka kiapo cha ndoa, tumfanyie sherehe ya kipekee',
    swFooter: 'Mavazi ya Kifahari • Sherehe Maalum ya Akina Mama'
  },
  celeb_anniversary: {
    id: 'celeb_anniversary',
    name: 'Anniversary Celebration',
    category: 'celebration',
    catName: 'Celebrations',
    catIcon: '🎉',
    tagline: 'Silver slate elegance & timeless devotion milestone',
    fontFamily: "'Cinzel', serif",
    headerColor1: '#475569',
    headerColor2: '#334155',
    headingColor: '#F8FAFC',
    bodyBg: '#0F172A',
    textColor: '#E2E8F0',
    accentColor: '#94A3B8',
    borderColor: '#CBD5E1',
    borderFrame: 'gold_double',
    seal: 'crown',
    texture: 'stardust',
    qrDark: '#0F172A',
    canvaQrLight: '#CBD5E1',
    badge: 'MILESTONE ANNIVERSARY',
    quote: 'Celebrating a milestone of devotion, harmony, and cherished family heritage',
    footer: 'Formal Evening • Scan at Entrance Ballroom Gate',
    swTitle: 'Maadhimisho ya Miaka ya Ndoa',
    swBadge: 'KUMBUKUMBU YA NDOA',
    swQuote: 'Kuadhimisha miaka ya upendo usiotetereka na baraka za kifamilia',
    swFooter: 'Mavazi ya Heshima ya Usiku • QR ya Ukumbi Mkuu'
  },
  celeb_family_reunion: {
    id: 'celeb_family_reunion',
    name: 'Family Reunion',
    category: 'celebration',
    catName: 'Celebrations',
    catIcon: '🎉',
    tagline: 'Warm chestnut hearth, family tree roots & shared laughter',
    fontFamily: "'Sora', sans-serif",
    headerColor1: '#7C2D12',
    headerColor2: '#9A3412',
    headingColor: '#FED7AA',
    bodyBg: '#FFF7ED',
    textColor: '#431407',
    accentColor: '#EA580C',
    borderColor: '#FDBA74',
    borderFrame: 'deco_corners',
    seal: 'ticket',
    texture: 'dots',
    qrDark: '#431407',
    canvaQrLight: '#FFFFFF',
    badge: 'FAMILY ROOTS PASS',
    quote: 'Different branches of one great tree • Reunited to share stories, love and joy',
    footer: 'All Generations Welcome • Family Feast Ticket',
    swTitle: 'Mkutano Mkuu wa Familia',
    swBadge: 'UKOO NA JAMAA',
    swQuote: 'Matawi tofauti ya shina moja • Kukutana, kufurahi na kujuana zaidi',
    swFooter: 'Vizazi Vyote Vimekaribishwa • Karamu ya Ukoo'
  },
  celeb_housewarming: {
    id: 'celeb_housewarming',
    name: 'Housewarming Celebration',
    category: 'celebration',
    catName: 'Celebrations',
    catIcon: '🎉',
    tagline: 'Hearth olive green, home key & welcoming sanctuary',
    fontFamily: "'Montserrat', sans-serif",
    headerColor1: '#15803D',
    headerColor2: '#16A34A',
    headingColor: '#F0FDF4',
    bodyBg: '#F7FEE7',
    textColor: '#14532D',
    accentColor: '#16A34A',
    borderColor: '#86EFAC',
    borderFrame: 'none',
    seal: 'verified',
    texture: 'glow',
    qrDark: '#14532D',
    canvaQrLight: '#FFFFFF',
    badge: 'NEW HOME SWEET HOME',
    quote: 'Our new house becomes a home with good friends like you around our table',
    footer: 'Casual Cozy Vibes • Gate Entry Code on QR Pass',
    swTitle: 'Uzinduzi wa Nyumba Mpya',
    swBadge: 'NYUMBA MPYA',
    swQuote: 'Kuta hufanya nyumba, lakini marafiki wema huleta baraka na amani',
    swFooter: 'Mavazi ya Kawaida • Karibu Nyumbani Kwetu'
  },

  // ============================================================
  // G. SPECIAL EVENTS (5 Templates)
  // ============================================================
  special_vip_exclusive: {
    id: 'special_vip_exclusive',
    name: 'VIP Exclusive Event',
    category: 'special',
    catName: 'Special Events',
    catIcon: '👑',
    tagline: 'Ultra void black, cyan hologram & accredited patron access',
    fontFamily: "'Cinzel', serif",
    headerColor1: '#020617',
    headerColor2: '#0B0F19',
    headingColor: '#38BDF8',
    bodyBg: '#020617',
    textColor: '#F8FAFC',
    accentColor: '#38BDF8',
    borderColor: '#38BDF8',
    borderFrame: 'glass_glow',
    seal: 'diamond',
    texture: 'stardust',
    qrDark: '#020617',
    canvaQrLight: '#38BDF8',
    badge: 'CONFIDENTIAL VIP PASS',
    quote: 'Access permitted only to strictly accredited patrons and high-level dignitaries',
    footer: 'Strict Identity Check & Encrypted QR Required • Non-Transferable',
    swTitle: 'Hafla Maalum ya VIP Pekee',
    swBadge: 'MGENI WA HESHIMA KUU',
    swQuote: 'Mwaliko maalum kwa viongozi na wageni wa ngazi za juu pekee',
    swFooter: 'Ukaguzi wa Kitambulisho na QR • Hairuhusiwi Kuhamisha'
  },
  special_red_carpet: {
    id: 'special_red_carpet',
    name: 'Red Carpet Event',
    category: 'special',
    catName: 'Special Events',
    catIcon: '👑',
    tagline: 'Hollywood scarlet, spotlight glow & film premiere glamour',
    fontFamily: "'Playfair Display', serif",
    headerColor1: '#881337',
    headerColor2: '#9F1239',
    headingColor: '#FEF08A',
    bodyBg: '#130308',
    textColor: '#FFE4E6',
    accentColor: '#F43F5E',
    borderColor: '#EAB308',
    borderFrame: 'gold_double',
    seal: 'crown',
    texture: 'stardust',
    qrDark: '#130308',
    canvaQrLight: '#FEF08A',
    badge: 'RED CARPET PREMIERE',
    quote: 'Step into the spotlight • An unforgettable night of glamour, stars, and cinema',
    footer: 'Black Tie & Evening Gown • Photo Call Access with QR Pass',
    swTitle: 'Usiku wa Zulia Jekundu',
    swBadge: 'ZULIA JEKUNDU',
    swQuote: 'Kukanyaga kwenye zulia la mastaa • Usiku wa filamu na sanaa kuu',
    swFooter: 'Mavazi ya Kifahari ya Picha • Kuingia ni kwa QR Pekee'
  },
  special_gala_dinner: {
    id: 'special_gala_dinner',
    name: 'Gala Dinner',
    category: 'special',
    catName: 'Special Events',
    catIcon: '👑',
    tagline: 'Midnight navy, champagne silk & diplomatic grand banquet',
    fontFamily: "'Cinzel', serif",
    headerColor1: '#0F172A',
    headerColor2: '#1E293B',
    headingColor: '#FDE047',
    bodyBg: '#0A0F1D',
    textColor: '#F1F5F9',
    accentColor: '#EAB308',
    borderColor: '#D4AF37',
    borderFrame: 'gold_single',
    seal: 'crown',
    texture: 'glow',
    qrDark: '#0A0F1D',
    canvaQrLight: '#FDE047',
    badge: 'ANNUAL CHARITY GALA',
    quote: 'Under the patronage of distinguished ambassadors and philanthropic benefactors',
    footer: 'Strict Formal Black Tie • Table & Course Pass Required',
    swTitle: 'Karamu Kuu ya Gala',
    swBadge: 'MGENI WA GALA',
    swQuote: 'Chini ya ulezi wa mabalozi na wafadhili wa maendeleo ya jamii',
    swFooter: 'Suti Rasmi / Gauni la Jioni • Namba ya Meza iko kwenye QR'
  },
  special_award_ceremony: {
    id: 'special_award_ceremony',
    name: 'Award Ceremony',
    category: 'special',
    catName: 'Special Events',
    catIcon: '👑',
    tagline: 'Trophy golden statue, laurels & ballroom excellence honors',
    fontFamily: "'Playfair Display', serif",
    headerColor1: '#1C1917',
    headerColor2: '#292524',
    headingColor: '#F59E0B',
    bodyBg: '#0F0E0D',
    textColor: '#FEF3C7',
    accentColor: '#F59E0B',
    borderColor: '#D97706',
    borderFrame: 'gold_double',
    seal: 'crown',
    texture: 'stardust',
    qrDark: '#0F0E0D',
    canvaQrLight: '#F59E0B',
    badge: 'AWARDS OF EXCELLENCE',
    quote: 'Recognizing outstanding triumph, relentless courage, and lifetime achievements',
    footer: 'Nominee & VIP Delegate Entrance • QR Verification at Red Carpet',
    swTitle: 'Tuzo za Ubora na Mafanikio',
    swBadge: 'MTEULE WA TUZO',
    swQuote: 'Kutambua mchango adimu, uongozi uliotukuka na mafanikio ya mfano',
    swFooter: 'Wateule na Wageni Maalum • Ukaguzi wa QR Zulia Jekundu'
  },
  special_charity_fundraising: {
    id: 'special_charity_fundraising',
    name: 'Charity Fundraising Event',
    category: 'special',
    catName: 'Special Events',
    catIcon: '👑',
    tagline: 'Compassion sapphire, giving heart & gala benefit pass',
    fontFamily: "'Montserrat', sans-serif",
    headerColor1: '#1E3A8A',
    headerColor2: '#2563EB',
    headingColor: '#FFFFFF',
    bodyBg: '#F8FAFC',
    textColor: '#0F172A',
    accentColor: '#2563EB',
    borderColor: '#93C5FD',
    borderFrame: 'glass_glow',
    seal: 'verified',
    texture: 'glow',
    qrDark: '#0F172A',
    canvaQrLight: '#FFFFFF',
    badge: 'BENEVOLENT PATRON',
    quote: 'Lighting hope and transforming lives through communal giving and shared purpose',
    footer: 'Philanthropic Benefit Evening • Digital Entry Token Included',
    swTitle: 'Harambee na Chakula cha Msaada',
    swBadge: 'MFADHILI WA HESHIMA',
    swQuote: 'Kuwasha tumaini jipya na kuboresha maisha ya jamii yetu',
    swFooter: 'Harambee ya Upendo • Kadi ya Mchangiaji Maalum'
  },

  // ============================================================
  // H. AFRICAN AND TANZANIAN EVENTS (5 Templates)
  // ============================================================
  african_traditional_ceremony: {
    id: 'african_traditional_ceremony',
    name: 'Traditional African Ceremony',
    category: 'african',
    catName: 'African & Swahili',
    catIcon: '🌍',
    tagline: 'Kigoma mahogany, ochre earth tones & ethnic beadwork',
    fontFamily: "'Sora', sans-serif",
    headerColor1: '#78350F',
    headerColor2: '#92400E',
    headingColor: '#FEF08A',
    bodyBg: '#FFFBEB',
    textColor: '#451A03',
    accentColor: '#D97706',
    borderColor: '#B45309',
    borderFrame: 'deco_corners',
    seal: 'ticket',
    texture: 'dots',
    qrDark: '#451A03',
    canvaQrLight: '#FFFFFF',
    badge: 'SHEREHE YA KIMILA',
    quote: 'Utamaduni ni utu • Kuadhimisha mila na desturi za mababu zetu kwa fahari kuu',
    footer: 'Mavazi ya Kitamaduni Yanakaribishwa • Mgeni Rasmi Aliyethibitishwa',
    swTitle: 'Sherehe Kuu ya Kimila',
    swBadge: 'MGENI WA MILA',
    swQuote: 'Mila zetu, fahari yetu • Tuungane katika usiku wa ngoma na shukrani',
    swFooter: 'Mavazi ya Asili • Thibitisha QR Mlangoni'
  },
  african_kanga_inspired: {
    id: 'african_kanga_inspired',
    name: 'Kanga-Inspired Celebration',
    category: 'african',
    catName: 'African & Swahili',
    catIcon: '🌍',
    tagline: 'Tanzanian Kanga border motifs & Swahili proverb ribbon',
    fontFamily: "'Playfair Display', serif",
    headerColor1: '#9F1239',
    headerColor2: '#BE123C',
    headingColor: '#FEF08A',
    bodyBg: '#FFF1F2',
    textColor: '#4C0519',
    accentColor: '#E11D48',
    borderColor: '#FB7185',
    borderFrame: 'gold_single',
    seal: 'ribbon',
    texture: 'dots',
    qrDark: '#4C0519',
    canvaQrLight: '#FFFFFF',
    badge: 'UTAMU WA KANGA PASS',
    quote: "'Mvumilivu hula mbivu, penye nia pana njia' • Karibuni sana katika sherehe yetu",
    footer: 'Kanga / Kitenge Elegant Dress Code • Scan Pass mlangoni',
    swTitle: 'Sherehe ya Utamaduni wa Kanga',
    swBadge: 'UBANI WA KANGA',
    swQuote: "'Mcheza kwao hutuzwa, umoja ni nguvu' • Karibuni ndugu na marafiki",
    swFooter: 'Mavazi ya Kanga za Kifahari • Kuingia ni kwa QR'
  },
  african_kitenge_luxury: {
    id: 'african_kitenge_luxury',
    name: 'Kitenge Luxury Invitation',
    category: 'african',
    catName: 'African & Swahili',
    catIcon: '🌍',
    tagline: 'Royal wax violet, marigold gold & African haute couture',
    fontFamily: "'Space Grotesk', sans-serif",
    headerColor1: '#581C87',
    headerColor2: '#7E22CE',
    headingColor: '#FDE047',
    bodyBg: '#FAF5FF',
    textColor: '#3B0764',
    accentColor: '#9333EA',
    borderColor: '#EAB308',
    borderFrame: 'glass_glow',
    seal: 'diamond',
    texture: 'stardust',
    qrDark: '#3B0764',
    canvaQrLight: '#FDE047',
    badge: 'KITENGE LUXE PASS',
    quote: 'Vibrant textures, African royalty, and celebratory joy woven into one grand night',
    footer: 'Glamorous Kitenge Attire • Karibu Sana Shereheni',
    swTitle: 'Mwaliko wa Kitenge cha Kifahari',
    swBadge: 'KITENGE ROYAL',
    swQuote: 'Rangi na maumbo yenye mvuto wa asili ya Kiafrika katika usiku wa heshima',
    swFooter: 'Mavazi ya Kitenge ya Kisasa • Uhakiki wa QR Mlangoni'
  },
  african_swahili_cultural: {
    id: 'african_swahili_cultural',
    name: 'Swahili Cultural Celebration',
    category: 'african',
    catName: 'African & Swahili',
    catIcon: '🌍',
    tagline: 'Zanzibar turquoise arabesque, clove spices & coastal majesty',
    fontFamily: "'Cormorant Garamond', serif",
    headerColor1: '#115E59',
    headerColor2: '#0F766E',
    headingColor: '#FEF08A',
    bodyBg: '#F0FDFA',
    textColor: '#134E4A',
    accentColor: '#0D9488',
    borderColor: '#5EEAD4',
    borderFrame: 'deco_corners',
    seal: 'crown',
    texture: 'marble',
    qrDark: '#134E4A',
    canvaQrLight: '#FFFFFF',
    badge: 'USWAHILI NA FURAHA',
    quote: 'Heshima, ukarimu, na vionjo vya mwambao • Tunakualika katika usiku wa Kiswahili',
    footer: 'Mavazi ya Kistaha ya Kiswahili • Mwaliko wa Mtu Mmoja',
    swTitle: 'Usiku wa Utamaduni wa Waswahili',
    swBadge: 'MKARIBISHO WA PWANI',
    swQuote: 'Ukarimu wa Waswahili hauna mfano • Karibu tule na tufurahi pamoja',
    swFooter: 'Mavazi ya Kistaha ya Mwambao • Kadi ya Mgeni Maalum'
  },
  african_royal_event: {
    id: 'african_royal_event',
    name: 'African Royal Event',
    category: 'african',
    catName: 'African & Swahili',
    catIcon: '🌍',
    tagline: 'Imperial Serengeti bronze, royal shields & chieftain prestige',
    fontFamily: "'Cinzel', serif",
    headerColor1: '#451A03',
    headerColor2: '#78350F',
    headingColor: '#FDE68A',
    bodyBg: '#0C0804',
    textColor: '#FEF3C7',
    accentColor: '#F59E0B',
    borderColor: '#D4AF37',
    borderFrame: 'gold_double',
    seal: 'crown',
    texture: 'stardust',
    qrDark: '#0C0804',
    canvaQrLight: '#FDE68A',
    badge: 'KIFALME CHA AFRIKA',
    quote: 'Under royal proclamation and the blessings of the revered council of elders',
    footer: 'African Royal Dignitary Regalia • Golden QR Gate Clearance',
    swTitle: 'Hafla ya Kifalme ya Kiafrika',
    swBadge: 'MKUBWA WA MILA',
    swQuote: 'Chini ya tangazo la kifalme na baraka za wazee wa mila na desturi',
    swFooter: 'Mavazi Rasmi ya Chifu au Kifalme • Uthibitisho wa Lango la Dhahabu'
  }
};

// ============================================================
// TEMPLATE LIBRARY MARKETPLACE ENGINE
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

function toggleFavoriteTemplate(tplId, e) {
  if (e) e.stopPropagation();
  let favs = getFavoriteTemplates();
  if (favs.includes(tplId)) {
    favs = favs.filter(id => id !== tplId);
    showToast('Removed from favorites', 'info');
  } else {
    favs.push(tplId);
    showToast('Added to favorites! ⭐', 'success');
  }
  localStorage.setItem('leula_favorite_templates', JSON.stringify(favs));
  renderTemplatesLibrary(currentTplCategory, currentTplSearch);
}

function filterTemplatesByCategory(catId) {
  currentTplCategory = catId;
  document.querySelectorAll('.tpl-filter-pill').forEach(btn => {
    btn.classList.toggle('active', btn.dataset.cat === catId);
  });
  renderTemplatesLibrary(catId, currentTplSearch);
}

function onTemplateSearchInput(query) {
  currentTplSearch = query.trim().toLowerCase();
  renderTemplatesLibrary(currentTplCategory, currentTplSearch);
}

function renderTemplatesLibrary(filterCat = 'all', searchQuery = '') {
  const container = document.getElementById('templatesMarketplaceGrid');
  if (!container) return;

  const favs = getFavoriteTemplates();
  const allTemplates = Object.values(TEMPLATES_LIBRARY);

  const filtered = allTemplates.filter(tpl => {
    // Category match
    if (filterCat === 'favorites') {
      if (!favs.includes(tpl.id)) return false;
    } else if (filterCat !== 'all' && tpl.category !== filterCat) {
      return false;
    }
    // Search match
    if (searchQuery) {
      const matchName = tpl.name.toLowerCase().includes(searchQuery);
      const matchCat = tpl.catName.toLowerCase().includes(searchQuery);
      const matchTag = (tpl.tagline || '').toLowerCase().includes(searchQuery);
      const matchBadge = (tpl.badge || '').toLowerCase().includes(searchQuery);
      if (!matchName && !matchCat && !matchTag && !matchBadge) return false;
    }
    return true;
  });

  const countEl = document.getElementById('tplResultsCount');
  if (countEl) {
    countEl.textContent = `${filtered.length} of ${allTemplates.length} templates`;
  }

  if (filtered.length === 0) {
    container.innerHTML = `
      <div style="grid-column: 1 / -1; text-align: center; padding: 40px 20px; background: var(--surface-2); border-radius: var(--radius-sm); border: 1px dashed var(--border);">
        <div style="font-size: 2rem; margin-bottom: 8px;">🔍</div>
        <div style="font-weight: 700; color: var(--text-primary); margin-bottom: 4px;">No templates found</div>
        <div style="font-size: 0.78rem; color: var(--text-muted); margin-bottom: 12px;">Try adjusting your search query or category filter.</div>
        <button type="button" class="btn btn-secondary btn-sm" onclick="filterTemplatesByCategory('all'); document.getElementById('tplSearchInput').value=''; currentTplSearch='';">
          View All 50 Templates
        </button>
      </div>
    `;
    return;
  }

  container.innerHTML = filtered.map(tpl => {
    const isFav = favs.includes(tpl.id);
    const isActive = (typeof state !== 'undefined' && state && state.template === tpl.id);

    return `
      <div class="tpl-card-item ${isActive ? 'active' : ''}" onclick="customizeTemplate('${tpl.id}', event)" title="Click to customize ${tpl.name}" data-template-id="${tpl.id}">
        <!-- Preview Swatch Banner -->
        <div class="tpl-card-swatch" style="background: linear-gradient(135deg, ${tpl.headerColor1}, ${tpl.headerColor2}); color: ${tpl.headingColor};">
          <div class="tpl-card-category-tag">${tpl.catIcon} ${tpl.catName}</div>
          <button type="button" class="tpl-fav-btn ${isFav ? 'favorited' : ''}" onclick="toggleFavoriteTemplate('${tpl.id}', event)" title="${isFav ? 'Remove from favorites' : 'Add to favorites'}">
            ${isFav ? '⭐' : '☆'}
          </button>
          
          <div class="tpl-swatch-badge" style="border-color: ${tpl.headingColor}55;">
            ${tpl.badge}
          </div>
          <div class="tpl-swatch-title" style="font-family: ${tpl.fontFamily}; color: ${tpl.headingColor};">
            ${tpl.name}
          </div>
        </div>

        <!-- Meta Footer & 4 Action Buttons -->
        <div class="tpl-card-footer">
          <div class="tpl-card-info">
            <div class="tpl-card-name">${tpl.name}</div>
            <div class="tpl-card-tagline">${tpl.tagline || ''}</div>
          </div>
          <div class="tpl-card-actions">
            <button type="button" class="btn btn-sm btn-primary tpl-use-btn" onclick="customizeTemplate('${tpl.id}', event)" title="Load into editing canvas">
              ✨ Customize
            </button>
            <button type="button" class="btn btn-sm btn-secondary tpl-preview-btn" onclick="previewTemplateModal('${tpl.id}', event)" title="Preview Full Invitation">
              👁️
            </button>
            <button type="button" class="btn btn-sm btn-secondary tpl-dup-btn" onclick="duplicateTemplate('${tpl.id}', event)" title="Duplicate as Custom Template">
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

  // Check for unsaved changes before replacing current design
  if (window.isDesignDirty && typeof showConfirmDialog === 'function') {
    showConfirmDialog({
      title: 'Load New Template?',
      subtitle: 'Replace current unsaved canvas design',
      message: `Load and customize "${tpl.name}"? Any unsaved changes on the current canvas will be replaced with this template.`,
      confirmText: 'Load Template',
      cancelText: 'Keep Current',
      type: 'warning'
    }).then(function(confirmed) {
      if (confirmed) {
        executeCustomizeTemplate(tplId);
      }
    });
  } else {
    executeCustomizeTemplate(tplId);
  }
}

function executeCustomizeTemplate(tplId) {
  const tpl = TEMPLATES_LIBRARY[tplId];
  if (!tpl) return;

  if (typeof applyTemplate === 'function') {
    applyTemplate(tplId);
  }

  // Minimize template gallery and switch to editing controls
  if (typeof switchStudioTab === 'function') {
    switchStudioTab('tabCoverLayout');
  }

  // Update Active Template Banner
  const banner = document.getElementById('activeTemplateBanner');
  const nameEl = document.getElementById('activeTemplateName');
  if (banner) banner.style.display = 'flex';
  if (nameEl) nameEl.textContent = tpl.name;

  if (typeof isDesignDirty !== 'undefined') {
    window.isDesignDirty = false;
  }

  if (typeof showToast === 'function') {
    showToast(`Loaded "${tpl.name}" into live canvas editor!`, 'success');
  }
}

// Duplicate Template Functionality
function duplicateTemplate(tplId, e) {
  if (e) e.stopPropagation();
  const source = TEMPLATES_LIBRARY[tplId];
  if (!source) return;

  const copyId = `${tplId}_copy_${Date.now()}`;
  const copyName = `${source.name} (Custom Copy)`;
  const copyTpl = Object.assign({}, source, {
    id: copyId,
    name: copyName,
    tagline: `Custom variant of ${source.name}`
  });

  TEMPLATES_LIBRARY[copyId] = copyTpl;

  // Persist to user custom templates in localStorage
  try {
    const customList = JSON.parse(localStorage.getItem('leula_custom_templates') || '[]');
    customList.unshift(copyTpl);
    localStorage.setItem('leula_custom_templates', JSON.stringify(customList.slice(0, 30)));
  } catch (err) {}

  if (typeof renderTemplatesLibrary === 'function') {
    renderTemplatesLibrary('all', '');
  }

  if (typeof showToast === 'function') {
    showToast(`Duplicated "${source.name}"! Opening in editor...`, 'success');
  }

  executeCustomizeTemplate(copyId);
}

// Full Modal Preview of Template
function previewTemplateModal(tplId, e) {
  if (e) e.stopPropagation();
  const tpl = TEMPLATES_LIBRARY[tplId];
  if (!tpl) return;

  const modal = document.getElementById('templatePreviewModal');
  const body = document.getElementById('modalPreviewContent');
  if (!modal || !body) return;

  const isDark = (function(hex) {
    if (!hex || hex[0] !== '#') return false;
    const rgb = parseInt(hex.substring(1), 16);
    const r = (rgb >> 16) & 0xff;
    const g = (rgb >> 8) & 0xff;
    const b = (rgb >> 0) & 0xff;
    return (0.2126 * r + 0.7152 * g + 0.0722 * b) < 90;
  })(tpl.bodyBg);

  body.innerHTML = `
    <div style="background: ${tpl.bodyBg}; color: ${tpl.textColor}; border-radius: 20px; overflow: hidden; box-shadow: 0 20px 50px rgba(0,0,0,0.25); border: 2px solid ${tpl.borderColor || '#E4E4E7'}; max-width: 360px; margin: 0 auto; text-align: center;">
      <!-- Header -->
      <div style="background: linear-gradient(135deg, ${tpl.headerColor1}, ${tpl.headerColor2}); color: ${tpl.headingColor}; padding: 24px 18px;">
        <div style="display:inline-block; font-size: 0.68rem; font-weight: 800; text-transform: uppercase; padding: 3px 10px; border-radius: 99px; background: rgba(255,255,255,0.2); margin-bottom: 8px; border: 1px solid ${tpl.headingColor}55;">
          ${tpl.badge}
        </div>
        <div style="font-family: ${tpl.fontFamily}; font-size: 1.25rem; font-weight: 800; line-height: 1.25; margin-bottom: 4px; color: ${tpl.headingColor};">
          ${tpl.name}
        </div>
        <div style="font-size: 0.72rem; opacity: 0.9;">
          Saturday, Nov 21, 2026 • 18:00 EAT
        </div>
      </div>
      <!-- Body -->
      <div style="padding: 18px 18px 22px;">
        <div style="font-size: 0.72rem; font-style: italic; opacity: 0.8; margin-bottom: 8px;">
          "${tpl.quote}"
        </div>
        <div style="font-size: 0.65rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; opacity: 0.65;">
          ADMIT ATTENDEE BEARER
        </div>
        <div style="font-family: ${tpl.fontFamily}; font-size: 1.15rem; font-weight: 800; margin: 4px 0 10px;">
          Dr. Reginald Massawe
        </div>
        <!-- Mock QR Box -->
        <div style="width: 110px; height: 110px; margin: 0 auto 10px; background: ${tpl.canvaQrLight || '#FFFFFF'}; border-radius: 12px; padding: 8px; box-shadow: 0 4px 16px rgba(0,0,0,0.12); display: flex; align-items: center; justify-content: center;">
          <div id="modalMockQr_${tpl.id}"></div>
        </div>
        <div style="font-family: monospace; font-size: 0.72rem; opacity: 0.85; margin-bottom: 10px;">
          INV-VIP-8821 • TOKEN-REG-01
        </div>
        <div style="background: ${isDark ? 'rgba(255,255,255,0.08)' : '#F9FAFB'}; padding: 8px; border-radius: 8px; font-size: 0.72rem; margin-bottom: 10px;">
          <div style="font-weight: 700;">Serena Grand Ballroom</div>
          <div style="font-size: 0.68rem; color: ${tpl.accentColor}; font-weight: 700;">Table 04 • VIP North Gate 1</div>
        </div>
        <div style="font-size: 0.65rem; opacity: 0.6; line-height: 1.4;">
          ${tpl.footer}
        </div>
      </div>
    </div>
    
    <div style="margin-top: 16px; display: flex; gap: 8px; justify-content: center; flex-wrap: wrap;">
      <button type="button" class="btn btn-primary btn-sm" onclick="customizeTemplate('${tpl.id}'); closeTemplatePreviewModal();" style="padding: 8px 18px; font-weight: 700;">
        ✨ Customize Design
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
        width: 94,
        height: 94,
        colorDark: tpl.qrDark || '#09090B',
        colorLight: tpl.canvaQrLight || '#FFFFFF',
        correctLevel: QRCode.CorrectLevel.M
      });
    }
  }, 50);
}

function closeTemplatePreviewModal() {
  const modal = document.getElementById('templatePreviewModal');
  if (modal) modal.style.display = 'none';
}

// Language Switcher Engine
function setDesignerLanguage(lang) {
  currentLanguage = lang;
  const btnEn = document.getElementById('langToggleEn');
  const btnSw = document.getElementById('langToggleSw');
  if (btnEn) btnEn.className = (lang === 'en' ? 'btn btn-sm btn-primary' : 'btn btn-sm btn-secondary');
  if (btnSw) btnSw.className = (lang === 'sw' ? 'btn btn-sm btn-primary' : 'btn btn-sm btn-secondary');

  const tpl = TEMPLATES_LIBRARY[state.template] || TEMPLATES_LIBRARY.wedding_royal_gold;
  if (!tpl) return;

  if (lang === 'sw') {
    state.badge = tpl.swBadge || 'MGENI WA HESHIMA';
    state.quote = tpl.swQuote || tpl.quote;
    state.footer = tpl.swFooter || tpl.footer;
    showToast('Switched to Kiswahili language template!', 'success');
  } else {
    state.badge = tpl.badge || 'VIP DELEGATE PASS';
    state.quote = tpl.quote || 'Honored to request the pleasure of your company';
    state.footer = tpl.footer || 'Black Tie Required • Non-Transferable Digital QR Ticket';
    showToast('Switched to English language template!', 'info');
  }

  syncStateToInputs();
  renderCanvas();
}

// Global Exports
if (typeof window !== 'undefined') {
  window.TEMPLATES_LIBRARY = TEMPLATES_LIBRARY;
  window.TEMPLATE_CATEGORIES = TEMPLATE_CATEGORIES;
}
if (typeof module !== 'undefined' && module.exports) {
  module.exports = { TEMPLATES_LIBRARY, TEMPLATE_CATEGORIES };
}
