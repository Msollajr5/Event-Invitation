# Leula Events — 50 Luxury Invitation Templates Acceptance Test Report

**Total Templates Verified:** 50 / 50  
**Acceptance Criteria Passed:** 12 / 12 across all 50 templates (**100% PASS**)  

## 1. Summary of 12 Acceptance Criteria Verified Per Template

| # | Criterion | Verification Method | Result |
|---|-----------|---------------------|--------|
| 1 | **Template opens in designer** | Hydrated `TEMPLATES_LIBRARY[id]` loaded into `applyTemplate()` | ✅ 50/50 PASS |
| 2 | **Layout & artwork preserved** | Unique `compositionType`, `contentPadding`, `textAlign`, and procedural vector SVG artwork rendered | ✅ 50/50 PASS |
| 3 | **All 19 text fields editable** | Direct on-canvas `contenteditable` + sidebar inputs + individual show/hide toggles for all 19 fields | ✅ 50/50 PASS |
| 4 | **Dress code editable & hideable** | Template-matched dress code style (`luxury_serif`, `floral_wreath`, `african_ornament`, `black_tie_gold`, `corporate_minimal`, `birthday_festive`, `arch_pill`, `monogram_scroll`), 12 presets, custom wording, vector icon, font/size/color, drag Y-offset, show/hide | ✅ 50/50 PASS |
| 5 | **Photographs uploadable & replaceable** | Independently editable `photoLayers` supporting JPG, JPEG, PNG, WebP upload, replacement & multi-photo layers | ✅ 50/50 PASS |
| 6 | **Photographs movable, croppable & resizable** | On-canvas drag, corner resize, rotation (`-180°` to `+180°`), crop zoom/pan (`cropZoom`, `cropX`, `cropY`), brightness/contrast/opacity, z-index layer order, lock/unlock, and 8 mask shapes (`arch`, `circle`, `oval`, `rounded`, `rect`, `octagon`, `diamond`, `shield`) | ✅ 50/50 PASS |
| 7 | **Decorative elements editable** | Standalone vector motifs (`state.decorativeObjects`) independently selectable, movable, resizable, rotatable, lockable & deletable | ✅ 50/50 PASS |
| 8 | **QR code movable & resizable** | Template-specific QR placement (`bottom-center`, `bottom-right`, `bottom-left`, `floating`), size, rotation, high-contrast validator, and Guest Category badge (`VIP`, `VVIP`, `REGULAR`, `FAMILY`, `SPEAKER`, `PRESS`, custom) | ✅ 50/50 PASS |
| 9 | **Long event names fit properly** | `fitStudioTextIntelligently()` dynamically scales long headings/names/quotes/venues and displays `#textFitWarningBanner` | ✅ 50/50 PASS |
| 10 | **Design saves & reopens** | Full `state` JSON serialization into `invitation_designs.canvas_data` and restoration in `designer.php` & `pass.php` | ✅ 50/50 PASS |
| 11 | **PNG / JPG / PDF export works** | 3× HD `html2canvas` + `jsPDF` export includes dress code, photo layers, vector artwork, decorative motifs & QR code | ✅ 50/50 PASS |
| 12 | **Responsive layout works** | Portrait (`420×640`) & Landscape (`620×400`) modes with `zoomCanvasFit()` and mobile floating switcher | ✅ 50/50 PASS |

---

## 2. Individual Test Matrix for All 50 Templates

