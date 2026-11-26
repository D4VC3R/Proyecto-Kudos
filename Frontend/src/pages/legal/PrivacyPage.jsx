import React from 'react';
import {useLegalPage} from '../../hooks/pages/useLegalPage';
import LegalPageLayout from '../../components/legal/LegalPageLayout.jsx';
import LegalPending from '../../components/legal/LegalPending.jsx';
import LegalContactEmail from '../../components/legal/LegalContactEmail.jsx';

const H2 = 'mb-2 text-lg font-bold text-text-highlight';

// Política de privacidad (F3-07), texto aprobado el 2026-11-01. Los datos que completa el titular fuera del código van como <LegalPending>.
const PrivacyPage = () => {
  const {state} = useLegalPage();
  const email = <LegalContactEmail email={state.contactEmail} isLoading={state.isLoading}/>;

  return (
    <LegalPageLayout title="Política de privacidad">
      <section>
        <h2 className={H2}>1. Responsable del tratamiento</h2>
        <p>David Cerdán Valero.</p>
        <p className="mt-2">Contacto: {email}</p>
      </section>

      <section>
        <h2 className={H2}>2. Qué datos tratamos</h2>
        <ul className="list-disc pl-5">
          <li>
            Datos de la cuenta: nombre de usuario, correo electrónico, contraseña (guardada cifrada con <i>hash</i>) y la
            fecha en que aceptaste esta política.
          </li>
          <li>
            Datos del perfil que decidas rellenar: avatar, biografía, ciudad, fecha de nacimiento e intereses.
          </li>
          <li>
            Tu actividad: votos y valoraciones, <i>wishlist</i>, comentarios y reseñas, listas, kudos, reportes que envíes
            y notificaciones.
          </li>
          <li>
            Datos técnicos: la dirección IP, que aparece en los registros (<i>logs</i>) del servidor y se usa para limitar
            el número de peticiones.
          </li>
        </ul>
      </section>

      <section>
        <h2 className={H2}>3. Para qué los usamos</h2>
        <p>
          Para prestar el servicio (cuenta, votaciones, rankings, recomendaciones a partir de tus votos y notificaciones
          dentro de la aplicación), moderar el contenido (reportes) y mantener la seguridad. No hay publicidad, no
          vendemos tus datos y no usamos analítica de terceros.
        </p>
      </section>

      <section>
        <h2 className={H2}>4. Base legal</h2>
        <p>
          Para tu cuenta y tu actividad, la ejecución del servicio que solicitas al registrarte (art. 6.1.b RGPD). Para
          los registros (<i>logs</i>) del servidor y los límites de peticiones, el interés legítimo (art. 6.1.f RGPD) en
          la seguridad del servicio y la prevención de abusos.
        </p>
      </section>

      <section>
        <h2 className={H2}>5. Cuánto tiempo los conservamos</h2>
        <p>
          Mientras tu cuenta exista. Puedes pedir que se suprima escribiendo al correo de contacto ({email}).
        </p>
        <ul className="mt-2 list-disc pl-5">
          <li>
            Registros del servidor: se rotan por tamaño (hasta 3 ficheros de 10 MB), así que solo se conservan los más
            recientes.{' '}
            <LegalPending>confirmar la rotación de los registros en el servidor (la documentación indica Docker, 10 MB × 3 ficheros)</LegalPending>
          </li>
          <li>Registro de auditoría de moderación (acciones sobre reportes): 30 días.</li>
          <li>Copias de seguridad (base de datos e imágenes): 7 días.</li>
        </ul>
      </section>

      <section>
        <h2 className={H2}>6. Encargados del tratamiento</h2>
        <p>
          Vercel (alojamiento de la web) y OVH (servidor de la API y de la base de datos). No cedemos tus datos a
          terceros. Vercel, que aloja el frontend, tiene sede en EE. UU., por lo que su tratamiento puede implicar una
          transferencia internacional de datos; se ampara en las garantías del Marco de Privacidad de Datos UE-EE. UU.
          y, en su caso, en las cláusulas contractuales tipo de la Comisión Europea.
        </p>
      </section>

      <section>
        <h2 className={H2}>7. Servicios de terceros que carga tu navegador</h2>
        <p>
          Al abrir la ficha de un destino, tu navegador descarga el mapa (las teselas) directamente de los servidores de
          la Fundación OpenStreetMap (<code>tile.openstreetmap.org</code>), que reciben tu dirección IP y los datos
          técnicos de cualquier petición web (navegador y página de origen). Solo ocurre en las fichas con mapa. La
          Fundación OpenStreetMap, con sede en el Reino Unido (país con decisión de adecuación de la Comisión Europea),
          trata esos datos según su{' '}
          <a
            href="https://osmfoundation.org/wiki/Privacy_Policy"
            target="_blank"
            rel="noopener noreferrer"
            className="font-bold text-primary hover:underline"
          >
            política de privacidad
          </a>.
        </p>
        <p className="mt-2">
          La tipografía de la web se sirve desde nuestro propio servidor: no se contacta con Google ni con otros
          proveedores de fuentes.
        </p>
      </section>

      <section>
        <h2 className={H2}>8. Tus derechos</h2>
        <p>
          Puedes ejercer los derechos de acceso, rectificación, supresión, oposición, limitación y portabilidad
          escribiendo al correo de contacto ({email}). También puedes reclamar ante la{' '}
          <a
            href="https://www.aepd.es"
            target="_blank"
            rel="noopener noreferrer"
            className="font-bold text-primary hover:underline"
          >
            Agencia Española de Protección de Datos
          </a>.
        </p>
      </section>

      <section>
        <h2 className={H2}>9. Edad mínima</h2>
        <p>Para registrarte debes tener 14 años o más (edad de consentimiento digital en España).</p>
      </section>

      <section>
        <h2 className={H2}>10. Cookies y almacenamiento local</h2>
        <p>
          No usamos cookies de terceros ni analítica. La sesión se guarda en el almacenamiento local del navegador
          (<code>localStorage</code>) solo para mantenerte conectado.
        </p>
      </section>

      <p className="text-sm">
        Última actualización: 26 de noviembre de 2026.
      </p>
    </LegalPageLayout>
  );
};

export default PrivacyPage;
