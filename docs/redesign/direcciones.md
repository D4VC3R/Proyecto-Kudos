# Rediseño visual: direcciones de diseño

Tres direcciones distintas para Kudos, más la que se recomienda y por qué. Parten de la identidad descrita en [auditoria.md](auditoria.md) §5 y de las decisiones del humano en [plan.md](plan.md) §1:

- El modo oscuro entra en el rediseño.
- Los kudos y el ranking siguen destacados.
- El nombre visible pasa a ser "Kudos".

Normas comunes a las tres:

- **Fuentes autoalojadas** con `@fontsource`, con licencia OFL y unos 30–60 KB por familia variable. Nada de Google Fonts, que la política de privacidad excluye ([../legal/cumplimiento.md](../legal/cumplimiento.md)).
- **Dos modos, claro y oscuro**, con los mismos tokens semánticos. Por defecto, el modo sigue `prefers-color-scheme`, y un conmutador en la cabecera guarda la elección.
- **Contraste medido.** Todos los pares de texto que se proponen cumplen AA: 4,5:1 el texto y 3:1 los bordes de campo y los iconos. En las tablas, "lienzo" es el fondo de la página y "superficie" el de las tarjetas.
- **Los kudos tienen un color propio** (un dorado) que no se usa para nada más, de modo que el contador, las recompensas y el podio se reconozcan de un vistazo.

---

## A. "Cuaderno" (recomendada)

**Concepto:** un diario editorial sobre papel cálido. Los pósteres y las fotos ponen el color; la interfaz se aparta y deja leer. Los kudos son el único brillo dorado de la página, como un sello en el margen del cuaderno.

**Tipografía:**
- Titulares en **Fraunces**: serif variable con carácter y eje de tamaño óptico, que en grande parece una revista y en pequeño se mantiene sobria.
- Texto en **Inter**, que ya está instalada.
- Cifras tabulares para notas y kudos.

**Paleta:**

| Token | Claro | Oscuro ("cuaderno de noche") |
|---|---|---|
| Lienzo | `#FAF7F2` (papel) | `#16130F` |
| Superficie | `#FFFFFF` | `#211D19` |
| Tinta | `#1C1917` (16,4:1) | `#F3EEE7` (16,0:1) |
| Texto secundario | `#57534E` (7,1:1) | `#ACA39A` (7,5:1) |
| Acento (teja) | `#B93815` (5,4:1); blanco encima, 5,8:1 | `#EE8A66` (7,5:1); tinta oscura encima, 7,1:1 |
| Positivo | `#166534` (6,7:1) | `#6CC58A` (8,8:1) |
| Borde decorativo | `#E7E1D8` | `#332D27` |
| Borde de campo | `#8A8178` (3,8:1) | `#7A7067` (3,5:1) |
| Kudos (dorado) | `#F2B33D`; con tinta encima, 9,4:1 | `#F2B33D` (10,0:1) |

Los colores de categoría aparecen apagados y solo en chips e iconos.

**Layout y componentes:**
- Una retícula de pósteres con aire, separada por filetes de 1 px en lugar de sombras.
- Radio de 8 px en las tarjetas y de 6 px en los campos.
- La nota aparece como un "sello" numérico en serif.
- En la ficha, dos columnas con el póster grande y *sticky* a la izquierda y el texto a la derecha (corrige F1).
- Las cabeceras de sección llevan el titular en serif y un enlace "Ver todo".
- El podio del ranking se dibuja como portada de revista: el primero grande y en dorado, el segundo y el tercero en tinta.
- El contador de kudos de la cabecera es una píldora dorada con cifra tabular de 14 px.

