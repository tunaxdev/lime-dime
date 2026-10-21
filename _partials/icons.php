<?php
function svg_icon($path, $size = 20, $stroke = 1.7, $extra = '') {
  return '<svg width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="'.$stroke.'" stroke-linecap="round" stroke-linejoin="round" '.$extra.'>'.$path.'</svg>';
}

function icon_search($s=20){return svg_icon('<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',$s);}
function icon_bag($s=20){return svg_icon('<path d="M5 7h14l-1 13H6L5 7Z"/><path d="M9 7a3 3 0 0 1 6 0"/>',$s);}
function icon_user($s=20){return svg_icon('<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',$s);}
function icon_box($s=20){return svg_icon('<path d="M3 7l9-4 9 4-9 4-9-4Z"/><path d="M3 7v10l9 4 9-4V7"/><path d="M12 11v10"/>',$s);}
function icon_heart($s=20){return svg_icon('<path d="M20.8 6.6a5 5 0 0 0-8.8-3.2 5 5 0 0 0-8.8 3.2c0 4.4 4.4 7.6 8.8 11 4.4-3.4 8.8-6.6 8.8-11Z"/>',$s);}
function icon_star($s=14){return '<svg width="'.$s.'" height="'.$s.'" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3 6.6 7.2.7-5.4 4.9 1.6 7.1L12 17.6 5.6 21.3l1.6-7.1L1.8 9.3 9 8.6 12 2Z"/></svg>';}
function icon_home($s=20){return svg_icon('<path d="M3 10l9-7 9 7v10a2 2 0 0 1-2 2h-4v-7H9v7H5a2 2 0 0 1-2-2V10Z"/>',$s);}
function icon_chev($s=20){return svg_icon('<path d="m9 6 6 6-6 6"/>',$s);}
function icon_chev_down($s=20){return svg_icon('<path d="m6 9 6 6 6-6"/>',$s);}
function icon_package($s=20){return svg_icon('<path d="M3 7l9-4 9 4v10l-9 4-9-4V7Z"/><path d="M3 7l9 4 9-4"/><path d="M12 11v10"/>',$s);}
function icon_bike($s=20){return svg_icon('<circle cx="6" cy="17" r="3"/><circle cx="18" cy="17" r="3"/><path d="M6 17l3-8h4l3 8"/><path d="M9 9l-1-3H6"/><path d="M14 9h3l1-3"/>',$s);}
function icon_clock($s=20){return svg_icon('<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',$s);}
function icon_cash($s=20){return svg_icon('<rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="3"/><path d="M6 6v12M18 6v12"/>',$s);}
function icon_trend($s=20){return svg_icon('<path d="M3 17l6-6 4 4 8-9"/><path d="M14 6h7v7"/>',$s);}
function icon_users($s=20){return svg_icon('<circle cx="9" cy="8" r="3.5"/><path d="M2 20a7 7 0 0 1 14 0"/><circle cx="17" cy="9" r="3"/><path d="M22 19a5 5 0 0 0-7-4.5"/>',$s);}
function icon_store($s=20){return svg_icon('<path d="M3 9l1-4h16l1 4"/><path d="M3 9v11h18V9"/><path d="M3 9h18"/><path d="M9 13h6v7H9z"/>',$s);}
function icon_settings($s=20){return svg_icon('<circle cx="12" cy="12" r="3"/><path d="M19 12a7 7 0 0 0-.2-1.7l2-1.5-2-3.5-2.4.9a7 7 0 0 0-3-1.7L13 2h-2l-.5 2.5a7 7 0 0 0-3 1.7l-2.4-.9-2 3.5 2 1.5A7 7 0 0 0 5 12c0 .6.1 1.1.2 1.7l-2 1.5 2 3.5 2.4-.9a7 7 0 0 0 3 1.7L11 22h2l.5-2.5a7 7 0 0 0 3-1.7l2.4.9 2-3.5-2-1.5c.1-.6.2-1.1.2-1.7Z"/>',$s);}
function icon_check($s=20){return svg_icon('<path d="M5 12l5 5 9-11"/>',$s);}
function icon_plus($s=20){return svg_icon('<path d="M12 5v14M5 12h14"/>',$s);}
function icon_minus($s=20){return svg_icon('<path d="M5 12h14"/>',$s);}
function icon_trash($s=20){return svg_icon('<path d="M4 7h16"/><path d="M10 11v6M14 11v6"/><path d="M6 7l1 13a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-13"/><path d="M9 7V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2"/>',$s);}
function icon_filter($s=20){return svg_icon('<path d="M4 5h16l-6 8v6l-4-2v-4L4 5Z"/>',$s);}
function icon_logout($s=20){return svg_icon('<path d="M15 4h4a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-4"/><path d="M10 17l-5-5 5-5"/><path d="M5 12h12"/>',$s);}
function icon_card($s=20){return svg_icon('<rect x="2" y="6" width="20" height="13" rx="2"/><path d="M2 11h20"/>',$s);}
function icon_pin($s=20){return svg_icon('<path d="M12 22s8-7 8-13a8 8 0 0 0-16 0c0 6 8 13 8 13Z"/><circle cx="12" cy="9" r="3"/>',$s);}
function icon_map($s=20){return svg_icon('<path d="M3 6l6-3 6 3 6-3v15l-6 3-6-3-6 3V6Z"/><path d="M9 3v15"/><path d="M15 6v15"/>',$s);}
function icon_mail($s=20){return svg_icon('<rect x="2" y="4" width="20" height="16" rx="2"/><path d="M2 7l10 7 10-7"/>',$s);}

function cat_icon($cat, $size = 13) {
  $s = $size;
  switch ($cat) {
    case 'food': return '<svg width="'.$s.'" height="'.$s.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 4 0 0 1 18 0"/><path d="M3 12h18"/><path d="M5 16h14l-1 4H6l-1-4Z"/></svg>';
    case 'clothing': return '<svg width="'.$s.'" height="'.$s.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M8 4l4 3 4-3 5 4-3 3v10H6V11L3 8l5-4Z"/></svg>';
    case 'electronics': return '<svg width="'.$s.'" height="'.$s.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="12" rx="2"/><path d="M8 21h8M12 17v4"/></svg>';
    case 'education': return '<svg width="'.$s.'" height="'.$s.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M2 9l10-5 10 5-10 5L2 9Z"/><path d="M6 11v5c2 2 10 2 12 0v-5"/><path d="M22 9v5"/></svg>';
    case 'beauty': return '<svg width="'.$s.'" height="'.$s.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 3h6v4l2 3v9a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2v-9l2-3V3Z"/><path d="M7 13h10"/></svg>';
    default: return '';
  }
}

function logo_wordmark($size = 36) {
  $img_size = $size;
  $font_size = $size * 0.55;
  return '<div style="display:flex;align-items:center;gap:10px;">
    <img src="/limedime/assets/img/logo.png" width="'.$img_size.'" height="'.$img_size.'" alt="Lime&amp;Dime logo" style="object-fit:contain;">
    <div style="font-family:Fraunces,serif;font-weight:700;font-size:'.$font_size.'px;letter-spacing:-0.03em;color:var(--ink);">
      <span>Lime</span><span style="color:var(--wine);margin:0 2px;">&amp;</span><span>Dime</span>
    </div>
  </div>';
}
