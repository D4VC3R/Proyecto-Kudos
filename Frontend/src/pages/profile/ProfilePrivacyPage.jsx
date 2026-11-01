import React from 'react';
import { Download, Trash2 } from 'lucide-react';
// Componentes
import FadeUp from '../../components/animations/FadeUp.jsx';
import Button from '../../components/ui/Button.jsx';
import InputField from '../../components/ui/InputField.jsx';
import Modal from '../../components/ui/Modal.jsx';
import ModalButtons from '../../components/ui/ModalButtons.jsx';
// Hooks
import { useProfilePrivacyPage } from '../../hooks/pages/useProfilePrivacyPage.js';

// Pestaña "Privacidad" del perfil (F3-08): descarga de datos y borrado de la cuenta (RGPD).
const ProfilePrivacyPage = () => {
  const { state, actions } = useProfilePrivacyPage();

  return (
    <FadeUp className="flex flex-col gap-6">
      <div className="bg-surface rounded-3xl p-6 md:p-8 shadow-sm border border-border flex flex-col gap-4">
        <h3 className="text-xl font-bold text-text-highlight">Descargar mis datos</h3>
        <p className="text-sm text-text-normal">
          Descarga un archivo JSON con tu cuenta, tu perfil, tus votos y tu wishlist, tus comentarios y reseñas,
          tus listas, los «me gusta» que has dado, tus propuestas y tu historial de kudos.
        </p>
        <div>
          <Button icon={Download} isLoading={state.isExporting} onClick={actions.handleExport}>
            Descargar mis datos
          </Button>
        </div>
      </div>

      <div className="bg-surface rounded-3xl p-6 md:p-8 shadow-sm border border-red-200 flex flex-col gap-4">
        <h3 className="text-xl font-bold text-text-highlight">Borrar mi cuenta</h3>
        <p className="text-sm text-text-normal">
          Se borran tu cuenta, tu perfil y tu avatar, tus votos y tu wishlist, tus listas, tus «me gusta», tus kudos,
          tus propuestas y tus notificaciones. Los comentarios que tengan respuestas de otras personas se conservan
          como «[comentario eliminado]», sin tu nombre; el resto se borran. No se puede deshacer.
        </p>
        <div>
          <Button variant="solid" color="danger" icon={Trash2} onClick={actions.openDelete}>
            Borrar mi cuenta
          </Button>
        </div>
      </div>

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
            <p className="text-sm text-text-normal">
              Para confirmar, escribe tu contraseña. Esta acción es definitiva.
            </p>
            <InputField
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
          <p className="text-sm text-text-normal">
            ¿Seguro que quieres borrar tu cuenta? Perderás todos tus datos y no podrás recuperarlos.
          </p>
        )}
      </Modal>
    </FadeUp>
  );
};

export default ProfilePrivacyPage;
