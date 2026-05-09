import React from 'react';
import { CheckCircle, XCircle } from 'lucide-react';
import { clsx } from 'clsx';
import { AnimatedCard } from '../animations/AnimatedCard.jsx';
import {statusColors} from '../../lib/constants.js';
import { Button } from '../common/Button';


export const AdminProposalCard = ({ proposal, onAccept, onReject }) => {
  return (
    <AnimatedCard className="bg-white border border-slate-100 shadow-sm hover:shadow-md transition-shadow rounded-3xl p-5 flex flex-col gap-3 group relative overflow-hidden">
      <div className="flex justify-between items-start gap-2">
        <h3 className="font-black text-slate-800 line-clamp-1">{proposal.name}</h3>
        <span className={clsx("px-2 py-0.5 rounded-lg text-[10px] font-black uppercase whitespace-nowrap", statusColors[proposal.status])}>
          {proposal.status}
        </span>
      </div>
      <p className="text-sm text-slate-600 line-clamp-2 mt-1">{proposal.description}</p>

      <div className="flex flex-col gap-1 mt-2 text-xs font-medium text-slate-500">
        <div>Por: <span className="font-bold text-slate-700">{proposal.creator?.name}</span></div>
        <div>Cat: <span className="font-bold text-slate-700">{proposal.category?.name}</span></div>
      </div>

      {proposal.status === 'pending' && (
        <div className="flex gap-2 mt-auto pt-4 border-t border-slate-100">
          <Button
            onClick={() => onAccept(proposal)}
            variant="ghost"
            color="success"
            icon={CheckCircle}
            isFullWidth={true}
          >
            Aceptar
          </Button>
          <Button
            onClick={() => onReject(proposal)}
            variant="ghost"
            color="danger"
            icon={XCircle}
            isFullWidth={true}
          >
            Rechazar
          </Button>
        </div>
      )}
    </AnimatedCard>
  );
};