<?php
function shade_hex($hex, $mix) {
  $hex = ltrim($hex, '#');
  if (strlen($hex) === 3) $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
  $r = hexdec(substr($hex,0,2));
  $g = hexdec(substr($hex,2,2));
  $b = hexdec(substr($hex,4,2));
  $r = round($r + (245 - $r) * $mix);
  $g = round($g + (235 - $g) * $mix);
  $b = round($b + (210 - $b) * $mix);
  return "rgb($r,$g,$b)";
}

function product_illu($kind, $palette) {
  $a = $palette[0];
  $b = $palette[1];
  $bg = shade_hex($a, 0.85);
  $bShade = shade_hex($b, 0.95);

  $dots = '<g opacity=".4">
    <circle cx="40" cy="30" r="1.2" fill="'.$a.'" opacity=".25"/>
    <circle cx="160" cy="50" r="1.2" fill="'.$a.'" opacity=".25"/>
    <circle cx="170" cy="170" r="1.2" fill="'.$a.'" opacity=".25"/>
    <circle cx="30" cy="160" r="1.2" fill="'.$a.'" opacity=".25"/>
  </g>';

  $inner = '';
  switch ($kind) {
    case 'jar':
      $inner = '<rect x="68" y="50" width="64" height="14" rx="3" fill="'.$b.'"/>
      <path d="M70 64 L 130 64 L 134 160 C 134 168, 128 172, 120 172 L 80 172 C 72 172, 66 168, 66 160 L 70 64 Z" fill="'.$a.'" stroke="'.$b.'" stroke-width="3"/>
      <rect x="78" y="100" width="44" height="46" rx="3" fill="'.$bShade.'" opacity=".85"/>
      <text x="100" y="128" text-anchor="middle" fill="'.$a.'" font-family="Fraunces,serif" font-weight="700" font-size="13">HARVEST</text>
      <text x="100" y="142" text-anchor="middle" fill="'.$a.'" font-size="9" font-family="Manrope,sans-serif" letter-spacing="2">PRESERVES</text>';
      break;
    case 'apron':
      $inner = '<path d="M80 50 Q 100 56 120 50 L 130 70 L 145 76 L 152 160 Q 100 175 48 160 L 55 76 L 70 70 Z" fill="'.$a.'" stroke="'.$b.'" stroke-width="3" stroke-linejoin="round"/>
      <path d="M80 50 Q 100 66 120 50" fill="none" stroke="'.$b.'" stroke-width="2.5"/>
      <line x1="100" y1="56" x2="100" y2="160" stroke="'.$b.'" stroke-width="1.2" opacity=".5"/>
      <rect x="86" y="100" width="28" height="22" rx="3" fill="none" stroke="'.$b.'" stroke-width="2"/>';
      break;
    case 'lamp':
      $inner = '<path d="M70 50 L 130 50 L 140 90 L 60 90 Z" fill="'.$a.'" stroke="'.$b.'" stroke-width="3" stroke-linejoin="round"/>
      <line x1="100" y1="90" x2="100" y2="150" stroke="'.$b.'" stroke-width="3.5" stroke-linecap="round"/>
      <ellipse cx="100" cy="160" rx="36" ry="6" fill="'.$b.'"/>
      <path d="M85 100 L 115 100" stroke="'.$b.'" stroke-width="1.5" opacity=".5"/>
      <circle cx="100" cy="70" r="6" fill="'.shade_hex($b, 1.2).'"/>';
      break;
    case 'book':
      $inner = '<rect x="50" y="40" width="100" height="130" rx="4" fill="'.$a.'" stroke="'.$b.'" stroke-width="3"/>
      <rect x="62" y="56" width="76" height="2" fill="'.$b.'" opacity=".5"/>
      <rect x="62" y="64" width="50" height="2" fill="'.$b.'" opacity=".5"/>
      <rect x="62" y="100" width="76" height="20" fill="'.$bShade.'"/>
      <text x="100" y="114" text-anchor="middle" fill="'.$a.'" font-family="Fraunces,serif" font-weight="700" font-size="11">VOL. III</text>
      <rect x="62" y="140" width="76" height="2" fill="'.$b.'" opacity=".5"/>
      <rect x="62" y="148" width="40" height="2" fill="'.$b.'" opacity=".5"/>';
      break;
    case 'bottle':
      $inner = '<rect x="86" y="40" width="28" height="22" fill="'.$b.'"/>
      <path d="M76 62 L 124 62 L 130 90 L 130 165 C 130 170, 125 172, 120 172 L 80 172 C 75 172, 70 170, 70 165 L 70 90 Z" fill="'.$a.'" stroke="'.$b.'" stroke-width="3"/>
      <rect x="78" y="110" width="44" height="40" rx="2" fill="'.$bShade.'"/>
      <text x="100" y="128" text-anchor="middle" fill="'.$a.'" font-family="Fraunces,serif" font-weight="700" font-style="italic" font-size="11">rosehip</text>
      <text x="100" y="143" text-anchor="middle" fill="'.$a.'" font-family="Manrope,sans-serif" font-size="7" letter-spacing="1.5">SERUM 30ML</text>';
      break;
    case 'beanie':
      $inner = '<path d="M50 130 Q 50 60 100 60 Q 150 60 150 130 Z" fill="'.$a.'" stroke="'.$b.'" stroke-width="3"/>
      <rect x="48" y="128" width="104" height="14" rx="4" fill="'.$bShade.'" stroke="'.$b.'" stroke-width="2"/>
      <path d="M70 70 Q 80 110 75 130 M 100 62 Q 100 110 100 130 M 130 70 Q 120 110 125 130" stroke="'.$b.'" stroke-width="1.5" fill="none" opacity=".4"/>
      <circle cx="100" cy="56" r="9" fill="'.$b.'"/>';
      break;
    case 'headphones':
      $inner = '<path d="M40 110 Q 40 50 100 50 Q 160 50 160 110" fill="none" stroke="'.$b.'" stroke-width="6" stroke-linecap="round"/>
      <rect x="30" y="100" width="32" height="50" rx="10" fill="'.$a.'" stroke="'.$b.'" stroke-width="3"/>
      <rect x="138" y="100" width="32" height="50" rx="10" fill="'.$a.'" stroke="'.$b.'" stroke-width="3"/>
      <circle cx="46" cy="125" r="8" fill="'.$b.'" opacity=".6"/>
      <circle cx="154" cy="125" r="8" fill="'.$b.'" opacity=".6"/>';
      break;
    case 'tube':
      $inner = '<rect x="85" y="42" width="30" height="8" rx="2" fill="'.$b.'"/>
      <path d="M70 50 L 130 50 L 130 165 C 130 170, 125 172, 120 172 L 80 172 C 75 172, 70 170, 70 165 Z" fill="'.$a.'" stroke="'.$b.'" stroke-width="3"/>
      <rect x="78" y="80" width="44" height="60" rx="3" fill="'.$bShade.'"/>
      <text x="100" y="106" text-anchor="middle" fill="'.$a.'" font-family="Fraunces,serif" font-weight="700" font-size="9">CITRUS</text>
      <text x="100" y="120" text-anchor="middle" fill="'.$a.'" font-family="Fraunces,serif" font-style="italic" font-size="14">hand</text>
      <text x="100" y="132" text-anchor="middle" fill="'.$a.'" font-family="Manrope,sans-serif" font-size="7" letter-spacing="1.5">CREAM</text>';
      break;
    case 'mug':
      $inner = '<path d="M60 70 L 140 70 L 132 160 C 132 168, 126 172, 118 172 L 82 172 C 74 172, 68 168, 68 160 Z" fill="'.$a.'" stroke="'.$b.'" stroke-width="3" stroke-linejoin="round"/>
      <path d="M140 95 Q 168 95 168 120 Q 168 145 140 145" fill="none" stroke="'.$b.'" stroke-width="3.5" stroke-linecap="round"/>
      <ellipse cx="100" cy="70" rx="40" ry="8" fill="'.$bShade.'"/>
      <ellipse cx="100" cy="68" rx="32" ry="4" fill="'.shade_hex($b, 0.85).'" opacity=".5"/>';
      break;
    default:
      $inner = '<circle cx="100" cy="100" r="50" fill="'.$a.'" stroke="'.$b.'" stroke-width="3"/>';
  }

  return '<svg viewBox="0 0 200 200" preserveAspectRatio="xMidYMid slice" xmlns="http://www.w3.org/2000/svg">
    <rect width="200" height="200" fill="'.$bg.'"/>
    '.$dots.'
    '.$inner.'
  </svg>';
}