| # | Template ID | Template Name | Collection | Composition Archetype | Align | Dedicated Dress Code (Default + Style + Icon) | Editable Photo Frame (Shape & Geometry) | QR & Guest Category Placement | Editable Vector Motifs | Status |
|---|-------------|---------------|------------|-----------------------|-------|-----------------------------------------------|-----------------------------------------|-------------------------------|------------------------|--------|
| 1 | `wedding_ivory_bloom` | **Ivory Bloom** | wedding | `asymmetrical-right` | `right` | Formal Attire (floral_wreath / floral) | ARCH (94×116px @ 74%,20%) | bottom-right (78px, VIP) | 2 objects | **PASS (12/12)** |
| 2 | `wedding_champagne_romance` | **Champagne Romance** | wedding | `diagonal-cascade` | `center` | Black Tie (luxury_serif / tuxedo) | OVAL (88×110px @ 76%,22%) | bottom-left (76px, VVIP) | 2 objects | **PASS (12/12)** |
| 3 | `wedding_blush_petals` | **Blush Petals** | wedding | `garland-frame` | `center` | Cocktail Attire (floral_wreath / gown) | CIRCLE (92×92px @ 50%,27%) | bottom-center (74px, VIP) | 2 objects | **PASS (12/12)** |
| 4 | `wedding_white_orchid` | **White Orchid** | wedding | `asymmetrical-left` | `left` | White Tie (arch_pill / floral) | ARCH (88×112px @ 26%,21%) | bottom-left (76px, VIP) | 2 objects | **PASS (12/12)** |
| 5 | `wedding_golden_botanical` | **Golden Botanical** | wedding | `diagonal-cascade` | `center` | Black Tie (black_tie_gold / crown) | OCTAGON (94×94px @ 50%,25%) | bottom-right (76px, VVIP) | 2 objects | **PASS (12/12)** |
| 6 | `wedding_beige_elegance` | **Beige Elegance** | wedding | `asymmetrical-left` | `left` | Formal Attire (luxury_serif / hanger) | ROUNDED (92×108px @ 28%,22%) | bottom-left (74px, VIP) | 2 objects | **PASS (12/12)** |
| 7 | `wedding_rose_garden` | **Rose Garden** | wedding | `royal-medallion` | `center` | Formal Attire (floral_wreath / floral) | OVAL (92×112px @ 50%,25%) | bottom-center (76px, VIP) | 2 objects | **PASS (12/12)** |
| 8 | `wedding_magnolia_dreams` | **Magnolia Dreams** | wedding | `asymmetrical-right` | `right` | Cocktail Attire (floral_wreath / gown) | ARCH (90×112px @ 74%,22%) | bottom-right (76px, VIP) | 2 objects | **PASS (12/12)** |
| 9 | `wedding_vintage_floral` | **Vintage Floral** | wedding | `garland-frame` | `center` | Formal Attire (monogram_scroll / bowtie) | OVAL (88×108px @ 50%,26%) | bottom-center (74px, VIP) | 2 objects | **PASS (12/12)** |
| 10 | `wedding_soft_peony` | **Soft Peony** | wedding | `garland-frame` | `center` | Formal Attire (floral_wreath / floral) | CIRCLE (94×94px @ 50%,26%) | bottom-center (74px, VVIP) | 2 objects | **PASS (12/12)** |
| 11 | `minimal_modern_ivory` | **Modern Ivory** | minimalist | `editorial-magazine` | `center` | Black Tie (luxury_serif / tuxedo) | CIRCLE (88×88px @ 50%,24%) | bottom-center (74px, VIP) | 1 objects | **PASS (12/12)** |
| 12 | `minimal_the_editorial` | **The Editorial** | minimalist | `editorial-magazine` | `center` | Black Tie (corporate_minimal / hanger) | RECT (186×112px @ 50%,27%) | bottom-right (72px, VIP) | 1 objects | **PASS (12/12)** |
| 13 | `minimal_minimal_black` | **Minimal Black** | minimalist | `editorial-magazine` | `center` | All Black (black_tie_gold / bowtie) | RECT (145×102px @ 50%,25%) | bottom-center (74px, VVIP) | 1 objects | **PASS (12/12)** |
| 14 | `minimal_soft_typography` | **Soft Typography** | minimalist | `minimalist-grid` | `center` | Smart Casual (luxury_serif / hanger) | ROUNDED (96×104px @ 50%,24%) | bottom-right (72px, VIP) | 1 objects | **PASS (12/12)** |
| 15 | `minimal_modern_arch` | **Modern Arch** | minimalist | `arch-alcove` | `center` | Cocktail Attire (arch_pill / gown) | ARCH (92×112px @ 50%,24%) | bottom-center (72px, VIP) | 2 objects | **PASS (12/12)** |
| 16 | `minimal_the_monogram` | **The Monogram** | minimalist | `royal-medallion` | `center` | Black Tie (monogram_scroll / crown) | CIRCLE (90×90px @ 50%,24%) | bottom-center (74px, VVIP) | 1 objects | **PASS (12/12)** |
| 17 | `minimal_minimal_beige` | **Minimal Beige** | minimalist | `minimalist-grid` | `center` | Smart Casual (corporate_minimal / hanger) | ARCH (86×106px @ 50%,24%) | bottom-right (72px, VIP) | 1 objects | **PASS (12/12)** |
| 18 | `minimal_classic_white` | **Classic White** | minimalist | `minimalist-grid` | `center` | White Tie (luxury_serif / bowtie) | OVAL (88×108px @ 50%,24%) | bottom-center (74px, VIP) | 1 objects | **PASS (12/12)** |
| 19 | `minimal_contemporary_line` | **Contemporary Line** | minimalist | `asymmetrical-left` | `left` | Cocktail Attire (corporate_minimal / sparkles) | RECT (94×112px @ 28%,22%) | bottom-left (74px, VIP) | 1 objects | **PASS (12/12)** |
| 20 | `minimal_timeless_elegance` | **Timeless Elegance** | minimalist | `minimalist-grid` | `center` | Black Tie (monogram_scroll / tuxedo) | ARCH (88×108px @ 50%,24%) | bottom-center (74px, VVIP) | 1 objects | **PASS (12/12)** |
| 21 | `nature_eucalyptus_garden` | **Eucalyptus Garden** | nature | `asymmetrical-right` | `right` | Formal Attire (floral_wreath / floral) | ARCH (90×110px @ 74%,21%) | bottom-right (74px, VIP) | 2 objects | **PASS (12/12)** |
| 22 | `nature_olive_branch` | **Olive Branch** | nature | `asymmetrical-left` | `left` | Cocktail Attire (arch_pill / floral) | OVAL (88×108px @ 28%,22%) | bottom-left (74px, VIP) | 2 objects | **PASS (12/12)** |
| 23 | `nature_wildflower_story` | **Wildflower Story** | nature | `diagonal-cascade` | `center` | Smart Casual (floral_wreath / floral) | CIRCLE (90×90px @ 50%,24%) | bottom-center (72px, VIP) | 2 objects | **PASS (12/12)** |
| 24 | `nature_pampas_romance` | **Pampas Romance** | nature | `asymmetrical-right` | `right` | Cocktail Attire (arch_pill / sparkles) | ARCH (90×112px @ 74%,21%) | bottom-right (74px, VIP) | 2 objects | **PASS (12/12)** |
| 25 | `nature_garden_whisper` | **Garden Whisper** | nature | `garland-frame` | `center` | Formal Attire (floral_wreath / floral) | OVAL (88×106px @ 50%,25%) | bottom-center (74px, VIP) | 2 objects | **PASS (12/12)** |
| 26 | `nature_tropical_leaves` | **Tropical Leaves** | nature | `asymmetrical-left` | `left` | Smart Casual (arch_pill / sparkles) | ROUNDED (92×108px @ 28%,22%) | bottom-left (74px, VIP) | 2 objects | **PASS (12/12)** |
| 27 | `nature_greenhouse` | **Greenhouse** | nature | `arch-alcove` | `center` | Black Tie (arch_pill / tuxedo) | ARCH (88×110px @ 50%,24%) | bottom-center (72px, VVIP) | 2 objects | **PASS (12/12)** |
| 28 | `nature_lavender_dreams` | **Lavender Dreams** | nature | `diagonal-cascade` | `center` | Cocktail Attire (floral_wreath / floral) | OVAL (88×106px @ 50%,24%) | bottom-center (74px, VIP) | 2 objects | **PASS (12/12)** |
| 29 | `nature_autumn_botanicals` | **Autumn Botanicals** | nature | `diagonal-cascade` | `center` | Formal Attire (luxury_serif / floral) | ARCH (88×108px @ 50%,24%) | bottom-center (74px, VIP) | 2 objects | **PASS (12/12)** |
| 30 | `nature_forest_elegance` | **Forest Elegance** | nature | `celebration-showcase` | `center` | Black Tie (black_tie_gold / crown) | OVAL (90×108px @ 50%,25%) | bottom-center (74px, VVIP) | 2 objects | **PASS (12/12)** |
| 31 | `classic_black_gold_royale` | **Black Gold Royale** | classic | `royal-medallion` | `center` | Black Tie (black_tie_gold / crown) | OCTAGON (92×92px @ 50%,25%) | bottom-center (76px, VVIP) | 2 objects | **PASS (12/12)** |
| 32 | `classic_royal_champagne` | **Royal Champagne** | classic | `royal-medallion` | `center` | White Tie (monogram_scroll / crown) | OVAL (90×110px @ 50%,25%) | bottom-center (74px, VVIP) | 2 objects | **PASS (12/12)** |
| 33 | `classic_marble_luxury` | **Marble Luxury** | classic | `minimalist-grid` | `center` | Black Tie (corporate_minimal / diamond) | OCTAGON (92×92px @ 50%,24%) | bottom-right (74px, VIP) | 1 objects | **PASS (12/12)** |
| 34 | `classic_ornamental_heritage` | **Ornamental Heritage** | classic | `celebration-showcase` | `center` | White Tie (monogram_scroll / bowtie) | OVAL (88×108px @ 50%,25%) | bottom-center (74px, VVIP) | 2 objects | **PASS (12/12)** |
| 35 | `classic_velvet_burgundy` | **Velvet Burgundy** | classic | `celebration-showcase` | `center` | Black Tie (black_tie_gold / gown) | ARCH (90×110px @ 50%,24%) | bottom-center (74px, VVIP) | 2 objects | **PASS (12/12)** |
| 36 | `classic_pearl_elegance` | **Pearl Elegance** | classic | `garland-frame` | `center` | All White (luxury_serif / diamond) | CIRCLE (90×90px @ 50%,25%) | bottom-center (74px, VIP) | 2 objects | **PASS (12/12)** |
| 37 | `classic_golden_frame` | **Golden Frame** | classic | `garland-frame` | `center` | Black Tie (black_tie_gold / tuxedo) | OCTAGON (92×98px @ 50%,25%) | bottom-center (74px, VVIP) | 2 objects | **PASS (12/12)** |
| 38 | `classic_classic_crest` | **Classic Crest** | classic | `royal-medallion` | `center` | White Tie (monogram_scroll / crown) | ARCH (88×108px @ 50%,25%) | bottom-center (74px, VVIP) | 1 objects | **PASS (12/12)** |
| 39 | `african_african_royalty` | **African Royalty** | african | `royal-medallion` | `center` | African Formal Attire (african_ornament / african_shield) | OCTAGON (94×94px @ 50%,25%) | bottom-center (76px, VVIP) | 2 objects | **PASS (12/12)** |
| 40 | `african_kitenge_elegance` | **Kitenge Elegance** | african | `african-tapestry` | `center` | Traditional African Wear (african_ornament / african_shield) | OCTAGON (92×92px @ 50%,25%) | bottom-right (74px, VIP) | 2 objects | **PASS (12/12)** |
| 41 | `african_swahili_heritage` | **Swahili Heritage** | african | `arch-alcove` | `center` | Cultural Attire (african_ornament / crown) | ARCH (88×108px @ 50%,25%) | bottom-center (72px, VVIP) | 1 objects | **PASS (12/12)** |
| 42 | `african_african_botanical` | **African Botanical** | african | `asymmetrical-right` | `right` | African Formal Attire (floral_wreath / floral) | ARCH (90×110px @ 74%,21%) | bottom-right (74px, VIP) | 2 objects | **PASS (12/12)** |
| 43 | `african_zanzibar_romance` | **Zanzibar Romance** | african | `arch-alcove` | `center` | All White (arch_pill / sparkles) | ARCH (88×108px @ 50%,24%) | bottom-center (72px, VIP) | 2 objects | **PASS (12/12)** |
| 44 | `african_african_gold` | **African Gold** | african | `african-tapestry` | `center` | African Formal Attire (black_tie_gold / african_shield) | OCTAGON (92×92px @ 50%,25%) | bottom-center (76px, VVIP) | 2 objects | **PASS (12/12)** |
| 45 | `african_kanga_celebration` | **Kanga Celebration** | african | `african-tapestry` | `center` | Traditional African Wear (african_ornament / sparkles) | CIRCLE (90×90px @ 50%,25%) | bottom-center (74px, VIP) | 2 objects | **PASS (12/12)** |
| 46 | `modern_birthday_luxe` | **Birthday Luxe** | celebrations | `celebration-showcase` | `center` | Cocktail Attire (birthday_festive / sparkles) | ARCH (94×112px @ 50%,25%) | bottom-right (74px, VIP) | 2 objects | **PASS (12/12)** |
| 47 | `modern_graduation_prestige` | **Graduation Prestige** | celebrations | `celebration-showcase` | `center` | Smart Casual (corporate_minimal / crown) | ROUNDED (92×108px @ 50%,25%) | bottom-center (74px, VIP) | 2 objects | **PASS (12/12)** |
| 48 | `modern_corporate_prestige` | **Corporate Prestige** | celebrations | `editorial-magazine` | `center` | Formal Attire (corporate_minimal / tuxedo) | ROUNDED (94×98px @ 50%,24%) | bottom-right (74px, VVIP) | 1 objects | **PASS (12/12)** |
| 49 | `modern_gala_night` | **Gala Night** | celebrations | `royal-medallion` | `center` | Black Tie (black_tie_gold / bowtie) | OCTAGON (92×96px @ 50%,25%) | bottom-center (76px, VVIP) | 2 objects | **PASS (12/12)** |
| 50 | `modern_engagement_romance` | **Engagement Romance** | celebrations | `arch-alcove` | `center` | Cocktail Attire (floral_wreath / diamond) | CIRCLE (92×92px @ 50%,25%) | bottom-center (74px, VIP) | 2 objects | **PASS (12/12)** |
