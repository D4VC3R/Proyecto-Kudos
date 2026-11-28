import React from 'react';
import { Download, Trash2 } from 'lucide-react';
// Componentes
import FadeUp from '../../components/animations/FadeUp.jsx';
import Button from '../../components/ui/Button.jsx';
import Card from '../../components/ui/Card.jsx';
import Input from '../../components/ui/Input.jsx';
import Modal from '../../components/ui/Modal.jsx';
import ModalButtons from '../../components/ui/ModalButtons.jsx';
// Hooks
import { useProfilePrivacyPage } from '../../hooks/pages/useProfilePrivacyPage.js';

// Pestaña "Privacidad" del perfil (F3-08): descarga de datos y borrado de la cuenta (RGPD).
const ProfilePrivacyPage = () => {
  const { state, actions } = useProfilePrivacyPage();

  return (
    <FadeUp className="flex flex-col gap-6">
      <Card className="flex flex-col gap-4 p-6 md:p-8">
        <h2 className="font-display text-h3 text-ink">Descargar mis datos</h2>
        <p className="text-small text-ink-muted">
          Descarga un archivo JSON con tu cuenta, tu perfil, tus votos y tu wishlist, tus comentarios y reseñas,
          tus listas, los «me gusta» que has dado, tus propuestas y tu historial de kudos.
        </p>
        <div>
          <Button variant="secondary" icon={Download} isLoading={state.isExporting} onClick={actions.handleExport}>
            Descargar mis datos
          </Button>
        </div>
      </Card>

      <Card className="flex flex-col gap-4 border-danger/40 p-6 md:p-8">
        <h2 className="font-display text-h3 text-ink">Borrar mi cuenta</h2>
        <p className="text-small text-ink-muted">
          Se borran tu cuenta, tu perfil y tu avatar, tus votos y tu wishlist, tus listas, tus «me gusta», tus kudos,
          tus propuestas y tus notificaciones. Los comentarios que tengan respuestas de otras personas se conservan
          como «[comentario eliminado]», sin tu nombre; el resto se borran. No se puede deshacer.
        </p>
        <div>
          <Button variant="danger" icon={Trash2} onClick={actions.openDelete}>
            Borrar mi cuenta
          </Button>
        </div>
      </Card>

      <Modal
        isOpen={state.step !== null}
        onClose={actions.closeDelete}
        title="Borrar mi cuenta"
        footer={
          state.step === 'password' ? (
            <ModalButtons
              onClose={actions.closeDelete}
              onConfirm={actions.confirmDelete}
              isPending={state.isDeleting}
              confirmText="Borrar definitivamente"
              actionStyle="danger"
            />
          ) : (
            <ModalButtons
              onClose={actions.closeDelete}
              onConfirm={actions.goToPassword}
              confirmText="Sí, continuar"
              actionStyle="danger"
            />
          )
        }
      >
        {state.step === 'password' ? (
          <div className="flex flex-col gap-4">
            <p className="text-ink">
              Para confirmar, escribe tu contraseña. Esta acción es definitiva.
            </p>
            <Input
              label="Contraseña"
              type="password"
              autoComplete="current-password"
              value={state.password}
              onChange={(event) => actions.setPassword(event.target.value)}
              onKeyDown={(event) => {
                if (event.key === 'Enter') actions.confirmDelete();
              }}
              error={state.passwordError}
              disabled={state.isDeleting}
            />
          </div>
        ) : (
          <p className="text-ink">
            ¿Seguro que quieres borrar tu cuenta? Perderás todos tus datos y no podrás recuperarlos.
          </p>
        )}
      </Modal>
    </FadeUp>
  );
};

export default ProfilePrivacyPage;