function seller_cover($accent, $pattern, $second = '#E1DD8F') {
  switch ($pattern) {
    case 'wave':
      return '<svg viewBox="0 0 280 110" preserveAspectRatio="xMidYMid slice" xmlns="http://www.w3.org/2000/svg">
        <rect width="280" height="110" fill="'.$accent.'"/>
        <path d="M0 70 Q 70 40 140 70 T 280 70 L 280 110 L 0 110 Z" fill="'.$second.'" opacity=".6"/>
        <path d="M0 85 Q 70 60 140 85 T 280 85 L 280 110 L 0 110 Z" fill="'.$second.'" opacity=".4"/>
      </svg>';
    case 'grid':
      $lines = '';
      for ($i=0;$i<8;$i++) $lines .= '<line x1="'.($i*40).'" y1="0" x2="'.($i*40).'" y2="110" />';
      for ($i=0;$i<4;$i++) $lines .= '<line x1="0" y1="'.($i*30).'" x2="280" y2="'.($i*30).'" />';
      return '<svg viewBox="0 0 280 110" preserveAspectRatio="xMidYMid slice" xmlns="http://www.w3.org/2000/svg">
        <rect width="280" height="110" fill="'.$accent.'"/>
        <g stroke="'.$second.'" stroke-width="1.5" opacity=".35" fill="none">'.$lines.'</g>
      </svg>';
    case 'rays':
      return '<svg viewBox="0 0 280 110" preserveAspectRatio="xMidYMid slice" xmlns="http://www.w3.org/2000/svg">
        <rect width="280" height="110" fill="'.$accent.'"/>
        <g fill="'.$second.'" opacity=".5">
          <circle cx="240" cy="-10" r="80"/>
          <circle cx="240" cy="-10" r="56" fill="'.$accent.'"/>
          <circle cx="240" cy="-10" r="32" fill="'.$second.'"/>
        </g>
      </svg>';
    default:
      $dots = '';
      for ($i=0;$i<6;$i++) for ($j=0;$j<3;$j++) $dots .= '<circle cx="'.(20+$i*46).'" cy="'.(18+$j*36).'" r="5"/>';
      return '<svg viewBox="0 0 280 110" preserveAspectRatio="xMidYMid slice" xmlns="http://www.w3.org/2000/svg">
        <rect width="280" height="110" fill="'.$accent.'"/>
        <g fill="'.$second.'" opacity=".4">'.$dots.'</g>
      </svg>';
  }
}

function product_thumb($p) {
  if (!empty($p['product_image'])) {
    return '<img src="/limedime/assets/img/products/'.htmlspecialchars($p['product_image']).'" alt="" style="width:100%;height:100%;object-fit:cover;display:block;">';
  }
  return product_illu($p['illu'], $p['palette']);
}