**Referencia real:** [criterion.com](https://www.criterion.com/), un catálogo editorial con serif, rejilla limpia y la imagen como protagonista.

---

## B. "Sala oscura"

**Concepto:** el cine con las luces apagadas. Es una interfaz oscura de serie para que los pósteres brillen, y la versión clara queda como alternativa.

**Tipografía:**
- Titulares en **Archivo**: variable con eje de anchura, condensada en los titulares para que parezcan carteles.
- Texto en **Inter**.

**Paleta:**

| Token | Oscuro (por defecto) | Claro |
|---|---|---|
| Lienzo | `#0F1115` | `#F6F5F2` |
| Superficie | `#181B22` | `#FFFFFF` |
| Texto | `#F2F1EE` (16,7:1) | `#15171C` (16,5:1) |
| Texto secundario | `#A1A1AA` (6,7:1) | `#52525B` (7,1:1) |
| Acento ámbar (también kudos) | `#F5B83D` (10,6:1); fondo oscuro encima, 10,6:1 | El texto pasa a `#8A5200` (5,9:1); botones en `#F5B83D` con tinta (10,1:1) |
| Secundario | `#7DD3FC` | `#0369A1` (5,9:1) |
| Borde | `#2A2E37`; el de campo, `#6B7080` (3,5:1) | `#E4E4E7` |

**Layout y componentes:**
- En la ficha, un *hero* a sangre con la imagen difuminada de fondo.
- Filas tipo plataforma de *streaming*, con flechas.
- Tarjetas de 12 px de radio que, al pasar el ratón, se elevan con un brillo.
- El ranking como marcador iluminado en ámbar.

**Pegas:**
- El acento y los kudos comparten color, así que estos destacan menos.
- Los mapas de OSM y las fotos de Commons quedan muy claras sobre negro.
- Los textos legales largos se leen peor en el modo por defecto.
- Recuerda mucho a las plataformas de *streaming* y a Letterboxd.

**Referencia real:** [letterboxd.com](https://letterboxd.com/).

---

## C. "Pasaporte"

**Concepto:** un tono amable y lúdico. Cada categoría tiene su color, y cada interacción deja un "sello" como los de un pasaporte. Los kudos y las insignias son el centro de la experiencia.

**Tipografía:**
- Titulares en **Bricolage Grotesque**: variable, expresiva y redondeada.
- Texto en **Figtree**.

**Paleta:**

| Token | Claro | Oscuro |
|---|---|---|
| Lienzo | `#FFFCF5` | `#17151F` |
| Superficie | `#FFFFFF` | `#211E2B` |
| Tinta | `#1E1B2E` (16,4:1) | `#F4F2FA` (16,3:1) |
| Texto secundario | `#5B5670` (6,8:1) | `#A9A4BC` (6,8:1) |
| Pelis | `#E11D48`, con blanco 4,7:1 | `#FB7185`, con tinta 6,7:1 |
| Series | `#7C3AED` (5,7:1) | `#A78BFA` (6,6:1) |
| Juegos | `#047857` (5,5:1) | `#34D399` (9,4:1) |
| Destinos | `#0369A1` (5,9:1) | `#38BDF8` (8,4:1) |
| Kudos | `#FBBF24`, con tinta 10:1 | `#FBBF24` (10,8:1) |

**Layout y componentes:**
- Tarjetas de 16 px de radio con un borde de 2 px del color de su categoría.
- Chips gruesos, e insignias y sellos para los kudos y la *wishlist*.
- Microanimaciones al votar.
- El ranking como tabla de "liga".

**Pegas:**
- Cuatro colores fuertes compiten con los pósteres, y una lista mixta se ve como un arcoíris.
- Envejece antes que las otras dos.
- Es la que más esfuerzo de movimiento necesita para no resultar infantil.

**Referencia real:** [duolingo.com](https://www.duolingo.com/), con su color por sección y su gamificación amable.

---

## Recomendación: A, "Cuaderno"

Las respuestas del humano cambian el análisis inicial en dos puntos. Con el modo oscuro dentro del rediseño, B deja de chocar con la visión. Con los kudos destacados, C gana peso. Aun así, se recomienda A:

1. **Encaja con "tu diario".** El contenido manda, y una lista que mezcla pósteres de cine y de juegos con fotos de ciudades queda coherente sobre papel neutro. En C se volvería un mosaico de colores, y en B las fotos claras chocarían con el negro.
2. **Destaca los kudos sin pelearse con nada.** Como la interfaz es sobria, el dorado es lo único que brilla: el contador, el podio y las recompensas se ven sin necesidad de más color. En B, el ámbar también es el acento general y pierde fuerza.
3. **Hace bien los dos modos.** El "cuaderno de noche" mantiene la calidez, con negro cálido y teja aclarada, y no es solo un negro genérico.
4. **Se distingue** de las webs de *streaming* oscuras (B) y de las apps gamificadas (C).
5. **Es la más fácil de mantener en AA** y la que menos movimiento necesita, lo que también ayuda a respetar `prefers-reduced-motion`.

Se pueden adoptar ideas de las otras dos: las flechas en las filas horizontales (de B) y el color de categoría apagado en chips e iconos (de C).

**Siguiente paso:** en la fase R1 se hacen maquetas HTML de las tres direcciones (portada, ficha y votar, en móvil y escritorio, en claro y oscuro) para elegir viéndolas, no solo leyendo esta descripción.
