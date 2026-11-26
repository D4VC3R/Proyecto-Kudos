// Contenido común de las tres maquetas: los mismos ítems, secciones y textos, para que la comparación sea justa.
// Las imágenes son ilustraciones generadas (SVG), no pósteres reales: no hay material con derechos en el repositorio.
(function () {
  const categories = {
    peliculas: { slug: 'peliculas', label: 'Películas', short: 'Película' },
    series: { slug: 'series', label: 'Series', short: 'Serie' },
    videojuegos: { slug: 'videojuegos', label: 'Videojuegos', short: 'Juego' },
    destinos: { slug: 'destinos', label: 'Destinos', short: 'Destino' },
  };

  // hue: tono base de la ilustración; motif: forma principal (sun, peaks, figure, grid, waves, city).
  const items = [
    { id: 1, name: 'Sentido y sensibilidad', cat: 'peliculas', year: 2026, genres: ['Drama', 'Romance'], avg: 6.8, votes: 12, hue: 205, motif: 'figure' },
    { id: 2, name: 'La otra madre', cat: 'peliculas', year: 2026, genres: ['Terror'], avg: 6.7, votes: 9, hue: 15, motif: 'figure' },
    { id: 3, name: 'Street Fighter', cat: 'peliculas', year: 2026, genres: ['Acción', 'Fantasía'], avg: 8.4, votes: 31, hue: 2, motif: 'sun' },
    { id: 4, name: 'Wildwood: El bosque salvaje', cat: 'peliculas', year: 2026, genres: ['Animación', 'Aventura'], avg: 6.8, votes: 7, hue: 120, motif: 'peaks' },
    { id: 5, name: 'VisionQuest', cat: 'peliculas', year: 2026, genres: ['Ciencia ficción'], avg: 6.7, votes: 5, hue: 265, motif: 'grid' },
    { id: 6, name: 'Clayface', cat: 'peliculas', year: 2026, genres: ['Terror', 'Suspense'], avg: 4.9, votes: 4, hue: 30, motif: 'figure' },
    { id: 7, name: 'Verity. La sombra de un engaño', cat: 'peliculas', year: 2026, genres: ['Misterio', 'Suspense'], avg: 6.1, votes: 6, hue: 340, motif: 'figure' },
    { id: 8, name: 'Ley y orden: Unidad de Víctimas Especiales', cat: 'series', year: 1999, genres: ['Crimen', 'Drama'], avg: 7.3, votes: 18, hue: 220, motif: 'city' },
    { id: 9, name: 'Silver Pines', cat: 'series', year: 2026, genres: ['Misterio'], avg: null, votes: 0, hue: 180, motif: 'peaks' },
    { id: 10, name: 'Onimusha: Way of the Sword', cat: 'videojuegos', year: 2026, genres: ['Rol', 'Hack and slash'], avg: null, votes: 0, hue: 355, motif: 'sun' },
    { id: 11, name: 'Castlevania: Belmont’s Curse', cat: 'videojuegos', year: 2026, genres: ['Plataformas'], avg: 7.9, votes: 3, hue: 285, motif: 'city' },
    { id: 12, name: 'Phantom Blade Zero', cat: 'videojuegos', year: 2026, genres: ['Rol', 'Acción'], avg: null, votes: 0, hue: 40, motif: 'figure' },
    { id: 13, name: 'Mortal Shell II', cat: 'videojuegos', year: 2026, genres: ['Rol', 'Aventura'], avg: 7.1, votes: 2, hue: 0, motif: 'grid' },
    { id: 14, name: 'Hela: Of Mice & Magic', cat: 'videojuegos', year: 2026, genres: ['Aventura'], avg: 8.0, votes: 4, hue: 150, motif: 'waves' },
    { id: 15, name: 'Albarracín', cat: 'destinos', year: null, genres: ['Teruel', 'Pueblo medieval'], avg: 9.1, votes: 14, hue: 25, motif: 'city', place: 'Teruel, Aragón' },
    { id: 16, name: 'Playa de las Catedrales', cat: 'destinos', year: null, genres: ['Lugo', 'Costa'], avg: 8.7, votes: 10, hue: 195, motif: 'waves', place: 'Ribadeo, Galicia' },
    { id: 17, name: 'Picos de Europa', cat: 'destinos', year: null, genres: ['Asturias', 'Montaña'], avg: 8.9, votes: 8, hue: 140, motif: 'peaks', place: 'Asturias, Cantabria y León' },
    { id: 18, name: 'Cuenca', cat: 'destinos', year: null, genres: ['Castilla-La Mancha', 'Ciudad'], avg: 8.2, votes: 6, hue: 35, motif: 'city', place: 'Cuenca, Castilla-La Mancha' },
  ];

  const byId = Object.fromEntries(items.map((it) => [it.id, it]));

  const home = {
    title: 'Descubre qué ver, jugar y visitar',
    subtitle: 'Recomendaciones, novedades y listas para encontrar tu próxima peli, serie, juego o destino.',
    forYou: [
      { because: 'Street Fighter', ids: [1, 2, 7, 5, 6, 4] },
      { because: 'Control Resonant', ids: [10, 11, 12, 13, 14] },
    ],
    news: [4, 5, 6, 7, 3, 2, 9],
    top: [15, 17, 16, 3, 18, 8, 14, 11],
    lists: [
      { title: 'Para un domingo de lluvia', by: 'kudosAdmin', ids: [1, 9, 14, 16], count: 12 },
      { title: 'Escapadas con castillo', by: 'marta_viaja', ids: [15, 18, 11, 17], count: 8 },
      { title: 'Terror que no te deja dormir', by: 'noctambulo', ids: [2, 6, 7, 13], count: 15 },
    ],
  };

  const detail = {
    id: 1,
    original: 'Sense and Sensibility',
    director: 'Georgia Oakley',
    runtime: '1 h 52 min',
    country: 'Reino Unido',
    distribution: [5, 3, 2, 1, 1], // Me encantó → No me gustó
    about: [
      'Tras la muerte de su padre, las hermanas Elinor y Marianne Dashwood se ven obligadas a dejar la finca familiar y empezar de cero en una casa modesta de Devonshire, junto a su madre y su hermana pequeña.',
      'Elinor, prudente y reservada, y Marianne, impulsiva y apasionada, viven el amor de formas opuestas. Entre bailes, cartas y rumores, las dos aprenden que la razón y el corazón necesitan entenderse.',
    ],
    aboutSource: 'Wikipedia en español, CC BY-SA 4.0',
    comments: [
      { user: 'lucia.lee', when: 'hace 2 días', score: 8, text: 'Muy bien adaptada. La fotografía de la costa es preciosa y las dos protagonistas se comen la pantalla.' },
      { user: 'pablo_cine', when: 'hace 5 días', score: 6, text: 'Bonita pero algo larga. La segunda mitad pierde ritmo.' },
    ],
    similar: [2, 7, 4, 5, 6],
  };

  const vote = {
    id: 2,
    queueLeft: 23,
    levels: [
      { key: '1', score: 2, label: 'No me gustó' },
      { key: '2', score: 4, label: 'Flojo' },
      { key: '3', score: 6, label: 'Bien' },
      { key: '4', score: 8, label: 'Muy bien' },
      { key: '5', score: 10, label: 'Me encantó' },
    ],
    synopsis: 'Una niña descubre una puerta tapiada en su casa nueva. Al otro lado vive una familia idéntica a la suya, que parece mejor… y que no quiere dejarla marchar.',
  };

  const user = { name: 'kudosAdmin', kudos: 45210, kudosLabel: '45,2 K', rank: 3, streak: 6 };

  const nav = [
    { key: 'categorias', label: 'Categorías' },
    { key: 'ranking', label: 'Ranking' },
    { key: 'wishlist', label: 'Wishlist' },
    { key: 'listas', label: 'Mis listas' },
    { key: 'admin', label: 'Administración' },
  ];

  const footer = {
    brand: 'Kudos',
    author: '© 2026 · David Cerdán Valero.',
    school: 'Desarrollo de Aplicaciones Web',
    schoolSub: 'IES Paco Mollà',
    tmdb: 'This website uses TMDB and the TMDB APIs but is not endorsed, certified, or otherwise approved by TMDB. Esta web usa TMDB y sus API, pero TMDB no la respalda, certifica ni aprueba.',
    igdb: 'Datos de videojuegos de IGDB.com.',
  };

  // Formato de nota: una cifra decimal con coma; sin votos, «sin nota».
  const score = (avg) => (avg == null ? null : avg.toFixed(1).replace('.', ','));
  const votesLabel = (n) => (n === 1 ? '1 voto' : `${n} votos`);

  // --- Ilustraciones generadas ---
  function rng(seed) {
    let s = seed * 9301 + 49297;
    return () => ((s = (s * 9301 + 49297) % 233280) / 233280);
  }

  function shapes(motif, r, w, h, hue) {
    const fg = `hsl(${hue},45%,82%)`;
    const mid = `hsl(${(hue + 20) % 360},50%,45%)`;
    const dark = `hsl(${hue},55%,10%)`;
    switch (motif) {
      case 'sun':
        return `<circle cx="${w * (0.3 + r() * 0.4)}" cy="${h * 0.38}" r="${w * 0.26}" fill="${fg}" opacity=".85"/>
          <path d="M0 ${h * 0.7} L${w * 0.35} ${h * 0.52} L${w * 0.6} ${h * 0.66} L${w} ${h * 0.5} L${w} ${h} L0 ${h}Z" fill="${dark}"/>`;
      case 'peaks':
        return `<path d="M0 ${h * 0.75} L${w * 0.25} ${h * 0.42} L${w * 0.45} ${h * 0.62} L${w * 0.7} ${h * 0.3} L${w} ${h * 0.58} L${w} ${h} L0 ${h}Z" fill="${mid}" opacity=".9"/>
          <path d="M0 ${h * 0.85} L${w * 0.35} ${h * 0.64} L${w * 0.65} ${h * 0.8} L${w} ${h * 0.7} L${w} ${h} L0 ${h}Z" fill="${dark}"/>`;
      case 'figure':
        return `<ellipse cx="${w * 0.5}" cy="${h * 0.36}" rx="${w * 0.16}" ry="${w * 0.2}" fill="${dark}" opacity=".85"/>
          <path d="M${w * 0.18} ${h} C${w * 0.22} ${h * 0.6} ${w * 0.78} ${h * 0.6} ${w * 0.82} ${h}Z" fill="${dark}" opacity=".85"/>
          <circle cx="${w * (0.2 + r() * 0.6)}" cy="${h * 0.16}" r="${w * 0.07}" fill="${fg}" opacity=".6"/>`;
      case 'grid': {
        let g = '';
        for (let i = 1; i < 9; i++) g += `<line x1="0" y1="${h * 0.55 + i * i * 4}" x2="${w}" y2="${h * 0.55 + i * i * 4}" stroke="${fg}" stroke-opacity=".35"/>`;
        for (let i = -4; i <= 4; i++) g += `<line x1="${w / 2}" y1="${h * 0.55}" x2="${w / 2 + i * w * 0.3}" y2="${h}" stroke="${fg}" stroke-opacity=".35"/>`;
        return `<circle cx="${w / 2}" cy="${h * 0.45}" r="${w * 0.22}" fill="${mid}"/>${g}`;
      }
      case 'waves':
        return [0, 1, 2, 3].map((i) => `<path d="M0 ${h * (0.55 + i * 0.1)} Q${w * 0.25} ${h * (0.5 + i * 0.1)} ${w * 0.5} ${h * (0.55 + i * 0.1)} T${w} ${h * (0.55 + i * 0.1)} L${w} ${h} L0 ${h}Z" fill="hsl(${hue},55%,${45 - i * 10}%)" opacity=".8"/>`).join('')
          + `<circle cx="${w * 0.72}" cy="${h * 0.26}" r="${w * 0.12}" fill="${fg}" opacity=".8"/>`;
      case 'city':
      default: {
        let b = '';
        let x = 0;
        while (x < w) {
          const bw = w * (0.08 + r() * 0.12);
          const bh = h * (0.18 + r() * 0.35);
          b += `<rect x="${x}" y="${h - bh}" width="${bw + 1}" height="${bh}" fill="${dark}"/>`;
          if (r() > 0.5) b += `<rect x="${x + bw * 0.3}" y="${h - bh + 6}" width="${bw * 0.25}" height="${bw * 0.25}" fill="${fg}" opacity=".5"/>`;
          x += bw;
        }
        return `<circle cx="${w * 0.3}" cy="${h * 0.3}" r="${w * 0.15}" fill="${fg}" opacity=".7"/>${b}`;
      }
    }
  }

  const xml = (s) => s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');

  // Título del póster en dos líneas como máximo, partiendo por palabras.
  function posterTitle(name, w, H, hue) {
    const words = name.split(/\s+/);
    const lines = [''];
    for (const word of words) {
      const cur = lines[lines.length - 1];
      if (cur && (cur + ' ' + word).length > 15) lines.push(word);
      else lines[lines.length - 1] = cur ? `${cur} ${word}` : word;
    }
    const shown = lines.slice(0, 2);
    if (lines.length > 2) shown[1] = `${shown[1]}…`;
    const size = shown.some((l) => l.length > 12) ? 30 : 36;
    return shown.map((l, i) => `<text x="${w / 2}" y="${H * 0.84 + i * (size + 6)}" text-anchor="middle" font-family="Georgia, serif" font-size="${size}" letter-spacing="2" fill="hsl(${hue},40%,92%)">${xml(l.toUpperCase())}</text>`).join('');
  }

  // Devuelve un SVG como URL de datos. ratio 'poster' (2:3) o 'wide' (16:9). title: pinta el título dentro (como un póster real).
  function art(item, { ratio = 'poster', title = true } = {}) {
    const w = ratio === 'wide' ? 640 : 400;
    const h = 360;
    const H = ratio === 'wide' ? h : 600;
    const r = rng(item.id);
    const hue = item.hue;
    const top = `hsl(${hue},${55 + r() * 20}%,${38 + r() * 14}%)`;
    const bottom = `hsl(${(hue + 30) % 360},60%,12%)`;
    const label = title && ratio !== 'wide' ? posterTitle(item.name, w, H, hue) : '';
    const svg = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ${w} ${H}" preserveAspectRatio="xMidYMid slice">
      <defs><linearGradient id="g" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="${top}"/><stop offset="1" stop-color="${bottom}"/></linearGradient></defs>
      <rect width="${w}" height="${H}" fill="url(#g)"/>${shapes(item.motif, r, w, H, hue)}${label}</svg>`;
    return `data:image/svg+xml;charset=utf-8,${encodeURIComponent(svg)}`;
  }

  window.KUDOS = { categories, items, byId, home, detail, vote, user, nav, footer, score, votesLabel, art };
})();
