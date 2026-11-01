import React from 'react';
import {useLegalPage} from '../../hooks/pages/useLegalPage';
import LegalPageLayout from '../../components/legal/LegalPageLayout.jsx';
import LegalContactEmail from '../../components/legal/LegalContactEmail.jsx';

const H2 = 'mb-2 text-lg font-bold text-text-highlight';
const LINK = 'font-bold text-primary hover:underline';

// Aviso legal (F3-07), texto aprobado el 2026-11-01. Los datos que completa el titular fuera del código van como <LegalPending>.
const LegalNoticePage = () => {
  const {state} = useLegalPage();

  return (
    <LegalPageLayout title="Aviso legal">
      <section>
        <h2 className={H2}>1. Titular</h2>
        <p>David Cerdán Valero.</p>
        <p className="mt-2">Dominio: kudos.dcerdan.es</p>
      </section>

      <section>
        <h2 className={H2}>2. Qué es Kudos</h2>
        <p>
          Proyecto sin ánimo de lucro, Proyecto Final del ciclo de Desarrollo de Aplicaciones Web (IES Paco Mollà). No
          hay publicidad, afiliados ni funciones de pago.
        </p>
      </section>

      <section>
        <h2 className={H2}>3. Contacto</h2>
        <p>
          Punto de contacto: <LegalContactEmail email={state.contactEmail} isLoading={state.isLoading}/>. Sirve para
          notificar contenido ilegal (además del botón «Reportar» de comentarios, reseñas y listas, disponible para
          usuarios registrados) y para reclamar una decisión de moderación.
        </p>
      </section>

      <section>
        <h2 className={H2}>4. Contenido de los usuarios y moderación</h2>
        <p>
          Cada usuario es responsable de lo que publica. Los reportes se revisan en una cola de moderación; si se oculta
          o se borra contenido, el autor recibe una notificación con el motivo y cómo reclamar.
        </p>
      </section>

      <section>
        <h2 className={H2}>5. Propiedad intelectual y fuentes de datos</h2>
        <ul className="list-disc pl-5">
          <li>
            Datos de películas y series de TMDB. <span lang="en">This website uses TMDB and the TMDB APIs but is not
            endorsed, certified, or otherwise approved by TMDB.</span>{' '}
            Esta web usa TMDB y sus API, pero TMDB no la respalda, certifica ni aprueba.
          </li>
          <li>
            Descripciones de Wikipedia bajo licencia{' '}
            <a
              href="https://creativecommons.org/licenses/by-sa/4.0/deed.es"
              target="_blank"
              rel="noopener noreferrer"
              className={LINK}
            >
              CC BY-SA 4.0
            </a>.
          </li>
          <li>Las portadas son © de sus titulares y se muestran solo para ilustrar las fichas.</li>
          <li>
            El código fuente se publica bajo licencia MIT en{' '}
            <a
              href="https://github.com/D4VC3R/Proyecto-Kudos"
              target="_blank"
              rel="noopener noreferrer"
              className={LINK}
            >
              github.com/D4VC3R/Proyecto-Kudos
            </a>.
          </li>
        </ul>
      </section>

      <section>
        <h2 className={H2}>6. Responsabilidad y legislación aplicable</h2>
        <p>
          Kudos se ofrece «tal cual», sin garantías de disponibilidad continua ni de que los datos de las fuentes
          externas o el contenido de los usuarios sean exactos o estén completos; el titular no responde de los daños
          derivados de su uso, salvo en los casos en que la ley no permita excluir esa responsabilidad. Este aviso se
          rige por la legislación española. Cuando el usuario sea consumidor, serán competentes los juzgados y
          tribunales de su domicilio.
        </p>
      </section>

      <p className="text-sm">
        Última actualización: 1 de noviembre de 2026.
      </p>
    </LegalPageLayout>
  );
};

export default LegalNoticePage;
